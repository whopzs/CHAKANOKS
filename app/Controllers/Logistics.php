<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\DeliveryModel;
use App\Models\DeliveryItemModel;
use App\Models\PurchaseOrderModel;
use App\Models\InventoryModel;

class Logistics extends Controller
{
    protected $deliveryModel;
    protected $deliveryItemModel;
    protected $purchaseOrderModel;
    protected $inventoryModel;

    public function __construct()
    {
        $this->deliveryModel = new DeliveryModel();
        $this->deliveryItemModel = new DeliveryItemModel();
        $this->purchaseOrderModel = new PurchaseOrderModel();
        $this->inventoryModel = new InventoryModel();
    }

    public function index()
    {
        // Get PO IDs that already have deliveries
        $db = \Config\Database::connect();
        $scheduledPOIds = $db->table('deliveries')
                            ->select('purchase_order_id')
                            ->get()
                            ->getResultArray();
        $scheduledPOIds = array_column($scheduledPOIds, 'purchase_order_id');
        
        // Get approved purchase orders that haven't been scheduled yet
        $builder = $this->purchaseOrderModel
            ->select('purchase_orders.*, suppliers.company_name as supplier_name, branches.branch_name, users.first_name, users.last_name')
            ->join('suppliers', 'suppliers.id = purchase_orders.supplier_id', 'left')
            ->join('branches', 'branches.id = purchase_orders.branch_id', 'left')
            ->join('users', 'users.id = purchase_orders.requested_by', 'left')
            ->where('purchase_orders.status', 'approved');
        
        if (!empty($scheduledPOIds)) {
            $builder->whereNotIn('purchase_orders.id', $scheduledPOIds);
        }
        
        $approvedPOs = $builder->orderBy('purchase_orders.created_at', 'DESC')->findAll();
        
        // Get all scheduled/active deliveries
        $deliveries = $this->deliveryModel
            ->select('deliveries.*, suppliers.company_name as supplier_name, branches.branch_name, purchase_orders.po_number')
            ->join('suppliers', 'suppliers.id = deliveries.supplier_id', 'left')
            ->join('branches', 'branches.id = deliveries.branch_id', 'left')
            ->join('purchase_orders', 'purchase_orders.id = deliveries.purchase_order_id', 'left')
            ->orderBy('deliveries.created_at', 'DESC')
            ->findAll();
        
        // Get delivery statistics
        $stats = $this->deliveryModel->getDeliveryStatistics();
        
        return view('logistics/dashboard', [
            'approvedPOs' => $approvedPOs,
            'deliveries' => $deliveries,
            'stats' => $stats
        ]);
    }

    // API: Create/schedule a delivery from an approved PO
    public function scheduleDelivery()
    {
        $payload = $this->request->getJSON(true) ?? $this->request->getPost();

        $poId = (int) ($payload['purchase_order_id'] ?? 0);
        $scheduledDate = $payload['scheduled_date'] ?? null;
        $driverName = $payload['driver_name'] ?? null;
        $vehicleNumber = $payload['vehicle_number'] ?? null;
        $notes = $payload['notes'] ?? null;

        if ($poId <= 0 || !$scheduledDate) {
            return $this->response->setJSON(['success' => false, 'error' => 'Missing required data'])->setStatusCode(400);
        }

        $po = $this->purchaseOrderModel->find($poId);
        if (!$po || !in_array($po['status'], ['approved', 'ordered'], true)) {
            return $this->response->setJSON(['success' => false, 'error' => 'PO not approved or not found'])->setStatusCode(422);
        }

        $deliveryData = [
            'delivery_number' => $this->deliveryModel->generateDeliveryNumber(),
            'purchase_order_id' => $poId,
            'supplier_id' => $po['supplier_id'],
            'branch_id' => $po['branch_id'],
            'status' => 'scheduled',
            'scheduled_date' => $scheduledDate,
            'driver_name' => $driverName,
            'vehicle_number' => $vehicleNumber,
            'notes' => $notes,
        ];

        $deliveryId = $this->deliveryModel->insert($deliveryData);
        if (!$deliveryId) {
            return $this->response->setJSON(['success' => false, 'error' => 'Failed to create delivery'])->setStatusCode(500);
        }

        // Create delivery items from purchase order items
        $poItemModel = new \App\Models\PurchaseOrderItemModel();
        $poItems = $poItemModel->getItemsByPurchaseOrder($poId);
        
        if (empty($poItems)) {
            // If no PO items exist, log a warning but don't fail
            log_message('warning', "No purchase order items found for PO ID: {$poId}");
        } else {
            foreach ($poItems as $poItem) {
                $deliveryItemData = [
                    'delivery_id' => $deliveryId,
                    'product_id' => $poItem['product_id'],
                    'expected_quantity' => $poItem['quantity'],
                    'received_quantity' => 0, // Will be updated when received
                    'unit_cost' => $poItem['unit_price'],
                    'condition_status' => 'good', // Default, can be updated on receipt
                ];
                
                if (!$this->deliveryItemModel->insert($deliveryItemData)) {
                    log_message('error', "Failed to create delivery item for product ID: {$poItem['product_id']}");
                }
            }
        }

        // Optionally mark PO as ordered
        if ($po['status'] === 'approved') {
            $this->purchaseOrderModel->update($poId, ['status' => 'ordered']);
        }

        return $this->response->setJSON(['success' => true, 'delivery_id' => $deliveryId]);
    }

    // API: Update delivery status (scheduled -> in_transit -> delivered/cancelled)
    public function updateDeliveryStatus($deliveryId)
    {
        $payload = $this->request->getJSON(true) ?? $this->request->getPost();
        $status = $payload['status'] ?? '';

        if (!in_array($status, ['scheduled', 'in_transit', 'delivered', 'cancelled'], true)) {
            return $this->response->setJSON(['success' => false, 'error' => 'Invalid status'])->setStatusCode(400);
        }

        $ok = $this->deliveryModel->updateStatus((int) $deliveryId, $status);
        if (!$ok) {
            return $this->response->setJSON(['success' => false, 'error' => 'Failed to update status'])->setStatusCode(500);
        }

        return $this->response->setJSON(['success' => true]);
    }

    // API: List deliveries with optional branch filter
    public function listDeliveries()
    {
        $branchId = (int) ($this->request->getGet('branch_id') ?? 0);
        if ($branchId > 0) {
            $data = $this->deliveryModel->getDeliveriesByBranch($branchId);
        } else {
            $data = $this->deliveryModel->orderBy('created_at', 'DESC')->findAll();
        }
        return $this->response->setJSON(['success' => true, 'data' => $data]);
    }
}
