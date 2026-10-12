<?php
require_once __DIR__ . '/includes/auth-check.php';
$pageTitle = 'My Orders';
$pageDescription = 'Track your digital product orders.';
$noIndex = true;
require_once __DIR__ . '/includes/header.php';
$orderList = bxm_orders_by_user($currentUser['uid']);
?>
<div class="container" id="bxmConfigNotice"></div>

<section class="bxm-section-sm">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
      <h1 class="bxm-section-title mb-0">My Orders</h1>
      <div class="bxm-tabs d-flex gap-2 flex-wrap" id="orderTabs">
        <button class="bxm-btn bxm-btn-outline bxm-btn-sm active" data-filter="all">All</button>
        <button class="bxm-btn bxm-btn-outline bxm-btn-sm" data-filter="pending">Pending</button>
        <button class="bxm-btn bxm-btn-outline bxm-btn-sm" data-filter="approved">Approved</button>
        <button class="bxm-btn bxm-btn-outline bxm-btn-sm" data-filter="rejected">Rejected</button>
      </div>
    </div>
    <div class="row g-3" id="ordersGrid"></div>
  </div>
</section>
<?php
$encodedOrders = json_encode($orderList, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$inlineScript = <<<HTML
<script>
(function () {
  var orders = {$encodedOrders} || [];
  var filter = 'all';

  function simplified(status) {
    if (status === 'approved' || status === 'completed') return 'approved';
    if (status === 'rejected' || status === 'cancelled') return 'rejected';
    return 'pending';
  }

  function render() {
    var grid = document.getElementById('ordersGrid');
    var list = orders.filter(function (o) { return filter === 'all' || simplified(o.status) === filter; });
    if (!list.length) {
      grid.innerHTML = '<div class="col-12"><div class="bxm-state-box text-center"><i class="bi bi-receipt bxm-state-icon"></i><h5>You haven\'t placed any orders yet</h5><p class="text-secondary mb-3">Browse our digital collection to get started.</p><a href="products.php" class="bxm-btn bxm-btn-primary">Browse Products</a></div></div>';
      return;
    }
    grid.innerHTML = list.map(function (o) {
      var status = o.status || 'pending';
      var isApproved = status === 'approved' || status === 'completed';
      var thumb = o.productThumbnail || (o.items && o.items[0] && o.items[0].thumbnailUrl) || '';
      var html = '<div class="col-md-6"><div class="bxm-order-card">';
      html += '<div class="d-flex gap-3">';
      html += '<img src="' + window.BXM.escapeHtml(thumb) + '" class="bxm-table-thumb" style="width:60px;height:60px" alt="">';
      html += '<div class="flex-grow-1 min-w-0">';
      html += '<div class="fw-semibold text-truncate">' + window.BXM.escapeHtml(o.productTitle || 'Order') + '</div>';
      html += '<div class="small text-muted">Order: ' + window.BXM.escapeHtml(o.orderId || '') + '</div>';
      html += '<div class="small text-muted">' + new Date(o.createdAt || Date.now()).toLocaleDateString() + '</div>';
      html += '</div>';
      html += '<div class="text-end"><div class="fw-bold">' + window.BXM.money(o.amount) + '</div><div class="mt-1">' + window.BXM.statusBadge(status) + '</div></div>';
      html += '</div>';
      if (status === 'rejected' && o.rejectionReason) {
        html += '<div class="small text-danger mt-3"><i class="bi bi-info-circle me-1"></i>' + window.BXM.escapeHtml(o.rejectionReason) + '</div>';
      }
      html += '<div class="d-flex gap-2 mt-3">';
      html += '<a href="' + window.BXM.url('order-details.php?id=' + encodeURIComponent(o.orderId)) + '" class="bxm-btn bxm-btn-outline bxm-btn-sm">View Order</a>';
      if (isApproved) {
        html += '<a href="' + window.BXM.url('order-details.php?id=' + encodeURIComponent(o.orderId) + '&card=1') + '" class="bxm-btn bxm-btn-primary bxm-btn-sm"><i class="bi bi-card-checklist"></i> View Card</a>';
      }
      html += '</div></div></div>';
      return html;
    }).join('');
  }

  document.getElementById('orderTabs').addEventListener('click', function (e) {
    var btn = e.target.closest('[data-filter]');
    if (!btn) return;
    filter = btn.getAttribute('data-filter');
    document.querySelectorAll('#orderTabs [data-filter]').forEach(function (b) { b.classList.remove('active', 'bxm-btn-primary'); b.classList.add('bxm-btn-outline'); });
    btn.classList.add('active', 'bxm-btn-primary');
    render();
  });

  render();
})();
</script>
HTML;
include __DIR__ . '/includes/footer.php';
?>
