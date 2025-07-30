<?php
session_start();
// Include the config file
require_once '../volunteer/config.php';

// Check if the user is logged in as an organizer
if (!isset($_SESSION['organizer_id'])) {
    header("Location: log_in_organizer.php");
    exit();
}

// Get the logged-in organizer's ID
$organizer_id = $_SESSION['organizer_id'];

// Fetch events for the dropdown, only for the logged-in organizer and where event_status is 'OPEN'
$event_query = "SELECT event_id, event_name, event_date FROM event_management WHERE organizer_id = ? AND event_status = 'OPEN'";
$stmt = $conn->prepare($event_query);
$stmt->bind_param("i", $organizer_id);
$stmt->execute();
$event_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Shift</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans">

<?php include 'organizer_sidebar.php'; ?>

    <div class="max-w-3xl mx-auto p-6 bg-white shadow-lg rounded-lg mt-10">

   

    <!-- Shift Creation Form -->
    <h2 class="text-2xl font-semibold text-gray-800 mb-6">Create a New Shift for an Event</h2>
    <form action="add_event_shift_process.php" method="POST" class="space-y-4">
        <div>
            <label class="block text-gray-600">Select Event:</label>
            <select name="event_id" id="event_selector" class="w-full p-2 border border-gray-300 rounded-lg" required>
                <option value="" data-start-date="">Choose an event</option>
                <?php
                if ($event_result->num_rows > 0) {
                    while ($row = $event_result->fetch_assoc()) {
                        echo "<option value='" . $row['event_id'] . "' data-start-date='" . $row['event_date'] . "'>" . $row['event_name'] . "</option>";
                    }
                } else {
                    echo "<option value=''>No OPEN events available</option>";
                }
                ?>
            </select>
        </div>

        <div>
            <label class="block text-gray-600">Quota:</label>
            <input type="number" name="shift_quota" class="w-full p-2 border border-gray-300 rounded-lg" required>
        </div>

        <div>
            <label class="block text-gray-600">Shift Start Date:</label>
            <input type="date" id="shift_start_date" name="shift_start_date" class="w-full p-2 border border-gray-300 rounded-lg" required>
        </div>
        <div>
            <label class="block text-gray-600">Shift End Date:</label>
            <input type="date" id="shift_end_date" name="shift_end_date" class="w-full p-2 border border-gray-300 rounded-lg" required>
        </div>
        <div>
            <label class="block text-gray-600">Shift Start Time:</label>
            <input type="time" name="shift_start_time" class="w-full p-2 border border-gray-300 rounded-lg" required>
        </div>
        <div>
            <label class="block text-gray-600">Shift End Time:</label>
            <input type="time" name="shift_end_time" class="w-full p-2 border border-gray-300 rounded-lg" required>
        </div>
        <div>
            <label class="block text-gray-600">Location:</label>
            <input type="text" name="shift_location" class="w-full p-2 border border-gray-300 rounded-lg" required>
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">Add Shift</button>
    </form>

</div>

<script>
    document.getElementById('event_selector').addEventListener('change', function () {
        const selectedOption = this.options[this.selectedIndex];
        const startDate = selectedOption.getAttribute('data-start-date');
        if (startDate) {
            document.getElementById('shift_start_date').setAttribute('min', startDate);
            document.getElementById('shift_end_date').setAttribute('min', startDate);
        } else {
            document.getElementById('shift_start_date').removeAttribute('min');
            document.getElementById('shift_end_date').removeAttribute('min');
        }
    });
</script>

</body>
</html>

<?php

$conn->close();
?>