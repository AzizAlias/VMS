<?php
session_start();

// Check if the user is logged in (organizer or volunteer)
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
require_once 'config.php'; // Adjust the path as needed

// Get the chat ID from the request
if (!isset($_GET['chat_id'])) {
    echo json_encode(["status" => "error", "message" => "Chat ID is missing"]);
    exit;
}

$chat_id = $_GET['chat_id'];

// Fetch all messages for the chat
$sql = "SELECT * FROM messages WHERE chat_id = ? ORDER BY sent_at ASC";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
    exit;
}

$stmt->bind_param("i", $chat_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

echo json_encode(["status" => "success", "messages" => $messages]);

$stmt->close();
$conn->close();
?>