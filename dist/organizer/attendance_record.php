<?php
session_start();
// Include the config file
require_once '../volunteer/config.php';

// Check if the user is logged in as an organizer
if (!isset($_SESSION['organizer_id'])) {
    header("Location: log_in_organizer.php");
    exit();
}

// Fetch events created by the organizer with status 'Ongoing'
$organizer_id = $_SESSION['organizer_id'];
$events_query = "SELECT event_id, event_name FROM event_management WHERE organizer_id = '$organizer_id' AND event_status = 'Ongoing'";
$events_result = $conn->query($events_query);

// Fetch shifts and attendance records if an event is selected
$selected_event = isset($_POST['event_id']) ? $_POST['event_id'] : (isset($_GET['event_id']) ? $_GET['event_id'] : null);
$selected_shift = isset($_POST['shift_id']) ? $_POST['shift_id'] : (isset($_GET['shift_id']) ? $_GET['shift_id'] : null);

// Search query for volunteer name
$search_query = isset($_POST['search_volunteer']) ? $_POST['search_volunteer'] : '';

$shifts = [];
$attendance_records = [];

if ($selected_event) {
    // Fetch the event status to ensure it's 'Ongoing'
    $event_status_query = "SELECT event_status FROM event_management WHERE event_id = '$selected_event'";
    $event_status_result = $conn->query($event_status_query);
    $event_status = $event_status_result->fetch_assoc()['event_status'];

    if ($event_status === 'ONGOING') {
        $shifts_query = "SELECT shift_id, shift_start_date, shift_end_date, shift_start_time, shift_end_time FROM shift WHERE event_id = '$selected_event'";
        $shifts = $conn->query($shifts_query);

        if ($selected_shift) {
            $attendance_query = "
            SELECT ev.volunteer_id, v.volunteer_name, ea.attendance_status, ea.check_in_time, ea.check_out_time, ea.remarks
            FROM event_volunteers ev
            INNER JOIN volunteer v ON ev.volunteer_id = v.volunteer_id
            INNER JOIN shift_registration sv ON sv.volunteer_id = ev.volunteer_id AND sv.shift_id = '$selected_shift'
            LEFT JOIN event_attendance ea ON sv.volunteer_id = ea.volunteer_id AND sv.shift_id = ea.shift_id
            WHERE ev.event_id = '$selected_event'
            ";

            // Add search filter if a search query is provided
            if (!empty($search_query)) {
                $attendance_query .= " AND v.volunteer_name LIKE '%$search_query%'";
            }

            $attendance_records = $conn->query($attendance_query);
        }
    } else {
        // Event is not ongoing, so no attendance can be updated
        $attendance_records = null;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_individual_attendance'])) {
    $volunteer_id = $_POST['volunteer_id'];
    $attendance_status = $_POST['attendance_status'];
    $check_in_time = $_POST['check_in_time'] ?? null;
    $check_out_time = $_POST['check_out_time'] ?? null;
    $remarks = $_POST['remarks'] ?? null;

    // Check if the volunteer_id exists in the volunteer table
    $check_volunteer_query = "SELECT volunteer_id FROM volunteer WHERE volunteer_id = '$volunteer_id'";
    $check_volunteer_result = $conn->query($check_volunteer_query);

    if ($check_volunteer_result->num_rows > 0) {
        $query = "
            INSERT INTO event_attendance (event_id, shift_id, volunteer_id, attendance_status, check_in_time, check_out_time, remarks)
            VALUES ('$selected_event', '$selected_shift', '$volunteer_id', '$attendance_status', '$check_in_time', '$check_out_time', '$remarks')
            ON DUPLICATE KEY UPDATE
                attendance_status = '$attendance_status',
                check_in_time = '$check_in_time',
                check_out_time = '$check_out_time',
                remarks = '$remarks';
        ";
        $conn->query($query);

        // Optionally, add a success message
        $_SESSION['attendance_updated'] = true;
        // Keep the selected event and shift in the form after the update
        header("Location: attendance_record.php?event_id=$selected_event&shift_id=$selected_shift"); 
        exit();
    } else {
        // Volunteer does not exist
        $_SESSION['attendance_error'] = "Volunteer ID does not exist.";
        header("Location: attendance_record.php?event_id=$selected_event&shift_id=$selected_shift"); 
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Record</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal">

<?php include 'organizer_sidebar.php'; ?>

<div class="main-content ml-72 p-6">
    <h1 class="text-3xl font-bold text-center mb-8">Attendance Record</h1>
    
    <!-- Success Message Popup -->
    <?php if (isset($_SESSION['attendance_updated']) && $_SESSION['attendance_updated']) { ?>
        <div id="alert-3" class="flex items-center p-4 mb-4 text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400" role="alert">
            <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
            </svg>
            <span class="sr-only">Info</span>
            <div class="ms-3 text-sm font-medium">
                Attendance successfully updated!
            </div>
            <button type="button" class="ms-auto -mx-1.5 -my-1.5 bg-green-50 text-green-500 rounded-lg focus:ring-2 focus:ring-green-400 p-1.5 hover:bg-green-200 inline-flex items-center justify-center h-8 w-8 dark:bg-gray-800 dark:text-green-400 dark:hover:bg-gray-700" data-dismiss-target="#alert-3" aria-label="Close">
                <span class="sr-only">Close</span>
                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                </svg>
            </button>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                setTimeout(function() {
                    const alertBox = document.getElementById('alert-3');
                    if (alertBox) {
                        alertBox.style.display = 'none';
                    }
                }, 3000); // Auto-hide after 3 seconds

                const closeButton = document.querySelector('[data-dismiss-target="#alert-3"]');
                if (closeButton) {
                    closeButton.addEventListener('click', function() {
                        const alertBox = document.getElementById('alert-3');
                        if (alertBox) {
                            alertBox.style.display = 'none';
                        }
                    });
                }
            });
        </script>

        <?php unset($_SESSION['attendance_updated']); // Clear the session flag ?>
    <?php } ?>

    <!-- Error Message Popup -->
    <?php if (isset($_SESSION['attendance_error'])) { ?>
        <div id="alert-error" class="flex items-center p-4 mb-4 text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400" role="alert">
            <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
            </svg>
            <span class="sr-only">Error</span>
            <div class="ms-3 text-sm font-medium">
                <?php echo htmlspecialchars($_SESSION['attendance_error']); ?>
            </div>
            <button type="button" class="ms-auto -mx-1.5 -my-1.5 bg-red-50 text-red-500 rounded-lg focus:ring-2 focus:ring-red-400 p-1.5 hover:bg-red-200 inline-flex items-center justify-center h-8 w-8 dark:bg-gray-800 dark:text-red-400 dark:hover:bg-gray-700" data-dismiss-target="#alert-error" aria-label="Close">
                <span class="sr-only">Close</span>
                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                </svg>
            </button>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                setTimeout(function() {
                    const alertBox = document.getElementById('alert-error');
                    if (alertBox) {
                        alertBox.style.display = 'none';
                    }
                }, 3000); // Auto-hide after 3 seconds

                const closeButton = document.querySelector('[data-dismiss-target="#alert-error"]');
                if (closeButton) {
                    closeButton.addEventListener('click', function() {
                        const alertBox = document.getElementById('alert-error');
                        if (alertBox) {
                            alertBox.style.display = 'none';
                        }
                    });
                }
            });
        </script>

        <?php unset($_SESSION['attendance_error']); // Clear the session flag ?>
    <?php } ?>

    <!-- Select Event and Shift -->
    <form method="POST" class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <select name="event_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" onchange="this.form.submit()">
                <option value="">Select Event</option>
                <?php while ($event = $events_result->fetch_assoc()) { ?>
                    <option value="<?php echo $event['event_id']; ?>" <?php echo ($selected_event == $event['event_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($event['event_name']); ?>
                    </option>
                <?php } ?>
            </select>

            <?php if (!empty($shifts)) { ?>
                <select name="shift_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" onchange="this.form.submit()">
                    <option value="">Select Shift</option>
                    <?php while ($shift = $shifts->fetch_assoc()) { ?>
                        <option value="<?php echo $shift['shift_id']; ?>" <?php echo ($selected_shift == $shift['shift_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($shift['shift_start_date'] . ' - ' . $shift['shift_start_time']); ?>
                        </option>
                    <?php } ?>
                </select>
            <?php } ?>
        </div>
    </form>

    <!-- Search Volunteer Name -->
    <?php if ($selected_event && $selected_shift) { ?>
        <form method="POST" class="mb-6">
            <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($selected_event); ?>">
            <input type="hidden" name="shift_id" value="<?php echo htmlspecialchars($selected_shift); ?>">
            <div class="flex items-center">
                <input type="text" name="search_volunteer" placeholder="Search volunteer name..." value="<?php echo htmlspecialchars($search_query); ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                <button type="submit" class="ml-2 text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">Search</button>
            </div>
        </form>
    <?php } ?>

    <!-- Attendance Table -->
    <?php if (!empty($attendance_records) && $attendance_records->num_rows > 0) { ?>
        <form method="POST">
            <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($selected_event); ?>">
            <input type="hidden" name="shift_id" value="<?php echo htmlspecialchars($selected_shift); ?>">
            <div class="overflow-x-auto rounded-lg shadow-md">
                <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">Volunteer Name</th>
                            <th scope="col" class="px-6 py-3">Status</th>
                            <th scope="col" class="px-6 py-3">Check-in Time</th>
                            <th scope="col" class="px-6 py-3">Check-out Time</th>
                            <th scope="col" class="px-6 py-3">Remark</th>
                            <th scope="col" class="px-6 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($record = $attendance_records->fetch_assoc()) { ?>
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                <td class="px-6 py-4"><?php echo htmlspecialchars($record['volunteer_name']); ?></td>
                                <td class="px-6 py-4">
                                    <select name="attendance_status" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                        <option value="Present" <?php echo ($record['attendance_status'] === 'Present') ? 'selected' : ''; ?>>Present</option>
                                        <option value="Absent" <?php echo ($record['attendance_status'] === 'Absent') ? 'selected' : ''; ?>>Absent</option>
                                    </select>
                                </td>
                                <td class="px-6 py-4">
                                    <input type="datetime-local" name="check_in_time" value="<?php echo htmlspecialchars($record['check_in_time']); ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                </td>
                                <td class="px-6 py-4">
                                    <input type="datetime-local" name="check_out_time" value="<?php echo htmlspecialchars($record['check_out_time']); ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                </td>
                                <td class="px-6 py-4">
                                    <textarea name="remarks" rows="2" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"><?php echo htmlspecialchars($record['remarks']); ?></textarea>
                                </td>
                                <td class="px-6 py-4">
                                    <!-- Add a hidden input for volunteer_id -->
                                    <input type="hidden" name="volunteer_id" value="<?php echo htmlspecialchars($record['volunteer_id']); ?>">
                                    <button type="submit" name="update_individual_attendance" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">Update</button>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </form>
    <?php } else { ?>
        <?php if ($selected_event && $event_status !== 'ONGOING') { ?>
            <p class="text-center text-red-500">Attendance cannot be updated for this event because it is not ongoing.</p>
        <?php } else { ?>
            <p class="text-center">No attendance records found for this shift.</p>
        <?php } ?>
    <?php } ?>
</div>

<!-- Include Flowbite JavaScript -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
</body>
</html>