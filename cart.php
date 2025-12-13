<?php
$pageTitle = 'Shopping Cart - Boutique Fashion Store';
require_once 'config/database.php';

$cart = $_SESSION['cart'] ?? [];
$cartItems = [];
$subtotal = 0;

if (!empty($cart)) {
    foreach ($cart as $item) {
        // Ensure product_id exists before querying
        if (!isset($item['product_id']) || empty($item['product_id'])) {
            continue;
        }
        
        $query = "
            SELECT p.*, pi.image_url 
            FROM products p 
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE p.id = " . intval($item['product_id']);
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
}

// Reservation system - no shipping costs
$reservationFee = 0; // Could add small processing fee if needed
$total = $subtotal + $reservationFee;
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
    <h1 class="mb-4 text-orange">Shopping Cart</h1>
    
    <?php if (empty($cartItems)): ?>
    <div class="alert alert-info text-center py-5">
        <h4>Your cart is empty</h4>
        <p class="mb-3">Start shopping to add items to your cart</p>
        <a href="shop.php" class="btn btn-primary-custom">Continue Shopping</a>
    </div>
    <?php else: ?>
    
    <div class="row">
        <!-- Cart Items -->
        <div class="col-lg-8">
            <div class="card">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cartItems as $cartKey => $item): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo htmlspecialchars($item['product']['image_url'] ?? 'https://via.placeholder.com/60x60?text=No+Image'); ?>" 
                                             style="width: 60px; height: 60px; object-fit: cover; margin-right: 15px; border-radius: 4px;" 
                                             alt="<?php echo htmlspecialchars($item['product']['name']); ?>"
                                             onerror="this.src='https://via.placeholder.com/60x60?text=No+Image'">
                                        <div>
                                            <a href="product.php?id=<?php echo $item['product_id']; ?>" class="text-decoration-none text-dark">
                                                <strong><?php echo htmlspecialchars($item['product']['name']); ?></strong>
                                            </a>
                                            <?php if ($item['size']): ?><br><small>Size: <?php echo htmlspecialchars($item['size']); ?></small><?php endif; ?>
                                            <?php if ($item['color']): ?><br><small>Color: <?php echo htmlspecialchars($item['color']); ?></small><?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>Ugx<?php echo number_format($item['product']['discount_price'] ?? $item['product']['price'], 2); ?></td>
                                <td>
                                    <input type="number" class="form-control" style="width: 70px;" value="<?php echo $item['quantity']; ?>" min="1" 
                                           onchange="updateCart(<?php echo $item['product_id']; ?>, <?php echo htmlspecialchars(json_encode($item['size'])); ?>, <?php echo htmlspecialchars(json_encode($item['color'])); ?>, this.value)">
                                </td>
                                <td>Ugx<?php echo number_format($item['subtotal'], 2); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-danger" onclick="removeFromCart(<?php echo $item['product_id']; ?>, <?php echo htmlspecialchars(json_encode($item['size'])); ?>, <?php echo htmlspecialchars(json_encode($item['color'])); ?>)">
                                        Remove
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Cart Summary -->
        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-body">
                    <h5 class="card-title mb-3">Order Summary</h5>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span>Ugx<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Reservation Fee:</span>
                        <span>Ugx<?php echo number_format($reservationFee, 2); ?></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <strong>Total:</strong>
                        <strong class="text-orange fs-5">Ugx<?php echo number_format($total, 2); ?></strong>
                    </div>
                    
                    <a href="checkout.php" class="btn btn-primary-custom w-100 mb-2">Proceed to Checkout</a>
                    <a href="shop.php" class="btn btn-outline-secondary w-100">Continue Shopping</a>
                </div>
            </div>
        </div>
    </div>
    
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function updateCart(productId, size, color, quantity) {
    fetch('cart_handler.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=update&product_id=' + productId + '&quantity=' + quantity + '&size=' + (size || '') + '&color=' + (color || '')
    })
    .then(() => location.reload());
}

function removeFromCart(productId, size, color) {
    if (confirm('Remove from cart?')) {
        fetch('cart_handler.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=remove&product_id=' + productId + '&size=' + (size || '') + '&color=' + (color || '')
        })
        .then(() => location.reload());
    }
}
</script>
</body>
</html>