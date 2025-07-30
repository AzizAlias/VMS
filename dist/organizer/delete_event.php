<?php
session_start();
// Include the config file
require_once '../volunteer/config.php';

// Check if the user is logged in as an organizer
if (!isset($_SESSION['organizer_id'])) {
    header("Location: log_in_organizer.php");
    exit();
}


// Check if event_id is provided
if (!isset($_GET['event_id'])) {
    die("Event ID not specified.");
}

$event_id = $_GET['event_id'];

// Begin transaction
$conn->begin_transaction();

try {
    // Step 1: Delete volunteers registered for shifts associated with this event
    $delete_shift_volunteers_query = "
        DELETE FROM shift_registration 
        WHERE shift_id IN (SELECT shift_id FROM shift WHERE event_id = ?)
    ";
    $stmt = $conn->prepare($delete_shift_volunteers_query);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();

    // Step 2: Delete shifts related to this event
    $delete_shifts_query = "DELETE FROM shift WHERE event_id = ?";
    $stmt = $conn->prepare($delete_shifts_query);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();

    // Step 3: Delete volunteers registered for this event
    $delete_volunteers_query = "DELETE FROM event_volunteers WHERE event_id = ?";
    $stmt = $conn->prepare($delete_volunteers_query);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();

    // Step 4: Delete the event itself
    $delete_event_query = "DELETE FROM event_management WHERE event_id = ?";
    $stmt = $conn->prepare($delete_event_query);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();

    // Commit the transaction
    $conn->commit();

    // Redirect to manage events page after deletion
    header("Location: manage_event.php?success=Event deleted successfully");
    exit();
} catch (Exception $e) {
    // Rollback transaction if any error occurs
    $conn->rollback();
    die("Error deleting event: " . $e->getMessage());
}

// Close database connection
$conn->close();
?>
