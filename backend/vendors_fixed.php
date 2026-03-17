<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't show errors in JSON response

try {
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
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data || !isset($data['name'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid input - name is required']);
                exit;
            }
            
            // Prepare the SQL with all possible fields
            $sql = "INSERT INTO vendors (
                name, contact_info, id_number, supply_items, items, prices, 
                specifications, size, color, location, delivery_method, 
                delivery_date, payment_method, payment_details, images, user_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
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
                is_array($data['images']) ? json_encode($data['images']) : ($data['images'] ?? null),
                $data['user_id'] ?? null
            ]);
            
            if ($result) {
                $vendorId = $pdo->lastInsertId();
                echo json_encode(['success' => true, 'id' => $vendorId]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Database insertion failed']);
            }
            break;
            
        case 'PUT':
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing vendor id']);
                exit;
            }
            
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            $sql = "UPDATE vendors SET name = ?, contact_info = ?, user_id = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                $data['name'] ?? '',
                $data['contact_info'] ?? null,
                $data['user_id'] ?? null,
                $_GET['id']
            ]);
            
            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Update failed']);
            }
            break;
            
        case 'DELETE':
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing vendor id']);
                exit;
            }
            
            $stmt = $pdo->prepare('DELETE FROM vendors WHERE id = ?');
            $result = $stmt->execute([$_GET['id']]);
            
            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Delete failed']);
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
?> 