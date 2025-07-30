<?php
session_start();
// Include the config file
require_once '../volunteer/config.php';

// Check if the user is logged in as an organizer
if (!isset($_SESSION['organizer_id'])) {
    header("Location: log_in_organizer.php");
    exit();
}

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

// Check if event_id is passed and fetch event details
if (isset($_GET['event_id'])) {
    $event_id = $_GET['event_id'];

    // Prepare a SQL statement to fetch event details, including event_date, required_skills
    $query = "SELECT event_name, event_location, event_description, event_date, event_quota, event_status, event_quota_status, event_poster, required_skills FROM event_management WHERE event_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $stmt->bind_result($event_name, $event_location, $event_description, $event_date, $event_quota, $event_status, $event_quota_status, $event_poster, $required_skills);
    $stmt->fetch();
    $stmt->close();

    // Fetch the required skills for this event from the database
    $skills_query = "SELECT required_skills FROM event_management WHERE event_id = ?";
    $skills_stmt = $conn->prepare($skills_query);
    $skills_stmt->bind_param("i", $event_id);
    $skills_stmt->execute();
    $skills_result = $skills_stmt->get_result();
    $skills = [];
    while ($row = $skills_result->fetch_assoc()) {
        $skills[] = $row['required_skills'];
    }
    $skills_stmt->close();
} else {
    echo "Event ID is missing.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<?php include 'organizer_sidebar.php'; ?>
<body class="bg-gray-100 text-gray-800 font-sans">
    <!-- Edit Event Form -->
    <div class="max-w-4xl mx-auto mt-10 bg-white p-8 rounded-lg shadow-lg">
        <h2 class="text-3xl font-bold mb-8 text-gray-800 text-center">Edit Event</h2>

        <form action="edit_event_process.php" method="POST" enctype="multipart/form-data" class="space-y-6">
            <!-- Event ID (Hidden) -->
            <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">

            <!-- Event Name -->
            <div>
                <label for="event_name" class="block text-gray-700 font-semibold mb-2">Event Name</label>
                <input type="text" id="event_name" name="event_name" value="<?php echo $event_name; ?>" required
                       class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Event Description -->
            <div>
                <label for="event_description" class="block text-gray-700 font-semibold mb-2">Event Description</label>
                <textarea id="event_description" name="event_description" rows="5" required
                          class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo $event_description; ?></textarea>
            </div>

            <!-- Event Date -->
            <div>
                <label for="event_date" class="block text-gray-700 font-semibold mb-2">Event Date</label>
                <input type="date" id="event_date" name="event_date" value="<?php echo $event_date; ?>" required
                       class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Event Location -->
            <div>
                <label for="event_location" class="block text-gray-700 font-semibold mb-2">Event Location</label>
                <input type="text" id="event_location" name="event_location" value="<?php echo $event_location; ?>" required
                       class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Event Quota -->
            <div>
                <label for="event_quota" class="block text-gray-700 font-semibold mb-2">Event Quota</label>
                <input type="number" id="event_quota" name="event_quota" min="1" value="<?php echo $event_quota; ?>" required
                       class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Event Quota Status -->
            <div>
                <label for="event_quota_status" class="block text-gray-700 font-semibold mb-2">Event Quota Status</label>
                <select id="event_quota_status" name="event_quota_status" required
                        class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Select Status</option>
                    <?php
                    foreach ($event_quota_status_values as $value) {
                        $selected = ($value == $event_quota_status) ? 'selected' : '';
                        echo "<option value='" . trim($value) . "' $selected>" . ucfirst(strtolower(trim($value))) . "</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- Event Status -->
            <div>
                <label for="event_status" class="block text-gray-700 font-semibold mb-2">Event Status</label>
                <select id="event_status" name="event_status" required
                        class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Select Status</option>
                    <?php
                    foreach ($event_status_values as $value) {
                        $selected = ($value == $event_status) ? 'selected' : '';
                        echo "<option value='" . trim($value) . "' $selected>" . ucfirst(strtolower(trim($value))) . "</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- Event Poster -->
            <div>
                <label for="event_poster" class="block text-gray-700 font-semibold mb-2">Event Poster</label>
                <input type="file" id="event_poster" name="event_poster"
                       class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <small class="text-gray-500">Current Poster: <?php echo $event_poster; ?></small>
            </div>

            <!-- Event Required Skills -->
            <div>
                <label for="required_skills" class="block text-gray-700 font-semibold mb-2">Required Skills</label>
                <div id="skills-container" class="space-y-2">
                    <?php
                    foreach ($skills as $skill) {
                        echo '<div class="flex items-center">
                                <input type="text" name="required_skills[]" value="' . $skill . '"
                                       class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter a skill">
                                <button type="button" class="ml-2 bg-red-500 text-white px-3 py-2 rounded-lg remove-skill">Remove</button>
                              </div>';
                    }
                    ?>
                </div>
                <button type="button" id="add-skill" class="mt-2 bg-green-500 text-white px-4 py-2 rounded-lg">
                    Add Another Skill
                </button>
            </div>

            <!-- Submit Button -->
            <div class="text-center">
                <button type="submit" class="bg-blue-500 text-white py-3 px-8 rounded-lg hover:bg-blue-600 transition duration-300">
                    Update Event
                </button>
            </div>
        </form>
    </div>

    <script>
    // Add new skill input field
    document.getElementById('add-skill').addEventListener('click', function () {
        const container = document.getElementById('skills-container');
        const newSkillField = document.createElement('div');
        newSkillField.className = "flex items-center mt-2";
        newSkillField.innerHTML = `
            <input type="text" name="required_skills[]" 
                   class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                   placeholder="Enter a skill" required>
            <button type="button" class="ml-2 bg-red-500 text-white px-3 py-2 rounded-lg remove-skill">
                Remove
            </button>`;
        container.appendChild(newSkillField);
    });

    // Remove a skill input field
    document.addEventListener('click', function (e) {
        if (e.target && e.target.classList.contains('remove-skill')) {
            e.target.parentElement.remove();
        }
    });
    </script>
</body>
</html>