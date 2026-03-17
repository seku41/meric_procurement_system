<?php
header('Content-Type: application/json');

// Create uploads directory if it doesn't exist
$uploadDir = '../uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploadedFiles = [];
    $errors = [];
    
    // Handle multiple file uploads
    if (isset($_FILES['images'])) {
        $files = $_FILES['images'];
    } elseif (isset($_FILES['images[]'])) {
        $files = $_FILES['images[]'];
    } elseif (isset($_FILES['images'])) {
        // Handle single file upload
        $files = array(
            'name' => array($_FILES['images']['name']),
            'type' => array($_FILES['images']['type']),
            'tmp_name' => array($_FILES['images']['tmp_name']),
            'error' => array($_FILES['images']['error']),
            'size' => array($_FILES['images']['size'])
        );
    } else {
        echo json_encode(['error' => 'No images uploaded']);
        exit;
    }
        
        // Handle single file upload
        if (!is_array($files['name'])) {
            $files = array(
                'name' => array($files['name']),
                'type' => array($files['type']),
                'tmp_name' => array($files['tmp_name']),
                'error' => array($files['error']),
                'size' => array($files['size'])
            );
        }
        
        for ($i = 0; $i < count($files['name']); $i++) {
            $fileName = $files['name'][$i];
            $fileType = $files['type'][$i];
            $fileTmpName = $files['tmp_name'][$i];
            $fileError = $files['error'][$i];
            $fileSize = $files['size'][$i];
            
            // Check for upload errors
            if ($fileError !== UPLOAD_ERR_OK) {
                $errors[] = "Error uploading $fileName: " . $fileError;
                continue;
            }
            
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!in_array($fileType, $allowedTypes)) {
                $errors[] = "Invalid file type for $fileName. Only JPG, PNG, and GIF are allowed.";
                continue;
            }
            
            // Validate file size (5MB max)
            if ($fileSize > 5 * 1024 * 1024) {
                $errors[] = "File $fileName is too large. Maximum size is 5MB.";
                continue;
            }
            
            // Generate unique filename
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $uniqueFileName = uniqid() . '_' . time() . '.' . $fileExtension;
            $uploadPath = $uploadDir . $uniqueFileName;
            
            // Move uploaded file
            if (move_uploaded_file($fileTmpName, $uploadPath)) {
                $uploadedFiles[] = 'uploads/' . $uniqueFileName;
            } else {
                $errors[] = "Failed to save $fileName";
            }
        }
    }
    
    if (empty($errors)) {
        echo json_encode([
            'success' => true,
            'files' => $uploadedFiles,
            'message' => 'Files uploaded successfully'
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'errors' => $errors,
            'files' => $uploadedFiles
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?> 