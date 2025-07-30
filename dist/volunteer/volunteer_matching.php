<?php
session_start();

// Include the config file
require_once 'config.php';

// Check if the user is logged in as a volunteer
if (!isset($_SESSION['volunteer_id'])) {
    header("Location: log_in_volunteer.php");
    exit();
}

// Fetch the logged-in volunteer's details
$volunteer_id = $_SESSION['volunteer_id'];
$volunteer_query = "SELECT volunteer_availability, volunteer_skills FROM volunteer WHERE volunteer_id = ?";
$stmt = $conn->prepare($volunteer_query);
$stmt->bind_param("i", $volunteer_id);
$stmt->execute();
$volunteer_result = $stmt->get_result();
$volunteer = $volunteer_result->fetch_assoc();

if (!$volunteer) {
    die("Volunteer not found.");
}

$volunteer_availability = strtolower($volunteer['volunteer_availability']);
$volunteer_skills = array_map('trim', explode(',', strtolower($volunteer['volunteer_skills']))); // Convert skills to an array

// Fetch events that meet the criteria: event_quota_status = 'AVAILABLE', event_status = 'OPEN', and have at least 1 shift
$query = "
    SELECT em.* 
    FROM event_management em
    WHERE em.event_quota_status = 'AVAILABLE'
    AND em.event_status = 'OPEN'
    AND em.event_quota > 0  -- Exclude events with a quota of 0
    AND EXISTS (
        SELECT 1 
        FROM shift s 
        WHERE s.event_id = em.event_id
    )
";
$events_result = $conn->query($query);

// Arrays to store matched events
$matched_availability_only = [];
$matched_skills_only = [];
$matched_both = [];

// Check each event against the volunteer's availability and skills
if ($events_result->num_rows > 0) {
    while ($event = $events_result->fetch_assoc()) {
        $event_availability = strtolower($event['event_date']);
        $event_skills = array_map('trim', explode(',', strtolower($event['required_skills']))); // Convert required skills to an array
        $event_id = $event['event_id'];

        // Check for availability match
        $availability_match = strpos($volunteer_availability, $event_availability) !== false;

        // Check for skills match
        $skills_match = !empty(array_intersect($volunteer_skills, $event_skills));

        // Categorize the event based on matches
        if ($availability_match && $skills_match) {
            $matched_both[] = $event;
        } elseif ($availability_match) {
            $matched_availability_only[] = $event;
        } elseif ($skills_match) {
            $matched_skills_only[] = $event;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer Matching</title>
    <!-- Include Flowbite CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet">
    <!-- Include Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script> <!-- Include jQuery for AJAX -->
</head>
<body class="font-sans leading-normal tracking-normal">

    <?php include 'volunteer_sidebar.php'; ?>

    <div class="main-content lg:ml-72 p-6 bg-white transition-all duration-300">
        <h1 class="text-3xl font-bold text-center mb-8 text-gray-800">Matched Events</h1>

        <!-- Dropdown to filter matched events -->
        <div class="flex justify-center mb-8">
            <label for="matched-type" class="block text-sm font-medium text-gray-700 mr-2">Filter by:</label>
            <select id="matched-type" class="p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="all">Show All</option>
                <option value="both">Matched in Both Availability and Skills</option>
                <option value="availability">Matched Only in Availability</option>
                <option value="skills">Matched Only in Skills</option>
            </select>
        </div>

        <!-- Section for events matched in both availability and skills -->
        <div id="matched-both-section" class="mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Matched in Both Availability and Skills</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($matched_both as $event): ?>
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <?php
                        $image_path = "../uploads/event poster/" . htmlspecialchars($event['event_poster']);
                        $event_id = $event['event_id'];

                        // Check if the volunteer is already registered for the event
                        $check_registration_query = "SELECT * FROM event_volunteers WHERE volunteer_id = ? AND event_id = ?";
                        $stmt = $conn->prepare($check_registration_query);
                        $stmt->bind_param("ii", $volunteer_id, $event_id);
                        $stmt->execute();
                        $registration_result = $stmt->get_result();
                        $registration = $registration_result->fetch_assoc();

                        $is_registered = $registration_result->num_rows > 0;
                        $requires_approval = $event['require_approval'];
                        ?>
                        <div class="flex justify-center items-center mb-4">
                            <img src="<?php echo $image_path; ?>" alt="Event Poster" class="w-64 h-96 object-cover rounded-lg">
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

                        <?php if ($is_registered): ?>
                            <?php if ($registration['status'] === 'PENDING'): ?>
                                <button type="button" class="bg-yellow-500 text-white font-bold py-2 px-4 rounded" disabled>
                                    Pending Approval
                                </button>
                            <?php elseif ($registration['status'] === 'APPROVED'): ?>
                                <button type="button" class="bg-green-500 text-white font-bold py-2 px-4 rounded" disabled>
                                    Registered
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if ($requires_approval): ?>
                                <form class="request-approval-form" data-event-id="<?php echo $event['event_id']; ?>">
                                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                        Request Approval
                                    </button>
                                </form>
                            <?php else: ?>
                                <form class="register-event-form" data-event-id="<?php echo $event['event_id']; ?>">
                                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                        Register
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Section for events matched only in availability -->
        <div id="matched-availability-section" class="mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Matched Only in Availability</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($matched_availability_only as $event): ?>
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <?php
                        $image_path = "../uploads/event poster/" . htmlspecialchars($event['event_poster']);
                        $event_id = $event['event_id'];

                        // Check if the volunteer is already registered for the event
                        $check_registration_query = "SELECT * FROM event_volunteers WHERE volunteer_id = ? AND event_id = ?";
                        $stmt = $conn->prepare($check_registration_query);
                        $stmt->bind_param("ii", $volunteer_id, $event_id);
                        $stmt->execute();
                        $registration_result = $stmt->get_result();
                        $registration = $registration_result->fetch_assoc();

                        $is_registered = $registration_result->num_rows > 0;
                        $requires_approval = $event['require_approval'];
                        ?>
                        <div class="flex justify-center items-center mb-4">
                            <img src="<?php echo $image_path; ?>" alt="Event Poster" class="w-64 h-96 object-cover rounded-lg">
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

                        <?php if ($is_registered): ?>
                            <?php if ($registration['status'] === 'PENDING'): ?>
                                <button type="button" class="bg-yellow-500 text-white font-bold py-2 px-4 rounded" disabled>
                                    Pending Approval
                                </button>
                            <?php elseif ($registration['status'] === 'APPROVED'): ?>
                                <button type="button" class="bg-green-500 text-white font-bold py-2 px-4 rounded" disabled>
                                    Registered
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if ($requires_approval): ?>
                                <form class="request-approval-form" data-event-id="<?php echo $event['event_id']; ?>">
                                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                        Request Approval
                                    </button>
                                </form>
                            <?php else: ?>
                                <form class="register-event-form" data-event-id="<?php echo $event['event_id']; ?>">
                                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                        Register
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Section for events matched only in skills -->
        <div id="matched-skills-section" class="mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Matched Only in Skills</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($matched_skills_only as $event): ?>
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <?php
                        $image_path = "../uploads/event poster/" . htmlspecialchars($event['event_poster']);
                        $event_id = $event['event_id'];

                        // Check if the volunteer is already registered for the event
                        $check_registration_query = "SELECT * FROM event_volunteers WHERE volunteer_id = ? AND event_id = ?";
                        $stmt = $conn->prepare($check_registration_query);
                        $stmt->bind_param("ii", $volunteer_id, $event_id);
                        $stmt->execute();
                        $registration_result = $stmt->get_result();
                        $registration = $registration_result->fetch_assoc();

                        $is_registered = $registration_result->num_rows > 0;
                        $requires_approval = $event['require_approval'];
                        ?>
                        <div class="flex justify-center items-center mb-4">
                            <img src="<?php echo $image_path; ?>" alt="Event Poster" class="w-64 h-96 object-cover rounded-lg">
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

                        <?php if ($is_registered): ?>
                            <?php if ($registration['status'] === 'PENDING'): ?>
                                <button type="button" class="bg-yellow-500 text-white font-bold py-2 px-4 rounded" disabled>
                                    Pending Approval
                                </button>
                            <?php elseif ($registration['status'] === 'APPROVED'): ?>
                                <button type="button" class="bg-green-500 text-white font-bold py-2 px-4 rounded" disabled>
                                    Registered
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if ($requires_approval): ?>
                                <form class="request-approval-form" data-event-id="<?php echo $event['event_id']; ?>">
                                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                        Request Approval
                                    </button>
                                </form>
                            <?php else: ?>
                                <form class="register-event-form" data-event-id="<?php echo $event['event_id']; ?>">
                                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                        Register
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Display a message if no events are matched -->
        <?php if (empty($matched_both) && empty($matched_availability_only) && empty($matched_skills_only)): ?>
            <p class="text-gray-600 text-center col-span-3">No matched events found for your skills and availability.</p>
        <?php endif; ?>
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
                        organizer_id: organizerId,
                    }),
                });

                const rawResponse = await response.text();
                console.log("Raw response:", rawResponse);

                const data = JSON.parse(rawResponse);

                if (data.status === 'success') {
                    openChatInterface(data.chat_id);
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

    <!-- JavaScript to handle dropdown filtering -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const dropdown = document.getElementById('matched-type');
            const bothSection = document.getElementById('matched-both-section');
            const availabilitySection = document.getElementById('matched-availability-section');
            const skillsSection = document.getElementById('matched-skills-section');

            // Function to show/hide sections based on dropdown selection
            function filterSections() {
                const selectedValue = dropdown.value;

                // Show all sections by default
                bothSection.style.display = 'block';
                availabilitySection.style.display = 'block';
                skillsSection.style.display = 'block';

                // Hide sections based on the selected value
                if (selectedValue === 'both') {
                    availabilitySection.style.display = 'none';
                    skillsSection.style.display = 'none';
                } else if (selectedValue === 'availability') {
                    bothSection.style.display = 'none';
                    skillsSection.style.display = 'none';
                } else if (selectedValue === 'skills') {
                    bothSection.style.display = 'none';
                    availabilitySection.style.display = 'none';
                } else if (selectedValue === 'all') {
                    // Show all sections
                    bothSection.style.display = 'block';
                    availabilitySection.style.display = 'block';
                    skillsSection.style.display = 'block';
                }
            }

            // Add event listener to the dropdown
            dropdown.addEventListener('change', filterSections);

            // Initial filter on page load
            filterSections();

            // Handle form submissions using AJAX
            $('.request-approval-form, .register-event-form').on('submit', function (e) {
                e.preventDefault(); // Prevent the default form submission

                const form = $(this);
                const eventId = form.data('event-id');
                const action = form.hasClass('request-approval-form') ? 'request_approval_process.php' : 'volunteer_matching_register_process.php';

                $.ajax({
                    url: action,
                    type: 'POST',
                    data: { event_id: eventId },
                    success: function (response) {
                        // Parse the response
                        const data = JSON.parse(response);

                        if (data.status === 'success') {
                            // Update the button or show a success message
                            if (data.registration_status === 'APPROVED') {
                                form.replaceWith('<button type="button" class="bg-green-500 text-white font-bold py-2 px-4 rounded" disabled>Registered</button>');
                            } else if (data.registration_status === 'PENDING') {
                                form.replaceWith('<button type="button" class="bg-yellow-500 text-white font-bold py-2 px-4 rounded" disabled>Pending Approval</button>');
                            }
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
$conn->close();
?>