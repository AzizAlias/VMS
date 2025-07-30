<?php
session_start();
// Include the config file
require_once 'config.php';

// Check if the user is logged in as a volunteer
if (!isset($_SESSION['volunteer_id'])) {
    header("Location: log_in_volunteer.php");
    exit();
}


$volunteer_id = $_SESSION['volunteer_id']; // Get the logged-in volunteer's ID

// Fetch shifts the volunteer has registered for
$sql = "SELECT s.shift_id, s.shift_start_date, s.shift_end_date, s.shift_start_time, s.shift_end_time, s.shift_location, s.shift_quota, s.current_registrations
        FROM shift s
        INNER JOIN shift_registration sr ON s.shift_id = sr.shift_id
        WHERE sr.volunteer_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $volunteer_id);
$stmt->execute();
$result = $stmt->get_result();

// Handle cancellation request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_shift'])) {
    $shift_id = $_POST['shift_id'];

    // Remove the registration
    $delete_sql = "DELETE FROM shift_registration WHERE shift_id = ? AND volunteer_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("ii", $shift_id, $volunteer_id);

    if ($delete_stmt->execute()) {
        // Decrement the current registrations in the shift table
        $update_quota_sql = "UPDATE shift SET current_registrations = current_registrations - 1 WHERE shift_id = ?";
        $update_stmt = $conn->prepare($update_quota_sql);
        $update_stmt->bind_param("i", $shift_id);
        $update_stmt->execute();

        echo "<p class='text-green-600'>Successfully canceled the registration for shift ID $shift_id.</p>";
    } else {
        echo "<p class='text-red-600'>Failed to cancel the registration. Please try again.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Registered Shifts</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans">
    <div class="max-w-3xl mx-auto p-6 bg-white shadow-lg rounded-lg mt-10">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">Manage Registered Shifts</h2>

        <?php
        if ($result->num_rows > 0) {
            // Loop through the registered shifts and display each one
            while ($row = $result->fetch_assoc()) {
                ?>
                <div class='border border-gray-300 rounded-lg p-4 mb-4'>
                    <h3 class='text-xl font-semibold text-gray-800'>Shift ID: <?php echo $row['shift_id']; ?></h3>
                    <p><strong>Start Date:</strong> <?php echo $row['shift_start_date']; ?></p>
                    <p><strong>End Date:</strong> <?php echo $row['shift_end_date']; ?></p>
                    <p><strong>Start Time:</strong> <?php echo $row['shift_start_time']; ?></p>
                    <p><strong>End Time:</strong> <?php echo $row['shift_end_time']; ?></p>
                    <p><strong>Location:</strong> <?php echo $row['shift_location']; ?></p>
                    <p><strong>Registered Spots:</strong> <?php echo $row['current_registrations']; ?> / <?php echo $row['shift_quota']; ?></p>

                    <form method="POST" action="unregister_shift.php">
                    <input type="hidden" name="shift_id" value="<?php echo $row['shift_id']; ?>">
                    <input type="hidden" name="volunteer_id" value="<?php echo $_SESSION['volunteer_id']; ?>">
                    <button type="submit" name="unregister" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-700">
                        Unregister
                    </button>
</form>
                </div>
                <?php
            }
        } else {
            echo "<p class='text-red-600'>You have not registered for any shifts yet.</p>";
        }
        ?>

    </div>
</body>
</html>

<?php
$conn->close();
?>
