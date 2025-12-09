const WebSocket = require('ws');
const mysql = require('mysql2');


const wss = new WebSocket.Server({ port: 8080 });

console.log('WebSocket server is running on ws://localhost:8080');

// Database connection
const db = mysql.createConnection({
    host: 'localhost',
    user: 'root', 
    password: '',
    database: 'volunteer_management' 
});

db.connect((err) => {
    if (err) {
        console.error('Database connection failed:', err.stack);
        return;
    }
    console.log('Connected to the database');
});

// Map to track which client is in which chat session
const chatClients = new Map();

// Handle new connections
wss.on('connection', function connection(ws) {
    console.log('A new client connected');

    // Handle incoming messages
    ws.on('message', function incoming(message) {
        console.log('Received:', message.toString());

        // Parse the message
        const parsedMessage = JSON.parse(message);

        // Handle chat session initialization
        if (parsedMessage.action === 'init_chat') {
            console.log('Chat session initialized:', parsedMessage.chat_id);
            // Store the chat_id and sender type for this client
            chatClients.set(ws, {
                chat_id: parsedMessage.chat_id,
                sender_type: parsedMessage.sender_type,
                sender_id: parsedMessage.sender_id
            });
            return;
        }

        // Save the message to the database
        const sql = "INSERT INTO messages (chat_id, sender_type, sender_id, message_text, file_path, file_name, file_type) VALUES (?, ?, ?, ?, ?, ?, ?)";
        const values = [
            parsedMessage.chat_id,
            parsedMessage.sender_type,
            parsedMessage.sender_id,
            parsedMessage.message_text || '', // Use empty string if message_text is null
            parsedMessage.file_path || null,  // Handle file messages
            parsedMessage.file_name || null,  // Handle file messages
            parsedMessage.file_type || null   // Handle file messages
        ];

        db.query(sql, values, (err, result) => {
            if (err) {
                console.error('Failed to save message:', err);
                return;
            }
            console.log('Message saved to database:', result);

            // Broadcast the message to all clients in the same chat session
            wss.clients.forEach(function each(client) {
                if (
                    client.readyState === WebSocket.OPEN &&
                    chatClients.get(client)?.chat_id === parsedMessage.chat_id
                ) {
                    client.send(JSON.stringify(parsedMessage)); // Broadcast the message
                }
            });
        });
    });

    // Handle client disconnection
    ws.on('close', function () {
        console.log('A client disconnected');
        // Remove the client from the chatClients map
        chatClients.delete(ws);
    });

    
});
