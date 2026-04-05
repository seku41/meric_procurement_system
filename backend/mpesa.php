<?php
header('Content-Type: application/json');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mpesa_config.php';

function ensureMpesaColumns(PDO $pdo): void
{
    $definitions = [
        'mpesa_phone' => "ALTER TABLE `orders` ADD COLUMN `mpesa_phone` VARCHAR(20) DEFAULT NULL AFTER `payment_status`",
        'mpesa_checkout_request_id' => "ALTER TABLE `orders` ADD COLUMN `mpesa_checkout_request_id` VARCHAR(120) DEFAULT NULL AFTER `mpesa_phone`",
        'mpesa_merchant_request_id' => "ALTER TABLE `orders` ADD COLUMN `mpesa_merchant_request_id` VARCHAR(120) DEFAULT NULL AFTER `mpesa_checkout_request_id`",
        'mpesa_receipt_number' => "ALTER TABLE `orders` ADD COLUMN `mpesa_receipt_number` VARCHAR(120) DEFAULT NULL AFTER `mpesa_merchant_request_id`",
        'mpesa_result_code' => "ALTER TABLE `orders` ADD COLUMN `mpesa_result_code` VARCHAR(20) DEFAULT NULL AFTER `mpesa_receipt_number`",
        'mpesa_result_desc' => "ALTER TABLE `orders` ADD COLUMN `mpesa_result_desc` TEXT DEFAULT NULL AFTER `mpesa_result_code`",
        'mpesa_status' => "ALTER TABLE `orders` ADD COLUMN `mpesa_status` VARCHAR(30) DEFAULT NULL AFTER `mpesa_result_desc`",
        'mpesa_requested_amount' => "ALTER TABLE `orders` ADD COLUMN `mpesa_requested_amount` DECIMAL(10,2) DEFAULT NULL AFTER `mpesa_status`",
        'mpesa_raw_callback' => "ALTER TABLE `orders` ADD COLUMN `mpesa_raw_callback` LONGTEXT DEFAULT NULL AFTER `mpesa_requested_amount`",
    ];

    $existing = $pdo->query('SHOW COLUMNS FROM `orders`')->fetchAll(PDO::FETCH_COLUMN, 0);
    foreach ($definitions as $column => $sql) {
        if (!in_array($column, $existing, true)) {
            $pdo->exec($sql);
        }
    }
}

function read_json_input(): array
{
    $input = file_get_contents('php://input');
    $data = json_decode($input ?: '', true);
    return is_array($data) ? $data : [];
}

function callback_ack(): void
{
    echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
}

ensureMpesaColumns($pdo);

$action = $_GET['action'] ?? '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'initiate') {
        $data = read_json_input();
        $orderId = isset($data['order_id']) ? (int) $data['order_id'] : 0;
        $phoneInput = trim((string) ($data['phone'] ?? ''));

        if ($orderId <= 0 || $phoneInput === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Order ID and phone number are required.']);
            exit;
        }

        $callbackUrl = mpesa_callback_url();
        if ($callbackUrl === '') {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Missing APP_URL or MPESA_CALLBACK_URL configuration.']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Order not found.']);
            exit;
        }

        $amount = (float) ($data['amount'] ?? $order['total_price'] ?? 0);
        if ($amount <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Order amount must be greater than zero.']);
            exit;
        }

        $shortcode = mpesa_env('MPESA_SHORTCODE', '');
        $passkey = mpesa_env('MPESA_PASSKEY', '');
        if ($shortcode === '' || $passkey === '') {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Missing M-Pesa shortcode or passkey.']);
            exit;
        }

        $phone = mpesa_normalize_phone($phoneInput);
        $timestamp = mpesa_timestamp();
        $payload = [
            'BusinessShortCode' => $shortcode,
            'Password' => mpesa_password($shortcode, $passkey, $timestamp),
            'Timestamp' => $timestamp,
            'TransactionType' => mpesa_env('MPESA_TRANSACTION_TYPE', 'CustomerPayBillOnline'),
            'Amount' => max(1, (int) round($amount)),
            'PartyA' => $phone,
            'PartyB' => $shortcode,
            'PhoneNumber' => $phone,
            'CallBackURL' => $callbackUrl,
            'AccountReference' => 'ORDER-' . $orderId,
            'TransactionDesc' => substr('Payment for order #' . $orderId . ' - ' . ($order['product'] ?? 'Procurement order'), 0, 182),
        ];

        $response = mpesa_stk_push($payload);

        $update = $pdo->prepare('UPDATE orders SET
            mpesa_phone = ?,
            mpesa_checkout_request_id = ?,
            mpesa_merchant_request_id = ?,
            mpesa_result_code = ?,
            mpesa_result_desc = ?,
            mpesa_status = ?,
            mpesa_requested_amount = ?
            WHERE id = ?');
        $update->execute([
            $phone,
            $response['CheckoutRequestID'] ?? null,
            $response['MerchantRequestID'] ?? null,
            $response['ResponseCode'] ?? null,
            $response['ResponseDescription'] ?? ($response['errorMessage'] ?? null),
            ($response['ResponseCode'] ?? '') === '0' ? 'pending' : 'failed',
            $amount,
            $orderId,
        ]);

        echo json_encode([
            'success' => ($response['ResponseCode'] ?? '') === '0',
            'message' => $response['CustomerMessage'] ?? $response['ResponseDescription'] ?? 'STK push request submitted.',
            'data' => $response,
        ]);
        exit;
    }

    if ($action === 'callback') {
        $payload = read_json_input();
        $callback = $payload['Body']['stkCallback'] ?? [];
        $checkoutId = $callback['CheckoutRequestID'] ?? null;
        $merchantId = $callback['MerchantRequestID'] ?? null;
        $resultCode = (string) ($callback['ResultCode'] ?? '');
        $resultDesc = $callback['ResultDesc'] ?? '';
        $items = $callback['CallbackMetadata']['Item'] ?? [];

        if ($checkoutId || $merchantId) {
            $stmt = $pdo->prepare('SELECT id FROM orders WHERE mpesa_checkout_request_id = ? OR mpesa_merchant_request_id = ? LIMIT 1');
            $stmt->execute([$checkoutId, $merchantId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($order) {
                $update = $pdo->prepare('UPDATE orders SET
                    payment_status = ?,
                    mpesa_receipt_number = ?,
                    mpesa_result_code = ?,
                    mpesa_result_desc = ?,
                    mpesa_status = ?,
                    mpesa_requested_amount = COALESCE(?, mpesa_requested_amount),
                    mpesa_phone = COALESCE(?, mpesa_phone),
                    mpesa_raw_callback = ?
                    WHERE id = ?');
                $update->execute([
                    $resultCode === '0' ? 'paid' : 'pending',
                    mpesa_extract_callback_value($items, 'MpesaReceiptNumber'),
                    $resultCode,
                    $resultDesc,
                    $resultCode === '0' ? 'paid' : 'failed',
                    mpesa_extract_callback_value($items, 'Amount'),
                    mpesa_extract_callback_value($items, 'PhoneNumber'),
                    json_encode($payload),
                    $order['id'],
                ]);
            }
        }

        callback_ack();
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'config') {
        echo json_encode([
            'success' => true,
            'environment' => mpesa_is_sandbox() ? 'sandbox' : 'production',
            'callback_url' => mpesa_callback_url(),
            'shortcode_configured' => mpesa_env('MPESA_SHORTCODE') !== null,
            'consumer_key_configured' => mpesa_env('MPESA_CONSUMER_KEY') !== null,
            'consumer_secret_configured' => mpesa_env('MPESA_CONSUMER_SECRET') !== null,
            'passkey_configured' => mpesa_env('MPESA_PASSKEY') !== null,
        ]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
} catch (Throwable $e) {
    error_log('M-Pesa error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'M-Pesa request failed.',
        'details' => getenv('APP_ENV') === 'local' ? $e->getMessage() : null,
    ]);
}
