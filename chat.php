<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: user/login.php');
    exit();
}

// Get chat room ID from URL
$chat_room_id = isset($_GET['room']) ? (int)$_GET['room'] : 0;

if (!$chat_room_id) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Verify user has access to this chat room
    $stmt = $pdo->prepare("
        SELECT cr.*, b.service_name, b.user_id as booking_user_id, b.booking_id
        FROM chat_rooms cr 
        JOIN bookings b ON cr.booking_id = b.booking_id 
        WHERE cr.room_id = ? AND (b.user_id = ? OR cr.agent_id = ?)
    ");
    $stmt->execute([$chat_room_id, $user_id, $user_id]);
    $chat_room = $stmt->fetch();
    
    if (!$chat_room) {
        echo '<script>alert("Access denied to this chat room."); window.location.href = "index.php";</script>';
        exit();
    }
    
    // Determine if current user is the agent or client
    $is_agent = ($chat_room['agent_id'] == $user_id);
    $other_party = $is_agent ? 'Client' : 'Agent';
    
    // Handle message submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
        $message = trim($_POST['message']);
        if (!empty($message)) {
            $stmt = $pdo->prepare("
                INSERT INTO chat_messages (room_id, sender_id, message, sent_at) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$chat_room_id, $user_id, $message]);
            
            // Redirect to prevent resubmission
            header("Location: chat.php?room=" . $chat_room_id);
            exit();
        }
    }
    
    // Get chat messages
    $stmt = $pdo->prepare("
        SELECT cm.*, 
               CASE 
                   WHEN cm.sender_type = 'user' THEN CONCAT(u.first_name, ' ', u.last_name)
                   WHEN cm.sender_type = 'admin' THEN CONCAT(a.first_name, ' ', a.last_name)
                   ELSE 'Unknown'
               END as sender_name
        FROM chat_messages cm 
        LEFT JOIN users u ON cm.sender_id = u.id AND cm.sender_type = 'user'
        LEFT JOIN admins a ON cm.sender_id = a.admin_id AND cm.sender_type = 'admin'
        WHERE cm.room_id = ? 
        ORDER BY cm.sent_at ASC
    ");
    $stmt->execute([$chat_room_id]);
    $messages = $stmt->fetchAll();
    
    // Update last read timestamp
    $stmt = $pdo->prepare("
        UPDATE chat_rooms 
        SET " . ($is_agent ? 'agent_last_read' : 'client_last_read') . " = NOW() 
        WHERE room_id = ?
    ");
    $stmt->execute([$chat_room_id]);
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - ConnectPro Agency</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .chat-container {
            height: 70vh;
            border: 1px solid #ddd;
            border-radius: 10px;
            display: flex;
            flex-direction: column;
        }
        
        .chat-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 10px 10px 0 0;
        }
        
        .chat-messages {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            background-color: #f8f9fa;
        }
        
        .message {
            margin-bottom: 15px;
            max-width: 70%;
        }
        
        .message.own {
            margin-left: auto;
        }
        
        .message.other {
            margin-right: auto;
        }
        
        .message-bubble {
            padding: 10px 15px;
            border-radius: 18px;
            position: relative;
        }
        
        .message.own .message-bubble {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .message.other .message-bubble {
            background: white;
            border: 1px solid #e9ecef;
            color: #333;
        }
        
        .message-info {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 5px;
        }
        
        .message.own .message-info {
            text-align: right;
        }
        
        .chat-input {
            padding: 15px;
            border-top: 1px solid #ddd;
            background: white;
            border-radius: 0 0 10px 10px;
        }
        
        .typing-indicator {
            display: none;
            padding: 10px;
            font-style: italic;
            color: #666;
        }
        
        .online-status {
            width: 10px;
            height: 10px;
            background: #28a745;
            border-radius: 50%;
            display: inline-block;
            margin-left: 10px;
        }
        
        .chat-info {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-handshake"></i> ConnectPro Agency
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-home"></i> Home
                </a>
                <a class="nav-link" href="user/logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <!-- Chat Info -->
                <div class="chat-info">
                    <h5>
                        <i class="fas fa-comments"></i> 
                        Chat for Service: <?php echo htmlspecialchars($chat_room['service_name']); ?>
                    </h5>
                    <p class="mb-2">
                        <strong>Booking ID:</strong> #<?php echo $chat_room['booking_id']; ?>
                        <span class="badge bg-success ms-2">
                            <i class="fas fa-circle"></i> Active Chat
                        </span>
                    </p>
                    <p class="mb-0 text-muted">
                        You are chatting as: <strong><?php echo $is_agent ? 'Agent' : 'Client'; ?></strong>
                        <span class="online-status" title="Online"></span>
                    </p>
                </div>

                <!-- Chat Container -->
                <div class="chat-container">
                    <!-- Chat Header -->
                    <div class="chat-header">
                        <h6 class="mb-0">
                            <i class="fas fa-user"></i> 
                            Chatting with <?php echo $other_party; ?>
                            <span class="online-status" title="Online"></span>
                        </h6>
                    </div>

                    <!-- Chat Messages -->
                    <div class="chat-messages" id="chatMessages">
                        <?php if (empty($messages)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-comments fa-3x mb-3"></i>
                                <p>No messages yet. Start the conversation!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($messages as $message): ?>
                                <?php $is_own = ($message['sender_id'] == $user_id); ?>
                                <div class="message <?php echo $is_own ? 'own' : 'other'; ?>">
                                    <div class="message-bubble">
                                        <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                    </div>
                                    <div class="message-info">
                                        <?php if (!$is_own): ?>
                                            <strong><?php echo htmlspecialchars($message['sender_name']); ?></strong> • 
                                        <?php endif; ?>
                                        <?php echo date('M j, Y g:i A', strtotime($message['sent_at'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Typing Indicator -->
                    <div class="typing-indicator" id="typingIndicator">
                        <i class="fas fa-ellipsis-h"></i> <?php echo $other_party; ?> is typing...
                    </div>

                    <!-- Chat Input -->
                    <div class="chat-input">
                        <form method="POST" id="chatForm">
                            <div class="input-group">
                                <input type="text" 
                                       class="form-control" 
                                       name="message" 
                                       id="messageInput"
                                       placeholder="Type your message..." 
                                       required
                                       autocomplete="off">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-paper-plane"></i> Send
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Chat Actions -->
                <div class="mt-3 text-center">
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i> 
                        This chat session is secure and monitored for quality assurance.
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-scroll to bottom of messages
        function scrollToBottom() {
            const chatMessages = document.getElementById('chatMessages');
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // Auto-refresh messages every 5 seconds
        function refreshMessages() {
            const currentScroll = document.getElementById('chatMessages').scrollTop;
            const maxScroll = document.getElementById('chatMessages').scrollHeight - document.getElementById('chatMessages').clientHeight;
            const isAtBottom = currentScroll >= maxScroll - 10;

            fetch('get-messages.php?room=<?php echo $chat_room_id; ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('chatMessages').innerHTML = data.messages;
                        if (isAtBottom) {
                            scrollToBottom();
                        }
                    }
                })
                .catch(error => console.error('Error refreshing messages:', error));
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            scrollToBottom();
            
            // Auto-refresh every 5 seconds
            setInterval(refreshMessages, 5000);

            // Focus on input
            document.getElementById('messageInput').focus();

            // Handle form submission
            document.getElementById('chatForm').addEventListener('submit', function(e) {
                const input = document.getElementById('messageInput');
                if (input.value.trim() === '') {
                    e.preventDefault();
                    return false;
                }
            });

            // Auto-resize input and handle enter key
            document.getElementById('messageInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    document.getElementById('chatForm').submit();
                }
            });
        });
    </script>
</body>
</html>
