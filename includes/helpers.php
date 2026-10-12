<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function bxm_config($key = null)
{
    $cfg = $GLOBALS['BXM'];
    if ($key === null) {
        return $cfg;
    }
    return isset($cfg[$key]) ? $cfg[$key] : null;
}

function bxm_site($key = null)
{
    $site = bxm_config('site');
    return $key === null ? $site : (isset($site[$key]) ? $site[$key] : null);
}

function bxm_e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function bxm_url($path = '')
{
    return BXM_BASE . '/' . ltrim($path, '/');
}

function bxm_money($amount)
{
    return bxm_site('currency') . number_format((float) $amount, 2);
}

function bxm_json($data, $status = 200)
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function bxm_input_json()
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function bxm_redirect($url)
{
    header('Location: ' . $url);
    exit;
}

function bxm_discount_percent($original, $discounted)
{
    $original = (float) $original;
    $discounted = (float) $discounted;
    if ($original <= 0) {
        return 0;
    }
    $percent = (($original - $discounted) / $original) * 100;
    return round(max(0, $percent), 2);
}

function bxm_product_status_label($status)
{
    $map = [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'out_of_stock' => 'Out of Stock',
    ];
    return isset($map[$status]) ? $map[$status] : ucfirst((string) $status);
}

function bxm_order_status_label($status)
{
    $map = [
        'pending' => 'Pending',
        'payment_submitted' => 'Payment Submitted',
        'under_review' => 'Under Review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];
    return isset($map[$status]) ? $map[$status] : ucfirst((string) $status);
}

/* ---------------------------------------------------------------------------
 * CSRF
 * ------------------------------------------------------------------------- */

function bxm_csrf_token()
{
    if (empty($_SESSION['bxm_csrf'])) {
        $_SESSION['bxm_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['bxm_csrf'];
}

function bxm_csrf_header()
{
    if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        return (string) $_SERVER['HTTP_X_CSRF_TOKEN'];
    }
    return '';
}

function bxm_csrf_verify()
{
    $sent = bxm_csrf_header();
    if ($sent === '') {
        return false;
    }
    return hash_equals(bxm_csrf_token(), $sent);
}

function bxm_require_csrf()
{
    if (!bxm_csrf_verify()) {
        bxm_json(['ok' => false, 'error' => 'Invalid request token. Please refresh the page and try again.'], 403);
    }
}

function bxm_require_post()
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        bxm_json(['ok' => false, 'error' => 'Method not allowed'], 405);
    }
}

/* ---------------------------------------------------------------------------
 * Logging
 * ------------------------------------------------------------------------- */

function bxm_log($message, $context = [])
{
    $line = '[BXM] ' . $message;
    if (!empty($context)) {
        $line .= ' ' . json_encode($context);
    }
    error_log($line);
}

/* ---------------------------------------------------------------------------
 * Auth / session
 * ------------------------------------------------------------------------- */

function bxm_user_row_to_array($row)
{
    if (!$row) {
        return null;
    }
    return [
        'uid' => $row['uid'],
        'name' => $row['name'] ?? '',
        'email' => $row['email'] ?? '',
        'phone' => $row['phone'] ?? '',
        'photoUrl' => $row['photo_url'] ?? '',
        'role' => $row['role'] ?? 'user',
        'status' => $row['status'] ?? 'active',
        'createdAt' => (int) ($row['created_at'] ?? 0),
        'updatedAt' => (int) ($row['updated_at'] ?? 0),
    ];
}

function bxm_user_find($uid)
{
    if (!$uid) {
        return null;
    }
    return bxm_user_row_to_array(bxm_row('SELECT * FROM users WHERE uid = ? LIMIT 1', [$uid]));
}

function bxm_user_find_by_email($email)
{
    $email = strtolower(trim((string) $email));
    if ($email === '') {
        return null;
    }
    return bxm_user_row_to_array(bxm_row('SELECT * FROM users WHERE email = ? LIMIT 1', [$email]));
}

function bxm_session_user()
{
    static $cached = false;
    if (is_array($cached)) {
        return $cached;
    }
    if (empty($_SESSION['bxm_uid'])) {
        $cached = null;
        return null;
    }
    $user = bxm_user_find($_SESSION['bxm_uid']);
    if (!$user || $user['status'] === 'banned') {
        $_SESSION = [];
        $cached = null;
        return null;
    }
    $cached = $user;
    return $cached;
}

function bxm_is_logged_in()
{
    return bxm_session_user() !== null;
}

function bxm_is_admin()
{
    $user = bxm_session_user();
    return $user !== null && $user['role'] === 'admin';
}

function bxm_set_session_user($uid)
{
    $_SESSION['bxm_uid'] = $uid;
    $user = bxm_user_find($uid);
    if ($user) {
        $_SESSION['bxm_email'] = $user['email'];
        $_SESSION['bxm_name'] = $user['name'];
        $_SESSION['bxm_photo'] = $user['photoUrl'];
        $_SESSION['bxm_role'] = $user['role'];
    }
}

function bxm_logout_session()
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function bxm_has_admin()
{
    $count = bxm_val("SELECT COUNT(*) FROM users WHERE role = 'admin' AND status <> 'banned'");
    return ((int) $count) > 0;
}

/* ---------------------------------------------------------------------------
 * Products
 * ------------------------------------------------------------------------- */

function bxm_product_row_to_array($row)
{
    if (!$row) {
        return null;
    }
    return [
        'id' => $row['id'],
        'title' => $row['title'] ?? '',
        'category' => $row['category'] ?? '',
        'description' => $row['description'] ?? '',
        'originalPrice' => (float) ($row['original_price'] ?? 0),
        'discountedPrice' => (float) ($row['discounted_price'] ?? 0),
        'discountPercent' => (float) ($row['discount_percent'] ?? 0),
        'status' => $row['status'] ?? 'active',
        'thumbnailUrl' => $row['thumbnail_url'] ?? '',
        'additionalDetails' => bxm_json_col($row['additional_details'] ?? null),
        'createdAt' => (int) ($row['created_at'] ?? 0),
        'updatedAt' => (int) ($row['updated_at'] ?? 0),
    ];
}

function bxm_products($status = null)
{
    if ($status !== null) {
        $rows = bxm_all('SELECT * FROM products WHERE status = ? ORDER BY created_at DESC', [$status]);
    } else {
        $rows = bxm_all('SELECT * FROM products ORDER BY created_at DESC');
    }
    return array_map('bxm_product_row_to_array', $rows);
}

function bxm_product($id)
{
    if (!$id) {
        return null;
    }
    return bxm_product_row_to_array(bxm_row('SELECT * FROM products WHERE id = ? LIMIT 1', [$id]));
}

/* ---------------------------------------------------------------------------
 * Carousels
 * ------------------------------------------------------------------------- */

function bxm_carousel_row_to_array($row)
{
    return [
        'id' => $row['id'],
        'title' => $row['title'] ?? '',
        'description' => $row['description'] ?? '',
        'buttonText' => $row['button_text'] ?? '',
        'link' => $row['link'] ?? '',
        'imageUrl' => $row['image_url'] ?? '',
        'order' => (int) ($row['sort_order'] ?? 0),
        'status' => $row['status'] ?? 'active',
        'createdAt' => (int) ($row['created_at'] ?? 0),
        'updatedAt' => (int) ($row['updated_at'] ?? 0),
    ];
}

function bxm_carousels($status = null)
{
    if ($status !== null) {
        $rows = bxm_all('SELECT * FROM carousels WHERE status = ? ORDER BY sort_order ASC', [$status]);
    } else {
        $rows = bxm_all('SELECT * FROM carousels ORDER BY sort_order ASC');
    }
    return array_map('bxm_carousel_row_to_array', $rows);
}

/* ---------------------------------------------------------------------------
 * Coupons
 * ------------------------------------------------------------------------- */

function bxm_coupon_row_to_array($row)
{
    return [
        'id' => $row['id'],
        'code' => $row['code'] ?? '',
        'type' => $row['type'] ?? 'percentage',
        'value' => (float) ($row['value'] ?? 0),
        'minAmount' => (float) ($row['min_amount'] ?? 0),
        'maxDiscount' => (float) ($row['max_discount'] ?? 0),
        'expiryDate' => $row['expiry_date'] ?? '',
        'usageLimit' => (int) ($row['usage_limit'] ?? 0),
        'usedCount' => (int) ($row['used_count'] ?? 0),
        'status' => $row['status'] ?? 'active',
        'createdAt' => (int) ($row['created_at'] ?? 0),
        'updatedAt' => (int) ($row['updated_at'] ?? 0),
    ];
}

function bxm_coupons()
{
    $rows = bxm_all('SELECT * FROM coupons ORDER BY created_at DESC');
    return array_map('bxm_coupon_row_to_array', $rows);
}

function bxm_validate_coupon($code, $cartAmount)
{
    $code = strtoupper(trim((string) $code));
    $cartAmount = (float) $cartAmount;

    if ($code === '') {
        return ['ok' => false, 'error' => 'Please enter a coupon code.'];
    }

    $row = bxm_row('SELECT * FROM coupons WHERE code = ? LIMIT 1', [$code]);
    if (!$row) {
        return ['ok' => false, 'error' => 'Invalid coupon code.'];
    }
    if (($row['status'] ?? 'active') !== 'active') {
        return ['ok' => false, 'error' => 'This coupon is no longer active.'];
    }

    $expiry = trim((string) ($row['expiry_date'] ?? ''));
    if ($expiry !== '') {
        $expiryTs = strtotime($expiry);
        if ($expiryTs && time() > $expiryTs + 86399) {
            return ['ok' => false, 'error' => 'This coupon has expired.'];
        }
    }

    $minAmount = (float) ($row['min_amount'] ?? 0);
    if ($cartAmount < $minAmount) {
        return ['ok' => false, 'error' => 'Minimum cart amount for this coupon is ' . bxm_money($minAmount) . '.'];
    }

    $usageLimit = (int) ($row['usage_limit'] ?? 0);
    $usedCount = (int) ($row['used_count'] ?? 0);
    if ($usageLimit > 0 && $usedCount >= $usageLimit) {
        return ['ok' => false, 'error' => 'This coupon usage limit has been reached.'];
    }

    $type = $row['type'] ?? 'percentage';
    $value = (float) ($row['value'] ?? 0);
    $discount = $type === 'flat' ? $value : $cartAmount * ($value / 100);

    $maxDiscount = (float) ($row['max_discount'] ?? 0);
    if ($maxDiscount > 0 && $discount > $maxDiscount) {
        $discount = $maxDiscount;
    }
    $discount = min($discount, $cartAmount);

    return [
        'ok' => true,
        'couponId' => $row['id'],
        'code' => strtoupper($row['code']),
        'discount' => round($discount, 2),
    ];
}

/* ---------------------------------------------------------------------------
 * Orders
 * ------------------------------------------------------------------------- */

function bxm_order_row_to_array($row)
{
    if (!$row) {
        return null;
    }
    return [
        'orderId' => $row['order_id'],
        'uid' => $row['uid'] ?? '',
        'userEmail' => $row['user_email'] ?? '',
        'items' => bxm_json_col($row['items'] ?? null),
        'productId' => $row['product_id'] ?? '',
        'productTitle' => $row['product_title'] ?? '',
        'productThumbnail' => $row['product_thumbnail'] ?? '',
        'subtotal' => (float) ($row['subtotal'] ?? 0),
        'couponCode' => $row['coupon_code'] ?? '',
        'couponId' => $row['coupon_id'] ?? '',
        'couponDiscount' => (float) ($row['coupon_discount'] ?? 0),
        'amount' => (float) ($row['amount'] ?? 0),
        'transactionId' => $row['transaction_id'] ?? '',
        'paymentScreenshotUrl' => $row['payment_screenshot_url'] ?? '',
        'paymentSnapshot' => bxm_json_col($row['payment_snapshot'] ?? null),
        'deliveryDetails' => bxm_json_col($row['delivery_details'] ?? null),
        'status' => $row['status'] ?? 'pending',
        'rejectionReason' => $row['rejection_reason'] ?? '',
        'createdAt' => (int) ($row['created_at'] ?? 0),
        'updatedAt' => (int) ($row['updated_at'] ?? 0),
    ];
}

function bxm_order($orderId)
{
    if (!$orderId) {
        return null;
    }
    return bxm_order_row_to_array(bxm_row('SELECT * FROM orders WHERE order_id = ? LIMIT 1', [$orderId]));
}

function bxm_orders_by_user($uid)
{
    $rows = bxm_all('SELECT * FROM orders WHERE uid = ? ORDER BY created_at DESC', [$uid]);
    return array_map('bxm_order_row_to_array', $rows);
}

function bxm_orders()
{
    $rows = bxm_all('SELECT * FROM orders ORDER BY created_at DESC');
    return array_map('bxm_order_row_to_array', $rows);
}

function bxm_generate_order_id()
{
    for ($i = 0; $i < 6; $i++) {
        $candidate = 'BXM-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $exists = bxm_val('SELECT COUNT(*) FROM orders WHERE order_id = ?', [$candidate]);
        if (!$exists) {
            return $candidate;
        }
    }
    return 'BXM-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(5)));
}

/* ---------------------------------------------------------------------------
 * Reviews
 * ------------------------------------------------------------------------- */

function bxm_review_row_to_array($row)
{
    return [
        'id' => $row['id'],
        'uid' => $row['uid'] ?? '',
        'userName' => $row['user_name'] ?? '',
        'productId' => $row['product_id'] ?? '',
        'productTitle' => $row['product_title'] ?? '',
        'rating' => (int) ($row['rating'] ?? 0),
        'comment' => $row['comment'] ?? '',
        'status' => $row['status'] ?? 'pending',
        'createdAt' => (int) ($row['created_at'] ?? 0),
    ];
}

function bxm_reviews($status = null, $productId = null, $limit = null)
{
    $sql = 'SELECT * FROM reviews WHERE 1=1';
    $params = [];
    if ($status !== null) {
        $sql .= ' AND status = ?';
        $params[] = $status;
    }
    if ($productId !== null) {
        $sql .= ' AND product_id = ?';
        $params[] = $productId;
    }
    $sql .= ' ORDER BY created_at DESC';
    if ($limit !== null) {
        $sql .= ' LIMIT ' . (int) $limit;
    }
    $rows = bxm_all($sql, $params);
    return array_map('bxm_review_row_to_array', $rows);
}

/* ---------------------------------------------------------------------------
 * Support tickets
 * ------------------------------------------------------------------------- */

function bxm_ticket_row_to_array($row)
{
    return [
        'id' => $row['id'],
        'uid' => $row['uid'] ?? '',
        'userName' => $row['user_name'] ?? '',
        'email' => $row['email'] ?? '',
        'orderId' => $row['order_id'] ?? '',
        'subject' => $row['subject'] ?? '',
        'message' => $row['message'] ?? '',
        'reply' => $row['reply'] ?? '',
        'status' => $row['status'] ?? 'open',
        'createdAt' => (int) ($row['created_at'] ?? 0),
        'updatedAt' => (int) ($row['updated_at'] ?? 0),
    ];
}

function bxm_tickets_by_user($uid)
{
    $rows = bxm_all('SELECT * FROM support_tickets WHERE uid = ? ORDER BY created_at DESC', [$uid]);
    return array_map('bxm_ticket_row_to_array', $rows);
}

function bxm_tickets()
{
    $rows = bxm_all('SELECT * FROM support_tickets ORDER BY created_at DESC');
    return array_map('bxm_ticket_row_to_array', $rows);
}

/* ---------------------------------------------------------------------------
 * Notifications
 * ------------------------------------------------------------------------- */

function bxm_notification_row_to_array($row)
{
    return [
        'id' => $row['id'],
        'uid' => $row['uid'] ?? '',
        'title' => $row['title'] ?? '',
        'message' => $row['message'] ?? '',
        'type' => $row['type'] ?? 'info',
        'link' => $row['link'] ?? '',
        'read' => ((int) ($row['is_read'] ?? 0)) === 1,
        'createdAt' => (int) ($row['created_at'] ?? 0),
    ];
}

function bxm_notifications($uid, $limit = 50)
{
    if (!$uid) {
        return [];
    }
    $rows = bxm_all('SELECT * FROM notifications WHERE uid = ? ORDER BY created_at DESC LIMIT ' . (int) $limit, [$uid]);
    return array_map('bxm_notification_row_to_array', $rows);
}

function bxm_notify($uid, $data)
{
    if (!$uid) {
        return;
    }
    bxm_exec(
        'INSERT INTO notifications (id, uid, title, message, type, link, is_read, created_at) VALUES (?,?,?,?,?,?,0,?)',
        [
            bxm_uid(),
            $uid,
            $data['title'] ?? 'Notification',
            $data['message'] ?? '',
            $data['type'] ?? 'info',
            $data['link'] ?? '',
            bxm_now_ms(),
        ]
    );
}

function bxm_unread_count($uid)
{
    if (!$uid) {
        return 0;
    }
    return (int) bxm_val('SELECT COUNT(*) FROM notifications WHERE uid = ? AND is_read = 0', [$uid]);
}

/* ---------------------------------------------------------------------------
 * Cart / Wishlist
 * ------------------------------------------------------------------------- */

function bxm_cart_items($uid)
{
    if (!$uid) {
        return [];
    }
    $rows = bxm_all('SELECT * FROM carts WHERE uid = ? ORDER BY added_at ASC', [$uid]);
    $out = [];
    foreach ($rows as $row) {
        $out[$row['product_id']] = [
            'productId' => $row['product_id'],
            'quantity' => (int) $row['quantity'],
            'addedAt' => (int) $row['added_at'],
        ];
    }
    return $out;
}

function bxm_cart_count($uid)
{
    if (!$uid) {
        return 0;
    }
    return (int) bxm_val('SELECT COALESCE(SUM(quantity),0) FROM carts WHERE uid = ?', [$uid]);
}

function bxm_wishlist_ids($uid)
{
    if (!$uid) {
        return [];
    }
    return array_column(bxm_all('SELECT product_id FROM wishlists WHERE uid = ?', [$uid]), 'product_id');
}

function bxm_wishlist_count($uid)
{
    if (!$uid) {
        return 0;
    }
    return (int) bxm_val('SELECT COUNT(*) FROM wishlists WHERE uid = ?', [$uid]);
}

/* ---------------------------------------------------------------------------
 * Settings
 * ------------------------------------------------------------------------- */

function bxm_get_setting($name, $default = [])
{
    $value = bxm_val('SELECT value FROM settings WHERE name = ? LIMIT 1', [$name]);
    if ($value === null) {
        return $default;
    }
    $decoded = json_decode($value, true);
    return is_array($decoded) ? $decoded : $default;
}

function bxm_set_setting($name, $value)
{
    $json = bxm_json_encode_col($value);
    bxm_exec(
        'INSERT INTO settings (name, value) VALUES (?,?) ON DUPLICATE KEY UPDATE value = VALUES(value)',
        [$name, $json]
    );
}

/* ---------------------------------------------------------------------------
 * Bootstrap helpers
 * ------------------------------------------------------------------------- */

function bxm_boot()
{
    if (!defined('BXM_BOOTED')) {
        define('BXM_BOOTED', true);
        bxm_csrf_token();
    }
}
