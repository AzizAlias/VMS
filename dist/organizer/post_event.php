<?php
session_start();
// Include the config file
require_once '../volunteer/config.php';

// Check if the user is logged in as an organizer
if (!isset($_SESSION['organizer_id'])) {
    header("Location: log_in_organizer.php");
    exit();
}

// Fetch categories
$categories_result = $conn->query("SELECT category_id, category_name FROM category");

// Fetch ENUM values for event_status
$event_status_values = [];
$enum_query = "SHOW COLUMNS FROM event_management LIKE 'event_status'";
$enum_result = $conn->query($enum_query);

if ($enum_result) {
    $enum_row = $enum_result->fetch_assoc();
    preg_match("/^enum\((.*)\)$/", $enum_row['Type'], $matches);
    $event_status_values = explode(",", str_replace("'", "", $matches[1]));
}

// Fetch ENUM values for event_quota_status
$event_quota_status_values = [];
$enum_query = "SHOW COLUMNS FROM event_management LIKE 'event_quota_status'";
$enum_result = $conn->query($enum_query);

if ($enum_result) {
    $enum_row = $enum_result->fetch_assoc();
    preg_match("/^enum\((.*)\)$/", $enum_row['Type'], $matches);
    $event_quota_status_values = explode(",", str_replace("'", "", $matches[1]));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Event</title>
    <!-- Include Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Include Flowbite CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.8.1/flowbite.min.css" rel="stylesheet" />
</head>
<body class="bg-gray-100 text-gray-800 flex justify-center items-center min-h-screen">

    <?php include 'organizer_sidebar.php'; ?>

    <div class="w-full max-w-2xl bg-white p-6 rounded-lg shadow-lg">
        <h1 class="text-xl font-semibold text-center text-gray-700 mb-6">Post a New Event</h1>
        <form action="post_event_process.php" method="POST" enctype="multipart/form-data" class="grid grid-cols-2 gap-4">
            <!-- Event Name -->
            <div class="col-span-2">
                <label for="event_name" class="block text-sm font-medium text-gray-900">Event Name</label>
                <input type="text" id="event_name" name="event_name" required
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2">
            </div>

            <!-- Event Description -->
            <div class="col-span-2">
                <label for="event_description" class="block text-sm font-medium text-gray-900">Event Description</label>
                <textarea id="event_description" name="event_description" rows="3" required
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2"></textarea>
            </div>

            <!-- Event Date -->
            <div>
                <label for="event_date" class="block text-sm font-medium text-gray-900">Event Date</label>
                <input type="date" id="event_date" name="event_date" required
                    min="<?php echo date('Y-m-d'); ?>"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2">
            </div>

            <!-- Event Quota Status -->
            <div>
                <label for="event_quota_status" class="block text-sm font-medium text-gray-900">Event Quota Status</label>
                <select id="event_quota_status" name="event_quota_status" required
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2">
                    <option value="">Select Status</option>
                    <?php
                    foreach ($event_quota_status_values as $value) {
                        echo "<option value='" . trim($value) . "'>" . ucfirst(strtolower(trim($value))) . "</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- Event Status -->
            <div>
                <label for="event_status" class="block text-sm font-medium text-gray-900">Event Status</label>
                <select id="event_status" name="event_status" required
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2">
                    <option value="">Select Status</option>
                    <?php
                    foreach ($event_status_values as $value) {
                        echo "<option value='" . trim($value) . "'>" . ucfirst(strtolower(trim($value))) . "</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- Event Quota -->
            <div>
                <label for="event_quota" class="block text-sm font-medium text-gray-900">Event Quota</label>
                <input type="number" id="event_quota" name="event_quota" min="1" required
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2">
            </div>

            <!-- Event Location -->
            <div>
                <label for="event_location" class="block text-sm font-medium text-gray-900">Event Location</label>
                <input type="text" id="event_location" name="event_location" required
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2">
            </div>

            <!-- Category -->
            <div>
                <label for="category_id" class="block text-sm font-medium text-gray-900">Category</label>
                <select id="category_id" name="category_id" required
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2">
                    <option value="">Select Category</option>
                    <?php
                    if ($categories_result->num_rows > 0) {
                        while ($category = $categories_result->fetch_assoc()) {
                            echo "<option value='" . $category['category_id'] . "'>" . $category['category_name'] . "</option>";
                        }
                    } else {
                        echo "<option value=''>No categories available</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- Skills Section -->
            <div class="col-span-2">
                <label for="required_skills" class="block text-sm font-medium text-gray-900">Skills:</label>
                <div id="skills-container" class="space-y-2">
                    <!-- Initial Skill Field -->
                    <div class="flex items-center">
                        <input type="text" name="required_skills[]"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2"
                            placeholder="Enter a skill">
                        <button type="button" class="ml-2 bg-red-500 text-white px-2 py-1 rounded-lg remove-skill">
                            Remove
                        </button>
                    </div>
                </div>
                <!-- Add Skill Button -->
                <button type="button" id="add-skill" class="mt-2 bg-green-500 text-white px-3 py-1 rounded-lg text-sm">
                    Add Another Skill
                </button>
            </div>

            <!-- Event Media (Poster/Image Upload) -->
            <div class="col-span-2">
                <label for="event_poster" class="block text-sm font-medium text-gray-900">Upload Event Media (Poster/Image)</label>
                <input type="file" id="event_poster" name="event_poster" accept="image/*"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2">
            </div>

            <!-- Registration Approval -->
            <div class="col-span-2">
                <label for="require_approval" class="block text-sm font-medium text-gray-900">Registration Approval</label>
                <select id="require_approval" name="require_approval" required
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2">
                    <option value="">Select Approval Status</option>
                    <option value="1">Yes (Requires Approval)</option>
                    <option value="0">No (No Approval Required)</option>
                </select>
            </div>

            <!-- Submit Button -->
            <div class="col-span-2">
                <button type="submit" class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                    Post Event
                </button>
            </div>
        </form>
    </div>

    <!-- Include Flowbite JS (for interactive components) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.8.1/flowbite.min.js"></script>
    <script>
        document.getElementById('add-skill').addEventListener('click', function () {
            const container = document.getElementById('skills-container');
            const newSkillField = document.createElement('div');
            newSkillField.className = "flex items-center mt-2";
            newSkillField.innerHTML = `
                <input type="text" name="required_skills[]" 
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2" 
                       placeholder="Enter a skill">
                <button type="button" class="ml-2 bg-red-500 text-white px-2 py-1 rounded-lg remove-skill">
                    Remove
                </button>`;
            container.appendChild(newSkillField);
        });

        document.addEventListener('click', function (e) {
            if (e.target && e.target.classList.contains('remove-skill')) {
                e.target.parentElement.remove();
            }
        });
    </script>
</body>
</html>