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
$event_query = "SELECT event_id, event_name FROM event_management WHERE organizer_id = ? AND event_status = 'OPEN'";
$stmt = $conn->prepare($event_query);
$stmt->bind_param("i", $organizer_id);
$stmt->execute();
$event_result = $stmt->get_result();

// Fetch shifts based on the selected event
$shifts = [];
if (isset($_POST['event_id']) && !empty($_POST['event_id'])) {
    $event_id = $_POST['event_id'];
    $shift_query = "SELECT * FROM shift WHERE event_id = ?";
    $shift_stmt = $conn->prepare($shift_query);
    $shift_stmt->bind_param("i", $event_id);
    $shift_stmt->execute();
    $shift_result = $shift_stmt->get_result();
    while ($shift = $shift_result->fetch_assoc()) {
        $shifts[] = $shift;
    }
}

// Fetch the event name for the selected event
$event_name = '';
if (isset($event_id) && !empty($event_id)) {
    $event_name_query = "SELECT event_name FROM event_management WHERE event_id = ?";
    $event_name_stmt = $conn->prepare($event_name_query);
    $event_name_stmt->bind_param("i", $event_id);
    $event_name_stmt->execute();
    $event_name_result = $event_name_stmt->get_result();
    if ($event_name_result->num_rows > 0) {
        $event_row = $event_name_result->fetch_assoc();
        $event_name = $event_row['event_name'];
    }
    $event_name_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Event Shifts</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

    <script>
    // Check URL parameters for success and delete messages
    const urlParams = new URLSearchParams(window.location.search);
    
    const deleteSuccess = urlParams.get('delete_success');
    const success = urlParams.get('success');
    if (deleteSuccess === '1') {
        alert('Shift deleted successfully!');
    } else if (deleteSuccess === '0') {
        alert('Failed to delete the shift. Please try again.');
    }

    if (success === '1') {
        alert('Shift updated successfully!');
    } else if (success === '0') {
        alert('Failed to update the shift. Please try again.');
    }
    
    // Remove parameters from the URL
    if (deleteSuccess || success) {
        urlParams.delete('delete_success');
        urlParams.delete('success');
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // Confirm before delete
    function confirmDelete(shiftId) {
        if (confirm("Are you sure you want to delete this shift?")) {
            window.location.href = "delete_event_shift.php?shift_id=" + shiftId;
        }
    }
    </script>

</head>
<body class="bg-gray-100 font-sans">

    <?php include 'organizer_sidebar.php'; ?>
<div class="max-w-3xl mx-auto p-6 bg-white shadow-lg rounded-lg mt-10">

    <h2 class="text-2xl font-semibold text-gray-800 mb-6">Manage Shifts for Your Events</h2>
    
    <form method="POST" class="space-y-4 mb-8">
        <div>
            <label class="block text-gray-600">Select Event:</label>
            <select name="event_id" class="w-full p-2 border border-gray-300 rounded-lg" required>
                <option value="">Choose an event</option>
                <?php
                if ($event_result->num_rows > 0) {
                    while ($row = $event_result->fetch_assoc()) {
                        echo "<option value='" . $row['event_id'] . "'>" . $row['event_name'] . "</option>";
                    }
                } else {
                    echo "<option value=''>No OPEN events available</option>";
                }
                ?>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">View Shifts</button>
    </form>

    <?php if (isset($event_id) && !empty($shifts)) { ?>
        <h3 class="text-xl font-semibold text-gray-800 mb-4">Shifts for <?php echo $event_name; ?></h3>

        <!-- Shift Table -->
        <table class="min-w-full bg-white border border-gray-300 rounded-lg">
            <thead>
                <tr class="bg-gray-200">
                    <th class="p-2 text-left">Shift ID</th>
                    <th class="p-2 text-left">Start Date</th>
                    <th class="p-2 text-left">End Date</th>
                    <th class="p-2 text-left">Start Time</th>
                    <th class="p-2 text-left">End Time</th>
                    <th class="p-2 text-left">Location</th>
                    <th class="p-2 text-left">Quota</th>
                    <th class="p-2 text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($shifts as $shift) { ?>
                    <tr>
                        <td class="p-2"><?php echo $shift['shift_id']; ?></td>
                        <td class="p-2"><?php echo $shift['shift_start_date']; ?></td>
                        <td class="p-2"><?php echo $shift['shift_end_date']; ?></td>
                        <td class="p-2"><?php echo $shift['shift_start_time']; ?></td>
                        <td class="p-2"><?php echo $shift['shift_end_time']; ?></td>
                        <td class="p-2"><?php echo $shift['shift_location']; ?></td>
                        <td class="p-2"><?php echo $shift['shift_quota']; ?></td>
                        <td class="p-2">
                            <a href="edit_event_shift.php?shift_id=<?php echo $shift['shift_id']; ?>" class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600">Edit</a>
                            <button onclick="confirmDelete(<?php echo $shift['shift_id']; ?>)" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">Delete</button>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php } elseif (isset($event_id)) { ?>
        <p class="text-gray-600 mt-4">No shifts available for the selected event.</p>
    <?php } ?>
</div>

</body>
</html>

<?php


?>