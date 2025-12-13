<?php
require_once '../config/database.php';

if (!isAdmin() || $_SESSION['admin_role'] !== 'worker') {
    header('Location: login.php');
    exit;
}

$adminId = $_SESSION['admin_id'];
$username = $_SESSION['admin_username'];

// Get worker info
$workerQuery = "
    SELECT w.*, wr.name as role_name 
    FROM workers w 
    JOIN worker_roles wr ON w.worker_role_id = wr.id 
    WHERE w.admin_id = $adminId AND w.is_active = 1
";
$workerResult = mysqli_query($conn, $workerQuery);
$worker = mysqli_fetch_assoc($workerResult);

// Get stats relevant to worker role
$statsQuery = "
    SELECT 
        (SELECT COUNT(*) FROM products WHERE is_active = 1) as total_products,
        (SELECT COUNT(*) FROM orders WHERE status = 'pending') as pending_orders,
        (SELECT COUNT(*) FROM product_images) as total_images
";
$statsResult = mysqli_query($conn, $statsQuery);
$stats = mysqli_fetch_assoc($statsResult);

// Get recent orders for reference
$recentQuery = "
    SELECT * FROM orders 
    WHERE status != 'cancelled'
    ORDER BY created_at DESC 
    LIMIT 5
";
$recentResult = mysqli_query($conn, $recentQuery);
$recentOrders = [];
while ($row = mysqli_fetch_assoc($recentResult)) {
    $recentOrders[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Worker Dashboard - <?php echo htmlspecialchars($worker['role_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid">
        <span class="navbar-brand">🔶 Boutique Worker Portal</span>
        <div>
            <span class="text-light me-3">Welcome, <?php echo htmlspecialchars($worker['first_name'] . ' ' . $worker['last_name']); ?></span>
            <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 d-md-block bg-light sidebar p-0">
            <div class="position-sticky pt-3">
                <h6 class="px-3 py-2 text-muted">YOUR ROLE</h6>
                <div class="px-3 py-2">
                    <div class="badge bg-orange text-white" style="font-size: 0.9rem; padding: 0.5rem 0.75rem;">
                        <?php echo htmlspecialchars($worker['role_name']); ?>
                    </div>
                </div>
                
                <h6 class="px-3 py-2 text-muted mt-4">MENU</h6>
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link active" href="worker-dashboard.php" style="color: #FF6B35; font-weight: 600; border-left: 4px solid #FF6B35; padding-left: 12px;">Dashboard</a></li>
                    
                    <?php if (strpos($worker['role_name'], 'Sales') !== false || strpos($worker['role_name'], 'Manager') !== false): ?>
                    <li class="nav-item"><a class="nav-link" href="orders.php">Orders</a></li>
                    <?php endif; ?>
                    
                    <?php if (strpos($worker['role_name'], 'Inventory') !== false || strpos($worker['role_name'], 'Manager') !== false): ?>
                    <li class="nav-item"><a class="nav-link" href="products.php">Products</a></li>
                    <?php endif; ?>
                    
                    <?php if (strpos($worker['role_name'], 'Visual') !== false || strpos($worker['role_name'], 'Manager') !== false): ?>
                    <li class="nav-item"><a class="nav-link" href="images.php">Images</a></li>
                    <li class="nav-item"><a class="nav-link" href="banners.php">Banners</a></li>
                    <?php endif; ?>
                    
                    <li class="nav-item mt-3"><a class="nav-link text-secondary" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </nav>

        <main class="col-md-9 ms-sm-auto px-md-4">
            <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
                <h2>Welcome to Your Dashboard</h2>
            </div>
            
            <!-- Worker Info Card -->
            <div class="card mb-4">
                <div class="card-header bg-orange text-white">
                    <h5 class="mb-0">Your Profile Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($worker['first_name'] . ' ' . $worker['last_name']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($worker['email']); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($worker['phone']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Role:</strong> <span class="badge bg-orange"><?php echo htmlspecialchars($worker['role_name']); ?></span></p>
                            <p><strong>Hire Date:</strong> <?php echo date('M d, Y', strtotime($worker['hire_date'])); ?></p>
                            <p><strong>Status:</strong> <span class="badge bg-success">Active</span></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Role-Based Access Info -->
            <div class="card mb-4">
                <div class="card-header bg-orange text-white">
                    <h5 class="mb-0">Your Access Permissions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php if (strpos($worker['role_name'], 'Sales') !== false): ?>
                        <div class="col-md-6 mb-3">
                            <h6>📊 Sales Associate</h6>
                            <p class="text-muted mb-0">Access to: Orders, Customer Information</p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (strpos($worker['role_name'], 'Inventory') !== false): ?>
                        <div class="col-md-6 mb-3">
                            <h6>📦 Inventory Manager</h6>
                            <p class="text-muted mb-0">Access to: Products, Stock Management</p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (strpos($worker['role_name'], 'Visual') !== false): ?>
                        <div class="col-md-6 mb-3">
                            <h6>🎨 Visual Merchandiser</h6>
                            <p class="text-muted mb-0">Access to: Product Images, Banners, Displays</p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (strpos($worker['role_name'], 'Manager') !== false): ?>
                        <div class="col-md-6 mb-3">
                            <h6>👔 Store Manager</h6>
                            <p class="text-muted mb-0">Access to: All sections for oversight</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card border-left-orange">
                        <div class="card-body">
                            <h6 class="text-muted">Total Products</h6>
                            <h3 class="text-orange"><?php echo $stats['total_products']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-left-orange">
                        <div class="card-body">
                            <h6 class="text-muted">Pending Orders</h6>
                            <h3 class="text-orange"><?php echo $stats['pending_orders']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-left-orange">
                        <div class="card-body">
                            <h6 class="text-muted">Product Images</h6>
                            <h3 class="text-orange"><?php echo $stats['total_images']; ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Orders -->
            <?php if (strpos($worker['role_name'], 'Sales') !== false || strpos($worker['role_name'], 'Manager') !== false): ?>
            <div class="card">
                <div class="card-header bg-orange text-white">
                    <h5 class="mb-0">Recent Orders (Reference Only)</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recentOrders)): ?>
                                <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td>$<?php echo number_format($order['total'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $order['status'] === 'completed' ? 'success' : ($order['status'] === 'pending' ? 'warning' : 'info'); ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">No orders found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<style>
    .border-left-orange {
        border-left: 4px solid #FF6B35 !important;
    }
    
    .text-orange {
        color: #FF6B35;
    }
    
    .card {
        border: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border-radius: 6px;
        margin-bottom: 1.5rem;
    }
    
    .card-header {
        border-radius: 6px 6px 0 0;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
