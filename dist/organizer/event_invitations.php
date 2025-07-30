<?php
session_start();
// Include the config file
require_once '../volunteer/config.php';

// Check if the user is logged in as an organizer
if (!isset($_SESSION['organizer_id'])) {
    header("Location: log_in_organizer.php");
    exit();
}

// Get the logged-in organizer's ID
$organizer_id = $_SESSION['organizer_id'];

// Fetch events posted by the organizer that have at least one shift
$events = [];
$event_query = "
    SELECT em.event_id, em.event_name, em.event_date 
    FROM event_management em
    JOIN shift es ON em.event_id = es.event_id
    WHERE em.organizer_id = ?
    GROUP BY em.event_id
    HAVING COUNT(es.shift_id) > 0
";

if ($stmt = $conn->prepare($event_query)) {
    $stmt->bind_param("i", $organizer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
    $stmt->close();
}

// Handle event selection and fetch available volunteers
$available_volunteers = [];
if (isset($_POST['event_id']) && !empty($_POST['event_id'])) {
    $event_id = $_POST['event_id'];

    // Fetch event start date
    $event_query = "SELECT event_date FROM event_management WHERE event_id = ?";
    if ($stmt = $conn->prepare($event_query)) {
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $event_row = $result->fetch_assoc();
            $event_start_date = $event_row['event_date'];

            // Fetch volunteers available on the event start date and check invitation status
            $volunteer_query = "
                SELECT v.volunteer_id, v.volunteer_name, v.volunteer_email, 
                       v.volunteer_phone_number, v.volunteer_availability, v.volunteer_skills,
                       i.status AS invitation_status
                FROM volunteer v
                LEFT JOIN event_invitations i ON v.volunteer_id = i.volunteer_id AND i.event_id = ?
                WHERE v.volunteer_availability LIKE ?
            ";

            if ($stmt = $conn->prepare($volunteer_query)) {
                $search_date = "%$event_start_date%";
                $stmt->bind_param("is", $event_id, $search_date);
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    $available_volunteers[] = $row;
                }
                $stmt->close();
            }
        } else {
            $message = "No volunteers available on this date.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Available Volunteers</title>
    <!-- Include Flowbite CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet" />
</head>
<body class="bg-gray-50 font-sans">

<!-- Sidebar -->
<?php include 'organizer_sidebar.php'; ?>

<div class="p-4 lg:ml-72 pt-12"> 
    <!-- Select Event and Find Available Volunteers -->
    <h1 class="text-3xl font-bold text-center mb-8 text-gray-800">Find Available Volunteers</h1>

    <!-- Select Event Form -->
    <form method="POST" class="space-y-4 mb-8">
        <div>
            <label class="block text-gray-600 mb-2">Select Event:</label>
            <select name="event_id" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required>
                <option value="">-- Select Event --</option>
                <?php foreach ($events as $event) { ?>
                    <option value="<?php echo htmlspecialchars($event['event_id']); ?>">
                        <?php echo htmlspecialchars($event['event_name']); ?> (Starts: <?php echo htmlspecialchars($event['event_date']); ?>)
                    </option>
                <?php } ?>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition duration-200">
            Find Volunteers
        </button>
    </form>

    <?php if (!empty($available_volunteers)) { ?>
        <h3 class="text-xl font-semibold text-gray-800 mb-4">Available Volunteers for <?php echo htmlspecialchars($event_start_date); ?></h3>

        <!-- Volunteer Table -->
        <div class="overflow-x-auto">
            <table class="w-full bg-white border border-gray-200 rounded-lg">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-3 text-left text-sm font-semibold text-gray-600">Volunteer ID</th>
                        <th class="p-3 text-left text-sm font-semibold text-gray-600">Name</th>
                        <th class="p-3 text-left text-sm font-semibold text-gray-600">Availability</th>
                        <th class="p-3 text-left text-sm font-semibold text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($available_volunteers as $volunteer) { ?>
                        <tr class="border-b border-gray-200 hover:bg-gray-50 transition duration-200">
                            <td class="p-3 text-sm text-gray-700"><?php echo htmlspecialchars($volunteer['volunteer_id']); ?></td>
                            <td class="p-3 text-sm text-gray-700">
                                <a href="#" onclick="showVolunteerDetails(<?php echo htmlspecialchars(json_encode($volunteer), ENT_QUOTES, 'UTF-8'); ?>)" class="text-blue-500 hover:underline">
                                    <?php echo htmlspecialchars($volunteer['volunteer_name']); ?>
                                </a>
                            </td>
                            <td class="p-3 text-sm text-gray-700"><?php echo htmlspecialchars($volunteer['volunteer_availability']); ?></td>
                            <td class="p-3">
                                <?php if ($volunteer['invitation_status'] === 'pending' || $volunteer['invitation_status'] === 'accepted') { ?>
                                    <button class="px-4 py-2 bg-gray-300 text-gray-600 rounded-lg cursor-not-allowed" disabled>
                                        Invited
                                    </button>
                                <?php } else { ?>
                                    <a href="#" onclick="sendInvitation(<?php echo htmlspecialchars($volunteer['volunteer_id']); ?>, <?php echo htmlspecialchars($event_id); ?>)" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition duration-200">
                                        Invite
                                    </a>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } elseif (isset($message)) { ?>
        <p class="text-gray-600 mt-4"><?php echo htmlspecialchars($message); ?></p>
    <?php } ?>
</div>

<!-- Volunteer Details Modal -->
<div id="volunteerDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white p-6 rounded-lg shadow-lg w-11/12 md:w-1/3 relative">
        <!-- Close Button (Top Right) -->
        <button onclick="closeModal()" class="absolute top-2 right-2 p-1 text-gray-800 hover:text-gray-600 transition duration-200">
            <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 9-6 6m0-6 6 6m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
            </svg>
        </button>

        <h3 class="text-xl font-semibold text-gray-800 mb-4">Volunteer Details</h3>
        <div id="volunteerDetailsContent" class="max-h-96 overflow-y-auto">
            <!-- Volunteer details will be populated here -->
        </div>
    </div>
</div>

<!-- Include Flowbite JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>

<script>
    function showVolunteerDetails(volunteer) {
        const modal = document.getElementById('volunteerDetailsModal');
        const content = document.getElementById('volunteerDetailsContent');

        // Populate the modal with volunteer details
        content.innerHTML = `
            <p><strong>Name:</strong> ${volunteer.volunteer_name}</p>
            <p><strong>Email:</strong> ${volunteer.volunteer_email}</p>
            <p><strong>Phone Number:</strong> ${volunteer.volunteer_phone_number}</p>
            <p><strong>Availability:</strong> ${volunteer.volunteer_availability}</p>
            <p><strong>Skills:</strong> ${volunteer.volunteer_skills}</p>
        `;

        // Show the modal
        modal.classList.remove('hidden');
    }

    function closeModal() {
        const modal = document.getElementById('volunteerDetailsModal');
        modal.classList.add('hidden');
    }

    function sendInvitation(volunteerId, eventId) {
        fetch(`send_invitations.php?volunteer_id=${volunteerId}&event_id=${eventId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Invitation sent successfully!');
                    window.location.reload(); // Reload the page to update the UI
                } else {
                    alert(data.message || 'Failed to send invitation.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while sending the invitation.');
            });
    }
</script>

</body>
</html>

<?php
$conn->close();
?>