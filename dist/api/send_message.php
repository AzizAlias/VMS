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

// Get the sender type and ID
if (isset($_SESSION['volunteer_id'])) {
    $sender_type = 'volunteer';
    $sender_id = $_SESSION['volunteer_id'];
} else {
    $sender_type = 'organizer';
    $sender_id = $_SESSION['organizer_id'];
}

// Get the chat ID and message text from the request
$chat_id = $_POST['chat_id'];
$message_text = $_POST['message_text'];

// Insert the message into the database
$sql = "INSERT INTO messages (chat_id, sender_type, sender_id, message_text) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("isss", $chat_id, $sender_type, $sender_id, $message_text);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message_id" => $stmt->insert_id]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to send message"]);
}

$stmt->close();
$conn->close();
?>