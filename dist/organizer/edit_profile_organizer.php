<?php
session_start();
// Include the config file
require_once '../volunteer/config.php';

// Check if the user is logged in as an organizer
if (!isset($_SESSION['organizer_id'])) {
    header("Location: log_in_organizer.php");
    exit();
}

// Fetch the organizer's current profile information
$organizer_id = $_SESSION['organizer_id'];
$sql = "SELECT * FROM organizer WHERE organizer_id = $organizer_id";
$result = $conn->query($sql);
$organizer = $result->fetch_assoc();

// Handle form submission (update profile)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input
    $name = $conn->real_escape_string($_POST['organizer_name']);
    $email = $conn->real_escape_string($_POST['organizer_email']);
    $phone = $conn->real_escape_string($_POST['organizer_phone_number']);
    $department = $conn->real_escape_string($_POST['organizer_department']);

    $organizer_profile_pic = $organizer['organizer_profile_picture']; // Keep current picture by default

    $upload_dir = "../uploads/organizer profile pics/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true); // Create the directory if it doesn't exist
    }

    if (isset($_FILES['organizer_profile_picture']) && $_FILES['organizer_profile_picture']['error'] == 0) {
        // Delete the old profile picture
        if (!empty($organizer['organizer_profile_picture'])) {
            $old_file = $upload_dir . $organizer['organizer_profile_picture'];
            if (file_exists($old_file)) {
                unlink($old_file);
            }
        }

        // Generate a unique file name
        $file_tmp = $_FILES['organizer_profile_picture']['tmp_name'];
        $file_name = uniqid() . "_" . basename($_FILES['organizer_profile_picture']['name']);
        $file_path = $upload_dir . $file_name;

        // Move the uploaded file
        if (move_uploaded_file($file_tmp, $file_path)) {
            $organizer_profile_pic = $file_name;
        } else {
            echo "<script>alert('File upload failed. Please check permissions.');</script>";
            exit();
        }
    }

    // Update the organizer profile in the database
    $update_sql = "UPDATE organizer 
                   SET organizer_name = '$name', 
                       organizer_email = '$email', 
                       organizer_phone_number = '$phone', 
                       organizer_department = '$department', 
                       organizer_profile_picture = '$organizer_profile_pic'
                   WHERE organizer_id = $organizer_id";

    if ($conn->query($update_sql) === TRUE) {
        // Show success modal using Flowbite
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    const modal = document.getElementById('popup-modal');
                    modal.classList.remove('hidden');
                    modal.setAttribute('aria-hidden', 'false');
                });
              </script>";
    } else {
        echo "<script>alert('Error updating profile: " . $conn->error . "');</script>";
    }
}
include 'profile_pic_config.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organizer Profile Management</title>
    <link href="../../src/output.css" rel="stylesheet">
    <script src="../../node_modules/flowbite/dist/flowbite.min.js"></script>
    <!-- Include Cropper.js CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" rel="stylesheet">
    <style>
        /* Custom styles for the modal */
        #popup-modal {
            position: fixed; /* Position relative to the viewport */
            top: 50%; /* Center vertically */
            left: 50%; /* Center horizontally */
            transform: translate(-50%, -50%); /* Adjust for exact centering */
            z-index: 1000; /* Ensure it appears above other content */
        }

        /* Cropper.js container */
        #image-cropper-container {
            max-width: 100%;
            margin: 0 auto;
        }

        #image-cropper-container img {
            max-width: 100%;
        }
    </style>
</head>
<?php include 'organizer_sidebar.php'; ?>

<body class="bg-gray-100">
    <!-- Main Content -->
    <div class="main-content flex items-center justify-center min-h-screen"> <!-- Center the form horizontally -->
        <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-3xl relative"> <!-- Added relative positioning -->
            <h1 class="text-3xl font-semibold text-center text-gray-700 mb-6">Edit Profile</h1>

            <form action="edit_profile_organizer.php" method="POST" enctype="multipart/form-data">
                <div class="space-y-6">
                    <!-- Profile Picture Section -->
                    <div>
                        <label for="organizer_profile_picture" class="block text-sm font-medium text-gray-600">Upload Profile Picture:</label>
                        <div class="mt-2">
                            <!-- Display current profile picture if it exists -->
                            <?php if (!empty($organizer['organizer_profile_picture'])): ?>
                                <div class="flex justify-center mb-4">
                                    <img src="<?php echo "../uploads/organizer profile pics/" . htmlspecialchars($organizer['organizer_profile_picture']); ?>"
                                        alt="Current Profile Picture" 
                                        class="w-32 h-32 rounded-full object-cover border-2 border-gray-300">
                                </div>
                            <?php else: ?>
                                <p class="text-sm text-gray-600 text-center">No profile picture uploaded.</p>
                            <?php endif; ?>

                            <!-- Input for new profile picture -->
                            <input type="file" id="organizer_profile_picture" name="organizer_profile_picture" accept="image/*" class="hidden">
                            <button type="button" id="open-cropper" class="mt-2 bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                Upload New Profile Picture
                            </button>

                            <!-- Cropper.js Container -->
                            <div id="image-cropper-container" class="mt-4 hidden">
                                <img id="image-to-crop" src="#" alt="Image to crop" class="max-w-full">
                            </div>

                            <!-- Cropper.js Controls -->
                            <div id="cropper-controls" class="mt-4 hidden">
                                <button type="button" id="crop-image" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500">
                                    Crop Image
                                </button>
                                <button type="button" id="cancel-crop" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Name and Email Fields -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label for="organizer_name" class="block text-sm font-medium text-gray-600">Name:</label>
                            <input type="text" id="organizer_name" name="organizer_name" value="<?= $organizer['organizer_name']; ?>"
                                class="mt-2 p-2 border border-gray-300 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>

                        <div>
                            <label for="organizer_email" class="block text-sm font-medium text-gray-600">Email:</label>
                            <input type="email" id="organizer_email" name="organizer_email" value="<?= $organizer['organizer_email']; ?>"
                                class="mt-2 p-2 border border-gray-300 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                    </div>

                    <!-- Phone Number and Department Fields -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label for="organizer_phone_number" class="block text-sm font-medium text-gray-600">Phone Number:</label>
                            <input type="tel" id="organizer_phone_number" name="organizer_phone_number" value="<?= $organizer['organizer_phone_number']; ?>"
                                class="mt-2 p-2 border border-gray-300 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>

                        <div>
                            <label for="organizer_department" class="block text-sm font-medium text-gray-600">Department:</label>
                            <input type="text" id="organizer_department" name="organizer_department" value="<?= $organizer['organizer_department']; ?>"
                                class="mt-2 p-2 border border-gray-300 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="mt-6">
                        <button type="submit" class="w-full bg-blue-500 text-white p-3 rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            Update Profile
                        </button>
                    </div>
                </div>
            </form>

            <!-- Flowbite Success Modal -->
            <div id="popup-modal" tabindex="-1" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 flex justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
                <div class="relative p-4 w-full max-w-md max-h-full">
                    <div class="relative bg-white rounded-lg shadow bg-white">
                        <button type="button" class="absolute top-3 end-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-hide="popup-modal">
                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                            </svg>
                            <span class="sr-only">Close modal</span>
                        </button>
                        <div class="p-4 md:p-5 text-center">
                            <svg class="mx-auto mb-4 text-green-500 w-12 h-12 dark:text-green-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                            </svg>
                            <h3 class="mb-5 text-lg font-normal text-gray-500 dark:text-gray-400">Your information has been successfully updated!</h3>
                            <button data-modal-hide="popup-modal" type="button" class="text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-500 dark:hover:bg-blue-600 dark:focus:ring-blue-800" onclick="window.location.href='edit_profile_organizer.php'">
                                OK
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Cropper.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <script>
        // Initialize Cropper.js
        let cropper;
        const image = document.getElementById('image-to-crop');
        const imageCropperContainer = document.getElementById('image-cropper-container');
        const cropperControls = document.getElementById('cropper-controls');
        const openCropperButton = document.getElementById('open-cropper');
        const cropImageButton = document.getElementById('crop-image');
        const cancelCropButton = document.getElementById('cancel-crop');
        const fileInput = document.getElementById('organizer_profile_picture');

        // Open Cropper when file is selected
        openCropperButton.addEventListener('click', () => {
            fileInput.click();
        });

        fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (event) => {
                    image.src = event.target.result;
                    imageCropperContainer.classList.remove('hidden');
                    cropperControls.classList.remove('hidden');

                    // Initialize Cropper.js
                    if (cropper) {
                        cropper.destroy();
                    }
                    cropper = new Cropper(image, {
                        aspectRatio: 1, // Square aspect ratio
                        viewMode: 1, // Restrict crop box to the image size
                    });
                };
                reader.readAsDataURL(file);
            }
        });

        // Crop the image
        cropImageButton.addEventListener('click', () => {
            if (cropper) {
                // Get the cropped image as a blob
                cropper.getCroppedCanvas().toBlob((blob) => {
                    // Create a new file from the blob
                    const file = new File([blob], 'cropped-profile-pic.png', { type: 'image/png' });

                    // Update the file input with the cropped image
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    fileInput.files = dataTransfer.files;

                    // Hide the cropper and show the preview
                    imageCropperContainer.classList.add('hidden');
                    cropperControls.classList.add('hidden');
                });
            }
        });

        // Cancel cropping
        cancelCropButton.addEventListener('click', () => {
            imageCropperContainer.classList.add('hidden');
            cropperControls.classList.add('hidden');
            fileInput.value = ''; // Clear the file input
        });
    </script>

    <!-- Include Flowbite JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
</body>
</html>