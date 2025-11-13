<?= $this->include('shared/header') ?>

<div class="main-container">
  <button class="btn btn-primary d-md-none position-fixed top-0 start-0 m-3" style="z-index:1100" data-bs-toggle="offcanvas" data-bs-target="#sidebar"><i class="bi bi-list"></i></button>
  <div id="mobileOverlay" class="d-md-none" onclick="closeSidebar()"></div>

  <?= $this->include('shared/sidebar') ?>

  <main class="main-content">
    <div class="header">
      <div>
        <h2>Supplier Performance Reports</h2>
        <p class="mb-0">Monitor supplier performance metrics, delivery times, and order fulfillment</p>
        <p class="mb-0">Role: Central Office Admin</p>
      </div>
      <div class="user-info">
        <div class="text-end">
          <div style="color:#ffd700;font-weight:600;">Central Office Admin</div>
          <div style="color:#ffffff;font-size:14px;">Supplier Analytics</div>
        </div>
        <div class="user-avatar">CA</div>
      </div>
    </div>

    <!-- Supplier Performance Table -->
    <div class="custom-card mb-4">
      <h3 class="text-warning mb-3 fs-5">📊 Supplier Performance (Last 90 Days)</h3>
      <div class="table-responsive">
        <table class="table table-dark table-hover">
          <thead>
            <tr>
              <th>Supplier</th>
              <th>Contact Person</th>
              <th>Total Orders</th>
              <th>Delivered Orders</th>
              <th>On-Time Rate</th>
              <th>Avg Delivery Days</th>
              <th>Total Order Value</th>
              <th>Performance</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($suppliers)): ?>
              <?php foreach ($suppliers as $supplier): ?>
                <?php $perf = $supplier['performance']; ?>
              <tr>
                <td><strong><?= esc($supplier['company_name']) ?></strong></td>
                <td><?= esc($supplier['contact_person']) ?></td>
                <td><?= $perf['total_orders'] ?></td>
                <td><?= $perf['delivered_orders'] ?></td>
                <td>
                  <?php
                  $rate = $perf['on_time_rate'];
                  $rateClass = $rate >= 90 ? 'badge-high' : ($rate >= 70 ? 'badge-normal' : 'badge-low');
                  ?>
                  <span class="badge <?= $rateClass ?>"><?= $rate ?>%</span>
                </td>
                <td><?= $perf['avg_delivery_days'] > 0 ? $perf['avg_delivery_days'] . ' days' : 'N/A' ?></td>
                <td>₱<?= number_format($perf['total_order_value'], 2) ?></td>
                <td>
                  <?php
                  $overallScore = 0;
                  if ($perf['total_orders'] > 0) {
                    $overallScore = ($perf['on_time_rate'] * 0.5) + (($perf['delivered_orders'] / $perf['total_orders']) * 50);
                  }
                  $scoreClass = $overallScore >= 80 ? 'badge-high' : ($overallScore >= 60 ? 'badge-normal' : 'badge-low');
                  ?>
                  <span class="badge <?= $scoreClass ?>"><?= round($overallScore, 1) ?>%</span>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="8" class="text-center text-muted">
                  <i class="bi bi-info-circle"></i> No supplier data available
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Performance Summary Cards -->
    <div class="row g-3 mb-4">
      <?php
      $totalSuppliers = count($suppliers);
      $highPerformers = 0;
      $totalOrderValue = 0;
      $avgOnTimeRate = 0;
      
      if (!empty($suppliers)) {
        foreach ($suppliers as $supplier) {
          $perf = $supplier['performance'];
          if ($perf['on_time_rate'] >= 90) $highPerformers++;
          $totalOrderValue += $perf['total_order_value'];
          $avgOnTimeRate += $perf['on_time_rate'];
        }
        $avgOnTimeRate = $totalSuppliers > 0 ? round($avgOnTimeRate / $totalSuppliers, 1) : 0;
      }
      ?>
      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-number"><?= $totalSuppliers ?></div>
          <div class="stat-label">Active Suppliers</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-number"><?= $highPerformers ?></div>
          <div class="stat-label">High Performers (≥90%)</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-number"><?= $avgOnTimeRate ?>%</div>
          <div class="stat-label">Avg On-Time Rate</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-number">₱<?= number_format($totalOrderValue, 0) ?></div>
          <div class="stat-label">Total Order Value</div>
        </div>
      </div>
    </div>
  </main>
</div>

<?= $this->include('shared/footer') ?>
