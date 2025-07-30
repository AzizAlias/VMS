<?php
session_start();
// Include the config file
require_once 'config.php';

// Check if the user is logged in as a volunteer
if (!isset($_SESSION['volunteer_id'])) {
    header("Location: log_in_volunteer.php");
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = $_POST['event_id'];
    $volunteer_id = $_SESSION['volunteer_id']; // Ensure session is active
    $rating = $_POST['rating'];
    $comments = $_POST['comments'];

    // Insert or update feedback
    $query = "
        INSERT INTO feedback (event_id, volunteer_id, rating, comments)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            rating = VALUES(rating),
            comments = VALUES(comments),
            updated_at = NOW()
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiis", $event_id, $volunteer_id, $rating, $comments);

    if ($stmt->execute()) {
        // Set a session variable to indicate successful submission
        $_SESSION['feedback_submitted'] = true;
        header("Location: event_feedback.php");
        exit();
    } else {
        header("Location: event_feedback.php?error=1");
        exit();
    }
}