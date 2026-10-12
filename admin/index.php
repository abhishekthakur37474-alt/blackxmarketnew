<?php
$adminTitle = 'Dashboard';
$adminActive = 'dashboard';
require_once __DIR__ . '/../includes/admin-header.php';

$dashUsers = bxm_all('SELECT uid, name, email, status, created_at FROM users ORDER BY created_at DESC');
$dashOrders = bxm_orders();
$dashStats = [
    'users' => count($dashUsers),
    'products' => (int) bxm_val('SELECT COUNT(*) FROM products'),
    'orders' => count($dashOrders),
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
    'revenue' => 0.0,
    'coupons' => (int) bxm_val("SELECT COUNT(*) FROM coupons WHERE status = 'active'"),
];
foreach ($dashOrders as $dashOrder) {
    $st = $dashOrder['status'];
    if (in_array($st, ['pending', 'payment_submitted', 'under_review'], true)) {
        $dashStats['pending']++;
    }
    if (in_array($st, ['approved', 'completed'], true)) {
        $dashStats['approved']++;
        $dashStats['revenue'] += (float) $dashOrder['amount'];
    }
    if ($st === 'rejected') {
        $dashStats['rejected']++;
    }
}
$dashData = [
    'stats' => $dashStats,
    'recentOrders' => array_slice($dashOrders, 0, 8),
    'recentUsers' => array_slice($dashUsers, 0, 5),
];
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <div>
    <h1 class="bxm-admin-page-title">Welcome back, Admin</h1>
    <p class="bxm-admin-page-sub mb-0">Here is what is happening in your marketplace.</p>
  </div>
  <a href="<?= bxm_url('admin/payments.php') ?>" class="bxm-btn bxm-btn-primary"><i class="bi bi-credit-card"></i> Review Payments</a>
</div>

<div class="row g-3 mb-3">
  <div class="col-6 col-lg-3"><div class="bxm-stat-card d-flex justify-content-between align-items-start"><div><div class="bxm-stat-value" id="statUsers">0</div><div class="bxm-stat-label">Total Users</div></div><span class="bxm-stat-icon"><i class="bi bi-people"></i></span></div></div>
  <div class="col-6 col-lg-3"><div class="bxm-stat-card d-flex justify-content-between align-items-start"><div><div class="bxm-stat-value" id="statProducts">0</div><div class="bxm-stat-label">Products</div></div><span class="bxm-stat-icon"><i class="bi bi-box-seam"></i></span></div></div>
  <div class="col-6 col-lg-3"><div class="bxm-stat-card d-flex justify-content-between align-items-start"><div><div class="bxm-stat-value" id="statOrders">0</div><div class="bxm-stat-label">Total Orders</div></div><span class="bxm-stat-icon"><i class="bi bi-receipt"></i></span></div></div>
  <div class="col-6 col-lg-3"><div class="bxm-stat-card d-flex justify-content-between align-items-start"><div><div class="bxm-stat-value" id="statPending">0</div><div class="bxm-stat-label">Pending Payments</div></div><span class="bxm-stat-icon"><i class="bi bi-hourglass-split"></i></span></div></div>
</div>
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3"><div class="bxm-stat-card d-flex justify-content-between align-items-start"><div><div class="bxm-stat-value" id="statRevenue">₹0</div><div class="bxm-stat-label">Revenue</div></div><span class="bxm-stat-icon"><i class="bi bi-cash-stack"></i></span></div></div>
  <div class="col-6 col-lg-3"><div class="bxm-stat-card d-flex justify-content-between align-items-start"><div><div class="bxm-stat-value" id="statApproved">0</div><div class="bxm-stat-label">Approved</div></div><span class="bxm-stat-icon"><i class="bi bi-check-circle"></i></span></div></div>
  <div class="col-6 col-lg-3"><div class="bxm-stat-card d-flex justify-content-between align-items-start"><div><div class="bxm-stat-value" id="statRejected">0</div><div class="bxm-stat-label">Rejected</div></div><span class="bxm-stat-icon"><i class="bi bi-x-circle"></i></span></div></div>
  <div class="col-6 col-lg-3"><div class="bxm-stat-card d-flex justify-content-between align-items-start"><div><div class="bxm-stat-value" id="statCoupons">0</div><div class="bxm-stat-label">Active Coupons</div></div><span class="bxm-stat-icon"><i class="bi bi-ticket-perforated"></i></span></div></div>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="bxm-table-wrap">
      <div class="p-3 border-bottom bxm-divider d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Recent Orders</h6>
        <a href="<?= bxm_url('admin/orders.php') ?>" class="small bxm-link-muted">View all</a>
      </div>
      <div class="table-responsive">
        <table class="table bxm-table">
          <thead><tr><th>Order ID</th><th>Customer</th><th>Product</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
          <tbody id="recentOrders"><tr><td colspan="6" class="text-center text-muted py-4">Loading...</td></tr></tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="bxm-card p-4 mb-3">
      <h6 class="mb-2">Pending Payments</h6>
      <p class="text-secondary small mb-3"><span id="pendingCount">0</span> payments waiting for verification.</p>
      <a href="<?= bxm_url('admin/payments.php') ?>" class="bxm-btn bxm-btn-outline w-100">Review Payments</a>
    </div>
    <div class="bxm-card p-4">
      <h6 class="mb-3">Recent Users</h6>
      <div id="recentUsers"><p class="text-secondary small mb-0">Loading...</p></div>
    </div>
  </div>
</div>
<?php
$encodedDash = json_encode($dashData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$inlineScript = <<<HTML
<script>
window.BXMAdmin.ready(function () {
  var data = {$encodedDash} || { stats: {}, recentOrders: [], recentUsers: [] };
  var stats = data.stats || {};

  function set(id, val) { var el = document.getElementById(id); if (el) el.textContent = val; }

  set('statUsers', stats.users || 0);
  set('statProducts', stats.products || 0);
  set('statOrders', stats.orders || 0);
  set('statPending', stats.pending || 0);
  set('statApproved', stats.approved || 0);
  set('statRejected', stats.rejected || 0);
  set('statCoupons', stats.coupons || 0);
  set('statRevenue', window.BXM.money(stats.revenue || 0));
  set('pendingCount', stats.pending || 0);

  var userHost = document.getElementById('recentUsers');
  var users = data.recentUsers || [];
  if (!users.length) {
    userHost.innerHTML = '<p class="text-secondary small mb-0">No users yet.</p>';
  } else {
    userHost.innerHTML = users.map(function (u) {
      return '<div class="d-flex justify-content-between align-items-center mb-2"><div class="min-w-0"><div class="small fw-semibold text-truncate">' + window.BXM.escapeHtml(u.name || 'User') + '</div><div class="small text-muted text-truncate">' + window.BXM.escapeHtml(u.email || '') + '</div></div>' + window.BXM.statusBadge(u.status || 'active') + '</div>';
    }).join('');
  }

  var host = document.getElementById('recentOrders');
  var orders = data.recentOrders || [];
  if (!orders.length) {
    host.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No orders yet.</td></tr>';
  } else {
    host.innerHTML = orders.map(function (o) {
      return '<tr><td>' + window.BXM.escapeHtml(o.orderId) + '</td><td>' + window.BXM.escapeHtml(o.userEmail || (o.uid || '').slice(0, 8)) + '</td><td class="text-truncate" style="max-width:180px">' + window.BXM.escapeHtml(o.productTitle || '-') + '</td><td>' + window.BXM.money(o.amount) + '</td><td>' + window.BXM.statusBadge(o.status) + '</td><td>' + window.BXMAdmin.fmtDate(o.createdAt) + '</td></tr>';
    }).join('');
  }
});
</script>
HTML;
require_once __DIR__ . '/../includes/admin-footer.php';
?>
