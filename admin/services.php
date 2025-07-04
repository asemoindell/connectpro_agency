<?php
session_start();
require_once '../config/database.php';
require_once 'includes/admin-layout.php';

$database = new Database();
$db = $database->getConnection();

// Handle service actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['service_id'])) {
        $action = $_POST['action'];
        $service_id = $_POST['service_id'];
        
        switch ($action) {
            case 'activate':
                $stmt = $db->prepare("UPDATE services SET status = 'active' WHERE id = ?");
                $stmt->execute([$service_id]);
                $success_message = "Service activated successfully!";
                break;
            case 'deactivate':
                $stmt = $db->prepare("UPDATE services SET status = 'inactive' WHERE id = ?");
                $stmt->execute([$service_id]);
                $success_message = "Service deactivated successfully!";
                break;
            case 'feature':
                $stmt = $db->prepare("UPDATE services SET is_featured = 1 WHERE id = ?");
                $stmt->execute([$service_id]);
                $success_message = "Service marked as featured!";
                break;
            case 'unfeature':
                $stmt = $db->prepare("UPDATE services SET is_featured = 0 WHERE id = ?");
                $stmt->execute([$service_id]);
                $success_message = "Service unfeatured successfully!";
                break;
            case 'delete':
                $stmt = $db->prepare("DELETE FROM services WHERE id = ?");
                $stmt->execute([$service_id]);
                $success_message = "Service deleted successfully!";
                break;
        }
    }
}

// Get filters
$status_filter = $_GET['status'] ?? 'all';
$category_filter = $_GET['category'] ?? 'all';
$search = $_GET['search'] ?? '';

// Get service statistics
$stats_query = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive,
        SUM(CASE WHEN is_featured = 1 THEN 1 ELSE 0 END) as featured
    FROM services
";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch();

// Get categories
$categories_stmt = $db->query("SELECT DISTINCT category FROM services WHERE category IS NOT NULL ORDER BY category");
$categories = $categories_stmt->fetchAll(PDO::FETCH_COLUMN);

// Build services query
$services_query = "SELECT * FROM services WHERE 1=1";
$params = [];

if ($status_filter !== 'all') {
    $services_query .= " AND status = ?";
    $params[] = $status_filter;
}

if ($category_filter !== 'all') {
    $services_query .= " AND category = ?";
    $params[] = $category_filter;
}

if ($search) {
    $services_query .= " AND (title LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$services_query .= " ORDER BY created_at DESC";

$services_stmt = $db->prepare($services_query);
$services_stmt->execute($params);
$services = $services_stmt->fetchAll();

// Get admin info
$stmt = $db->prepare("SELECT * FROM admins WHERE admin_id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch();

// Build the content
ob_start();
?>

<?php if (isset($success_message)): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i>
    <?php echo htmlspecialchars($success_message); ?>
</div>
<?php endif; ?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title">Service Management</h1>
            <p class="page-subtitle">Manage and monitor all services</p>
        </div>
        <a href="service-add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i>
            Add New Service
        </a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <?php echo renderStatsCard('fas fa-cogs', $stats['total'], 'Total Services'); ?>
    <?php echo renderStatsCard('fas fa-check-circle', $stats['active'], 'Active Services'); ?>
    <?php echo renderStatsCard('fas fa-times-circle', $stats['inactive'], 'Inactive Services'); ?>
    <?php echo renderStatsCard('fas fa-star', $stats['featured'], 'Featured Services'); ?>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="d-flex gap-3 align-items-end">
            <div class="form-group" style="margin-bottom: 0;">
                <label for="status" class="form-label">Filter by Status</label>
                <select name="status" id="status" class="form-control form-select">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Services</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            
            <div class="form-group" style="margin-bottom: 0;">
                <label for="category" class="form-label">Filter by Category</label>
                <select name="category" id="category" class="form-control form-select">
                    <option value="all" <?php echo $category_filter === 'all' ? 'selected' : ''; ?>>All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category); ?>" 
                                <?php echo $category_filter === $category ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" style="margin-bottom: 0;">
                <label for="search" class="form-label">Search Services</label>
                <input type="text" name="search" id="search" class="form-control" 
                       placeholder="Search by title or description..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i>
                Search
            </button>
            
            <a href="services.php" class="btn btn-outline">
                <i class="fas fa-times"></i>
                Clear
            </a>
        </form>
    </div>
</div>

<!-- Services Grid -->
<?php if (empty($services)): ?>
    <div class="card">
        <div class="card-body">
            <div class="text-center py-5">
                <i class="fas fa-cogs text-muted" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                <h4 class="text-muted">No services found</h4>
                <p class="text-muted">No services match your current filters.</p>
                <a href="service-add.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    Add First Service
                </a>
            </div>
        </div>
    </div>
<?php else: ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem;">
        <?php foreach ($services as $service): ?>
        <div class="card">
            <?php if ($service['image_url']): ?>
                <div style="height: 200px; background: url('<?php echo htmlspecialchars($service['image_url']); ?>') center/cover; border-radius: var(--border-radius) var(--border-radius) 0 0;"></div>
            <?php else: ?>
                <div style="height: 200px; background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem; border-radius: var(--border-radius) var(--border-radius) 0 0;">
                    <i class="fas fa-cog"></i>
                </div>
            <?php endif; ?>
            
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h5 style="margin: 0; font-weight: 600;"><?php echo htmlspecialchars($service['title']); ?></h5>
                    <div class="d-flex gap-2">
                        <?php echo renderStatusBadge($service['status']); ?>
                        <?php if ($service['is_featured']): ?>
                            <span class="status-badge" style="background: #fef3c7; color: #92400e;">
                                <i class="fas fa-star"></i> Featured
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <p style="color: var(--text-secondary); margin-bottom: 1rem; font-size: 0.875rem;">
                    <?php echo htmlspecialchars(substr($service['short_description'] ?: $service['description'], 0, 120)) . '...'; ?>
                </p>
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span style="color: var(--text-muted); font-size: 0.875rem;">
                        <i class="fas fa-tag"></i>
                        <?php echo htmlspecialchars($service['category'] ?: 'No Category'); ?>
                    </span>
                    <span style="font-weight: 600; color: var(--primary-color);">
                        <?php echo htmlspecialchars($service['price_range'] ?: 'Contact for Price'); ?>
                    </span>
                </div>
                
                <div class="d-flex gap-2">
                    <?php if ($service['status'] === 'active'): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="deactivate">
                            <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                            <button type="submit" class="btn btn-warning btn-sm" title="Deactivate">
                                <i class="fas fa-pause"></i>
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="activate">
                            <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                            <button type="submit" class="btn btn-success btn-sm" title="Activate">
                                <i class="fas fa-play"></i>
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <?php if ($service['is_featured']): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="unfeature">
                            <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                            <button type="submit" class="btn btn-outline btn-sm" title="Remove from Featured">
                                <i class="fas fa-star-o"></i>
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="feature">
                            <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                            <button type="submit" class="btn btn-warning btn-sm" title="Mark as Featured">
                                <i class="fas fa-star"></i>
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <a href="service-edit.php?id=<?php echo $service['id']; ?>" class="btn btn-primary btn-sm" title="Edit Service">
                        <i class="fas fa-edit"></i>
                    </a>
                    
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this service?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                        <button type="submit" class="btn btn-danger btn-sm" title="Delete Service">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
echo renderAdminLayout('Service Management', 'services', $content);
?>
