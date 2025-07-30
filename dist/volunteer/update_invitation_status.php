<?php
session_start();
require_once 'config.php';

// Check if the user is logged in as a volunteer
if (!isset($_SESSION['volunteer_id'])) {
    header("Location: log_in_volunteer.php");
    exit();
}

// Get invitation_id and status from URL
if (isset($_GET['invitation_id']) && isset($_GET['status'])) {
    $invitation_id = $_GET['invitation_id'];
    $status = strtolower($_GET['status']); // Normalize to lowercase
    $volunteer_id = $_SESSION['volunteer_id']; // Get the volunteer ID from the session

    // Validate status
    if (in_array($status, ['accepted', 'declined'])) {
        // Update the invitation's status
        $query = "UPDATE event_invitations SET status = '$status' WHERE invitation_id = '$invitation_id'";

        if ($conn->query($query) === TRUE) {
            if ($status == 'accepted') {
                // Fetch the event ID from the invitation
                $event_query = "SELECT event_id FROM event_invitations WHERE invitation_id = '$invitation_id'";
                $result = $conn->query($event_query);

                if ($result && $result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $event_id = $row['event_id'];

                    // Check if the volunteer is already registered for the event
                    $check_query = "SELECT * FROM event_volunteers WHERE event_id = '$event_id' AND volunteer_id = '$volunteer_id'";
                    $check_result = $conn->query($check_query);

                    if ($check_result && $check_result->num_rows == 0) {
                        // Insert into event_volunteers table
                        $register_query = "INSERT INTO event_volunteers (event_id, volunteer_id, registration_date, status)
                                           VALUES ('$event_id', '$volunteer_id', NOW(), 'registered')";

                        if ($conn->query($register_query) === TRUE) {
                            // Decrease the event quota in event_management table
                            $update_quota_query = "UPDATE event_management 
                                                   SET event_quota = event_quota - 1 
                                                   WHERE event_id = '$event_id' AND event_quota > 0";

                            if ($conn->query($update_quota_query) === TRUE) {
                                $message = "Invitation accepted, you are now registered for the event, and the quota has been updated.";
                            } else {
                                $message = "Error updating event quota: " . $conn->error;
                            }
                        } else {
                            $message = "Error registering for the event: " . $conn->error;
                        }
                    } else {
                        $message = "You are already registered for this event.";
                    }
                } else {
                    $message = "Event not found for this invitation.";
                }
            } else {
                $message = "Invitation declined.";
            }
        } else {
            $message = "Error updating invitation status: " . $conn->error;
        }
    } else {
        $message = "Invalid status.";
    }
} else {
    $message = "Invalid request.";
}

// Redirect back to the manage invitations page
header("Location: view_event_invitations.php?message=" . urlencode($message));
exit();

$conn->close();
?>