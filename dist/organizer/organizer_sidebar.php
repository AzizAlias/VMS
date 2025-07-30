<?php
// Include profile_pic_config.php (which handles session_start() and database queries)
include 'profile_pic_config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organizer Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white font-sans min-h-screen">

<!-- Sidebar Toggle Button (Visible on small screens) -->
<button id="sidebar-toggle" class="fixed top-4 left-4 z-50 p-2 bg-[#0047AB] text-white rounded-lg lg:hidden">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
    </svg>
</button>

<!-- Sidebar -->
<nav id="sidebar" class="sidebar h-screen w-72 fixed top-0 left-0 bg-[#0047AB] text-white flex flex-col shadow-lg overflow-y-auto transform -translate-x-full lg:translate-x-0 transition-transform duration-300">
    <!-- Logo and Brand Name -->
    <div class="logo-container p-6 border-b border-white/10">
        <a class="flex items-center space-x-3">
            <img src="../icon/uniserve.png" class="h-12" alt="UniServe Logo" />
        </a>
    </div>

    <!-- Navigation Links -->
    <div class="nav-links p-6 flex-grow space-y-2">
        <a href="organizer_dashboard.php" class="sidebar-link block p-3 text-white hover:bg-white/10 rounded-lg transition-all duration-300 hover:translate-x-2">Dashboard</a>
        
        <!-- Event Dropdown -->
        <div class="event-dropdown">
            <button onclick="toggleDropdown('event-dropdown-menu')" class="sidebar-link block w-full p-3 text-white hover:bg-white/10 rounded-lg transition-all duration-300 hover:translate-x-2 text-left flex items-center justify-between">
                <span>Event</span>
                <svg id="event-dropdown-icon" class="w-4 h-4 ml-2 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            <div id="event-dropdown-menu" class="event-dropdown-menu pl-6 mt-2 space-y-2 hidden">
                <a href="post_event.php" class="sidebar-link block p-3 text-white hover:bg-white/10 rounded-lg transition-all duration-300 hover:translate-x-2">Post Event</a>
                <a href="manage_event.php" class="sidebar-link block p-3 text-white hover:bg-white/10 rounded-lg transition-all duration-300 hover:translate-x-2">Manage Event</a>
            </div>
        </div>

        <!-- Shift Dropdown -->
        <div class="shift-dropdown">
            <button onclick="toggleDropdown('shift-dropdown-menu')" class="sidebar-link block w-full p-3 text-white hover:bg-white/10 rounded-lg transition-all duration-300 hover:translate-x-2 text-left flex items-center justify-between">
                <span>Shift</span>
                <svg id="shift-dropdown-icon" class="w-4 h-4 ml-2 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            <div id="shift-dropdown-menu" class="shift-dropdown-menu pl-6 mt-2 space-y-2 hidden">
                <a href="add_event_shift.php" class="sidebar-link block p-3 text-white hover:bg-white/10 rounded-lg transition-all duration-300 hover:translate-x-2">Add Shift</a>
                <a href="manage_shift.php" class="sidebar-link block p-3 text-white hover:bg-white/10 rounded-lg transition-all duration-300 hover:translate-x-2">Manage Shift</a>
            </div>
        </div>

        <a href="event_invitations.php" class="sidebar-link block p-3 text-white hover:bg-white/10 rounded-lg transition-all duration-300 hover:translate-x-2">Invite</a>
        <a href="manage_registrations.php" class="sidebar-link block p-3 text-white hover:bg-white/10 rounded-lg transition-all duration-300 hover:translate-x-2">Approval Request</a>
        <a href="attendance_record.php" class="sidebar-link block p-3 text-white hover:bg-white/10 rounded-lg transition-all duration-300 hover:translate-x-2">Event Attendance</a>
        <a href="event_feedback_review.php" class="sidebar-link block p-3 text-white hover:bg-white/10 rounded-lg transition-all duration-300 hover:translate-x-2">Feedback</a>
        <a href="organizer_chat_list.php" class="sidebar-link block p-3 text-white hover:bg-white/10 rounded-lg transition-all duration-300 hover:translate-x-2">Messaging</a>
    </div>

    <!-- Profile Section with Dropdown -->
    <div class="profile-section p-6 mt-auto border-t border-white/10">
        <div class="flex items-center space-x-3 cursor-pointer" onclick="toggleDropdown('profile-dropdown')">
            <!-- Organizer Profile Picture -->
            <img src="<?php echo $profile_picture; ?>" alt="Profile Picture" class="w-10 h-10 rounded-full border-2 border-white/30" />
            <!-- Organizer Name -->
            <span class="profile-name text-lg font-medium"><?php echo $organizer_name; ?></span>
        </div>
        <!-- Dropdown Menu -->
        <div id="profile-dropdown" class="dropdown-menu mt-2 hidden bg-[#0047AB] rounded-lg shadow-lg p-2">
            <a href="edit_profile_organizer.php" class="block p-2 text-white hover:bg-white/10 rounded-lg transition-all duration-300">Edit Profile</a>
            <a href="log_out_organizer.php" class="block p-2 text-white hover:bg-white/10 rounded-lg transition-all duration-300">Logout</a>
        </div>
    </div>
</nav>

<!-- Main Content -->
<div class="p-6 lg:pl-80 transition-all duration-300">
    <!-- Your main content goes here -->
</div>

<!-- Include Flowbite JavaScript -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>

<!-- Custom JavaScript -->
<script>
    // Function to toggle dropdown menus
    function toggleDropdown(menuId) {
        const menu = document.getElementById(menuId);
        menu.classList.toggle('hidden');
        if (menuId === 'event-dropdown-menu' || menuId === 'shift-dropdown-menu') {
            const icon = document.getElementById(`${menuId}-icon`);
            icon.classList.toggle('rotate-180');
        }
    }

    // Close the dropdown if clicked outside
    document.addEventListener('click', function(event) {
        const dropdown = document.getElementById('profile-dropdown');
        const profileSection = document.querySelector('.profile-section');
        if (!profileSection.contains(event.target)) {
            dropdown.classList.add('hidden');
        }
    });

    // Manage sidebar state
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const mainContent = document.querySelector('.main-content');

        // Check localStorage for sidebar state
        const isSidebarOpen = localStorage.getItem('sidebarOpen') === 'true';

        // Set initial state based on localStorage and screen size
        if (window.innerWidth < 1024) {
            // On small screens, use the saved state
            if (isSidebarOpen) {
                sidebar.classList.remove('-translate-x-full');
            } else {
                sidebar.classList.add('-translate-x-full');
            }
        } else {
            // On larger screens, always show the sidebar
            sidebar.classList.remove('-translate-x-full');
        }

        // Toggle sidebar and save state to localStorage
        sidebarToggle.addEventListener('click', () => {
            const isOpen = !sidebar.classList.contains('-translate-x-full');
            sidebar.classList.toggle('-translate-x-full');
            localStorage.setItem('sidebarOpen', !isOpen);
        });

        // Update state on window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) {
                // On larger screens, always show the sidebar
                sidebar.classList.remove('-translate-x-full');
            } else {
                // On small screens, use the saved state
                const isSidebarOpen = localStorage.getItem('sidebarOpen') === 'true';
                if (isSidebarOpen) {
                    sidebar.classList.remove('-translate-x-full');
                } else {
                    sidebar.classList.add('-translate-x-full');
                }
            }
        });
    });
</script>
</body>
</html>