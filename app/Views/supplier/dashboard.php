<?= $this->include('shared/header') ?>

<div class="main-container">
  <button class="btn btn-primary d-md-none position-fixed top-0 start-0 m-3" style="z-index:1100" data-bs-toggle="offcanvas" data-bs-target="#sidebar"><i class="bi bi-list"></i></button>
  <div id="mobileOverlay" class="d-md-none" onclick="closeSidebar()"></div>

  <?= $this->include('shared/sidebar') ?>

  <main class="main-content">
    <div class="header">
      <div>
        <h2>Supplier Portal</h2>
        <p class="mb-0">View and manage your purchase orders</p>
        <p class="mb-0">Role: Supplier</p>
      </div>
      <div class="user-info">
        <div class="text-end">
          <div style="color:#ffd700;font-weight:600;">Supplier</div>
          <div style="color:#ffffff;font-size:14px;">Order Management</div>
        </div>
        <div class="user-avatar">SP</div>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <div class="stat-card">
          <div class="stat-number"><?= count($pendingOrders) ?></div>
          <div class="stat-label">Pending Orders</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-card">
          <div class="stat-number text-info"><?= count($activeOrders) ?></div>
          <div class="stat-label">Active Orders</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-card">
          <div class="stat-number text-success"><?= count($completedOrders) ?></div>
          <div class="stat-label">Completed (Recent)</div>
        </div>
      </div>
    </div>

    <!-- Pending Orders -->
    <div class="custom-card mb-4">
      <h3 class="text-warning mb-3 fs-5">⏳ Pending Orders (Awaiting Your Action)</h3>
      <div class="table-responsive">
        <table class="table table-dark table-hover">
          <thead>
            <tr>
              <th>PO Number</th>
              <th>Branch</th>
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
                <td><?= esc($po['first_name'] . ' ' . $po['last_name']) ?></td>
                <td>₱<?= number_format($po['total_amount'], 2) ?></td>
                <td><?= date('M d, Y', strtotime($po['requested_date'])) ?></td>
                <td>
                  <button class="btn btn-sm btn-outline-primary me-2" onclick="viewOrderDetails(<?= $po['id'] ?>)">
                    <i class="bi bi-eye"></i> View
                  </button>
                  <button class="btn btn-sm btn-success" onclick="markAsReady(<?= $po['id'] ?>)">
                    <i class="bi bi-check-circle"></i> Mark Ready
                  </button>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="text-center text-muted">
                  <i class="bi bi-info-circle"></i> No pending orders
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Active Orders -->
    <div class="custom-card mb-4">
      <h3 class="text-warning mb-3 fs-5">📦 Active Orders</h3>
      <div class="table-responsive">
        <table class="table table-dark table-hover">
          <thead>
            <tr>
              <th>PO Number</th>
              <th>Branch</th>
              <th>Status</th>
              <th>Total Amount</th>
              <th>Expected Delivery</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($activeOrders)): ?>
              <?php foreach ($activeOrders as $po): ?>
              <tr>
                <td><strong><?= esc($po['po_number']) ?></strong></td>
                <td><?= esc($po['branch_name']) ?></td>
                <td>
                  <span class="badge badge-<?= $po['status'] === 'ordered' ? 'transit' : 'active' ?>">
                    <?= ucfirst($po['status']) ?>
                  </span>
                </td>
                <td>₱<?= number_format($po['total_amount'], 2) ?></td>
                <td><?= $po['expected_delivery'] ? date('M d, Y', strtotime($po['expected_delivery'])) : 'Not set' ?></td>
                <td>
                  <button class="btn btn-sm btn-outline-primary" onclick="viewOrderDetails(<?= $po['id'] ?>)">
                    <i class="bi bi-eye"></i> View
                  </button>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="text-center text-muted">
                  <i class="bi bi-info-circle"></i> No active orders
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Completed Orders -->
    <div class="custom-card mb-4">
      <h3 class="text-warning mb-3 fs-5">✅ Recently Completed Orders</h3>
      <div class="table-responsive">
        <table class="table table-dark table-hover">
          <thead>
            <tr>
              <th>PO Number</th>
              <th>Branch</th>
              <th>Total Amount</th>
              <th>Completed Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($completedOrders)): ?>
              <?php foreach ($completedOrders as $po): ?>
              <tr>
                <td><strong><?= esc($po['po_number']) ?></strong></td>
                <td><?= esc($po['branch_name']) ?></td>
                <td>₱<?= number_format($po['total_amount'], 2) ?></td>
                <td><?= date('M d, Y', strtotime($po['updated_at'])) ?></td>
                <td>
                  <button class="btn btn-sm btn-outline-primary" onclick="viewOrderDetails(<?= $po['id'] ?>)">
                    <i class="bi bi-eye"></i> View
                  </button>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="5" class="text-center text-muted">
                  <i class="bi bi-info-circle"></i> No completed orders
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content" style="background: var(--card-bg); color: #ffffff;">
      <div class="modal-header" style="border-bottom: 1px solid #333;">
        <h5 class="modal-title text-warning">Order Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="orderDetailsContent">
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
function viewOrderDetails(poId) {
  fetch(`<?= base_url('supplier/api/orders/') ?>${poId}/details`)
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        const po = data.po;
        const items = data.items;
        
        let html = `
          <div class="mb-3">
            <strong>PO Number:</strong> ${po.po_number}<br>
            <strong>Branch:</strong> ${po.branch_name}<br>
            <strong>Status:</strong> <span class="badge badge-${po.status === 'approved' ? 'active' : 'transit'}">${po.status}</span><br>
            <strong>Total Amount:</strong> ₱${parseFloat(po.total_amount).toLocaleString('en-US', {minimumFractionDigits: 2})}<br>
            <strong>Requested Date:</strong> ${new Date(po.requested_date).toLocaleDateString()}
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
        
        document.getElementById('orderDetailsContent').innerHTML = html;
        new bootstrap.Modal(document.getElementById('orderDetailsModal')).show();
      }
    })
    .catch(err => {
      alert('Error loading order details');
      console.error(err);
    });
}

function markAsReady(poId) {
  const expectedDate = prompt('Enter expected delivery date (YYYY-MM-DD):');
  if (!expectedDate) return;
  
  fetch(`<?= base_url('supplier/api/orders/') ?>${poId}/status`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    },
    body: JSON.stringify({
      status: 'ordered',
      expected_delivery: expectedDate
    })
  })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert('Order marked as ready!');
        location.reload();
      } else {
        alert('Error: ' + data.message);
      }
    })
    .catch(err => {
      alert('Error updating order status');
      console.error(err);
    });
}
</script>

<?= $this->include('shared/footer') ?>

