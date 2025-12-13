<?php
$pageTitle = 'Product - Boutique Fashion Store';
require_once 'config/database.php';

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$productId) {
    header('Location: shop.php');
    exit;
}

// Get product details
$productId_int = intval($productId);
$productQuery = "
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.id = $productId_int AND p.is_active = 1
";
$productResult = mysqli_query($conn, $productQuery);
if (!$productResult) {
    die('Database error: ' . mysqli_error($conn));
}
$product = mysqli_fetch_assoc($productResult);

if (!$product) {
    header('Location: shop.php');
    exit;
}

// Get product images
$imagesQuery = "
    SELECT * FROM product_images 
    WHERE product_id = $productId_int
    ORDER BY is_primary DESC, display_order ASC
";
$imagesResult = mysqli_query($conn, $imagesQuery);
if (!$imagesResult) {
    die('Database error: ' . mysqli_error($conn));
}
$images = [];
while ($row = mysqli_fetch_assoc($imagesResult)) {
    $images[] = $row;
}

if (empty($images)) {
    $images = [['image_url' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600']];
}

// Get related products
$categoryId_int = intval($product['category_id']);
$relatedQuery = "
    SELECT p.*, pi.image_url FROM products p 
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    WHERE p.category_id = $categoryId_int AND p.id != $productId_int AND p.is_active = 1 
    LIMIT 4
";
$relatedResult = mysqli_query($conn, $relatedQuery);
if (!$relatedResult) {
    die('Database error: ' . mysqli_error($conn));
}
$relatedProducts = [];
while ($row = mysqli_fetch_assoc($relatedResult)) {
    $relatedProducts[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Boutique Fashion Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/header.php'; ?>

<div class="container my-5">
    <div class="row">
        <!-- Product Images -->
        <div class="col-md-5">
            <div class="mb-3">
                <img id="mainImage" src="<?php echo htmlspecialchars($images[0]['image_url'] ?? ''); ?>" 
                     class="img-fluid" alt="<?php echo htmlspecialchars($product['name']); ?>">
            </div>
            <?php if (count($images) > 1): ?>
            <div class="row g-2">
                <?php foreach (array_slice($images, 0, 4) as $img): ?>
                <div class="col-3">
                    <img src="<?php echo htmlspecialchars($img['image_url']); ?>" 
                         class="img-fluid cursor-pointer" alt="Thumbnail"
                         onclick="document.getElementById('mainImage').src = this.src;"
                         style="cursor: pointer;">
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Product Details -->
        <div class="col-md-7">
            <h1 class="mb-2"><?php echo htmlspecialchars($product['name']); ?></h1>
            
            <p class="text-muted mb-3">
                <a href="shop.php?category=<?php echo htmlspecialchars($product['category_id']); ?>" class="text-orange">
                    <?php echo htmlspecialchars($product['category_name']); ?>
                </a>
            </p>
            
            <!-- Price -->
            <div class="mb-3">
                <h3 class="text-orange mb-0">
                    <?php if ($product['discount_price']): ?>
                    Ugx<?php echo number_format($product['discount_price'], 2); ?>
                    <del class="text-muted fs-5">Ugx<?php echo number_format($product['price'], 2); ?></del>
                    <?php else: ?>
                    Ugx<?php echo number_format($product['price'], 2); ?>
                    <?php endif; ?>
                </h3>
                <?php if ($product['stock_quantity'] > 0): ?>
                <span class="badge bg-success">In Stock (<?php echo $product['stock_quantity']; ?> available)</span>
                <?php else: ?>
                <span class="badge bg-danger">Out of Stock</span>
                <?php endif; ?>
            </div>
            
            <!-- Description -->
            <div class="mb-4">
                <h5>Description</h5>
                <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
            </div>
            
            <!-- Sizes -->
            <?php if ($product['sizes']): ?>
            <div class="mb-4">
                <h5>Size</h5>
                <div class="btn-group" role="group">
                    <?php foreach (explode(',', $product['sizes']) as $size): ?>
                    <input type="radio" class="btn-check" name="size" id="size_<?php echo trim($size); ?>" value="<?php echo trim($size); ?>">
                    <label class="btn btn-outline-secondary" for="size_<?php echo trim($size); ?>">
                        <?php echo trim($size); ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Colors -->
            <?php if ($product['colors']): ?>
            <div class="mb-4">
                <h5>Color</h5>
                <div class="btn-group" role="group">
                    <?php foreach (explode(',', $product['colors']) as $color): ?>
                    <input type="radio" class="btn-check" name="color" id="color_<?php echo trim($color); ?>" value="<?php echo trim($color); ?>">
                    <label class="btn btn-outline-secondary" for="color_<?php echo trim($color); ?>">
                        <?php echo trim($color); ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Quantity & Add to Cart -->
            <div class="mb-4">
                <label for="quantity" class="form-label">Quantity</label>
                <div class="input-group" style="width: 150px;">
                    <button class="btn btn-outline-secondary" type="button" onclick="decreaseQty()">−</button>
                    <input type="number" class="form-control text-center" id="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>">
                    <button class="btn btn-outline-secondary" type="button" onclick="increaseQty()">+</button>
                </div>
            </div>
            
            <?php if ($product['stock_quantity'] > 0): ?>
            <button class="btn btn-primary-custom btn-lg mb-3 w-100" onclick="addToCart(<?php echo $productId; ?>)">
                🛒 Add to Cart
            </button>
            <?php else: ?>
            <button class="btn btn-secondary btn-lg mb-3 w-100" disabled>Out of Stock</button>
            <?php endif; ?>
            
            <a href="shop.php" class="btn btn-outline-secondary w-100">Continue Shopping</a>
        </div>
    </div>
    
    <!-- Related Products -->
    <?php if (!empty($relatedProducts)): ?>
    <hr class="my-5">
    <h3 class="text-center mb-4 text-orange">Related Products</h3>
    <div class="row g-4">
        <?php foreach ($relatedProducts as $related): ?>
        <div class="col-md-3 col-sm-6">
            <div class="card product-card">
                <a href="product.php?id=<?php echo $related['id']; ?>" class="text-decoration-none text-dark">
                    <img src="<?php echo htmlspecialchars($related['image_url'] ?? ''); ?>" class="card-img-top" alt="">
                    <div class="card-body">
                        <h6><?php echo htmlspecialchars($related['name']); ?></h6>
                        <span class="text-orange">$<?php echo number_format($related['discount_price'] ?? $related['price'], 2); ?></span>
                    </div>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function decreaseQty() {
    let qty = document.getElementById('quantity');
    if (qty.value > 1) qty.value--;
}

function increaseQty() {
    let qty = document.getElementById('quantity');
    let max = parseInt(qty.max);
    if (qty.value < max) qty.value++;
}

function addToCart(productId) {
    const quantity = parseInt(document.getElementById('quantity').value);
    const size = document.querySelector('input[name="size"]:checked')?.value || '';
    const color = document.querySelector('input[name="color"]:checked')?.value || '';
    
    fetch('cart_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=add&product_id=' + productId + '&quantity=' + quantity + '&size=' + size + '&color=' + color
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Added to cart!');
            location.reload();
        } else {
            alert(data.message || 'Error adding to cart');
        }
    });
}
</script>
</body>
</html>