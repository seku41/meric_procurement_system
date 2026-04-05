<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
require_once 'db.php';

function ensureOrderInventoryTracking(PDO $pdo) {
    $stmt = $pdo->query("SHOW COLUMNS FROM `orders` LIKE 'inventory_recorded'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("
            ALTER TABLE `orders`
            ADD COLUMN `inventory_recorded` TINYINT(1) NOT NULL DEFAULT 0
            AFTER `payment_status`
        ");
    }
}

function ensureOrderMpesaColumns(PDO $pdo) {
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

    $columns = $pdo->query('SHOW COLUMNS FROM `orders`')->fetchAll(PDO::FETCH_COLUMN, 0);
    foreach ($definitions as $column => $sql) {
        if (!in_array($column, $columns, true)) {
            $pdo->exec($sql);
        }
    }
}

try {
ensureOrderInventoryTracking($pdo);
ensureOrderMpesaColumns($pdo);
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
            $stmt->execute([$_GET['id']]);
            $order = $stmt->fetch();
            echo json_encode($order);
        } else {
                $stmt = $pdo->query('SELECT * FROM orders ORDER BY order_date DESC');
            $orders = $stmt->fetchAll();
            echo json_encode($orders);
        }
        break;
    case 'POST':
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON input: ' . json_last_error_msg()]);
                exit;
            }
            
            if (!isset($data['vendor_id'], $data['product'], $data['quantity'])) {
            http_response_code(400);
                echo json_encode(['error' => 'Missing required fields: vendor_id, product, quantity']);
            exit;
        }
            
            // Prepare the SQL with all possible fields
            $sql = 'INSERT INTO orders (
                vendor_id, user_id, product, quantity, unit_price, total_price, 
                specifications, size, color, payment_method, payment_details, 
                vendor_id_number, location, delivery_method, delivery_date, 
                deadline, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
            $data['vendor_id'],
                $data['user_id'] ?? null, // Make user_id optional
            $data['product'],
            $data['quantity'],
                $data['unit_price'] ?? 0.00,
                $data['total_price'] ?? 0.00,
                $data['specifications'] ?? null,
                $data['size'] ?? null,
                $data['color'] ?? null,
                $data['payment_method'] ?? null,
                isset($data['payment_details']) ? json_encode($data['payment_details']) : null,
                $data['vendor_id_number'] ?? null,
                $data['location'] ?? null,
                $data['delivery_method'] ?? null,
                $data['delivery_date'] ?? null,
                $data['deadline'] ?? null,
            $data['status'] ?? 'pending'
        ]);
            
            if ($result) {
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create order: ' . implode(', ', $stmt->errorInfo())]);
            }
        break;
    case 'PUT':
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing order id']);
            exit;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $fields = [];
        $params = [];
            
            // Define all possible fields that can be updated
            $possibleFields = [
                'vendor_id', 'user_id', 'product', 'quantity', 'unit_price', 
                'total_price', 'specifications', 'size', 'color', 'payment_method', 
                'payment_details', 'vendor_id_number', 'location', 'delivery_method', 
                'delivery_date', 'deadline', 'status', 'payment_status', 'inventory_recorded'
            ];
            
            foreach ($possibleFields as $field) {
                if (isset($data[$field])) {
                    $fields[] = $field . ' = ?';
                    if ($field === 'payment_details' && is_array($data[$field])) {
                        $params[] = json_encode($data[$field]);
                    } else {
                        $params[] = $data[$field];
        }
                }
            }
            
        if (empty($fields)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update']);
            exit;
        }
            
        $params[] = $_GET['id'];
        $sql = 'UPDATE orders SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
        echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update order: ' . implode(', ', $stmt->errorInfo())]);
            }
        break;
    case 'DELETE':
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing order id']);
            exit;
        }
        $stmt = $pdo->prepare('DELETE FROM orders WHERE id = ?');
            $result = $stmt->execute([$_GET['id']]);
            
            if ($result) {
        echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to delete order: ' . implode(', ', $stmt->errorInfo())]);
            }
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
} 
