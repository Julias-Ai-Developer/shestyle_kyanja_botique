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

// Reservation system - no shipping costs
$reservationFee = 0; // Could add small processing fee if needed
$total = $subtotal + $reservationFee;

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
                    shipping_address, city, postal_code, subtotal, reservation_fee, payment_percentage, total,
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
                    $reservationFee,
                    " . (int)$_POST['payment_percentage'] . ",
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
                    <div class="card-header bg-orange text-white">
                        <h5 class="mb-0">📋 Pickup Information</h5>
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
                    <div class="card-header bg-orange text-white">
                        <h5 class="mb-0">💳 Payment Method</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Choose your preferred mobile money payment method:</p>
                        
                        <div class="form-check mb-3 p-3 border rounded" style="background: rgba(255, 107, 53, 0.05);">
                            <input class="form-check-input" type="radio" name="payment_method" id="airtel_money" value="airtel_money" checked required>
                            <label class="form-check-label" for="airtel_money" style="cursor: pointer;">
                                <strong>📱 Airtel Money</strong>
                                <br>
                                <small class="text-muted">Fast and secure mobile money payment</small>
                            </label>
                        </div>
                        
                        <div class="form-check mb-3 p-3 border rounded" style="background: rgba(255, 179, 102, 0.05);">
                            <input class="form-check-input" type="radio" name="payment_method" id="mtn_money" value="mtn_money" required>
                            <label class="form-check-label" for="mtn_money" style="cursor: pointer;">
                                <strong>📱 MTN Mobile Money</strong>
                                <br>
                                <small class="text-muted">Convenient payment via MTN MoMo</small>
                            </label>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <small>
                                <strong>How it works:</strong> After placing your order, you will receive a payment prompt on your phone. 
                                Enter your PIN to complete the payment. Your order will be confirmed once payment is received.
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header bg-orange text-white">
                        <h5 class="mb-0">💰 Payment Amount</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Choose how much to pay now:</p>
                        
                        <div class="form-check mb-3 p-3 border rounded" style="background: rgba(255, 107, 53, 0.05);">
                            <input class="form-check-input payment-percentage" type="radio" name="payment_percentage" id="pay_50" value="50" required onchange="updatePaymentAmount()">
                            <label class="form-check-label" for="pay_50" style="cursor: pointer; width: 100%;">
                                <strong>Pay 50% Now</strong>
                                <br>
                                <small class="text-muted">Pay remaining 50% when picking up</small>
                                <br>
                                <small id="amount_50" class="text-orange fw-bold">Ugx0.00</small>
                            </label>
                        </div>
                        
                        <div class="form-check mb-3 p-3 border rounded" style="background: rgba(255, 179, 102, 0.05);">
                            <input class="form-check-input payment-percentage" type="radio" name="payment_percentage" id="pay_70" value="70" required onchange="updatePaymentAmount()">
                            <label class="form-check-label" for="pay_70" style="cursor: pointer; width: 100%;">
                                <strong>Pay 70% Now</strong>
                                <br>
                                <small class="text-muted">Pay remaining 30% when picking up</small>
                                <br>
                                <small id="amount_70" class="text-orange fw-bold">Ugx0.00</small>
                            </label>
                        </div>
                        
                        <div class="form-check mb-3 p-3 border rounded" style="background: rgba(100, 200, 100, 0.05);">
                            <input class="form-check-input payment-percentage" type="radio" name="payment_percentage" id="pay_100" value="100" checked required onchange="updatePaymentAmount()">
                            <label class="form-check-label" for="pay_100" style="cursor: pointer; width: 100%;">
                                <strong>Pay 100% Now</strong>
                                <br>
                                <small class="text-muted">Full payment upfront</small>
                                <br>
                                <small id="amount_100" class="text-orange fw-bold">Ugx0.00</small>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-success">
                    <strong>Amount to Pay Now:</strong>
                    <h5 class="mb-0" id="display_amount">Ugx0.00</h5>
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
                    <hr>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Reservation Amount:</span>
                        <span class="text-orange fw-bold">Ugx<?php echo number_format($total, 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 p-2 bg-light rounded">
                        <span>Payment Percentage:</span>
                        <span id="percentageDisplay" class="fw-bold">100%</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 p-2 bg-warning bg-opacity-10 rounded">
                        <strong>Amount to Pay Now:</strong>
                        <strong class="text-orange fs-5" id="amountDisplay">Ugx<?php echo number_format($total, 2); ?></strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <strong>Total:</strong>
                        <strong class="text-orange fs-5">Ugx<?php echo number_format($total, 2); ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const totalAmount = <?php echo $total; ?>;
    
    function updatePaymentAmount() {
        const percentage = document.querySelector('input[name="payment_percentage"]:checked').value;
        const amountToPay = (totalAmount * percentage) / 100;
        
        // Update display amounts in payment options
        document.getElementById('amount_50').textContent = 'Ugx' + (totalAmount * 0.5).toLocaleString('en-UG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('amount_70').textContent = 'Ugx' + (totalAmount * 0.7).toLocaleString('en-UG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('amount_100').textContent = 'Ugx' + totalAmount.toLocaleString('en-UG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        
        // Update summary section
        document.getElementById('percentageDisplay').textContent = percentage + '%';
        document.getElementById('amountDisplay').textContent = 'Ugx' + amountToPay.toLocaleString('en-UG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        
        // Update main display (if exists)
        if (document.getElementById('display_amount')) {
            document.getElementById('display_amount').textContent = 'Ugx' + amountToPay.toLocaleString('en-UG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    }
    
    // Initialize on page load
    window.addEventListener('DOMContentLoaded', function() {
        updatePaymentAmount();
    });
</script>
</body>
</html>