<?php
session_start();
// Include the config file
require_once '../volunteer/config.php';

// Check if the user is logged in as an organizer
if (!isset($_SESSION['organizer_id'])) {
    header("Location: log_in_organizer.php");
    exit();
}

// Fetch the organizer's registration date
$organizer_id = $_SESSION['organizer_id'];
$query = "SELECT registration_date FROM organizer WHERE organizer_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $organizer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $registration_year = date('Y', strtotime($row['registration_date'])); // Extract year from registration date
} else {
    $registration_year = date('Y'); // Default to current year if registration date is not found
}
$stmt->close();

// Initialize filter variables
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('m'); // Default to current month
$selected_year = isset($_GET['year']) ? $_GET['year'] : date('Y'); // Default to current year

// Fetch the total number of FINISHED events organized by the logged-in organizer
$query = "SELECT COUNT(*) AS total_events FROM event_management WHERE organizer_id = ? AND event_status = 'FINISHED'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $organizer_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_events = $row['total_events']; // Total number of FINISHED events
$stmt->close();

// Fetch the total number of volunteers who attended FINISHED events organized by the logged-in organizer
$query = "
    SELECT COUNT(DISTINCT ea.volunteer_id) AS total_volunteers 
    FROM event_attendance ea
    INNER JOIN event_management em ON ea.event_id = em.event_id
    WHERE em.organizer_id = ? 
    AND em.event_status = 'FINISHED' 
    AND ea.attendance_status = 'PRESENT'
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $organizer_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_volunteers = $row['total_volunteers']; // Total number of volunteers who attended
$stmt->close();

// Fetch data for the graphs (events and participants by month)
$query = "
    SELECT 
        DATE_FORMAT(em.event_date, '%Y-%m') AS month,
        COUNT(DISTINCT em.event_id) AS total_events,
        COUNT(DISTINCT ea.volunteer_id) AS total_participants
    FROM event_management em
    LEFT JOIN event_attendance ea ON em.event_id = ea.event_id AND ea.attendance_status = 'PRESENT'
    WHERE em.organizer_id = ? AND em.event_status = 'FINISHED'
    GROUP BY DATE_FORMAT(em.event_date, '%Y-%m')
    ORDER BY month
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $organizer_id);
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

// Fetch filtered data for the selected month and year
$filtered_query = "
    SELECT 
        COUNT(DISTINCT em.event_id) AS filtered_events,
        COUNT(DISTINCT ea.volunteer_id) AS filtered_participants
    FROM event_management em
    LEFT JOIN event_attendance ea ON em.event_id = ea.event_id AND ea.attendance_status = 'PRESENT'
    WHERE em.organizer_id = ? 
    AND em.event_status = 'FINISHED'
    AND DATE_FORMAT(em.event_date, '%Y-%m') = ?
";
$stmt = $conn->prepare($filtered_query);
$filtered_month_year = $selected_year . '-' . $selected_month;
$stmt->bind_param("is", $organizer_id, $filtered_month_year);
$stmt->execute();
$result = $stmt->get_result();
$filtered_data = $result->fetch_assoc();
$filtered_events = $filtered_data['filtered_events'] ?? 0;
$filtered_participants = $filtered_data['filtered_participants'] ?? 0;
$stmt->close();

// Fetch average feedback rating per month (with filter)
$sql_feedback = "
    SELECT 
        DATE_FORMAT(em.event_date, '%Y-%m') AS month,
        AVG(f.rating) AS average_rating
    FROM 
        event_management em
    LEFT JOIN 
        feedback f ON em.event_id = f.event_id
    WHERE 
        em.organizer_id = ? 
        AND em.event_status = 'FINISHED'
        AND (DATE_FORMAT(em.event_date, '%Y') = ? OR ? = '') -- Year filter
        AND (DATE_FORMAT(em.event_date, '%m') = ? OR ? = '') -- Month filter
    GROUP BY 
        DATE_FORMAT(em.event_date, '%Y-%m')
    ORDER BY 
        month;
";

$stmt_feedback = $conn->prepare($sql_feedback);
$stmt_feedback->bind_param("issss", $organizer_id, $selected_year, $selected_year, $selected_month, $selected_month);
$stmt_feedback->execute();
$result_feedback = $stmt_feedback->get_result();

$feedbackData = [];
while ($row = $result_feedback->fetch_assoc()) {
    $feedbackData[] = $row['average_rating'];
}

$stmt_feedback->close();

// Calculate overall average feedback rating
$sql_overall_feedback = "
    SELECT 
        AVG(f.rating) AS overall_average_rating
    FROM 
        event_management em
    LEFT JOIN 
        feedback f ON em.event_id = f.event_id
    WHERE 
        em.organizer_id = ? 
        AND em.event_status = 'FINISHED'
";

$stmt_overall_feedback = $conn->prepare($sql_overall_feedback);
$stmt_overall_feedback->bind_param("i", $organizer_id);
$stmt_overall_feedback->execute();
$result_overall_feedback = $stmt_overall_feedback->get_result();

if ($result_overall_feedback->num_rows > 0) {
    $row = $result_overall_feedback->fetch_assoc();
    $average_feedback_rating = $row['overall_average_rating'] ?? 0;
} else {
    $average_feedback_rating = 0;
}

$stmt_overall_feedback->close();

// Fetch data for the bar chart (participants per event)
$query_bar_chart = "
    SELECT 
        em.event_name, 
        COUNT(DISTINCT ea.volunteer_id) AS participants_count
    FROM 
        event_management em
    LEFT JOIN 
        event_attendance ea ON em.event_id = ea.event_id AND ea.attendance_status = 'PRESENT'
    WHERE 
        em.organizer_id = ? 
        AND em.event_status = 'FINISHED'
    GROUP BY 
        em.event_id
    ORDER BY 
        em.event_date DESC
    LIMIT 5"; // Limit to 5 most recent events
$stmt_bar_chart = $conn->prepare($query_bar_chart);
$stmt_bar_chart->bind_param("i", $organizer_id);
$stmt_bar_chart->execute();
$result_bar_chart = $stmt_bar_chart->get_result();

$barChartLabels = []; // Event names
$barChartData = []; // Participants count per event

while ($row = $result_bar_chart->fetch_assoc()) {
    $barChartLabels[] = $row['event_name'];
    $barChartData[] = $row['participants_count'];
}
$stmt_bar_chart->close();

// Fetch data for the pie chart (event status distribution)
$query_pie_chart = "
    SELECT 
        event_status, 
        COUNT(*) AS status_count
    FROM 
        event_management
    WHERE 
        organizer_id = ?
    GROUP BY 
        event_status";
$stmt_pie_chart = $conn->prepare($query_pie_chart);
$stmt_pie_chart->bind_param("i", $organizer_id);
$stmt_pie_chart->execute();
$result_pie_chart = $stmt_pie_chart->get_result();

$pieChartLabels = []; // Event statuses
$pieChartData = []; // Count of events per status
$pieChartColors = []; // Colors for each status

while ($row = $result_pie_chart->fetch_assoc()) {
    $pieChartLabels[] = $row['event_status'];
    $pieChartData[] = $row['status_count'];
    // Assign colors based on status
    switch ($row['event_status']) {
        case 'FINISHED':
            $pieChartColors[] = '#10B981'; // Green
            break;
        case 'ONGOING':
            $pieChartColors[] = '#3B82F6'; // Blue
            break;
        case 'OPEN':
            $pieChartColors[] = '#FBBF24'; // Yellow
            break;
        case 'CANCELLED':
            $pieChartColors[] = '#EF4444'; // Red
            break;
        default:
            $pieChartColors[] = '#6B7280'; // Gray
    }
}
$stmt_pie_chart->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organizer Dashboard</title>
    <!-- Include Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Include Flowbite CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet" />
    <!-- Include Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Include html2canvas and jsPDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>
<body class="bg-gray-50">
    <!-- Sidebar -->
    <?php include 'organizer_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="ml-72 p-8"> <!-- Adjusted margin to account for sidebar -->
        <!-- Header -->
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Welcome, Organizer!</h1>
        </header>

        <!-- Filter Section -->
        <form method="GET" class="mb-8 bg-white p-6 rounded-lg shadow-sm">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Month Selector -->
                <div>
                    <label for="month" class="block text-sm font-medium text-gray-700 mb-1">Month</label>
                    <select id="month" name="month" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select Month</option>
                        <?php
                        for ($i = 1; $i <= 12; $i++) {
                            $month = str_pad($i, 2, '0', STR_PAD_LEFT);
                            $selected = ($month == $selected_month) ? 'selected' : '';
                            echo "<option value='$month' $selected>" . date('F', mktime(0, 0, 0, $i, 10)) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <!-- Year Selector -->
                <div>
                    <label for="year" class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                    <select id="year" name="year" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select Year</option>
                        <?php
                        $current_year = date('Y');
                        for ($i = $current_year; $i >= $registration_year; $i--) {
                            $selected = ($i == $selected_year) ? 'selected' : '';
                            echo "<option value='$i' $selected>$i</option>";
                        }
                        ?>
                    </select>
                </div>
                <!-- Filter Button -->
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500">
                        Filter
                    </button>
                </div>
            </div>
        </form>

        <!-- Graph Display Selector -->
        <div class="mb-8 bg-white p-6 rounded-lg shadow-sm">
            <label for="graphDisplay" class="block text-sm font-medium text-gray-700 mb-2">Display Graph By:</label>
            <select id="graphDisplay" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="monthly">Monthly</option>
                <option value="overall">Overall</option>
            </select>
        </div>

        <!-- Download Buttons -->
        <div class="flex flex-wrap gap-4 mb-8">
            <button id="downloadEventsChart" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500">
                Download Events Chart
            </button>
            <button id="downloadParticipantsChart" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 focus:ring-2 focus:ring-red-500">
                Download Participants Chart
            </button>
            <button id="downloadFeedbackChart" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 focus:ring-2 focus:ring-purple-500">
                Download Feedback Chart
            </button>
            <button id="downloadAllAnalytics" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 focus:ring-2 focus:ring-green-500">
                Download All Analytics (PDF)
            </button>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Total Events Card -->
            <div class="bg-white p-6 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Total Events (Filtered)</h3>
                <p class="text-3xl font-bold text-gray-900"><?php echo $filtered_events; ?></p>
            </div>
            <!-- Total Participants Card -->
            <div class="bg-white p-6 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Total Participants (Filtered)</h3>
                <p class="text-3xl font-bold text-gray-900"><?php echo $filtered_participants; ?></p>
            </div>
            <!-- Average Feedback Rating Card -->
            <div class="bg-white p-6 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Average Feedback Rating</h3>
                <p class="text-3xl font-bold text-gray-900"><?php echo number_format($average_feedback_rating, 2); ?></p>
            </div>
        </div>

        <!-- Graph Section -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Total Events Graph -->
            <div class="bg-white p-6 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Total Events Over Time</h2>
                <div class="w-full h-96">
                    <canvas id="eventsChart"></canvas>
                </div>
            </div>
            <!-- Total Participants Graph -->
            <div class="bg-white p-6 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Total Participants Over Time</h2>
                <div class="w-full h-96">
                    <canvas id="participantsChart"></canvas>
                </div>
            </div>
            <!-- Average Feedback Rating Graph -->
            <div class="bg-white p-6 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Average Feedback Rating Over Time</h2>
                <div class="w-full h-96">
                    <canvas id="feedbackChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Bar Chart Section -->
        <div class="bg-white p-6 rounded-lg shadow-sm hover:shadow-md transition-shadow mb-8">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Participants per Event</h2>
            <div class="w-full h-96">
                <canvas id="barChart"></canvas>
            </div>
        </div>

        <!-- Pie Chart Section -->
        <div class="bg-white p-6 rounded-lg shadow-sm hover:shadow-md transition-shadow mb-8">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Event Status Distribution</h2>
            <div class="w-full h-96">
                <canvas id="pieChart"></canvas>
            </div>
        </div>

        <!-- Recent Events Table -->
        <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow">
            <div class="p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Recent Events</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Participants Registered</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            // Fetch recent events for the logged-in organizer
                            $query = "
                                SELECT 
                                    em.event_name, 
                                    em.event_date, 
                                    em.event_quota, 
                                    em.event_status,
                                    COUNT(ea.volunteer_id) AS participants_registered
                                FROM 
                                    event_management em
                                LEFT JOIN 
                                    event_attendance ea ON em.event_id = ea.event_id
                                WHERE 
                                    em.organizer_id = ? 
                                GROUP BY 
                                    em.event_id
                                ORDER BY 
                                    em.event_date DESC 
                                LIMIT 5"; // Limit to 5 most recent events
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param("i", $organizer_id);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            if ($result->num_rows > 0) {
                                while ($event = $result->fetch_assoc()) {
                                    $status_color = '';
                                    switch ($event['event_status']) {
                                        case 'FINISHED':
                                            $status_color = 'bg-green-100 text-green-800';
                                            break;
                                        case 'ONGOING':
                                            $status_color = 'bg-blue-100 text-blue-800';
                                            break;
                                        case 'OPEN':
                                            $status_color = 'bg-yellow-100 text-yellow-800';
                                            break;
                                        case 'CANCELLED':
                                            $status_color = 'bg-red-100 text-red-800';
                                            break;
                                        default:
                                            $status_color = 'bg-gray-100 text-gray-800';
                                    }
                                    echo "<tr>";
                                    echo "<td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900'>" . htmlspecialchars($event['event_name']) . "</td>";
                                    echo "<td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900'>" . htmlspecialchars($event['event_date']) . "</td>";
                                    echo "<td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900'>" . htmlspecialchars($event['participants_registered']) . "</td>";
                                    echo "<td class='px-6 py-4 whitespace-nowrap text-sm'>";
                                    echo "<span class='px-2 py-1 rounded-full text-xs $status_color'>" . htmlspecialchars($event['event_status']) . "</span>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4' class='px-6 py-4 text-center text-gray-600'>No recent events found.</td></tr>";
                            }
                            $stmt->close();
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js Script -->
    <script>
        // Data for the graphs
        const monthlyEventsData = {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [
                {
                    label: 'Total Events',
                    data: <?php echo json_encode($eventsData); ?>,
                    backgroundColor: 'rgba(59, 130, 246, 0.2)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 2,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: 'rgba(59, 130, 246, 1)',
                    pointHoverRadius: 8,
                    fill: true,
                }
            ]
        };

        const overallEventsData = {
            labels: ['Overall'],
            datasets: [
                {
                    label: 'Total Events',
                    data: [<?php echo $total_events; ?>],
                    backgroundColor: 'rgba(59, 130, 246, 0.2)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 2,
                }
            ]
        };

        const monthlyParticipantsData = {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [
                {
                    label: 'Total Participants',
                    data: <?php echo json_encode($participantsData); ?>,
                    backgroundColor: 'rgba(34, 197, 94, 0.2)',
                    borderColor: 'rgba(34, 197, 94, 1)',
                    borderWidth: 2,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: 'rgba(34, 197, 94, 1)',
                    pointHoverRadius: 8,
                    fill: true,
                }
            ]
        };

        const overallParticipantsData = {
            labels: ['Overall'],
            datasets: [
                {
                    label: 'Total Participants',
                    data: [<?php echo $total_volunteers; ?>],
                    backgroundColor: 'rgba(34, 197, 94, 0.2)',
                    borderColor: 'rgba(34, 197, 94, 1)',
                    borderWidth: 2,
                }
            ]
        };

        const monthlyFeedbackData = {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [
                {
                    label: 'Average Feedback Rating',
                    data: <?php echo json_encode($feedbackData); ?>,
                    backgroundColor: 'rgba(156, 39, 176, 0.2)', // Purple
                    borderColor: 'rgba(156, 39, 176, 1)',
                    borderWidth: 2,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: 'rgba(156, 39, 176, 1)',
                    pointHoverRadius: 8,
                    fill: true,
                }
            ]
        };

        const overallFeedbackData = {
            labels: ['Overall'],
            datasets: [
                {
                    label: 'Average Feedback Rating',
                    data: [<?php echo $average_feedback_rating; ?>],
                    backgroundColor: 'rgba(156, 39, 176, 0.2)', // Purple
                    borderColor: 'rgba(156, 39, 176, 1)',
                    borderWidth: 2,
                }
            ]
        };

        // Bar Chart Data
        const barChartData = {
            labels: <?php echo json_encode($barChartLabels); ?>,
            datasets: [
                {
                    label: 'Participants',
                    data: <?php echo json_encode($barChartData); ?>,
                    backgroundColor: 'rgba(59, 130, 246, 0.6)', // Blue
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 2,
                }
            ]
        };

        // Pie Chart Data
        const pieChartData = {
            labels: <?php echo json_encode($pieChartLabels); ?>,
            datasets: [
                {
                    label: 'Event Status',
                    data: <?php echo json_encode($pieChartData); ?>,
                    backgroundColor: <?php echo json_encode($pieChartColors); ?>,
                    borderWidth: 2,
                }
            ]
        };

        // Chart.js Configuration for Events
        const eventsCtx = document.getElementById('eventsChart').getContext('2d');
        let eventsChart = new Chart(eventsCtx, {
            type: 'line',
            data: monthlyEventsData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: { size: 14, weight: 'bold' },
                        bodyFont: { size: 12 },
                        padding: 10,
                        cornerRadius: 5,
                        displayColors: false,
                    },
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            font: { size: 14, weight: 'bold' },
                            color: '#4B5563',
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: true,
                            color: 'rgba(0, 0, 0, 0.1)',
                        },
                        ticks: {
                            font: { size: 12 },
                            color: '#4B5563',
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: true,
                            color: 'rgba(0, 0, 0, 0.1)',
                        },
                        ticks: {
                            font: { size: 12 },
                            color: '#4B5563',
                        }
                    }
                }
            }
        });

        // Chart.js Configuration for Participants
        const participantsCtx = document.getElementById('participantsChart').getContext('2d');
        let participantsChart = new Chart(participantsCtx, {
            type: 'line',
            data: monthlyParticipantsData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: { size: 14, weight: 'bold' },
                        bodyFont: { size: 12 },
                        padding: 10,
                        cornerRadius: 5,
                        displayColors: false,
                    },
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            font: { size: 14, weight: 'bold' },
                            color: '#4B5563',
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: true,
                            color: 'rgba(0, 0, 0, 0.1)',
                        },
                        ticks: {
                            font: { size: 12 },
                            color: '#4B5563',
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: true,
                            color: 'rgba(0, 0, 0, 0.1)',
                        },
                        ticks: {
                            font: { size: 12 },
                            color: '#4B5563',
                        }
                    }
                }
            }
        });

        // Chart.js Configuration for Feedback
        const feedbackCtx = document.getElementById('feedbackChart').getContext('2d');
        let feedbackChart = new Chart(feedbackCtx, {
            type: 'line',
            data: monthlyFeedbackData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: { size: 14, weight: 'bold' },
                        bodyFont: { size: 12 },
                        padding: 10,
                        cornerRadius: 5,
                        displayColors: false,
                    },
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            font: { size: 14, weight: 'bold' },
                            color: '#4B5563',
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: true,
                            color: 'rgba(0, 0, 0, 0.1)',
                        },
                        ticks: {
                            font: { size: 12 },
                            color: '#4B5563',
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: true,
                            color: 'rgba(0, 0, 0, 0.1)',
                        },
                        ticks: {
                            font: { size: 12 },
                            color: '#4B5563',
                        }
                    }
                }
            }
        });

        // Chart.js Configuration for Bar Chart
        const barCtx = document.getElementById('barChart').getContext('2d');
        const barChart = new Chart(barCtx, {
            type: 'bar',
            data: barChartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: { size: 14, weight: 'bold' },
                        bodyFont: { size: 12 },
                        padding: 10,
                        cornerRadius: 5,
                        displayColors: false,
                    },
                    legend: {
                        display: false,
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                        },
                        ticks: {
                            font: { size: 12 },
                            color: '#4B5563',
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: true,
                            color: 'rgba(0, 0, 0, 0.1)',
                        },
                        ticks: {
                            font: { size: 12 },
                            color: '#4B5563',
                        }
                    }
                }
            }
        });

        // Chart.js Configuration for Pie Chart
        const pieCtx = document.getElementById('pieChart').getContext('2d');
        const pieChart = new Chart(pieCtx, {
            type: 'pie',
            data: pieChartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: { size: 14, weight: 'bold' },
                        bodyFont: { size: 12 },
                        padding: 10,
                        cornerRadius: 5,
                        displayColors: false,
                    },
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            font: { size: 12 },
                            color: '#4B5563',
                        }
                    }
                }
            }
        });

        // Function to update the charts based on the selected display option
        function updateCharts() {
            const displayOption = document.getElementById('graphDisplay').value;

            if (displayOption === 'monthly') {
                eventsChart.data = monthlyEventsData;
                participantsChart.data = monthlyParticipantsData;
                feedbackChart.data = monthlyFeedbackData;
            } else {
                eventsChart.data = overallEventsData;
                participantsChart.data = overallParticipantsData;
                feedbackChart.data = overallFeedbackData;
            }

            eventsChart.update();
            participantsChart.update();
            feedbackChart.update();
        }

        // Add event listener to the graph display selector
        document.getElementById('graphDisplay').addEventListener('change', updateCharts);

        // Function to download a chart as an image
        function downloadChart(chartId, fileName) {
            html2canvas(document.getElementById(chartId)).then((canvas) => {
                const link = document.createElement('a');
                link.download = fileName + '.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            });
        }

        // Download Events Chart
        document.getElementById('downloadEventsChart').addEventListener('click', () => {
            downloadChart('eventsChart', 'events_chart');
        });

        // Download Participants Chart
        document.getElementById('downloadParticipantsChart').addEventListener('click', () => {
            downloadChart('participantsChart', 'participants_chart');
        });

        // Download Feedback Chart
        document.getElementById('downloadFeedbackChart').addEventListener('click', () => {
            downloadChart('feedbackChart', 'feedback_chart');
        });

        // Function to download all analytics as a PDF
        document.getElementById('downloadAllAnalytics').addEventListener('click', () => {
            const pdf = new jspdf.jsPDF('p', 'mm', 'a4'); // Create a new PDF
            const margin = 10; // Margin in mm
            const pageWidth = pdf.internal.pageSize.getWidth() - 2 * margin; // Usable page width

            // Add Events Chart to PDF
            html2canvas(document.getElementById('eventsChart')).then((canvas) => {
                const imgData = canvas.toDataURL('image/png');
                pdf.addImage(imgData, 'PNG', margin, margin, pageWidth, 80); // Add image to PDF
                pdf.text('Total Events Over Time', margin, margin + 90); // Add title

                // Add Participants Chart to PDF
                html2canvas(document.getElementById('participantsChart')).then((canvas) => {
                    const imgData = canvas.toDataURL('image/png');
                    pdf.addPage(); // Add a new page
                    pdf.addImage(imgData, 'PNG', margin, margin, pageWidth, 80); // Add image to PDF
                    pdf.text('Total Participants Over Time', margin, margin + 90); // Add title

                    // Add Feedback Chart to PDF
                    html2canvas(document.getElementById('feedbackChart')).then((canvas) => {
                        const imgData = canvas.toDataURL('image/png');
                        pdf.addPage(); // Add a new page
                        pdf.addImage(imgData, 'PNG', margin, margin, pageWidth, 80); // Add image to PDF
                        pdf.text('Average Feedback Rating Over Time', margin, margin + 90); // Add title

                        // Add Stats Section to PDF
                        pdf.addPage(); // Add a new page
                        pdf.setFontSize(12);
                        pdf.text('Organizer Statistics', margin, margin + 10);
                        pdf.text(`Total Events: ${<?php echo $total_events; ?>}`, margin, margin + 20);
                        pdf.text(`Total Participants: ${<?php echo $total_volunteers; ?>}`, margin, margin + 30);
                        pdf.text(`Average Feedback Rating: ${<?php echo number_format($average_feedback_rating, 2); ?>}`, margin, margin + 40);

                        // Save the PDF
                        pdf.save('organizer_analytics.pdf');
                    });
                });
            });
        });
    </script>

    <!-- Include Flowbite JS (for interactive components) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
</body>
</html>