<?php
session_start();
// Include the config file
require_once '../volunteer/config.php';

// Check if the user is logged in as an organizer
if (!isset($_SESSION['organizer_id'])) {
    header("Location: log_in_organizer.php");
    exit();
}




// Get the logged-in organizer's ID
$organizer_id = $_SESSION['organizer_id']; // Assuming the organizer's ID is stored in the session

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $event_id = $_POST['event_id'];
    $shift_start_date = $_POST['shift_start_date'];
    $shift_end_date = $_POST['shift_end_date'];
    $shift_start_time = $_POST['shift_start_time'];
    $shift_end_time = $_POST['shift_end_time'];
    $shift_location = $_POST['shift_location'];
    $shift_quota = $_POST['shift_quota'];

    // Fetch the event's total quota
    $event_query = "SELECT event_quota FROM event_management WHERE event_id = ?";
    $stmt = $conn->prepare($event_query);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $stmt->bind_result($event_quota);
    $stmt->fetch();
    $stmt->close();

    // Fetch the total quota used by existing shifts
    $total_shift_quota_query = "SELECT SUM(shift_quota) FROM shift WHERE event_id = ?";
    $stmt = $conn->prepare($total_shift_quota_query);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $stmt->bind_result($total_shift_quota_used);
    $stmt->fetch();
    $stmt->close();

    
    $remaining_quota = $event_quota - $total_shift_quota_used;

    // Check if the remaining quota is enough for the new shift
    if ($remaining_quota >= $shift_quota) {
        // Insert shift into the database (including organizer_id)
        $shift_insert_query = "INSERT INTO shift (event_id, organizer_id, shift_start_date, shift_end_date, shift_start_time, shift_end_time, shift_location, shift_quota) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($shift_insert_query);
        $stmt->bind_param("iisssssi", $event_id, $organizer_id, $shift_start_date, $shift_end_date, $shift_start_time, $shift_end_time, $shift_location, $shift_quota);

        if ($stmt->execute()) {
            echo "<script>alert('Shift added successfully.'); window.location.href = 'add_event_shift.php';</script>";
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        
        echo "<script>alert('The shift quota exceeds the remaining available quota for this event.'); window.location.href = 'add_event_shift.php';</script>";
    }
}

$conn->close();
?>
