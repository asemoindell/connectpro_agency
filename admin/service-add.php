<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $slug = trim($_POST['slug']);
    $description = trim($_POST['description']);
    $short_description = trim($_POST['short_description']);
    $price_range = trim($_POST['price_range']);
    $category = trim($_POST['category']);
    $features = trim($_POST['features']);
    $image_url = trim($_POST['image_url']);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $status = $_POST['status'];
    
    // Validate required fields
    if (empty($title) || empty($slug) || empty($description) || empty($category)) {
        $error_message = "Please fill in all required fields.";
    } else {
        try {
            // Check if slug already exists
            $check_stmt = $db->prepare("SELECT id FROM services WHERE slug = ?");
            $check_stmt->execute([$slug]);
            
            if ($check_stmt->fetch()) {
                $error_message = "Slug already exists. Please choose a different slug.";
            } else {
                // Create new service
                $stmt = $db->prepare("INSERT INTO services 
                    (title, slug, description, short_description, price_range, category, 
                     features, image_url, is_featured, status, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                
                $stmt->execute([
                    $title, $slug, $description, $short_description,
                    $price_range, $category, $features, $image_url,
                    $is_featured, $status
                ]);
                
                $success_message = "Service created successfully!";
                
                // Clear form data after successful creation
                $title = $slug = $description = $short_description = '';
                $price_range = $category = $features = $image_url = '';
                $is_featured = 0;
                $status = 'active';
            }
        } catch (PDOException $e) {
            $error_message = "Error creating service: " . $e->getMessage();
        }
    }
}

// Get categories for the dropdown
$categories_stmt = $db->query("SELECT DISTINCT category FROM services WHERE category IS NOT NULL ORDER BY category");
$categories = $categories_stmt->fetchAll(PDO::FETCH_COLUMN);

// Set default values if not from form submission
if (!isset($title)) {
    $title = $slug = $description = $short_description = '';
    $price_range = $category = $features = $image_url = '';
    $is_featured = 0;
    $status = 'active';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Service - ConnectPro Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Inter', sans-serif;
        }
        .navbar-brand {
            font-weight: 600;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .form-label {
            font-weight: 500;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
        }
        .preview-image {
            max-width: 200px;
            max-height: 150px;
            border-radius: 8px;
            margin-top: 10px;
        }
        .required {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-shield-alt me-2"></i>ConnectPro Admin
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a class="nav-link" href="users.php"><i class="fas fa-users"></i> Users</a>
                <a class="nav-link" href="agents.php"><i class="fas fa-user-tie"></i> Agents</a>
                <a class="nav-link active" href="services.php"><i class="fas fa-cogs"></i> Services</a>
                <a class="nav-link" href="bookings.php"><i class="fas fa-calendar-check"></i> Bookings</a>
                <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 mb-0">Add New Service</h1>
                        <p class="text-muted mb-0">Create a new service for your platform</p>
                    </div>
                    <a href="services.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Services
                    </a>
                </div>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label for="title" class="form-label">Service Title <span class="required">*</span></label>
                                        <input type="text" class="form-control" id="title" name="title" 
                                               value="<?php echo htmlspecialchars($title); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="slug" class="form-label">Slug <span class="required">*</span></label>
                                        <input type="text" class="form-control" id="slug" name="slug" 
                                               value="<?php echo htmlspecialchars($slug); ?>" required>
                                        <div class="form-text">Used in URL. Should be unique and URL-friendly.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="short_description" class="form-label">Short Description</label>
                                        <textarea class="form-control" id="short_description" name="short_description" 
                                                  rows="3"><?php echo htmlspecialchars($short_description); ?></textarea>
                                        <div class="form-text">Brief summary shown in service listings.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Full Description <span class="required">*</span></label>
                                        <textarea class="form-control" id="description" name="description" 
                                                  rows="8" required><?php echo htmlspecialchars($description); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="features" class="form-label">Features</label>
                                        <textarea class="form-control" id="features" name="features" 
                                                  rows="5"><?php echo htmlspecialchars($features); ?></textarea>
                                        <div class="form-text">List features separated by new lines or use HTML formatting.</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Status <span class="required">*</span></label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="category" class="form-label">Category <span class="required">*</span></label>
                                        <input type="text" class="form-control" id="category" name="category" 
                                               value="<?php echo htmlspecialchars($category); ?>" 
                                               list="categories" required>
                                        <datalist id="categories">
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?php echo htmlspecialchars($cat); ?>">
                                            <?php endforeach; ?>
                                        </datalist>
                                        <div class="form-text">Type a new category or select from existing ones.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="price_range" class="form-label">Price Range</label>
                                        <input type="text" class="form-control" id="price_range" name="price_range" 
                                               value="<?php echo htmlspecialchars($price_range); ?>"
                                               placeholder="e.g., $50 - $200">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="image_url" class="form-label">Image URL</label>
                                        <input type="url" class="form-control" id="image_url" name="image_url" 
                                               value="<?php echo htmlspecialchars($image_url); ?>"
                                               onchange="previewImage(this.value)">
                                        <div id="image-preview" class="mt-2"></div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" 
                                                   <?php echo $is_featured ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="is_featured">
                                                Featured Service
                                            </label>
                                        </div>
                                        <div class="form-text">Featured services appear prominently on the website.</div>
                                    </div>
                                    
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title">Tips</h6>
                                            <ul class="list-unstyled small mb-0">
                                                <li><i class="fas fa-lightbulb text-warning"></i> Use clear, descriptive titles</li>
                                                <li><i class="fas fa-lightbulb text-warning"></i> Keep slugs short and SEO-friendly</li>
                                                <li><i class="fas fa-lightbulb text-warning"></i> Include detailed descriptions</li>
                                                <li><i class="fas fa-lightbulb text-warning"></i> Use high-quality images</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-4">
                                <div class="col-12">
                                    <hr>
                                    <div class="d-flex justify-content-between">
                                        <a href="services.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-plus"></i> Create Service
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-generate slug from title
        document.getElementById('title').addEventListener('input', function() {
            const title = this.value;
            const slug = title.toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim('-');
            document.getElementById('slug').value = slug;
        });

        // Image preview function
        function previewImage(url) {
            const previewContainer = document.getElementById('image-preview');
            if (url) {
                previewContainer.innerHTML = `<img src="${url}" alt="Service Image Preview" class="preview-image">`;
            } else {
                previewContainer.innerHTML = '';
            }
        }
    </script>
</body>
</html>
