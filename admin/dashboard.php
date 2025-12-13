<?php
require_once '../config/database.php';

if (!isAdmin()) {
    header('Location: login.php');
    exit;
}

// Redirect workers to their dashboard
if ($_SESSION['admin_role'] === 'worker') {
    header('Location: worker-dashboard.php');
    exit;
}

$statsQuery = "
    SELECT 
        (SELECT COUNT(*) FROM orders) as total_orders,
        (SELECT COUNT(*) FROM users) as total_customers,
        (SELECT SUM(total) FROM orders WHERE status != 'cancelled') as total_sales,
        (SELECT COUNT(*) FROM products WHERE stock_quantity < 10) as low_stock
";
$statsResult = mysqli_query($conn, $statsQuery);
if (!$statsResult) {
    die('Database error: ' . mysqli_error($conn));
}
$stats = mysqli_fetch_assoc($statsResult);

// Recent orders
$recentQuery = "
    SELECT * FROM orders 
    ORDER BY created_at DESC 
    LIMIT 5
";
$recentResult = mysqli_query($conn, $recentQuery);
if (!$recentResult) {
    die('Database error: ' . mysqli_error($conn));
}
$recentOrders = [];
while ($row = mysqli_fetch_assoc($recentResult)) {
    $recentOrders[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid">
        <span class="navbar-brand">🔶 Boutique Admin</span>
        <div class="d-flex align-items-center gap-3">
            <div class="text-light">
                <!-- <small class="d-block">Logged in as:</small> -->
                <strong><?php echo htmlspecialchars($_SESSION['admin_username']); ?></strong>
                <span class="badge bg-orange ms-2"><?php echo ucfirst(str_replace('_', ' ', $_SESSION['admin_role'])); ?></span>
            </div>
            <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 d-md-block bg-light sidebar p-0">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link active" href="dashboard.php" style="color: #FF6B35; font-weight: 600; border-left: 4px solid #FF6B35; padding-left: 12px;">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="products.php">Products</a></li>
                    <li class="nav-item"><a class="nav-link" href="images.php">Images</a></li>
                    <li class="nav-item"><a class="nav-link" href="banners.php">Banners</a></li>
                    <li class="nav-item"><a class="nav-link" href="workers.php">Workers</a></li>
                    <li class="nav-item"><a class="nav-link" href="orders.php">Orders</a></li>
                    <li class="nav-item"><a class="nav-link" href="customers.php">Customers</a></li>
                    <li class="nav-item"><a class="nav-link" href="settings.php">Settings</a></li>
                </ul>
            </div>
        </nav>

        <main class="col-md-10 ms-sm-auto px-md-4">
            <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
                <h2>Dashboard</h2>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <h6 class="card-title">Total Orders</h6>
                            <h3 class="mb-0"><?php echo $stats['total_orders']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <h6 class="card-title">Total Sales</h6>
                            <h3 class="mb-0">Ugx<?php echo number_format($stats['total_sales'] ?? 0, 2); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <h6 class="card-title">Customers</h6>
                            <h3 class="mb-0"><?php echo $stats['total_customers']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <h6 class="card-title">Low Stock Items</h6>
                            <h3 class="mb-0"><?php echo $stats['low_stock']; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Orders</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td>Ugx<?php echo number_format($order['total'], 2); ?></td>
                                <td><span class="badge bg-<?php echo $order['status'] === 'delivered' ? 'success' : ($order['status'] === 'cancelled' ? 'danger' : 'warning'); ?>"><?php echo ucfirst($order['status']); ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td><a href="orders.php" class="btn btn-sm btn-outline-primary">View</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
