<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $stmt = $pdo->query('SELECT * FROM vendors ORDER BY created_at DESC');
        $vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Decode JSON fields
        foreach ($vendors as &$vendor) {
            if (isset($vendor['supply_items']) && $vendor['supply_items']) {
                $decoded = json_decode($vendor['supply_items'], true);
                $vendor['supply_items'] = $decoded ? $decoded : $vendor['supply_items'];
            }
            if (isset($vendor['payment_details']) && $vendor['payment_details']) {
                $decoded = json_decode($vendor['payment_details'], true);
                $vendor['payment_details'] = $decoded ? $decoded : $vendor['payment_details'];
            }
        }
        
        echo json_encode($vendors);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

if ($method === 'POST') {
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || !isset($data['name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Name is required']);
            exit;
        }
        
        $sql = "INSERT INTO vendors (name, contact_info, id_number, supply_items, items, prices, specifications, size, color, location, delivery_method, delivery_date, payment_method, payment_details, user_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        
        $result = $stmt->execute([
            $data['name'] ?? '',
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
            $data['user_id'] ?? null,
            'pending'
        ]);
        
        if ($result) {
            $vendorId = $pdo->lastInsertId();
            echo json_encode(['success' => true, 'id' => $vendorId]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to insert vendor']);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Registration failed: ' . $e->getMessage()]);
    }
}

if ($method === 'PUT') {
    try {
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing vendor ID']);
            exit;
        }
        
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON input']);
            exit;
        }
        
        $vendorId = $_GET['id'];
        
        // Build dynamic update query based on provided fields
        $updateFields = [];
        $updateValues = [];
        
        if (isset($data['name'])) {
            $updateFields[] = 'name = ?';
            $updateValues[] = $data['name'];
        }
        if (isset($data['contact_info'])) {
            $updateFields[] = 'contact_info = ?';
            $updateValues[] = $data['contact_info'];
        }
        if (isset($data['status'])) {
            $updateFields[] = 'status = ?';
            $updateValues[] = $data['status'];
        }
        if (isset($data['supply_items'])) {
            $updateFields[] = 'supply_items = ?';
            $updateValues[] = is_array($data['supply_items']) ? json_encode($data['supply_items']) : $data['supply_items'];
        }
        if (isset($data['items'])) {
            $updateFields[] = 'items = ?';
            $updateValues[] = $data['items'];
        }
        if (isset($data['prices'])) {
            $updateFields[] = 'prices = ?';
            $updateValues[] = $data['prices'];
        }
        if (isset($data['specifications'])) {
            $updateFields[] = 'specifications = ?';
            $updateValues[] = $data['specifications'];
        }
        if (isset($data['size'])) {
            $updateFields[] = 'size = ?';
            $updateValues[] = $data['size'];
        }
        if (isset($data['color'])) {
            $updateFields[] = 'color = ?';
            $updateValues[] = $data['color'];
        }
        if (isset($data['location'])) {
            $updateFields[] = 'location = ?';
            $updateValues[] = $data['location'];
        }
        if (isset($data['delivery_method'])) {
            $updateFields[] = 'delivery_method = ?';
            $updateValues[] = $data['delivery_method'];
        }
        if (isset($data['delivery_date'])) {
            $updateFields[] = 'delivery_date = ?';
            $updateValues[] = $data['delivery_date'];
        }
        if (isset($data['payment_method'])) {
            $updateFields[] = 'payment_method = ?';
            $updateValues[] = $data['payment_method'];
        }
        if (isset($data['payment_details'])) {
            $updateFields[] = 'payment_details = ?';
            $updateValues[] = is_array($data['payment_details']) ? json_encode($data['payment_details']) : $data['payment_details'];
        }
        
        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update']);
            exit;
        }
        
        $updateValues[] = $vendorId; // Add ID for WHERE clause
        
        $sql = "UPDATE vendors SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        
        $result = $stmt->execute($updateValues);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Vendor updated successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update vendor']);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Update failed: ' . $e->getMessage()]);
    }
}
?> 