<?php
session_start();
// Include the config file
require_once '../volunteer/config.php';

// Check if the user is logged in as an organizer
if (!isset($_SESSION['organizer_id'])) {
    header("Location: log_in_organizer.php");
    exit();
}

// Get the organizer ID from the session
$organizer_id = $_SESSION['organizer_id'];

// Fetch events posted by this organizer
$query = "SELECT * FROM event_management WHERE organizer_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $organizer_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events</title>
    <!-- Include Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Include Flowbite CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet" />
</head>
<body class="bg-gray-100 text-gray-800">

    <!-- Include Organizer Sidebar -->
    <?php include 'organizer_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="ml-72 p-6">
        <div class="max-w-full mx-auto py-10">
            <h1 class="text-3xl font-bold text-center text-gray-800 mb-6">Manage Events</h1>

            <!-- Responsive Table Container -->
            <div class="overflow-x-auto bg-white rounded-lg shadow-md">
                <table class="min-w-full table-auto">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="py-2 px-4 text-left text-gray-700 whitespace-nowrap">Event Name</th>
                            <th class="py-2 px-4 text-left text-gray-700 whitespace-nowrap">Description</th>
                            <th class="py-2 px-4 text-left text-gray-700 whitespace-nowrap">Date</th>
                            <th class="py-2 px-4 text-left text-gray-700 whitespace-nowrap">Quota</th>
                            <th class="py-2 px-4 text-left text-gray-700 whitespace-nowrap">Status</th>
                            <th class="py-2 px-4 text-left text-gray-700 whitespace-nowrap">Quota Status</th>
                            <th class="py-2 px-4 text-left text-gray-700 whitespace-nowrap">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($event = $result->fetch_assoc()) : ?>
                            <tr class="border-b">
                                <td class="py-2 px-4 whitespace-nowrap"><?php echo htmlspecialchars($event['event_name']); ?></td>
                                <td class="py-2 px-4 whitespace-nowrap"><?php echo htmlspecialchars($event['event_description']); ?></td>
                                <td class="py-2 px-4 whitespace-nowrap"><?php echo htmlspecialchars($event['event_date']); ?></td>
                                <td class="py-2 px-4 whitespace-nowrap"><?php echo htmlspecialchars($event['event_quota']); ?></td>
                                <td class="py-2 px-4 whitespace-nowrap"><?php echo htmlspecialchars($event['event_status']); ?></td>
                                <td class="py-2 px-4 whitespace-nowrap"><?php echo htmlspecialchars($event['event_quota_status']); ?></td>
                                <td class="py-2 px-4 whitespace-nowrap text-center space-x-2">
                                    <form action="edit_event.php" method="GET" class="inline-block">
                                        <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                                        <button type="submit" class="bg-blue-500 text-white py-1 px-4 rounded hover:bg-blue-600">Edit</button>
                                    </form>
                                    <form action="delete_event.php" onClick="return confirm('Are you sure you want to delete this event?');" class="inline-block">
                                        <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                                        <button type="submit" class="bg-red-500 text-white py-1 px-4 rounded hover:bg-red-600">Delete</button>
                                    </form>
                                    <a href="view_volunteers_list.php?event_id=<?php echo $event['event_id']; ?>" class="bg-green-500 text-white py-1 px-4 rounded hover:bg-green-600 inline-block">View Volunteers</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Include Flowbite JS (for interactive components) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>