<?= $this->include('shared/header') ?>

<div class="main-container">
  <button class="btn btn-primary d-md-none position-fixed top-0 start-0 m-3" style="z-index:1100" data-bs-toggle="offcanvas" data-bs-target="#sidebar"><i class="bi bi-list"></i></button>
  <div id="mobileOverlay" class="d-md-none" onclick="closeSidebar()"></div>

  <?= $this->include('shared/sidebar') ?>

  <main class="main-content">
    <div class="header">
      <div>
        <h2>Delivery Management</h2>
        <p class="mb-0">Track and confirm incoming deliveries at the branch</p>
      </div>
      <div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#receiveDeliveryModal">
          <i class="bi bi-truck"></i> Receive Delivery
        </button>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-number text-warning" id="pendingDeliveries">0</div>
          <div class="stat-label">Pending Deliveries</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-number text-success" id="completedToday">0</div>
          <div class="stat-label">Completed Today</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-number text-info" id="totalValue">₱0</div>
          <div class="stat-label">Total Value Today</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-number" id="totalItems">0</div>
          <div class="stat-label">Items Received</div>
        </div>
      </div>
    </div>

    <!-- Filters and Search -->
    <div class="custom-card mb-4">
      <div class="row g-3">
        <div class="col-md-3">
          <input type="text" class="form-control bg-dark text-white border-warning" 
                 id="searchInput" placeholder="Search deliveries...">
        </div>
        <div class="col-md-3">
          <select class="form-select bg-dark text-white border-warning" id="statusFilter">
            <option value="">All Status</option>
            <option value="pending">Pending</option>
            <option value="in_transit">In Transit</option>
            <option value="delivered">Delivered</option>
            <option value="partial">Partial</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
        <div class="col-md-3">
          <input type="date" class="form-control bg-dark text-white border-warning" id="dateFilter">
        </div>
        <div class="col-md-3">
          <button class="btn btn-outline-primary w-100" onclick="exportDeliveries()">
            <i class="bi bi-download"></i> Export
          </button>
        </div>
      </div>
    </div>

    <!-- Deliveries Table -->
    <div class="custom-card">
      <div class="table-responsive">
        <table class="table table-dark table-hover">
          <thead>
            <tr>
              <th>Delivery ID</th>
              <th>Supplier</th>
              <th>Expected Date</th>
              <th>Items</th>
              <th>Total Value</th>
              <th>Status</th>
              <th>Received By</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="deliveriesTableBody">
            <!-- Dynamic content -->
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<!-- Receive Delivery Modal -->
<div class="modal fade" id="receiveDeliveryModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content bg-dark text-white">
      <div class="modal-header border-warning">
        <h5 class="modal-title text-warning">Receive Delivery</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="deliveryForm">
          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label text-warning">Delivery ID</label>
              <select class="form-select bg-dark text-white border-warning" id="deliverySelect" required>
                <option value="">Select Pending Delivery</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label text-warning">Actual Delivery Date</label>
              <input type="datetime-local" class="form-control bg-dark text-white border-warning" 
                     id="actualDate" required>
            </div>
          </div>
          
          <div class="mb-3">
            <h6 class="text-warning">Delivery Items</h6>
            <div class="table-responsive">
              <table class="table table-dark table-sm">
                <thead>
                  <tr>
                    <th>Product</th>
                    <th>Expected Qty</th>
                    <th>Received Qty</th>
                    <th>Condition</th>
                    <th>Notes</th>
                  </tr>
                </thead>
                <tbody id="deliveryItemsTable">
                  <!-- Dynamic content -->
                </tbody>
              </table>
            </div>
          </div>
          
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label text-warning">Delivery Person</label>
              <input type="text" class="form-control bg-dark text-white border-warning" 
                     id="deliveryPerson" placeholder="Name of delivery person">
            </div>
            <div class="col-md-6">
              <label class="form-label text-warning">Vehicle/Reference</label>
              <input type="text" class="form-control bg-dark text-white border-warning" 
                     id="vehicleRef" placeholder="Truck number or reference">
            </div>
            <div class="col-12">
              <label class="form-label text-warning">General Notes</label>
              <textarea class="form-control bg-dark text-white border-warning" 
                        id="generalNotes" rows="3" placeholder="Any general observations or notes..."></textarea>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer border-warning">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="confirmDelivery()">Confirm Receipt</button>
      </div>
    </div>
  </div>
</div>

<!-- View Delivery Details Modal -->
<div class="modal fade" id="viewDeliveryModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content bg-dark text-white">
      <div class="modal-header border-warning">
        <h5 class="modal-title text-warning">Delivery Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="deliveryDetailsContent">
        <!-- Dynamic content -->
      </div>
      <div class="modal-footer border-warning">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onclick="printDeliveryReceipt()">Print Receipt</button>
      </div>
    </div>
  </div>
</div>

<script>
// Deliveries data will be loaded from database
let deliveries = [];

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
  fetchDeliveries();
  setCurrentDateTime();
  
  // Event listeners
  document.getElementById('searchInput').addEventListener('input', filterDeliveries);
  document.getElementById('statusFilter').addEventListener('change', filterDeliveries);
  document.getElementById('dateFilter').addEventListener('change', filterDeliveries);
  document.getElementById('deliverySelect').addEventListener('change', loadDeliveryItems);
});

async function fetchDeliveries() {
  try {
    const response = await fetch('<?= base_url("inventory/api/delivery-items") ?>');
    if (response.ok) {
      const result = await response.json();
      // Handle new response format with success/data structure
      if (result.success && result.data) {
        deliveries = result.data;
      } else if (Array.isArray(result)) {
        // Fallback for old format (direct array)
        deliveries = result;
      } else {
        throw new Error(result.message || 'Invalid response format');
      }
      loadDeliveries();
      updateStats();
      populateDeliverySelect();
    } else {
      const errorData = await response.json().catch(() => ({message: 'Failed to load deliveries'}));
      showAlert(errorData.message || 'Failed to load deliveries', 'danger');
    }
  } catch (error) {
    console.error('Error fetching deliveries:', error);
    showAlert('Error loading deliveries: ' + error.message, 'danger');
  }
}

function loadDeliveries() {
  const tbody = document.getElementById('deliveriesTableBody');
  tbody.innerHTML = '';
  
  const filteredData = getFilteredDeliveries();
  
  filteredData.forEach(delivery => {
    // Calculate total value based on delivery type
    let totalValue = 0;
    if (delivery.is_pending_schedule && delivery.total_amount) {
      // For pending schedules, use the PO total amount directly
      totalValue = delivery.total_amount;
    } else if (delivery.total_amount && (delivery.items || []).length === 0) {
      // For actual deliveries with no items but PO total available, use PO total
      totalValue = delivery.total_amount;
    } else {
      // For actual deliveries, calculate from items based on status
      // For delivered items, use received quantity; for others, use expected quantity
      totalValue = (delivery.items || []).reduce((sum, item) => {
        let qty = 0;
        if (delivery.status === 'delivered') {
          qty = item.received_quantity || item.received_qty || 0;
        } else {
          qty = item.expected_quantity || item.expected_qty || 0;
        }
        const cost = item.unit_cost || item.unit_value || 0;
        return sum + (qty * cost);
      }, 0);
    }
    const statusClass = getDeliveryStatusClass(delivery.status);
    // Use total_quantity from PO if available, otherwise count items
    const itemCount = delivery.total_quantity > 0 ? delivery.total_quantity :
                     (delivery.items && delivery.items.length > 0) ? delivery.items.length :
                     (delivery.is_pending_schedule ? delivery.items.length : 0);
    
    const row = `
      <tr>
        <td><strong>${delivery.id}</strong></td>
        <td>${delivery.supplier}</td>
        <td>${formatDate(delivery.expected_date)}</td>
        <td>${itemCount} item${itemCount !== 1 ? 's' : ''}</td>
        <td>₱${totalValue.toLocaleString()}</td>
        <td><span class="badge ${statusClass}">${delivery.status.toUpperCase().replace('_', ' ')}</span></td>
        <td>${delivery.received_by || '-'}</td>
        <td>
          <button class="btn btn-sm btn-outline-info me-1" onclick="viewDeliveryDetails('${delivery.id}')" title="View Details">
            <i class="bi bi-eye"></i>
          </button>
          ${delivery.status === 'pending' || delivery.status === 'in_transit' ? 
            `<button class="btn btn-sm btn-outline-success" onclick="receiveDelivery('${delivery.id}')" title="Receive">
              <i class="bi bi-check-circle"></i>
            </button>` : ''}
        </td>
      </tr>
    `;
    tbody.innerHTML += row;
  });
}

function getFilteredDeliveries() {
  const search = document.getElementById('searchInput').value.toLowerCase();
  const status = document.getElementById('statusFilter').value;
  const date = document.getElementById('dateFilter').value;
  
  return deliveries.filter(delivery => {
    const matchesSearch = delivery.id.toLowerCase().includes(search) || 
                         delivery.supplier.toLowerCase().includes(search);
    const matchesStatus = !status || delivery.status === status;
    const matchesDate = !date || delivery.expected_date === date;
    
    return matchesSearch && matchesStatus && matchesDate;
  });
}

function getDeliveryStatusClass(status) {
  switch(status) {
    case 'pending': return 'bg-warning text-dark';
    case 'in_transit': return 'bg-info';
    case 'delivered': return 'bg-success';
    case 'partial': return 'bg-secondary';
    case 'cancelled': return 'bg-danger';
    default: return 'bg-secondary';
  }
}

function updateStats() {
  const pending = deliveries.filter(d => d.status === 'pending' || d.status === 'in_transit').length;
  const today = new Date().toISOString().split('T')[0];
  const completedToday = deliveries.filter(d => 
    d.status === 'delivered' && d.actual_date && d.actual_date.startsWith(today)).length;
  
  const todayValue = deliveries
    .filter(d => d.status === 'delivered' && d.actual_date && d.actual_date.startsWith(today))
    .reduce((sum, d) => sum + d.items.reduce((itemSum, item) => itemSum + (item.received_qty * item.unit_value), 0), 0);
  
  const todayItems = deliveries
    .filter(d => d.status === 'delivered' && d.actual_date && d.actual_date.startsWith(today))
    .reduce((sum, d) => sum + d.items.reduce((itemSum, item) => itemSum + item.received_qty, 0), 0);
  
  document.getElementById('pendingDeliveries').textContent = pending;
  document.getElementById('completedToday').textContent = completedToday;
  document.getElementById('totalValue').textContent = '₱' + todayValue.toLocaleString();
  document.getElementById('totalItems').textContent = todayItems;
}

function filterDeliveries() {
  loadDeliveries();
}

function setCurrentDateTime() {
  const now = new Date();
  const localDateTime = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
  document.getElementById('actualDate').value = localDateTime;
}

function populateDeliverySelect() {
  const select = document.getElementById('deliverySelect');
  select.innerHTML = '<option value="">Select Pending Delivery</option>';
  
  deliveries.filter(d => d.status === 'pending' || d.status === 'in_transit').forEach(delivery => {
    const option = document.createElement('option');
    option.value = delivery.id;
    option.textContent = `${delivery.id} - ${delivery.supplier}`;
    select.appendChild(option);
  });
}

function loadDeliveryItems() {
  const deliveryId = document.getElementById('deliverySelect').value;
  const delivery = deliveries.find(d => d.id === deliveryId);
  const tbody = document.getElementById('deliveryItemsTable');
  
  if (!delivery) {
    tbody.innerHTML = '';
    return;
  }
  
  tbody.innerHTML = '';
  delivery.items.forEach((item, index) => {
    const row = `
      <tr>
        <td>${item.product}</td>
        <td>${item.expected_qty}</td>
        <td>
          <input type="number" class="form-control form-control-sm bg-dark text-white border-warning" 
                 value="${item.expected_qty}" min="0" max="${item.expected_qty}" 
                 onchange="updateReceivedQty(${index}, this.value)">
        </td>
        <td>
          <select class="form-select form-select-sm bg-dark text-white border-warning" 
                  onchange="updateCondition(${index}, this.value)">
            <option value="good">Good</option>
            <option value="damaged">Damaged</option>
            <option value="expired">Expired</option>
          </select>
        </td>
        <td>
          <input type="text" class="form-control form-control-sm bg-dark text-white border-warning" 
                 placeholder="Notes..." onchange="updateItemNotes(${index}, this.value)">
        </td>
      </tr>
    `;
    tbody.innerHTML += row;
  });
}

function updateReceivedQty(index, qty) {
  const deliveryId = document.getElementById('deliverySelect').value;
  const delivery = deliveries.find(d => d.id === deliveryId);
  if (delivery && delivery.items[index]) {
    delivery.items[index].received_qty = parseInt(qty);
  }
}

function updateCondition(index, condition) {
  const deliveryId = document.getElementById('deliverySelect').value;
  const delivery = deliveries.find(d => d.id === deliveryId);
  if (delivery && delivery.items[index]) {
    delivery.items[index].condition = condition;
  }
}

function updateItemNotes(index, notes) {
  const deliveryId = document.getElementById('deliverySelect').value;
  const delivery = deliveries.find(d => d.id === deliveryId);
  if (delivery && delivery.items[index]) {
    delivery.items[index].notes = notes;
  }
}

function receiveDelivery(deliveryId) {
  const delivery = deliveries.find(d => d.id === deliveryId);
  if (!delivery) return;
  
  document.getElementById('deliverySelect').value = deliveryId;
  loadDeliveryItems();
  new bootstrap.Modal(document.getElementById('receiveDeliveryModal')).show();
}

async function confirmDelivery() {
  const form = document.getElementById('deliveryForm');
  const deliveryId = document.getElementById('deliverySelect').value;
  const actualDate = document.getElementById('actualDate').value;
  const deliveryPerson = document.getElementById('deliveryPerson').value;
  const vehicleRef = document.getElementById('vehicleRef').value;
  const generalNotes = document.getElementById('generalNotes').value;
  
  if (!deliveryId || !actualDate) {
    showAlert('Please select a delivery and set the actual date', 'warning');
    return;
  }
  
  const delivery = deliveries.find(d => d.id === deliveryId);
  if (!delivery) return;
  
  const deliveryData = {
    delivery_id: deliveryId,
    actual_date: actualDate,
    delivery_person: deliveryPerson,
    vehicle_ref: vehicleRef,
    notes: generalNotes,
    items: delivery.items.map(item => ({
      product_id: item.product_id,
      expected_qty: item.expected_qty,
      received_qty: item.received_qty || item.expected_qty,
      condition: item.condition || 'good',
      notes: item.notes || ''
    }))
  };
  
  try {
    const response = await fetch('<?= base_url("inventory/api/receive-delivery") ?>', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(deliveryData)
    });
    
    const result = await response.json();
    
    if (result.success) {
      await fetchDeliveries(); // Reload deliveries from database
      bootstrap.Modal.getInstance(document.getElementById('receiveDeliveryModal')).hide();
      showAlert('Delivery confirmed successfully!', 'success');
    } else {
      showAlert('Error confirming delivery: ' + (result.errors || 'Unknown error'), 'danger');
    }
  } catch (error) {
    console.error('Error confirming delivery:', error);
    showAlert('Error confirming delivery', 'danger');
  }
}

function viewDeliveryDetails(deliveryId) {
  const delivery = deliveries.find(d => d.id === deliveryId);
  if (!delivery) return;
  
  const totalValue = delivery.items.reduce((sum, item) => 
    sum + ((delivery.status === 'delivered' ? item.received_qty : item.expected_qty) * item.unit_value), 0);
  
  const itemsHtml = delivery.items.map(item => `
    <tr>
      <td>${item.product}</td>
      <td>${item.expected_qty}</td>
      <td>${item.received_qty || '-'}</td>
      <td>${item.condition ? `<span class="badge ${item.condition === 'good' ? 'bg-success' : 'bg-warning'}">${item.condition}</span>` : '-'}</td>
      <td>${item.notes || '-'}</td>
    </tr>
  `).join('');
  
  const content = `
    <div class="row g-3 mb-4">
      <div class="col-md-6">
        <strong class="text-warning">Delivery ID:</strong><br>
        ${delivery.id}
      </div>
      <div class="col-md-6">
        <strong class="text-warning">Supplier:</strong><br>
        ${delivery.supplier}
      </div>
      <div class="col-md-6">
        <strong class="text-warning">Expected Date:</strong><br>
        ${formatDate(delivery.expected_date)}
      </div>
      <div class="col-md-6">
        <strong class="text-warning">Actual Date:</strong><br>
        ${delivery.actual_date ? formatDateTime(delivery.actual_date) : 'Not delivered yet'}
      </div>
      <div class="col-md-6">
        <strong class="text-warning">Status:</strong><br>
        <span class="badge ${getDeliveryStatusClass(delivery.status)}">${delivery.status.toUpperCase().replace('_', ' ')}</span>
      </div>
      <div class="col-md-6">
        <strong class="text-warning">Total Value:</strong><br>
        ₱${totalValue.toLocaleString()}
      </div>
    </div>
    
    <h6 class="text-warning mb-3">Items</h6>
    <div class="table-responsive mb-4">
      <table class="table table-dark table-sm">
        <thead>
          <tr>
            <th>Product</th>
            <th>Expected</th>
            <th>Received</th>
            <th>Condition</th>
            <th>Notes</th>
          </tr>
        </thead>
        <tbody>
          ${itemsHtml}
        </tbody>
      </table>
    </div>
    
    ${delivery.received_by ? `
      <div class="row g-3">
        <div class="col-md-6">
          <strong class="text-warning">Received By:</strong><br>
          ${delivery.received_by}
        </div>
        <div class="col-md-6">
          <strong class="text-warning">Delivery Person:</strong><br>
          ${delivery.delivery_person || 'Not specified'}
        </div>
        <div class="col-md-6">
          <strong class="text-warning">Vehicle/Reference:</strong><br>
          ${delivery.vehicle_ref || 'Not specified'}
        </div>
        <div class="col-12">
          <strong class="text-warning">Notes:</strong><br>
          ${delivery.notes || 'No notes'}
        </div>
      </div>
    ` : ''}
  `;
  
  document.getElementById('deliveryDetailsContent').innerHTML = content;
  new bootstrap.Modal(document.getElementById('viewDeliveryModal')).show();
}

function exportDeliveries() {
  const filteredData = getFilteredDeliveries();
  const csv = convertDeliveriesToCSV(filteredData);
  downloadCSV(csv, 'deliveries.csv');
}

function convertDeliveriesToCSV(data) {
  const headers = ['Delivery ID', 'Supplier', 'Expected Date', 'Actual Date', 'Status', 'Items Count', 'Total Value', 'Received By'];
  const rows = data.map(delivery => {
    // Calculate total value based on delivery type
    let totalValue = 0;
    if (delivery.is_pending_schedule && delivery.total_amount) {
      // For pending schedules, use the PO total amount directly
      totalValue = delivery.total_amount;
    } else if (delivery.total_amount && (delivery.items || []).length === 0) {
      // For actual deliveries with no items but PO total available, use PO total
      totalValue = delivery.total_amount;
    } else {
      // For actual deliveries, calculate from items based on status
      totalValue = (delivery.items || []).reduce((sum, item) => {
        let qty = 0;
        if (delivery.status === 'delivered') {
          qty = item.received_quantity || item.received_qty || 0;
        } else {
          qty = item.expected_quantity || item.expected_qty || 0;
        }
        const cost = item.unit_cost || item.unit_value || 0;
        return sum + (qty * cost);
      }, 0);
    }
    return [
      delivery.id,
      delivery.supplier,
      delivery.expected_date,
      delivery.actual_date || '',
      delivery.status,
      delivery.total_quantity > 0 ? delivery.total_quantity :
      (delivery.items && delivery.items.length > 0) ? delivery.items.length :
      (delivery.is_pending_schedule ? delivery.items.length : 0),
      totalValue,
      delivery.received_by || ''
    ];
  });

  return [headers, ...rows].map(row => row.join(',')).join('\n');
}

function downloadCSV(csv, filename) {
  const blob = new Blob([csv], { type: 'text/csv' });
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.setAttribute('hidden', '');
  a.setAttribute('href', url);
  a.setAttribute('download', filename);
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
}

function formatDate(dateString) {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
}

function formatDateTime(dateString) {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

function printDeliveryReceipt() {
  window.print();
}

function showAlert(message, type) {
  const alertDiv = document.createElement('div');
  alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
  alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
  alertDiv.innerHTML = `
    ${message}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  `;
  document.body.appendChild(alertDiv);
  
  setTimeout(() => {
    if (alertDiv.parentNode) {
      alertDiv.parentNode.removeChild(alertDiv);
    }
  }, 5000);
}
</script>

<?= $this->include('shared/footer') ?>
