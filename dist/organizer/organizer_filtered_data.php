<?php
session_start();
require_once '../volunteer/config.php';

// Check if the user is logged in as an organizer
if (!isset($_SESSION['organizer_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit();
}

// Get the selected month and year from the request
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('m');
$selected_year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Fetch data for the graphs (events and participants by month)
$organizer_id = $_SESSION['organizer_id'];
$query = "
    SELECT 
        DATE_FORMAT(em.event_date, '%Y-%m') AS month,
        COUNT(DISTINCT em.event_id) AS total_events,
        COUNT(DISTINCT ea.volunteer_id) AS total_participants
    FROM event_management em
    LEFT JOIN event_attendance ea ON em.event_id = ea.event_id AND ea.attendance_status = 'PRESENT'
    WHERE em.organizer_id = ? AND em.event_status = 'FINISHED'
    AND DATE_FORMAT(em.event_date, '%Y-%m') = ?
    GROUP BY DATE_FORMAT(em.event_date, '%Y-%m')
    ORDER BY month
";
$stmt = $conn->prepare($query);
$filtered_month_year = $selected_year . '-' . $selected_month;
$stmt->bind_param("is", $organizer_id, $filtered_month_year);
$stmt->execute();
$result = $stmt->get_result();

$labels = []; // Months
$eventsData = []; // Total events per month
$participantsData = []; // Total participants per month

while ($row = $result->fetch_assoc()) {
    $labels[] = $row['month'];
    $eventsData[] = $row['total_events'];
    $participantsData[] = $row['total_participants'];
}
$stmt->close();

// Return the data as JSON
header('Content-Type: application/json');
echo json_encode([
    'labels' => $labels,
    'eventsData' => $eventsData,
    'participantsData' => $participantsData
]);
?>