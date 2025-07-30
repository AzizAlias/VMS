<?php
session_start();

// Check if the organizer is logged in
if (!isset($_SESSION['organizer_id'])) {
    header("Location: log_in_organizer.php");
    exit;
}

require_once '../volunteer/config.php';

// Debugging: Check if chat_id is present in the URL
if (!isset($_GET['chat_id'])) {
    die("Chat ID is missing. Current URL: " . $_SERVER['REQUEST_URI']);
}
$chat_id = $_GET['chat_id'];

// Fetch messages for the current chat
$sql = "SELECT m.*, v.volunteer_name, v.volunteer_profile_pic, f.file_name, f.file_path, f.file_type
        FROM messages m
        LEFT JOIN volunteer v ON m.sender_id = v.volunteer_id AND m.sender_type = 'volunteer'
        LEFT JOIN message_files f ON m.message_id = f.message_id
        WHERE m.chat_id = ?
        ORDER BY m.sent_at ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $chat_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

// Fetch the volunteer's details for the chat header
$volunteer_sql = "SELECT v.volunteer_name, v.volunteer_profile_pic
                  FROM chats c
                  JOIN volunteer v ON c.volunteer_id = v.volunteer_id
                  WHERE c.chat_id = ?";
$volunteer_stmt = $conn->prepare($volunteer_sql);
$volunteer_stmt->bind_param("i", $chat_id);
$volunteer_stmt->execute();
$volunteer_result = $volunteer_stmt->get_result();
$volunteer = $volunteer_result->fetch_assoc();

// Debugging: Output the volunteer profile picture path
$profilePicPath = !empty($volunteer['volunteer_profile_pic']) ? '../uploads/volunteer profile pics/' . htmlspecialchars($volunteer['volunteer_profile_pic']) : 'path/to/default-profile-pic.jpg';

$stmt->close();
$volunteer_stmt->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organizer Chat</title>
    <!-- Include Flowbite CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet" />
    <!-- Include Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<!-- Main Content -->
<?php include 'organizer_sidebar.php'; ?>
<div class="p-6 lg:ml-72 transition-all duration-300">
    <div class="max-w-3xl mx-auto">
        <!-- Chat Header -->
        <header class="bg-white p-4 md:p-6 rounded-t-lg shadow-md flex items-center space-x-4">
            <!-- Volunteer Profile Picture -->
            <img src="<?php echo $profilePicPath; ?>" alt="<?php echo htmlspecialchars($volunteer['volunteer_name']); ?>" class="w-10 h-10 md:w-12 md:h-12 rounded-full object-cover shadow-sm">
            <div>
                <h1 class="text-lg md:text-xl font-bold text-gray-800"><?php echo htmlspecialchars($volunteer['volunteer_name']); ?></h1>
                <p class="text-xs md:text-sm text-gray-500">Online</p>
            </div>
        </header>

        <!-- Chat Container -->
        <div id="chat-container" class="bg-white p-4 md:p-6 rounded-b-lg shadow-md h-[400px] md:h-[500px] overflow-y-auto flex flex-col space-y-4">
            <?php foreach ($messages as $message): ?>
                <!-- Organizer Message (Right) -->
                <?php if ($message['sender_type'] === 'organizer'): ?>
                    <div class="flex justify-end">
                        <div class="bg-blue-500 text-white p-2 md:p-3 rounded-lg max-w-[70%] shadow-sm">
                            <?php if ($message['file_path']): ?>
                                <!-- File Message -->
                                <a href="<?php echo htmlspecialchars($message['file_path']); ?>" target="_blank" class="text-white hover:underline">
                                    📄 <?php echo htmlspecialchars($message['file_name']); ?>
                                </a>
                            <?php else: ?>
                                <!-- Text Message -->
                                <div class="text-sm">
                                    <?php echo htmlspecialchars($message['message_text']); ?>
                                </div>
                            <?php endif; ?>
                            <div class="text-xs text-blue-100 mt-1 text-right">
                                <?php echo date('h:i A', strtotime($message['sent_at'])); ?>
                            </div>
                        </div>
                    </div>
                <!-- Volunteer Message (Left) -->
                <?php else: ?>
                    <div class="flex justify-start">
                        <div class="bg-gray-200 p-2 md:p-3 rounded-lg max-w-[70%] shadow-sm">
                            <?php if ($message['file_path']): ?>
                                <!-- File Message -->
                                <a href="<?php echo htmlspecialchars($message['file_path']); ?>" target="_blank" class="text-blue-500 hover:underline">
                                    📄 <?php echo htmlspecialchars($message['file_name']); ?>
                                </a>
                            <?php else: ?>
                                <!-- Text Message -->
                                <div class="text-sm text-gray-700">
                                    <?php echo htmlspecialchars($message['message_text']); ?>
                                </div>
                            <?php endif; ?>
                            <div class="text-xs text-gray-500 mt-1">
                                <?php echo date('h:i A', strtotime($message['sent_at'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <!-- Message Input -->
        <div id="message-input" class="mt-4 md:mt-6 flex gap-2">
            <textarea id="message" placeholder="Type a message..." rows="1" class="flex-1 p-2 md:p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
            <input type="file" id="file-input" class="hidden" accept="image/*, .pdf, .doc, .docx" />
            <button onclick="sendMessage()" class="px-4 md:px-6 py-2 md:py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                Send
            </button>
            <button onclick="document.getElementById('file-input').click()" class="px-4 md:px-6 py-2 md:py-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500">
                📎
            </button>
        </div>
    </div>
</div>

<!-- WebSocket Script -->
<script>
    const chatId = <?php echo $chat_id; ?>; // Use the chat ID from the URL
    const senderType = 'organizer'; // Organizer
    const senderId = <?php echo $_SESSION['organizer_id']; ?>; // Organizer ID

    const ws = new WebSocket('ws://localhost:8080');

    ws.onopen = function() {
        console.log('Connected to WebSocket server');
        ws.send(JSON.stringify({
            action: 'init_chat',
            chat_id: chatId,
            sender_type: senderType,
            sender_id: senderId
        }));
    };

    ws.onmessage = function(event) {
        const message = JSON.parse(event.data);
        const chatContainer = document.getElementById('chat-container');

        const messageDiv = document.createElement('div');
        if (message.sender_type === 'organizer' || message.sender_type === 'volunteer') {
            messageDiv.className = message.sender_type === senderType ? 'flex justify-end' : 'flex justify-start';
            messageDiv.innerHTML = `
                <div class="${message.sender_type === senderType ? 'bg-blue-500 text-white' : 'bg-gray-200'} p-3 rounded-lg max-w-[70%] shadow-sm">
                    ${message.file_path ? `
                        <a href="${message.file_path}" target="_blank" class="${message.sender_type === senderType ? 'text-white hover:underline' : 'text-blue-500 hover:underline'}">
                            📄 ${message.file_name}
                        </a>
                    ` : `
                        <div class="text-sm">
                            ${message.message_text}
                        </div>
                    `}
                    <div class="text-xs ${message.sender_type === senderType ? 'text-blue-100' : 'text-gray-500'} mt-1 ${message.sender_type === senderType ? 'text-right' : ''}">
                        ${new Date().toLocaleTimeString()}
                    </div>
                </div>
            `;
        }

        chatContainer.appendChild(messageDiv);
        chatContainer.scrollTop = chatContainer.scrollHeight; // Auto-scroll
    };

    function sendMessage() {
        const messageInput = document.getElementById('message');
        const messageText = messageInput.value;

        if (messageText) {
            const message = {
                chat_id: chatId,
                sender_type: senderType,
                sender_id: senderId,
                message_text: messageText
            };

            ws.send(JSON.stringify(message));
            messageInput.value = '';
        }
    }

    // Handle file uploads
    document.getElementById('file-input').addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (file) {
            uploadFile(file);
        }
    });

    function uploadFile(file) {
        const formData = new FormData();
        formData.append('file', file);

        fetch('../api/upload_file.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Send the file metadata as a message
                const message = {
                    chat_id: chatId,
                    sender_type: senderType,
                    sender_id: senderId,
                    message_text: '', // Empty string for file messages
                    file_path: data.file_path,
                    file_name: data.file_name,
                    file_type: data.file_type
                };
                ws.send(JSON.stringify(message)); // Send file metadata via WebSocket
            } else {
                alert('File upload failed: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error uploading file:', error);
        });
    }
</script>

<!-- Include Flowbite JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
</body>
</html>