<?php
require_once __DIR__ . '/../includes/helpers.php';

if (bxm_is_admin()) {
    bxm_redirect(bxm_url('admin/index.php'));
}

if (bxm_has_admin()) {
    bxm_redirect(bxm_url('admin/login.php'));
}

$pageTitle = 'Create Admin Account';
$pageDescription = 'Create the first BLACK X MARKET admin account.';
$noIndex = true;
require_once __DIR__ . '/../includes/header.php';
?>
<section class="bxm-auth-wrap">
  <div class="container">
    <div class="bxm-auth-card">
      <div class="text-center mb-4">
        <img src="<?= bxm_url('assets/images/logo/logo.jpeg') ?>" alt="BLACK X MARKET" width="56" height="56" class="bxm-brand-logo mb-3">
        <h1 class="h4 mb-1">Create Admin Account</h1>
        <p class="text-secondary small mb-0">No admin exists yet. Register the first admin.</p>
      </div>
      <form id="adminRegisterForm" novalidate>
        <div class="mb-3">
          <label class="form-label">Full Name</label>
          <input type="text" class="form-control" id="adminRegName" placeholder="Admin name" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" class="form-control" id="adminRegEmail" placeholder="admin@example.com" required>
        </div>
        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <label class="form-label">Password</label>
            <input type="password" class="form-control" id="adminRegPassword" minlength="6" placeholder="Min 6 characters" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Confirm Password</label>
            <input type="password" class="form-control" id="adminRegConfirm" minlength="6" required>
          </div>
        </div>
        <button type="submit" class="bxm-btn bxm-btn-primary w-100" id="adminRegisterBtn">Create Admin</button>
      </form>
    </div>
  </div>
</section>
<?php
$inlineScript = <<<'HTML'
<script>
(function () {
  document.getElementById('adminRegisterForm').addEventListener('submit', function (e) {
    e.preventDefault();
    var name = document.getElementById('adminRegName').value.trim();
    var email = document.getElementById('adminRegEmail').value.trim();
    var pass = document.getElementById('adminRegPassword').value;
    var confirm = document.getElementById('adminRegConfirm').value;
    var btn = document.getElementById('adminRegisterBtn');

    if (!name || !email || !pass) { window.BXM.toast('Please fill in all fields', 'warning'); return; }
    if (pass.length < 6) { window.BXM.toast('Password must be at least 6 characters', 'warning'); return; }
    if (pass !== confirm) { window.BXM.toast('Passwords do not match', 'warning'); return; }

    btn.disabled = true;
    window.BXM.apiFetch('auth.php', { method: 'POST', json: { action: 'register-admin', name: name, email: email, password: pass } })
      .then(function (res) {
        if (!res || !res.ok) throw new Error((res && res.error) || 'Registration failed');
        window.BXM.toast('Admin account created', 'success');
        setTimeout(function () { window.location.replace(window.BXM.url('admin/index.php')); }, 500);
      }).catch(function (err) {
        btn.disabled = false;
        window.BXM.toast(err.message || 'Registration failed', 'danger');
      });
  });
})();
</script>
HTML;
include __DIR__ . '/../includes/footer.php';
?>
