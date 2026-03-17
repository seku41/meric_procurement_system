<?php
header('Content-Type: application/json');
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['username'], $data['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Username and password are required']);
        exit;
    }
    
    $username = $data['username'];
    $password = $data['password'];
    
    // Check if user exists and verify password
    $stmt = $pdo->prepare('SELECT id, username, email, password, role FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        // Remove password from response for security
        unset($user['password']);
        echo json_encode([
            'success' => true,
            'user' => $user,
            'message' => 'Login successful'
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid username or password'
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Login unsuccessful']);
}
?> 