<?php
// Start the session
require_once '../volunteer/config.php'; // Adjust the path to your organizer config file

// Check if the organizer is logged in
if (!isset($_SESSION['organizer_id'])) {
    // Redirect to login if organizer_id is not set
    header("Location: log_in_organizer.php");
    exit();
}

// Fetch organizer details for the sidebar
$organizer_id = $_SESSION['organizer_id'];
$sidebar_query = "SELECT organizer_name, organizer_profile_picture FROM organizer WHERE organizer_id = ?";
$stmt = $conn->prepare($sidebar_query);
$stmt->bind_param("i", $organizer_id);
$stmt->execute();
$sidebar_result = $stmt->get_result();

if ($sidebar_result->num_rows > 0) {
    $sidebar_organizer = $sidebar_result->fetch_assoc();
    // Store the data in session variables
    $_SESSION['organizer_name'] = $sidebar_organizer['organizer_name'];
    $_SESSION['organizer_profile_picture'] = $sidebar_organizer['organizer_profile_picture'] ? "../uploads/organizer profile pics/" . $sidebar_organizer['organizer_profile_picture'] : "https://via.placeholder.com/150";
} else {
    die("Organizer not found.");
}

$stmt->close();

// Set variables for use in the sidebar
$organizer_name = $_SESSION['organizer_name'];
$profile_picture = $_SESSION['organizer_profile_picture'];
?>