<?php
session_start();
// Include the config file
require_once '../volunteer/config.php';

// Check if the user is logged in as an organizer
if (!isset($_SESSION['organizer_id'])) {
    header("Location: log_in_organizer.php");
    exit();
}


// Organizer ID from session
$organizer_id = $_SESSION['organizer_id'];

// Fetch pending registrations for the organizer's events
$query = "
    SELECT er.event_volunteer_id, v.volunteer_name AS volunteer_name, e.event_name, er.status, er.event_id
    FROM event_volunteers er
    JOIN event_management e ON er.event_id = e.event_id
    JOIN volunteer v ON er.volunteer_id = v.volunteer_id
    WHERE e.organizer_id = ? AND er.status = 'PENDING'
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $organizer_id);
$stmt->execute();
$registrations_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Registrations</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal">
<?php include 'organizer_sidebar.php'; ?>

<!-- Main Content -->
<div class="p-4 lg:ml-72"> <!-- Adjusted padding to account for sidebar -->
    <div class="container mx-auto mt-10">
        <h1 class="text-3xl font-bold text-center mb-8 text-gray-800">Manage Volunteer Applications</h1>

        <div class="bg-white shadow-md rounded p-6">
            <?php if ($registrations_result->num_rows > 0) { ?>
                <!-- Wrap the table in a scrollable container -->
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-3">Volunteer Name</th>
                                <th scope="col" class="px-4 py-3">Event Name</th>
                                <th scope="col" class="px-4 py-3">Status</th>
                                <th scope="col" class="px-4 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($registration = $registrations_result->fetch_assoc()) { ?>
                                <tr class="bg-white border-b hover:bg-gray-50">
                                    <td class="px-4 py-3"><?php echo htmlspecialchars($registration['volunteer_name']); ?></td>
                                    <td class="px-4 py-3"><?php echo htmlspecialchars($registration['event_name']); ?></td>
                                    <td class="px-4 py-3"><?php echo htmlspecialchars($registration['status']); ?></td>
                                    <td class="px-4 py-3 flex space-x-2">
                                        <!-- Approve Button -->
                                        <form action="update_registration_status.php" method="POST" class="inline">
                                            <input type="hidden" name="event_volunteer_id" value="<?php echo $registration['event_volunteer_id']; ?>">
                                            <input type="hidden" name="event_id" value="<?php echo $registration['event_id']; ?>">
                                            <input type="hidden" name="action" value="APPROVE">
                                            <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-1 px-3 rounded">
                                                Approve
                                            </button>
                                        </form>
                                        <!-- Reject Button -->
                                        <form action="update_registration_status.php" method="POST" class="inline">
                                            <input type="hidden" name="event_volunteer_id" value="<?php echo $registration['event_volunteer_id']; ?>">
                                            <input type="hidden" name="action" value="REJECT">
                                            <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-3 rounded">
                                                Reject
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php } else { ?>
                <p class="text-gray-600 text-center">No pending applications.</p>
            <?php } ?>
        </div>
    </div>
</div>

<!-- Include Flowbite JavaScript -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
</body>
</html>
<?php

?>
