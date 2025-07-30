<?php
session_start();
// Include the config file
require_once 'config.php';

// Validate reCAPTCHA
if (isset($_POST['g-recaptcha-response'])) {
    $recaptchaResponse = $_POST['g-recaptcha-response'];
    $secretKey = "6Ldte78qAAAAAAAmuEGQ16r_JELsMGDh7rcIUhNH"; // Replace with your reCAPTCHA Secret Key
    $ip = $_SERVER['REMOTE_ADDR'];

    // Verify reCAPTCHA response with Google
    $url = "https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$recaptchaResponse&remoteip=$ip";
    $response = file_get_contents($url);
    $responseKeys = json_decode($response, true);

    if (!$responseKeys["success"]) {
        // reCAPTCHA validation failed
        echo '<script>
                alert("reCAPTCHA verification failed. Please try again.");
                window.location.href = "signup_volunteer.php";  // Redirect back to the sign-up page
              </script>';
        exit();
    }
} else {
    // reCAPTCHA response missing
    echo '<script>
            alert("reCAPTCHA response is missing. Please complete the reCAPTCHA.");
            window.location.href = "signup_volunteer.php";  // Redirect back to the sign-up page
          </script>';
    exit();
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get form input and sanitize it
    $name = $conn->real_escape_string($_POST['volunteer_name']);
    $username = $conn->real_escape_string($_POST['volunteer_username']);
    $email = $conn->real_escape_string($_POST['volunteer_email']);
    $password = $conn->real_escape_string($_POST['volunteer_password']);
    $phoneNumber = $conn->real_escape_string($_POST['volunteer_phone_number']);
    $volunteerDOB = $conn->real_escape_string($_POST['volunteer_DOB']);
    $volunteerAddress = $conn->real_escape_string($_POST['volunteer_address']);

    // Handle profile picture upload
    $volunteer_profile_pic = "";
    if (isset($_FILES['volunteer_profile_pic']) && $_FILES['volunteer_profile_pic']['error'] == 0) {
        $upload_dir = "../uploads/volunteer profile pics/";

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Get file information
        $file_tmp = $_FILES['volunteer_profile_pic']['tmp_name'];
        $file_name = uniqid() . "_" . basename($_FILES['volunteer_profile_pic']['name']);
        $file_path = $upload_dir . $file_name;

        // Move uploaded file to the specified directory
        if (move_uploaded_file($file_tmp, $file_path)) {
            $volunteer_profile_pic = $file_name; // Store the file name in the database, not the full path
        } else {
            echo '<script>
                    alert("Error uploading the file.");
                    window.location.href = "signup_volunteer.php";  // Redirect back to the sign-up page
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
            $volunteer_profile_pic = $croppedImageName; // Use the cropped image instead of the original
        }
    }

    // Password Hashing for security
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Handle availability dates
    $availability = "";
    if (isset($_POST['volunteer_availability']) && is_array($_POST['volunteer_availability'])) {
        // Join the dates into a comma-separated string
        $availability = implode(',', array_map([$conn, 'real_escape_string'], $_POST['volunteer_availability']));
    }

    // Handle skills
    $skills = "";
    if (isset($_POST['volunteer_skills']) && is_array($_POST['volunteer_skills'])) {
        // Join the skills into a comma-separated string
        $skills = implode(',', array_map([$conn, 'real_escape_string'], $_POST['volunteer_skills']));
    }

    // Insert data into the database
    $sql = "INSERT INTO volunteer (volunteer_name, volunteer_username, volunteer_email, volunteer_password, volunteer_phone_number, volunteer_DOB, volunteer_address, volunteer_profile_pic, volunteer_availability, volunteer_skills) 
            VALUES ('$name', '$username', '$email', '$hashed_password', '$phoneNumber', '$volunteerDOB', '$volunteerAddress', '$volunteer_profile_pic', '$availability', '$skills')";

    if ($conn->query($sql) === TRUE) {
        // Redirect after displaying success message
        echo '<script>
                alert("You have successfully registered to the system.");
                window.location.href = "log_in_volunteer.php";  // Replace with the actual login page path
              </script>';
        exit();  // Ensure no further code is executed
    } else {
        echo '<script>
                alert("Error: ' . $conn->error . '");
                window.location.href = "signup_volunteer.php";  // Redirect back to the sign-up page
              </script>';
    }

    $conn->close();
}
?>