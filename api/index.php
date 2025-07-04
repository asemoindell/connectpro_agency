<?php
// API endpoints handler
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Get the request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['REQUEST_URI'];
$path_parts = explode('/', trim(parse_url($path, PHP_URL_PATH), '/'));

// Find the api index and get the endpoint parts
$api_index = array_search('api', $path_parts);
if ($api_index !== false) {
    $endpoint_parts = array_slice($path_parts, $api_index + 1);
    // Remove index.php if it exists
    if (!empty($endpoint_parts) && $endpoint_parts[0] === 'index.php') {
        $endpoint_parts = array_slice($endpoint_parts, 1);
    }
} else {
    $endpoint_parts = [];
}

$endpoint = $endpoint_parts[0] ?? '';
$id = $endpoint_parts[1] ?? null;

try {
    switch($endpoint) {
        case 'auth':
            handleAuth($db, $method);
            break;
        case 'user-auth':
            handleUserAuth($db, $method);
            break;
        case 'services':
            handleServices($db, $method, $id);
            break;
        case 'content':
            handleContent($db, $method, $id);
            break;
        case 'inquiries':
            handleInquiries($db, $method, $id);
            break;
        case 'settings':
            handleSettings($db, $method, $id);
            break;
        case 'dashboard':
            handleDashboard($db, $method);
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

// Authentication endpoints
function handleAuth($db, $method) {
    if ($method === 'POST') {
        $action = $_GET['action'] ?? '';
        
        if ($action === 'login') {
            $input = json_decode(file_get_contents('php://input'), true);
            $email = $input['email'] ?? '';
            $password = $input['password'] ?? '';
            
            $stmt = $db->prepare("SELECT * FROM admin_users WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Update last login
                $update_stmt = $db->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
                $update_stmt->execute([$user['id']]);
                
                // Set session variables
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['admin_email'] = $user['email'];
                $_SESSION['admin_role'] = $user['role'];
                
                // Remove password from response
                unset($user['password']);
                
                echo json_encode([
                    'success' => true,
                    'user' => $user,
                    'token' => session_id()
                ]);
            } else {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid credentials']);
            }
        } elseif ($action === 'register') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $firstName = $input['firstName'] ?? '';
            $lastName = $input['lastName'] ?? '';
            $email = $input['email'] ?? '';
            $password = $input['password'] ?? '';
            $role = $input['role'] ?? 'content-admin';
            
            // Check if email already exists
            $check_stmt = $db->prepare("SELECT id FROM admin_users WHERE email = ?");
            $check_stmt->execute([$email]);
            
            if ($check_stmt->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'Email already exists']);
                return;
            }
            
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("INSERT INTO admin_users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$firstName, $lastName, $email, $hashedPassword, $role]);
            
            echo json_encode(['success' => true, 'message' => 'Admin user created successfully']);
        } elseif ($action === 'logout') {
            session_destroy();
            echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
        }
    }
}

// User authentication endpoints
function handleUserAuth($db, $method) {
    if ($method === 'POST') {
        $action = $_GET['action'] ?? '';
        
        if ($action === 'login') {
            $input = json_decode(file_get_contents('php://input'), true);
            $email = $input['email'] ?? '';
            $password = $input['password'] ?? '';
            
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Update last login
                $update_stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $update_stmt->execute([$user['id']]);
                
                // Set session variables
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['user_email'] = $user['email'];
                
                // Remove password from response
                unset($user['password']);
                
                echo json_encode([
                    'success' => true,
                    'user' => $user,
                    'token' => session_id()
                ]);
            } else {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid credentials']);
            }
        } elseif ($action === 'register') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $firstName = $input['firstName'] ?? '';
            $lastName = $input['lastName'] ?? '';
            $email = $input['email'] ?? '';
            $phone = $input['phone'] ?? '';
            $password = $input['password'] ?? '';
            $newsletter = $input['newsletter'] ?? false;
            
            // Check if email already exists
            $check_stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $check_stmt->execute([$email]);
            
            if ($check_stmt->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'Email already exists']);
                return;
            }
            
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("INSERT INTO users (first_name, last_name, email, phone, password, newsletter_subscribed) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$firstName, $lastName, $email, $phone, $hashedPassword, $newsletter ? 1 : 0]);
            
            echo json_encode(['success' => true, 'message' => 'User account created successfully']);
        } elseif ($action === 'logout') {
            session_destroy();
            echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
        }
    }
}

// Services endpoints
function handleServices($db, $method, $id) {
    if ($method === 'GET') {
        if ($id) {
            $stmt = $db->prepare("SELECT * FROM services WHERE id = ?");
            $stmt->execute([$id]);
            $service = $stmt->fetch();
            
            if ($service) {
                $service['features'] = json_decode($service['features'], true);
                echo json_encode($service);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Service not found']);
            }
        } else {
            $stmt = $db->query("SELECT * FROM services ORDER BY created_at DESC");
            $services = $stmt->fetchAll();
            
            foreach ($services as &$service) {
                $service['features'] = json_decode($service['features'], true);
            }
            
            echo json_encode($services);
        }
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $db->prepare("INSERT INTO services (title, slug, description, short_description, price_range, category, features, is_featured, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $input['title'],
            $input['slug'],
            $input['description'],
            $input['short_description'],
            $input['price_range'],
            $input['category'],
            json_encode($input['features']),
            $input['is_featured'] ?? false,
            $input['status'] ?? 'active'
        ]);
        
        echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
    } elseif ($method === 'PUT' && $id) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $db->prepare("UPDATE services SET title = ?, slug = ?, description = ?, short_description = ?, price_range = ?, category = ?, features = ?, is_featured = ?, status = ? WHERE id = ?");
        $stmt->execute([
            $input['title'],
            $input['slug'],
            $input['description'],
            $input['short_description'],
            $input['price_range'],
            $input['category'],
            json_encode($input['features']),
            $input['is_featured'] ?? false,
            $input['status'] ?? 'active',
            $id
        ]);
        
        echo json_encode(['success' => true]);
    } elseif ($method === 'DELETE' && $id) {
        $stmt = $db->prepare("DELETE FROM services WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true]);
    }
}

// Content endpoints
function handleContent($db, $method, $id) {
    if ($method === 'GET') {
        $page = $_GET['page'] ?? '';
        $section = $_GET['section'] ?? '';
        
        $query = "SELECT * FROM content_pages WHERE 1=1";
        $params = [];
        
        if ($page) {
            $query .= " AND page_name = ?";
            $params[] = $page;
        }
        
        if ($section) {
            $query .= " AND section_name = ?";
            $params[] = $section;
        }
        
        $query .= " ORDER BY page_name, section_name, content_key";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $content = $stmt->fetchAll();
        
        echo json_encode($content);
    } elseif ($method === 'PUT' && $id) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $db->prepare("UPDATE content_pages SET content_value = ? WHERE id = ?");
        $stmt->execute([$input['content_value'], $id]);
        
        echo json_encode(['success' => true]);
    }
}

// Inquiries endpoints
function handleInquiries($db, $method, $id) {
    if ($method === 'GET') {
        $stmt = $db->query("SELECT * FROM contact_inquiries ORDER BY created_at DESC");
        $inquiries = $stmt->fetchAll();
        echo json_encode($inquiries);
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $db->prepare("INSERT INTO contact_inquiries (name, email, phone, service, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $input['name'],
            $input['email'],
            $input['phone'] ?? '',
            $input['service'] ?? '',
            $input['message']
        ]);
        
        echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
    } elseif ($method === 'PUT' && $id) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $db->prepare("UPDATE contact_inquiries SET status = ?, admin_notes = ? WHERE id = ?");
        $stmt->execute([$input['status'], $input['admin_notes'] ?? '', $id]);
        
        echo json_encode(['success' => true]);
    }
}

// Settings endpoints
function handleSettings($db, $method, $id) {
    if ($method === 'GET') {
        $stmt = $db->query("SELECT * FROM site_settings ORDER BY setting_key");
        $settings = $stmt->fetchAll();
        echo json_encode($settings);
    } elseif ($method === 'PUT' && $id) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $db->prepare("UPDATE site_settings SET setting_value = ? WHERE id = ?");
        $stmt->execute([$input['setting_value'], $id]);
        
        echo json_encode(['success' => true]);
    }
}

// Dashboard stats
function handleDashboard($db, $method) {
    if ($method === 'GET') {
        // Get dashboard statistics
        $stats = [];
        
        // Total services
        $stmt = $db->query("SELECT COUNT(*) as count FROM services WHERE status = 'active'");
        $stats['services'] = $stmt->fetch()['count'];
        
        // Total inquiries
        $stmt = $db->query("SELECT COUNT(*) as count FROM contact_inquiries");
        $stats['inquiries'] = $stmt->fetch()['count'];
        
        // New inquiries this month
        $stmt = $db->query("SELECT COUNT(*) as count FROM contact_inquiries WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
        $stats['new_inquiries'] = $stmt->fetch()['count'];
        
        // Active admins
        $stmt = $db->query("SELECT COUNT(*) as count FROM admin_users WHERE status = 'active'");
        $stats['admins'] = $stmt->fetch()['count'];
        
        echo json_encode($stats);
    }
}
?>
