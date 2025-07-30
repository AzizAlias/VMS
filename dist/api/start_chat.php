<?php
session_start();

if (!isset($_SESSION['volunteer_id']) && !isset($_SESSION['organizer_id'])) {
    // Determine the base URL for redirection
    $baseUrl = "../../"; // Adjust this based on your folder structure

    // Redirect to the appropriate login page
    if (strpos($_SERVER['REQUEST_URI'], 'volunteer') !== false) {
        // If the user is trying to access a volunteer page, redirect to volunteer login
        header("Location: " . $baseUrl . "dist/volunteer/log_in_volunteer.php");
    } else {
        // Otherwise, redirect to organizer login
        header("Location: " . $baseUrl . "dist/organizer/log_in_organizer.php");
    }
    exit;
}
// Database connection
require_once 'config.php';

// Get the volunteer's ID from the session
$volunteer_id = $_SESSION['volunteer_id'];

// Get the organizer ID from the request
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['organizer_id'])) {
    echo json_encode(["status" => "error", "message" => "Invalid request data"]);
    exit;
}

$organizer_id = $data['organizer_id'];

// Check if a chat already exists between the volunteer and organizer
$sql = "SELECT chat_id FROM chats WHERE volunteer_id = ? AND organizer_id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
    exit;
}

$stmt->bind_param("ii", $volunteer_id, $organizer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Chat already exists, return the chat ID
    $row = $result->fetch_assoc();
    echo json_encode(["status" => "success", "chat_id" => $row['chat_id']]);
} else {
    // Create a new chat
    $sql = "INSERT INTO chats (volunteer_id, organizer_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
        exit;
    }

    $stmt->bind_param("ii", $volunteer_id, $organizer_id);

    if ($stmt->execute()) {
        // Return the new chat ID
        echo json_encode(["status" => "success", "chat_id" => $stmt->insert_id]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to start chat: " . $stmt->error]);
    }
}

$stmt->close();
$conn->close();
?>