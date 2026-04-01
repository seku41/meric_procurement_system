<?php
header('Content-Type: application/json');
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

function ensureInventoryTable(PDO $pdo) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `inventory` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `item_name` VARCHAR(150) NOT NULL,
            `category` VARCHAR(100) DEFAULT NULL,
            `quantity` INT NOT NULL DEFAULT 0,
            `used_quantity` INT NOT NULL DEFAULT 0,
            `unit` VARCHAR(30) DEFAULT NULL,
            `reorder_level` INT NOT NULL DEFAULT 0,
            `notes` TEXT DEFAULT NULL,
            `last_used_at` TIMESTAMP NULL DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY `idx_inventory_item_name` (`item_name`),
            KEY `idx_inventory_category` (`category`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function ensureInventoryUsageColumns(PDO $pdo) {
    $columns = $pdo->query('SHOW COLUMNS FROM `inventory`')->fetchAll(PDO::FETCH_COLUMN, 0);

    if (!in_array('used_quantity', $columns, true)) {
        $pdo->exec('ALTER TABLE `inventory` ADD COLUMN `used_quantity` INT NOT NULL DEFAULT 0 AFTER `quantity`');
    }

    if (!in_array('last_used_at', $columns, true)) {
        $pdo->exec('ALTER TABLE `inventory` ADD COLUMN `last_used_at` TIMESTAMP NULL DEFAULT NULL AFTER `notes`');
    }
}

function readJsonBody() {
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') return null;
    return json_decode($raw, true);
}

function normalizeInventoryQuantity($value, string $fieldName): int {
    $quantity = (int)$value;
    if ($quantity < 0) {
        throw new InvalidArgumentException($fieldName . ' cannot be negative');
    }

    return $quantity;
}

try {
    ensureInventoryTable($pdo);
    ensureInventoryUsageColumns($pdo);

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
                isset($data['quantity']) ? normalizeInventoryQuantity($data['quantity'], 'Quantity') : 0,
                $data['unit'] ?? null,
                isset($data['reorder_level']) ? normalizeInventoryQuantity($data['reorder_level'], 'Reorder level') : 0,
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
            $allowed = ['item_name', 'category', 'quantity', 'used_quantity', 'unit', 'reorder_level', 'notes', 'last_used_at'];
            foreach ($allowed as $field) {
                if (array_key_exists($field, $data)) {
                    $fields[] = "`$field` = ?";
                    if ($field === 'quantity' || $field === 'used_quantity' || $field === 'reorder_level') {
                        $fieldLabel = $field === 'quantity'
                            ? 'Quantity'
                            : ($field === 'used_quantity' ? 'Used quantity' : 'Reorder level');
                        $params[] = normalizeInventoryQuantity($data[$field], $fieldLabel);
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
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

