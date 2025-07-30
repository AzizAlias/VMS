<?php
session_start();
// Include the config file
require_once '../volunteer/config.php';

// Check if the user is logged in as an organizer
if (!isset($_SESSION['organizer_id'])) {
    header("Location: log_in_organizer.php");
    exit();
}

// Get form data
$organizer_id = $_SESSION['organizer_id'];
$event_name = $_POST['event_name'];
$event_description = $_POST['event_description'];
$event_date = $_POST['event_date'];
$event_quota_status = $_POST['event_quota_status'];
$event_status = $_POST['event_status'];
$event_quota = $_POST['event_quota'];
$event_location = $_POST['event_location'];
$category_id = $_POST['category_id'];
$required_skills = $_POST['required_skills'];
$registration_approval = $_POST['require_approval'];

// Convert the skills array into a comma-separated string
$required_skills_string = implode(",", $required_skills);

// Handle the event poster upload
$event_poster = null;
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
}

// Prepare and execute the SQL query
$sql = "INSERT INTO event_management (event_name, event_description, event_date, event_quota_status, event_status, event_quota, event_location, category_id, required_skills, event_poster, organizer_id, require_approval)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "sssssisissii",
    $event_name,
    $event_description,
    $event_date,
    $event_quota_status,
    $event_status,
    $event_quota,
    $event_location,
    $category_id,
    $required_skills_string, // Use the string here
    $event_poster,
    $organizer_id,
    $registration_approval
);

if ($stmt->execute()) {
    // Event successfully added
    echo "<script>
            alert('Event successfully registered!');
            window.location.href = 'post_event.php';
          </script>";
} else {
    // Error occurred
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>