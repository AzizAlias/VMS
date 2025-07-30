<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer Sign Up</title>
    <!-- Include Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Include Flowbite CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet" />
    <!-- Include Flatpickr for Date Input -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <!-- Include Cropper.js for Image Cropping -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
    <!-- Include reCAPTCHA Script -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">

    <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-4xl">
        <h1 class="text-3xl font-semibold text-center text-gray-800 mb-6">Volunteer Sign Up</h1>

        <form action="signup_volunteer_process.php" method="POST" enctype="multipart/form-data">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Name Field -->
                <div>
                    <label for="volunteer_name" class="block text-sm font-medium text-gray-700 mb-2">Name:</label>
                    <input type="text" id="volunteer_name" name="volunteer_name"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        required>
                </div>

                <!-- Username Field -->
                <div>
                    <label for="volunteer_username" class="block text-sm font-medium text-gray-700 mb-2">Username:</label>
                    <input type="text" id="volunteer_username" name="volunteer_username"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        required>
                </div>

                <!-- Email Field -->
                <div>
                    <label for="volunteer_email" class="block text-sm font-medium text-gray-700 mb-2">Email:</label>
                    <input type="email" id="volunteer_email" name="volunteer_email"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        required>
                </div>

                <!-- Password Field -->
                <div>
                    <label for="volunteer_password" class="block text-sm font-medium text-gray-700 mb-2">Password:</label>
                    <input type="password" id="volunteer_password" name="volunteer_password"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        required>
                </div>

                <!-- Phone Number Field -->
                <div>
                    <label for="volunteer_phone_number" class="block text-sm font-medium text-gray-700 mb-2">Phone Number:</label>
                    <input type="tel" id="volunteer_phone_number" name="volunteer_phone_number"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        required>
                </div>

                <!-- Date of Birth Field -->
                <div>
                    <label for="volunteer_DOB" class="block text-sm font-medium text-gray-700 mb-2">Date of Birth:</label>
                    <input type="date" id="volunteer_DOB" name="volunteer_DOB"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        required>
                </div>

                <!-- Address Field -->
                <div class="sm:col-span-2">
                    <label for="volunteer_address" class="block text-sm font-medium text-gray-700 mb-2">Address:</label>
                    <input type="text" id="volunteer_address" name="volunteer_address"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        required>
                </div>

                <!-- Profile Picture Upload -->
                <div class="sm:col-span-2">
                    <label for="volunteer_profile_pic" class="block text-sm font-medium text-gray-700 mb-2">Upload Profile Picture:</label>
                    <input type="file" id="volunteer_profile_pic" name="volunteer_profile_pic" accept="image/*"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        onchange="loadFile(event)">
                    <div class="mt-4">
                        <img id="image-preview" src="#" alt="Preview" class="hidden max-w-full max-h-64 rounded-lg">
                    </div>
                    <!-- Crop and Cancel Buttons -->
                    <div id="crop-buttons" class="mt-2 space-x-2 hidden">
                        <button type="button" id="crop-button" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600">
                            Crop Image
                        </button>
                        <button type="button" id="cancel-crop-button" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>

            <!-- Availability Section -->
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Availability:</label>
                <div id="availability-container" class="space-y-2">
                    <!-- Initial Date Field -->
                    <div class="flex items-center gap-2">
                        <input type="date" name="volunteer_availability[]"
                            class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <button type="button" class="bg-red-500 text-white px-3 py-1 rounded-lg hover:bg-red-600 remove-date">
                            Remove
                        </button>
                    </div>
                </div>
                <!-- Add Date Button -->
                <button type="button" id="add-date" class="mt-2 bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600">
                    Add Another Date
                </button>
            </div>

            <!-- Skills Section -->
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Skills:</label>
                <div id="skills-container" class="space-y-2">
                    <!-- Initial Skill Field -->
                    <div class="flex items-center gap-2">
                        <input type="text" name="volunteer_skills[]"
                            class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Enter a skill">
                        <button type="button" class="bg-red-500 text-white px-3 py-1 rounded-lg hover:bg-red-600 remove-skill">
                            Remove
                        </button>
                    </div>
                </div>
                <!-- Add Skill Button -->
                <button type="button" id="add-skill" class="mt-2 bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600">
                    Add Another Skill
                </button>
            </div>

            <!-- reCAPTCHA Widget -->
            <div class="mt-6">
                <div class="g-recaptcha" data-sitekey="6Ldte78qAAAAAHCOiQ_-zc77jSqh_pinBZbgxl4T"></div>
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

    <script>
        // Dynamic Availability Fields
        document.getElementById('add-date').addEventListener('click', function () {
            const container = document.getElementById('availability-container');
            const newDateField = document.createElement('div');
            newDateField.className = "flex items-center gap-2";
            newDateField.innerHTML = `
                <input type="date" name="volunteer_availability[]" 
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <button type="button" class="bg-red-500 text-white px-3 py-1 rounded-lg hover:bg-red-600 remove-date">
                    Remove
                </button>`;
            container.appendChild(newDateField);
        });

        // Dynamic Skills Fields
        document.getElementById('add-skill').addEventListener('click', function () {
            const container = document.getElementById('skills-container');
            const newSkillField = document.createElement('div');
            newSkillField.className = "flex items-center gap-2";
            newSkillField.innerHTML = `
                <input type="text" name="volunteer_skills[]" 
                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                       placeholder="Enter a skill">
                <button type="button" class="bg-red-500 text-white px-3 py-1 rounded-lg hover:bg-red-600 remove-skill">
                    Remove
                </button>`;
            container.appendChild(newSkillField);
        });

        document.addEventListener('click', function (e) {
            if (e.target && e.target.classList.contains('remove-date')) {
                e.target.parentElement.remove();
            } else if (e.target && e.target.classList.contains('remove-skill')) {
                e.target.parentElement.remove();
            }
        });

        // Cropper.js Integration
        let cropper;

        function loadFile(event) {
            const imagePreview = document.getElementById('image-preview');
            const cropButtons = document.getElementById('crop-buttons');
            const file = event.target.files[0];

            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.classList.remove('hidden');
                    cropButtons.classList.remove('hidden');

                    if (cropper) {
                        cropper.destroy();
                    }

                    cropper = new Cropper(imagePreview, {
                        aspectRatio: 1, // Set aspect ratio as needed
                        viewMode: 1,
                    });
                };
                reader.readAsDataURL(file);
            }
        }

        // Crop Image Button
        document.getElementById('crop-button').addEventListener('click', function() {
            const croppedCanvas = cropper.getCroppedCanvas();
            const croppedImage = document.createElement('img');
            croppedImage.src = croppedCanvas.toDataURL('image/jpeg');

            // Replace the image preview with the cropped image
            const imagePreview = document.getElementById('image-preview');
            imagePreview.src = croppedImage.src;
            imagePreview.classList.remove('hidden');

            // Hide the crop buttons after cropping
            document.getElementById('crop-buttons').classList.add('hidden');

            // Optionally, you can create a hidden input to store the cropped image data
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'cropped_image';
            hiddenInput.value = croppedImage.src;
            document.querySelector('form').appendChild(hiddenInput);
        });

        // Cancel Crop Button
        document.getElementById('cancel-crop-button').addEventListener('click', function() {
            const imagePreview = document.getElementById('image-preview');
            const cropButtons = document.getElementById('crop-buttons');
            const fileInput = document.getElementById('volunteer_profile_pic');

            // Reset the image preview and file input
            imagePreview.src = '#';
            imagePreview.classList.add('hidden');
            cropButtons.classList.add('hidden');
            fileInput.value = ''; // Clear the file input

            if (cropper) {
                cropper.destroy(); // Destroy the cropper instance
            }
        });
    </script>

    <!-- Include Flowbite JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
</body>

</html>