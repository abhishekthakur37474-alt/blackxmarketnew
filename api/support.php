<?php
require_once __DIR__ . '/../includes/helpers.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$user = bxm_session_user();

if ($method === 'GET') {
    if (!$user) {
        bxm_json(['ok' => true, 'tickets' => []]);
    }
    bxm_json(['ok' => true, 'tickets' => bxm_tickets_by_user($user['uid'])]);
}

bxm_require_post();
bxm_require_csrf();

if (!$user) {
    bxm_json(['ok' => false, 'error' => 'Please login to continue.'], 401);
}

$input = bxm_input_json();
$subject = trim((string) ($input['subject'] ?? ''));
$message = trim((string) ($input['message'] ?? ''));
$orderId = trim((string) ($input['orderId'] ?? ''));

if ($subject === '' || $message === '') {
    bxm_json(['ok' => false, 'error' => 'Please fill in subject and message.'], 400);
}

$now = bxm_now_ms();
bxm_exec(
    'INSERT INTO support_tickets (id, uid, user_name, email, order_id, subject, message, reply, status, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
    [bxm_uid(), $user['uid'], $user['name'] ?: 'Customer', $user['email'], $orderId, $subject, $message, '', 'open', $now, $now]
);

bxm_json(['ok' => true, 'message' => 'Ticket submitted successfully.']);
