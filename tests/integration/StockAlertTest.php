<?php

namespace Tests\Integration;

use CodeIgniter\Test\CIUnitTestCase;
use App\Models\InventoryModel;
use App\Services\NotificationService;

/**
 * Integration tests for Stock Alert System
 * Covers: Low stock detection, critical stock alerts, notification system
 */
class StockAlertTest extends CIUnitTestCase
{
    protected $inventoryModel;
    protected $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventoryModel = new InventoryModel();
        $this->notificationService = new NotificationService();
    }

    /**
     * Test low stock detection
     */
    public function testLowStockDetection()
    {
        $lowStockItems = $this->inventoryModel->getLowStockItems();
        
        $this->assertIsArray($lowStockItems);
        
        // Verify structure
        if (!empty($lowStockItems)) {
            $item = $lowStockItems[0];
            $this->assertArrayHasKey('current_stock', $item);
            $this->assertArrayHasKey('min_stock_level', $item);
            $this->assertArrayHasKey('product_name', $item);
        }
    }

    /**
     * Test critical stock detection
     */
    public function testCriticalStockDetection()
    {
        $criticalItems = $this->inventoryModel->getCriticalStockItems();
        
        $this->assertIsArray($criticalItems);
        
        // Verify structure
        if (!empty($criticalItems)) {
            $item = $criticalItems[0];
            $this->assertArrayHasKey('current_stock', $item);
            $this->assertArrayHasKey('reorder_point', $item);
            $this->assertArrayHasKey('product_name', $item);
        }
    }

    /**
     * Test notification service exists
     */
    public function testNotificationService()
    {
        $this->assertTrue(
            method_exists($this->notificationService, 'sendLowStockAlert'),
            'Low stock alert method should exist'
        );
        
        $this->assertTrue(
            method_exists($this->notificationService, 'sendCriticalStockAlert'),
            'Critical stock alert method should exist'
        );
    }
}

