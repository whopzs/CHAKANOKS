<?php

namespace App\Models;

use CodeIgniter\Model;

class DeliveryModel extends Model
{
	protected $table = 'deliveries';
	protected $primaryKey = 'id';
	protected $useAutoIncrement = true;
	protected $returnType = 'array';
	protected $useSoftDeletes = false;
	protected $protectFields = true;
	protected $allowedFields = [
		'delivery_number',
		'purchase_order_id',
		'supplier_id',
		'branch_id',
		'status',
		'scheduled_date',
		'delivered_date',
		'driver_name',
		'vehicle_number',
		'notes',
	];

	protected $useTimestamps = true;
	protected $dateFormat = 'datetime';
	protected $createdField = 'created_at';
	protected $updatedField = 'updated_at';

	protected $validationRules = [
		'delivery_number' => 'required|min_length[5]|max_length[50]|is_unique[deliveries.delivery_number,id,{id}]',
		'purchase_order_id' => 'required|integer',
		'supplier_id' => 'required|integer',
		'branch_id' => 'required|integer',
		'status' => 'required|in_list[scheduled,in_transit,delivered,cancelled]',
		'scheduled_date' => 'required|valid_date',
		'delivered_date' => 'permit_empty|valid_date',
		'driver_name' => 'permit_empty|max_length[255]',
		'vehicle_number' => 'permit_empty|max_length[50]',
	];

	public function generateDeliveryNumber(): string
	{
		$prefix = 'DLV-' . date('Y') . '-';
		$last = $this->like('delivery_number', $prefix)
			->orderBy('delivery_number', 'DESC')
			->first();

		if ($last) {
			$lastNumber = (int) substr($last['delivery_number'], -4);
			$newNumber = $lastNumber + 1;
		} else {
			$newNumber = 1;
		}

		return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
	}

	public function getDeliveriesByBranch(int $branchId): array
	{
		return $this->select('deliveries.*, suppliers.company_name as supplier_name')
			->join('suppliers', 'suppliers.id = deliveries.supplier_id', 'left')
			->where('deliveries.branch_id', $branchId)
			->orderBy('deliveries.created_at', 'DESC')
			->findAll();
	}

	public function updateStatus(int $deliveryId, string $status): bool
	{
		$fields = ['status' => $status];
		if ($status === 'delivered') {
			$fields['delivered_date'] = date('Y-m-d H:i:s');
			
			// Update related purchase order status to 'delivered'
			$delivery = $this->find($deliveryId);
			if ($delivery && isset($delivery['purchase_order_id'])) {
				$poModel = new \App\Models\PurchaseOrderModel();
				$poModel->update($delivery['purchase_order_id'], [
					'status' => 'delivered'
				]);
			}
		}
		return (bool) $this->update($deliveryId, $fields);
	}

	/**
	 * Get all deliveries for Central Office Admin
	 */
	public function getAllDeliveries($status = null)
	{
		$builder = $this->select('deliveries.*, suppliers.company_name as supplier_name, branches.branch_name, purchase_orders.po_number')
			->join('suppliers', 'suppliers.id = deliveries.supplier_id', 'left')
			->join('branches', 'branches.id = deliveries.branch_id', 'left')
			->join('purchase_orders', 'purchase_orders.id = deliveries.purchase_order_id', 'left');
		
		if ($status) {
			$builder->where('deliveries.status', $status);
		}
		
		return $builder->orderBy('deliveries.created_at', 'DESC')->findAll();
	}

	/**
	 * Get delivery statistics
	 */
	public function getDeliveryStatistics()
	{
		// Use separate query builder instances to avoid interference
		$db = \Config\Database::connect();
		
		$stats = [
			'total' => $db->table('deliveries')->countAllResults(false),
			'scheduled' => $db->table('deliveries')->where('status', 'scheduled')->countAllResults(false),
			'in_transit' => $db->table('deliveries')->where('status', 'in_transit')->countAllResults(false),
			'delivered' => $db->table('deliveries')->where('status', 'delivered')->countAllResults(false),
			'cancelled' => $db->table('deliveries')->where('status', 'cancelled')->countAllResults(false),
		];
		
		return $stats;
	}
}


