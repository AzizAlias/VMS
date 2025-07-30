<?php
session_start();
// Include the config file
require_once 'config.php';

// Check if the user is logged in as a volunteer
if (!isset($_SESSION['volunteer_id'])) {
    header("Location: log_in_volunteer.php");
    exit();
}

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = ""; // Set your MySQL password here
$dbname = "volunteer_management"; // Replace with your database name

// Create database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$volunteer_id = $_SESSION['volunteer_id']; // Get the logged-in volunteer's ID

// Get the event_id from the URL
if (isset($_GET['event_id'])) {
    $event_id = $_GET['event_id'];

    // Get the shifts for the selected event
    $sql = "SELECT s.shift_id, s.shift_start_date, s.shift_end_date, s.shift_start_time, s.shift_end_time, s.shift_location, s.shift_quota, s.current_registrations
    FROM shift s
    WHERE s.event_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
} else {
    echo "<p class='text-red-600'>No event selected.</p>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Shifts for Event</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans">
<?php include 'volunteer_sidebar.php'; ?>
    <div class="main-content">
    <div class="max-w-3xl mx-auto p-6 bg-white shadow-lg rounded-lg mt-10">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">Available Shifts for the Selected Event</h2>

        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $shift_id = $row['shift_id'];
                $is_registered = false;

                // Check if the volunteer is already registered for this shift
                $check_registration_query = "SELECT 1 FROM shift_registration WHERE volunteer_id = ? AND shift_id = ?";
                $stmt_check = $conn->prepare($check_registration_query);
                $stmt_check->bind_param("ii", $volunteer_id, $shift_id);
                $stmt_check->execute();
                $stmt_check->store_result();
                if ($stmt_check->num_rows > 0) {
                    $is_registered = true;
                }
                ?>

                <div class="border border-gray-300 rounded-lg p-4 mb-4">
                    <h3 class="text-xl font-semibold text-gray-800">Shift ID: <?php echo $shift_id; ?></h3>
                    <p><strong>Start Date:</strong> <?php echo $row['shift_start_date']; ?></p>
                    <p><strong>End Date:</strong> <?php echo $row['shift_end_date']; ?></p>
                    <p><strong>Start Time:</strong> <?php echo $row['shift_start_time']; ?></p>
                    <p><strong>End Time:</strong> <?php echo $row['shift_end_time']; ?></p>
                    <p><strong>Location:</strong> <?php echo $row['shift_location']; ?></p>
                    <p><strong>Available Spots:</strong> <?php echo ($row['shift_quota'] - $row['current_registrations']); ?> / <?php echo $row['shift_quota']; ?></p>

                    <?php if ($is_registered): ?>
                        <!-- Show Unregister button if the user is registered -->
                        <form method="POST" action="shift_registration.php?event_id=<?php echo htmlspecialchars($event_id); ?>">
                            <input type="hidden" name="shift_id" value="<?php echo $shift_id; ?>">
                            <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                            <button type="submit" name="unregister_shift" class="mt-4 px-6 py-2 bg-red-500 text-white rounded-lg hover:bg-red-700">Unregister from Shift</button>
                        </form>
                    <?php else: ?>
                        <!-- Show Register button if the user is not registered -->
                        <form method="POST" action="shift_registration.php?event_id=<?php echo htmlspecialchars($event_id); ?>">
                            <input type="hidden" name="shift_id" value="<?php echo $shift_id; ?>">
                            <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                            <button type="submit" name="register_shift" class="mt-4 px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-700">Register for Shift</button>
                        </form>
                    <?php endif; ?>
                </div>
                <?php
            }
        } else {
            echo "<p class='text-red-600'>No shifts available for this event.</p>";
        }
        ?>

    </div>
</body>
</html>

<?php
$conn->close();
?>