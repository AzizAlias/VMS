<?php
session_start();
require_once '../volunteer/config.php';

// Check if the user is logged in as an organizer
if (!isset($_SESSION['organizer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

// Get the volunteer ID and event ID from the query string
$volunteer_id = $_GET['volunteer_id'] ?? null;
$event_id = $_GET['event_id'] ?? null;

if (!$volunteer_id || !$event_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

// Check if the invitation already exists
$check_query = "SELECT * FROM event_invitations WHERE event_id = ? AND volunteer_id = ?";
if ($stmt = $conn->prepare($check_query)) {
    $stmt->bind_param("ii", $event_id, $volunteer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Invitation already sent.']);
        exit();
    }
    $stmt->close();
}

// Insert the new invitation
$insert_query = "INSERT INTO event_invitations (event_id, volunteer_id, status) VALUES (?, ?, 'pending')";
if ($stmt = $conn->prepare($insert_query)) {
    $stmt->bind_param("ii", $event_id, $volunteer_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Invitation sent successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send invitation.']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}

$conn->close();
?>