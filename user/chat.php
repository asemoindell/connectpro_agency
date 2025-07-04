<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get user's bookings with agents and payment status
    $stmt = $pdo->prepare("
        SELECT sb.*, 
               s.title as service_title,
               au.id as agent_id,
               CONCAT(au.first_name, ' ', au.last_name) as agent_name,
               au.profile_image as agent_image,
               cp.status as payment_status,
               cp.id as payment_id,
               CASE 
                   WHEN cp.status = 'confirmed' THEN 1
                   ELSE 0
               END as can_chat
        FROM service_bookings sb
        JOIN services s ON sb.service_id = s.id
        LEFT JOIN admin_users au ON sb.assigned_admin_id = au.id
        LEFT JOIN crypto_payments cp ON sb.id = cp.booking_id
        WHERE sb.user_id = ? AND sb.status IN ('approved', 'in_progress', 'completed')
        ORDER BY sb.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $bookings = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $bookings = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - ConnectPro</title>
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
        
        .room-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .room-initials {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 0 20px;
        }
        
        .send-btn:hover {
            background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
        }
        
        .no-chat-selected {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #6c757d;
            text-align: center;
        }
        
        .booking-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .booking-item:hover {
            background-color: #f8f9fa;
        }
        
        .typing-indicator {
            display: none;
            padding: 10px 0;
            font-style: italic;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-comments me-2"></i>ConnectPro Chat
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a class="nav-link" href="profile.php"><i class="fas fa-user"></i> Profile</a>
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
                                    <div class="p-3 border-bottom">
                                        <h5 class="mb-0">
                                            <i class="fas fa-comments me-2"></i>Conversations
                                            <span class="badge bg-primary ms-2" id="total-unread">0</span>
                                        </h5>
                                    </div>
                                    
                                    <!-- Active Chat Rooms -->
                                    <div id="chat-rooms">
                                        <div class="p-3 text-center text-muted">
                                            <i class="fas fa-spinner fa-spin"></i> Loading conversations...
                                        </div>
                                    </div>
                                    
                                    <!-- Available Bookings to Start Chat -->
                                    <?php if (!empty($bookings)): ?>
                                    <div class="p-3 border-top">
                                        <h6 class="text-muted mb-3">Start New Conversation</h6>
                                        <?php foreach ($bookings as $booking): ?>
                                            <?php if ($booking['agent_id']): ?>
                                            <div class="booking-item" onclick="createChatRoom(<?php echo $booking['id']; ?>, <?php echo $booking['agent_id']; ?>)">
                                                <div class="d-flex align-items-center">
                                                    <?php if ($booking['agent_image']): ?>
                                                        <img src="<?php echo htmlspecialchars($booking['agent_image']); ?>" alt="Agent" class="room-avatar me-3">
                                                    <?php else: ?>
                                                        <div class="room-initials me-3">
                                                            <?php echo strtoupper(substr($booking['agent_name'], 0, 2)); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="flex-grow-1">
                                                        <div class="fw-medium"><?php echo htmlspecialchars($booking['agent_name']); ?></div>
                                                        <div class="text-muted small"><?php echo htmlspecialchars($booking['service_title']); ?></div>
                                                    </div>
                                                    <i class="fas fa-plus text-primary"></i>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Chat Main Area -->
                                <div class="col-md-8 chat-main">
                                    <div id="chat-content">
                                        <div class="no-chat-selected">
                                            <div>
                                                <i class="fas fa-comments fa-3x mb-3"></i>
                                                <h5>Select a conversation to start chatting</h5>
                                                <p class="text-muted">Choose from your existing conversations or start a new one with your agent.</p>
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
                container.innerHTML = '<div class="p-3 text-center text-muted">No conversations yet</div>';
                return;
            }
            
            container.innerHTML = rooms.map(room => `
                <div class="room-item ${room.room_id == currentRoomId ? 'active' : ''}" 
                     onclick="selectRoom(${room.room_id}, '${room.agent_name || room.client_name}')">
                    <div class="d-flex align-items-center">
                        ${room.agent_image ? 
                            `<img src="${room.agent_image}" alt="Agent" class="room-avatar me-3">` :
                            `<div class="room-initials me-3">${(room.agent_name || room.client_name).substring(0, 2).toUpperCase()}</div>`
                        }
                        <div class="flex-grow-1">
                            <div class="fw-medium">${room.agent_name || room.client_name}</div>
                            <div class="text-muted small">${room.last_message ? truncateText(room.last_message, 30) : 'No messages yet'}</div>
                        </div>
                        ${room.unread_count > 0 ? `<div class="unread-badge">${room.unread_count}</div>` : ''}
                    </div>
                </div>
            `).join('');
        }
        
        // Select chat room
        function selectRoom(roomId, contactName) {
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
                        <h5 class="mb-0">${contactName}</h5>
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
                container.innerHTML = '<div class="text-center text-muted">No messages yet. Start the conversation!</div>';
                return;
            }
            
            container.innerHTML = messages.map(msg => `
                <div class="message ${msg.sender_type === 'user' ? 'sent' : 'received'}">
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
        
        // Create new chat room
        function createChatRoom(bookingId, agentId) {
            const data = {
                action: 'create_room',
                booking_id: bookingId,
                agent_id: agentId
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
                    loadChatRooms();
                    if (!data.existing) {
                        // If it's a new room, select it
                        setTimeout(() => {
                            const roomElement = document.querySelector(`[onclick*="${data.room_id}"]`);
                            if (roomElement) roomElement.click();
                        }, 500);
                    }
                }
            })
            .catch(error => console.error('Error creating room:', error));
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
            // Update rooms every 10 seconds
            roomsInterval = setInterval(loadChatRooms, 10000);
            
            // Update messages every 3 seconds if a room is selected
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
            }, 3000);
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
