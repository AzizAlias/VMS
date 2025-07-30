<?php
session_start();
require_once '../volunteer/config.php';

// Check if the organizer is logged in
if (!isset($_SESSION['organizer_id'])) {
    header("Location: ../organizer/log_in_organizer.php");
    exit;
}

// Check if volunteer_id is provided
if (!isset($_GET['volunteer_id'])) {
    die("Volunteer ID not specified.");
}

$organizer_id = $_SESSION['organizer_id'];
$volunteer_id = $_GET['volunteer_id'];

// Check if a chat already exists between the organizer and volunteer
$sql = "SELECT chat_id FROM chats WHERE organizer_id = ? AND volunteer_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $organizer_id, $volunteer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Chat already exists, redirect to the chat
    $row = $result->fetch_assoc();
    header("Location: ../organizer/organizer_chat.php?chat_id=" . $row['chat_id']);
    exit;
} else {
    // Create a new chat
    $sql = "INSERT INTO chats (organizer_id, volunteer_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $organizer_id, $volunteer_id);

    if ($stmt->execute()) {
        // Redirect to the new chat
        header("Location: ../organizer/organizer_chat.php?chat_id=" . $stmt->insert_id);
        exit;
    } else {
        die("Failed to start chat: " . $stmt->error);
    }
}
?>