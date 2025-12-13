<?php
$pageTitle = 'Checkout - Boutique Fashion Store';
require_once 'config/database.php';

$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    header('Location: cart.php');
    exit;
}

// Calculate totals
$cartItems = [];
$subtotal = 0;

foreach ($cart as $item) {
    // Ensure product_id exists before querying
    if (!isset($item['product_id']) || empty($item['product_id'])) {
        continue;
    }
    
    $query = "SELECT * FROM products WHERE id = " . intval($item['product_id']);
    $result = mysqli_query($conn, $query);
    $product = $result ? mysqli_fetch_assoc($result) : null;
    
    if ($product) {
        $price = $product['discount_price'] ?? $product['price'];
        $item['product'] = $product;
        $item['subtotal'] = $price * $item['quantity'];
        $cartItems[] = $item;
        $subtotal += $item['subtotal'];
    }
}

$shippingCost = 10;
$total = $subtotal + $shippingCost;

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $firstName = sanitizeInput($_POST['first_name'] ?? '');
        $lastName = sanitizeInput($_POST['last_name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $address = sanitizeInput($_POST['address'] ?? '');
        $city = sanitizeInput($_POST['city'] ?? '');
        $postalCode = sanitizeInput($_POST['postal_code'] ?? '');
        $paymentMethod = sanitizeInput($_POST['payment_method'] ?? '');
        
        if (empty($firstName) || empty($lastName) || empty($email) || empty($phone) || empty($address) || empty($city) || empty($postalCode)) {
            $error = 'All fields are required.';
        } else {
            // Generate order number
            $orderNumber = 'ORD-' . time();
            
            // Insert order
            $userId = isLoggedIn() ? $_SESSION['user_id'] : null;
            
            $customerName = "$firstName $lastName";
            $customerName = mysqli_real_escape_string($conn, $customerName);
            $email = mysqli_real_escape_string($conn, $email);
            $phone = mysqli_real_escape_string($conn, $phone);
            $address = mysqli_real_escape_string($conn, $address);
            $city = mysqli_real_escape_string($conn, $city);
            $postalCode = mysqli_real_escape_string($conn, $postalCode);
            $paymentMethod = mysqli_real_escape_string($conn, $paymentMethod);
            
            $orderQuery = "
                INSERT INTO orders (
                    user_id, order_number, customer_name, customer_email, customer_phone,
                    shipping_address, city, postal_code, subtotal, shipping_cost, total,
                    payment_method, status
                ) VALUES (
                    " . ($userId ? $userId : "NULL") . ",
                    '$orderNumber',
                    '$customerName',
                    '$email',
                    '$phone',
                    '$address',
                    '$city',
                    '$postalCode',
                    $subtotal,
                    $shippingCost,
                    $total,
                    '$paymentMethod',
                    'pending'
                )
            ";
            
            if (mysqli_query($conn, $orderQuery)) {
                $orderId = mysqli_insert_id($conn);
                
                // Insert order items
                foreach ($cartItems as $item) {
                    $price = $item['product']['discount_price'] ?? $item['product']['price'];
                    
                    $productName = mysqli_real_escape_string($conn, $item['product']['name']);
                    $size = mysqli_real_escape_string($conn, $item['size'] ?? '');
                    $color = mysqli_real_escape_string($conn, $item['color'] ?? '');
                    
                    $itemQuery = "
                        INSERT INTO order_items (order_id, product_id, product_name, product_price, quantity, size, color, subtotal)
                        VALUES (
                            $orderId,
                            " . intval($item['product_id']) . ",
                            '$productName',
                            $price,
                            " . intval($item['quantity']) . ",
                            '$size',
                            '$color',
                            " . ($price * $item['quantity']) . "
                        )
                    ";
                    
                    mysqli_query($conn, $itemQuery);
                }
                
                // Clear cart
                $_SESSION['cart'] = [];
                
                // Redirect to order confirmation
                header('Location: order_confirmation.php?order_id=' . $orderId);
                exit;
            } else {
                $error = 'Order processing failed: ' . mysqli_error($conn);
            }
        }
    }
}

$csrfToken = generateCSRFToken();
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
    <h1 class="mb-4 text-orange">Checkout</h1>
    
    <div class="row">
        <!-- Checkout Form -->
        <div class="col-lg-8">
            <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Shipping Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="phone" name="phone" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <input type="text" class="form-control" id="address" name="address" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="postal_code" class="form-label">Postal Code</label>
                                <input type="text" class="form-control" id="postal_code" name="postal_code" required>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Payment Method</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" id="credit_card" value="credit_card" checked required>
                            <label class="form-check-label" for="credit_card">Credit Card</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" id="debit_card" value="debit_card" required>
                            <label class="form-check-label" for="debit_card">Debit Card</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_method" id="paypal" value="paypal" required>
                            <label class="form-check-label" for="paypal">PayPal</label>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary-custom btn-lg w-100">Place Order</button>
            </form>
        </div>
        
        <!-- Order Summary -->
        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header">
                    <h5 class="mb-0">Order Summary</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6>Items:</h6>
                        <div class="list-group list-group-sm">
                            <?php foreach ($cartItems as $item): ?>
                            <div class="list-group-item d-flex justify-content-between">
                                <span><?php echo htmlspecialchars($item['product']['name']); ?> (x<?php echo $item['quantity']; ?>)</span>
                                <span>Ugx<?php echo number_format($item['subtotal'], 2); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span>Ugx<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Shipping:</span>
                        <span>Ugx<?php echo number_format($shippingCost, 2); ?></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <strong>Total:</strong>
                        <strong class="text-orange fs-5">$<?php echo number_format($total, 2); ?></strong>
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