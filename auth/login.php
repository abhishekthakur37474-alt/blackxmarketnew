<?php
require_once __DIR__ . '/../includes/helpers.php';

if (bxm_is_logged_in()) {
    $target = $_GET['redirect'] ?? 'index.php';
    if (stripos($target, 'http') === 0) {
        $target = 'index.php';
    }
    bxm_redirect(bxm_url(ltrim($target, '/')));
}

$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '';
if (stripos($redirect, 'http') === 0) {
    $redirect = '';
}
$pageTitle = 'Login';
$pageDescription = 'Login to your BLACK X MARKET account.';
$noIndex = true;
require_once __DIR__ . '/../includes/header.php';
?>
<section class="bxm-auth-wrap">
  <div class="container">
    <div class="bxm-auth-card">
      <div class="text-center mb-4">
        <img src="<?= bxm_url('assets/images/logo/logo.jpeg') ?>" alt="BLACK X MARKET" width="56" height="56" class="bxm-brand-logo mb-3">
        <h1 class="h4 mb-1">Welcome Back</h1>
        <p class="text-secondary small mb-0">Login to continue to BLACK X MARKET.</p>
      </div>
      <form id="loginForm" novalidate>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" class="form-control" id="loginEmail" placeholder="you@example.com" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" class="form-control" id="loginPassword" placeholder="Your password" required>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-4">
          <a href="<?= bxm_url('auth/forgot-password.php') ?>" class="small bxm-link-muted">Forgot Password?</a>
        </div>
        <button type="submit" class="bxm-btn bxm-btn-primary w-100" id="loginBtn">Login</button>
      </form>
      <p class="text-center text-secondary small mt-4 mb-0">Don't have an account? <a href="<?= bxm_url('auth/register.php' . ($redirect ? '?redirect=' . urlencode($redirect) : '')) ?>" class="text-white">Create Account</a></p>
    </div>
  </div>
</section>
<?php
$encodedRedirect = json_encode($redirect);
$inlineScript = <<<HTML
<script>
(function () {
  var redirect = {$encodedRedirect};
  document.getElementById('loginForm').addEventListener('submit', function (e) {
    e.preventDefault();
    var email = document.getElementById('loginEmail').value.trim();
    var password = document.getElementById('loginPassword').value;
    var btn = document.getElementById('loginBtn');
    if (!email || !password) { window.BXM.toast('Please fill in all fields', 'warning'); return; }
    btn.disabled = true;
    function go() {
      var target = window.BXM.url('index.php');
      if (redirect && redirect.indexOf('http') !== 0) target = window.BXM.url(redirect.replace(/^\\//, ''));
      window.location.href = target;
    }
    window.BXM.apiFetch('auth.php', {
      method: 'POST',
      body: { action: 'login', email: email, password: password }
    }).then(function (data) {
      window.BXM.toast('Login successful', 'success');
      setTimeout(go, 400);
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
