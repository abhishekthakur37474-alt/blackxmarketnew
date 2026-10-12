<?php
require_once __DIR__ . '/../includes/helpers.php';

$token = trim((string) ($_GET['token'] ?? ''));
$email = trim((string) ($_GET['email'] ?? ''));
if ($token === '' || $email === '') {
    bxm_redirect(bxm_url('auth/login.php'));
}

$pageTitle = 'Reset Password';
$pageDescription = 'Set a new password for your BLACK X MARKET account.';
$noIndex = true;
require_once __DIR__ . '/../includes/header.php';
?>
<section class="bxm-auth-wrap">
  <div class="container">
    <div class="bxm-auth-card">
      <div class="text-center mb-4">
        <img src="<?= bxm_url('assets/images/logo/logo.jpeg') ?>" alt="BLACK X MARKET" width="56" height="56" class="bxm-brand-logo mb-3">
        <h1 class="h4 mb-1">Set a New Password</h1>
        <p class="text-secondary small mb-0">Choose a strong password for your account.</p>
      </div>
      <form id="resetForm" novalidate>
        <div class="mb-3">
          <label class="form-label">New Password</label>
          <input type="password" class="form-control" id="resetPassword" minlength="6" placeholder="Min 6 characters" required>
        </div>
        <div class="mb-4">
          <label class="form-label">Confirm New Password</label>
          <input type="password" class="form-control" id="resetConfirm" minlength="6" required>
        </div>
        <button type="submit" class="bxm-btn bxm-btn-primary w-100" id="resetBtn">Reset Password</button>
      </form>
      <p class="text-center text-secondary small mt-4 mb-0"><a href="<?= bxm_url('auth/login.php') ?>" class="text-white">Back to Login</a></p>
    </div>
  </div>
</section>
<?php
$encodedToken = json_encode($token);
$encodedEmail = json_encode($email);
$inlineScript = <<<HTML
<script>
(function () {
  var token = {$encodedToken};
  var email = {$encodedEmail};
  document.getElementById('resetForm').addEventListener('submit', function (e) {
    e.preventDefault();
    var pass = document.getElementById('resetPassword').value;
    var confirm = document.getElementById('resetConfirm').value;
    var btn = document.getElementById('resetBtn');
    if (pass.length < 6) { window.BXM.toast('Password must be at least 6 characters', 'warning'); return; }
    if (pass !== confirm) { window.BXM.toast('Passwords do not match', 'warning'); return; }
    btn.disabled = true;
    window.BXM.apiFetch('auth.php', {
      method: 'POST',
      body: { action: 'reset-password', email: email, token: token, password: pass }
    }).then(function () {
      window.BXM.toast('Password reset successfully. Please login.', 'success');
      setTimeout(function () { window.location.href = window.BXM.url('auth/login.php'); }, 800);
    }).catch(function (err) {
      btn.disabled = false;
      window.BXM.toast(err.message || 'Could not reset password', 'danger');
    });
  });
})();
</script>
HTML;
include __DIR__ . '/../includes/footer.php';
?>
