<?php
$pageTitle = 'Order Details - Boutique Fashion Store';
require_once 'config/database.php';

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$orderId) {
    header('Location: index.php');
    exit;
}

$orderId_int = intval($orderId);
$orderQuery = "SELECT * FROM orders WHERE id = $orderId_int";
$orderResult = mysqli_query($conn, $orderQuery);
if (!$orderResult) {
    die('Database error: ' . mysqli_error($conn));
}
$order = mysqli_fetch_assoc($orderResult);

if (!$order) {
    header('Location: index.php');
    exit;
}

// Check authorization
if (isLoggedIn() && $order['user_id'] !== $_SESSION['user_id']) {
    header('Location: index.php');
    exit;
}

// Get order items
$itemsQuery = "
    SELECT oi.*, p.name, pi.image_url FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    WHERE oi.order_id = $orderId_int
";
$itemsResult = mysqli_query($conn, $itemsQuery);
if (!$itemsResult) {
    die('Database error: ' . mysqli_error($conn));
}
$items = [];
while ($row = mysqli_fetch_assoc($itemsResult)) {
    $items[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/header.php'; ?>

<div class="container my-5">
    <a href="<?php echo isLoggedIn() ? 'profile.php' : 'index.php'; ?>" class="btn btn-outline-secondary mb-3">← Back</a>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Order <?php echo htmlspecialchars($order['order_number']); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted">Order Status</h6>
                            <p>
                                <span class="badge bg-<?php echo $order['status'] === 'delivered' ? 'success' : ($order['status'] === 'cancelled' ? 'danger' : 'warning'); ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Order Date</h6>
                            <p><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></p>
                        </div>
                    </div>
                    
                    <h6 class="text-orange mb-3">Items</h6>
                    <div class="table-responsive">
                        <table class="table">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Qty</th>
                                    <th>product_price</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo htmlspecialchars($item['image_url'] ?? ''); ?>" style="width: 50px; height: 50px; object-fit: cover; margin-right: 10px;">
                                            <div>
                                                <?php echo htmlspecialchars($item['name']); ?>
                                                <?php if ($item['size']): ?><br><small>Size: <?php echo htmlspecialchars($item['size']); ?></small><?php endif; ?>
                                                <?php if ($item['color']): ?><br><small>Color: <?php echo htmlspecialchars($item['color']); ?></small><?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>Ugx<?php echo number_format($item['product_price'], 2); ?></td>
                                    <td>Ugx<?php echo number_format($item['product_price'] * $item['quantity'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class=\"mb-0\">📍 Pickup Address</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0">
                        <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
                        <?php echo htmlspecialchars($order['shipping_address']); ?><br>
                        <?php echo htmlspecialchars($order['city']); ?>, <?php echo htmlspecialchars($order['postal_code']); ?><br>
                        <?php echo htmlspecialchars($order['customer_phone']); ?>
                    </p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Order Summary</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span>Ugx<?php echo number_format($order['subtotal'], 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Reservation Fee:</span>
                        <span>Ugx<?php echo number_format($order['reservation_fee'] ?? 0, 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 text-info">
                        <span>Payment Percentage:</span>
                        <span><?php echo $order['payment_percentage'] ?? 100; ?>%</span>
                    </div>
                    <?php if ($order['discount'] > 0): ?>
                    <div class="d-flex justify-content-between mb-2 text-success">
                        <span>Discount:</span>
                        <span>-Ugx<?php echo number_format($order['discount'], 2); ?></span>
                    </div>
                    <?php endif; ?>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <strong>Total:</strong>
                        <strong class="text-orange fs-5">Ugx<?php echo number_format($order['total'], 2); ?></strong>
                    </div>
                    
                    <div class="mt-3 p-3 bg-light rounded">
                        <h6 class="mb-2">Payment Method</h6>
                        <p class="mb-0"><?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
