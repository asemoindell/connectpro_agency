<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in (either user or agent)
$user_id = null;
$user_type = null;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $user_type = 'user';
} elseif (isset($_SESSION['agent_id'])) {
    $user_id = $_SESSION['agent_id'];
    $user_type = 'admin'; // agents are stored as admin_users
} else {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            if (isset($_GET['action'])) {
                switch ($_GET['action']) {
                    case 'get_rooms':
                        getRooms($pdo, $user_id, $user_type);
                        break;
                    case 'get_messages':
                        $room_id = $_GET['room_id'] ?? null;
                        if ($room_id) {
                            getMessages($pdo, $room_id, $user_id, $user_type);
                        } else {
                            echo json_encode(['error' => 'Room ID required']);
                        }
                        break;
                    case 'get_unread_count':
                        getUnreadCount($pdo, $user_id, $user_type);
                        break;
                    default:
                        echo json_encode(['error' => 'Invalid action']);
                }
            } else {
                echo json_encode(['error' => 'Action required']);
            }
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (isset($input['action'])) {
                switch ($input['action']) {
                    case 'send_message':
                        sendMessage($pdo, $input, $user_id, $user_type);
                        break;
                    case 'create_room':
                        createRoom($pdo, $input, $user_id, $user_type);
                        break;
                    case 'mark_read':
                        markMessagesAsRead($pdo, $input['room_id'], $user_id, $user_type);
                        break;
                    default:
                        echo json_encode(['error' => 'Invalid action']);
                }
            } else {
                echo json_encode(['error' => 'Action required']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

function getRooms($pdo, $user_id, $user_type) {
    if ($user_type === 'user') {
        $sql = "SELECT cr.*, 
                       CONCAT(au.first_name, ' ', au.last_name) as agent_name,
                       au.profile_image as agent_image,
                       (SELECT COUNT(*) FROM chat_messages cm 
                        WHERE cm.room_id = cr.room_id 
                        AND cm.sender_type = 'admin' 
                        AND cm.read_at IS NULL) as unread_count,
                       (SELECT cm.message FROM chat_messages cm 
                        WHERE cm.room_id = cr.room_id 
                        ORDER BY cm.sent_at DESC LIMIT 1) as last_message
                FROM chat_rooms cr
                LEFT JOIN admin_users au ON cr.admin_id = au.id
                WHERE cr.user_id = ? AND cr.status = 'active'
                ORDER BY cr.last_message_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
    } else {
        $sql = "SELECT cr.*, 
                       CONCAT(u.first_name, ' ', u.last_name) as client_name,
                       u.email as client_email,
                       (SELECT COUNT(*) FROM chat_messages cm 
                        WHERE cm.room_id = cr.room_id 
                        AND cm.sender_type = 'user' 
                        AND cm.read_at IS NULL) as unread_count,
                       (SELECT cm.message FROM chat_messages cm 
                        WHERE cm.room_id = cr.room_id 
                        ORDER BY cm.sent_at DESC LIMIT 1) as last_message
                FROM chat_rooms cr
                LEFT JOIN users u ON cr.user_id = u.id
                WHERE cr.admin_id = ? AND cr.status = 'active'
                ORDER BY cr.last_message_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
    }
    
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'rooms' => $rooms]);
}

function getMessages($pdo, $room_id, $user_id, $user_type) {
    // Verify user has access to this room
    $check_sql = "SELECT * FROM chat_rooms WHERE room_id = ? AND " . 
                ($user_type === 'user' ? 'user_id = ?' : 'admin_id = ?');
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$room_id, $user_id]);
    
    if (!$check_stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        return;
    }
    
    $sql = "SELECT cm.*, 
                   CASE 
                       WHEN cm.sender_type = 'user' THEN CONCAT(u.first_name, ' ', u.last_name)
                       ELSE CONCAT(au.first_name, ' ', au.last_name)
                   END as sender_name,
                   CASE 
                       WHEN cm.sender_type = 'admin' THEN au.profile_image
                       ELSE NULL
                   END as sender_image
            FROM chat_messages cm
            LEFT JOIN users u ON cm.sender_type = 'user' AND cm.sender_id = u.id
            LEFT JOIN admin_users au ON cm.sender_type = 'admin' AND cm.sender_id = au.id
            WHERE cm.room_id = ?
            ORDER BY cm.sent_at ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$room_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'messages' => $messages]);
}

function sendMessage($pdo, $input, $user_id, $user_type) {
    $room_id = $input['room_id'] ?? null;
    $message = trim($input['message'] ?? '');
    
    if (!$room_id || !$message) {
        echo json_encode(['error' => 'Room ID and message are required']);
        return;
    }
    
    // Verify user has access to this room
    $check_sql = "SELECT * FROM chat_rooms WHERE room_id = ? AND " . 
                ($user_type === 'user' ? 'user_id = ?' : 'admin_id = ?');
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$room_id, $user_id]);
    
    if (!$check_stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        return;
    }
    
    // Insert message
    $sql = "INSERT INTO chat_messages (room_id, sender_type, sender_id, message, sent_at) 
            VALUES (?, ?, ?, ?, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$room_id, $user_type, $user_id, $message]);
    
    // Update room's last message time
    $update_sql = "UPDATE chat_rooms SET last_message_at = NOW() WHERE room_id = ?";
    $update_stmt = $pdo->prepare($update_sql);
    $update_stmt->execute([$room_id]);
    
    echo json_encode(['success' => true, 'message_id' => $pdo->lastInsertId()]);
}

function createRoom($pdo, $input, $user_id, $user_type) {
    if ($user_type !== 'user') {
        echo json_encode(['error' => 'Only users can create chat rooms']);
        return;
    }
    
    $booking_id = $input['booking_id'] ?? null;
    $agent_id = $input['agent_id'] ?? null;
    
    if (!$booking_id || !$agent_id) {
        echo json_encode(['error' => 'Booking ID and Agent ID are required']);
        return;
    }
    
    // Check if room already exists for this booking
    $check_sql = "SELECT room_id FROM chat_rooms WHERE booking_id = ? AND user_id = ? AND admin_id = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$booking_id, $user_id, $agent_id]);
    $existing_room = $check_stmt->fetch();
    
    if ($existing_room) {
        echo json_encode(['success' => true, 'room_id' => $existing_room['room_id'], 'existing' => true]);
        return;
    }
    
    // Create new room
    $sql = "INSERT INTO chat_rooms (booking_id, user_id, admin_id, created_at, last_message_at) 
            VALUES (?, ?, ?, NOW(), NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$booking_id, $user_id, $agent_id]);
    
    $room_id = $pdo->lastInsertId();
    echo json_encode(['success' => true, 'room_id' => $room_id, 'existing' => false]);
}

function markMessagesAsRead($pdo, $room_id, $user_id, $user_type) {
    // Verify user has access to this room
    $check_sql = "SELECT * FROM chat_rooms WHERE room_id = ? AND " . 
                ($user_type === 'user' ? 'user_id = ?' : 'admin_id = ?');
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$room_id, $user_id]);
    
    if (!$check_stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        return;
    }
    
    // Mark messages as read (messages from the other party)
    $other_sender_type = $user_type === 'user' ? 'admin' : 'user';
    $sql = "UPDATE chat_messages SET read_at = NOW() 
            WHERE room_id = ? AND sender_type = ? AND read_at IS NULL";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$room_id, $other_sender_type]);
    
    echo json_encode(['success' => true]);
}

function getUnreadCount($pdo, $user_id, $user_type) {
    $other_sender_type = $user_type === 'user' ? 'admin' : 'user';
    
    $sql = "SELECT COUNT(*) as unread_count
            FROM chat_messages cm
            JOIN chat_rooms cr ON cm.room_id = cr.room_id
            WHERE cm.sender_type = ? AND cm.read_at IS NULL 
            AND " . ($user_type === 'user' ? 'cr.user_id = ?' : 'cr.admin_id = ?');
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$other_sender_type, $user_id]);
    $result = $stmt->fetch();
    
    echo json_encode(['success' => true, 'unread_count' => (int)$result['unread_count']]);
}
?>
