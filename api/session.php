<?php
require_once __DIR__ . '/../includes/helpers.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'DELETE') {
    bxm_require_csrf();
    bxm_logout_session();
    bxm_json(['ok' => true]);
}

if ($method === 'GET') {
    bxm_json(['ok' => true, 'user' => bxm_session_user(), 'csrf' => bxm_csrf_token()]);
}

bxm_json(['ok' => false, 'error' => 'Method not allowed'], 405);
