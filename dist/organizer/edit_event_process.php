<?php
session_start();
// Include the config file
require_once '../volunteer/config.php';

// Check if the user is logged in as an organizer
if (!isset($_SESSION['organizer_id'])) {
    header("Location: log_in_organizer.php");
    exit();
}

// Get the organizer_id from the session
$organizer_id = $_SESSION['organizer_id'];

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the event details from the form
    $event_id = $_POST['event_id'];
    $event_name = $_POST['event_name'];
    $event_description = $_POST['event_description'];
    $event_date = $_POST['event_date'];
    $event_location = $_POST['event_location'];
    $event_quota = $_POST['event_quota'];
    $event_status = $_POST['event_status'];
    $event_quota_status = $_POST['event_quota_status'];

    // Handle the event poster upload
    if (isset($_FILES['event_poster']) && $_FILES['event_poster']['error'] == 0) {
        $event_poster = $_FILES['event_poster']['name'];
        $target_dir = "../uploads/event poster/"; // Adjust the path as needed
        $target_file = $target_dir . basename($event_poster);
        
        // Move the uploaded file to the desired directory
        if (move_uploaded_file($_FILES['event_poster']['tmp_name'], $target_file)) {
            // File uploaded successfully
        } else {
            echo "Error uploading event poster.";
            exit();
        }
    } else {
        // If no new poster uploaded, keep the existing one
        $event_poster = $_POST['existing_poster'];
    }

    // Handle the required skills (convert array to comma-separated string)
    if (isset($_POST['required_skills']) && !empty($_POST['required_skills'])) {
        $required_skills = implode(", ", $_POST['required_skills']);
    } else {
        $required_skills = ""; // If no skills are provided
    }

    // Update the event in the database
    $query = "UPDATE event_management SET 
              event_name = ?, 
              event_description = ?, 
              event_date = ?, 
              event_location = ?, 
              event_quota = ?, 
              event_status = ?, 
              event_quota_status = ?, 
              event_poster = ?, 
              required_skills = ? 
              WHERE event_id = ? AND organizer_id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssisssssi", $event_name, $event_description, $event_date, $event_location, $event_quota, 
                     $event_status, $event_quota_status, $event_poster, $required_skills, $event_id, $organizer_id);
    
    if ($stmt->execute()) {
        // Redirect back to the event details or the event list page after successful update
        header("Location: edit_event.php?event_id=$event_id");
        exit();
    } else {
        echo "Error updating event: " . $stmt->error;
    }
    $stmt->close();
}
$conn->close();
?>