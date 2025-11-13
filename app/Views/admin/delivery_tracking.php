<?= $this->include('shared/header') ?>

<div class="main-container">
  <button class="btn btn-primary d-md-none position-fixed top-0 start-0 m-3" style="z-index:1100" data-bs-toggle="offcanvas" data-bs-target="#sidebar"><i class="bi bi-list"></i></button>
  <div id="mobileOverlay" class="d-md-none" onclick="closeSidebar()"></div>

  <?= $this->include('shared/sidebar') ?>

  <main class="main-content">
    <div class="header">
      <div>
        <h2>Delivery Tracking</h2>
        <p class="mb-0">Monitor all deliveries across all branches in real-time</p>
        <p class="mb-0">Role: Central Office Admin</p>
      </div>
      <div class="user-info">
        <div class="text-end">
          <div style="color:#ffd700;font-weight:600;">Central Office Admin</div>
          <div style="color:#ffffff;font-size:14px;">Logistics Overview</div>
        </div>
        <div class="user-avatar">CA</div>
      </div>
    </div>

    <!-- Delivery Statistics -->
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-number"><?= $stats['total'] ?? 0 ?></div>
          <div class="stat-label">Total Deliveries</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-number text-warning"><?= $stats['scheduled'] ?? 0 ?></div>
          <div class="stat-label">Scheduled</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-number text-info"><?= $stats['in_transit'] ?? 0 ?></div>
          <div class="stat-label">In Transit</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-number text-success"><?= $stats['delivered'] ?? 0 ?></div>
          <div class="stat-label">Delivered</div>
        </div>
      </div>
    </div>

    <!-- Filter Tabs -->
    <div class="custom-card mb-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="text-warning mb-0 fs-5">🚚 All Deliveries</h3>
        <div class="btn-group" role="group">
          <button type="button" class="btn btn-outline-primary btn-sm" onclick="filterDeliveries('all')">All</button>
          <button type="button" class="btn btn-outline-primary btn-sm" onclick="filterDeliveries('scheduled')">Scheduled</button>
          <button type="button" class="btn btn-outline-primary btn-sm" onclick="filterDeliveries('in_transit')">In Transit</button>
          <button type="button" class="btn btn-outline-primary btn-sm" onclick="filterDeliveries('delivered')">Delivered</button>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-dark table-hover" id="deliveriesTable">
          <thead>
            <tr>
              <th>Delivery #</th>
              <th>PO Number</th>
              <th>Branch</th>
              <th>Supplier</th>
              <th>Status</th>
              <th>Scheduled Date</th>
              <th>Delivered Date</th>
              <th>Driver</th>
              <th>Vehicle</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($deliveries)): ?>
              <?php foreach ($deliveries as $delivery): ?>
              <tr data-status="<?= $delivery['status'] ?>">
                <td><strong><?= esc($delivery['delivery_number']) ?></strong></td>
                <td><?= esc($delivery['po_number'] ?? 'N/A') ?></td>
                <td><?= esc($delivery['branch_name'] ?? 'N/A') ?></td>
                <td><?= esc($delivery['supplier_name'] ?? 'N/A') ?></td>
                <td>
                  <?php
                  $statusClass = match($delivery['status']) {
                    'scheduled' => 'badge-pending',
                    'in_transit' => 'badge-transit',
                    'delivered' => 'badge-delivered',
                    'cancelled' => 'badge-critical',
                    default => 'badge-pending'
                  };
                  ?>
                  <span class="badge <?= $statusClass ?>"><?= ucfirst(str_replace('_', ' ', $delivery['status'])) ?></span>
                </td>
                <td><?= $delivery['scheduled_date'] ? date('M d, Y H:i', strtotime($delivery['scheduled_date'])) : 'N/A' ?></td>
                <td><?= $delivery['delivered_date'] ? date('M d, Y H:i', strtotime($delivery['delivered_date'])) : '-' ?></td>
                <td><?= esc($delivery['driver_name'] ?? '-') ?></td>
                <td><?= esc($delivery['vehicle_number'] ?? '-') ?></td>
              </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="9" class="text-center text-muted">
                  <i class="bi bi-info-circle"></i> No deliveries found
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<script>
function filterDeliveries(status) {
  const rows = document.querySelectorAll('#deliveriesTable tbody tr');
  rows.forEach(row => {
    if (status === 'all' || row.getAttribute('data-status') === status) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
  
  // Update active button
  document.querySelectorAll('.btn-group button').forEach(btn => {
    btn.classList.remove('active');
  });
  event.target.classList.add('active');
}
</script>

<?= $this->include('shared/footer') ?>
