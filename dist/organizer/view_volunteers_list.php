<?php
session_start();
// Include the config file
require_once '../volunteer/config.php';

// Check if the user is logged in as an organizer
if (!isset($_SESSION['organizer_id'])) {
    header("Location: log_in_organizer.php");
    exit();
}

// Check if event_id is provided
if (!isset($_GET['event_id'])) {
    die("Event ID not specified.");
}

$event_id = $_GET['event_id'];

// Fetch volunteers registered for this event along with their attendance status
$query = "
    SELECT v.volunteer_id, v.volunteer_name, v.volunteer_email, v.volunteer_phone_number, a.attendance_status
    FROM event_volunteers er
    JOIN volunteer v ON v.volunteer_id = er.volunteer_id
    LEFT JOIN event_attendance a ON a.volunteer_id = v.volunteer_id AND a.event_id = er.event_id
    WHERE er.event_id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();

// Fetch event details
$event_query = "SELECT event_name FROM event_management WHERE event_id = ?";
$event_stmt = $conn->prepare($event_query);
$event_stmt->bind_param("i", $event_id);
$event_stmt->execute();
$event_result = $event_stmt->get_result();
$event = $event_result->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registered Volunteers</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.6.5/flowbite.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800">

<!-- Sidebar -->
<?php include 'organizer_sidebar.php'; ?>

<!-- Main Content -->
<div class="p-4 sm:ml-64">
    <div class="max-w-6xl mx-auto py-6">
        <h1 class="text-3xl font-bold text-center text-gray-800 mb-6">
            Volunteers Registered for "<?php echo htmlspecialchars($event['event_name']); ?>"
        </h1>

        <?php if ($result->num_rows > 0): ?>
            <div class="overflow-x-auto bg-white rounded-lg shadow-md">
                <table class="min-w-full table-auto">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="py-3 px-6 text-left text-sm font-medium text-gray-700 uppercase tracking-wider">#</th>
                            <th class="py-3 px-6 text-left text-sm font-medium text-gray-700 uppercase tracking-wider">Name</th>
                            <th class="py-3 px-6 text-left text-sm font-medium text-gray-700 uppercase tracking-wider">Email</th>
                            <th class="py-3 px-6 text-left text-sm font-medium text-gray-700 uppercase tracking-wider">Phone</th>
                            <th class="py-3 px-6 text-left text-sm font-medium text-gray-700 uppercase tracking-wider">Attendance Status</th>
                            <th class="py-3 px-6 text-left text-sm font-medium text-gray-700 uppercase tracking-wider">Action</th> <!-- New column for the Chat button -->
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php $counter = 1; // Initialize a counter variable ?>
                        <?php while ($volunteer = $result->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="py-4 px-6"><?php echo $counter++; ?></td> <!-- Display the counter -->
                                <td class="py-4 px-6"><?php echo htmlspecialchars($volunteer['volunteer_name']); ?></td>
                                <td class="py-4 px-6"><?php echo htmlspecialchars($volunteer['volunteer_email']); ?></td>
                                <td class="py-4 px-6"><?php echo htmlspecialchars($volunteer['volunteer_phone_number']); ?></td>
                                <td class="py-4 px-6">
                                    <?php
                                    $attendanceStatus = $volunteer['attendance_status'] ?? 'Not Marked';
                                    $statusColor = $attendanceStatus === 'Present' ? 'bg-green-100 text-green-800' :
                                                  ($attendanceStatus === 'Absent' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800');
                                    ?>
                                    <span class="px-3 py-1 text-sm font-semibold rounded-full <?php echo $statusColor; ?>">
                                        <?php echo htmlspecialchars($attendanceStatus); ?>
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <!-- Chat button -->
                                    <td class="py-4 px-6">
                                    <a href="../api/organizer_start_chat.php?volunteer_id=<?php echo $volunteer['volunteer_id']; ?>" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                                        Chat
                                    </a>
                                </td>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-center text-gray-600">No volunteers have registered for this event yet.</p>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.6.5/flowbite.min.js"></script>
</body>
</html>