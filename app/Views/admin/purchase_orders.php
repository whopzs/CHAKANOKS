<?= $this->include('shared/header') ?>

<div class="main-container">
  <button class="btn btn-primary d-md-none position-fixed top-0 start-0 m-3" style="z-index:1100" data-bs-toggle="offcanvas" data-bs-target="#sidebar"><i class="bi bi-list"></i></button>
  <div id="mobileOverlay" class="d-md-none" onclick="closeSidebar()"></div>

  <?= $this->include('shared/sidebar') ?>

  <main class="main-content">
    <div class="header">
      <div>
        <h2>Purchase Order Approval</h2>
        <p class="mb-0">Review and approve purchase orders from all branches</p>
        <p class="mb-0">Role: Central Office Admin</p>
      </div>
      <div class="user-info">
        <div class="text-end">
          <div style="color:#ffd700;font-weight:600;">Central Office Admin</div>
          <div style="color:#ffffff;font-size:14px;">Purchase Management</div>
        </div>
        <div class="user-avatar">CA</div>
      </div>
    </div>

    <!-- Pending Approvals Section -->
    <div class="custom-card mb-4">
      <h3 class="text-warning mb-3 fs-5">⏳ Pending Approvals (<?= count($pendingOrders) ?>)</h3>
      <div class="table-responsive">
        <table class="table table-dark table-hover">
          <thead>
            <tr>
              <th>PO Number</th>
              <th>Branch</th>
              <th>Supplier</th>
              <th>Requested By</th>
              <th>Total Amount</th>
              <th>Requested Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($pendingOrders)): ?>
              <?php foreach ($pendingOrders as $po): ?>
              <tr>
                <td><strong><?= esc($po['po_number']) ?></strong></td>
                <td><?= esc($po['branch_name']) ?></td>
                <td><?= esc($po['company_name']) ?></td>
                <td><?= esc($po['first_name'] . ' ' . $po['last_name']) ?></td>
                <td>₱<?= number_format($po['total_amount'], 2) ?></td>
                <td><?= date('M d, Y', strtotime($po['requested_date'])) ?></td>
                <td>
                  <button class="btn btn-sm btn-outline-primary me-2" onclick="viewPODetails(<?= $po['id'] ?>)">
                    <i class="bi bi-eye"></i> View
                  </button>
                  <button class="btn btn-sm btn-success me-2" onclick="approvePO(<?= $po['id'] ?>)">
                    <i class="bi bi-check-circle"></i> Approve
                  </button>
                  <button class="btn btn-sm btn-danger" onclick="rejectPO(<?= $po['id'] ?>)">
                    <i class="bi bi-x-circle"></i> Reject
                  </button>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="7" class="text-center text-muted">
                  <i class="bi bi-info-circle"></i> No pending purchase orders
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- All Purchase Orders -->
    <div class="custom-card mb-4">
      <h3 class="text-warning mb-3 fs-5">📋 All Purchase Orders</h3>
      <div class="table-responsive">
        <table class="table table-dark table-hover">
          <thead>
            <tr>
              <th>PO Number</th>
              <th>Branch</th>
              <th>Supplier</th>
              <th>Status</th>
              <th>Total Amount</th>
              <th>Requested Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($allOrders)): ?>
              <?php foreach ($allOrders as $po): ?>
              <tr>
                <td><strong><?= esc($po['po_number']) ?></strong></td>
                <td><?= esc($po['branch_name']) ?></td>
                <td><?= esc($po['company_name']) ?></td>
                <td>
                  <?php
                  $statusClass = match($po['status']) {
                    'pending' => 'badge-pending',
                    'approved' => 'badge-active',
                    'rejected' => 'badge-critical',
                    'ordered' => 'badge-transit',
                    'delivered' => 'badge-delivered',
                    default => 'badge-pending'
                  };
                  ?>
                  <span class="badge <?= $statusClass ?>"><?= ucfirst($po['status']) ?></span>
                </td>
                <td>₱<?= number_format($po['total_amount'], 2) ?></td>
                <td><?= date('M d, Y', strtotime($po['requested_date'])) ?></td>
                <td>
                  <button class="btn btn-sm btn-outline-primary" onclick="viewPODetails(<?= $po['id'] ?>)">
                    <i class="bi bi-eye"></i> View
                  </button>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="7" class="text-center text-muted">
                  <i class="bi bi-info-circle"></i> No purchase orders found
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<!-- PO Details Modal -->
<div class="modal fade" id="poDetailsModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content" style="background: var(--card-bg); color: #ffffff;">
      <div class="modal-header" style="border-bottom: 1px solid #333;">
        <h5 class="modal-title text-warning">Purchase Order Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="poDetailsContent">
        <div class="text-center">
          <div class="spinner-border text-warning" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function viewPODetails(poId) {
  fetch(`<?= base_url('admin/api/purchase-orders/') ?>${poId}/details`)
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        const po = data.po;
        const items = data.items;
        
        let html = `
          <div class="mb-3">
            <strong>PO Number:</strong> ${po.po_number}<br>
            <strong>Branch:</strong> ${po.branch_name}<br>
            <strong>Supplier:</strong> ${po.company_name}<br>
            <strong>Contact:</strong> ${po.contact_person} (${po.email})<br>
            <strong>Phone:</strong> ${po.phone}<br>
            <strong>Status:</strong> <span class="badge badge-${po.status === 'pending' ? 'pending' : 'active'}">${po.status}</span><br>
            <strong>Total Amount:</strong> ₱${parseFloat(po.total_amount).toLocaleString('en-US', {minimumFractionDigits: 2})}
          </div>
          <hr style="border-color: #333;">
          <h6 class="text-warning mb-3">Items:</h6>
          <table class="table table-dark table-sm">
            <thead>
              <tr>
                <th>Product</th>
                <th>Code</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody>
        `;
        
        items.forEach(item => {
          html += `
            <tr>
              <td>${item.product_name}</td>
              <td>${item.product_code || 'N/A'}</td>
              <td>${item.quantity} ${item.unit || ''}</td>
              <td>₱${parseFloat(item.unit_price).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
              <td>₱${parseFloat(item.total_price).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
            </tr>
          `;
        });
        
        html += `
            </tbody>
          </table>
        `;
        
        if (po.notes) {
          html += `<div class="mt-3"><strong>Notes:</strong> ${po.notes}</div>`;
        }
        
        document.getElementById('poDetailsContent').innerHTML = html;
        new bootstrap.Modal(document.getElementById('poDetailsModal')).show();
      }
    })
    .catch(err => {
      alert('Error loading PO details');
      console.error(err);
    });
}

function approvePO(poId) {
  if (!confirm('Are you sure you want to approve this purchase order?')) return;
  
  fetch(`<?= base_url('admin/api/purchase-orders/') ?>${poId}/approve`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    }
  })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert('Purchase order approved successfully!');
        location.reload();
      } else {
        alert('Error: ' + data.message);
      }
    })
    .catch(err => {
      alert('Error approving purchase order');
      console.error(err);
    });
}

function rejectPO(poId) {
  if (!confirm('Are you sure you want to reject this purchase order?')) return;
  
  fetch(`<?= base_url('admin/api/purchase-orders/') ?>${poId}/reject`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    }
  })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert('Purchase order rejected');
        location.reload();
      } else {
        alert('Error: ' + data.message);
      }
    })
    .catch(err => {
      alert('Error rejecting purchase order');
      console.error(err);
    });
}
</script>

<?= $this->include('shared/footer') ?>
