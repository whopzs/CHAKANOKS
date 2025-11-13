<?php

namespace App\Controllers;

use App\Models\InventoryModel;
use App\Models\BranchModel;
use App\Models\PurchaseOrderModel;
use App\Models\StockMovementModel;
use App\Models\UserModel;
use App\Models\SupplierModel;
use App\Models\DeliveryModel;
use App\Models\PurchaseOrderItemModel;
use App\Services\NotificationService;
use CodeIgniter\Controller;

class Dashboard extends Controller
{
    protected $inventoryModel;
    protected $branchModel;
    protected $purchaseOrderModel;
    protected $stockMovementModel;
    protected $userModel;
    protected $supplierModel;
    protected $deliveryModel;
    protected $purchaseOrderItemModel;

    public function __construct()
    {
        $this->inventoryModel = new InventoryModel();
        $this->branchModel = new BranchModel();
        $this->purchaseOrderModel = new PurchaseOrderModel();
        $this->stockMovementModel = new StockMovementModel();
        $this->userModel = new UserModel();
        $this->supplierModel = new SupplierModel();
        $this->deliveryModel = new DeliveryModel();
        $this->purchaseOrderItemModel = new PurchaseOrderItemModel();
    }

    public function index()
    {
        // Check if user is logged in
        if (!session()->get('is_logged_in')) {
            return redirect()->to(base_url('login'));
        }

        $userRole = session()->get('role');
        $userBranchId = session()->get('branch_id');
        
        // Get role-specific data
        $data = $this->getRoleSpecificData($userRole, $userBranchId);
        
        // Add common user data
        $data['user'] = [
            'name' => session()->get('first_name') . ' ' . session()->get('last_name'),
            'role' => $userRole,
            'branch_id' => $userBranchId,
            'username' => session()->get('username')
        ];

        return view('auth/dashboard', $data);
    }

    private function getRoleSpecificData($role, $branchId = null)
    {
        $data = [];
        
        switch ($role) {
            case 'admin':
                $data = $this->getAdminData();
                break;
            case 'branch_manager':
                $data = $this->getBranchManagerData($branchId);
                break;
            case 'inventory_staff':
                $data = $this->getInventoryStaffData($branchId);
                break;
            case 'logistics_coordinator':
                $data = $this->getLogisticsData();
                break;
            case 'supplier':
                $data = $this->getSupplierData();
                break;
            case 'franchise_manager':
                $data = $this->getFranchiseManagerData();
                break;
            case 'system_admin':
                $data = $this->getSystemAdminData();
                break;
            default:
                $data = $this->getDefaultData();
        }
        
        return $data;
    }

    private function getAdminData()
    {
        $branches = $this->branchModel->getActiveBranches();
        $totalBranches = count($branches);
        $criticalStockItems = $this->inventoryModel->getCriticalStockItems();
        $pendingApprovals = $this->purchaseOrderModel->getPurchaseOrdersByStatus('pending');
        
        $totalInventoryValue = 0;
        foreach ($branches as $branch) {
            $totalInventoryValue += $this->inventoryModel->getInventoryValue($branch['id']);
        }
        
        return [
            'branches' => $branches,
            'totalBranches' => $totalBranches,
            'criticalStockItems' => $criticalStockItems,
            'pendingApprovals' => $pendingApprovals,
            'totalInventoryValue' => $totalInventoryValue,
            'dashboardType' => 'admin'
        ];
    }

    private function getBranchManagerData($branchId)
    {
        $branchInventory = $this->inventoryModel->getBranchInventory($branchId);
        $criticalItems = $this->inventoryModel->getCriticalStockItemsByBranch($branchId);
        $pendingRequests = $this->purchaseOrderModel->getPurchaseOrdersByBranch($branchId);
        
        return [
            'branchInventory' => $branchInventory,
            'criticalItems' => $criticalItems,
            'pendingRequests' => $pendingRequests,
            'dashboardType' => 'branch_manager'
        ];
    }

    private function getInventoryStaffData($branchId)
    {
        $stockLevels = $this->inventoryModel->getStockLevels($branchId);
        $recentDeliveries = $this->inventoryModel->getRecentDeliveries($branchId);
        $expiringItems = $this->inventoryModel->getExpiringItems($branchId, 30);
        
        // Send email alert for expiring items if any found
        if (!empty($expiringItems)) {
            try {
                $notificationService = new \App\Services\NotificationService();
                $notificationService->sendExpiringItemsAlert($expiringItems, $branchId);
            } catch (\Exception $e) {
                log_message('error', 'Error sending expiring items alert: ' . $e->getMessage());
            }
        }
        
        return [
            'stockLevels' => $stockLevels,
            'recentDeliveries' => $recentDeliveries,
            'expiringItems' => $expiringItems,
            'dashboardType' => 'inventory_staff'
        ];
    }

    private function getLogisticsData()
    {
        $activeDeliveries = $this->purchaseOrderModel->getActiveDeliveries();
        $scheduledDeliveries = $this->purchaseOrderModel->getScheduledDeliveries();
        
        return [
            'activeDeliveries' => $activeDeliveries,
            'scheduledDeliveries' => $scheduledDeliveries,
            'dashboardType' => 'logistics_coordinator'
        ];
    }

    private function getSupplierData()
    {
        // Get supplier_id from session (set during login)
        $supplierId = session()->get('supplier_id');
        
        // If not in session, try to find by email
        if (!$supplierId) {
            $userEmail = session()->get('email');
            if ($userEmail) {
                $supplier = $this->supplierModel->where('email', $userEmail)->first();
                if ($supplier) {
                    $supplierId = $supplier['id'];
                    session()->set('supplier_id', $supplierId);
                }
            }
        }
        
        $pendingOrders = $supplierId ? $this->purchaseOrderModel->getSupplierOrdersByStatus($supplierId, 'approved') : [];
        $activeOrders = $supplierId ? $this->purchaseOrderModel->getSupplierOrders($supplierId) : [];
        $completedOrders = $supplierId ? $this->purchaseOrderModel
            ->select('purchase_orders.*, branches.branch_name')
            ->join('branches', 'branches.id = purchase_orders.branch_id')
            ->where('purchase_orders.supplier_id', $supplierId)
            ->where('purchase_orders.status', 'delivered')
            ->orderBy('purchase_orders.updated_at', 'DESC')
            ->limit(10)
            ->findAll() : [];
        
        return [
            'pendingOrders' => $pendingOrders,
            'activeOrders' => $activeOrders,
            'completedOrders' => $completedOrders,
            'dashboardType' => 'supplier'
        ];
    }

    // Supplier Portal - View Orders
    public function supplierOrders()
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
                    session()->set('supplier_id', $supplierId);
                }
            }
        }
        
        if (!$supplierId) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Supplier account not linked to a supplier record. Please contact administrator.');
        }

        $pendingOrders = $this->purchaseOrderModel->getSupplierOrdersByStatus($supplierId, 'approved');
        $allOrders = $this->purchaseOrderModel->getSupplierOrders($supplierId);
        $deliveries = $this->deliveryModel->select('deliveries.*, purchase_orders.po_number, branches.branch_name')
            ->join('purchase_orders', 'purchase_orders.id = deliveries.purchase_order_id')
            ->join('branches', 'branches.id = deliveries.branch_id')
            ->where('deliveries.supplier_id', $supplierId)
            ->orderBy('deliveries.created_at', 'DESC')
            ->findAll();

        $data = [
            'pendingOrders' => $pendingOrders,
            'allOrders' => $allOrders,
            'deliveries' => $deliveries,
            'user' => [
                'name' => session()->get('first_name') . ' ' . session()->get('last_name'),
                'role' => session()->get('role')
            ]
        ];

        return view('supplier/orders', $data);
    }

    private function getFranchiseManagerData()
    {
        $franchiseApplications = $this->purchaseOrderModel->getFranchiseApplications();
        $franchiseAllocations = $this->purchaseOrderModel->getFranchiseAllocations();
        
        return [
            'franchiseApplications' => $franchiseApplications,
            'franchiseAllocations' => $franchiseAllocations,
            'dashboardType' => 'franchise_manager'
        ];
    }

    private function getSystemAdminData()
    {
        $branches = $this->branchModel->getActiveBranches();
        $totalUsers = $this->userModel->countAll();
        $activeUsers = $this->userModel->where('is_active', 1)->countAllResults();
        $systemAlerts = $this->getSystemAlerts();
        
        return [
            'branches' => $branches,
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'systemAlerts' => $systemAlerts,
            'dashboardType' => 'system_admin'
        ];
    }

    private function getSystemAlerts()
    {
        // This would typically check for system issues, backup status, etc.
        return [
            [
                'type' => 'info',
                'message' => 'System backup completed successfully',
                'timestamp' => date('Y-m-d H:i:s')
            ],
            [
                'type' => 'warning',
                'message' => 'Database maintenance scheduled for tonight',
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ];
    }

    private function getDefaultData()
    {
        return [
            'dashboardType' => 'default',
            'message' => 'Welcome to ChakaNoks SCMS'
        ];
    }

    // Central Office Admin - Inventory Management Methods
    public function adminInventory()
    {
        if (!session()->get('is_logged_in') || session()->get('role') !== 'admin') {
            return redirect()->to(base_url('login'));
        }

        try {
            $branches = $this->branchModel->getActiveBranches();
            $totalInventoryValue = $this->getTotalInventoryValue();
            $criticalStockItems = $this->getCriticalStockItemsWithBranchInfo();
            $lowStockAlerts = $this->getLowStockAlerts();
            $inventorySummary = $this->getInventorySummary();
            
            $data = [
                'branches' => $branches,
                'totalInventoryValue' => $totalInventoryValue,
                'criticalStockItems' => $criticalStockItems,
                'lowStockAlerts' => $lowStockAlerts,
                'inventorySummary' => $inventorySummary
            ];

            return view('admin/inventory_overview', $data);
        } catch (\Exception $e) {
            log_message('error', 'Admin Inventory Error: ' . $e->getMessage());
            return view('admin/inventory_overview', [
                'branches' => [],
                'totalInventoryValue' => 0,
                'criticalStockItems' => [],
                'lowStockAlerts' => [],
                'inventorySummary' => []
            ]);
        }
    }

    public function adminProducts()
    {
        if (!session()->get('is_logged_in') || session()->get('role') !== 'admin') {
            return redirect()->to(base_url('login'));
        }

        try {
            $data = [
                'products' => $this->getAllProductsWithStock(),
                'categories' => $this->getProductCategories(),
                'suppliers' => $this->getAllSuppliers(),
                'productAnalytics' => $this->getProductAnalytics()
            ];

            return view('admin/products_management', $data);
        } catch (\Exception $e) {
            log_message('error', 'Admin Products Error: ' . $e->getMessage());
            return view('admin/products_management', [
                'products' => [],
                'categories' => [],
                'suppliers' => [],
                'productAnalytics' => [
                    'total_products' => 0,
                    'active_products' => 0,
                    'perishable_products' => 0,
                    'categories' => []
                ]
            ]);
        }
    }

    public function branchInventory($branchId)
    {
        if (!session()->get('is_logged_in') || session()->get('role') !== 'admin') {
            return redirect()->to(base_url('login'));
        }

        $branch = $this->branchModel->find($branchId);
        if (!$branch) {
            return redirect()->to(base_url('dashboard/admin-inventory'));
        }

        $data = [
            'branch' => $branch,
            'inventory' => $this->getBranchInventory($branchId),
            'stockMovements' => $this->getBranchStockMovements($branchId),
            'criticalItems' => $this->getBranchCriticalItems($branchId)
        ];

        return view('admin/branch_inventory', $data);
    }

    // API Methods for Admin
    public function getInventoryData()
    {
        if (!session()->get('is_logged_in') || session()->get('role') !== 'admin') {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        try {
            $data = [
                'branches' => $this->branchModel->getActiveBranches(),
                'inventorySummary' => $this->getInventorySummary(),
                'criticalItems' => $this->inventoryModel->getCriticalStockItems(),
                'lowStockAlerts' => $this->getLowStockAlerts()
            ];

            return $this->response->setJSON([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error fetching inventory data: ' . $e->getMessage()
            ]);
        }
    }

    public function getProductData()
    {
        if (!session()->get('is_logged_in') || session()->get('role') !== 'admin') {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        try {
            $data = [
                'products' => $this->getAllProductsWithStock(),
                'categories' => $this->getProductCategories(),
                'analytics' => $this->getProductAnalytics()
            ];

            return $this->response->setJSON([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error fetching product data: ' . $e->getMessage()
            ]);
        }
    }

    // Helper Methods for Admin Functions
    private function getTotalInventoryValue()
    {
        $branches = $this->branchModel->getActiveBranches();
        $totalValue = 0;
        
        foreach ($branches as $branch) {
            $totalValue += $this->inventoryModel->getInventoryValue($branch['id']);
        }
        
        return $totalValue;
    }

    private function getLowStockAlerts()
    {
        try {
            $alerts = [];
            $branches = $this->branchModel->getActiveBranches();
            
            if (empty($branches)) {
                return [];
            }
            
            foreach ($branches as $branch) {
                $lowStockItems = $this->inventoryModel->getLowStockItems($branch['id']);
                if (!empty($lowStockItems)) {
                    $alerts[] = [
                        'branch_id' => $branch['id'],
                        'branch_name' => $branch['branch_name'],
                        'items' => $lowStockItems
                    ];
                }
            }
            
            return $alerts;
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getInventorySummary()
    {
        try {
            $branches = $this->branchModel->getActiveBranches();
            $summary = [];
            
            if (empty($branches)) {
                return [];
            }
            
            foreach ($branches as $branch) {
                $productCount = $this->inventoryModel->getProductCount($branch['id']);
                $inventoryValue = $this->inventoryModel->getInventoryValue($branch['id']);
                $lowStockCount = $this->inventoryModel->getLowStockCount($branch['id']);
                $criticalCount = $this->inventoryModel->getCriticalStockCount($branch['id']);
                
                $summary[] = [
                    'branch_id' => $branch['id'],
                    'branch_name' => $branch['branch_name'],
                    'total_products' => $productCount,
                    'total_value' => $inventoryValue,
                    'low_stock_count' => $lowStockCount,
                    'critical_count' => $criticalCount
                ];
            }
            
            return $summary;
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getAllProductsWithStock()
    {
        try {
            $productModel = new \App\Models\ProductModel();
            $products = $productModel->findAll();
            
            foreach ($products as &$product) {
                $product['total_stock'] = $this->inventoryModel->getTotalStockAcrossBranches($product['id']);
                $product['branch_stocks'] = $this->inventoryModel->getProductStockByBranch($product['id']);
            }
            
            return $products;
        } catch (\Exception $e) {
            log_message('error', 'getAllProductsWithStock Error: ' . $e->getMessage());
            return [];
        }
    }

    private function getProductCategories()
    {
        $productModel = new \App\Models\ProductModel();
        return $productModel->getCategories();
    }

    private function getAllSuppliers()
    {
        try {
            $supplierModel = new \App\Models\SupplierModel();
            $suppliers = $supplierModel->findAll();
            return $suppliers ?: [];
        } catch (\Exception $e) {
            log_message('error', 'getAllSuppliers Error: ' . $e->getMessage());
            return [];
        }
    }

    private function getProductAnalytics()
    {
        return [
            'total_products' => $this->inventoryModel->getTotalProductCount(),
            'active_products' => $this->inventoryModel->getActiveProductCount(),
            'perishable_products' => $this->inventoryModel->getPerishableProductCount(),
            'categories' => $this->getProductCategories()
        ];
    }

    private function getBranchInventory($branchId)
    {
        return $this->inventoryModel->getBranchInventory($branchId);
    }

    private function getBranchStockMovements($branchId)
    {
        return $this->stockMovementModel->getBranchMovements($branchId);
    }

    private function getBranchCriticalItems($branchId)
    {
        return $this->inventoryModel->getBranchCriticalItems($branchId);
    }

    private function getCriticalStockItemsWithBranchInfo()
    {
        try {
            $branches = $this->branchModel->getActiveBranches();
            $criticalItems = [];
            
            if (empty($branches)) {
                return [];
            }
            
            foreach ($branches as $branch) {
                $branchCriticalItems = $this->inventoryModel->getBranchCriticalItems($branch['id']);
                foreach ($branchCriticalItems as $item) {
                    $item['branch_name'] = $branch['branch_name'];
                    $criticalItems[] = $item;
                }
            }
            
            return $criticalItems;
        } catch (\Exception $e) {
            return [];
        }
    }

    // Purchase Order Approval Interface
    public function purchaseOrders()
    {
        if (!session()->get('is_logged_in') || session()->get('role') !== 'admin') {
            return redirect()->to(base_url('login'));
        }

        $pendingOrders = $this->purchaseOrderModel->getPendingApprovals();
        $allOrders = $this->purchaseOrderModel->select('purchase_orders.*, suppliers.company_name, branches.branch_name, users.first_name, users.last_name')
            ->join('suppliers', 'suppliers.id = purchase_orders.supplier_id')
            ->join('branches', 'branches.id = purchase_orders.branch_id')
            ->join('users', 'users.id = purchase_orders.requested_by')
            ->orderBy('purchase_orders.created_at', 'DESC')
            ->findAll();

        $data = [
            'pendingOrders' => $pendingOrders,
            'allOrders' => $allOrders,
            'user' => [
                'name' => session()->get('first_name') . ' ' . session()->get('last_name'),
                'role' => session()->get('role')
            ]
        ];

        return view('admin/purchase_orders', $data);
    }

    // API: Approve Purchase Order
    public function approvePO($poId)
    {
        if (!session()->get('is_logged_in') || session()->get('role') !== 'admin') {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized'])->setStatusCode(401);
        }

        $approvedBy = session()->get('user_id');
        $result = $this->purchaseOrderModel->approvePurchaseOrder($poId, $approvedBy);

        if ($result) {
            return $this->response->setJSON(['success' => true, 'message' => 'Purchase order approved successfully']);
        }

        return $this->response->setJSON(['success' => false, 'message' => 'Failed to approve purchase order']);
    }

    // API: Reject Purchase Order
    public function rejectPO($poId)
    {
        if (!session()->get('is_logged_in') || session()->get('role') !== 'admin') {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized'])->setStatusCode(401);
        }

        $approvedBy = session()->get('user_id');
        $result = $this->purchaseOrderModel->rejectPurchaseOrder($poId, $approvedBy);

        if ($result) {
            return $this->response->setJSON(['success' => true, 'message' => 'Purchase order rejected']);
        }

        return $this->response->setJSON(['success' => false, 'message' => 'Failed to reject purchase order']);
    }

    // API: Get Purchase Order Details
    public function getPODetails($poId)
    {
        if (!session()->get('is_logged_in') || session()->get('role') !== 'admin') {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized'])->setStatusCode(401);
        }

        $po = $this->purchaseOrderModel->select('purchase_orders.*, suppliers.company_name, suppliers.contact_person, suppliers.email, suppliers.phone, branches.branch_name, users.first_name, users.last_name')
            ->join('suppliers', 'suppliers.id = purchase_orders.supplier_id')
            ->join('branches', 'branches.id = purchase_orders.branch_id')
            ->join('users', 'users.id = purchase_orders.requested_by')
            ->find($poId);

        if (!$po) {
            return $this->response->setJSON(['success' => false, 'message' => 'Purchase order not found']);
        }

        $items = $this->purchaseOrderItemModel->getItemsByPurchaseOrder($poId);

        return $this->response->setJSON([
            'success' => true,
            'po' => $po,
            'items' => $items
        ]);
    }

    // Supplier Performance Reports
    public function supplierReports()
    {
        if (!session()->get('is_logged_in') || session()->get('role') !== 'admin') {
            return redirect()->to(base_url('login'));
        }

        $suppliers = $this->supplierModel->getAllSuppliersWithPerformance(90);
        $data = [
            'suppliers' => $suppliers,
            'user' => [
                'name' => session()->get('first_name') . ' ' . session()->get('last_name'),
                'role' => session()->get('role')
            ]
        ];

        return view('admin/supplier_reports', $data);
    }

    // Delivery Tracking
    public function deliveryTracking()
    {
        if (!session()->get('is_logged_in') || session()->get('role') !== 'admin') {
            return redirect()->to(base_url('login'));
        }

        $allDeliveries = $this->deliveryModel->getAllDeliveries();
        $deliveryStats = $this->deliveryModel->getDeliveryStatistics();

        $data = [
            'deliveries' => $allDeliveries,
            'stats' => $deliveryStats,
            'user' => [
                'name' => session()->get('first_name') . ' ' . session()->get('last_name'),
                'role' => session()->get('role')
            ]
        ];

        return view('admin/delivery_tracking', $data);
    }

    // API: Get Deliveries
    public function getDeliveries()
    {
        if (!session()->get('is_logged_in') || session()->get('role') !== 'admin') {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized'])->setStatusCode(401);
        }

        $status = $this->request->getGet('status');
        $deliveries = $this->deliveryModel->getAllDeliveries($status);

        return $this->response->setJSON(['success' => true, 'deliveries' => $deliveries]);
    }

}
