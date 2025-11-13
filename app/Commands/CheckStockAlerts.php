<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\InventoryModel;
use App\Services\NotificationService;

class CheckStockAlerts extends BaseCommand
{
    protected $group       = 'Inventory';
    protected $name        = 'inventory:check-alerts';
    protected $description = 'Check all branches for low stock and send email alerts';

    public function run(array $params)
    {
        CLI::write('Checking stock alerts for all branches...', 'yellow');

        $inventoryModel = new InventoryModel();
        $notificationService = new NotificationService();
        $branchModel = new \App\Models\BranchModel();

        $branches = $branchModel->where('is_active', 1)->findAll();
        $totalAlerts = 0;

        foreach ($branches as $branch) {
            CLI::write("Checking branch: {$branch['branch_name']}...", 'cyan');

            // Check for low stock items
            $lowStockItems = $inventoryModel->getLowStockItems($branch['id']);
            $criticalItems = $inventoryModel->getCriticalStockItems($branch['id']);

            // Send alerts for critical items
            foreach ($criticalItems as $item) {
                $productModel = new \App\Models\ProductModel();
                $product = $productModel->find($item['product_id']);
                
                if ($product) {
                    $notificationService->sendCriticalStockAlert(
                        $product['product_name'],
                        $item['current_stock'],
                        $item['reorder_point'],
                        $branch['id'],
                        $product['product_code'] ?? ''
                    );
                    $totalAlerts++;
                    CLI::write("  ✓ Sent critical alert for: {$product['product_name']}", 'green');
                }
            }

            // Send alerts for low stock items (not critical)
            foreach ($lowStockItems as $item) {
                // Skip if already sent as critical
                $isCritical = false;
                foreach ($criticalItems as $critical) {
                    if ($critical['product_id'] == $item['product_id']) {
                        $isCritical = true;
                        break;
                    }
                }

                if (!$isCritical) {
                    $productModel = new \App\Models\ProductModel();
                    $product = $productModel->find($item['product_id']);
                    
                    if ($product) {
                        $notificationService->sendLowStockAlert(
                            $product['product_name'],
                            $item['current_stock'],
                            $item['min_stock_level'],
                            $branch['id'],
                            $product['product_code'] ?? ''
                        );
                        $totalAlerts++;
                        CLI::write("  ✓ Sent low stock alert for: {$product['product_name']}", 'green');
                    }
                }
            }

            // Check for expiring items
            $expiringItems = $inventoryModel->getExpiringItems($branch['id'], 30);
            if (!empty($expiringItems)) {
                $notificationService->sendExpiringItemsAlert($expiringItems, $branch['id']);
                $totalAlerts++;
                CLI::write("  ✓ Sent expiring items alert", 'green');
            }
        }

        CLI::write("\nTotal alerts sent: {$totalAlerts}", 'yellow');
        CLI::write('Stock alert check completed!', 'green');
    }
}

