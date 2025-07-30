<?php
session_start();

require_once 'config.php';

// Check if the user is logged in as a volunteer
if (!isset($_SESSION['volunteer_id'])) {
    header("Location: log_in_volunteer.php");
    exit();
}

// Volunteer ID from the session
$volunteer_id = $_SESSION['volunteer_id'];

// Get filter parameters from the query string
$monthFilter = $_GET['month'] ?? '';
$yearFilter = $_GET['year'] ?? '';

// Fetch volunteer registration date
$sql_registration_date = "SELECT registration_date FROM volunteer WHERE volunteer_id = ?";
$stmt_registration_date = $conn->prepare($sql_registration_date);
$stmt_registration_date->bind_param("i", $volunteer_id);
$stmt_registration_date->execute();
$result_registration_date = $stmt_registration_date->get_result();

if ($result_registration_date->num_rows > 0) {
    $row_registration_date = $result_registration_date->fetch_assoc();
    $registration_year = date('Y', strtotime($row_registration_date['registration_date'])); // Extract year
} else {
    $registration_year = date('Y'); // Default to current year if registration date is not found
}

$stmt_registration_date->close();

// Query to calculate total hours (with filter)
$sql_hours = "
    SELECT 
        SUM(TIMESTAMPDIFF(HOUR, check_in_time, check_out_time)) AS total_hours
    FROM 
        event_attendance
    WHERE 
        volunteer_id = ?
        AND attendance_status = 'Present'
        AND (DATE_FORMAT(check_in_time, '%Y') = ? OR ? = '') -- Year filter
        AND (DATE_FORMAT(check_in_time, '%m') = ? OR ? = '') -- Month filter
";

$stmt_hours = $conn->prepare($sql_hours);
$stmt_hours->bind_param("issss", $volunteer_id, $yearFilter, $yearFilter, $monthFilter, $monthFilter);
$stmt_hours->execute();
$result_hours = $stmt_hours->get_result();

if ($result_hours->num_rows > 0) {
    $row_hours = $result_hours->fetch_assoc();
    $total_hours = $row_hours['total_hours'];
} else {
    $total_hours = 0; // Default to 0 if no records are found
}

$stmt_hours->close();

// Query to count total events joined (with filter)
$sql_events = "
    SELECT 
        COUNT(DISTINCT event_id) AS total_events
    FROM 
        event_attendance
    WHERE 
        volunteer_id = ?
        AND attendance_status = 'Present'
        AND (DATE_FORMAT(check_in_time, '%Y') = ? OR ? = '') -- Year filter
        AND (DATE_FORMAT(check_in_time, '%m') = ? OR ? = '') -- Month filter
";

$stmt_events = $conn->prepare($sql_events);
$stmt_events->bind_param("issss", $volunteer_id, $yearFilter, $yearFilter, $monthFilter, $monthFilter);
$stmt_events->execute();
$result_events = $stmt_events->get_result();

if ($result_events->num_rows > 0) {
    $row_events = $result_events->fetch_assoc();
    $total_events = $row_events['total_events'];
} else {
    $total_events = 0; // Default to 0 if no records are found
}

$stmt_events->close();

// Fetch events joined per month (with filter)
$sql_events_per_month = "
    SELECT 
        DATE_FORMAT(event_date, '%Y-%m') AS month,
        COUNT(DISTINCT event_attendance.event_id) AS events_joined
    FROM 
        event_attendance
        JOIN event_management ON event_attendance.event_id = event_management.event_id
    WHERE 
        event_attendance.volunteer_id = ?
        AND event_attendance.attendance_status = 'Present'
        AND (DATE_FORMAT(event_date, '%Y') = ? OR ? = '') -- Year filter
        AND (DATE_FORMAT(event_date, '%m') = ? OR ? = '') -- Month filter
    GROUP BY 
        DATE_FORMAT(event_date, '%Y-%m')
    ORDER BY 
        month;
";

$stmt_events_per_month = $conn->prepare($sql_events_per_month);
$stmt_events_per_month->bind_param("issss", $volunteer_id, $yearFilter, $yearFilter, $monthFilter, $monthFilter);
$stmt_events_per_month->execute();
$result_events_per_month = $stmt_events_per_month->get_result();

$events_data = [];
while ($row = $result_events_per_month->fetch_assoc()) {
    $events_data[] = $row;
}

$stmt_events_per_month->close();

// Fetch total hours volunteered per month (with filter)
$sql_hours_per_month = "
    SELECT 
        DATE_FORMAT(event_date, '%Y-%m') AS month,
        SUM(TIMESTAMPDIFF(HOUR, check_in_time, check_out_time)) AS hours_volunteered
    FROM 
        event_attendance
        JOIN event_management ON event_attendance.event_id = event_management.event_id
    WHERE 
        event_attendance.volunteer_id = ?
        AND event_attendance.attendance_status = 'Present'
        AND (DATE_FORMAT(event_date, '%Y') = ? OR ? = '') -- Year filter
        AND (DATE_FORMAT(event_date, '%m') = ? OR ? = '') -- Month filter
    GROUP BY 
        DATE_FORMAT(event_date, '%Y-%m')
    ORDER BY 
        month;
";

$stmt_hours_per_month = $conn->prepare($sql_hours_per_month);
$stmt_hours_per_month->bind_param("issss", $volunteer_id, $yearFilter, $yearFilter, $monthFilter, $monthFilter);
$stmt_hours_per_month->execute();
$result_hours_per_month = $stmt_hours_per_month->get_result();

$hours_data = [];
while ($row = $result_hours_per_month->fetch_assoc()) {
    $hours_data[] = $row;
}

$stmt_hours_per_month->close();

// Fetch volunteer name
$sql_name = "SELECT volunteer_name FROM volunteer WHERE volunteer_id = ?";
$stmt_name = $conn->prepare($sql_name);
$stmt_name->bind_param("i", $volunteer_id);
$stmt_name->execute();
$result_name = $stmt_name->get_result();

if ($result_name->num_rows > 0) {
    $row_name = $result_name->fetch_assoc();
    $volunteer_name = $row_name['volunteer_name'];
} else {
    $volunteer_name = "Volunteer"; // Default name if not found
}

$stmt_name->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer Dashboard</title>
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
    <?php include 'volunteer_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="p-6 lg:ml-64 transition-all duration-300"> <!-- Adjusted margin to account for sidebar -->
        <!-- Header -->
        <header class="mb-8">
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Welcome, <?php echo $volunteer_name; ?>!</h1>
        </header>

        <!-- Filter Section -->
        <form method="GET" class="mb-8 bg-white p-4 md:p-6 rounded-lg shadow-sm">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Month Selector -->
                <div>
                    <label for="month" class="block text-sm font-medium text-gray-700 mb-1">Month</label>
                    <select id="month" name="month" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select Month</option>
                        <?php
                        for ($i = 1; $i <= 12; $i++) {
                            $month = str_pad($i, 2, '0', STR_PAD_LEFT);
                            $selected = ($month == $monthFilter) ? 'selected' : '';
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
                            $selected = ($i == $yearFilter) ? 'selected' : '';
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
        <div class="mb-8 bg-white p-4 md:p-6 rounded-lg shadow-sm">
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
            <button id="downloadHoursChart" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 focus:ring-2 focus:ring-red-500">
                Download Hours Chart
            </button>
            <button id="downloadAllAnalytics" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 focus:ring-2 focus:ring-green-500">
                Download All Analytics (PDF)
            </button>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Total Hours Volunteered -->
            <div class="bg-white p-4 md:p-6 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Total Hours Volunteered</h3>
                <p class="text-2xl md:text-3xl font-bold text-gray-900"><?php echo $total_hours; ?> hours</p>
            </div>
            <!-- Total Events Joined -->
            <div class="bg-white p-4 md:p-6 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Total Events Joined</h3>
                <p class="text-2xl md:text-3xl font-bold text-gray-900"><?php echo $total_events; ?> events</p>
            </div>
        </div>

        <!-- Graph Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Events Joined Graph -->
            <div class="bg-white p-4 md:p-6 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Events Joined Over Time</h2>
                <div class="w-full h-64 md:h-96">
                    <canvas id="eventsChart"></canvas>
                </div>
            </div>
            <!-- Hours Volunteered Graph -->
            <div class="bg-white p-4 md:p-6 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Hours Volunteered Over Time</h2>
                <div class="w-full h-64 md:h-96">
                    <canvas id="hoursChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js Script -->
    <script>
        // Data for the graphs
        const monthlyEventsData = {
            labels: <?php echo json_encode(array_column($events_data, 'month')); ?>,
            datasets: [
                {
                    label: 'Events Joined',
                    data: <?php echo json_encode(array_column($events_data, 'events_joined')); ?>,
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
                    label: 'Total Events Joined',
                    data: [<?php echo $total_events; ?>],
                    backgroundColor: 'rgba(59, 130, 246, 0.2)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 2,
                }
            ]
        };

        const monthlyHoursData = {
            labels: <?php echo json_encode(array_column($hours_data, 'month')); ?>,
            datasets: [
                {
                    label: 'Hours Volunteered',
                    data: <?php echo json_encode(array_column($hours_data, 'hours_volunteered')); ?>,
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

        const overallHoursData = {
            labels: ['Overall'],
            datasets: [
                {
                    label: 'Total Hours Volunteered',
                    data: [<?php echo $total_hours; ?>],
                    backgroundColor: 'rgba(34, 197, 94, 0.2)',
                    borderColor: 'rgba(34, 197, 94, 1)',
                    borderWidth: 2,
                }
            ]
        };

        // Chart.js Configuration for Events Joined
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

        // Chart.js Configuration for Hours Volunteered
        const hoursCtx = document.getElementById('hoursChart').getContext('2d');
        let hoursChart = new Chart(hoursCtx, {
            type: 'line',
            data: monthlyHoursData,
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

        // Function to update the charts based on the selected display option
        function updateCharts() {
            const displayOption = document.getElementById('graphDisplay').value;

            if (displayOption === 'monthly') {
                eventsChart.data = monthlyEventsData;
                hoursChart.data = monthlyHoursData;
            } else {
                eventsChart.data = overallEventsData;
                hoursChart.data = overallHoursData;
            }

            eventsChart.update();
            hoursChart.update();
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

        // Download Hours Chart
        document.getElementById('downloadHoursChart').addEventListener('click', () => {
            downloadChart('hoursChart', 'hours_chart');
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
                pdf.text('Events Joined Over Time', margin, margin + 90); // Add title

                // Add Hours Chart to PDF
                html2canvas(document.getElementById('hoursChart')).then((canvas) => {
                    const imgData = canvas.toDataURL('image/png');
                    pdf.addPage(); // Add a new page
                    pdf.addImage(imgData, 'PNG', margin, margin, pageWidth, 80); // Add image to PDF
                    pdf.text('Hours Volunteered Over Time', margin, margin + 90); // Add title

                    // Add Stats Section to PDF
                    pdf.addPage(); // Add a new page
                    pdf.setFontSize(12);
                    pdf.text('Volunteer Statistics', margin, margin + 10);
                    pdf.text(`Total Hours Volunteered: ${<?php echo $total_hours; ?>} hours`, margin, margin + 20);
                    pdf.text(`Total Events Joined: ${<?php echo $total_events; ?>} events`, margin, margin + 30);

                    // Save the PDF
                    pdf.save('volunteer_analytics.pdf');
                });
            });
        });
    </script>

    <!-- Include Flowbite JS (for interactive components) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
</body>
</html>