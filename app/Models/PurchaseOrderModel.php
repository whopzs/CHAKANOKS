<?php

namespace App\Models;

use CodeIgniter\Model;

class PurchaseOrderModel extends Model
{
    protected $table = 'purchase_orders';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'po_number', 'supplier_id', 'branch_id', 'requested_by', 'status',
        'total_amount', 'total_quantity', 'notes', 'requested_date', 'approved_date',
        'approved_by', 'expected_delivery'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [
        'po_number' => 'required|min_length[5]|max_length[50]|is_unique[purchase_orders.po_number,id,{id}]',
        'supplier_id' => 'required|integer',
        'branch_id' => 'required|integer',
        'requested_by' => 'required|integer',
        'status' => 'required|in_list[draft,pending,approved,rejected,ordered,delivered,cancelled]',
        'total_amount' => 'required|decimal|greater_than_equal_to[0]',
        'total_quantity' => 'required|decimal|greater_than_equal_to[0]',
        'notes' => 'permit_empty',
        'requested_date' => 'required|valid_date',
        'approved_date' => 'permit_empty|valid_date',
        'approved_by' => 'permit_empty|integer',
        'expected_delivery' => 'permit_empty|valid_date'
    ];

    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;

    public function getPurchaseOrdersByBranch($branchId)
    {
        return $this->select('purchase_orders.*, suppliers.company_name, users.first_name, users.last_name')
                   ->join('suppliers', 'suppliers.id = purchase_orders.supplier_id')
                   ->join('users', 'users.id = purchase_orders.requested_by')
                   ->where('purchase_orders.branch_id', $branchId)
                   ->orderBy('purchase_orders.created_at', 'DESC')
                   ->findAll();
    }

    public function getPurchaseOrdersByStatus($status)
    {
        return $this->select('purchase_orders.*, suppliers.company_name, branches.branch_name, users.first_name, users.last_name')
                   ->join('suppliers', 'suppliers.id = purchase_orders.supplier_id')
                   ->join('branches', 'branches.id = purchase_orders.branch_id')
                   ->join('users', 'users.id = purchase_orders.requested_by')
                   ->where('purchase_orders.status', $status)
                   ->orderBy('purchase_orders.created_at', 'DESC')
                   ->findAll();
    }

    public function getPendingApprovals()
    {
        return $this->getPurchaseOrdersByStatus('pending');
    }

    public function approvePurchaseOrder($poId, $approvedBy)
    {
        return $this->update($poId, [
            'status' => 'approved',
            'approved_date' => date('Y-m-d H:i:s'),
            'approved_by' => $approvedBy
        ]);
    }

    public function rejectPurchaseOrder($poId, $approvedBy)
    {
        return $this->update($poId, [
            'status' => 'rejected',
            'approved_date' => date('Y-m-d H:i:s'),
            'approved_by' => $approvedBy
        ]);
    }

    public function generatePONumber()
    {
        $prefix = 'PO-' . date('Y') . '-';
        $lastPO = $this->like('po_number', $prefix)
                      ->orderBy('po_number', 'DESC')
                      ->first();

        if ($lastPO) {
            $lastNumber = (int) substr($lastPO['po_number'], -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public function getActiveDeliveries()
    {
        return $this->select('purchase_orders.*, suppliers.company_name, branches.branch_name')
                   ->join('suppliers', 'suppliers.id = purchase_orders.supplier_id')
                   ->join('branches', 'branches.id = purchase_orders.branch_id')
                   ->where('purchase_orders.status', 'ordered')
                   ->orderBy('purchase_orders.expected_delivery', 'ASC')
                   ->findAll();
    }

    public function getScheduledDeliveries()
    {
        return $this->select('purchase_orders.*, suppliers.company_name, branches.branch_name')
                   ->join('suppliers', 'suppliers.id = purchase_orders.supplier_id')
                   ->join('branches', 'branches.id = purchase_orders.branch_id')
                   ->where('purchase_orders.status', 'approved')
                   ->where('purchase_orders.expected_delivery >=', date('Y-m-d'))
                   ->orderBy('purchase_orders.expected_delivery', 'ASC')
                   ->findAll();
    }

    public function getSupplierOrders()
    {
        // This would typically filter by supplier_id from session
        // For now, return all pending orders
        return $this->select('purchase_orders.*, branches.branch_name')
                   ->join('branches', 'branches.id = purchase_orders.branch_id')
                   ->where('purchase_orders.status', 'approved')
                   ->orderBy('purchase_orders.created_at', 'DESC')
                   ->findAll();
    }

    public function getRecentSupplierOrders()
    {
        return $this->select('purchase_orders.*, branches.branch_name')
                   ->join('branches', 'branches.id = purchase_orders.branch_id')
                   ->where('purchase_orders.status', 'delivered')
                   ->where('purchase_orders.updated_at >=', date('Y-m-d', strtotime('-7 days')))
                   ->orderBy('purchase_orders.updated_at', 'DESC')
                   ->findAll();
    }

    public function getFranchiseApplications()
    {
        // This would typically join with a franchise_applications table
        // For now, return empty array as placeholder
        return [];
    }

    public function getFranchiseAllocations()
    {
        // This would typically join with a franchise_allocations table
        // For now, return empty array as placeholder
        return [];
    }
}
