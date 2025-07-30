<?php
session_start();

// Check if the organizer is logged in
if (!isset($_SESSION['organizer_id'])) {
    header("Location: log_in_organizer.php"); // Redirect to organizer login page
    exit;
}

require_once '../volunteer/config.php';

// Debugging: Check if chat_id is present in the URL
if (!isset($_GET['chat_id'])) {
    die("Chat ID is missing. Please ensure the chat_id is passed in the URL.");
}
$chat_id = $_GET['chat_id'];
echo "Chat ID: " . $chat_id . "<br>"; // Debugging statement

// Fetch messages for the current chat
$sql = "SELECT m.*, v.volunteer_name, v.volunteer_profile_pic
        FROM messages m
        LEFT JOIN volunteers v ON m.sender_id = v.volunteer_id AND m.sender_type = 'volunteer'
        WHERE m.chat_id = ?
        ORDER BY m.sent_at ASC";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error); // Debugging: Print the database error
}

$stmt->bind_param("i", $chat_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

echo "Number of Messages: " . count($messages) . "<br>"; // Debugging: Print the number of messages

// Fetch the volunteer's details for the chat header
$volunteer_sql = "SELECT v.volunteer_name, v.volunteer_profile_pic
                  FROM chats c
                  JOIN volunteers v ON c.volunteer_id = v.volunteer_id
                  WHERE c.chat_id = ?";
$volunteer_stmt = $conn->prepare($volunteer_sql);

if (!$volunteer_stmt) {
    die("Database error: " . $conn->error); // Debugging: Print the database error
}

$volunteer_stmt->bind_param("i", $chat_id);
$volunteer_stmt->execute();
$volunteer_result = $volunteer_stmt->get_result();
$volunteer = $volunteer_result->fetch_assoc();

$stmt->close();
$volunteer_stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organizer Chat</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        header {
            background-color: #2196F3;
            color: white;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        #chat-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 20px;
            overflow-y: auto;
            background-color: white;
            margin: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .message {
            max-width: 60%;
            margin-bottom: 15px;
            padding: 10px 15px;
            border-radius: 8px;
            position: relative;
        }

        .message.organizer {
            background-color: #e3f2fd;
            align-self: flex-end;
        }

        .message.volunteer {
            background-color: #f1f1f1;
            align-self: flex-start;
        }

        .message .sender {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }

        .message .text {
            font-size: 14px;
            color: #333;
        }

        .message .time {
            font-size: 10px;
            color: #999;
            text-align: right;
        }

        #message-input {
            display: flex;
            padding: 10px;
            background-color: white;
            border-top: 1px solid #ddd;
        }

        #message-input textarea {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: none;
            font-size: 14px;
        }

        #message-input button {
            background-color: #2196F3;
            color: white;
            border: none;
            padding: 10px 20px;
            margin-left: 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        #message-input button:hover {
            background-color: #1e88e5;
        }
    </style>
</head>
<body>
    <header>
        <h1>Chat with <?php echo htmlspecialchars($volunteer['volunteer_name']); ?></h1>
    </header>

    <div id="chat-container">
        <?php foreach ($messages as $message): ?>
            <div class="message <?php echo $message['sender_type'] === 'organizer' ? 'organizer' : 'volunteer'; ?>">
                <div class="sender">
                    <?php echo $message['sender_type'] === 'organizer' ? 'You' : htmlspecialchars($volunteer['volunteer_name']); ?>
                </div>
                <div class="text"><?php echo htmlspecialchars($message['message_text']); ?></div>
                <div class="time"><?php echo date('h:i A', strtotime($message['sent_at'])); ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div id="message-input">
        <textarea id="message" placeholder="Type a message..." rows="1"></textarea>
        <button onclick="sendMessage()">Send</button>
    </div>

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
            messageDiv.className = `message ${message.sender_type}`;
            messageDiv.innerHTML = `
                <div class="sender">${message.sender_type === 'organizer' ? 'You' : 'Volunteer'}</div>
                <div class="text">${message.message_text}</div>
                <div class="time">${new Date().toLocaleTimeString()}</div>
            `;

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
    </script>
</body>
</html>