<?php
$pageTitle = 'Order Confirmation - Boutique Fashion Store';
require_once 'config/database.php';

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if (!$orderId) {
    header('Location: index.php');
    exit;
}

$query = "SELECT * FROM orders WHERE id = " . $orderId;
$result = mysqli_query($conn, $query);
$order = $result ? mysqli_fetch_assoc($result) : null;

if (!$order) {
    header('Location: index.php');
    exit;
}

// Get order items
$itemsQuery = "
    SELECT oi.*, p.name FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = $orderId
";
$itemsResult = mysqli_query($conn, $itemsQuery);
$items = [];
if ($itemsResult) {
    while ($row = mysqli_fetch_assoc($itemsResult)) {
        $items[] = $row;
    }
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
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-lg">
                <div class="card-body p-5 text-center">
                    <div class="mb-4">
                        <h1 class="text-success mb-2">✓ Order Confirmed!</h1>
                        <p class="text-muted fs-5">Thank you for your purchase</p>
                    </div>
                    
                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <h6 class="text-muted">Order Number</h6>
                            <h4 class="text-orange"><?php echo htmlspecialchars($order['order_number']); ?></h4>
                        </div>
                    </div>
                    
                    <div class="row text-center mb-4">
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted">Order Date</h6>
                            <p><?php echo date('M d, Y', strtotime($order['created_at'])); ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted">Total Amount</h6>
                            <p class="text-orange fs-5">$<?php echo number_format($order['total'], 2); ?></p>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6 class="text-muted mb-3">Order Details</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Qty</th>
                                    <th>Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>$<?php echo number_format($item['product_price'] ?? 0, 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="alert alert-info mb-4">
                        <h6 class="mb-2">📦 Shipping Address</h6>
                        <p class="mb-0">
                            <?php echo htmlspecialchars($order['customer_name']); ?><br>
                            <?php echo htmlspecialchars($order['shipping_address']); ?><br>
                            <?php echo htmlspecialchars($order['city']); ?>, <?php echo htmlspecialchars($order['postal_code']); ?>
                        </p>
                    </div>
                    
                    <p class="text-muted mb-4">
                        A confirmation email has been sent to <strong><?php echo htmlspecialchars($order['customer_email']); ?></strong>
                    </p>
                    
                    <div class="d-grid gap-2 d-sm-flex justify-content-center">
                        <a href="index.php" class="btn btn-primary-custom">Continue Shopping</a>
                        <?php if (isLoggedIn()): ?>
                        <a href="profile.php" class="btn btn-outline-secondary">View Order History</a>
                        <?php endif; ?>
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
