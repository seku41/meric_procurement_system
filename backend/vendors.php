<?php
header('Content-Type: application/json');
require_once 'db.php';

function decodeVendorRow(array $vendor): array {
    foreach (['supply_items', 'payment_details', 'images'] as $jsonField) {
        if (!empty($vendor[$jsonField]) && is_string($vendor[$jsonField])) {
            $decoded = json_decode($vendor[$jsonField], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $vendor[$jsonField] = $decoded;
            }
        }
    }

    return $vendor;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $stmt = $pdo->prepare('SELECT * FROM vendors WHERE id = ?');
                $stmt->execute([$_GET['id']]);
                $vendor = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode($vendor ? decodeVendorRow($vendor) : null);
            } else {
                $stmt = $pdo->query('SELECT * FROM vendors ORDER BY created_at DESC');
                $vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $vendors = array_map('decodeVendorRow', $vendors);
                echo json_encode($vendors);
            }
            break;
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data || !isset($data['name'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid input']);
                exit;
            }

            $stmt = $pdo->prepare('INSERT INTO vendors (
                name, contact_info, id_number, supply_items, items, prices,
                specifications, size, color, location, delivery_method,
                delivery_date, payment_method, payment_details, images, user_id, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

            $stmt->execute([
                $data['name'],
                $data['contact_info'] ?? null,
                $data['id_number'] ?? null,
                is_array($data['supply_items']) ? json_encode($data['supply_items']) : ($data['supply_items'] ?? null),
                $data['items'] ?? null,
                $data['prices'] ?? null,
                $data['specifications'] ?? null,
                $data['size'] ?? null,
                $data['color'] ?? null,
                $data['location'] ?? null,
                $data['delivery_method'] ?? null,
                $data['delivery_date'] ?? null,
                $data['payment_method'] ?? null,
                is_array($data['payment_details']) ? json_encode($data['payment_details']) : ($data['payment_details'] ?? null),
            is_array($data['images']) ? json_encode($data['images']) : ($data['images'] ?? null),
            $data['user_id'] ?? null
            ]);

            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            break;
        case 'PUT':
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Missing vendor id']);
                exit;
            }
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid input']);
                exit;
            }

            $fields = [];
            $params = [];
            $allowed = [
                'name', 'contact_info', 'id_number', 'supply_items', 'items', 'prices',
                'specifications', 'size', 'color', 'location', 'delivery_method',
                'delivery_date', 'payment_method', 'payment_details', 'images', 'user_id', 'status'
            ];

            foreach ($allowed as $field) {
                if (!array_key_exists($field, $data)) {
                    continue;
                }

                $fields[] = "`$field` = ?";
                if (in_array($field, ['supply_items', 'payment_details', 'images'], true) && is_array($data[$field])) {
                    $params[] = json_encode($data[$field]);
                } else {
                    $params[] = $data[$field];
                }
            }

            if (empty($fields)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'No valid fields to update']);
                exit;
            }

            $params[] = $_GET['id'];
            $sql = 'UPDATE vendors SET ' . implode(', ', $fields) . ' WHERE id = ?';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['success' => true]);
            break;
        case 'DELETE':
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Missing vendor id']);
                exit;
            }
            $stmt = $pdo->prepare('DELETE FROM vendors WHERE id = ?');
            $stmt->execute([$_GET['id']]);
            echo json_encode(['success' => true]);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            break;
    }
} catch (PDOException $e) {
    http_response_code(500);
    $message = $e->getCode() === '23000'
        ? 'This ID number is already registered.'
        : 'Database error: ' . $e->getMessage();
    echo json_encode(['success' => false, 'error' => $message]);
}
