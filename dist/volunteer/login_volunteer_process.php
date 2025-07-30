<?php
session_start();
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login_input = $conn->real_escape_string($_POST['login_input']); // Can be email or username
    $password = $_POST['volunteer_password'];

    // Prepare the query to check if the login input is an email or username
    $sql = "SELECT * FROM volunteer WHERE volunteer_email = '$login_input' OR volunteer_username = '$login_input'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Verify the password
        if (password_verify($password, $row['volunteer_password'])) {
            // Start session and save user info
            $_SESSION['volunteer_id'] = $row['volunteer_id'];
            $_SESSION['volunteer_name'] = $row['volunteer_name'];

            // Redirect to the home screen after successful login
            header("Location: volunteer_dashboard.php");
            exit();
        } else {
            $error_message = "Invalid username/email or password.";
        }
    } else {
        $error_message = "No user found with this username/email.";
    }

    // Redirect back to the login page with the error message
    header("Location: log_in_volunteer.php?error=" . urlencode($error_message));
    exit();
}

$conn->close();
?>