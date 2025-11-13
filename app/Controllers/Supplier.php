<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\PurchaseOrderModel;
use App\Models\PurchaseOrderItemModel;
use App\Models\SupplierModel;

class Supplier extends Controller
{
    protected $purchaseOrderModel;
    protected $purchaseOrderItemModel;
    protected $supplierModel;

    public function __construct()
    {
        $this->purchaseOrderModel = new PurchaseOrderModel();
        $this->purchaseOrderItemModel = new PurchaseOrderItemModel();
        $this->supplierModel = new SupplierModel();
    }

    public function index()
    {
        if (!session()->get('is_logged_in') || session()->get('role') !== 'supplier') {
            return redirect()->to(base_url('login'));
        }

        // Get supplier_id from session (set during login)
        $supplierId = session()->get('supplier_id');
        
        // If not in session, try to find by email
        if (!$supplierId) {
            $userEmail = session()->get('email');
            if ($userEmail) {
                $supplier = $this->supplierModel->where('email', $userEmail)->first();
                if ($supplier) {
                    $supplierId = $supplier['id'];
                    // Save to session for next time
                    session()->set('supplier_id', $supplierId);
                }
            }
        }
        
        if (!$supplierId) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Supplier account not linked to a supplier record. Please contact administrator.');
        }
        
        // Get orders for this supplier
        $pendingOrders = $this->purchaseOrderModel
            ->select('purchase_orders.*, branches.branch_name, users.first_name, users.last_name')
            ->join('branches', 'branches.id = purchase_orders.branch_id')
            ->join('users', 'users.id = purchase_orders.requested_by')
            ->where('purchase_orders.supplier_id', $supplierId)
            ->where('purchase_orders.status', 'approved')
            ->orderBy('purchase_orders.created_at', 'DESC')
            ->findAll();

        $activeOrders = $this->purchaseOrderModel
            ->select('purchase_orders.*, branches.branch_name')
            ->join('branches', 'branches.id = purchase_orders.branch_id')
            ->where('purchase_orders.supplier_id', $supplierId)
            ->whereIn('purchase_orders.status', ['approved', 'ordered'])
            ->orderBy('purchase_orders.created_at', 'DESC')
            ->findAll();

        $completedOrders = $this->purchaseOrderModel
            ->select('purchase_orders.*, branches.branch_name')
            ->join('branches', 'branches.id = purchase_orders.branch_id')
            ->where('purchase_orders.supplier_id', $supplierId)
            ->where('purchase_orders.status', 'delivered')
            ->orderBy('purchase_orders.updated_at', 'DESC')
            ->limit(10)
            ->findAll();

        $data = [
            'pendingOrders' => $pendingOrders,
            'activeOrders' => $activeOrders,
            'completedOrders' => $completedOrders,
            'user' => [
                'name' => session()->get('first_name') . ' ' . session()->get('last_name'),
                'role' => session()->get('role')
            ]
        ];

        return view('supplier/dashboard', $data);
    }

    // API: Get Order Details
    public function getOrderDetails($poId)
    {
        if (!session()->get('is_logged_in') || session()->get('role') !== 'supplier') {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized'])->setStatusCode(401);
        }

        // Get supplier_id from session (set during login)
        $supplierId = session()->get('supplier_id');
        
        // If not in session, try to find by email
        if (!$supplierId) {
            $userEmail = session()->get('email');
            if ($userEmail) {
                $supplier = $this->supplierModel->where('email', $userEmail)->first();
                if ($supplier) {
                    $supplierId = $supplier['id'];
                    // Save to session for next time
                    session()->set('supplier_id', $supplierId);
                }
            }
        }
        
        if (!$supplierId) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Supplier account not linked to a supplier record. Please contact administrator.');
        }
        
        $po = $this->purchaseOrderModel
            ->select('purchase_orders.*, suppliers.company_name, branches.branch_name, users.first_name, users.last_name')
            ->join('suppliers', 'suppliers.id = purchase_orders.supplier_id')
            ->join('branches', 'branches.id = purchase_orders.branch_id')
            ->join('users', 'users.id = purchase_orders.requested_by')
            ->where('purchase_orders.id', $poId)
            ->where('purchase_orders.supplier_id', $supplierId)
            ->first();

        if (!$po) {
            return $this->response->setJSON(['success' => false, 'message' => 'Order not found']);
        }

        $items = $this->purchaseOrderItemModel->getItemsByPurchaseOrder($poId);

        return $this->response->setJSON([
            'success' => true,
            'po' => $po,
            'items' => $items
        ]);
    }

    // API: Update Order Status (for supplier to mark as ready/shipped)
    public function updateOrderStatus($poId)
    {
        if (!session()->get('is_logged_in') || session()->get('role') !== 'supplier') {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized'])->setStatusCode(401);
        }

        // Get supplier_id from session (set during login)
        $supplierId = session()->get('supplier_id');
        
        // If not in session, try to find by email
        if (!$supplierId) {
            $userEmail = session()->get('email');
            if ($userEmail) {
                $supplier = $this->supplierModel->where('email', $userEmail)->first();
                if ($supplier) {
                    $supplierId = $supplier['id'];
                    // Save to session for next time
                    session()->set('supplier_id', $supplierId);
                }
            }
        }
        
        if (!$supplierId) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Supplier account not linked to a supplier record. Please contact administrator.');
        }
        $payload = $this->request->getJSON(true) ?? $this->request->getPost();
        $status = $payload['status'] ?? '';

        // Verify supplier owns this order
        $po = $this->purchaseOrderModel
            ->where('id', $poId)
            ->where('supplier_id', $supplierId)
            ->first();

        if (!$po) {
            return $this->response->setJSON(['success' => false, 'message' => 'Order not found']);
        }

        // Supplier can only update status to 'ordered' (ready to ship) if currently 'approved'
        if ($status === 'ordered' && $po['status'] === 'approved') {
            $result = $this->purchaseOrderModel->update($poId, [
                'status' => 'ordered',
                'expected_delivery' => $payload['expected_delivery'] ?? null
            ]);

            if ($result) {
                return $this->response->setJSON(['success' => true, 'message' => 'Order status updated']);
            }
        }

        return $this->response->setJSON(['success' => false, 'message' => 'Invalid status update']);
    }
}

