<?php
session_start();

// Check if the volunteer is logged in
if (!isset($_SESSION['volunteer_id'])) {
    header("Location: log_in_volunteer.php"); // Redirect to volunteer login page
    exit;
}

require_once 'config.php'; // Adjust the path as needed

// Fetch all chats for the logged-in volunteer
$volunteer_id = $_SESSION['volunteer_id'];
$sql = "SELECT c.chat_id, o.organizer_name, o.organizer_profile_picture, MAX(m.sent_at) AS last_message_time
        FROM chats c
        JOIN organizer o ON c.organizer_id = o.organizer_id
        LEFT JOIN messages m ON c.chat_id = m.chat_id
        WHERE c.volunteer_id = ?
        GROUP BY c.chat_id
        ORDER BY last_message_time DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $volunteer_id);
$stmt->execute();
$result = $stmt->get_result();

$chats = [];
while ($row = $result->fetch_assoc()) {
    $chats[] = $row;
}

$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer Chat List</title>
    <!-- Include Flowbite CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet" />
    <!-- Include Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<?php include 'volunteer_sidebar.php'; ?>

<!-- Main Content -->
<div class="p-6 lg:ml-72 transition-all duration-300">
    <div class="container mx-auto">
        <h1 class="text-3xl font-bold text-center mb-8">Chats</h1>
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <?php if (empty($chats)): ?>
                <p class="p-4 text-gray-600">No chats available.</p>
            <?php else: ?>
                <?php foreach ($chats as $chat): ?>
                    <?php
                    // Debugging: Output the profile picture path
                    $profilePicPath = !empty($chat['organizer_profile_picture']) ? '../uploads/organizer profile pics/' . htmlspecialchars($chat['organizer_profile_picture']) : 'path/to/default-profile-pic.jpg';
                    ?>
                    <a href="volunteer_chat.php?chat_id=<?php echo $chat['chat_id']; ?>" class="block hover:bg-gray-50 transition duration-150 ease-in-out">
                        <div class="flex items-center p-4 border-b border-gray-200">
                            <!-- Organizer Profile Picture -->
                            <img src="<?php echo $profilePicPath; ?>" alt="<?php echo htmlspecialchars($chat['organizer_name']); ?>" class="w-12 h-12 rounded-full object-cover">
                            <!-- Organizer Name and Last Message -->
                            <div class="ml-4 flex-1">
                                <h3 class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($chat['organizer_name']); ?></h3>
                                <p class="text-sm text-gray-600">
                                    Last message: <?php echo $chat['last_message_time'] ? date('h:i A', strtotime($chat['last_message_time'])) : 'No messages yet'; ?>
                                </p>
                            </div>
                            <!-- Last Message Time -->
                            <div class="text-sm text-gray-500">
                                <?php echo $chat['last_message_time'] ? date('M d', strtotime($chat['last_message_time'])) : ''; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Include Flowbite JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
</body>
</html>