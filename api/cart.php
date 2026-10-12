<?php
require_once __DIR__ . '/../includes/helpers.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$user = bxm_session_user();

if ($method === 'GET') {
    if (!$user) {
        bxm_json(['ok' => true, 'items' => new stdClass(), 'count' => 0]);
    }
    bxm_json(['ok' => true, 'items' => bxm_cart_items($user['uid']), 'count' => bxm_cart_count($user['uid'])]);
}

bxm_require_post();
bxm_require_csrf();

if (!$user) {
    bxm_json(['ok' => false, 'error' => 'Please login to continue.'], 401);
}

$input = bxm_input_json();
$action = (string) ($input['action'] ?? '');
$productId = trim((string) ($input['productId'] ?? ''));

if (in_array($action, ['add', 'set', 'remove', 'buy-now'], true) && $productId === '') {
    bxm_json(['ok' => false, 'error' => 'Product is required.'], 400);
}

$uid = $user['uid'];

if ($action === 'add' || $action === 'buy-now') {
    $product = bxm_product($productId);
    if (!$product || $product['status'] === 'inactive') {
        bxm_json(['ok' => false, 'error' => 'This product is not available.'], 400);
    }
}

switch ($action) {
    case 'add':
        $existing = bxm_row('SELECT quantity FROM carts WHERE uid = ? AND product_id = ? LIMIT 1', [$uid, $productId]);
        if ($existing) {
            bxm_exec('UPDATE carts SET quantity = quantity + 1, added_at = ? WHERE uid = ? AND product_id = ?', [bxm_now_ms(), $uid, $productId]);
        } else {
            bxm_exec('INSERT INTO carts (uid, product_id, quantity, added_at) VALUES (?,?,1,?)', [$uid, $productId, bxm_now_ms()]);
        }
        bxm_json(['ok' => true, 'count' => bxm_cart_count($uid)]);
        break;

    case 'set':
        $quantity = (int) ($input['quantity'] ?? 1);
        if ($quantity <= 0) {
            bxm_exec('DELETE FROM carts WHERE uid = ? AND product_id = ?', [$uid, $productId]);
        } else {
            bxm_exec('UPDATE carts SET quantity = ? WHERE uid = ? AND product_id = ?', [$quantity, $uid, $productId]);
        }
        bxm_json(['ok' => true, 'count' => bxm_cart_count($uid)]);
        break;

    case 'remove':
        bxm_exec('DELETE FROM carts WHERE uid = ? AND product_id = ?', [$uid, $productId]);
        bxm_json(['ok' => true, 'count' => bxm_cart_count($uid)]);
        break;

    case 'clear':
        bxm_exec('DELETE FROM carts WHERE uid = ?', [$uid]);
        bxm_json(['ok' => true, 'count' => 0]);
        break;

    case 'buy-now':
        bxm_exec('DELETE FROM carts WHERE uid = ?', [$uid]);
        bxm_exec('INSERT INTO carts (uid, product_id, quantity, added_at) VALUES (?,?,1,?)', [$uid, $productId, bxm_now_ms()]);
        bxm_json(['ok' => true, 'count' => 1]);
        break;

    default:
        bxm_json(['ok' => false, 'error' => 'Unknown action.'], 400);
}
