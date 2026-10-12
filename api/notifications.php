<?php
require_once __DIR__ . '/../includes/helpers.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$user = bxm_session_user();

if ($method === 'GET') {
    if (!$user) {
        bxm_json(['ok' => true, 'notifications' => [], 'unread' => 0]);
    }
    bxm_json([
        'ok' => true,
        'notifications' => bxm_notifications($user['uid'], 30),
        'unread' => bxm_unread_count($user['uid']),
    ]);
}

bxm_require_post();
bxm_require_csrf();

if (!$user) {
    bxm_json(['ok' => false, 'error' => 'Please login to continue.'], 401);
}

$input = bxm_input_json();
$action = (string) ($input['action'] ?? '');

switch ($action) {
    case 'read':
        $id = trim((string) ($input['id'] ?? ''));
        if ($id !== '') {
            bxm_exec('UPDATE notifications SET is_read = 1 WHERE id = ? AND uid = ?', [$id, $user['uid']]);
        }
        bxm_json(['ok' => true, 'unread' => bxm_unread_count($user['uid'])]);
        break;

    case 'read-all':
        bxm_exec('UPDATE notifications SET is_read = 1 WHERE uid = ?', [$user['uid']]);
        bxm_json(['ok' => true, 'unread' => 0]);
        break;

    default:
        bxm_json(['ok' => false, 'error' => 'Unknown action.'], 400);
}
