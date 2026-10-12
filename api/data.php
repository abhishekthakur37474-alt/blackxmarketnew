<?php
require_once __DIR__ . '/../includes/helpers.php';

bxm_require_post();
bxm_require_csrf();

if (!bxm_is_admin()) {
    bxm_json(['ok' => false, 'error' => 'Access denied.'], 403);
}

$input = bxm_input_json();
$resource = (string) ($input['resource'] ?? '');
$action = (string) ($input['action'] ?? '');
$now = bxm_now_ms();

function bxm_data_fields($input, $map)
{
    $out = [];
    foreach ($map as $key => $column) {
        if (array_key_exists($key, $input)) {
            $out[$column] = $input[$key];
        }
    }
    return $out;
}

function bxm_normalize_details($value)
{
    if (!is_array($value)) {
        return [];
    }
    $out = [];
    $i = 0;
    foreach ($value as $item) {
        if (!is_array($item)) {
            continue;
        }
        $name = trim((string) ($item['name'] ?? ''));
        if ($name === '') {
            continue;
        }
        $out[] = [
            'name' => $name,
            'value' => (string) ($item['value'] ?? ''),
            'order' => $i,
        ];
        $i++;
    }
    return $out;
}

switch ($resource) {
    case 'product':
        if ($action === 'save') {
            $id = trim((string) ($input['id'] ?? ''));
            $title = trim((string) ($input['title'] ?? ''));
            $original = (float) ($input['originalPrice'] ?? 0);
            $discounted = (float) ($input['discountedPrice'] ?? 0);
            if ($title === '') {
                bxm_json(['ok' => false, 'error' => 'Title is required.'], 400);
            }
            $data = [
                'title' => $title,
                'category' => trim((string) ($input['category'] ?? '')),
                'description' => trim((string) ($input['description'] ?? '')),
                'original_price' => $original,
                'discounted_price' => $discounted,
                'discount_percent' => bxm_discount_percent($original, $discounted),
                'status' => (string) ($input['status'] ?? 'active'),
                'thumbnail_url' => trim((string) ($input['thumbnailUrl'] ?? '')),
                'additional_details' => bxm_json_encode_col(bxm_normalize_details($input['additionalDetails'] ?? [])),
                'updated_at' => $now,
            ];
            if ($id !== '' && bxm_product($id)) {
                $sets = implode(', ', array_map(function ($k) { return $k . ' = ?'; }, array_keys($data)));
                $params = array_values($data);
                $params[] = $id;
                bxm_exec('UPDATE products SET ' . $sets . ' WHERE id = ?', $params);
            } else {
                $id = bxm_uid();
                $data['id'] = $id;
                $data['created_at'] = $now;
                $cols = implode(', ', array_keys($data));
                $place = implode(', ', array_fill(0, count($data), '?'));
                bxm_exec('INSERT INTO products (' . $cols . ') VALUES (' . $place . ')', array_values($data));
            }
            bxm_json(['ok' => true, 'id' => $id]);
        }
        if ($action === 'delete') {
            bxm_exec('DELETE FROM products WHERE id = ?', [trim((string) ($input['id'] ?? ''))]);
            bxm_json(['ok' => true]);
        }
        if ($action === 'duplicate') {
            $src = bxm_product(trim((string) ($input['id'] ?? '')));
            if (!$src) {
                bxm_json(['ok' => false, 'error' => 'Product not found.'], 404);
            }
            $newId = bxm_uid();
            bxm_exec(
                'INSERT INTO products (id, title, category, description, original_price, discounted_price, discount_percent, status, thumbnail_url, additional_details, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
                [
                    $newId,
                    $src['title'] . ' (Copy)',
                    $src['category'],
                    $src['description'],
                    $src['originalPrice'],
                    $src['discountedPrice'],
                    $src['discountPercent'],
                    $src['status'],
                    $src['thumbnailUrl'],
                    bxm_json_encode_col($src['additionalDetails']),
                    $now,
                    $now,
                ]
            );
            bxm_json(['ok' => true, 'id' => $newId]);
        }
        if ($action === 'toggle') {
            $id = trim((string) ($input['id'] ?? ''));
            $product = bxm_product($id);
            if (!$product) {
                bxm_json(['ok' => false, 'error' => 'Product not found.'], 404);
            }
            $next = $product['status'] === 'active' ? 'inactive' : 'active';
            bxm_exec('UPDATE products SET status = ?, updated_at = ? WHERE id = ?', [$next, $now, $id]);
            bxm_json(['ok' => true, 'status' => $next]);
        }
        break;

    case 'carousel':
        $map = [
            'title' => 'title',
            'description' => 'description',
            'buttonText' => 'button_text',
            'link' => 'link',
            'imageUrl' => 'image_url',
            'status' => 'status',
        ];
        if ($action === 'save') {
            $id = trim((string) ($input['id'] ?? ''));
            $data = bxm_data_fields($input, $map);
            $data['sort_order'] = (int) ($input['order'] ?? 0);
            $data['updated_at'] = $now;
            if ($id !== '' && bxm_row('SELECT id FROM carousels WHERE id = ? LIMIT 1', [$id])) {
                $sets = implode(', ', array_map(function ($k) { return $k . ' = ?'; }, array_keys($data)));
                $params = array_values($data);
                $params[] = $id;
                bxm_exec('UPDATE carousels SET ' . $sets . ' WHERE id = ?', $params);
            } else {
                $id = bxm_uid();
                $data['id'] = $id;
                $data['created_at'] = $now;
                $cols = implode(', ', array_keys($data));
                $place = implode(', ', array_fill(0, count($data), '?'));
                bxm_exec('INSERT INTO carousels (' . $cols . ') VALUES (' . $place . ')', array_values($data));
            }
            bxm_json(['ok' => true, 'id' => $id]);
        }
        if ($action === 'delete') {
            bxm_exec('DELETE FROM carousels WHERE id = ?', [trim((string) ($input['id'] ?? ''))]);
            bxm_json(['ok' => true]);
        }
        if ($action === 'toggle') {
            $id = trim((string) ($input['id'] ?? ''));
            $row = bxm_row('SELECT status FROM carousels WHERE id = ? LIMIT 1', [$id]);
            if (!$row) {
                bxm_json(['ok' => false, 'error' => 'Slide not found.'], 404);
            }
            $next = $row['status'] === 'active' ? 'inactive' : 'active';
            bxm_exec('UPDATE carousels SET status = ?, updated_at = ? WHERE id = ?', [$next, $now, $id]);
            bxm_json(['ok' => true, 'status' => $next]);
        }
        break;

    case 'coupon':
        if ($action === 'save') {
            $id = trim((string) ($input['id'] ?? ''));
            $code = strtoupper(trim((string) ($input['code'] ?? '')));
            if ($code === '') {
                bxm_json(['ok' => false, 'error' => 'Coupon code is required.'], 400);
            }
            $dupe = bxm_row('SELECT id FROM coupons WHERE code = ? AND id <> ? LIMIT 1', [$code, $id]);
            if ($dupe) {
                bxm_json(['ok' => false, 'error' => 'This coupon code already exists.'], 400);
            }
            $data = [
                'code' => $code,
                'type' => (string) ($input['type'] ?? 'percentage'),
                'value' => (float) ($input['value'] ?? 0),
                'min_amount' => (float) ($input['minAmount'] ?? 0),
                'max_discount' => (float) ($input['maxDiscount'] ?? 0),
                'expiry_date' => trim((string) ($input['expiryDate'] ?? '')),
                'usage_limit' => (int) ($input['usageLimit'] ?? 0),
                'status' => (string) ($input['status'] ?? 'active'),
                'updated_at' => $now,
            ];
            if ($id !== '' && bxm_row('SELECT id FROM coupons WHERE id = ? LIMIT 1', [$id])) {
                $sets = implode(', ', array_map(function ($k) { return $k . ' = ?'; }, array_keys($data)));
                $params = array_values($data);
                $params[] = $id;
                bxm_exec('UPDATE coupons SET ' . $sets . ' WHERE id = ?', $params);
            } else {
                $id = bxm_uid();
                $data['id'] = $id;
                $data['used_count'] = 0;
                $data['created_at'] = $now;
                $cols = implode(', ', array_keys($data));
                $place = implode(', ', array_fill(0, count($data), '?'));
                bxm_exec('INSERT INTO coupons (' . $cols . ') VALUES (' . $place . ')', array_values($data));
            }
            bxm_json(['ok' => true, 'id' => $id]);
        }
        if ($action === 'delete') {
            bxm_exec('DELETE FROM coupons WHERE id = ?', [trim((string) ($input['id'] ?? ''))]);
            bxm_json(['ok' => true]);
        }
        if ($action === 'toggle') {
            $id = trim((string) ($input['id'] ?? ''));
            $row = bxm_row('SELECT status FROM coupons WHERE id = ? LIMIT 1', [$id]);
            if (!$row) {
                bxm_json(['ok' => false, 'error' => 'Coupon not found.'], 404);
            }
            $next = $row['status'] === 'active' ? 'inactive' : 'active';
            bxm_exec('UPDATE coupons SET status = ?, updated_at = ? WHERE id = ?', [$next, $now, $id]);
            bxm_json(['ok' => true, 'status' => $next]);
        }
        break;

    case 'order':
        $orderId = trim((string) ($input['orderId'] ?? ''));
        $order = bxm_order($orderId);
        if (!$order) {
            bxm_json(['ok' => false, 'error' => 'Order not found.'], 404);
        }
        if ($action === 'approve') {
            $details = bxm_normalize_details($input['deliveryDetails'] ?? []);
            if (empty($details)) {
                bxm_json(['ok' => false, 'error' => 'Add at least one delivery detail.'], 400);
            }
            bxm_exec(
                'UPDATE orders SET status = ?, delivery_details = ?, rejection_reason = ?, updated_at = ? WHERE order_id = ?',
                ['approved', bxm_json_encode_col($details), '', $now, $orderId]
            );
            bxm_notify($order['uid'], [
                'title' => 'Order approved',
                'message' => 'Your order ' . $orderId . ' is approved. Your delivery card is now available.',
                'type' => 'success',
                'link' => bxm_url('order-details.php?id=' . urlencode($orderId) . '&card=1'),
            ]);
            bxm_json(['ok' => true]);
        }
        if ($action === 'reject') {
            $reason = trim((string) ($input['reason'] ?? ''));
            if ($reason === '') {
                bxm_json(['ok' => false, 'error' => 'Please enter a rejection reason.'], 400);
            }
            bxm_exec('UPDATE orders SET status = ?, rejection_reason = ?, updated_at = ? WHERE order_id = ?', ['rejected', $reason, $now, $orderId]);
            bxm_notify($order['uid'], [
                'title' => 'Payment rejected',
                'message' => 'Your payment for order ' . $orderId . ' was rejected. Reason: ' . $reason,
                'type' => 'danger',
                'link' => bxm_url('order-details.php?id=' . urlencode($orderId)),
            ]);
            bxm_json(['ok' => true]);
        }
        break;

    case 'user':
        if ($action === 'toggle-status') {
            $uid = trim((string) ($input['uid'] ?? ''));
            $row = bxm_row('SELECT status FROM users WHERE uid = ? LIMIT 1', [$uid]);
            if (!$row) {
                bxm_json(['ok' => false, 'error' => 'User not found.'], 404);
            }
            $next = $row['status'] === 'banned' ? 'active' : 'banned';
            bxm_exec('UPDATE users SET status = ?, updated_at = ? WHERE uid = ?', [$next, $now, $uid]);
            bxm_json(['ok' => true, 'status' => $next]);
        }
        break;

    case 'review':
        $id = trim((string) ($input['id'] ?? ''));
        if ($action === 'set-status') {
            $status = (string) ($input['status'] ?? '');
            if (!in_array($status, ['approved', 'rejected', 'pending'], true)) {
                bxm_json(['ok' => false, 'error' => 'Invalid status.'], 400);
            }
            bxm_exec('UPDATE reviews SET status = ? WHERE id = ?', [$status, $id]);
            $row = bxm_row('SELECT uid, product_title FROM reviews WHERE id = ? LIMIT 1', [$id]);
            if ($row && $row['uid']) {
                if ($status === 'approved') {
                    bxm_notify($row['uid'], [
                        'title' => 'Review approved',
                        'message' => 'Your review for ' . ($row['product_title'] ?: 'a product') . ' is now live.',
                        'type' => 'success',
                        'link' => bxm_url('reviews.php'),
                    ]);
                } elseif ($status === 'rejected') {
                    bxm_notify($row['uid'], [
                        'title' => 'Review not published',
                        'message' => 'Your review for ' . ($row['product_title'] ?: 'a product') . ' was not approved.',
                        'type' => 'warning',
                        'link' => bxm_url('reviews.php'),
                    ]);
                }
            }
            bxm_json(['ok' => true]);
        }
        if ($action === 'delete') {
            bxm_exec('DELETE FROM reviews WHERE id = ?', [$id]);
            bxm_json(['ok' => true]);
        }
        break;

    case 'support':
        if ($action === 'reply') {
            $id = trim((string) ($input['id'] ?? ''));
            $reply = trim((string) ($input['reply'] ?? ''));
            $status = (string) ($input['status'] ?? 'open');
            if (!in_array($status, ['open', 'replied', 'closed'], true)) {
                $status = 'open';
            }
            $row = bxm_row('SELECT uid, subject, reply, status FROM support_tickets WHERE id = ? LIMIT 1', [$id]);
            if (!$row) {
                bxm_json(['ok' => false, 'error' => 'Ticket not found.'], 404);
            }
            bxm_exec('UPDATE support_tickets SET reply = ?, status = ?, updated_at = ? WHERE id = ?', [$reply, $status, $now, $id]);
            if ($row['uid'] && ($reply !== ($row['reply'] ?? '') || $status === 'replied')) {
                bxm_notify($row['uid'], [
                    'title' => $status === 'closed' ? 'Support ticket closed' : 'Support replied',
                    'message' => 'Your ticket "' . ($row['subject'] ?? '') . '" has a new update.',
                    'type' => 'info',
                    'link' => bxm_url('support.php'),
                ]);
            }
            bxm_json(['ok' => true]);
        }
        break;

    case 'settings':
        if ($action === 'save-payment') {
            $instructions = trim((string) ($input['instructions'] ?? ''));
            bxm_set_setting('payment', [
                'upiId' => trim((string) ($input['upiId'] ?? '')),
                'paymentDisplayName' => trim((string) ($input['paymentDisplayName'] ?? '')),
                'instructions' => $instructions,
                'active' => !empty($input['active']),
                'qrCodeUrl' => trim((string) ($input['qrCodeUrl'] ?? '')),
                'updatedAt' => $now,
            ]);
            bxm_json(['ok' => true]);
        }
        if ($action === 'remove-qr') {
            $payment = bxm_get_setting('payment', []);
            $payment['qrCodeUrl'] = '';
            bxm_set_setting('payment', $payment);
            bxm_json(['ok' => true]);
        }
        if ($action === 'save-site') {
            bxm_set_setting('siteInfo', [
                'siteName' => trim((string) ($input['siteName'] ?? 'BLACK X MARKET')),
                'tagline' => trim((string) ($input['tagline'] ?? '')),
                'supportEmail' => trim((string) ($input['supportEmail'] ?? '')),
                'currency' => trim((string) ($input['currency'] ?? '₹')) ?: '₹',
                'updatedAt' => $now,
            ]);
            bxm_json(['ok' => true]);
        }
        break;
}

bxm_json(['ok' => false, 'error' => 'Unsupported action.'], 400);
