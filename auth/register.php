<?php
require_once __DIR__ . '/../includes/helpers.php';

if (bxm_is_logged_in()) {
    bxm_redirect(bxm_url('index.php'));
}

$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '';
if (stripos($redirect, 'http') === 0) {
    $redirect = '';
}
$pageTitle = 'Create Account';
$pageDescription = 'Create your BLACK X MARKET account.';
$noIndex = true;
require_once __DIR__ . '/../includes/header.php';
?>
<section class="bxm-auth-wrap">
  <div class="container">
    <div class="bxm-auth-card">
      <div class="text-center mb-4">
        <img src="<?= bxm_url('assets/images/logo/logo.jpeg') ?>" alt="BLACK X MARKET" width="56" height="56" class="bxm-brand-logo mb-3">
        <h1 class="h4 mb-1">Create Account</h1>
        <p class="text-secondary small mb-0">Join BLACK X MARKET in a few seconds.</p>
      </div>
      <form id="registerForm" novalidate>
        <div class="mb-3">
          <label class="form-label">Full Name</label>
          <input type="text" class="form-control" id="regName" placeholder="Your full name" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" class="form-control" id="regEmail" placeholder="you@example.com" required>
        </div>
        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <label class="form-label">Password</label>
            <input type="password" class="form-control" id="regPassword" minlength="6" placeholder="Min 6 characters" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Confirm Password</label>
            <input type="password" class="form-control" id="regConfirm" minlength="6" required>
          </div>
        </div>
        <button type="submit" class="bxm-btn bxm-btn-primary w-100" id="registerBtn">Create Account</button>
      </form>
      <p class="text-center text-secondary small mt-4 mb-0">Already have an account? <a href="<?= bxm_url('auth/login.php' . ($redirect ? '?redirect=' . urlencode($redirect) : '')) ?>" class="text-white">Login</a></p>
    </div>
  </div>
</section>
<?php
$encodedRedirect = json_encode($redirect);
$inlineScript = <<<HTML
<script>
(function () {
  var redirect = {$encodedRedirect};
  function target() {
    if (redirect && redirect.indexOf('http') !== 0) return window.BXM.url(redirect.replace(/^\\//, ''));
    return window.BXM.url('index.php');
  }

  document.getElementById('registerForm').addEventListener('submit', function (e) {
    e.preventDefault();
    var name = document.getElementById('regName').value.trim();
    var email = document.getElementById('regEmail').value.trim();
    var pass = document.getElementById('regPassword').value;
    var confirm = document.getElementById('regConfirm').value;
    var btn = document.getElementById('registerBtn');

    if (!name || !email || !pass) { window.BXM.toast('Please fill in all fields', 'warning'); return; }
    if (pass.length < 6) { window.BXM.toast('Password must be at least 6 characters', 'warning'); return; }
    if (pass !== confirm) { window.BXM.toast('Passwords do not match', 'warning'); return; }

    btn.disabled = true;
    window.BXM.apiFetch('auth.php', {
      method: 'POST',
      body: { action: 'register', name: name, email: email, password: pass }
    }).then(function () {
      window.BXM.toast('Account created successfully', 'success');
      setTimeout(function () { window.location.href = target(); }, 600);
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
