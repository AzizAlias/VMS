<?php
session_start();

require_once 'config.php';

// Check if the user is logged in as a volunteer
if (!isset($_SESSION['volunteer_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = intval($_POST['event_id']);
    $volunteer_id = $_SESSION['volunteer_id'];

    // Check if the volunteer is already registered for the event
    $check_query = "SELECT * FROM event_volunteers WHERE volunteer_id = $volunteer_id AND event_id = $event_id";
    $check_result = $conn->query($check_query);

    if ($check_result->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Already registered']);
    } else {
        // Check if the event requires approval
        $event_query = "SELECT require_approval, event_quota, event_quota_status FROM event_management WHERE event_id = $event_id";
        $event_result = $conn->query($event_query);
        $event = $event_result->fetch_assoc();

        if ($event) {
            // Check if there is available quota
            if ($event['event_quota'] > 0) {
                // Insert the registration record with PENDING status
                $insert_query = "INSERT INTO event_volunteers (event_id, volunteer_id, status, registration_date) 
                                 VALUES ($event_id, $volunteer_id, 'PENDING', NOW())";
                if ($conn->query($insert_query) === TRUE) {
                    // Update the event quota (reduce by 1)
                    $update_event_query = "UPDATE event_management SET event_quota = event_quota - 1 WHERE event_id = $event_id";
                    $conn->query($update_event_query);

                    // Return a success response with the status
                    echo json_encode(['status' => 'success', 'message' => 'Approval request sent', 'registration_status' => 'PENDING']);
                } else {
                    // If the registration fails
                    echo json_encode(['status' => 'error', 'message' => 'Database error']);
                }
            } else {
                // If the event quota is full
                echo json_encode(['status' => 'error', 'message' => 'Quota full']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Event not found']);
        }
    }
}
$conn->close();
?>