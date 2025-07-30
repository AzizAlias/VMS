<?php
session_start();
// Include the config file
require_once 'config.php';

// Check if the user is logged in as a volunteer
if (!isset($_SESSION['volunteer_id'])) {
    header("Location: log_in_volunteer.php");
    exit();
}


// Get volunteer id
$volunteer_id = $_SESSION['volunteer_id'];

// Get shift_id and event_id from POST
if (isset($_POST['shift_id']) && isset($_POST['event_id'])) {
    $shift_id = $_POST['shift_id'];
    $event_id = $_POST['event_id'];

    // Ensure volunteer exists
    $volunteer_check = "SELECT * FROM volunteer WHERE volunteer_id = ?";
    $volunteer_stmt = $conn->prepare($volunteer_check);
    $volunteer_stmt->bind_param("i", $volunteer_id);
    $volunteer_stmt->execute();
    $volunteer_result = $volunteer_stmt->get_result();

    if ($volunteer_result->num_rows === 0) {
        echo "<script>alert('Volunteer not found.'); window.location.href = 'view_event_shift.php?event_id=" . $event_id . "';</script>";
        exit();
    }

    // Check if the register or unregister action was triggered
    if (isset($_POST['register_shift'])) {
        // **Check if already registered**
        $check_registration_query = "SELECT * FROM shift_registration WHERE shift_id = ? AND volunteer_id = ?";
        $check_stmt = $conn->prepare($check_registration_query);
        $check_stmt->bind_param("ii", $shift_id, $volunteer_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            echo "<script>alert('You are already registered for this shift.'); window.location.href = 'view_event_shift.php?event_id=" . $event_id . "';</script>";
            exit();
        }

        // Register for the shift
        $register_query = "INSERT INTO shift_registration (shift_id, volunteer_id) VALUES (?, ?)";
        $stmt = $conn->prepare($register_query);
        $stmt->bind_param("ii", $shift_id, $volunteer_id);
        $stmt->execute();

        // Update current registrations in the shift table
        $update_query = "UPDATE shift SET current_registrations = current_registrations + 1 WHERE shift_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("i", $shift_id);
        $stmt->execute();

        echo "<script>alert('You have successfully registered for this shift.'); window.location.href = 'view_event_shift.php?event_id=" . $event_id . "';</script>";
    } elseif (isset($_POST['unregister_shift'])) {
        // Unregister from the shift
        $unregister_query = "DELETE FROM shift_registration WHERE volunteer_id = ? AND shift_id = ?";
        $stmt = $conn->prepare($unregister_query);
        $stmt->bind_param("ii", $volunteer_id, $shift_id);
        $stmt->execute();

        // Decrement the current registrations in the shift table
        $update_query = "UPDATE shift SET current_registrations = current_registrations - 1 WHERE shift_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("i", $shift_id);
        $stmt->execute();

        echo "<script>alert('You have successfully unregistered from this shift.'); window.location.href = 'view_event_shift.php?event_id=" . $event_id . "';</script>";
    }
}

// Close database connection
$conn->close();
?>
