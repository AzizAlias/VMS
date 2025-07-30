<?php
session_start();
require_once 'config.php';

// Check if the user is logged in as a volunteer
if (!isset($_SESSION['volunteer_id'])) {
    header("Location: log_in_volunteer.php");
    exit();
}

// Initialize search variables
$search_keyword = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_date = isset($_GET['date']) ? trim($_GET['date']) : '';

// Construct the SQL query with case-insensitive search
$query = "
    SELECT DISTINCT em.* 
    FROM event_management em
    INNER JOIN shift s ON em.event_id = s.event_id
    WHERE em.event_quota_status = 'AVAILABLE'
    AND em.event_status = 'OPEN'
    AND em.event_quota > 0 
";

// Add search conditions
if (!empty($search_keyword)) {
    $search_keyword = $conn->real_escape_string($search_keyword);
    $query .= " AND (LOWER(em.event_name) LIKE LOWER('%$search_keyword%') 
                OR LOWER(em.event_description) LIKE LOWER('%$search_keyword%') 
                OR LOWER(em.required_skills) LIKE LOWER('%$search_keyword%'))";
}

if (!empty($search_date)) {
    $query .= " AND em.event_date = '$search_date'";
}

$events_result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Events</title>
    <!-- Include Flowbite CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet">
    <!-- Include Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Include jQuery for AJAX -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</head>
<body class="font-sans leading-normal tracking-normal">

    <?php include 'volunteer_sidebar.php'; ?>

    <div class="main-content lg:ml-72 p-6 bg-white transition-all duration-300">
        <h1 class="text-3xl font-bold text-center mb-8 text-gray-800">Available Events</h1>

        <!-- Search Form -->
        <form method="GET" class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search_keyword); ?>" 
                    placeholder="Search by name, description, or skills" 
                    class="border rounded px-4 py-2 w-full">
                <input type="date" name="date" value="<?php echo htmlspecialchars($search_date); ?>" 
                    class="border rounded px-4 py-2 w-full">
            </div>
            <button type="submit" 
                    class="mt-4 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Search
            </button>
        </form>

        <!-- Events Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php
            if ($events_result && $events_result->num_rows > 0) {
                while ($event = $events_result->fetch_assoc()) {
                    // Absolute path for file_exists()
                    $base_dir = "../uploads/event poster/"; // Replace with your server's absolute path
                    $image_path = $base_dir . htmlspecialchars($event['event_poster']);

                    // Relative path for the <img> tag
                    $base_url = "../uploads/event poster/"; // Relative path for the browser
                    $image_src = $base_url . htmlspecialchars($event['event_poster']);

                    // Debugging: Check if the image file exists
                    if (!file_exists($image_path)) {
                        echo "<p class='text-red-500'>Image not found: " . htmlspecialchars($event['event_poster']) . "</p>";
                        continue; // Skip this event if the image is missing
                    }

                    // Check if the volunteer is already registered for the event
                    $volunteer_id = $_SESSION['volunteer_id'];
                    $event_id = $event['event_id'];
                    $check_registration_query = "
                        SELECT * 
                        FROM event_volunteers 
                        WHERE volunteer_id = '$volunteer_id' AND event_id = '$event_id'";
                    $registration_result = $conn->query($check_registration_query);
                    $registration = $registration_result->fetch_assoc();

                    // Check if the volunteer has an invitation for this event
                    $check_invitation_query = "
                        SELECT * 
                        FROM event_invitations 
                        WHERE volunteer_id = '$volunteer_id' AND event_id = '$event_id'";
                    $invitation_result = $conn->query($check_invitation_query);
                    $invitation = $invitation_result->fetch_assoc();

                    $requires_approval = $event['require_approval'];
                    $is_registered = $registration_result->num_rows > 0;
                    $has_pending_invitation = $invitation_result->num_rows > 0 && $invitation['status'] == 'pending';
                    $has_accepted_invitation = $invitation_result->num_rows > 0 && $invitation['status'] == 'accepted';
                    $is_rejected = $registration_result->num_rows > 0 && $registration['status'] == 'REJECTED';
                    $is_approved = $registration_result->num_rows > 0 && $registration['status'] == 'APPROVED';
                    $is_pending = $registration_result->num_rows > 0 && $registration['status'] == 'PENDING'; // Check for PENDING status
            ?>
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <!-- Centered Poster Image -->
                        <div class="flex justify-center items-center mb-4">
                            <img src="<?php echo $image_src; ?>" alt="Event Poster" class="w-64 h-96 object-cover rounded-lg">
                        </div>
                        <h2 class="text-xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($event['event_name']); ?></h2>
                        <p class="text-gray-600"><?php echo htmlspecialchars($event['event_description']); ?></p>
                        <p class="text-gray-600 mt-2"><strong>Date:</strong> <?php echo htmlspecialchars($event['event_date']); ?></p>
                        <p class="text-gray-600"><strong>Location:</strong> <?php echo htmlspecialchars($event['event_location']); ?></p>
                        <p class="text-gray-600"><strong>Quota:</strong> <?php echo htmlspecialchars($event['event_quota']); ?> slots</p>

                        <!-- Chat Button with Organizer -->
                        <div class="flex justify-between space-x-2 items-center mt-4">
                            <!-- Message Button with SVG -->
                            <a onclick="startChat(<?php echo $event['organizer_id']; ?>)" class="inline-flex items-center px-2 py-2 text-sm font-medium text-center text-white bg-gray-700 rounded-lg hover:bg-gray-800 focus:ring-4 focus:outline-none focus:ring-gray-300 dark:bg-gray-600 dark:hover:bg-gray-700 dark:focus:ring-gray-800">
                                <!-- SVG Icon for Message Button -->
                                <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M17 3.33782C15.5291 2.48697 13.8214 2 12 2C6.47715 2 2 6.47715 2 12C2 13.5997 2.37562 15.1116 3.04346 16.4525C3.22094 16.8088 3.28001 17.2161 3.17712 17.6006L2.58151 19.8267C2.32295 20.793 3.20701 21.677 4.17335 21.4185L6.39939 20.8229C6.78393 20.72 7.19121 20.7791 7.54753 20.9565C8.88837 21.6244 10.4003 22 12 22C17.5228 22 22 17.5228 22 12C22 10.1786 21.513 8.47087 20.6622 7" stroke="white" stroke-width="2" stroke-linecap="round" />
                                    <path d="M8 12H8.009M11.991 12H12M15.991 12H16" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </a>
                        </div>

                        <?php if ($is_rejected) { ?>
                            <!-- Rejected Status -->
                            <button type="button" class="bg-red-500 text-white font-bold py-2 px-4 rounded" disabled>
                                Rejected
                            </button>
                        <?php } elseif ($is_approved || ($is_registered && !$requires_approval)) { ?>
                            <!-- Registered Status -->
                            <button type="button" class="bg-green-500 text-white font-bold py-2 px-4 rounded" disabled>
                                Registered
                            </button>
                        <?php } elseif ($is_pending) { ?>
                            <!-- Pending Status -->
                            <button type="button" class="bg-yellow-500 text-white font-bold py-2 px-4 rounded" disabled>
                                Pending
                            </button>
                        <?php } else { ?>
                            <!-- Registration or Request Approval Button -->
                            <?php if ($requires_approval) { ?>
                                <form action="request_approval_process.php" method="POST" class="request-approval-form mt-4" data-event-id="<?php echo $event['event_id']; ?>">
                                    <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                        Request Approval
                                    </button>
                                </form>
                            <?php } else { ?>
                                <form action="register_event_process.php" method="POST" class="mt-4">
                                    <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                        Register
                                    </button>
                                </form>
                            <?php } ?>
                        <?php } ?>
                    </div>
            <?php
                }
            } else {
                echo "<p class='text-gray-600 text-center col-span-3'>No events with shifts match your search criteria.</p>";
            }
            ?>
        </div>
    </div>

    <!-- Include Flowbite JS (for interactive components) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>

    <!-- JavaScript for Chat Functionality -->
    <script>
        // Function to open the chat interface
        function openChatInterface(chatId) {
            // Redirect to the chat interface with the chatId
            window.location.href = `volunteer_chat.php?chat_id=${chatId}`;
        }

        async function startChat(organizerId) {
    try {
        console.log("Sending request to start chat with organizer ID:", organizerId);

        const response = await fetch('../api/start_chat.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                organizer_id: organizerId, // Correct field name
            }),
        });

        const rawResponse = await response.text();
        console.log("Raw response:", rawResponse);

        const data = JSON.parse(rawResponse);

        if (data.status === 'success') {
            // Redirect to the chat interface with the chatId
            window.location.href = `volunteer_chat.php?chat_id=${data.chat_id}`;
        } else {
            console.error("Error from server:", data.message);
            alert('Failed to start chat: ' + data.message);
        }
    } catch (error) {
        console.error('Error in startChat:', error);
        alert('An error occurred while starting the chat. Check the console for details.');
    }
}
    </script>

    <!-- JavaScript to handle form submissions asynchronously -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Handle "Request Approval" form submissions
            $('.request-approval-form').on('submit', function (e) {
                e.preventDefault(); // Prevent the default form submission

                const form = $(this);
                const eventId = form.data('event-id');

                $.ajax({
                    url: 'request_approval_process.php',
                    type: 'POST',
                    data: { event_id: eventId },
                    success: function (response) {
                        // Parse the response
                        const data = JSON.parse(response);

                        if (data.status === 'success') {
                            // Update the button to show "Pending Approval"
                            form.replaceWith('<button type="button" class="bg-yellow-500 text-white font-bold py-2 px-4 rounded" disabled>Pending Approval</button>');
                            alert(data.message); // Show success message
                        } else {
                            alert('Error: ' + data.message); // Show error message
                        }
                    },
                    error: function (xhr, status, error) {
                        alert('An error occurred. Please try again.');
                    }
                });
            });
        });
    </script>
</body>
</html>

<?php
// Close the connection at the end of the script
$conn->close();
?>