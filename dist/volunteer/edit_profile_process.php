<?php
session_start();
// Include the config file
require_once 'config.php';

// Check if the user is logged in as a volunteer
if (!isset($_SESSION['volunteer_id'])) {
    header("Location: log_in_volunteer.php");
    exit();
}

// Fetch the volunteer's current profile information
$volunteer_id = $_SESSION['volunteer_id'];
$sql = "SELECT * FROM volunteer WHERE volunteer_id = $volunteer_id";
$result = $conn->query($sql);
$volunteer = $result->fetch_assoc();

// Handle form submission (update profile)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input
    $name = $conn->real_escape_string($_POST['volunteer_name']);
    $email = $conn->real_escape_string($_POST['volunteer_email']);
    $phone = $conn->real_escape_string($_POST['volunteer_phone_number']);
    $dob = $conn->real_escape_string($_POST['volunteer_DOB']);
    $address = $conn->real_escape_string($_POST['volunteer_address']);

    $volunteer_profile_pic = $volunteer['volunteer_profile_pic']; // Keep current picture by default

    $upload_dir = "../uploads/volunteer profile pics/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    if (isset($_FILES['volunteer_profile_pic']) && $_FILES['volunteer_profile_pic']['error'] == 0) {
        // Delete the old profile picture
        if (!empty($volunteer['volunteer_profile_pic'])) {
            $old_file = $upload_dir . $volunteer['volunteer_profile_pic'];
            if (file_exists($old_file)) {
                unlink($old_file);
            }
        }

        // Generate a unique file name
        $file_tmp = $_FILES['volunteer_profile_pic']['tmp_name'];
        $file_name = uniqid() . "_" . basename($_FILES['volunteer_profile_pic']['name']);
        $file_path = $upload_dir . $file_name;

        // Move the uploaded file
        if (move_uploaded_file($file_tmp, $file_path)) {
            $volunteer_profile_pic = $file_name;
        } else {
            echo "<script>alert('File upload failed. Please check permissions.');</script>";
            exit();
        }
    }

    // Process availability data from checkboxes
    $availability = isset($_POST['volunteer_availability']) ? implode(',', $_POST['volunteer_availability']) : '';
    $skills = isset($_POST['volunteer_skills']) ? implode(',', $_POST['volunteer_skills']) : '';

    // Update the volunteer profile in the database
    $update_sql = "UPDATE volunteer 
                   SET volunteer_name = '$name', 
                       volunteer_email = '$email', 
                       volunteer_phone_number = '$phone', 
                       volunteer_DOB = '$dob', 
                       volunteer_address = '$address', 
                       volunteer_profile_pic = '$volunteer_profile_pic', 
                       volunteer_availability = '$availability',
                       volunteer_skills = '$skills' 
                   WHERE volunteer_id = $volunteer_id";

    if ($conn->query($update_sql) === TRUE) {
        // Show success popup using JavaScript and redirect to profile management page
        echo "<script>
                alert('Your information has been successfully updated!');
                window.location.href = 'edit_profile.php';
              </script>";
        exit();
    } else {
        echo "<script>alert('Error updating profile: " . $conn->error . "');</script>";
    }
}

$conn->close();
?>