<?php
/**
 * Role-Based Access Control (RBAC) Helper Functions
 * Centralized navigation logic for cleaner code organization
 */

// Get current user role and path
$currentRole = session()->get('role');
$currentPath = uri_string();

/**
 * Generate navigation item HTML
 */
function createNavItem($icon, $text, $url = null, $isActive = false, $badge = null, $onclick = null) {
    $activeClass = $isActive ? 'active' : '';
    $onclickAttr = $onclick ? "onclick=\"$onclick\"" : '';
    $urlAttr = $url ? "onclick=\"window.location.href='$url'\"" : '';
    
    $badgeHtml = $badge ? "<span class=\"badge bg-warning text-dark ms-auto\">$badge</span>" : '';
    
    return "<div class=\"nav-item $activeClass\" $urlAttr $onclickAttr>
        <i class=\"$icon\"></i>
        $text
        $badgeHtml
    </div>";
}

/**
 * Get role-specific navigation items
 */
function getRoleNavigation($role, $currentPath) {
    switch ($role) {
        case 'admin':
            // Get pending approvals count for badge
            $pendingCount = 0;
            try {
                $poModel = new \App\Models\PurchaseOrderModel();
                $pendingOrders = $poModel->getPurchaseOrdersByStatus('pending');
                $pendingCount = count($pendingOrders);
            } catch (\Exception $e) {
                // Silently fail if model not available
            }
            return [
                createNavItem('bi bi-boxes', 'Inventory Overview', base_url('admin/inventory'), $currentPath === 'admin/inventory'),
                createNavItem('bi bi-box-seam', 'Products Management', base_url('admin/products'), $currentPath === 'admin/products'),
                createNavItem('bi bi-cart', 'Purchase Orders', base_url('admin/purchase-orders'), $currentPath === 'admin/purchase-orders', $pendingCount > 0 ? $pendingCount : null),
                createNavItem('bi bi-people', 'Supplier Reports', base_url('admin/supplier-reports'), $currentPath === 'admin/supplier-reports'),
                createNavItem('bi bi-truck', 'Delivery Tracking', base_url('admin/delivery-tracking'), $currentPath === 'admin/delivery-tracking'),
                createNavItem('bi bi-arrow-left-right', 'Branch Transfers', null, false, null, "alert('Branch Transfers - Coming Soon')"),
                createNavItem('bi bi-bar-chart', 'Reports & Analytics', null, false, null, "alert('Reports & Analytics - Coming Soon')"),
                createNavItem('bi bi-shop', 'Franchising', null, false, '4', "alert('Franchising - Coming Soon')"),
                createNavItem('bi bi-person-gear', 'User Management', null, false, null, "alert('User Management - Coming Soon')")
            ];
            
        case 'branch_manager':
            return [
                createNavItem('bi bi-box-seam', 'Inventory', base_url('branchmanager/inventory'), $currentPath === 'branchmanager/inventory'),
                createNavItem('bi bi-cart', 'Purchase Req', base_url('branchmanager/purchase-requests'), $currentPath === 'branchmanager/purchase-requests'),
                createNavItem('bi bi-arrow-left-right', 'Transfers', base_url('branchmanager/transfers'), $currentPath === 'branchmanager/transfers'),
                createNavItem('bi bi-bar-chart', 'Reports', base_url('branchmanager/reports'), $currentPath === 'branchmanager/reports')
            ];
            
        case 'inventory_staff':
            return [
                createNavItem('bi bi-box-seam', 'Stock Levels', base_url('staff/stock-levels'), $currentPath === 'staff/stock-levels'),
                createNavItem('bi bi-truck', 'Deliveries', base_url('staff/deliveries'), $currentPath === 'staff/deliveries'),
                createNavItem('bi bi-exclamation-triangle', 'Damaged/Expired', base_url('staff/damages-expired'), $currentPath === 'staff/damages-expired'),
                createNavItem('bi bi-bar-chart', 'Reports', base_url('staff/reports'), $currentPath === 'staff/reports')
            ];
            
        case 'logistics_coordinator':
            return [
                createNavItem('bi bi-grid', 'Dashboard', base_url('logistics'), $currentPath === 'logistics'),
                createNavItem('bi bi-calendar', 'Schedule Delivery', base_url('logistics'), $currentPath === 'logistics'),
                createNavItem('bi bi-truck', 'Track Deliveries', base_url('logistics'), $currentPath === 'logistics'),
                createNavItem('bi bi-bar-chart', 'Reports', base_url('dashboard'), $currentPath === 'dashboard')
            ];
            
        case 'supplier':
            return [
                createNavItem('bi bi-list-check', 'View Orders', base_url('supplier'), $currentPath === 'supplier'),
                createNavItem('bi bi-pencil-square', 'Update Status', base_url('supplier'), $currentPath === 'supplier'),
                createNavItem('bi bi-receipt', 'Submit Invoice', null, false, null, "alert('Submit Invoice - Coming Soon')"),
                createNavItem('bi bi-bar-chart', 'Reports', null, false, null, "alert('Reports - Coming Soon')")
            ];
            
        case 'franchise_manager':
            return [
                createNavItem('bi bi-file-text', 'Review Applications', null, false, null, "alert('Review Applications - Coming Soon')"),
                createNavItem('bi bi-box-seam', 'Allocate Supplies', null, false, null, "alert('Allocate Supplies - Coming Soon')"),
                createNavItem('bi bi-credit-card', 'Track Payments', null, false, null, "alert('Track Payments - Coming Soon')"),
                createNavItem('bi bi-bar-chart', 'Reports', null, false, null, "alert('Reports - Coming Soon')")
            ];
            
        case 'system_admin':
            return [
                createNavItem('bi bi-people', 'User Management', null, false, null, "alert('User Management - Coming Soon')"),
                createNavItem('bi bi-gear', 'System Settings', null, false, null, "alert('System Settings - Coming Soon')"),
                createNavItem('bi bi-hdd-stack', 'Backup & Recovery', null, false, null, "alert('Backup & Recovery - Coming Soon')"),
                createNavItem('bi bi-journal-text', 'System Logs', null, false, null, "alert('System Logs - Coming Soon')"),
                createNavItem('bi bi-database', 'Database Management', null, false, null, "alert('Database Management - Coming Soon')")
            ];
            
        default:
            return [
                createNavItem('bi bi-person-lines-fill', 'Contact Administrator', null, false, null, "alert('Contact Administrator - Coming Soon')"),
                createNavItem('bi bi-info-circle', 'System Information', null, false, null, "alert('System Information - Coming Soon')")
            ];
    }
}
?>

    <!-- Sidebar -->
    <nav class="sidebar offcanvas-md offcanvas-start" id="sidebar" tabindex="-1">
      <div class="logo">
        <h1>ChakaNoks</h1>
        <p>Supply Chain Management</p>
      </div>
      
      <div class="nav-section">
        <div class="nav-section-title">Navigation</div>

        <!-- Universal Dashboard Link -->
        <?= createNavItem('bi bi-grid-3x3-gap', 'Dashboard', base_url('dashboard'), $currentPath === 'dashboard') ?>

        <!-- Role-Based Navigation -->
        <?php 
        $roleNavigation = getRoleNavigation($currentRole, $currentPath);
        foreach ($roleNavigation as $navItem) {
            echo $navItem;
        }
        ?>
      </div>

      <div class="logout" onclick="logout()">
        <i class="bi bi-box-arrow-right"></i>
        Logout
      </div>
    </nav>

