<?php
session_start();
// Include the config file
require_once '../volunteer/config.php';

// Check if the user is logged in as an organizer
if (!isset($_SESSION['organizer_id'])) {
    header("Location: log_in_organizer.php");
    exit();
}


// Fetch all finished events
$eventsQuery = "
    SELECT event_id, event_name
    FROM event_management
    WHERE event_status = 'FINISHED'
    ORDER BY event_name
";
$eventsResult = $conn->query($eventsQuery);
$events = $eventsResult->fetch_all(MYSQLI_ASSOC);

// Fetch feedback for the selected event
$selectedEventId = $_GET['event_id'] ?? null;
$feedback = [];

if ($selectedEventId) {
    $feedbackQuery = "
        SELECT f.volunteer_id, f.rating, f.comments, v.volunteer_name
        FROM feedback f
        INNER JOIN volunteer v ON f.volunteer_id = v.volunteer_id
        WHERE f.event_id = ?
        ORDER BY v.volunteer_name
    ";
    $stmt = $conn->prepare($feedbackQuery);
    $stmt->bind_param("i", $selectedEventId);
    $stmt->execute();
    $feedbackResult = $stmt->get_result();
    $feedback = $feedbackResult->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Reviews</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.6.5/flowbite.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto mt-8 p-6 bg-white rounded-lg shadow-md">
        <h1 class="text-2xl font-bold mb-4">Event Reviews</h1>

        <!-- Event Selection Dropdown -->
        <form method="GET" action="" class="mb-6">
            <label for="event_id" class="block font-medium mb-2">Select Event:</label>
            <select name="event_id" id="event_id" onchange="this.form.submit()"
                class="w-full p-2 border rounded-lg focus:ring focus:ring-blue-300">
                <option value="" disabled selected>Choose an event</option>
                <?php foreach ($events as $event): ?>
                    <option value="<?= $event['event_id'] ?>" <?= $selectedEventId == $event['event_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($event['event_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <!-- Feedback Display -->
        <?php if ($selectedEventId): ?>
            <div class="space-y-4">
                <?php if (empty($feedback)): ?>
                    <p class="text-gray-500">No feedback submitted for this event.</p>
                <?php else: ?>
                    <?php foreach ($feedback as $review): ?>
                        <div class="p-4 bg-gray-50 rounded-lg">
                            <p class="font-medium">Volunteer: <?= htmlspecialchars($review['volunteer_name']) ?></p>
                            <div class="flex items-center mt-2">
                                <p class="font-medium mr-2">Rating:</p>
                                <div class="flex items-center">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <svg class="w-5 h-5 <?= $i <= $review['rating'] ? 'text-yellow-300' : 'text-gray-300' ?>" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 22 20">
                                            <path d="M20.924 7.625a1.523 1.523 0 0 0-1.238-1.044l-5.051-.734-2.259-4.577a1.534 1.534 0 0 0-2.752 0L7.365 5.847l-5.051.734A1.535 1.535 0 0 0 1.463 9.2l3.656 3.563-.863 5.031a1.532 1.532 0 0 0 2.226 1.616L11 17.033l4.518 2.375a1.534 1.534 0 0 0 2.226-1.617l-.863-5.03L20.537 9.2a1.523 1.523 0 0 0 .387-1.575Z"/>
                                        </svg>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <p class="font-medium mt-2">Comments:</p>
                            <p class="text-gray-700"><?= htmlspecialchars($review['comments']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-500">Please select an event to view feedback.</p>
        <?php endif; ?>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.6.5/flowbite.min.js"></script>
</body>
</html>