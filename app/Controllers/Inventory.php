<?php

namespace App\Controllers;

use App\Models\InventoryModel;
use App\Models\ProductModel;
use App\Models\StockMovementModel;
use App\Models\InventoryReportModel;
use App\Models\DamageExpiredItemModel;
use App\Models\DeliveryItemModel;
use App\Models\DeliveryModel;

class Inventory extends BaseController
{
    protected $inventoryModel;
    protected $reportModel;
    protected $damageModel;
    protected $deliveryModel;
    protected $stockMovementModel;
    protected $db;
    protected $deliveryItemModel;

    public function __construct()
    {
        $this->inventoryModel = new InventoryModel();
        $this->productModel = new ProductModel();
        $this->stockMovementModel = new StockMovementModel();
        $this->reportModel = new InventoryReportModel();
        $this->damageModel = new DamageExpiredItemModel();
        $this->deliveryItemModel = new DeliveryItemModel();
        $this->deliveryModel = new DeliveryModel();
        $this->db = \Config\Database::connect();
    }

    public function dashboard()
    {
        return view('inventory/dashboard');
    }

    public function stockLevels()
    {
        return view('inventory/stock_levels');
    }

    public function reports()
    {
        $data['recent_reports'] = $this->reportModel->getRecentReports(10);
        return view('inventory/reports', $data);
    }

    public function deliveries()
    {
        return view('inventory/deliveries');
    }

    public function damagesExpired()
    {
        $data['damage_items'] = $this->damageModel->getDamageItemsByType('damaged');
        $data['expired_items'] = $this->damageModel->getDamageItemsByType('expired');
        return view('inventory/damages_expired', $data);
    }

    // API Methods for AJAX calls
    
    public function testApi()
    {
        // Set CORS headers for API access
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $this->response->setHeader('Access-Control-Allow-Headers', 'Content-Type');
        
        try {
            // Test database connection
            $productCount = $this->db->table('products')->countAllResults();
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'API is working',
                'product_count' => $productCount,
                'session_branch_id' => session('branch_id'),
                'timestamp' => date('Y-m-d H:i:s'),
                'base_url' => base_url(),
                'current_url' => current_url()
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function searchBarcode($barcode)
    {
        // Set CORS headers for API access
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $this->response->setHeader('Access-Control-Allow-Headers', 'Content-Type');
        
        try {
            if (empty($barcode)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Barcode is required'
                ]);
            }

            // Search for product by barcode
            $product = $this->productModel->getProductByBarcode($barcode);

            if ($product) {
                // Get inventory data for this product in current branch
                $branchId = session('branch_id') ?? 1;
                $inventory = $this->inventoryModel->where('product_id', $product['id'])
                                               ->where('branch_id', $branchId)
                                               ->first();

                $data = [
                    'id' => $product['id'],
                    'product_code' => $product['product_code'],
                    'product_name' => $product['product_name'],
                    'description' => $product['description'],
                    'category' => $product['category'],
                    'unit' => $product['unit'],
                    'unit_price' => $product['unit_price'],
                    'barcode' => $product['barcode'],
                    'is_perishable' => $product['is_perishable'],
                    'shelf_life_days' => $product['shelf_life_days'],
                    'current_stock' => $inventory ? $inventory['current_stock'] : 0,
                    'min_stock_level' => $inventory ? $inventory['min_stock_level'] : 0,
                    'reorder_point' => $inventory ? $inventory['reorder_point'] : 0
                ];

                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Product found',
                    'data' => $data
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Product not found for barcode: ' . $barcode
                ]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Barcode search error: ' . $e->getMessage()
            ]);
        }
    }

    public function getStockData()
    {
        // Set CORS headers for API access
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $this->response->setHeader('Access-Control-Allow-Headers', 'Content-Type');
        
        try {
            $branchId = session('branch_id') ?: 1; // Default to branch 1 if no session
            
            // Get all products and their inventory data for this branch
            $stocks = $this->db->table('products')
                              ->select('products.*, inventory.current_stock, inventory.min_stock_level, inventory.max_stock_level, inventory.reorder_point, inventory.id as inventory_id')
                              ->join('inventory', 'inventory.product_id = products.id AND inventory.branch_id = ' . $branchId, 'left')
                              ->where('products.is_active', 1)
                              ->get()
                              ->getResultArray();
            
            // Set default values for products without inventory records
            foreach ($stocks as &$stock) {
                if (is_null($stock['current_stock'])) {
                    $stock['current_stock'] = 0;
                    $stock['min_stock_level'] = 0;
                    $stock['max_stock_level'] = 0;
                    $stock['reorder_point'] = 0;
                    $stock['inventory_id'] = null;
                }
            }
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $stocks,
                'count' => count($stocks),
                'branch_id' => $branchId
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ]);
        }
    }

    public function getRecentReports()
    {
        $reports = $this->reportModel->getRecentReports(10, session('branch_id'));
        return $this->response->setJSON($reports);
    }

    public function saveStock()
    {
        // Set CORS headers for API access
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $this->response->setHeader('Access-Control-Allow-Headers', 'Content-Type');
        
        try {
            $data = $this->request->getJSON(true);
            $branchId = session('branch_id') ?: 1; // Default to branch 1 if no session
            
            // Debug log
            log_message('debug', 'Save stock data received: ' . json_encode($data));
            
            // Check if this is creating a new product or updating existing inventory
            if (isset($data['id']) && $data['id'] && isset($data['inventory_id']) && $data['inventory_id']) {
                // Update existing inventory record
                $inventoryData = [
                    'current_stock' => $data['current_stock'],
                    'min_stock_level' => $data['min_stock'],
                    'max_stock_level' => $data['max_stock'],
                    'reorder_point' => $data['min_stock'], // Use min_stock as reorder point
                    'last_updated' => date('Y-m-d H:i:s')
                ];
                
                if ($this->inventoryModel->update($data['inventory_id'], $inventoryData)) {
                    return $this->response->setJSON(['success' => true, 'message' => 'Stock updated successfully']);
                }
            } else if (isset($data['id']) && $data['id']) {
                // Create new inventory record for existing product
                $inventoryData = [
                    'product_id' => $data['id'],
                    'branch_id' => $branchId,
                    'current_stock' => $data['current_stock'],
                    'min_stock_level' => $data['min_stock'],
                    'max_stock_level' => $data['max_stock'],
                    'reorder_point' => $data['min_stock'],
                    'last_updated' => date('Y-m-d H:i:s')
                ];
                
                if ($this->inventoryModel->save($inventoryData)) {
                    return $this->response->setJSON(['success' => true, 'message' => 'Stock saved successfully']);
                }
            } else {
                // Create new product and inventory record
                $productData = [
                    'product_code' => $data['product_code'],
                    'product_name' => $data['product_name'],
                    'category' => $data['category'],
                    'unit_price' => $data['unit_price'],
                    'description' => $data['description'] ?? '',
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                if ($this->productModel->save($productData)) {
                    $productId = $this->productModel->getInsertID();
                    
                    // Create inventory record for new product
                    $inventoryData = [
                        'product_id' => $productId,
                        'branch_id' => $branchId,
                        'current_stock' => $data['current_stock'],
                        'min_stock_level' => $data['min_stock'],
                        'max_stock_level' => $data['max_stock'],
                        'reorder_point' => $data['min_stock'],
                        'last_updated' => date('Y-m-d H:i:s')
                    ];
                    
                    if ($this->inventoryModel->save($inventoryData)) {
                        return $this->response->setJSON(['success' => true, 'message' => 'New product and stock saved successfully']);
                    }
                }
            }
            
            return $this->response->setJSON([
                'success' => false, 
                'errors' => $this->inventoryModel->errors(),
                'debug_data' => $data
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function adjustStock()
    {
        // Set CORS headers for API access
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $this->response->setHeader('Access-Control-Allow-Headers', 'Content-Type');
        
        try {
            $data = $this->request->getJSON(true);
            $branchId = session('branch_id') ?: 1;
            
            // Debug log
            log_message('debug', 'Adjust stock data received: ' . json_encode($data));
            
            // Record stock movement
            $movementData = [
                'product_id' => $data['product_id'],
                'branch_id' => $branchId,
                'movement_type' => $data['type'], // 'in' or 'out'
                'quantity' => $data['quantity'],
                'reason' => $data['reason'],
                'notes' => $data['notes'],
                'created_by' => session('user_id') ?: 1,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            if ($this->stockMovementModel->save($movementData)) {
                // Update inventory stock
                $inventory = $this->inventoryModel->where('product_id', $data['product_id'])
                                                ->where('branch_id', $branchId)
                                                ->first();
                
                if ($inventory) {
                    $newStock = $data['type'] === 'in' ? 
                        $inventory['current_stock'] + $data['quantity'] : 
                        $inventory['current_stock'] - $data['quantity'];
                    
                    $this->inventoryModel->update($inventory['id'], ['current_stock' => $newStock]);
                }
                
                return $this->response->setJSON(['success' => true, 'message' => 'Stock adjusted successfully']);
            }
            
            return $this->response->setJSON([
                'success' => false, 
                'errors' => $this->stockMovementModel->errors(),
                'debug_data' => $data
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function generateReport()
    {
        $data = $this->request->getJSON(true);
        
        // Generate report data based on type
        $reportData = $this->generateReportData($data['report_type']);
        
        // Save report to database
        $reportRecord = [
            'report_type' => $data['report_type'],
            'report_name' => $data['report_name'],
            'branch_id' => session('branch_id'),
            'date_range_start' => $data['date_range_start'],
            'date_range_end' => $data['date_range_end'],
            'filters' => json_encode($data['filters']),
            'export_format' => $data['export_format'],
            'file_path' => 'reports/' . $data['report_type'] . '_' . date('Y-m-d_H-i-s') . '.' . $data['export_format'],
            'records_count' => count($reportData),
            'generated_by' => session('user_id')
        ];
        
        if ($this->reportModel->save($reportRecord)) {
            return $this->response->setJSON([
                'success' => true, 
                'message' => 'Report generated successfully',
                'data' => $reportData
            ]);
        }
        
        return $this->response->setJSON(['success' => false, 'errors' => 'Failed to save report']);
    }

    private function generateReportData($reportType)
    {
        $branchId = session('branch_id');
        
        switch ($reportType) {
            case 'stock':
                return $this->db->table('products')
                               ->select('products.product_code, products.product_name, products.category, inventory.current_stock, inventory.min_stock_level, inventory.max_stock_level')
                               ->join('inventory', 'inventory.product_id = products.id AND inventory.branch_id = ' . $branchId, 'left')
                               ->where('products.is_active', 1)
                               ->get()
                               ->getResultArray();
            
            case 'low_stock':
                return $this->db->table('products')
                               ->select('products.product_code, products.product_name, products.category, inventory.current_stock, inventory.min_stock_level')
                               ->join('inventory', 'inventory.product_id = products.id')
                               ->where('inventory.branch_id', $branchId)
                               ->where('inventory.current_stock <=', 'inventory.min_stock_level', false)
                               ->where('products.is_active', 1)
                               ->get()
                               ->getResultArray();
            
            case 'movement':
                return $this->db->table('stock_movements')
                               ->select('stock_movements.*, products.product_name, products.product_code')
                               ->join('products', 'products.id = stock_movements.product_id')
                               ->where('stock_movements.branch_id', $branchId)
                               ->orderBy('stock_movements.created_at', 'DESC')
                               ->limit(100)
                               ->get()
                               ->getResultArray();
            
            default:
                return [];
        }
    }

    private function generateReportContent($type, $filters = [])
    {
        switch ($type) {
            case 'stock_level':
                return $this->inventoryModel->select('inventory.*, products.product_name, products.product_code')
                                          ->join('products', 'products.id = inventory.product_id')
                                          ->where('inventory.branch_id', session('branch_id'))
                                          ->findAll();
            
            case 'low_stock':
                return $this->inventoryModel->select('inventory.*, products.product_name, products.product_code')
                                          ->join('products', 'products.id = inventory.product_id')
                                          ->where('inventory.branch_id', session('branch_id'))
                                          ->where('inventory.current_stock <=', 'inventory.min_stock', false)
                                          ->findAll();
            
            case 'damage':
                return $this->damageModel->getDamageItemsByType('damaged', session('branch_id'));
            
            case 'expiry':
                $days = $filters['expiry_days'] ?? 30;
                return $this->damageModel->getItemsNearingExpiry($days, session('branch_id'));
            
            default:
                return [];
        }
    }

    public function reportDamage()
    {
        $data = $this->request->getJSON(true);
        
        $damageData = [
            'product_id' => $data['product_id'],
            'branch_id' => session('branch_id'),
            'damage_type' => $data['damage_type'],
            'quantity' => $data['quantity'],
            'unit_cost' => $data['unit_cost'],
            'expiry_date' => $data['expiry_date'] ?? null,
            'batch_number' => $data['batch_number'] ?? null,
            'reason' => $data['reason'] ?? null,
            'reported_by' => session('user_id'),
            'status' => 'reported'
        ];
        
        if ($this->damageModel->save($damageData)) {
            return $this->response->setJSON(['success' => true, 'message' => 'Damage reported successfully']);
        }
        
        return $this->response->setJSON(['success' => false, 'errors' => $this->damageModel->errors()]);
    }

    public function receiveDelivery()
    {
        $data = $this->request->getJSON(true);
        
        $success = true;
        $errors = [];
        
        foreach ($data['items'] as $item) {
            $deliveryItemData = [
                'delivery_id' => $data['delivery_id'],
                'product_id' => $item['product_id'],
                'expected_quantity' => $item['expected_quantity'],
                'received_quantity' => $item['received_quantity'],
                'unit_cost' => $item['unit_cost'],
                'condition_status' => $item['condition_status'] ?? 'good',
                'batch_number' => $item['batch_number'] ?? null,
                'expiry_date' => $item['expiry_date'] ?? null,
                'notes' => $item['notes'] ?? null
            ];
            
            if (!$this->deliveryItemModel->save($deliveryItemData)) {
                $success = false;
                $errors[] = $this->deliveryItemModel->errors();
            } else {
                // Update inventory if item is in good condition
                if ($item['condition_status'] === 'good' && $item['received_quantity'] > 0) {
                    $this->updateInventoryFromDelivery($item['product_id'], $item['received_quantity']);
                }
            }
        }
        
        if ($success) {
            return $this->response->setJSON(['success' => true, 'message' => 'Delivery received successfully']);
        }
        
        return $this->response->setJSON(['success' => false, 'errors' => $errors]);
    }

    private function updateInventoryFromDelivery($productId, $quantity)
    {
        $inventory = $this->inventoryModel->where('product_id', $productId)
                                        ->where('branch_id', session('branch_id'))
                                        ->first();
        
        if ($inventory) {
            $newQuantity = $inventory['current_stock'] + $quantity;
            $this->inventoryModel->update($inventory['id'], ['current_stock' => $newQuantity]);
        } else {
            // Create new inventory record
            $this->inventoryModel->save([
                'product_id' => $productId,
                'branch_id' => session('branch_id'),
                'current_stock' => $quantity,
                'min_stock' => 0,
                'max_stock' => 1000
            ]);
        }
        
        // Record stock movement
        $this->stockMovementModel->save([
            'product_id' => $productId,
            'branch_id' => session('branch_id'),
            'movement_type' => 'in',
            'quantity' => $quantity,
            'reference_type' => 'delivery',
            'notes' => 'Delivery received',
            'created_by' => session('user_id')
        ]);
    }

    public function getDeliveryItems($deliveryId)
    {
        $items = $this->deliveryItemModel->getItemsByDelivery($deliveryId);
        return $this->response->setJSON($items);
    }

    public function getDamageItems()
    {
        $items = $this->damageModel->select('damage_expired_items.*, products.product_name, products.product_code')
                                 ->join('products', 'products.id = damage_expired_items.product_id')
                                 ->where('damage_expired_items.branch_id', session('branch_id'))
                                 ->orderBy('damage_expired_items.created_at', 'DESC')
                                 ->findAll();
        
        return $this->response->setJSON($items);
    }

    public function deleteStock($id)
    {
        // Set CORS headers for API access
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHeader('Access-Control-Allow-Methods', 'GET, POST, DELETE, OPTIONS');
        $this->response->setHeader('Access-Control-Allow-Headers', 'Content-Type');
        
        try {
            // Debug log
            log_message('debug', 'Delete stock ID: ' . $id);
            
            if ($this->inventoryModel->delete($id)) {
                return $this->response->setJSON(['success' => true, 'message' => 'Stock deleted successfully']);
            }
            
            return $this->response->setJSON([
                'success' => false, 
                'errors' => $this->inventoryModel->errors(),
                'debug_id' => $id
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getAllDeliveries()
    {
        // Set CORS headers for API access
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $this->response->setHeader('Access-Control-Allow-Headers', 'Content-Type');
        
        try {
            $branchId = session('branch_id');
            
            if (!$branchId) {
                return $this->response->setJSON(['success' => false, 'message' => 'Branch ID not found in session']);
            }
            
            // Get actual deliveries for this branch
            $deliveries = $this->deliveryModel->select('deliveries.*, suppliers.company_name as supplier_name, purchase_orders.po_number, purchase_orders.total_amount, purchase_orders.total_quantity')
                                            ->join('suppliers', 'suppliers.id = deliveries.supplier_id', 'left')
                                            ->join('purchase_orders', 'purchase_orders.id = deliveries.purchase_order_id', 'left')
                                            ->where('deliveries.branch_id', $branchId)
                                            ->orderBy('deliveries.created_at', 'DESC')
                                            ->findAll();

            // Get delivery items for each delivery
            $result = [];
            foreach ($deliveries as $delivery) {
                $items = $this->deliveryItemModel->getItemsByDelivery($delivery['id']);

                // If no delivery items exist, try to get from PO items
                if (empty($items) && !empty($delivery['purchase_order_id'])) {
                    $poItemModel = new \App\Models\PurchaseOrderItemModel();
                    $poItems = $poItemModel->getItemsByPurchaseOrder($delivery['purchase_order_id']);

                    // Convert PO items to delivery item format
                    if (!empty($poItems)) {
                        foreach ($poItems as $poItem) {
                            $items[] = [
                                'product_id' => $poItem['product_id'],
                                'product_name' => $poItem['product_name'] ?? 'Unknown Product',
                                'product_code' => $poItem['product_code'] ?? '',
                                'product' => $poItem['product_name'] ?? 'Unknown Product',
                                'expected_quantity' => $poItem['quantity'],
                                'expected_qty' => $poItem['quantity'],
                                'received_quantity' => 0,
                                'received_qty' => 0,
                                'unit_cost' => $poItem['unit_price'],
                                'unit_value' => $poItem['unit_price'],
                                'condition_status' => 'good',
                                'condition' => 'good'
                            ];
                        }
                    }
                }

                $result[] = [
                    'id' => $delivery['id'],
                    'delivery_number' => $delivery['delivery_number'],
                    'supplier' => $delivery['supplier_name'] ?? 'Unknown',
                    'po_number' => $delivery['po_number'] ?? '',
                    'expected_date' => $delivery['scheduled_date'],
                    'status' => $delivery['status'],
                    'received_by' => $delivery['driver_name'] ?? null,
                    'items' => $items,
                    'total_amount' => $delivery['total_amount'] ?? null, // Include PO total amount for actual deliveries too
                    'total_quantity' => $delivery['total_quantity'] ?? 0, // Include PO total quantity
                    'created_at' => $delivery['created_at']
                ];
            }
            
            // Also include approved/ordered POs that haven't been scheduled yet (for reference)
            $poModel = new \App\Models\PurchaseOrderModel();
            
            // Get PO IDs that already have deliveries
            $db = \Config\Database::connect();
            $scheduledPOIds = $db->table('deliveries')
                                ->select('purchase_order_id')
                                ->where('branch_id', $branchId)
                                ->get()
                                ->getResultArray();
            $scheduledPOIds = array_column($scheduledPOIds, 'purchase_order_id');
            
            // Get approved/ordered POs that don't have deliveries yet
            $builder = $poModel->select('purchase_orders.*, suppliers.company_name as supplier_name')
                              ->join('suppliers', 'suppliers.id = purchase_orders.supplier_id', 'left')
                              ->where('purchase_orders.branch_id', $branchId)
                              ->whereIn('purchase_orders.status', ['approved', 'ordered']);
            
            if (!empty($scheduledPOIds)) {
                $builder->whereNotIn('purchase_orders.id', $scheduledPOIds);
            }
            
            $pendingPOs = $builder->orderBy('purchase_orders.created_at', 'DESC')->findAll();
            
            // Add pending POs as "pending_schedule" status
            foreach ($pendingPOs as $po) {
                $poItemModel = new \App\Models\PurchaseOrderItemModel();
                $poItems = $poItemModel->getItemsByPurchaseOrder($po['id']);
                
                // Convert PO items to delivery item format for display
                $formattedItems = [];
                if (!empty($poItems)) {
                    foreach ($poItems as $poItem) {
                        $formattedItems[] = [
                            'product_id' => $poItem['product_id'],
                            'product_name' => $poItem['product_name'] ?? 'Unknown Product',
                            'product_code' => $poItem['product_code'] ?? '',
                            'product' => $poItem['product_name'] ?? 'Unknown Product',
                            'expected_quantity' => $poItem['quantity'],
                            'expected_qty' => $poItem['quantity'],
                            'received_quantity' => 0,
                            'received_qty' => 0,
                            'unit_cost' => $poItem['unit_price'],
                            'unit_value' => $poItem['unit_price'],
                            'condition_status' => 'good',
                            'condition' => 'good'
                        ];
                    }
                }
                
                $result[] = [
                    'id' => 'po_' . $po['id'], // Prefix to distinguish from delivery IDs
                    'delivery_number' => $po['po_number'],
                    'supplier' => $po['supplier_name'] ?? 'Unknown',
                    'po_number' => $po['po_number'],
                    'expected_date' => $po['expected_delivery'] ?? $po['requested_date'],
                    'status' => 'pending_schedule', // Special status for unscheduled POs
                    'received_by' => null,
                    'items' => $formattedItems,
                    'total_amount' => $po['total_amount'], // Include PO total amount
                    'total_quantity' => $po['total_quantity'] ?? 0, // Include PO total quantity
                    'created_at' => $po['created_at'],
                    'is_pending_schedule' => true,
                    'po_id' => $po['id']
                ];
            }
            
            return $this->response->setJSON(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            log_message('error', 'getAllDeliveries Error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Error loading deliveries: ' . $e->getMessage()]);
        }
    }
}
