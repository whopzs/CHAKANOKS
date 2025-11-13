<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use App\Models\InventoryModel;
use App\Models\ProductModel;
use App\Models\BranchModel;

/**
 * Test cases for InventoryModel
 * Covers: Real-time tracking, stock alerts, update functions, expiry tracking
 */
class InventoryModelTest extends CIUnitTestCase
{
    protected $inventoryModel;
    protected $productModel;
    protected $branchModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventoryModel = new InventoryModel();
        $this->productModel = new ProductModel();
        $this->branchModel = new BranchModel();
    }

    /**
     * Test real-time inventory tracking
     */
    public function testGetBranchInventory()
    {
        $branchId = 1;
        $inventory = $this->inventoryModel->getBranchInventory($branchId);
        
        $this->assertIsArray($inventory);
        // Verify structure includes product information
        if (!empty($inventory)) {
            $this->assertArrayHasKey('product_name', $inventory[0]);
            $this->assertArrayHasKey('current_stock', $inventory[0]);
        }
    }

    /**
     * Test stock level retrieval
     */
    public function testGetStockLevels()
    {
        $branchId = 1;
        $stockLevels = $this->inventoryModel->getStockLevels($branchId);
        
        $this->assertIsArray($stockLevels);
    }

    /**
     * Test low stock detection
     */
    public function testGetLowStockItems()
    {
        $lowStockItems = $this->inventoryModel->getLowStockItems();
        
        $this->assertIsArray($lowStockItems);
        // Verify items are actually low stock
        foreach ($lowStockItems as $item) {
            $this->assertLessThanOrEqual(
                $item['min_stock_level'], 
                $item['current_stock'],
                'Low stock items should have current_stock <= min_stock_level'
            );
        }
    }

    /**
     * Test critical stock detection
     */
    public function testGetCriticalStockItems()
    {
        $criticalItems = $this->inventoryModel->getCriticalStockItems();
        
        $this->assertIsArray($criticalItems);
        // Verify items are actually critical
        foreach ($criticalItems as $item) {
            $this->assertLessThanOrEqual(
                $item['reorder_point'], 
                $item['current_stock'],
                'Critical stock items should have current_stock <= reorder_point'
            );
        }
    }

    /**
     * Test inventory value calculation
     */
    public function testGetInventoryValue()
    {
        $value = $this->inventoryModel->getInventoryValue();
        
        $this->assertIsNumeric($value);
        $this->assertGreaterThanOrEqual(0, $value);
    }

    /**
     * Test expiry tracking
     */
    public function testGetExpiringItems()
    {
        $branchId = 1;
        $expiringItems = $this->inventoryModel->getExpiringItems($branchId, 30);
        
        $this->assertIsArray($expiringItems);
    }

    /**
     * Test stock counting methods
     */
    public function testGetLowStockCount()
    {
        $branchId = 1;
        $count = $this->inventoryModel->getLowStockCount($branchId);
        
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testGetCriticalStockCount()
    {
        $branchId = 1;
        $count = $this->inventoryModel->getCriticalStockCount($branchId);
        
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }
}

