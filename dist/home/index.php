<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Volunteer Management System</title>
    <!-- Include Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Include Flowbite CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet" />
</head>
<body class="bg-gray-50 font-sans">

    <!-- Navigation Bar -->
    <nav class="bg-[#0047AB] p-4 shadow-lg"> <!-- Cobalt Blue -->
        <div class="container mx-auto flex justify-between items-center">
            <img src="../icon/uniserve.png" class="h-12" alt="UniServe Logo" />
            <div class="space-x-4">
                <a href="../volunteer/log_in_volunteer.php" class="text-white hover:text-gray-200">Volunteer Login</a>
                <a href="../organizer/log_in_organizer.php" class="text-white hover:text-gray-200">Organizer Login</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="bg-[#0047AB] text-white py-20"> <!-- Cobalt Blue -->
        <div class="container mx-auto text-center">
            <h1 class="text-5xl font-bold mb-4">Welcome to UniServe</h1>
            <p class="text-xl mb-8">Your one-stop platform for managing university volunteer activities, events, and opportunities.</p>
            <div class="space-x-4">
                <a href="../volunteer/sign-up_volunteer.php" class="bg-white text-[#0047AB] px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition duration-200">Sign Up as Volunteer</a>
                <a href="../organizer/sign-up_organizer.php" class="bg-transparent border border-white text-white px-6 py-3 rounded-lg font-semibold hover:bg-white hover:text-[#0047AB] transition duration-200">Sign Up as Organizer</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-16 bg-gray-100">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-12 text-gray-800">Key Features</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1: Volunteer Profile Management -->
                <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200">
                    <h3 class="text-xl font-semibold mb-4 text-[#0047AB]">Volunteer Profile Management</h3> <!-- Cobalt Blue -->
                    <p class="text-gray-700">Volunteers can sign up and manage their personal information, skills, and availability.</p>
                </div>

                <!-- Feature 2: Opportunity Posting and Management -->
                <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200">
                    <h3 class="text-xl font-semibold mb-4 text-[#0047AB]">Opportunity Posting</h3> <!-- Cobalt Blue -->
                    <p class="text-gray-700">Organizers can post volunteer opportunities such as events or projects.</p>
                </div>

                <!-- Feature 3: Volunteer Matching and Assignment -->
                <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200">
                    <h3 class="text-xl font-semibold mb-4 text-[#0047AB]">Volunteer Matching</h3> <!-- Cobalt Blue -->
                    <p class="text-gray-700">Match volunteers with suitable opportunities based on their availability.</p>
                </div>

                <!-- Feature 4: Scheduling and Shift Management -->
                <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200">
                    <h3 class="text-xl font-semibold mb-4 text-[#0047AB]">Shift Management</h3> <!-- Cobalt Blue -->
                    <p class="text-gray-700">Create and manage shifts for events with details like date, time, and location.</p>
                </div>

                <!-- Feature 5: Communication and Reminder -->
                <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200">
                    <h3 class="text-xl font-semibold mb-4 text-[#0047AB]">Communication</h3> <!-- Cobalt Blue -->
                    <p class="text-gray-700">Facilitate communication between volunteers and organizers through messages.</p>
                </div>

                <!-- Feature 6: Activity Tracking and Reporting -->
                <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200">
                    <h3 class="text-xl font-semibold mb-4 text-[#0047AB]">Activity Tracking</h3> <!-- Cobalt Blue -->
                    <p class="text-gray-700">Track volunteer hours, completed tasks, and generate reports for analysis.</p>
                </div>

                <!-- Feature 7: Feedback and Evaluation -->
                <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200">
                    <h3 class="text-xl font-semibold mb-4 text-[#0047AB]">Feedback</h3> <!-- Cobalt Blue -->
                    <p class="text-gray-700">Volunteers can provide feedback on their experiences.</p>
                </div>

                <!-- Feature 8: Discussion Board -->
                <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200">
                    <h3 class="text-xl font-semibold mb-4 text-[#0047AB]">Discussion Board</h3> <!-- Cobalt Blue -->
                    <p class="text-gray-700">Volunteers can interact through posts and comments on various topics.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-[#0047AB] text-white py-8"> <!-- Cobalt Blue -->
        <div class="container mx-auto text-center">
            <p class="mb-4">&copy; 2024 UniServe. All rights reserved.</p>

        </div>
    </footer>


</body>
</html>