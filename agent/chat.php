<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['agent_logged_in'])) {
    header('Location: login.php');
    exit();
}

$agent_id = $_SESSION['agent_id'];

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get agent info
    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE id = ?");
    $stmt->execute([$agent_id]);
    $agent = $stmt->fetch();
    
} catch (PDOException $e) {
    $agent = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Chat - ConnectPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Inter', sans-serif;
        }
        
        .chat-container {
            height: 80vh;
            max-height: 600px;
        }
        
        .chat-sidebar {
            border-right: 1px solid #dee2e6;
            background: white;
            height: 100%;
            overflow-y: auto;
        }
        
        .chat-main {
            background: white;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .room-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.2s;
            position: relative;
        }
        
        .room-item:hover {
            background-color: #f8f9fa;
        }
        
        .room-item.active {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
        }
        
        .client-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .unread-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: bold;
        }
        
        .chat-header {
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            max-height: 400px;
        }
        
        .message {
            margin-bottom: 15px;
            display: flex;
        }
        
        .message.sent {
            justify-content: flex-end;
        }
        
        .message-bubble {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 18px;
            position: relative;
        }
        
        .message.received .message-bubble {
            background-color: #e9ecef;
            color: #333;
        }
        
        .message.sent .message-bubble {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .message-time {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 5px;
        }
        
        .chat-input {
            padding: 20px;
            border-top: 1px solid #dee2e6;
            background: #f8f9fa;
        }
        
        .chat-input .input-group {
            background: white;
            border-radius: 25px;
            overflow: hidden;
        }
        
        .chat-input .form-control {
            border: none;
            padding: 12px 20px;
            background: white;
        }
        
        .chat-input .form-control:focus {
            box-shadow: none;
            border-color: transparent;
        }
        
        .send-btn {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            padding: 0 20px;
        }
        
        .send-btn:hover {
            background: linear-gradient(135deg, #218838 0%, #1ec085 100%);
        }
        
        .no-chat-selected {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #6c757d;
            text-align: center;
        }
        
        .agent-info {
            padding: 15px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            text-align: center;
        }
        
        .agent-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
            margin-bottom: 10px;
        }
        
        .agent-initials {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.2);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.2rem;
            margin: 0 auto 10px;
        }
        
        .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            background: #28a745;
            border-radius: 50%;
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-comments me-2"></i>Agent Chat
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a class="nav-link active" href="chat.php"><i class="fas fa-comments"></i> Chat</a>
                <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body p-0">
                        <div class="chat-container">
                            <div class="row h-100 g-0">
                                <!-- Chat Sidebar -->
                                <div class="col-md-4 chat-sidebar">
                                    <!-- Agent Info -->
                                    <div class="agent-info">
                                        <?php if ($agent && $agent['profile_image']): ?>
                                            <img src="<?php echo htmlspecialchars($agent['profile_image']); ?>" alt="Agent" class="agent-avatar">
                                        <?php else: ?>
                                            <div class="agent-initials">
                                                <?php echo $agent ? strtoupper(substr($agent['first_name'], 0, 1) . substr($agent['last_name'], 0, 1)) : 'AG'; ?>
                                            </div>
                                        <?php endif; ?>
                                        <h6 class="mb-1"><?php echo $agent ? htmlspecialchars($agent['first_name'] . ' ' . $agent['last_name']) : 'Agent'; ?></h6>
                                        <small>
                                            <span class="status-indicator"></span>Online
                                        </small>
                                    </div>
                                    
                                    <div class="p-3 border-bottom">
                                        <h6 class="mb-0">
                                            <i class="fas fa-users me-2"></i>Client Conversations
                                            <span class="badge bg-success ms-2" id="total-unread">0</span>
                                        </h6>
                                    </div>
                                    
                                    <!-- Active Chat Rooms -->
                                    <div id="chat-rooms">
                                        <div class="p-3 text-center text-muted">
                                            <i class="fas fa-spinner fa-spin"></i> Loading conversations...
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Chat Main Area -->
                                <div class="col-md-8 chat-main">
                                    <div id="chat-content">
                                        <div class="no-chat-selected">
                                            <div>
                                                <i class="fas fa-comments fa-3x mb-3"></i>
                                                <h5>Select a client to start chatting</h5>
                                                <p class="text-muted">Choose from your client conversations to provide support and assistance.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentRoomId = null;
        let messageInterval = null;
        let roomsInterval = null;
        
        // Initialize chat
        document.addEventListener('DOMContentLoaded', function() {
            loadChatRooms();
            startPeriodicUpdates();
        });
        
        // Load chat rooms
        function loadChatRooms() {
            fetch('../api/chat.php?action=get_rooms')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayChatRooms(data.rooms);
                        updateUnreadCount();
                    }
                })
                .catch(error => console.error('Error loading rooms:', error));
        }
        
        // Display chat rooms
        function displayChatRooms(rooms) {
            const container = document.getElementById('chat-rooms');
            
            if (rooms.length === 0) {
                container.innerHTML = '<div class="p-3 text-center text-muted">No client conversations yet</div>';
                return;
            }
            
            container.innerHTML = rooms.map(room => `
                <div class="room-item ${room.room_id == currentRoomId ? 'active' : ''}" 
                     onclick="selectRoom(${room.room_id}, '${room.client_name}', '${room.client_email}')">
                    <div class="d-flex align-items-center">
                        <div class="client-avatar me-3">
                            ${room.client_name ? room.client_name.substring(0, 2).toUpperCase() : 'CL'}
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-medium">${room.client_name || 'Client'}</div>
                            <div class="text-muted small">${room.client_email || ''}</div>
                            <div class="text-muted small">${room.last_message ? truncateText(room.last_message, 25) : 'No messages yet'}</div>
                        </div>
                        ${room.unread_count > 0 ? `<div class="unread-badge">${room.unread_count}</div>` : ''}
                    </div>
                </div>
            `).join('');
        }
        
        // Select chat room
        function selectRoom(roomId, clientName, clientEmail) {
            currentRoomId = roomId;
            
            // Update active room styling
            document.querySelectorAll('.room-item').forEach(item => {
                item.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
            
            // Load chat interface
            document.getElementById('chat-content').innerHTML = `
                <div class="chat-header">
                    <div class="d-flex align-items-center">
                        <div>
                            <h5 class="mb-0">${clientName}</h5>
                            <small>${clientEmail}</small>
                        </div>
                        <div class="ms-auto">
                            <button class="btn btn-light btn-sm" onclick="refreshMessages()">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="chat-messages" id="chat-messages">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin"></i> Loading messages...
                    </div>
                </div>
                <div class="chat-input">
                    <div class="input-group">
                        <input type="text" class="form-control" id="message-input" 
                               placeholder="Type your message..." onkeypress="handleEnterKey(event)">
                        <button class="btn send-btn" onclick="sendMessage()">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            `;
            
            // Load messages
            loadMessages(roomId);
            
            // Mark messages as read
            markAsRead(roomId);
        }
        
        // Load messages for a room
        function loadMessages(roomId) {
            fetch(`../api/chat.php?action=get_messages&room_id=${roomId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayMessages(data.messages);
                    }
                })
                .catch(error => console.error('Error loading messages:', error));
        }
        
        // Display messages
        function displayMessages(messages) {
            const container = document.getElementById('chat-messages');
            
            if (messages.length === 0) {
                container.innerHTML = '<div class="text-center text-muted">No messages yet. Start the conversation with your client!</div>';
                return;
            }
            
            container.innerHTML = messages.map(msg => `
                <div class="message ${msg.sender_type === 'admin' ? 'sent' : 'received'}">
                    <div class="message-bubble">
                        <div>${escapeHtml(msg.message)}</div>
                        <div class="message-time">${formatTime(msg.sent_at)}</div>
                    </div>
                </div>
            `).join('');
            
            // Scroll to bottom
            container.scrollTop = container.scrollHeight;
        }
        
        // Send message
        function sendMessage() {
            const input = document.getElementById('message-input');
            const message = input.value.trim();
            
            if (!message || !currentRoomId) return;
            
            const data = {
                action: 'send_message',
                room_id: currentRoomId,
                message: message
            };
            
            fetch('../api/chat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    input.value = '';
                    loadMessages(currentRoomId);
                    loadChatRooms(); // Refresh rooms to update last message
                }
            })
            .catch(error => console.error('Error sending message:', error));
        }
        
        // Mark messages as read
        function markAsRead(roomId) {
            const data = {
                action: 'mark_read',
                room_id: roomId
            };
            
            fetch('../api/chat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });
        }
        
        // Update unread count
        function updateUnreadCount() {
            fetch('../api/chat.php?action=get_unread_count')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('total-unread').textContent = data.unread_count;
                    }
                });
        }
        
        // Start periodic updates
        function startPeriodicUpdates() {
            // Update rooms every 5 seconds
            roomsInterval = setInterval(loadChatRooms, 5000);
            
            // Update messages every 2 seconds if a room is selected
            messageInterval = setInterval(() => {
                if (currentRoomId) {
                    const oldScrollHeight = document.getElementById('chat-messages').scrollHeight;
                    loadMessages(currentRoomId);
                    
                    // Maintain scroll position if not at bottom
                    setTimeout(() => {
                        const messagesContainer = document.getElementById('chat-messages');
                        if (messagesContainer.scrollTop + messagesContainer.clientHeight >= oldScrollHeight - 50) {
                            messagesContainer.scrollTop = messagesContainer.scrollHeight;
                        }
                    }, 100);
                }
            }, 2000);
        }
        
        // Handle enter key
        function handleEnterKey(event) {
            if (event.key === 'Enter') {
                sendMessage();
            }
        }
        
        // Refresh messages
        function refreshMessages() {
            if (currentRoomId) {
                loadMessages(currentRoomId);
            }
        }
        
        // Utility functions
        function truncateText(text, length) {
            return text.length > length ? text.substring(0, length) + '...' : text;
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function formatTime(timestamp) {
            const date = new Date(timestamp);
            const now = new Date();
            const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            const messageDate = new Date(date.getFullYear(), date.getMonth(), date.getDate());
            
            if (messageDate.getTime() === today.getTime()) {
                return date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            } else {
                return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            }
        }
        
        // Cleanup intervals when leaving page
        window.addEventListener('beforeunload', function() {
            if (messageInterval) clearInterval(messageInterval);
            if (roomsInterval) clearInterval(roomsInterval);
        });
    </script>
</body>
</html>
