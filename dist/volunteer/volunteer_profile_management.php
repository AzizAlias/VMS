<?php
session_start();



// Check if the user is logged in as a volunteer
if (!isset($_SESSION['volunteer_id'])) {
    header("Location: log_in_volunteer.php");
    exit();
}

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = ""; // Set your MySQL password here
$dbname = "volunteer_management"; // Replace with your database name

// Create database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch the volunteer's profile information
$volunteer_id = $_SESSION['volunteer_id'];
$sql = "SELECT * FROM volunteer WHERE volunteer_id = $volunteer_id";
$result = $conn->query($sql);
$volunteer = $result->fetch_assoc();

$conn->close();
?>

<!DOCTYPE html> 
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Profile</title>
    <link href="../../src/output.css" rel="stylesheet">
</head>
<body class="bg-gray-50">



<section class="bg-gray-50 dark:bg-gray-900">
    <div class="flex justify-start p-5">
        <a href="volunteer_homepage.php" class="text-white bg-gray-500 hover:bg-gray-600 font-medium rounded-lg text-sm px-5 py-2.5">Back to Homepage</a>
    </div>
    <div class="flex flex-col items-center justify-center px-6 py-8 mx-auto md:h-screen lg:py-0">
        <div class="w-full bg-white rounded-lg shadow sm:max-w-md xl:p-0">
            <div class="p-6 space-y-4 md:space-y-6 sm:p-8 text-center">
                
                <!-- Profile Picture -->
                <div class="flex justify-center mb-4">
                    <?php if (!empty($volunteer['volunteer_profile_pic'])): ?>
                        <img src="../uploads/volunteer profile pics/<?php echo htmlspecialchars($volunteer['volunteer_profile_pic']); ?>" alt="Profile Picture" class="rounded-full w-24 h-24 object-cover">
                    <?php else: ?>
                        <p>No profile picture uploaded.</p>
                    <?php endif; ?>
                </div>

                <!-- Volunteer Details -->
                <h1 class="text-xl font-bold leading-tight text-gray-900 md:text-2xl">
                    Your Profile
                </h1>
                
                <div class="space-y-4 text-left">
                    <div>
                        <label class="block text-sm font-medium text-gray-900">Name:</label>
                        <p><?php echo htmlspecialchars($volunteer['volunteer_name']); ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-900">Email:</label>
                        <p><?php echo htmlspecialchars($volunteer['volunteer_email']); ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-900">Phone Number:</label>
                        <p><?php echo htmlspecialchars($volunteer['volunteer_phone_number']); ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-900">Date of Birth:</label>
                        <p><?php echo htmlspecialchars($volunteer['volunteer_DOB']); ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-900">Address:</label>
                        <p><?php echo htmlspecialchars($volunteer['volunteer_address']); ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-900">Availability:</label>
                        <p>
                            <?php
                            // Convert comma-separated days into a readable format
                            $availability = explode(',', $volunteer['volunteer_availability']);
                            echo htmlspecialchars(implode(', ', $availability));
                            ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

</body>
</html>
