<?php
// Start the session


// Include the database configuration
require_once 'config.php';

// Check if the volunteer is logged in
if (!isset($_SESSION['volunteer_id'])) {
    // Redirect to login if volunteer_id is not set
    header("Location: log_in_volunteer.php");
    exit();
}

// Fetch volunteer details for the sidebar
$volunteer_id = $_SESSION['volunteer_id'];
$sidebar_query = "SELECT volunteer_name, volunteer_profile_pic FROM volunteer WHERE volunteer_id = ?";
$stmt = $conn->prepare($sidebar_query);
$stmt->bind_param("i", $volunteer_id);
$stmt->execute();
$sidebar_result = $stmt->get_result();

if ($sidebar_result->num_rows > 0) {
    $sidebar_volunteer = $sidebar_result->fetch_assoc();
    // Store the data in session variables
    $_SESSION['volunteer_name'] = $sidebar_volunteer['volunteer_name'];
    $_SESSION['volunteer_profile_pic'] = $sidebar_volunteer['volunteer_profile_pic'] ? "../uploads/volunteer profile pics/" . $sidebar_volunteer['volunteer_profile_pic'] : "https://via.placeholder.com/150";
} else {
    die("Volunteer not found.");
}

$stmt->close();

// Set variables for use in the sidebar
$volunteer_name = $_SESSION['volunteer_name'];
$profile_picture = $_SESSION['volunteer_profile_pic'];
?>