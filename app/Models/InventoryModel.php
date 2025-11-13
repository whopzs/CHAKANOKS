<?php

namespace App\Models;

use CodeIgniter\Model;

class InventoryModel extends Model
{
    protected $table = 'inventory';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'product_id', 'branch_id', 'current_stock', 'min_stock_level', 
        'max_stock_level', 'reorder_point', 'last_updated'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [
        'product_id' => 'required|integer',
        'branch_id' => 'required|integer',
        'current_stock' => 'required|decimal|greater_than_equal_to[0]',
        'min_stock_level' => 'required|decimal|greater_than_equal_to[0]',
        'max_stock_level' => 'required|decimal|greater_than_equal_to[0]',
        'reorder_point' => 'required|decimal|greater_than_equal_to[0]'
    ];

    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;

    public function getInventoryByBranch($branchId)
    {
        return $this->select('inventory.*, products.product_name, products.product_code, products.unit, products.unit_price')
                   ->join('products', 'products.id = inventory.product_id')
                   ->where('inventory.branch_id', $branchId)
                   ->where('products.is_active', 1)
                   ->findAll();
    }

    public function getLowStockItems($branchId = null)
    {
        $builder = $this->select('inventory.*, products.product_name, products.product_code, products.unit')
                       ->join('products', 'products.id = inventory.product_id')
                       ->where('inventory.current_stock <= inventory.min_stock_level')
                       ->where('products.is_active', 1);

        if ($branchId) {
            $builder->where('inventory.branch_id', $branchId);
        }

        return $builder->findAll();
    }

    public function getCriticalStockItems($branchId = null)
    {
        $builder = $this->select('inventory.*, products.product_name, products.product_code, products.unit')
                       ->join('products', 'products.id = inventory.product_id')
                       ->where('inventory.current_stock <= inventory.reorder_point')
                       ->where('products.is_active', 1);

        if ($branchId) {
            $builder->where('inventory.branch_id', $branchId);
        }

        return $builder->findAll();
    }

    public function updateStock($productId, $branchId, $quantity, $movementType = 'adjustment')
    {
        $inventory = $this->where('product_id', $productId)
                         ->where('branch_id', $branchId)
                         ->first();

        if (!$inventory) {
            return false;
        }

        $oldStock = $inventory['current_stock'];
        $newStock = $oldStock;
        if ($movementType === 'in') {
            $newStock += $quantity;
        } elseif ($movementType === 'out') {
            $newStock -= $quantity;
        } else {
            $newStock = $quantity; // adjustment
        }

        if ($newStock < 0) {
            return false; // Cannot have negative stock
        }

        $this->update($inventory['id'], [
            'current_stock' => $newStock,
            'last_updated' => date('Y-m-d H:i:s')
        ]);

        // Check if stock alert should be sent (only if stock decreased)
        if ($newStock < $oldStock) {
            $this->checkAndSendStockAlerts($productId, $branchId, $newStock, $inventory);
        }

        return true;
    }

    /**
     * Check stock levels and send alerts if needed
     */
    private function checkAndSendStockAlerts($productId, $branchId, $currentStock, $inventory)
    {
        try {
            $notificationService = new \App\Services\NotificationService();
            $productModel = new \App\Models\ProductModel();
            $product = $productModel->find($productId);

            if (!$product) {
                return;
            }

            $productName = $product['product_name'];
            $productCode = $product['product_code'] ?? '';

            // Check for critical stock (below reorder point)
            if ($currentStock <= $inventory['reorder_point'] && $currentStock > 0) {
                $notificationService->sendCriticalStockAlert(
                    $productName,
                    $currentStock,
                    $inventory['reorder_point'],
                    $branchId,
                    $productCode
                );
            }
            // Check for low stock (below min level but above reorder point)
            elseif ($currentStock <= $inventory['min_stock_level'] && $currentStock > $inventory['reorder_point']) {
                $notificationService->sendLowStockAlert(
                    $productName,
                    $currentStock,
                    $inventory['min_stock_level'],
                    $branchId,
                    $productCode
                );
            }
        } catch (\Exception $e) {
            // Log error but don't fail the stock update
            log_message('error', 'Error checking stock alerts: ' . $e->getMessage());
        }
    }

    public function getInventoryValue($branchId = null)
    {
        $builder = $this->select('SUM(inventory.current_stock * products.unit_price) as total_value')
                       ->join('products', 'products.id = inventory.product_id')
                       ->where('products.is_active', 1);

        if ($branchId) {
            $builder->where('inventory.branch_id', $branchId);
        }

        $result = $builder->first();
        return $result['total_value'] ?? 0;
    }

    public function getBranchInventory($branchId)
    {
        return $this->select('inventory.*, products.product_name, products.product_code, products.unit, products.unit_price, products.category')
                   ->join('products', 'products.id = inventory.product_id')
                   ->where('inventory.branch_id', $branchId)
                   ->where('products.is_active', 1)
                   ->findAll();
    }

    public function getCriticalStockItemsByBranch($branchId)
    {
        return $this->select('inventory.*, products.product_name, products.product_code, products.unit')
                   ->join('products', 'products.id = inventory.product_id')
                   ->where('inventory.branch_id', $branchId)
                   ->where('inventory.current_stock <= inventory.min_stock_level')
                   ->where('products.is_active', 1)
                   ->findAll();
    }

    public function getStockLevels($branchId)
    {
        return $this->select('inventory.*, products.product_name, products.product_code, products.unit, products.unit_price')
                   ->join('products', 'products.id = inventory.product_id')
                   ->where('inventory.branch_id', $branchId)
                   ->where('products.is_active', 1)
                   ->orderBy('inventory.current_stock', 'ASC')
                   ->findAll();
    }

    public function getRecentDeliveries($branchId)
    {
        // This would typically join with a deliveries table
        // For now, return empty array as placeholder
        return [];
    }

    public function getExpiringItems($branchId, $days = 30)
    {
        // Get items from delivery_items that are nearing expiry
        $deliveryItemModel = new \App\Models\DeliveryItemModel();
        $expiringItems = $deliveryItemModel->getItemsNearingExpiry($days, $branchId);
        
        // Format the data to match expected structure
        $formattedItems = [];
        foreach ($expiringItems as $item) {
            $formattedItems[] = [
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'product_code' => $item['product_code'],
                'expiry_date' => $item['expiry_date'],
                'quantity' => $item['received_quantity'],
                'batch_number' => $item['batch_number'] ?? null,
                'days_remaining' => $this->calculateDaysUntilExpiry($item['expiry_date'])
            ];
        }
        
        return $formattedItems;
    }
    
    /**
     * Calculate days until expiry
     */
    private function calculateDaysUntilExpiry($expiryDate)
    {
        if (empty($expiryDate)) {
            return null;
        }
        
        $expiry = new \DateTime($expiryDate);
        $today = new \DateTime();
        $diff = $today->diff($expiry);
        
        return $diff->invert ? -$diff->days : $diff->days;
    }

    // Admin-specific methods for cross-branch inventory management
    public function getTotalStockAcrossBranches($productId)
    {
        $result = $this->selectSum('current_stock')
                    ->where('product_id', $productId)
                    ->get()
                    ->getRow();
        
        return $result ? $result->current_stock : 0;
    }

    public function getProductStockByBranch($productId)
    {
        return $this->select('inventory.*, branches.name as branch_name')
                   ->join('branches', 'branches.id = inventory.branch_id')
                   ->where('inventory.product_id', $productId)
                   ->findAll();
    }

    public function getTotalProductCount()
    {
        $productModel = new \App\Models\ProductModel();
        return $productModel->countAll();
    }

    public function getActiveProductCount()
    {
        $productModel = new \App\Models\ProductModel();
        return $productModel->where('is_active', 1)->countAllResults();
    }

    public function getPerishableProductCount()
    {
        $productModel = new \App\Models\ProductModel();
        return $productModel->where('is_perishable', 1)->countAllResults();
    }

    public function getProductCount($branchId)
    {
        return $this->where('branch_id', $branchId)->countAllResults();
    }

    public function getLowStockCount($branchId)
    {
        $result = $this->where('branch_id', $branchId)->findAll();
        $count = 0;
        foreach ($result as $item) {
            if ($item['current_stock'] <= $item['min_stock_level']) {
                $count++;
            }
        }
        return $count;
    }

    public function getCriticalStockCount($branchId)
    {
        $result = $this->where('branch_id', $branchId)->findAll();
        $count = 0;
        foreach ($result as $item) {
            if ($item['current_stock'] <= $item['reorder_point']) {
                $count++;
            }
        }
        return $count;
    }


    public function getBranchCriticalItems($branchId)
    {
        return $this->select('inventory.*, products.product_name, products.product_code, products.unit')
                   ->join('products', 'products.id = inventory.product_id')
                   ->where('inventory.branch_id', $branchId)
                   ->where('inventory.current_stock <=', 'inventory.reorder_point', false)
                   ->findAll();
    }

}

