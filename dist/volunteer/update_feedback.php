<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = $_POST['event_id'];
    $rating = $_POST['rating'];
    $comments = $_POST['comments'];
    $volunteer_id = $_SESSION['volunteer_id'];

    // Update feedback in the database
    $query = "UPDATE feedback SET rating = ?, comments = ? WHERE event_id = ? AND volunteer_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isii", $rating, $comments, $event_id, $volunteer_id);
    $stmt->execute();
    $stmt->close();

    $_SESSION['feedback_submitted'] = true;
    header("Location: event_feedback.php");
    exit();
}
?>