<?php
require_once __DIR__ . '/includes/auth-check.php';
$pageTitle = 'My Profile';
$pageDescription = 'Manage your account details.';
$noIndex = true;
require_once __DIR__ . '/includes/header.php';

$profileUid = $currentUser['uid'];
$profileOrders = bxm_orders_by_user($profileUid);
$approvedOrders = 0;
foreach ($profileOrders as $profileOrder) {
    if (in_array($profileOrder['status'], ['approved', 'completed'], true)) {
        $approvedOrders++;
    }
}
$profileData = [
    'name' => $currentUser['name'],
    'email' => $currentUser['email'],
    'phone' => $currentUser['phone'],
    'photoUrl' => $currentUser['photoUrl'],
    'role' => $currentUser['role'],
    'createdAt' => $currentUser['createdAt'],
    'orderCount' => count($profileOrders),
    'approvedCount' => $approvedOrders,
    'wishlistCount' => bxm_wishlist_count($profileUid),
];
?>
<div class="container" id="bxmConfigNotice"></div>

<section class="bxm-section-sm">
  <div class="container">
    <h1 class="bxm-section-title mb-4">My Profile</h1>
    <div class="row g-4">
      <div class="col-lg-4">
        <div class="bxm-card p-4 text-center">
          <img id="profilePhoto" src="<?= bxm_e($currentUser['photoUrl'] ?: bxm_url('assets/images/logo/logo.jpeg')) ?>" alt="Profile" class="bxm-brand-logo mx-auto mb-3" style="width:96px;height:96px">
          <h5 class="mb-1" id="profileName"><?= bxm_e($currentUser['name'] ?: 'User') ?></h5>
          <p class="text-secondary small mb-3" id="profileEmail"><?= bxm_e($currentUser['email']) ?></p>
          <span class="bxm-badge" id="profileRole"><?= $currentUser['role'] === 'admin' ? 'Admin' : 'User' ?></span>
          <hr class="bxm-divider my-4">
          <div class="row text-center g-3">
            <div class="col-4"><div class="fw-bold" id="statOrders"><?= (int) $profileData['orderCount'] ?></div><div class="small text-muted">Orders</div></div>
            <div class="col-4"><div class="fw-bold" id="statApproved"><?= (int) $profileData['approvedCount'] ?></div><div class="small text-muted">Approved</div></div>
            <div class="col-4"><div class="fw-bold" id="statWishlist"><?= (int) $profileData['wishlistCount'] ?></div><div class="small text-muted">Wishlist</div></div>
          </div>
        </div>
      </div>
      <div class="col-lg-8">
        <div class="bxm-card p-4 mb-4">
          <h6 class="mb-3">Account Information</h6>
          <form id="profileForm">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Full Name</label>
                <input type="text" class="form-control" id="inputName" placeholder="Your name" value="<?= bxm_e($currentUser['name']) ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Phone</label>
                <input type="text" class="form-control" id="inputPhone" placeholder="Optional" value="<?= bxm_e($currentUser['phone']) ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" id="inputEmail" value="<?= bxm_e($currentUser['email']) ?>" disabled>
              </div>
              <div class="col-md-6">
                <label class="form-label">Profile Picture</label>
                <input type="file" class="form-control" id="inputPhoto" accept="image/jpeg,image/png,image/webp">
              </div>
              <div class="col-12">
                <button type="submit" class="bxm-btn bxm-btn-primary"><i class="bi bi-check2"></i> Save Changes</button>
              </div>
            </div>
          </form>
        </div>

        <div class="bxm-card p-4">
          <h6 class="mb-3">Change Password</h6>
          <form id="passwordForm">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Current Password</label>
                <input type="password" class="form-control" id="currentPassword" placeholder="Current password">
              </div>
              <div class="col-md-4">
                <label class="form-label">New Password</label>
                <input type="password" class="form-control" id="newPassword" minlength="6" placeholder="Minimum 6 characters">
              </div>
              <div class="col-md-4">
                <label class="form-label">Confirm Password</label>
                <input type="password" class="form-control" id="confirmPassword" minlength="6">
              </div>
              <div class="col-12">
                <button type="submit" class="bxm-btn bxm-btn-outline"><i class="bi bi-shield-lock"></i> Update Password</button>
                <a href="#" class="bxm-btn bxm-btn-ghost text-danger ms-2" data-bxm-logout><i class="bi bi-box-arrow-right"></i> Logout</a>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
<?php
$encodedProfile = json_encode($profileData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$inlineScript = <<<HTML
<script>
(function () {
  var profile = {$encodedProfile} || {};

  function fill(p) {
    profile = p || {};
    document.getElementById('inputName').value = profile.name || '';
    document.getElementById('inputPhone').value = profile.phone || '';
    document.getElementById('inputEmail').value = profile.email || '';
    document.getElementById('profileName').textContent = profile.name || 'User';
    document.getElementById('profileEmail').textContent = profile.email || '';
    document.getElementById('profileRole').textContent = (profile.role === 'admin' ? 'Admin' : 'User');
    if (profile.photoUrl) document.getElementById('profilePhoto').src = profile.photoUrl;
    if (profile.createdAt) {
      var d = new Date(Number(profile.createdAt));
      if (!isNaN(d.getTime())) document.getElementById('profileEmail').textContent = (profile.email || '') + ' - joined ' + d.toLocaleDateString();
    }
  }

  fill(profile);

  document.getElementById('profileForm').addEventListener('submit', function (e) {
    e.preventDefault();
    var name = document.getElementById('inputName').value.trim();
    var phone = document.getElementById('inputPhone').value.trim();
    var file = document.getElementById('inputPhoto').files[0];
    window.BXM.loader(true);
    var photoPromise = file ? window.BXM.uploadImage(file, 'profile') : Promise.resolve(null);
    photoPromise.then(function (up) {
      if (up && !up.ok) throw new Error(up.error || 'Image upload failed');
      var body = { action: 'update-profile', name: name, phone: phone };
      if (up && up.url) body.photoUrl = up.url;
      return window.BXM.apiFetch('auth.php', { method: 'POST', json: body });
    }).then(function (res) {
      window.BXM.loader(false);
      if (!res || !res.ok) throw new Error((res && res.error) || 'Could not update profile');
      window.BXM.toast('Profile updated successfully', 'success');
      document.getElementById('inputPhoto').value = '';
      fill(res.user || profile);
    }).catch(function (err) {
      window.BXM.loader(false);
      window.BXM.toast(err.message || 'Could not update profile', 'danger');
    });
  });

  document.getElementById('passwordForm').addEventListener('submit', function (e) {
    e.preventDefault();
    var current = document.getElementById('currentPassword').value;
    var pass = document.getElementById('newPassword').value;
    var confirm = document.getElementById('confirmPassword').value;
    if (!current) { window.BXM.toast('Please enter your current password', 'warning'); return; }
    if (pass.length < 6) { window.BXM.toast('Password must be at least 6 characters', 'warning'); return; }
    if (pass !== confirm) { window.BXM.toast('Passwords do not match', 'warning'); return; }
    window.BXM.loader(true);
    window.BXM.apiFetch('auth.php', { method: 'POST', json: { action: 'change-password', currentPassword: current, newPassword: pass } })
      .then(function (res) {
        window.BXM.loader(false);
        if (!res || !res.ok) throw new Error((res && res.error) || 'Could not update password');
        window.BXM.toast('Password updated successfully', 'success');
        document.getElementById('passwordForm').reset();
      }).catch(function (err) {
        window.BXM.loader(false);
        window.BXM.toast(err.message || 'Could not update password', 'danger');
      });
  });
})();
</script>
HTML;
include __DIR__ . '/includes/footer.php';
?>
