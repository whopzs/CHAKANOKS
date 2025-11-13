<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
// Public routes
$routes->get('/', 'Home::index');
$routes->get('home', 'Home::index');
$routes->get('login', 'Login::index');
$routes->post('login/auth', 'Login::auth');
$routes->get('logout', 'Login::logout');

// Test route
$routes->get('test', function() {
    return 'Test route is working!';
});

// Protected routes (require authentication)
$routes->get('dashboard', 'Dashboard::index', ['filter' => 'auth']);
$routes->get('branchmanager', 'BranchManager::index', ['filter' => 'auth']);
$routes->get('staff', 'Staff::index', ['filter' => 'auth']);
$routes->get('logistics', 'Logistics::index', ['filter' => 'auth']);

// Supplier routes
$routes->group('supplier', ['filter' => 'auth'], static function($routes) {
    $routes->get('orders', 'Dashboard::supplierOrders');
});

// Central Office Admin routes (admin role only)
$routes->group('admin', ['filter' => 'auth'], static function($routes) {
    $routes->get('inventory', 'Dashboard::adminInventory');
    $routes->get('products', 'Dashboard::adminProducts');
    $routes->get('branch-inventory/(:num)', 'Dashboard::branchInventory/$1');
    $routes->get('purchase-orders', 'Dashboard::purchaseOrders');
    $routes->get('supplier-reports', 'Dashboard::supplierReports');
    $routes->get('delivery-tracking', 'Dashboard::deliveryTracking');
    
    // Admin API routes
    $routes->get('api/inventory-data', 'Dashboard::getInventoryData');
    $routes->get('api/product-data', 'Dashboard::getProductData');
    $routes->post('api/purchase-orders/(:num)/approve', 'Dashboard::approvePO/$1');
    $routes->post('api/purchase-orders/(:num)/reject', 'Dashboard::rejectPO/$1');
    $routes->get('api/purchase-orders/(:num)/details', 'Dashboard::getPODetails/$1');
    $routes->get('api/deliveries', 'Dashboard::getDeliveries');
});

// Inventory Staff subpages
$routes->group('staff', ['filter' => 'auth'], static function($routes) {
    $routes->get('stock-levels', 'Staff::stockLevels');
    $routes->get('deliveries', 'Staff::deliveries');
    $routes->get('damages-expired', 'Staff::damagesExpired');
    $routes->get('reports', 'Staff::reports');
});

// Inventory routes (protected)
$routes->group('inventory', ['filter' => 'auth'], static function($routes) {
    // View routes
    $routes->get('dashboard', 'Inventory::dashboard');
    $routes->get('stock-levels', 'Inventory::stockLevels');
    $routes->get('reports', 'Inventory::reports');
    $routes->get('deliveries', 'Inventory::deliveries');
    $routes->get('damages-expired', 'Inventory::damagesExpired');
    
    // API routes
    $routes->get('api/test', 'Inventory::testApi');
    $routes->get('api/stock-data', 'Inventory::getStockData');
    $routes->get('api/search-barcode/(:any)', 'Inventory::searchBarcode/$1');
    $routes->post('api/save-stock', 'Inventory::saveStock');
    $routes->post('api/adjust-stock', 'Inventory::adjustStock');
    $routes->delete('api/delete-stock/(:num)', 'Inventory::deleteStock/$1');
    $routes->get('api/recent-reports', 'Inventory::getRecentReports');
    $routes->post('api/generate-report', 'Inventory::generateReport');
    $routes->post('api/report-damage', 'Inventory::reportDamage');
    $routes->post('api/receive-delivery', 'Inventory::receiveDelivery');
    $routes->get('api/delivery-items/(:num)', 'Inventory::getDeliveryItems/$1');
    $routes->get('api/delivery-items', 'Inventory::getAllDeliveries');
    $routes->get('api/damage-items', 'Inventory::getDamageItems');
});

// Logistics API
$routes->group('logistics', ['filter' => 'auth'], static function($routes) {
    $routes->get('api/deliveries', 'Logistics::listDeliveries');
    $routes->post('api/deliveries/schedule', 'Logistics::scheduleDelivery');
    $routes->post('api/deliveries/(:num)/status', 'Logistics::updateDeliveryStatus/$1');
});

// Branch Manager subpages
$routes->group('branchmanager', ['filter' => 'auth'], static function($routes) {
    $routes->get('inventory', 'BranchManager::inventory');
    $routes->get('purchase-requests', 'BranchManager::purchaseRequests');
    $routes->get('transfers', 'BranchManager::transfers');
    $routes->get('reports', 'BranchManager::reports');

    // API routes for branch manager
    $routes->get('api/inventory-data', 'BranchManager::apiInventoryData');
    $routes->get('api/critical-alerts', 'BranchManager::apiCriticalAlerts');
    $routes->post('api/adjust-stock', 'BranchManager::apiAdjustStock');

    // Purchase order actions
    $routes->post('api/purchase-orders', 'BranchManager::createPurchaseOrder');
    $routes->post('api/purchase-orders/(:num)/approve', 'BranchManager::approvePurchaseOrder/$1');
    $routes->post('api/purchase-orders/(:num)/reject', 'BranchManager::rejectPurchaseOrder/$1');

    // Purchase requests UI data endpoints
    $routes->get('api/purchase-requests', 'BranchManager::apiPurchaseRequests');
    $routes->get('api/suppliers', 'BranchManager::apiSuppliers');
    $routes->get('api/products', 'BranchManager::apiProducts');
    $routes->post('api/create-purchase-request', 'BranchManager::apiCreatePurchaseRequest');
    $routes->delete('api/delete-purchase-request/(:num)', 'BranchManager::apiDeletePurchaseRequest/$1');
});

// Supplier routes
$routes->group('supplier', ['filter' => 'auth'], static function($routes) {
    $routes->get('/', 'Supplier::index');
    $routes->get('api/orders/(:num)/details', 'Supplier::getOrderDetails/$1');
    $routes->post('api/orders/(:num)/status', 'Supplier::updateOrderStatus/$1');
});