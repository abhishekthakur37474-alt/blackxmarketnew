<?php
require_once __DIR__ . '/includes/auth-check.php';
$pageTitle = 'Customer Support';
$pageDescription = 'Get help with your orders and account.';
$activePage = 'support';
require_once __DIR__ . '/includes/header.php';
$prefillOrder = isset($_GET['order']) ? preg_replace('/[^A-Za-z0-9_-]/', '', $_GET['order']) : '';
$ticketList = bxm_tickets_by_user($currentUser['uid']);
?>
<div class="container" id="bxmConfigNotice"></div>

<section class="bxm-section-sm">
  <div class="container">
    <div class="mb-4">
      <h1 class="bxm-section-title">Customer Support</h1>
      <p class="bxm-section-sub mb-0">How can we help? Submit a ticket and our team will respond.</p>
    </div>

    <div class="row g-4">
      <div class="col-lg-5">
        <div class="bxm-card p-4">
          <h6 class="mb-3">Submit a Ticket</h6>
          <form id="ticketForm">
            <div class="mb-3">
              <label class="form-label">Order ID (optional)</label>
              <input type="text" class="form-control" id="ticketOrder" placeholder="BXM-2026-XXXXXX" value="<?= bxm_e($prefillOrder) ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">Subject</label>
              <input type="text" class="form-control" id="ticketSubject" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Message</label>
              <textarea class="form-control" id="ticketMessage" rows="5" required></textarea>
            </div>
            <button type="submit" class="bxm-btn bxm-btn-primary w-100"><i class="bi bi-send"></i> Submit Ticket</button>
          </form>
        </div>
      </div>
      <div class="col-lg-7">
        <h6 class="mb-3">My Tickets</h6>
        <div id="ticketsList"></div>
      </div>
    </div>
  </div>
</section>
<?php
$encodedTickets = json_encode($ticketList, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$inlineScript = <<<HTML
<script>
(function () {
  var tickets = {$encodedTickets} || [];

  function renderTickets(list) {
    list = (list || []).slice().sort(function (a, b) { return (b.createdAt || 0) - (a.createdAt || 0); });
    var host = document.getElementById('ticketsList');
    if (!list.length) {
      host.innerHTML = '<div class="bxm-state-box text-center"><i class="bi bi-life-preserver bxm-state-icon"></i><p class="text-secondary mb-0">No support tickets yet.</p></div>';
      return;
    }
    host.innerHTML = list.map(function (t) {
      return '<div class="bxm-card p-3 mb-3">' +
        '<div class="d-flex justify-content-between align-items-start gap-2">' +
        '<div><div class="fw-semibold">' + window.BXM.escapeHtml(t.subject || 'Ticket') + '</div>' +
        '<div class="small text-muted">' + (t.orderId ? 'Order: ' + window.BXM.escapeHtml(t.orderId) + ' - ' : '') + new Date(t.createdAt || Date.now()).toLocaleString() + '</div></div>' +
        window.BXM.statusBadge(t.status === 'replied' ? 'replied' : (t.status || 'open')) + '</div>' +
        '<p class="text-secondary small mt-2 mb-1">' + window.BXM.escapeHtml(t.message || '') + '</p>' +
        (t.reply ? '<div class="bxm-instruction-box mt-2"><b class="text-white">Support Reply:</b>\n' + window.BXM.escapeHtml(t.reply) + '</div>' : '') +
        '</div>';
    }).join('');
  }

  function loadTickets() {
    window.BXM.apiFetch('support.php?action=list').then(function (res) {
      if (res && res.ok) {
        tickets = res.tickets || [];
        renderTickets(tickets);
      }
    });
  }

  document.getElementById('ticketForm').addEventListener('submit', function (e) {
    e.preventDefault();
    var subject = document.getElementById('ticketSubject').value.trim();
    var message = document.getElementById('ticketMessage').value.trim();
    var orderId = document.getElementById('ticketOrder').value.trim();
    if (!subject || !message) { window.BXM.toast('Please fill in subject and message', 'warning'); return; }
    window.BXM.loader(true);
    window.BXM.apiFetch('support.php', { method: 'POST', json: { subject: subject, message: message, orderId: orderId } })
      .then(function (res) {
        window.BXM.loader(false);
        if (!res || !res.ok) throw new Error((res && res.error) || 'Could not submit ticket');
        document.getElementById('ticketForm').reset();
        window.BXM.toast('Ticket submitted successfully', 'success');
        loadTickets();
      }).catch(function (err) {
        window.BXM.loader(false);
        window.BXM.toast(err.message || 'Could not submit ticket', 'danger');
      });
  });

  renderTickets(tickets);
})();
</script>
HTML;
include __DIR__ . '/includes/footer.php';
?>
