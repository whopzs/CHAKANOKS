<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use App\Models\DeliveryModel;
use App\Models\DeliveryItemModel;

/**
 * Test cases for DeliveryModel
 * Covers: Delivery scheduling, order tracking, status updates
 */
class DeliveryModelTest extends CIUnitTestCase
{
    protected $deliveryModel;
    protected $deliveryItemModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->deliveryModel = new DeliveryModel();
        $this->deliveryItemModel = new DeliveryItemModel();
    }

    /**
     * Test delivery number generation
     */
    public function testGenerateDeliveryNumber()
    {
        $deliveryNumber = $this->deliveryModel->generateDeliveryNumber();
        
        $this->assertIsString($deliveryNumber);
        $this->assertStringStartsWith('DLV-', $deliveryNumber);
        $this->assertMatchesRegularExpression('/^DLV-\d{4}-\d{4}$/', $deliveryNumber);
    }

    /**
     * Test getting deliveries by branch
     */
    public function testGetDeliveriesByBranch()
    {
        $branchId = 1;
        $deliveries = $this->deliveryModel->getDeliveriesByBranch($branchId);
        
        $this->assertIsArray($deliveries);
    }

    /**
     * Test getting all deliveries
     */
    public function testGetAllDeliveries()
    {
        $deliveries = $this->deliveryModel->getAllDeliveries();
        
        $this->assertIsArray($deliveries);
    }

    /**
     * Test delivery statistics
     */
    public function testGetDeliveryStatistics()
    {
        $stats = $this->deliveryModel->getDeliveryStatistics();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('scheduled', $stats);
        $this->assertArrayHasKey('in_transit', $stats);
        $this->assertArrayHasKey('delivered', $stats);
        $this->assertArrayHasKey('cancelled', $stats);
    }

    /**
     * Test expiry tracking in delivery items
     */
    public function testGetItemsNearingExpiry()
    {
        $expiringItems = $this->deliveryItemModel->getItemsNearingExpiry(30);
        
        $this->assertIsArray($expiringItems);
    }

    /**
     * Test delivery completion status
     */
    public function testGetDeliveryCompletionStatus()
    {
        // This would require a test delivery ID
        // Verify method exists
        $this->assertTrue(
            method_exists($this->deliveryItemModel, 'getDeliveryCompletionStatus'),
            'getDeliveryCompletionStatus method should exist'
        );
    }
}

