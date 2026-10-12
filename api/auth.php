<?php
require_once __DIR__ . '/../includes/helpers.php';

bxm_require_post();
bxm_require_csrf();

$input = bxm_input_json();
$action = (string) ($input['action'] ?? '');

function bxm_auth_fail($message, $status = 400)
{
    bxm_json(['ok' => false, 'error' => $message], $status);
}

switch ($action) {
    case 'register':
        $name = trim((string) ($input['name'] ?? ''));
        $email = strtolower(trim((string) ($input['email'] ?? '')));
        $password = (string) ($input['password'] ?? '');

        if ($name === '' || $email === '' || $password === '') {
            bxm_auth_fail('Please fill in all fields.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            bxm_auth_fail('Please enter a valid email address.');
        }
        if (strlen($password) < 6) {
            bxm_auth_fail('Password must be at least 6 characters.');
        }
        if (bxm_user_find_by_email($email)) {
            bxm_auth_fail('This email is already registered. Please login.');
        }

        $uid = bxm_uid();
        $now = bxm_now_ms();
        $ok = bxm_exec(
            'INSERT INTO users (uid, name, email, password_hash, phone, photo_url, role, status, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?)',
            [$uid, $name, $email, password_hash($password, PASSWORD_DEFAULT), '', '', 'user', 'active', $now, $now]
        );
        if (!$ok) {
            bxm_log('register_failed', ['email' => $email]);
            bxm_auth_fail('Could not create your account. Please try again.', 500);
        }

        session_regenerate_id(true);
        bxm_set_session_user($uid);
        bxm_json(['ok' => true, 'user' => bxm_session_user()]);
        break;

    case 'register-admin':
        if (bxm_has_admin()) {
            bxm_auth_fail('An admin account already exists.', 403);
        }
        $name = trim((string) ($input['name'] ?? ''));
        $email = strtolower(trim((string) ($input['email'] ?? '')));
        $password = (string) ($input['password'] ?? '');

        if ($name === '' || $email === '' || $password === '') {
            bxm_auth_fail('Please fill in all fields.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            bxm_auth_fail('Please enter a valid email address.');
        }
        if (strlen($password) < 6) {
            bxm_auth_fail('Password must be at least 6 characters.');
        }
        if (bxm_user_find_by_email($email)) {
            bxm_auth_fail('This email is already registered. Please login.');
        }

        $uid = bxm_uid();
        $now = bxm_now_ms();
        $ok = bxm_exec(
            'INSERT INTO users (uid, name, email, password_hash, phone, photo_url, role, status, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?)',
            [$uid, $name, $email, password_hash($password, PASSWORD_DEFAULT), '', '', 'admin', 'active', $now, $now]
        );
        if (!$ok) {
            bxm_log('admin_register_failed', ['email' => $email]);
            bxm_auth_fail('Could not create the admin account. Please try again.', 500);
        }

        session_regenerate_id(true);
        bxm_set_session_user($uid);
        bxm_json(['ok' => true, 'user' => bxm_session_user()]);
        break;

    case 'login':        $email = strtolower(trim((string) ($input['email'] ?? '')));
        $password = (string) ($input['password'] ?? '');

        if ($email === '' || $password === '') {
            bxm_auth_fail('Please fill in all fields.');
        }
        $row = bxm_row('SELECT * FROM users WHERE email = ? LIMIT 1', [$email]);
        if (!$row || !password_verify($password, $row['password_hash'] ?? '')) {
            bxm_auth_fail('Invalid email or password.', 401);
        }
        if (($row['status'] ?? 'active') === 'banned') {
            bxm_auth_fail('Your account has been disabled.', 403);
        }

        session_regenerate_id(true);
        bxm_set_session_user($row['uid']);
        bxm_json(['ok' => true, 'user' => bxm_session_user()]);
        break;

    case 'logout':
        bxm_logout_session();
        bxm_json(['ok' => true]);
        break;

    case 'update-profile':
        $user = bxm_session_user();
        if (!$user) {
            bxm_auth_fail('Please login to continue.', 401);
        }
        $name = trim((string) ($input['name'] ?? ''));
        $phone = trim((string) ($input['phone'] ?? ''));
        $photoUrl = trim((string) ($input['photoUrl'] ?? ''));

        $sets = [];
        $params = [];
        if ($name !== '') {
            $sets[] = 'name = ?';
            $params[] = $name;
        }
        $sets[] = 'phone = ?';
        $params[] = $phone;
        if ($photoUrl !== '') {
            $sets[] = 'photo_url = ?';
            $params[] = $photoUrl;
        }
        $sets[] = 'updated_at = ?';
        $params[] = bxm_now_ms();
        $params[] = $user['uid'];

        bxm_exec('UPDATE users SET ' . implode(', ', $sets) . ' WHERE uid = ?', $params);
        bxm_json(['ok' => true, 'user' => bxm_user_find($user['uid'])]);
        break;

    case 'change-password':
        $user = bxm_session_user();
        if (!$user) {
            bxm_auth_fail('Please login to continue.', 401);
        }
        $current = (string) ($input['currentPassword'] ?? '');
        $new = (string) ($input['newPassword'] ?? '');
        if (strlen($new) < 6) {
            bxm_auth_fail('Password must be at least 6 characters.');
        }
        $row = bxm_row('SELECT password_hash FROM users WHERE uid = ? LIMIT 1', [$user['uid']]);
        if (!$row || !password_verify($current, $row['password_hash'] ?? '')) {
            bxm_auth_fail('Your current password is incorrect.', 403);
        }
        bxm_exec('UPDATE users SET password_hash = ?, updated_at = ? WHERE uid = ?', [password_hash($new, PASSWORD_DEFAULT), bxm_now_ms(), $user['uid']]);
        bxm_json(['ok' => true]);
        break;

    case 'forgot-password':
        $email = strtolower(trim((string) ($input['email'] ?? '')));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            bxm_auth_fail('Please enter a valid email address.');
        }
        $user = bxm_user_find_by_email($email);
        if ($user) {
            $token = bin2hex(random_bytes(24));
            bxm_exec('DELETE FROM password_resets WHERE email = ?', [$email]);
            bxm_exec(
                'INSERT INTO password_resets (id, email, token, expires_at, created_at) VALUES (?,?,?,?,?)',
                [bxm_uid(), $email, $token, bxm_now_ms() + 3600000, bxm_now_ms()]
            );
            $resetUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
                . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . bxm_url('auth/reset-password.php?token=' . $token);
            $subject = bxm_site('name') . ' - Password Reset';
            $body = "Hello " . $user['name'] . ",\n\nUse the link below to reset your password. It expires in 1 hour.\n\n" . $resetUrl . "\n\nIf you did not request this, ignore this email.";
            @mail($email, $subject, $body, 'From: no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        }
        // Do not reveal whether the email exists.
        bxm_json(['ok' => true, 'message' => 'If that email exists, a reset link has been sent.']);
        break;

    case 'reset-password':
        $token = trim((string) ($input['token'] ?? ''));
        $password = (string) ($input['password'] ?? '');
        if ($token === '') {
            bxm_auth_fail('Invalid reset link.');
        }
        if (strlen($password) < 6) {
            bxm_auth_fail('Password must be at least 6 characters.');
        }
        $row = bxm_row('SELECT * FROM password_resets WHERE token = ? LIMIT 1', [$token]);
        if (!$row) {
            bxm_auth_fail('This reset link is invalid or has already been used.');
        }
        if ((int) $row['expires_at'] < bxm_now_ms()) {
            bxm_exec('DELETE FROM password_resets WHERE id = ?', [$row['id']]);
            bxm_auth_fail('This reset link has expired. Please request a new one.');
        }
        $user = bxm_user_find_by_email($row['email']);
        if (!$user) {
            bxm_auth_fail('Account not found.');
        }
        bxm_exec('UPDATE users SET password_hash = ?, updated_at = ? WHERE uid = ?', [password_hash($password, PASSWORD_DEFAULT), bxm_now_ms(), $user['uid']]);
        bxm_exec('DELETE FROM password_resets WHERE email = ?', [$row['email']]);
        bxm_json(['ok' => true]);
        break;

    default:
        bxm_auth_fail('Unknown action.', 400);
}
