<?php
session_start();
// Include the config file
require_once '../volunteer/config.php';

// Only process the form if it's a POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and retrieve form inputs
    $email = $conn->real_escape_string($_POST['organizer_email']);
    $password = $_POST['organizer_password'];

    // Query to fetch organizer details
    $sql = "SELECT * FROM organizer WHERE organizer_email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Verify the password
        if (password_verify($password, $row['organizer_password'])) {
            // Start session and save user info
            $_SESSION['organizer_id'] = $row['organizer_id'];
            $_SESSION['organizer_name'] = $row['organizer_name'];

            // Redirect to the home screen after successful login
            header("Location: organizer_dashboard.php");
            exit();
        } else {
            // Invalid password
            $error_message = "Invalid email or password.";
        }
    } else {
        // No user found with this email
        $error_message = "No user found with this email.";
    }

    // Redirect back to the login page with the error message
    header("Location: log_in_organizer.php?error=" . urlencode($error_message));
    exit();

    $conn->close();
} else {
    // If it's not a POST request, redirect to the login page
    header("Location: log_in_organizer.php");
    exit();
}
?>