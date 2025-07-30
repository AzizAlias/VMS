<?php
session_start();

require_once 'config.php';

// Check if the volunteer is logged in
if (!isset($_SESSION['volunteer_id'])) {
    header("Location: log_in_volunteer.php");
    exit();
}

// Get logged-in volunteer ID
$volunteer_id = $_SESSION['volunteer_id'];

// Fetch invitations for the volunteer
$query = "
    SELECT i.invitation_id, e.event_name, e.event_date, e.event_location, i.status
    FROM event_invitations i
    JOIN event_management e ON i.event_id = e.event_id
    WHERE i.volunteer_id = '$volunteer_id'
";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Invitations</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans">
    <?php include 'volunteer_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="p-6 lg:pl-80 transition-all duration-300">
        <!-- Ensure the container takes up the full available width -->
        <div class="w-full">
            <!-- Center the max-w-4xl container within the available space -->
            <div class="max-w-4xl mx-auto p-4 md:p-6 bg-white shadow-lg rounded-lg">
                <h1 class="text-3xl font-bold text-center mb-8 text-gray-800">View Event Invitations</h1>

                <?php if (isset($_GET['message'])) { ?>
                    <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">
                        <?php echo htmlspecialchars($_GET['message']); ?>
                    </div>
                <?php } ?>

                <?php if ($result->num_rows > 0) { ?>
                    <table class="min-w-full bg-white border border-gray-300 rounded-lg">
                        <thead>
                            <tr class="bg-gray-200">
                                <th class="p-2 text-left">Event</th>
                                <th class="p-2 text-left">Date</th>
                                <th class="p-2 text-left">Location</th>
                                <th class="p-2 text-left">Status</th>
                                <th class="p-2 text-left">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()) { ?>
                                <tr>
                                    <td class="p-2"><?php echo $row['event_name']; ?></td>
                                    <td class="p-2"><?php echo $row['event_date']; ?></td>
                                    <td class="p-2"><?php echo $row['event_location']; ?></td>
                                    <td class="p-2"><?php echo ucfirst($row['status']); ?></td>
                                    <td class="p-2">
                                        <?php if ($row['status'] == 'pending') { ?>
                                            <a href="update_invitation_status.php?invitation_id=<?php echo $row['invitation_id']; ?>&status=accepted" 
                                               class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">
                                                Accept
                                            </a>
                                            <a href="update_invitation_status.php?invitation_id=<?php echo $row['invitation_id']; ?>&status=declined" 
                                               class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">
                                                Reject
                                            </a>
                                        <?php } else { ?>
                                            <span class="text-gray-500">No Action Available</span>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } else { ?>
                    <p class="text-gray-600">No invitations received yet.</p>
                <?php } ?>
            </div>
        </div>
    </div>

    <!-- Include Flowbite JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>