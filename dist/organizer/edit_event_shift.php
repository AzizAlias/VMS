<?php
session_start();
// Include the config file
require_once '../volunteer/config.php';

// Check if the user is logged in as an organizer
if (!isset($_SESSION['organizer_id'])) {
    header("Location: log_in_organizer.php");
    exit();
}


// Get the shift ID from the URL
if (isset($_GET['shift_id'])) {
    $shift_id = $_GET['shift_id'];

    // Fetch shift details based on the shift ID
    $shift_query = "
    SELECT s.*, e.event_name, e.event_date AS event_start_date 
    FROM shift s
    INNER JOIN event_management e ON s.event_id = e.event_id
    WHERE s.shift_id = ?";
    $stmt = $conn->prepare($shift_query);
    $stmt->bind_param("i", $shift_id);
    $stmt->execute();
    $shift_result = $stmt->get_result();

    if ($shift_result->num_rows > 0) {
        $shift = $shift_result->fetch_assoc();
        $event_id = $shift['event_id'];
        $event_name = $shift['event_name'];
        $event_start_date = $shift['event_start_date'];
    } else {
        echo "Shift not found.";
        exit();
    }
} else {
    echo "Invalid request.";
    exit();
}

// Initialize a flag for success or failure
$update_status = '';

// Fetch the event's total quota
$event_query = "SELECT event_quota FROM event_management WHERE event_id = ?";
$event_stmt = $conn->prepare($event_query);
$event_stmt->bind_param("i", $event_id);
$event_stmt->execute();
$event_result = $event_stmt->get_result();
$event = $event_result->fetch_assoc();
$event_quota = $event['event_quota'];

// Fetch the total number of volunteers already assigned to shifts for the event (excluding the shift being edited)
$assigned_volunteers_query = "
    SELECT SUM(shift_quota) AS total_assigned_quota 
    FROM shift 
    WHERE event_id = ? AND shift_id != ?";
$assigned_stmt = $conn->prepare($assigned_volunteers_query);
$assigned_stmt->bind_param("ii", $event_id, $shift_id);
$assigned_stmt->execute();
$assigned_result = $assigned_stmt->get_result();
$assigned = $assigned_result->fetch_assoc();
$assigned_quota = $assigned['total_assigned_quota'];

// Handle form submission to update the shift details
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $shift_start_date = $_POST['shift_start_date'];
    $shift_end_date = $_POST['shift_end_date'];
    $shift_start_time = $_POST['shift_start_time'];
    $shift_end_time = $_POST['shift_end_time'];
    $shift_location = $_POST['shift_location'];
    $shift_quota = $_POST['shift_quota'];

    // Check if the new shift quota exceeds the event's quota, considering the total assigned volunteers
    if (($assigned_quota + $shift_quota) > $event_quota) {
        $update_status = 'quota_error';
    } else {
        // Update the shift in the database
        $update_query = "UPDATE shift SET shift_start_date = ?, shift_end_date = ?, shift_start_time = ?, shift_end_time = ?, shift_location = ?, shift_quota = ? WHERE shift_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ssssssi", $shift_start_date, $shift_end_date, $shift_start_time, $shift_end_time, $shift_location, $shift_quota, $shift_id);
        $update_stmt->execute();

        if ($update_stmt->affected_rows > 0) {
            // Redirect to the shift management page with a success message
            header("Location: manage_shift.php?event_id=" . $shift['event_id'] . "&success=1");
            exit();
        } else {
            // Set update_status to 'failed' if no rows were updated
            $update_status = 'failed';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event Shift</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script>
        // Show alerts based on the update status
        <?php if ($update_status == 'failed') : ?>
            alert("Failed to update the shift. Please try again.");
        <?php elseif ($update_status == 'quota_error') : ?>
            alert("The shift quota exceeds the available event quota. Please enter a valid quota.");
        <?php endif; ?>
    </script>
</head>
<body class="bg-gray-100 font-sans">

<div class="max-w-3xl mx-auto p-6 bg-white shadow-lg rounded-lg mt-10">

    <!-- Back to Manage Shifts Button -->
    <div class="mb-6">
        <a href="manage_shift.php?event_id=<?php echo $shift['event_id']; ?>" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
            Back to Manage Shifts
        </a>
    </div>

    <!-- Edit Shift Form -->
    <h2 class="text-2xl font-semibold text-gray-800 mb-6">Edit Shift for Event: <?php echo $event_name; ?></h2>

    <form method="POST" class="space-y-4">
        <div>
            <label class="block text-gray-600">Start Date:</label>
            <input type="date" name="shift_start_date" class="w-full p-2 border border-gray-300 rounded-lg" value="<?php echo $shift['shift_start_date']; ?>" required min="<?php echo $event_start_date; ?>">
        </div>

        <div>
            <label class="block text-gray-600">End Date:</label>
            <input type="date" name="shift_end_date" class="w-full p-2 border border-gray-300 rounded-lg" value="<?php echo $shift['shift_end_date']; ?>" required min="<?php echo $event_start_date; ?>">
        </div>

        <div>
            <label class="block text-gray-600">Start Time:</label>
            <input type="time" name="shift_start_time" class="w-full p-2 border border-gray-300 rounded-lg" value="<?php echo $shift['shift_start_time']; ?>" required>
        </div>

        <div>
            <label class="block text-gray-600">End Time:</label>
            <input type="time" name="shift_end_time" class="w-full p-2 border border-gray-300 rounded-lg" value="<?php echo $shift['shift_end_time']; ?>" required>
        </div>

        <div>
            <label class="block text-gray-600">Location:</label>
            <input type="text" name="shift_location" class="w-full p-2 border border-gray-300 rounded-lg" value="<?php echo $shift['shift_location']; ?>" required>
        </div>

        <div>
            <label class="block text-gray-600">Quota:</label>
            <input type="number" name="shift_quota" class="w-full p-2 border border-gray-300 rounded-lg" value="<?php echo $shift['shift_quota']; ?>" required>
        </div>

        <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">Update Shift</button>
    </form>
</div>

</body>
</html>

<?php
// Close the connection
$stmt->close();
$conn->close();
?>
