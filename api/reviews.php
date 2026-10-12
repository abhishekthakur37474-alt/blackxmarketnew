<?php
require_once __DIR__ . '/../includes/helpers.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $productId = isset($_GET['productId']) ? preg_replace('/[^A-Za-z0-9_-]/', '', $_GET['productId']) : null;
    $limit = isset($_GET['limit']) ? max(1, (int) $_GET['limit']) : null;
    bxm_json(['ok' => true, 'reviews' => bxm_reviews('approved', $productId ?: null, $limit)]);
}

bxm_require_post();
bxm_require_csrf();

$user = bxm_session_user();
if (!$user) {
    bxm_json(['ok' => false, 'error' => 'Please login to continue.'], 401);
}

$input = bxm_input_json();
$productId = trim((string) ($input['productId'] ?? ''));
$rating = (int) ($input['rating'] ?? 0);
$comment = trim((string) ($input['comment'] ?? ''));

if ($productId === '') {
    bxm_json(['ok' => false, 'error' => 'Please select a product.'], 400);
}
if ($rating < 1 || $rating > 5) {
    bxm_json(['ok' => false, 'error' => 'Please select a rating.'], 400);
}
if ($comment === '') {
    bxm_json(['ok' => false, 'error' => 'Please write a review.'], 400);
}

$uid = $user['uid'];
$purchased = bxm_row(
    "SELECT order_id FROM orders WHERE uid = ? AND status IN ('approved','completed') AND (product_id = ? OR items LIKE ?) LIMIT 1",
    [$uid, $productId, '%"productId":"' . $productId . '"%']
);
if (!$purchased) {
    bxm_json(['ok' => false, 'error' => 'You can review only products you have purchased and received.'], 403);
}

$product = bxm_product($productId);
$productTitle = $product ? $product['title'] : 'Product';

bxm_exec(
    'INSERT INTO reviews (id, uid, user_name, product_id, product_title, rating, comment, status, created_at) VALUES (?,?,?,?,?,?,?,?,?)',
    [bxm_uid(), $uid, $user['name'] ?: $user['email'], $productId, $productTitle, $rating, $comment, 'pending', bxm_now_ms()]
);

bxm_json(['ok' => true, 'message' => 'Review submitted for moderation.']);
