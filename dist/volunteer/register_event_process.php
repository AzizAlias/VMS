<?php
session_start();

require_once 'config.php';
// Check if the user is logged in as a volunteer
if (!isset($_SESSION['volunteer_id'])) {
    header("Location: log_in_volunteer.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = intval($_POST['event_id']);
    $volunteer_id = $_SESSION['volunteer_id'];

    // Check if the volunteer is already registered for the event
    $check_query = "SELECT * FROM event_volunteers WHERE volunteer_id = $volunteer_id AND event_id = $event_id";
    $check_result = $conn->query($check_query);

    if ($check_result->num_rows > 0) {
        // Redirect back with an error message
        header("Location: view_event.php?error=already_registered");
    } else {
        // Check if the event requires approval
        $event_query = "SELECT require_approval, event_quota, event_quota_status FROM event_management WHERE event_id = $event_id";
        $event_result = $conn->query($event_query);
        $event = $event_result->fetch_assoc();

        if ($event) {
            // Check if there is available quota
            if ($event['event_quota'] > 0) {
                // If the event does not require approval, automatically approve the registration
                $status = $event['require_approval'] ? 'PENDING' : 'APPROVED';

                // Insert the registration record
                $insert_query = "INSERT INTO event_volunteers (event_id, volunteer_id, status, registration_date) 
                                 VALUES ($event_id, $volunteer_id, '$status', NOW())";
                if ($conn->query($insert_query) === TRUE) {
                    // Update the event quota (reduce by 1)
                    $update_event_query = "UPDATE event_management SET event_quota = event_quota - 1 WHERE event_id = $event_id";
                    $conn->query($update_event_query);

                    // If registration was approved, we redirect to the event page with a success message
                    if ($status === 'APPROVED') {
                        header("Location: view_event.php?success=registered");
                    } else {
                        // If the status is PENDING, we redirect with a request sent message
                        header("Location: view_event.php?success=request_sent");
                    }
                } else {
                    // If the registration fails
                    header("Location: view_event.php?error=database_error");
                }
            } else {
                // If the event quota is full
                header("Location: view_event.php?error=quota_full");
            }
        } else {
            header("Location: view_event.php?error=event_not_found");
        }
    }
}
$conn->close();
?>
