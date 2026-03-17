<?php
header('Content-Type: application/json');
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $stmt = $pdo->prepare('SELECT * FROM vendors WHERE id = ?');
            $stmt->execute([$_GET['id']]);
            $vendor = $stmt->fetch();
            echo json_encode($vendor);
        } else {
            $stmt = $pdo->query('SELECT * FROM vendors');
            $vendors = $stmt->fetchAll();
            echo json_encode($vendors);
        }
        break;
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid input']);
            exit;
        }
        
        // Prepare all the fields for insertion
        $stmt = $pdo->prepare('INSERT INTO vendors (
            name, contact_info, id_number, supply_items, items, prices, 
            specifications, size, color, location, delivery_method, 
            delivery_date, payment_method, payment_details, images, user_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        
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
            echo json_encode(['error' => 'Missing vendor id']);
            exit;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid input']);
            exit;
        }

        $fields = [];
        $params = [];
        $allowed = ['name', 'contact_info', 'user_id', 'status'];
        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $fields[] = "`$field` = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($fields)) {
            http_response_code(400);
            echo json_encode(['error' => 'No valid fields to update']);
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
            echo json_encode(['error' => 'Missing vendor id']);
            exit;
        }
        $stmt = $pdo->prepare('DELETE FROM vendors WHERE id = ?');
        $stmt->execute([$_GET['id']]);
        echo json_encode(['success' => true]);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
} 