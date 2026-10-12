<?php
require_once __DIR__ . '/../includes/helpers.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$user = bxm_session_user();

if ($method === 'GET') {
    if (!$user) {
        bxm_json(['ok' => true, 'ids' => [], 'count' => 0]);
    }
    bxm_json(['ok' => true, 'ids' => bxm_wishlist_ids($user['uid']), 'count' => bxm_wishlist_count($user['uid'])]);
}

bxm_require_post();
bxm_require_csrf();

if (!$user) {
    bxm_json(['ok' => false, 'error' => 'Please login to continue.'], 401);
}

$input = bxm_input_json();
$action = (string) ($input['action'] ?? '');
$productId = trim((string) ($input['productId'] ?? ''));

if ($productId === '') {
    bxm_json(['ok' => false, 'error' => 'Product is required.'], 400);
}

$uid = $user['uid'];

switch ($action) {
    case 'toggle':
        $exists = bxm_row('SELECT 1 FROM wishlists WHERE uid = ? AND product_id = ? LIMIT 1', [$uid, $productId]);
        if ($exists) {
            bxm_exec('DELETE FROM wishlists WHERE uid = ? AND product_id = ?', [$uid, $productId]);
            bxm_json(['ok' => true, 'wished' => false, 'count' => bxm_wishlist_count($uid)]);
        }
        bxm_exec('INSERT INTO wishlists (uid, product_id, added_at) VALUES (?,?,?)', [$uid, $productId, bxm_now_ms()]);
        bxm_json(['ok' => true, 'wished' => true, 'count' => bxm_wishlist_count($uid)]);
        break;

    case 'remove':
        bxm_exec('DELETE FROM wishlists WHERE uid = ? AND product_id = ?', [$uid, $productId]);
        bxm_json(['ok' => true, 'count' => bxm_wishlist_count($uid)]);
        break;

    case 'move-to-cart':
        $product = bxm_product($productId);
        if (!$product || $product['status'] === 'inactive') {
            bxm_json(['ok' => false, 'error' => 'This product is not available.'], 400);
        }
        bxm_exec('DELETE FROM wishlists WHERE uid = ? AND product_id = ?', [$uid, $productId]);
        bxm_exec('DELETE FROM carts WHERE uid = ?', [$uid]);
        bxm_exec('INSERT INTO carts (uid, product_id, quantity, added_at) VALUES (?,?,1,?)', [$uid, $productId, bxm_now_ms()]);
        bxm_json(['ok' => true, 'count' => 1]);
        break;

    default:
        bxm_json(['ok' => false, 'error' => 'Unknown action.'], 400);
}
