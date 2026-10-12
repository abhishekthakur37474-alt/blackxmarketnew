<?php
require_once __DIR__ . '/../includes/helpers.php';

if (bxm_is_admin()) {
    bxm_redirect(bxm_url('admin/index.php'));
}

if (!bxm_has_admin()) {
    bxm_redirect(bxm_url('admin/register.php'));
}

$pageTitle = 'Admin Login';
$pageDescription = 'Login to the BLACK X MARKET admin panel.';
$noIndex = true;
require_once __DIR__ . '/../includes/header.php';
?>
<section class="bxm-auth-wrap">
  <div class="container">
    <div class="bxm-auth-card">
      <div class="text-center mb-4">
        <img src="<?= bxm_url('assets/images/logo/logo.jpeg') ?>" alt="BLACK X MARKET" width="56" height="56" class="bxm-brand-logo mb-3">
        <h1 class="h4 mb-1">Admin Login</h1>
        <p class="text-secondary small mb-0">Sign in to manage BLACK X MARKET.</p>
      </div>
      <form id="adminLoginForm" novalidate>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" class="form-control" id="adminEmail" placeholder="admin@example.com" required>
        </div>
        <div class="mb-4">
          <label class="form-label">Password</label>
          <input type="password" class="form-control" id="adminPassword" placeholder="Your password" required>
        </div>
        <button type="submit" class="bxm-btn bxm-btn-primary w-100" id="adminLoginBtn">Login as Admin</button>
      </form>
    </div>
  </div>
</section>
<?php
$inlineScript = <<<'HTML'
<script>
(function () {
  document.getElementById('adminLoginForm').addEventListener('submit', function (e) {
    e.preventDefault();
    var email = document.getElementById('adminEmail').value.trim();
    var password = document.getElementById('adminPassword').value;
    var btn = document.getElementById('adminLoginBtn');
    if (!email || !password) { window.BXM.toast('Please fill in all fields', 'warning'); return; }
    btn.disabled = true;
    window.BXM.apiFetch('auth.php', { method: 'POST', json: { action: 'login', email: email, password: password } })
      .then(function (res) {
        if (!res || !res.ok) throw new Error((res && res.error) || 'Login failed');
        if (!res.user || res.user.role !== 'admin') {
          return window.BXM.apiFetch('auth.php', { method: 'POST', json: { action: 'logout' } }).then(function () {
            throw new Error('This account is not an admin.');
          });
        }
        window.BXM.toast('Welcome, Admin', 'success');
        setTimeout(function () { window.location.replace(window.BXM.url('admin/index.php')); }, 400);
      }).catch(function (err) {
        btn.disabled = false;
        window.BXM.toast(err.message || 'Login failed', 'danger');
      });
  });
})();
</script>
HTML;
include __DIR__ . '/../includes/footer.php';
?>
