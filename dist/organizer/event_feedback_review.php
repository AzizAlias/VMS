<?php
session_start();

require_once '../volunteer/config.php';

if (!isset($_SESSION['organizer_id'])) {
    header("Location: log_in_organizer.php");
    exit();
}

// Fetch all FINISHED events organized by the logged-in organizer
$organizer_id = $_SESSION['organizer_id'];
$event_query = "SELECT event_id, event_name FROM event_management WHERE organizer_id = ? AND event_status = 'FINISHED'";
$event_stmt = $conn->prepare($event_query);
$event_stmt->bind_param("i", $organizer_id);
$event_stmt->execute();
$event_result = $event_stmt->get_result();
$events = $event_result->fetch_all(MYSQLI_ASSOC);

// Fetch reviews based on the selected event (if any)
$selected_event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : null;

if ($selected_event_id) {
    $review_query = "SELECT feedback.*, volunteer.volunteer_name AS volunteer_name, volunteer.volunteer_id 
                     FROM feedback 
                     JOIN volunteer ON feedback.volunteer_id = volunteer.volunteer_id 
                     WHERE feedback.event_id = ?
                     ORDER BY feedback.created_at DESC";
    $review_stmt = $conn->prepare($review_query);
    $review_stmt->bind_param("i", $selected_event_id);
} else {
    $review_query = "SELECT feedback.*, volunteer.volunteer_name AS volunteer_name, volunteer.volunteer_id 
                     FROM feedback 
                     JOIN volunteer ON feedback.volunteer_id = volunteer.volunteer_id 
                     JOIN event_management ON feedback.event_id = event_management.event_id 
                     WHERE event_management.organizer_id = ? AND event_management.event_status = 'FINISHED'
                     ORDER BY feedback.created_at DESC";
    $review_stmt = $conn->prepare($review_query);
    $review_stmt->bind_param("i", $organizer_id);
}

$review_stmt->execute();
$review_result = $review_stmt->get_result();
$reviews = $review_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Feedback</title>
    <!-- Flowbite CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet" />
    <!-- Custom CSS -->
    <style>
        .review-card {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        .review-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-100">

    <?php include 'organizer_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="ml-64 p-8"> <!-- Adjust margin-left to match sidebar width -->
        <h1 class="text-3xl font-bold text-center mb-8">Event Feedback Review</h1>

        <!-- Event Dropdown -->
        <div class="flex justify-center mb-8">
            <form method="GET" action="">
                <label for="event_id" class="block text-sm font-medium text-gray-700">Select Event</label>
                <select id="event_id" name="event_id" onchange="this.form.submit()" class="mt-1 block w-64 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="">All Events</option>
                    <?php foreach ($events as $event): ?>
                        <option value="<?php echo $event['event_id']; ?>" <?php echo ($selected_event_id == $event['event_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($event['event_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <!-- Centered Grid Container -->
        <div class="mx-auto max-w-7xl">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if (count($reviews) > 0): ?>
                    <?php foreach ($reviews as $review): ?>
                        <!-- Review Card -->
                        <div class="bg-white p-6 rounded-lg shadow-lg review-card">
                            <div class="flex items-center mb-4">
                                <img class="w-10 h-10 rounded-full mr-4" src="https://via.placeholder.com/40" alt="Volunteer Avatar">
                                <div>
                                    <h2 class="text-lg font-semibold"><?php echo htmlspecialchars($review['volunteer_name']); ?></h2>
                                    <p class="text-sm text-gray-500">Volunteer</p>
                                </div>
                            </div>
                            <div class="text-yellow-400 mb-2">
                                <?php
                                // Display star rating
                                $rating = $review['rating'];
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $rating) {
                                        echo '★';
                                    } else {
                                        echo '☆';
                                    }
                                }
                                ?>
                            </div>
                            <p class="text-gray-700">
                                "<?php echo htmlspecialchars($review['comments']); ?>"
                            </p>
                            <p class="text-sm text-gray-500 mt-2">
                                <?php echo date('F j, Y, g:i a', strtotime($review['created_at'])); ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center text-gray-500 col-span-full">No reviews found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Flowbite JS (optional, for interactive components) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
</body>
</html>