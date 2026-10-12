<?php
require_once __DIR__ . '/includes/auth-check.php';
$pageTitle = 'Notifications';
$pageDescription = 'Your account notifications and order updates.';
$noIndex = true;
require_once __DIR__ . '/includes/header.php';
$notifList = bxm_notifications($currentUser['uid'], 50);
?>
<div class="container" id="bxmConfigNotice"></div>

<section class="bxm-section-sm">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
      <div>
        <h1 class="bxm-section-title mb-1">Notifications</h1>
        <p class="bxm-section-sub mb-0">Order and account updates for you.</p>
      </div>
      <button type="button" class="bxm-btn bxm-btn-outline bxm-btn-sm" id="notifMarkAllBtn"><i class="bi bi-check2-all"></i> Mark all as read</button>
    </div>
    <div class="bxm-card p-0" id="notifFullList"></div>
  </div>
</section>
<?php
$encodedNotifs = json_encode($notifList, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$inlineScript = <<<HTML
<script>
(function () {
  var notifications = {$encodedNotifs} || [];

  function icon(type) {
    var map = {
      success: 'bi-check-circle-fill text-success',
      danger: 'bi-x-circle-fill text-danger',
      warning: 'bi-exclamation-triangle-fill text-warning',
      info: 'bi-info-circle-fill text-info'
    };
    return map[type] || map.info;
  }

  function render() {
    var host = document.getElementById('notifFullList');
    if (!notifications.length) {
      host.innerHTML = '<div class="bxm-state-box text-center"><i class="bi bi-bell-slash bxm-state-icon"></i><h5>No notifications yet</h5><p class="text-secondary mb-0">Order and account updates will appear here.</p></div>';
      return;
    }
    host.innerHTML = notifications.map(function (n) {
      var unread = !n.read;
      return '<a class="bxm-notif-item bxm-notif-item-lg' + (unread ? ' unread' : '') + '" href="' + window.BXM.escapeHtml(n.link || '#') + '" data-id="' + window.BXM.escapeHtml(n.id) + '">' +
        '<span class="bxm-notif-icon"><i class="bi ' + icon(n.type) + '"></i></span>' +
        '<span class="flex-grow-1 min-w-0"><span class="bxm-notif-title">' + window.BXM.escapeHtml(n.title || 'Notification') + '</span>' +
        '<span class="bxm-notif-msg">' + window.BXM.escapeHtml(n.message || '') + '</span>' +
        '<span class="bxm-notif-time">' + window.BXM.timeAgo(n.createdAt) + '</span></span>' +
        (unread ? '<span class="bxm-notif-dot"></span>' : '') +
        '</a>';
    }).join('');
  }

  function markRead(id) {
    if (!id) return;
    notifications.forEach(function (n) { if (String(n.id) === String(id)) n.read = true; });
    render();
    window.BXM.apiFetch('notifications.php', { method: 'POST', json: { action: 'read', id: id } });
    window.BXM.loadNotifications();
  }

  document.getElementById('notifFullList').addEventListener('click', function (e) {
    var item = e.target.closest('[data-id]');
    if (item) markRead(item.getAttribute('data-id'));
  });

  document.getElementById('notifMarkAllBtn').addEventListener('click', function () {
    notifications.forEach(function (n) { n.read = true; });
    render();
    window.BXM.markAllNotificationsRead();
  });

  render();
})();
</script>
HTML;
include __DIR__ . '/includes/footer.php';
?>
