
<?php
$pageTitle = 'Home - Boutique Fashion Store';
include 'includes/header.php';

// Get recent products in stock (ordered by creation date)
$recentQuery = "
    SELECT p.*, pi.image_url, c.name as category_name 
    FROM products p 
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.is_active = 1 AND p.stock_quantity > 0
    ORDER BY p.id DESC
    LIMIT 8
";
$recentResult = mysqli_query($conn, $recentQuery);
$recentProducts = [];
if ($recentResult) {
    while ($row = mysqli_fetch_assoc($recentResult)) {
        $recentProducts[] = $row;
    }
}

$query = "
    SELECT p.*, pi.image_url, c.name as category_name 
    FROM products p 
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.is_featured = 1 AND p.is_active = 1 
    LIMIT 6
";
$result = mysqli_query($conn, $query);
$featuredProducts = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $featuredProducts[] = $row;
    }
}

$categoriesQuery = "SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order";
$categoriesResult = mysqli_query($conn, $categoriesQuery);
$categories = [];
if ($categoriesResult) {
    while ($row = mysqli_fetch_assoc($categoriesResult)) {
        $categories[] = $row;
    }
}
?>

<!-- Promotional Modal - Shows on first visit -->
<div class="modal fade" id="promoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-orange text-white border-0">
                <h5 class="modal-title fw-bold">🎉 Limited Time Offer!</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-5">
                <h2 class="display-4 fw-bold text-orange mb-3">50% OFF</h2>
                <p class="fs-5 mb-4">Get amazing discounts on selected items today!</p>
                <div class="discount-badge mb-4">
                    <span class="badge bg-orange p-3 fs-6">SAVE TODAY</span>
                </div>
                <p class="text-muted mb-4">Limited stocks available. Shop now before items run out!</p>
                <a href="shop.php" class="btn btn-primary-custom btn-lg" data-bs-dismiss="modal">
                    Shop Now & Save
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Static Hero Section -->
<div class="hero-section-static" style="background: linear-gradient(135deg, #FF6B35 0%, #D9534F 100%); min-height: 500px; display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden;">
    <!-- Decorative Background Elements -->
    <div style="position: absolute; top: -50px; right: -50px; width: 300px; height: 300px; background: rgba(255,255,255,0.1); border-radius: 50%; z-index: 1;"></div>
    <div style="position: absolute; bottom: -30px; left: -30px; width: 200px; height: 200px; background: rgba(255,255,255,0.1); border-radius: 50%; z-index: 1;"></div>
    
    <div class="container position-relative z-2" style="z-index: 2;">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-3 fw-bold text-white mb-4" style="animation: fadeInUp 1s ease-out;">
                    Welcome to Our Boutique
                </h1>
                <p class="fs-5 text-white mb-5" style="opacity: 0.95; animation: fadeInUp 1s ease-out 0.2s backwards;">
                    Discover the latest fashion trends and elevate your style with our exclusive collection of premium clothing and accessories.
                </p>
                <div class="d-flex gap-3" style="animation: fadeInUp 1s ease-out 0.4s backwards;">
                    <a href="shop.php" class="btn btn-light btn-lg fw-bold" style="color: #FF6B35; padding: 12px 40px; border-radius: 50px;">
                        Shop Now
                    </a>
                    <a href="about.php" class="btn btn-outline-light btn-lg fw-bold" style="padding: 12px 40px; border-radius: 50px;">
                        Learn More
                    </a>
                </div>
            </div>
            <div class="col-lg-4 d-none d-lg-block">
                <div style="position: relative; perspective: 1000px;">
                    <div style="background: rgba(255,255,255,0.1); padding: 40px; border-radius: 20px; backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2);">
                        <div style="text-align: center; color: white;">
                            <div style="font-size: 80px; margin-bottom: 20px;">👗</div>
                            <h3 class="fw-bold mb-3">Premium Collection</h3>
                            <p style="font-size: 14px; opacity: 0.9;">Curated styles for every occasion</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .hero-section-static .btn-light:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        .hero-section-static .btn-outline-light:hover {
            transform: translateY(-2px);
            background: rgba(255,255,255,0.1);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
    </style>
</div>

<!-- Recent Products Auto-Sliding Carousel -->
<?php if (!empty($recentProducts)): ?>
<section class="container my-5">
    <div class="position-relative">
        <div class="carousel-container">
            <div class="carousel-slider" id="productsSlider">
                <?php foreach ($recentProducts as $product): ?>
                <div class="carousel-item">
                    <div class="product-card card h-100">
                        <div class="position-relative">
                            <img src="<?php echo $product['image_url'] ?: 'placeholder.jpg'; ?>" 
                                 class="card-img-top product-image" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <?php if ($product['discount_price']): ?>
                            <span class="badge bg-danger position-absolute top-0 end-0 m-2">
                                SALE
                            </span>
                            <?php endif; ?>
                            <?php if ($product['stock_quantity'] < 5): ?>
                            <span class="badge bg-warning position-absolute top-0 start-0 m-2">
                                Low Stock
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <h6 class="card-title"><?php echo htmlspecialchars(substr($product['name'], 0, 30)); ?></h6>
                            <p class="text-muted small"><?php echo htmlspecialchars($product['category_name']); ?></p>
                            <div class="mb-3">
                                <span class="fs-5 fw-bold text-orange">
                                    Ugx<?php echo number_format($product['discount_price'] ?: $product['price'], 2); ?>
                                </span>
                                <?php if ($product['discount_price']): ?>
                                <span class="text-muted text-decoration-line-through small ms-2">
                                    Ugx<?php echo number_format($product['price'], 2); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <button class="btn btn-add-cart btn-sm w-100" onclick="addToCart(<?php echo $product['id']; ?>)">
                                Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="container my-5">
    <h2 class="text-center mb-5 text-orange fw-bold">Shop by Category</h2>
    <div class="row g-4">
        <?php foreach ($categories as $cat): ?>
        <div class="col-md-3 col-sm-6">
            <a href="shop.php?category=<?php echo $cat['slug']; ?>" class="text-decoration-none">
                <div class="card product-card">
                    <img src="https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=400" class="card-img-top" alt="<?php echo htmlspecialchars($cat['name']); ?>">
                    <div class="card-body text-center">
                        <h5><?php echo htmlspecialchars($cat['name']); ?></h5>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="container my-5">
    <h2 class="text-center mb-5 text-orange fw-bold">Featured Products</h2>
    <div class="row">
        <?php foreach ($featuredProducts as $product): ?>
        <div class="col-md-4 mb-4">
            <div class="product-card card">
                <img src="<?php echo $product['image_url'] ?: 'placeholder.jpg'; ?>" 
                     class="card-img-top product-image" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>">
                <div class="card-body">
                    <h5><?php echo htmlspecialchars($product['name']); ?></h5>
                    <p class="text-muted"><?php echo htmlspecialchars($product['category_name']); ?></p>
                    <div class="mb-3">
                        <span class="fs-4 fw-bold text-orange">
                            Ugx<?php echo number_format($product['discount_price'] ?: $product['price'], 2); ?>
                        </span>
                        <?php if ($product['discount_price']): ?>
                        <span class="text-muted text-decoration-line-through ms-2">
                            Ugx<?php echo number_format($product['price'], 2); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <button class="btn btn-add-cart" onclick="addToCart(<?php echo $product['id']; ?>)">
                        Add to Cart
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

<script>
// Show promotional modal on first visit
document.addEventListener('DOMContentLoaded', function() {
    if (!localStorage.getItem('promoShown')) {
        const modal = new bootstrap.Modal(document.getElementById('promoModal'));
        modal.show();
        localStorage.setItem('promoShown', 'true');
    }
    
    // Auto-scroll carousel every 5 seconds
    const slider = document.getElementById('productsSlider');
    if (slider) {
        setInterval(() => {
            autoScrollSlider();
        }, 5000);
    }
});

// Auto-scroll carousel
let currentSlide = 0;

function autoScrollSlider() {
    const slider = document.getElementById('productsSlider');
    if (!slider) return;
    
    const items = document.querySelectorAll('.carousel-item');
    const itemWidth = 280; // Width of carousel item + gap
    
    currentSlide++;
    
    // Wrap around
    if (currentSlide > items.length - 4) {
        currentSlide = 0;
    }
    
    slider.style.transform = `translateX(-${currentSlide * itemWidth}px)`;
    slider.style.transition = 'transform 0.5s ease-in-out';
}

function addToCart(productId) {
    fetch('cart_handler.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=add&product_id=' + productId
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            alert('Product added to cart!');
            location.reload();
        }
    });
}
</script>




