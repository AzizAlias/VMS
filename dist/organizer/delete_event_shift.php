<?php
session_start();
// Include the config file
require_once '../volunteer/config.php';

// Check if the user is logged in as an organizer
if (!isset($_SESSION['organizer_id'])) {
    header("Location: log_in_organizer.php");
    exit();
}



if (isset($_GET['shift_id'])) {
    $shift_id = intval($_GET['shift_id']);
    $delete_query = "DELETE FROM shift WHERE shift_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $shift_id);

    if ($stmt->execute()) {
        header("Location: manage_shift.php?delete_success=1");
    } else {
        header("Location: manage_shift.php?delete_success=0");
    }
    $stmt->close();
    exit();
}

$conn->close();
?>
