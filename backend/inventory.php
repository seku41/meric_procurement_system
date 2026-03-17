<?php
header('Content-Type: application/json');
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

function readJsonBody() {
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') return null;
    return json_decode($raw, true);
}

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $stmt = $pdo->prepare('SELECT * FROM inventory WHERE id = ?');
                $stmt->execute([$_GET['id']]);
                $item = $stmt->fetch();
                echo json_encode($item ?: null);
            } else {
                $stmt = $pdo->query('SELECT * FROM inventory ORDER BY updated_at DESC, id DESC');
                echo json_encode($stmt->fetchAll());
            }
            break;

        case 'POST':
            $data = readJsonBody();
            if (!$data || !isset($data['item_name'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid input']);
                exit;
            }

            $stmt = $pdo->prepare('
                INSERT INTO inventory (item_name, category, quantity, unit, reorder_level, notes)
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                trim($data['item_name']),
                $data['category'] ?? null,
                isset($data['quantity']) ? (int)$data['quantity'] : 0,
                $data['unit'] ?? null,
                isset($data['reorder_level']) ? (int)$data['reorder_level'] : 0,
                $data['notes'] ?? null
            ]);

            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            break;

        case 'PUT':
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing inventory id']);
                exit;
            }

            $data = readJsonBody();
            if (!$data) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid input']);
                exit;
            }

            $fields = [];
            $params = [];
            $allowed = ['item_name', 'category', 'quantity', 'unit', 'reorder_level', 'notes'];
            foreach ($allowed as $field) {
                if (array_key_exists($field, $data)) {
                    $fields[] = "`$field` = ?";
                    if ($field === 'quantity' || $field === 'reorder_level') {
                        $params[] = (int)$data[$field];
                    } else if ($field === 'item_name') {
                        $params[] = trim((string)$data[$field]);
                    } else {
                        $params[] = $data[$field];
                    }
                }
            }

            if (empty($fields)) {
                http_response_code(400);
                echo json_encode(['error' => 'No valid fields to update']);
                exit;
            }

            $params[] = $_GET['id'];
            $sql = 'UPDATE inventory SET ' . implode(', ', $fields) . ' WHERE id = ?';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            echo json_encode(['success' => true]);
            break;

        case 'DELETE':
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing inventory id']);
                exit;
            }
            $stmt = $pdo->prepare('DELETE FROM inventory WHERE id = ?');
            $stmt->execute([$_GET['id']]);
            echo json_encode(['success' => true]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}

