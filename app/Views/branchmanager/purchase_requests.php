<?= $this->include('shared/header') ?>

<div class="main-container">
  <button class="btn btn-primary d-md-none position-fixed top-0 start-0 m-3" style="z-index:1100" data-bs-toggle="offcanvas" data-bs-target="#sidebar"><i class="bi bi-list"></i></button>
  <div id="mobileOverlay" class="d-md-none" onclick="closeSidebar()"></div>

  <?= $this->include('shared/sidebar') ?>

  <main class="main-content">
    <div class="header">
      <div>
        <h2>Purchase Requests Management</h2>
        <p class="mb-0">Create, manage, and track purchase requests for your branch</p>
        <p class="mb-0">Branch: <?= $branch['name'] ?? 'Unknown Branch' ?></p>
      </div>
      <div>
        <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#createRequestModal">
          <i class="bi bi-plus-circle"></i> New Request
        </button>
        <button class="btn btn-outline-primary" onclick="exportRequests()">
          <i class="bi bi-download"></i> Export
        </button>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-number" id="totalRequests">0</div>
          <div class="stat-label">Total Requests</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-number text-warning" id="pendingRequests">0</div>
          <div class="stat-label">Pending Approval</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-number text-success" id="approvedRequests">0</div>
          <div class="stat-label">Approved</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-number text-info" id="totalValue">₱0</div>
          <div class="stat-label">Total Value</div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="custom-card mb-4">
      <div class="row g-3">
        <div class="col-md-3">
          <input type="text" class="form-control bg-dark text-white border-warning" 
                 id="searchInput" placeholder="Search requests...">
        </div>
        <div class="col-md-3">
          <select class="form-select bg-dark text-white border-warning" id="statusFilter">
            <option value="">All Status</option>
            <option value="draft">Draft</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="ordered">Ordered</option>
            <option value="delivered">Delivered</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
        <div class="col-md-3">
          <input type="date" class="form-control bg-dark text-white border-warning" id="dateFilter">
        </div>
        <div class="col-md-3">
          <button class="btn btn-outline-warning w-100" onclick="clearFilters()">
            <i class="bi bi-x-circle"></i> Clear Filters
          </button>
        </div>
      </div>
    </div>

    <!-- Purchase Requests Table -->
    <div class="custom-card">
      <div class="table-responsive">
        <table class="table table-dark table-hover" id="requestsTable">
          <thead>
            <tr>
              <th>PO Number</th>
              <th>Supplier</th>
              <th>Items Count</th>
              <th>Total Amount</th>
              <th>Requested Date</th>
              <th>Expected Delivery</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="requestsTableBody">
            <!-- Dynamic content will be loaded here -->
          </tbody>
        </table>
      </div>
      
      <!-- Pagination -->
      <nav aria-label="Requests pagination" class="mt-3">
        <ul class="pagination justify-content-center" id="pagination">
          <!-- Pagination will be generated dynamically -->
        </ul>
      </nav>
    </div>
  </main>
</div>

<!-- Create Purchase Request Modal -->
<div class="modal fade" id="createRequestModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content bg-dark text-white">
      <div class="modal-header border-warning">
        <h5 class="modal-title text-warning">Create Purchase Request</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="purchaseRequestForm">
          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label text-warning">Supplier</label>
              <select class="form-select bg-dark text-white border-warning" id="supplierSelect" required>
                <option value="">Select Supplier</option>
                <!-- Dynamic suppliers will be loaded here -->
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label text-warning">Expected Delivery Date</label>
              <input type="date" class="form-control bg-dark text-white border-warning" 
                     id="expectedDelivery" required>
            </div>
            <div class="col-12">
              <label class="form-label text-warning">Notes</label>
              <textarea class="form-control bg-dark text-white border-warning" 
                        id="requestNotes" rows="3" placeholder="Additional notes for this purchase request..."></textarea>
            </div>
          </div>

          <!-- Items Section -->
          <div class="row g-3 mb-3">
            <div class="col-12">
              <h6 class="text-warning">Add Items to Request</h6>
            </div>
            <div class="col-md-4">
              <label class="form-label text-warning">Product</label>
              <select class="form-select bg-dark text-white border-warning" id="productSelect">
                <option value="">Select Product</option>
                <!-- Dynamic products will be loaded here -->
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label text-warning">Quantity</label>
              <input type="number" class="form-control bg-dark text-white border-warning" 
                     id="itemQuantity" min="1" value="1">
            </div>
            <div class="col-md-2">
              <label class="form-label text-warning">Unit Price</label>
              <input type="number" step="0.01" class="form-control bg-dark text-white border-warning" 
                     id="itemUnitPrice" readonly>
            </div>
            <div class="col-md-2">
              <label class="form-label text-warning">Total</label>
              <input type="number" step="0.01" class="form-control bg-dark text-white border-warning" 
                     id="itemTotal" readonly>
            </div>
            <div class="col-md-2">
              <label class="form-label text-warning">&nbsp;</label>
              <button type="button" class="btn btn-primary w-100" onclick="addItemToRequest()">
                <i class="bi bi-plus"></i> Add
              </button>
            </div>
          </div>

          <!-- Items List -->
          <div class="table-responsive">
            <table class="table table-dark table-sm" id="itemsTable">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Quantity</th>
                  <th>Unit Price</th>
                  <th>Total</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="itemsTableBody">
                <!-- Dynamic items will be added here -->
              </tbody>
            </table>
          </div>

          <!-- Total Summary -->
          <div class="row g-3 mt-3">
            <div class="col-md-6">
              <div class="alert alert-info">
                <strong>Total Items:</strong> <span id="totalItemsCount">0</span><br>
                <strong>Total Amount:</strong> ₱<span id="totalAmount">0.00</span>
              </div>
            </div>
            <div class="col-md-6 text-end">
              <button type="button" class="btn btn-outline-secondary me-2" onclick="saveAsDraft()">
                <i class="bi bi-save"></i> Save as Draft
              </button>
              <button type="button" class="btn btn-primary" onclick="submitRequest()">
                <i class="bi bi-send"></i> Submit Request
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- View Request Details Modal -->
<div class="modal fade" id="viewRequestModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content bg-dark text-white">
      <div class="modal-header border-warning">
        <h5 class="modal-title text-warning">Purchase Request Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="requestDetailsContent">
        <!-- Dynamic content will be loaded here -->
      </div>
      <div class="modal-footer border-warning">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-warning" onclick="editRequest()" id="editRequestBtn">
          <i class="bi bi-pencil"></i> Edit
        </button>
      </div>
    </div>
  </div>
</div>

<script>
// Global variables
let requestsData = [];
let suppliersData = [];
let productsData = [];
let requestItems = [];
let currentPage = 1;
const itemsPerPage = 10;

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
  loadRequestsData();
  loadSuppliers();
  loadProducts();
  setTodayDate();
  
  // Event listeners
  document.getElementById('searchInput').addEventListener('input', filterRequests);
  document.getElementById('statusFilter').addEventListener('change', filterRequests);
  document.getElementById('dateFilter').addEventListener('change', filterRequests);
  
  // Product selection change
  document.getElementById('productSelect').addEventListener('change', updateItemPrice);
  document.getElementById('itemQuantity').addEventListener('input', calculateItemTotal);
});

async function loadRequestsData() {
  try {
    const response = await fetch('<?= base_url("branchmanager/api/purchase-requests") ?>');
    const result = await response.json();
    
    if (result.success) {
      requestsData = result.data;
      loadRequestsTable();
      updateStats();
    } else {
      console.error('Failed to load requests data:', result.error);
      showAlert('Failed to load purchase requests', 'danger');
    }
  } catch (error) {
    console.error('Error loading requests data:', error);
    showAlert('Error loading purchase requests', 'danger');
  }
}

async function loadSuppliers() {
  try {
    const response = await fetch('<?= base_url("branchmanager/api/suppliers") ?>');
    const result = await response.json();
    
    if (result.success) {
      suppliersData = result.data;
      const select = document.getElementById('supplierSelect');
      select.innerHTML = '<option value="">Select Supplier</option>' + 
        result.data.map(supplier => `<option value="${supplier.id}">${supplier.company_name}</option>`).join('');
    }
  } catch (error) {
    console.error('Error loading suppliers:', error);
  }
}

async function loadProducts() {
  try {
    const response = await fetch('<?= base_url("branchmanager/api/products") ?>');
    const result = await response.json();
    
    if (result.success) {
      productsData = result.data;
      const select = document.getElementById('productSelect');
      select.innerHTML = '<option value="">Select Product</option>' + 
        result.data.map(product => `<option value="${product.id}" data-price="${product.unit_price}">${product.product_name} (${product.product_code})</option>`).join('');
    }
  } catch (error) {
    console.error('Error loading products:', error);
  }
}

function loadRequestsTable() {
  const tbody = document.getElementById('requestsTableBody');
  tbody.innerHTML = '';
  
  if (!requestsData || requestsData.length === 0) {
    tbody.innerHTML = '<tr><td colspan="8" class="text-center">No purchase requests found</td></tr>';
    return;
  }
  
  const filteredData = getFilteredRequestsData();
  const startIndex = (currentPage - 1) * itemsPerPage;
  const endIndex = startIndex + itemsPerPage;
  const pageData = filteredData.slice(startIndex, endIndex);
  
  pageData.forEach(request => {
    const statusClass = getStatusClass(request.status);
    const requestedDate = formatDate(request.requested_date);
    const expectedDelivery = request.expected_delivery ? formatDate(request.expected_delivery) : 'N/A';
    
    const row = `
      <tr>
        <td><strong>${request.po_number || ''}</strong></td>
        <td>${request.company_name || 'Unknown Supplier'}</td>
        <td>${request.items_count || 0}</td>
        <td>₱${parseFloat(request.total_amount || 0).toLocaleString()}</td>
        <td>${requestedDate}</td>
        <td>${expectedDelivery}</td>
        <td><span class="badge ${statusClass}">${request.status.toUpperCase()}</span></td>
        <td>
          <button class="btn btn-sm btn-outline-info me-1" onclick="viewRequestDetails(${request.id})" title="View Details">
            <i class="bi bi-eye"></i>
          </button>
          ${request.status === 'draft' ? `
            <button class="btn btn-sm btn-outline-warning me-1" onclick="editRequest(${request.id})" title="Edit">
              <i class="bi bi-pencil"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger" onclick="deleteRequest(${request.id})" title="Delete">
              <i class="bi bi-trash"></i>
            </button>
          ` : ''}
        </td>
      </tr>
    `;
    tbody.innerHTML += row;
  });
  
  updatePagination(filteredData.length);
}

function getFilteredRequestsData() {
  const search = document.getElementById('searchInput').value.toLowerCase();
  const status = document.getElementById('statusFilter').value;
  const date = document.getElementById('dateFilter').value;
  
  return requestsData.filter(request => {
    const matchesSearch = request.po_number.toLowerCase().includes(search) || 
                         request.company_name.toLowerCase().includes(search);
    const matchesStatus = !status || request.status === status;
    const matchesDate = !date || request.requested_date.startsWith(date);
    
    return matchesSearch && matchesStatus && matchesDate;
  });
}

function getStatusClass(status) {
  switch(status) {
    case 'draft': return 'bg-secondary';
    case 'pending': return 'bg-warning text-dark';
    case 'approved': return 'bg-success';
    case 'rejected': return 'bg-danger';
    case 'ordered': return 'bg-info';
    case 'delivered': return 'bg-primary';
    case 'cancelled': return 'bg-dark';
    default: return 'bg-secondary';
  }
}

function updateStats() {
  const totalRequests = requestsData.length;
  const pendingRequests = requestsData.filter(r => r.status === 'pending').length;
  const approvedRequests = requestsData.filter(r => r.status === 'approved').length;
  const totalValue = requestsData.reduce((sum, r) => sum + parseFloat(r.total_amount || 0), 0);
  
  // Check if elements exist before setting textContent
  const totalRequestsEl = document.getElementById('totalRequests');
  const pendingRequestsEl = document.getElementById('pendingRequests');
  const approvedRequestsEl = document.getElementById('approvedRequests');
  const totalValueEl = document.getElementById('totalValue');
  
  if (totalRequestsEl) totalRequestsEl.textContent = totalRequests;
  if (pendingRequestsEl) pendingRequestsEl.textContent = pendingRequests;
  if (approvedRequestsEl) approvedRequestsEl.textContent = approvedRequests;
  if (totalValueEl) totalValueEl.textContent = '₱' + totalValue.toLocaleString();
}

function filterRequests() {
  currentPage = 1;
  loadRequestsTable();
}

function clearFilters() {
  document.getElementById('searchInput').value = '';
  document.getElementById('statusFilter').value = '';
  document.getElementById('dateFilter').value = '';
  filterRequests();
}

function updatePagination(totalItems) {
  const totalPages = Math.ceil(totalItems / itemsPerPage);
  const pagination = document.getElementById('pagination');
  pagination.innerHTML = '';
  
  if (totalPages <= 1) return;
  
  for (let i = 1; i <= totalPages; i++) {
    const li = document.createElement('li');
    li.className = `page-item ${i === currentPage ? 'active' : ''}`;
    li.innerHTML = `<a class="page-link bg-dark text-warning border-warning" href="#" onclick="changePage(${i})">${i}</a>`;
    pagination.appendChild(li);
  }
}

function changePage(page) {
  currentPage = page;
  loadRequestsTable();
}

function updateItemPrice() {
  const productSelect = document.getElementById('productSelect');
  const selectedOption = productSelect.options[productSelect.selectedIndex];
  const unitPrice = selectedOption.getAttribute('data-price') || 0;
  
  document.getElementById('itemUnitPrice').value = unitPrice;
  calculateItemTotal();
}

function calculateItemTotal() {
  const quantity = parseFloat(document.getElementById('itemQuantity').value) || 0;
  const unitPrice = parseFloat(document.getElementById('itemUnitPrice').value) || 0;
  const total = quantity * unitPrice;
  
  document.getElementById('itemTotal').value = total.toFixed(2);
}

function addItemToRequest() {
  const productSelect = document.getElementById('productSelect');
  const selectedOption = productSelect.options[productSelect.selectedIndex];
  
  if (!selectedOption.value) {
    showAlert('Please select a product', 'warning');
    return;
  }
  
  const quantity = parseInt(document.getElementById('itemQuantity').value);
  const unitPrice = parseFloat(document.getElementById('itemUnitPrice').value);
  const total = quantity * unitPrice;
  
  const item = {
    product_id: selectedOption.value,
    product_name: selectedOption.text,
    quantity: quantity,
    unit_price: unitPrice,
    total: total
  };
  
  requestItems.push(item);
  updateItemsTable();
  updateRequestSummary();
  
  // Reset form
  productSelect.value = '';
  document.getElementById('itemQuantity').value = 1;
  document.getElementById('itemUnitPrice').value = '';
  document.getElementById('itemTotal').value = '';
}

function updateItemsTable() {
  const tbody = document.getElementById('itemsTableBody');
  tbody.innerHTML = '';
  
  requestItems.forEach((item, index) => {
    const row = `
      <tr>
        <td>${item.product_name}</td>
        <td>${item.quantity}</td>
        <td>₱${item.unit_price.toFixed(2)}</td>
        <td>₱${item.total.toFixed(2)}</td>
        <td>
          <button class="btn btn-sm btn-outline-danger" onclick="removeItem(${index})">
            <i class="bi bi-trash"></i>
          </button>
        </td>
      </tr>
    `;
    tbody.innerHTML += row;
  });
}

function removeItem(index) {
  requestItems.splice(index, 1);
  updateItemsTable();
  updateRequestSummary();
}

function updateRequestSummary() {
  const totalItems = requestItems.length;
  const totalAmount = requestItems.reduce((sum, item) => sum + item.total, 0);
  
  // Check if elements exist before setting textContent
  const totalItemsEl = document.getElementById('totalItemsCount');
  const totalAmountEl = document.getElementById('totalAmount');
  
  if (totalItemsEl) {
    totalItemsEl.textContent = totalItems;
  }
  if (totalAmountEl) {
    totalAmountEl.textContent = totalAmount.toFixed(2);
  }
}

async function saveAsDraft() {
  if (requestItems.length === 0) {
    showAlert('Please add at least one item to the request', 'warning');
    return;
  }
  
  const requestData = {
    supplier_id: document.getElementById('supplierSelect').value,
    expected_delivery: document.getElementById('expectedDelivery').value,
    notes: document.getElementById('requestNotes').value,
    items: requestItems,
    status: 'draft'
  };
  
  try {
    const response = await fetch('<?= base_url("branchmanager/api/create-purchase-request") ?>', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(requestData)
    });
    
    const result = await response.json();
    
    if (result.success) {
      await loadRequestsData();
      bootstrap.Modal.getInstance(document.getElementById('createRequestModal')).hide();
      resetForm();
      showAlert('Purchase request saved as draft successfully!', 'success');
    } else {
      showAlert('Error saving request: ' + (result.error || 'Unknown error'), 'danger');
    }
  } catch (error) {
    console.error('Error saving request:', error);
    showAlert('Error saving request: ' + error.message, 'danger');
  }
}

async function submitRequest() {
  if (requestItems.length === 0) {
    showAlert('Please add at least one item to the request', 'warning');
    return;
  }
  
  const requestData = {
    supplier_id: document.getElementById('supplierSelect').value,
    expected_delivery: document.getElementById('expectedDelivery').value,
    notes: document.getElementById('requestNotes').value,
    items: requestItems,
    status: 'pending'
  };
  
  try {
    const response = await fetch('<?= base_url("branchmanager/api/create-purchase-request") ?>', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(requestData)
    });
    
    const result = await response.json();
    
    if (result.success) {
      await loadRequestsData();
      bootstrap.Modal.getInstance(document.getElementById('createRequestModal')).hide();
      resetForm();
      showAlert('Purchase request submitted successfully!', 'success');
    } else {
      showAlert('Error submitting request: ' + (result.error || 'Unknown error'), 'danger');
    }
  } catch (error) {
    console.error('Error submitting request:', error);
    showAlert('Error submitting request: ' + error.message, 'danger');
  }
}

function resetForm() {
  document.getElementById('purchaseRequestForm').reset();
  requestItems = [];
  updateItemsTable();
  updateRequestSummary();
  setTodayDate();
}

function setTodayDate() {
  const today = new Date();
  const tomorrow = new Date(today);
  tomorrow.setDate(tomorrow.getDate() + 7); // Default to 7 days from now
  document.getElementById('expectedDelivery').value = tomorrow.toISOString().split('T')[0];
}

function viewRequestDetails(requestId) {
  const request = requestsData.find(r => r.id === requestId);
  if (!request) return;
  
  const content = `
    <div class="row g-3">
      <div class="col-md-6">
        <strong class="text-warning">PO Number:</strong><br>
        ${request.po_number}
      </div>
      <div class="col-md-6">
        <strong class="text-warning">Supplier:</strong><br>
        ${request.company_name}
      </div>
      <div class="col-md-6">
        <strong class="text-warning">Status:</strong><br>
        <span class="badge ${getStatusClass(request.status)}">${request.status.toUpperCase()}</span>
      </div>
      <div class="col-md-6">
        <strong class="text-warning">Total Amount:</strong><br>
        ₱${parseFloat(request.total_amount).toLocaleString()}
      </div>
      <div class="col-md-6">
        <strong class="text-warning">Requested Date:</strong><br>
        ${formatDate(request.requested_date)}
      </div>
      <div class="col-md-6">
        <strong class="text-warning">Expected Delivery:</strong><br>
        ${request.expected_delivery ? formatDate(request.expected_delivery) : 'N/A'}
      </div>
      <div class="col-12">
        <strong class="text-warning">Notes:</strong><br>
        ${request.notes || 'No notes provided'}
      </div>
    </div>
  `;
  
  document.getElementById('requestDetailsContent').innerHTML = content;
  new bootstrap.Modal(document.getElementById('viewRequestModal')).show();
}

function editRequest(requestId) {
  // In a real implementation, this would open an edit modal
  showAlert('Edit functionality will be implemented in the next phase', 'info');
}

async function deleteRequest(requestId) {
  if (confirm('Are you sure you want to delete this purchase request?')) {
    try {
      const response = await fetch(`<?= base_url("branchmanager/api/delete-purchase-request") ?>/${requestId}`, {
        method: 'DELETE'
      });
      
      const result = await response.json();
      
      if (result.success) {
        await loadRequestsData();
        showAlert('Purchase request deleted successfully!', 'success');
      } else {
        showAlert('Error deleting request: ' + (result.error || 'Unknown error'), 'danger');
      }
    } catch (error) {
      console.error('Error deleting request:', error);
      showAlert('Error deleting request: ' + error.message, 'danger');
    }
  }
}

function exportRequests() {
  const filteredData = getFilteredRequestsData();
  const csv = convertRequestsToCSV(filteredData);
  downloadCSV(csv, 'purchase_requests.csv');
}

function convertRequestsToCSV(data) {
  const headers = ['PO Number', 'Supplier', 'Items Count', 'Total Amount', 'Requested Date', 'Expected Delivery', 'Status'];
  const rows = data.map(request => [
    request.po_number,
    request.company_name,
    request.items_count,
    request.total_amount,
    request.requested_date,
    request.expected_delivery || 'N/A',
    request.status
  ]);
  
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

// Reset form when modal is closed
document.getElementById('createRequestModal').addEventListener('hidden.bs.modal', function () {
  resetForm();
});
</script>

<?= $this->include('shared/footer') ?>


