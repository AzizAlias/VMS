<?php
session_start();

// Check if the user is logged in as a volunteer
if (!isset($_SESSION['volunteer_id'])) {
    header("Location: log_in_volunteer.php"); // Redirect to login if not logged in
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

// Get the events that the volunteer has registered for
$sql = "SELECT ve.event_id, e.event_name
        FROM event_volunteers ve
        JOIN event_management e ON ve.event_id = e.event_id
        WHERE ve.volunteer_id = ? AND ve.status IN ('registered', 'approved')"; // Include both registered and approved statuses

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $volunteer_id); // Bind volunteer ID
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registered Events</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans">

<?php include 'volunteer_sidebar.php'; ?>
    <div class="main-content min-h-screen">
        <div class="max-w-3xl mx-auto p-6 bg-white shadow-lg rounded-lg mt-10">
        <h1 class="text-3xl font-bold text-center mb-8 text-gray-800">Registered Events</h1>

        <?php
        if ($result->num_rows > 0) {
            // Loop through the events and display each one
            while ($row = $result->fetch_assoc()) {
                echo "
                <div class='border border-gray-300 rounded-lg p-4 mb-4'>
                    <h3 class='text-xl font-semibold text-gray-800'>{$row['event_name']}</h3>
                    <a href='view_event_shift.php?event_id={$row['event_id']}' class='text-blue-500 hover:underline'>View Available Shifts</a>
                </div>";
            }
        } else {
            echo "<p class='text-red-600'>You have not registered for any events.</p>";
        }
        ?>

    </div>
</body>
</html>

<?php
$conn->close();
?>
