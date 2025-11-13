<?= $this->include('shared/header') ?>

<div class="main-container">
  <button class="btn btn-primary d-md-none position-fixed top-0 start-0 m-3" style="z-index:1100" data-bs-toggle="offcanvas" data-bs-target="#sidebar"><i class="bi bi-list"></i></button>
  <div id="mobileOverlay" class="d-md-none" onclick="closeSidebar()"></div>

  <?= $this->include('shared/sidebar') ?>

  <main class="main-content">
    <div class="header">
      <div>
        <h2>Delivery Scheduling</h2>
        <p class="mb-0">Schedule and track deliveries from approved purchase orders</p>
        <p class="mb-0">Role: Logistics Coordinator</p>
      </div>
      <div class="user-info">
        <div class="text-end">
          <div style="color:#ffd700;font-weight:600;">Logistics Coordinator</div>
          <div style="color:#ffffff;font-size:14px;">Delivery Management</div>
        </div>
        <div class="user-avatar">LC</div>
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

    <!-- Approved Purchase Orders (Ready to Schedule) -->
    <div class="custom-card mb-4">
      <h3 class="text-warning mb-3 fs-5">📋 Approved Purchase Orders (Ready to Schedule) - <?= count($approvedPOs) ?></h3>
      <?php if (empty($approvedPOs)): ?>
        <div class="alert alert-info">
          <i class="bi bi-info-circle"></i> No approved purchase orders available for scheduling.
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-dark table-hover">
            <thead>
              <tr>
                <th>PO Number</th>
                <th>Branch</th>
                <th>Supplier</th>
                <th>Requested By</th>
                <th>Total Amount</th>
                <th>Expected Delivery</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($approvedPOs as $po): ?>
              <tr>
                <td><strong><?= esc($po['po_number']) ?></strong></td>
                <td><?= esc($po['branch_name']) ?></td>
                <td><?= esc($po['supplier_name']) ?></td>
                <td><?= esc($po['first_name'] . ' ' . $po['last_name']) ?></td>
                <td>₱<?= number_format($po['total_amount'], 2) ?></td>
                <td><?= $po['expected_delivery'] ? date('M d, Y', strtotime($po['expected_delivery'])) : 'Not set' ?></td>
                <td>
                  <button class="btn btn-sm btn-primary" onclick="openScheduleModal(<?= $po['id'] ?>, '<?= esc($po['po_number'], 'js') ?>', '<?= esc($po['branch_name'], 'js') ?>', '<?= esc($po['supplier_name'], 'js') ?>', '<?= $po['expected_delivery'] ?? '' ?>')">
                    <i class="bi bi-calendar-plus"></i> Schedule Delivery
                  </button>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <!-- Active Deliveries -->
    <div class="custom-card">
      <h3 class="text-warning mb-3 fs-5">🚚 Active Deliveries</h3>
      <?php if (empty($deliveries)): ?>
        <div class="alert alert-info">
          <i class="bi bi-info-circle"></i> No deliveries scheduled yet.
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-dark table-hover">
            <thead>
              <tr>
                <th>Delivery #</th>
                <th>PO Number</th>
                <th>Branch</th>
                <th>Supplier</th>
                <th>Status</th>
                <th>Scheduled Date</th>
                <th>Driver</th>
                <th>Vehicle</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($deliveries as $delivery): ?>
              <tr>
                <td><strong><?= esc($delivery['delivery_number']) ?></strong></td>
                <td><?= esc($delivery['po_number']) ?></td>
                <td><?= esc($delivery['branch_name']) ?></td>
                <td><?= esc($delivery['supplier_name']) ?></td>
                <td>
                  <?php
                  $statusClass = match($delivery['status']) {
                    'scheduled' => 'bg-warning text-dark',
                    'in_transit' => 'bg-info',
                    'delivered' => 'bg-success',
                    'cancelled' => 'bg-danger',
                    default => 'bg-secondary'
                  };
                  ?>
                  <span class="badge <?= $statusClass ?>"><?= strtoupper(str_replace('_', ' ', $delivery['status'])) ?></span>
                </td>
                <td><?= $delivery['scheduled_date'] ? date('M d, Y', strtotime($delivery['scheduled_date'])) : '-' ?></td>
                <td><?= esc($delivery['driver_name'] ?? '-') ?></td>
                <td><?= esc($delivery['vehicle_number'] ?? '-') ?></td>
                <td>
                  <?php if ($delivery['status'] === 'scheduled'): ?>
                    <button class="btn btn-sm btn-info" onclick="updateDeliveryStatus(<?= $delivery['id'] ?>, 'in_transit')">
                      <i class="bi bi-truck"></i> Mark In Transit
                    </button>
                  <?php elseif ($delivery['status'] === 'in_transit'): ?>
                    <button class="btn btn-sm btn-success" onclick="updateDeliveryStatus(<?= $delivery['id'] ?>, 'delivered')">
                      <i class="bi bi-check-circle"></i> Mark Delivered
                    </button>
                  <?php endif; ?>
                  <?php if (in_array($delivery['status'], ['scheduled', 'in_transit'])): ?>
                    <button class="btn btn-sm btn-danger" onclick="updateDeliveryStatus(<?= $delivery['id'] ?>, 'cancelled')">
                      <i class="bi bi-x-circle"></i> Cancel
                    </button>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </main>
</div>

<!-- Schedule Delivery Modal -->
<div class="modal fade" id="scheduleModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content bg-dark text-light">
      <div class="modal-header border-secondary">
        <h5 class="modal-title text-warning">Schedule Delivery</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="scheduleForm">
          <input type="hidden" id="poId" name="purchase_order_id">
          
          <div class="mb-3">
            <label class="form-label">PO Number</label>
            <input type="text" class="form-control bg-dark text-light border-secondary" id="poNumber" readonly>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Branch</label>
              <input type="text" class="form-control bg-dark text-light border-secondary" id="branchName" readonly>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Supplier</label>
              <input type="text" class="form-control bg-dark text-light border-secondary" id="supplierName" readonly>
            </div>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Scheduled Date <span class="text-danger">*</span></label>
            <input type="date" class="form-control bg-dark text-light border-secondary" id="scheduledDate" name="scheduled_date" required>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Driver Name</label>
              <input type="text" class="form-control bg-dark text-light border-secondary" id="driverName" name="driver_name" placeholder="Optional">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Vehicle Number</label>
              <input type="text" class="form-control bg-dark text-light border-secondary" id="vehicleNumber" name="vehicle_number" placeholder="Optional">
            </div>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Notes</label>
            <textarea class="form-control bg-dark text-light border-secondary" id="notes" name="notes" rows="3" placeholder="Optional notes about this delivery"></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer border-secondary">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="submitSchedule()">
          <i class="bi bi-calendar-plus"></i> Schedule Delivery
        </button>
      </div>
    </div>
  </div>
</div>

<?= $this->include('shared/footer') ?>

<script>
let scheduleModal;

document.addEventListener('DOMContentLoaded', function() {
  scheduleModal = new bootstrap.Modal(document.getElementById('scheduleModal'));
  
  // Set minimum date to today
  const today = new Date().toISOString().split('T')[0];
  document.getElementById('scheduledDate').setAttribute('min', today);
});

function openScheduleModal(poId, poNumber, branchName, supplierName, expectedDate) {
  document.getElementById('poId').value = poId;
  document.getElementById('poNumber').value = poNumber;
  document.getElementById('branchName').value = branchName;
  document.getElementById('supplierName').value = supplierName;
  
  // Set expected delivery date as default if available
  if (expectedDate) {
    const date = new Date(expectedDate);
    document.getElementById('scheduledDate').value = date.toISOString().split('T')[0];
  } else {
    document.getElementById('scheduledDate').value = '';
  }
  
  // Clear other fields
  document.getElementById('driverName').value = '';
  document.getElementById('vehicleNumber').value = '';
  document.getElementById('notes').value = '';
  
  scheduleModal.show();
}

async function submitSchedule() {
  const form = document.getElementById('scheduleForm');
  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }
  
  const formData = {
    purchase_order_id: document.getElementById('poId').value,
    scheduled_date: document.getElementById('scheduledDate').value,
    driver_name: document.getElementById('driverName').value || null,
    vehicle_number: document.getElementById('vehicleNumber').value || null,
    notes: document.getElementById('notes').value || null
  };
  
  try {
    const response = await fetch('<?= base_url("logistics/api/deliveries/schedule") ?>', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(formData)
    });
    
    const result = await response.json();
    
    if (result.success) {
      alert('Delivery scheduled successfully!');
      scheduleModal.hide();
      location.reload(); // Reload to show new delivery
    } else {
      alert('Error: ' + (result.error || 'Failed to schedule delivery'));
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Error scheduling delivery: ' + error.message);
  }
}

async function updateDeliveryStatus(deliveryId, status) {
  const statusText = status.replace('_', ' ').toUpperCase();
  if (!confirm(`Are you sure you want to mark this delivery as ${statusText}?`)) {
    return;
  }
  
  try {
    const response = await fetch(`<?= base_url("logistics/api/deliveries") ?>/${deliveryId}/status`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ status: status })
    });
    
    const result = await response.json();
    
    if (result.success) {
      alert(`Delivery status updated to ${statusText}`);
      location.reload();
    } else {
      alert('Error: ' + (result.error || 'Failed to update status'));
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Error updating status: ' + error.message);
  }
}
</script>

