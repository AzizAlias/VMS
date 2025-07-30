<?php
session_start();
// Include the config file
require_once '../volunteer/config.php';

// Validate reCAPTCHA
if (isset($_POST['g-recaptcha-response'])) {
    $recaptchaResponse = $_POST['g-recaptcha-response'];
    $secretKey = "6LdFlL8qAAAAAEbDjEJt_Qrl9nhrp8z9aVINK5g5"; // Replace with your reCAPTCHA Secret Key
    $ip = $_SERVER['REMOTE_ADDR'];

    // Verify reCAPTCHA response with Google
    $url = "https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$recaptchaResponse&remoteip=$ip";
    $response = file_get_contents($url);
    $responseKeys = json_decode($response, true);

    if (!$responseKeys["success"]) {
        // reCAPTCHA validation failed
        echo '<script>
                alert("reCAPTCHA verification failed. Please try again.");
                window.location.href = "signup_organizer.php";  // Redirect back to the sign-up page
              </script>';
        exit();
    }
} else {
    // reCAPTCHA response missing
    echo '<script>
            alert("reCAPTCHA response is missing. Please complete the reCAPTCHA.");
            window.location.href = "signup_organizer.php";  // Redirect back to the sign-up page
          </script>';
    exit();
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form input and sanitize it
    $name = $conn->real_escape_string($_POST['organizer_name']);
    $email = $conn->real_escape_string($_POST['organizer_email']);
    $password = $conn->real_escape_string($_POST['organizer_password']);
    $phoneNumber = $conn->real_escape_string($_POST['organizer_phone_number']);
    $organization = $conn->real_escape_string($_POST['organizer_department']);

    // Password Hashing for security
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Handle profile picture upload
    $organizer_profile_pic = ""; // Default value if no picture is uploaded
    if (isset($_FILES['organizer_profile_pic']) && $_FILES['organizer_profile_pic']['error'] == 0) {
        $upload_dir = "../uploads/organizer profile pics/"; // Directory for organizer profile pictures

        // Create the directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Get file information
        $file_tmp = $_FILES['organizer_profile_pic']['tmp_name'];
        $file_name = uniqid() . "_" . basename($_FILES['organizer_profile_pic']['name']); // Unique file name
        $file_path = $upload_dir . $file_name;

        // Move uploaded file to the specified directory
        if (move_uploaded_file($file_tmp, $file_path)) {
            $organizer_profile_pic = $file_name; // Store the file name in the database, not the full path
        } else {
            echo '<script>
                    alert("Error uploading profile picture. Please try again.");
                    window.location.href = "signup_organizer.php";  // Redirect back to the sign-up page
                  </script>';
            exit();
        }
    }

    // Handle cropped image (if available)
    if (isset($_POST['cropped_image']) && !empty($_POST['cropped_image'])) {
        $croppedImageData = $_POST['cropped_image'];
        $croppedImageName = uniqid() . "_cropped.jpg";
        $croppedImagePath = $upload_dir . $croppedImageName;

        // Save the cropped image to the server
        if (file_put_contents($croppedImagePath, base64_decode(explode(',', $croppedImageData)[1]))) {
            $organizer_profile_pic = $croppedImageName; // Use the cropped image instead of the original
        }
    }

    // Insert organizer data into the database
    $sql = "INSERT INTO organizer (organizer_name, organizer_email, organizer_password, organizer_phone_number, organizer_department, organizer_profile_picture) 
            VALUES ('$name', '$email', '$hashed_password', '$phoneNumber', '$organization', '$organizer_profile_pic')";

    if ($conn->query($sql) === TRUE) {
        // Use JavaScript for popup and redirection
        echo "<script>
                alert('You have successfully registered!');
                window.location.href = 'log_in_organizer.php'; 
              </script>";
    } else {
        echo '<script>
                alert("Error: ' . $conn->error . '");
                window.location.href = "signup_organizer.php";  // Redirect back to the sign-up page
              </script>';
    }

    $conn->close();
}
?>