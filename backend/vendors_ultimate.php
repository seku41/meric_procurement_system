<?php
// Prevent any HTML output
ob_clean();
ob_start();

// Set JSON header immediately
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Disable error display in output
error_reporting(0);
ini_set('display_errors', 0);

// Function to send JSON response and exit
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    sendJsonResponse(['message' => 'OK']);
}

try {
    // Include database connection
    require_once 'db.php';
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            try {
                if (isset($_GET['id'])) {
                    $stmt = $pdo->prepare('SELECT * FROM vendors WHERE id = ?');
                    $stmt->execute([$_GET['id']]);
                    $vendor = $stmt->fetch(PDO::FETCH_ASSOC);
                    sendJsonResponse($vendor ?: ['error' => 'Vendor not found']);
                } else {
                    $stmt = $pdo->query('SELECT * FROM vendors ORDER BY created_at DESC');
                    $vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    sendJsonResponse($vendors);
                }
            } catch (Exception $e) {
                sendJsonResponse(['error' => 'Database query failed: ' . $e->getMessage()], 500);
            }
            break;
            
        case 'POST':
            try {
                $input = file_get_contents('php://input');
                $data = json_decode($input, true);
                
                if (!$data) {
                    sendJsonResponse(['error' => 'Invalid JSON input'], 400);
                }
                
                if (!isset($data['name']) || empty($data['name'])) {
                    sendJsonResponse(['error' => 'Vendor name is required'], 400);
                }
                
                // Check for duplicate ID number
                if (isset($data['id_number']) && !empty($data['id_number'])) {
                    $checkStmt = $pdo->prepare('SELECT id FROM vendors WHERE id_number = ?');
                    $checkStmt->execute([$data['id_number']]);
                    if ($checkStmt->fetch()) {
                        sendJsonResponse(['error' => 'ID Number already exists'], 400);
                    }
                }
                
                // Prepare SQL with all fields
                $sql = "INSERT INTO vendors (
                    name, contact_info, id_number, supply_items, items, prices, 
                    specifications, size, color, location, delivery_method, 
                    delivery_date, payment_method, payment_details, images, user_id, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
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
                                         null, // Images field removed
                    $data['user_id'] ?? null,
                    'pending'
                ]);
                
                if ($result) {
                    $vendorId = $pdo->lastInsertId();
                    sendJsonResponse([
                        'success' => true, 
                        'id' => $vendorId,
                        'message' => 'Vendor registered successfully'
                    ]);
                } else {
                    sendJsonResponse(['error' => 'Failed to insert vendor'], 500);
                }
                
            } catch (Exception $e) {
                sendJsonResponse(['error' => 'Registration failed: ' . $e->getMessage()], 500);
            }
            break;
            
        case 'PUT':
            try {
                if (!isset($_GET['id'])) {
                    sendJsonResponse(['error' => 'Missing vendor ID'], 400);
                }
                
                $input = file_get_contents('php://input');
                $data = json_decode($input, true);
                
                if (!$data) {
                    sendJsonResponse(['error' => 'Invalid JSON input'], 400);
                }
                
                $sql = "UPDATE vendors SET name = ?, contact_info = ?, user_id = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([
                    $data['name'] ?? '',
                    $data['contact_info'] ?? null,
                    $data['user_id'] ?? null,
                    $_GET['id']
                ]);
                
                if ($result) {
                    sendJsonResponse(['success' => true, 'message' => 'Vendor updated successfully']);
                } else {
                    sendJsonResponse(['error' => 'Failed to update vendor'], 500);
                }
                
            } catch (Exception $e) {
                sendJsonResponse(['error' => 'Update failed: ' . $e->getMessage()], 500);
            }
            break;
            
        case 'DELETE':
            try {
                if (!isset($_GET['id'])) {
                    sendJsonResponse(['error' => 'Missing vendor ID'], 400);
                }
                
                $stmt = $pdo->prepare('DELETE FROM vendors WHERE id = ?');
                $result = $stmt->execute([$_GET['id']]);
                
                if ($result) {
                    sendJsonResponse(['success' => true, 'message' => 'Vendor deleted successfully']);
                } else {
                    sendJsonResponse(['error' => 'Failed to delete vendor'], 500);
                }
                
            } catch (Exception $e) {
                sendJsonResponse(['error' => 'Delete failed: ' . $e->getMessage()], 500);
            }
            break;
            
        default:
            sendJsonResponse(['error' => 'Method not allowed'], 405);
            break;
    }
    
} catch (Exception $e) {
    sendJsonResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
}
?> 