<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organizer Sign Up</title>
    <!-- Include Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Include Cropper.js CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css">
    <!-- Include reCAPTCHA Script -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-4xl">
        <h1 class="text-3xl font-semibold text-center text-gray-700 mb-6">Organizer Sign Up</h1>

        <form action="signup_organizer_process.php" method="POST" enctype="multipart/form-data" id="signupForm">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Name Field -->
                <div>
                    <label for="organizer_name" class="block text-sm font-medium text-gray-600">Name:</label>
                    <input type="text" id="organizer_name" name="organizer_name"
                        class="mt-2 p-2 border border-gray-300 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required>
                </div>

                <!-- Email Field -->
                <div>
                    <label for="organizer_email" class="block text-sm font-medium text-gray-600">Email:</label>
                    <input type="email" id="organizer_email" name="organizer_email"
                        class="mt-2 p-2 border border-gray-300 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required>
                </div>

                <!-- Password Field -->
                <div>
                    <label for="organizer_password" class="block text-sm font-medium text-gray-600">Password:</label>
                    <input type="password" id="organizer_password" name="organizer_password"
                        class="mt-2 p-2 border border-gray-300 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required>
                </div>

                <!-- Phone Number Field -->
                <div>
                    <label for="organizer_phone_number" class="block text-sm font-medium text-gray-600">Phone Number:</label>
                    <input type="tel" id="organizer_phone_number" name="organizer_phone_number"
                        class="mt-2 p-2 border border-gray-300 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required>
                </div>

                <!-- Department Field -->
                <div>
                    <label for="organizer_department" class="block text-sm font-medium text-gray-600">Organization Department:</label>
                    <input type="text" id="organizer_department" name="organizer_department"
                        class="mt-2 p-2 border border-gray-300 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required>
                </div>

                <!-- Profile Picture Upload -->
                <div class="sm:col-span-2">
                    <label for="organizer_profile_pic" class="block text-sm font-medium text-gray-600">Upload Profile Picture:</label>
                    <input type="file" id="organizer_profile_pic" name="organizer_profile_pic" accept="image/*"
                        class="mt-2 p-2 border border-gray-300 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                        onchange="loadFile(event)">
                </div>

                <!-- Image Cropper -->
                <div class="sm:col-span-2">
                    <div id="image-cropper-container" class="hidden">
                        <img id="image-to-crop" src="#" alt="Image to crop" class="max-w-full h-auto">
                    </div>
                    <button type="button" id="crop-button" class="mt-2 bg-green-500 text-white px-3 py-1 rounded hidden">
                        Crop Image
                    </button>
                    <button type="button" id="cancel-crop-button" class="mt-2 bg-red-500 text-white px-3 py-1 rounded hidden">
                        Cancel
                    </button>
                </div>

                <!-- Hidden input for cropped image -->
                <input type="hidden" id="cropped_image" name="cropped_image">
            </div>

            <!-- reCAPTCHA Widget -->
            <div class="mt-6">
                <div class="g-recaptcha" data-sitekey="6LdFlL8qAAAAAEQBpo2oMbX__jaUSEDYKzZP0pu9"></div>
            </div>

            <!-- Submit Button -->
            <div class="mt-6">
                <button type="submit"
                    class="w-full bg-blue-500 text-white p-3 rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    Sign Up
                </button>
            </div>
        </form>
    </div>

    <!-- Cropper.js JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const profilePicInput = document.getElementById('organizer_profile_pic');
            const imageCropperContainer = document.getElementById('image-cropper-container');
            const imageToCrop = document.getElementById('image-to-crop');
            const cropButton = document.getElementById('crop-button');
            const cancelCropButton = document.getElementById('cancel-crop-button');
            const croppedImageInput = document.getElementById('cropped_image');
            let cropper;

            // When a file is selected
            profilePicInput.addEventListener('change', function (e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function (event) {
                        // Show the image and cropper
                        imageToCrop.src = event.target.result;
                        imageCropperContainer.classList.remove('hidden');
                        cropButton.classList.remove('hidden');
                        cancelCropButton.classList.remove('hidden');

                        // Initialize Cropper.js
                        if (cropper) {
                            cropper.destroy();
                        }
                        cropper = new Cropper(imageToCrop, {
                            aspectRatio: 1, // Square aspect ratio
                            viewMode: 1, // Restrict the crop box to the image size
                        });
                    };
                    reader.readAsDataURL(file);
                }
            });

            // When the crop button is clicked
            cropButton.addEventListener('click', function () {
                if (cropper) {
                    // Get the cropped image as a data URL
                    const croppedCanvas = cropper.getCroppedCanvas();
                    const croppedImageData = croppedCanvas.toDataURL('image/jpeg');

                    // Set the cropped image data to the hidden input
                    croppedImageInput.value = croppedImageData;

                    // Hide the cropper and show a preview of the cropped image
                    imageCropperContainer.classList.add('hidden');
                    cropButton.classList.add('hidden');
                    cancelCropButton.classList.add('hidden');
                    alert('Image cropped successfully!');
                }
            });

            // When the cancel button is clicked
            cancelCropButton.addEventListener('click', function () {
                // Reset the image cropper and hide the container
                if (cropper) {
                    cropper.destroy();
                }
                imageCropperContainer.classList.add('hidden');
                cropButton.classList.add('hidden');
                cancelCropButton.classList.add('hidden');
                profilePicInput.value = ''; // Clear the file input
                croppedImageInput.value = ''; // Clear the cropped image data
            });
        });
    </script>

</body>

</html>