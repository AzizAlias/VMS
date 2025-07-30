<?php
session_start();
require_once 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['volunteer_id']) && !isset($_SESSION['organizer_id'])) {
    echo json_encode(["status" => "error", "message" => "User not logged in"]);
    exit;
}

// Check if a file was uploaded
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(["status" => "error", "message" => "No file uploaded or upload error"]);
    exit;
}

// Define the upload directory
$uploadDir = '../uploads/chat_files/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Generate a unique file name
$fileName = uniqid() . '_' . basename($_FILES['file']['name']);
$filePath = $uploadDir . $fileName;

// Move the uploaded file to the upload directory
if (move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
    echo json_encode([
        "status" => "success",
        "file_path" => $filePath,
        "file_name" => $_FILES['file']['name'],
        "file_type" => $_FILES['file']['type']
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to move uploaded file"]);
}
?>  