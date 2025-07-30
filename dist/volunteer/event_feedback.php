<?php
session_start();
require_once 'config.php';

// Check if the user is logged in as a volunteer
if (!isset($_SESSION['volunteer_id'])) {
    header("Location: log_in_volunteer.php");
    exit();
}

$volunteer_id = $_SESSION['volunteer_id']; // Ensure session is active

// Fetch events the volunteer attended and can leave feedback for
$query = "
    SELECT e.event_id, e.event_name, e.event_status, f.rating, f.comments, a.attendance_status
    FROM event_management e
    INNER JOIN event_attendance a ON e.event_id = a.event_id
    LEFT JOIN feedback f ON e.event_id = f.event_id AND f.volunteer_id = ?
    WHERE a.volunteer_id = ? AND e.event_status = 'FINISHED' AND a.attendance_status = 'Present'
";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $volunteer_id, $volunteer_id);
$stmt->execute();
$result = $stmt->get_result();
$events = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Check if feedback was successfully submitted
$feedbackSubmitted = $_SESSION['feedback_submitted'] ?? false;
unset($_SESSION['feedback_submitted']); // Clear the session variable

// Check if an event is selected
$selectedEventId = $_GET['event_id'] ?? null;
$selectedEvent = null;
if ($selectedEventId) {
    foreach ($events as $event) {
        if ($event['event_id'] == $selectedEventId) {
            $selectedEvent = $event;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Feedback</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.6.5/flowbite.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">

<!-- Sidebar -->
<?php include 'volunteer_sidebar.php'; ?>

<!-- Main Content -->
<div class="flex flex-col md:flex-row min-h-screen">
    <!-- Sidebar (hidden on smaller screens) -->
    <div class="w-full md:w-64 bg-white shadow-md md:block">
        <?php include 'volunteer_sidebar.php'; ?>
    </div>

    <!-- Main Content Area -->
    <div class="flex-1 p-4 md:p-8">
        <div class="container mx-auto">
            <h1 class="text-3xl font-bold text-center mb-8 text-gray-800">Provide Feedback</h1>

            <!-- Event Selection Dropdown -->
            <div class="mb-6">
                <label for="eventSelect" class="block font-medium mb-2">Select an Event:</label>
                <select id="eventSelect" onchange="location = this.value;" class="w-full p-2 border rounded-lg focus:ring focus:ring-blue-300">
                    <option value="event_feedback.php">-- Select an Event --</option>
                    <?php foreach ($events as $event): ?>
                        <option value="event_feedback.php?event_id=<?= $event['event_id'] ?>" <?= $selectedEventId == $event['event_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($event['event_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Display Feedback Form or Existing Feedback -->
            <?php if ($selectedEventId && $selectedEvent): ?>
                <div class="p-4 border-b last:border-b-0 bg-white rounded-lg shadow-md mb-4">
                    <h2 class="text-lg font-semibold"><?= htmlspecialchars($selectedEvent['event_name']) ?></h2>
                    <?php if (empty($selectedEvent['rating'])): ?>
                        <!-- Display form if no feedback exists -->
                        <form action="submit_feedback.php" method="POST" class="mt-4">
                            <input type="hidden" name="event_id" value="<?= $selectedEvent['event_id'] ?>">
                            <div class="mb-4">
                                <label class="block font-medium">Rating:</label>
                                <div class="flex items-center" id="rating_<?= $selectedEvent['event_id'] ?>">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <svg class="w-6 h-6 cursor-pointer star-rating" data-rating="<?= $i ?>" data-event-id="<?= $selectedEvent['event_id'] ?>" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 22 20">
                                            <path d="M20.924 7.625a1.523 1.523 0 0 0-1.238-1.044l-5.051-.734-2.259-4.577a1.534 1.534 0 0 0-2.752 0L7.365 5.847l-5.051.734A1.535 1.535 0 0 0 1.463 9.2l3.656 3.563-.863 5.031a1.532 1.532 0 0 0 2.226 1.616L11 17.033l4.518 2.375a1.534 1.534 0 0 0 2.226-1.617l-.863-5.03L20.537 9.2a1.523 1.523 0 0 0 .387-1.575Z"/>
                                        </svg>
                                    <?php endfor; ?>
                                </div>
                                <input type="hidden" name="rating" id="selected_rating_<?= $selectedEvent['event_id'] ?>" required>
                            </div>
                            <div class="mb-4">
                                <label for="comments_<?= $selectedEvent['event_id'] ?>" class="block font-medium">Comments:</label>
                                <textarea name="comments" id="comments_<?= $selectedEvent['event_id'] ?>" rows="4" required
                                    class="w-full p-2 border rounded-lg focus:ring focus:ring-blue-300"></textarea>
                            </div>
                            <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring focus:ring-blue-300">
                                Submit Feedback
                            </button>
                        </form>
                    <?php else: ?>
                        <!-- Display existing feedback if already submitted -->
                        <div class="mt-4">
                            <p class="font-medium">Your Rating:</p>
                            <div class="flex items-center">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <svg class="w-6 h-6 <?= $i <= $selectedEvent['rating'] ? 'text-yellow-300' : 'text-gray-300' ?>" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 22 20">
                                        <path d="M20.924 7.625a1.523 1.523 0 0 0-1.238-1.044l-5.051-.734-2.259-4.577a1.534 1.534 0 0 0-2.752 0L7.365 5.847l-5.051.734A1.535 1.535 0 0 0 1.463 9.2l3.656 3.563-.863 5.031a1.532 1.532 0 0 0 2.226 1.616L11 17.033l4.518 2.375a1.534 1.534 0 0 0 2.226-1.617l-.863-5.03L20.537 9.2a1.523 1.523 0 0 0 .387-1.575Z"/>
                                    </svg>
                                <?php endfor; ?>
                            </div>
                            <p class="font-medium mt-2">Your Comments:</p>
                            <p class="text-gray-700"><?= htmlspecialchars($selectedEvent['comments']) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php elseif ($selectedEventId && !$selectedEvent): ?>
                <p class="text-gray-500">Invalid event selected.</p>
            <?php else: ?>
                <p class="text-gray-500">Please select an event to provide feedback.</p>
            <?php endif; ?>
        </div>

        <!-- Success Modal -->
        <?php if ($feedbackSubmitted): ?>
            <div id="successModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
                <div class="bg-white p-6 rounded-lg shadow-md text-center">
                    <h2 class="text-xl font-bold mb-4">Success!</h2>
                    <p class="mb-4">Your feedback has been successfully submitted.</p>
                    <button onclick="closeModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Close
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.6.5/flowbite.min.js"></script>
<script>
    // JavaScript to handle star rating selection
    document.querySelectorAll('.star-rating').forEach(star => {
        star.addEventListener('click', function() {
            const eventId = this.getAttribute('data-event-id');
            const rating = this.getAttribute('data-rating');

            // Highlight selected stars
            const stars = document.querySelectorAll(`[data-event-id="${eventId}"]`);
            stars.forEach((s, index) => {
                if (index < rating) {
                    s.classList.remove('text-gray-300');
                    s.classList.add('text-yellow-300');
                } else {
                    s.classList.remove('text-yellow-300');
                    s.classList.add('text-gray-300');
                }
            });

            // Set the selected rating in the hidden input
            document.getElementById(`selected_rating_${eventId}`).value = rating;
        });
    });

    // Close the success modal
    function closeModal() {
        document.getElementById('successModal').style.display = 'none';
    }
</script>
</body>
</html>