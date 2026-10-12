<?php
require_once __DIR__ . '/../includes/helpers.php';

bxm_require_post();
bxm_require_csrf();

$user = bxm_session_user();
if (!$user) {
    bxm_json(['ok' => false, 'error' => 'Please login to place an order.'], 401);
}
if ($user['status'] === 'banned') {
    bxm_json(['ok' => false, 'error' => 'Your account has been disabled.'], 403);
}

$uid = $user['uid'];
$input = bxm_input_json();
$transactionId = trim($input['transactionId'] ?? '');
$paymentScreenshotUrl = trim($input['paymentScreenshotUrl'] ?? '');
$couponCode = trim($input['couponCode'] ?? '');

if ($transactionId === '') {
    bxm_json(['ok' => false, 'error' => 'Transaction ID is required.'], 400);
}
if ($paymentScreenshotUrl === '') {
    bxm_json(['ok' => false, 'error' => 'Payment screenshot is required.'], 400);
}

$cart = bxm_cart_items($uid);
if (empty($cart)) {
    bxm_json(['ok' => false, 'error' => 'No product selected.'], 400);
}

$items = [];
$subtotal = 0;

foreach ($cart as $productId => $entry) {
    $product = bxm_product($productId);
    if (!$product) {
        continue;
    }
    if ($product['status'] !== 'active') {
        bxm_json(['ok' => false, 'error' => 'This product is no longer available.'], 400);
    }
    $quantity = max(1, (int) ($entry['quantity'] ?? 1));
    $price = (float) ($product['discountedPrice'] ?: $product['originalPrice']);
    $lineTotal = round($price * $quantity, 2);
    $subtotal += $lineTotal;
    $items[] = [
        'productId' => $productId,
        'title' => $product['title'],
        'category' => $product['category'],
        'thumbnailUrl' => $product['thumbnailUrl'],
        'price' => $price,
        'quantity' => $quantity,
        'lineTotal' => $lineTotal,
    ];
}

if (empty($items)) {
    bxm_json(['ok' => false, 'error' => 'This product is no longer available.'], 400);
}

$subtotal = round($subtotal, 2);
$couponDiscount = 0;
$couponId = '';
$appliedCode = '';

if ($couponCode !== '') {
    $coupon = bxm_validate_coupon($couponCode, $subtotal);
    if (empty($coupon['ok'])) {
        bxm_json(['ok' => false, 'error' => $coupon['error'] ?? 'Invalid coupon.'], 400);
    }
    $couponDiscount = $coupon['discount'];
    $couponId = $coupon['couponId'];
    $appliedCode = $coupon['code'];
}

$amount = round(max(0, $subtotal - $couponDiscount), 2);

$paymentSettings = bxm_get_setting('payment', []);
$orderId = bxm_generate_order_id();
$now = bxm_now_ms();

$productTitle = count($items) > 1
    ? ($items[0]['title'] . ' +' . (count($items) - 1) . ' more')
    : $items[0]['title'];

$created = bxm_exec(
    'INSERT INTO orders (order_id, uid, user_email, items, product_id, product_title, product_thumbnail, subtotal, coupon_code, coupon_id, coupon_discount, amount, transaction_id, payment_screenshot_url, payment_snapshot, delivery_details, status, rejection_reason, created_at, updated_at)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
    [
        $orderId,
        $uid,
        $user['email'],
        bxm_json_encode_col($items),
        $items[0]['productId'],
        $productTitle,
        $items[0]['thumbnailUrl'],
        $subtotal,
        $appliedCode,
        $couponId,
        $couponDiscount,
        $amount,
        $transactionId,
        $paymentScreenshotUrl,
        bxm_json_encode_col([
            'upiId' => $paymentSettings['upiId'] ?? '',
            'qrCodeUrl' => $paymentSettings['qrCodeUrl'] ?? '',
            'paymentDisplayName' => $paymentSettings['paymentDisplayName'] ?? '',
        ]),
        bxm_json_encode_col([]),
        'payment_submitted',
        '',
        $now,
        $now,
    ]
);

if ($created === false) {
    bxm_log('order_create_failed', ['orderId' => $orderId, 'uid' => $uid]);
    bxm_json(['ok' => false, 'error' => 'Could not create your order. Please try again.'], 502);
}

if ($couponId !== '') {
    bxm_exec('UPDATE coupons SET used_count = used_count + 1 WHERE id = ?', [$couponId]);
}

bxm_exec('DELETE FROM carts WHERE uid = ?', [$uid]);

bxm_notify($uid, [
    'title' => 'Payment submitted',
    'message' => 'We received your payment for ' . $productTitle . '. Your order is now pending verification.',
    'type' => 'info',
    'link' => bxm_url('order-details.php?id=' . $orderId),
]);

bxm_json([
    'ok' => true,
    'orderId' => $orderId,
    'amount' => $amount,
    'subtotal' => $subtotal,
    'couponDiscount' => $couponDiscount,
]);
