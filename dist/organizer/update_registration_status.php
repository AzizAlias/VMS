<?php
session_start();
// Include the config file
require_once '../volunteer/config.php';

// Check if the user is logged in as an organizer
if (!isset($_SESSION['organizer_id'])) {
    header("Location: log_in_organizer.php");
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $registration_id = intval($_POST['event_volunteer_id']);
    $event_id = intval($_POST['event_id']);
    $action = $_POST['action'];

    if ($action === 'APPROVE') {
        $status = 'APPROVED';

        // Update registration status
        $update_status_query = "UPDATE event_volunteers SET status = ? WHERE event_volunteer_id = ?";
        $update_stmt = $conn->prepare($update_status_query);
        $update_stmt->bind_param("si", $status, $registration_id);

        if ($update_stmt->execute()) {
            // Debug log for status update success
            error_log("Registration status updated to APPROVED for event_volunteer_id: $registration_id");

            // Decrease event quota
            $update_quota_query = "UPDATE event_management SET event_quota = event_quota - 1 WHERE event_id = ? AND event_quota > 0";
            $update_quota_stmt = $conn->prepare($update_quota_query);
            $update_quota_stmt->bind_param("i", $event_id);

            if ($update_quota_stmt->execute() && $update_quota_stmt->affected_rows > 0) {
                // Debug log for successful quota update
                error_log("Event quota decremented for event_id: $event_id");
                header("Location: manage_registrations.php?success=approved");
            } else {
                // Debug log for quota update failure
                error_log("Failed to decrement quota for event_id: $event_id. Possible reasons: quota already 0 or query error.");
                header("Location: manage_registrations.php?error=quota_update_failed");
            }
        } else {
            error_log("Error updating status for event_volunteer_id: $registration_id");
            header("Location: manage_registrations.php?error=status_update_failed");
        }
    } elseif ($action === 'REJECT' || $action === 'UNREGISTER') {
        $status = ($action === 'REJECT') ? 'REJECTED' : 'PENDING';

        $update_status_query = "UPDATE event_volunteers SET status = ? WHERE event_volunteer_id = ?";
        $update_stmt = $conn->prepare($update_status_query);
        $update_stmt->bind_param("si", $status, $registration_id);

        if ($update_stmt->execute()) {
            header("Location: manage_registrations.php?success=status_updated");
        } else {
            header("Location: manage_registrations.php?error=status_update_failed");
        }
    }
}

$conn->close();
?>
