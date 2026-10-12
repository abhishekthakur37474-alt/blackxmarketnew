<?php
require_once __DIR__ . '/../includes/helpers.php';

bxm_require_post();
bxm_require_csrf();

$user = bxm_session_user();
if (!$user) {
    bxm_json(['ok' => false, 'error' => 'Please login to apply coupons.'], 401);
}

$input = bxm_input_json();
$result = bxm_validate_coupon($input['code'] ?? '', $input['cartAmount'] ?? 0);

if (empty($result['ok'])) {
    bxm_json(['ok' => false, 'error' => $result['error'] ?? 'Invalid coupon.'], 400);
}

bxm_json([
    'ok' => true,
    'couponId' => $result['couponId'],
    'code' => $result['code'],
    'discount' => $result['discount'],
    'message' => 'Coupon applied successfully.',
]);
