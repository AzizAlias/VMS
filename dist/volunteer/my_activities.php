<?php
session_start();
// Include the config file
require_once 'config.php';

// Check if the user is logged in as a volunteer
if (!isset($_SESSION['volunteer_id'])) {
    header("Location: log_in_volunteer.php");
    exit();
}

// Fetch the volunteer ID from the session
$volunteer_id = $_SESSION['volunteer_id'];

// Handle unregister request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['unregister'])) {
    $event_id = $_POST['event_id'];

    // Start transaction to ensure data consistency
    $conn->begin_transaction();

    try {
        // Check if the volunteer has accepted an invitation or registered for the event
        $check_query = "
            SELECT status 
            FROM event_invitations 
            WHERE volunteer_id = ? AND event_id = ? AND status = 'accepted'
            UNION
            SELECT status 
            FROM event_volunteers 
            WHERE volunteer_id = ? AND event_id = ?
        ";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("iiii", $volunteer_id, $event_id, $volunteer_id, $event_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Proceed with unregistering
            // Check if the volunteer is registered for any shifts
            $check_shifts_query = "
                SELECT shift_id 
                FROM shift_registration 
                WHERE volunteer_id = ? 
                AND shift_id IN (
                    SELECT shift_id FROM shift WHERE event_id = ?
                )
            ";
            $stmt = $conn->prepare($check_shifts_query);
            $stmt->bind_param("ii", $volunteer_id, $event_id);
            $stmt->execute();
            $shift_result = $stmt->get_result();

            if ($shift_result->num_rows > 0) {
                // Unregister from each shift
                while ($row = $shift_result->fetch_assoc()) {
                    $shift_id = $row['shift_id'];

                    // Delete the volunteer from the shift
                    $delete_shift_volunteer_query = "
                        DELETE FROM shift_registration 
                        WHERE volunteer_id = ? AND shift_id = ?
                    ";
                    $delete_stmt = $conn->prepare($delete_shift_volunteer_query);
                    $delete_stmt->bind_param("ii", $volunteer_id, $shift_id);
                    if (!$delete_stmt->execute()) {
                        throw new Exception("Failed to delete volunteer from shift ID: $shift_id");
                    }

                    // Decrement the shift's current registrations
                    $update_shift_quota_query = "
                        UPDATE shift 
                        SET current_registrations = current_registrations - 1 
                        WHERE shift_id = ? AND current_registrations > 0
                    ";
                    $update_stmt = $conn->prepare($update_shift_quota_query);
                    $update_stmt->bind_param("i", $shift_id);
                    if (!$update_stmt->execute()) {
                        throw new Exception("Failed to update quota for shift ID: $shift_id");
                    }
                }
            }

            // Delete the volunteer's event registration
            $delete_event_query = "
                DELETE FROM event_volunteers 
                WHERE volunteer_id = ? AND event_id = ?
            ";
            $stmt = $conn->prepare($delete_event_query);
            $stmt->bind_param("ii", $volunteer_id, $event_id);
            $stmt->execute();

            // Remove invitation status if it exists
            $delete_invitation_query = "
                DELETE FROM event_invitations 
                WHERE volunteer_id = ? AND event_id = ?
            ";
            $stmt = $conn->prepare($delete_invitation_query);
            $stmt->bind_param("ii", $volunteer_id, $event_id);
            $stmt->execute();

            // Increase event quota
            $update_event_quota_query = "
                UPDATE event_management
                SET event_quota = event_quota + 1
                WHERE event_id = ?
            ";
            $stmt = $conn->prepare($update_event_quota_query);
            $stmt->bind_param("i", $event_id);
            $stmt->execute();

            // Commit transaction
            $conn->commit();

            echo "<script>alert('You have successfully unregistered from the event.'); window.location.href='my_activities.php';</script>";
        } else {
            echo "<script>alert('You are not registered or have not accepted an invitation for this event.');</script>";
        }
    } catch (Exception $e) {
        // Rollback transaction on failure
        $conn->rollback();
        echo "<script>alert('An error occurred while unregistering. Please try again.');</script>";
    }
}

// Fetch events the volunteer has joined or accepted invitations
$query = "
    SELECT e.event_id, e.event_name, e.event_date, e.event_location, e.event_description, e.event_status, 
           ev.status AS volunteer_status, ei.status AS invitation_status
    FROM event_management e
    LEFT JOIN event_volunteers ev ON e.event_id = ev.event_id AND ev.volunteer_id = ?
    LEFT JOIN event_invitations ei ON e.event_id = ei.event_id AND ei.volunteer_id = ?
    WHERE (ev.volunteer_id IS NOT NULL AND ev.status = 'APPROVED') 
       OR (ei.volunteer_id IS NOT NULL AND ei.status = 'accepted')
    ORDER BY e.event_date ASC;
";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $volunteer_id, $volunteer_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <title>My Activities</title>
</head>
<body class="bg-gray-100 min-h-screen font-sans">
    <!-- Sidebar -->
    <?php include 'volunteer_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="p-6 lg:ml-64 transition-all duration-300"> <!-- Adjusted margin-left and padding -->
        <div class="container mx-auto">
            <h1 class="text-2xl font-bold text-gray-800 mb-6 text-center">My Activities</h1>
            <?php if ($result->num_rows > 0): ?>
                <div class="overflow-x-auto bg-white shadow-md rounded-lg">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-3">Event Name</th>
                                <th scope="col" class="px-4 py-3">Date</th>
                                <th scope="col" class="px-4 py-3">Location</th>
                                <th scope="col" class="px-4 py-3">Description</th>
                                <th scope="col" class="px-4 py-3">Status</th>
                                <th scope="col" class="px-4 py-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr class="bg-white border-b hover:bg-gray-50">
                                    <td class="px-4 py-4 font-medium text-gray-900"><?= htmlspecialchars($row['event_name']) ?></td>
                                    <td class="px-4 py-4"><?= htmlspecialchars($row['event_date']) ?></td>
                                    <td class="px-4 py-4"><?= htmlspecialchars($row['event_location']) ?></td>
                                    <td class="px-4 py-4"><?= htmlspecialchars($row['event_description']) ?></td>
                                    <td class="px-4 py-4">
                                        <?php
                                        // Map event status to Flowbite badge classes
                                        $status = strtolower($row['event_status']);
                                        $badge_class = '';
                                        switch ($status) {
                                            case 'open':
                                                $badge_class = 'bg-blue-100 text-blue-800 text-sm font-medium px-2.5 py-0.5 rounded';
                                                break;
                                            case 'ongoing':
                                                $badge_class = 'bg-green-100 text-green-800 text-sm font-medium px-2.5 py-0.5 rounded';
                                                break;
                                            case 'finished':
                                                $badge_class = 'bg-gray-100 text-gray-800 text-sm font-medium px-2.5 py-0.5 rounded';
                                                break;
                                            case 'cancel':
                                                $badge_class = 'bg-red-100 text-red-800 text-sm font-medium px-2.5 py-0.5 rounded';
                                                break;
                                            default:
                                                $badge_class = 'bg-yellow-100 text-yellow-800 text-sm font-medium px-2.5 py-0.5 rounded';
                                                break;
                                        }
                                        ?>
                                        <span class="<?= $badge_class ?>"><?= htmlspecialchars($row['event_status']) ?></span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <?php if ($row['event_status'] == 'OPEN'): ?>
                                            <!-- Button to trigger the confirmation modal -->
                                            <button type="button" data-modal-target="confirm-unregister-modal" data-modal-toggle="confirm-unregister-modal" data-event-id="<?= htmlspecialchars($row['event_id']) ?>" class="bg-red-500 text-white px-3 py-1.5 md:px-4 md:py-2 rounded hover:bg-red-600 text-sm md:text-base">
                                                Unregister
                                            </button>
                                        <?php else: ?>
                                            <span class="text-gray-500 text-sm md:text-base">No action available</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-600 text-center mt-6">You have not registered for any activities yet.</p>
            <?php endif; ?>
            <?php $conn->close(); ?>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirm-unregister-modal" tabindex="-1" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative p-4 w-full max-w-md max-h-full">
            <div class="relative bg-white rounded-lg shadow">
                <!-- Modal header -->
                <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                    <h3 class="text-xl font-semibold text-gray-900">Confirm Unregister</h3>
                    <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="confirm-unregister-modal">
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                        </svg>
                        <span class="sr-only">Close modal</span>
                    </button>
                </div>
                <!-- Modal body -->
                <div class="p-4 md:p-5">
                    <p class="text-gray-600">Are you sure you want to unregister from this event?</p>
                </div>
                <!-- Modal footer -->
                <div class="flex items-center p-4 md:p-5 border-t border-gray-200 rounded-b">
                    <form id="unregister-form" method="POST" action="">
                        <input type="hidden" name="event_id" id="event-id-input">
                        <button type="submit" name="unregister" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Yes, Unregister</button>
                        <button type="button" data-modal-hide="confirm-unregister-modal" class="ml-2 bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Cancel</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
    <script>
        // Add event listeners to all unregister buttons
        document.querySelectorAll('[data-modal-target="confirm-unregister-modal"]').forEach(button => {
            button.addEventListener('click', () => {
                const eventId = button.getAttribute('data-event-id');
                document.getElementById('event-id-input').value = eventId;
            });
        });
    </script>
</body>
</html>