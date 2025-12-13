
<?php
$pageTitle = 'Home - Boutique Fashion Store';
include 'includes/header.php';

// Get banners for carousel
$bannersQuery = "
    SELECT * FROM banners 
    WHERE is_active = 1 
    ORDER BY display_order ASC
    LIMIT 5
";
$bannersResult = mysqli_query($conn, $bannersQuery);
$banners = [];
if ($bannersResult) {
    while ($row = mysqli_fetch_assoc($bannersResult)) {
        $banners[] = $row;
    }
}

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

<!-- Image Carousel Hero Section -->
<div id="heroCarousel" class="carousel slide hero-carousel" data-bs-ride="carousel" data-bs-interval="5000">
    <div class="carousel-indicators">
        <?php foreach ($banners as $idx => $banner): ?>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="<?php echo $idx; ?>" 
                <?php echo $idx === 0 ? 'class="active" aria-current="true"' : ''; ?>></button>
        <?php endforeach; ?>
    </div>
    
    <div class="carousel-inner">
        <?php if (!empty($banners)): ?>
            <?php foreach ($banners as $idx => $banner): 
                // Check if image is local or external URL
                $imgSrc = (strpos($banner['image'], 'http') === 0) ? $banner['image'] : $banner['image'];
            ?>
            <div class="carousel-item <?php echo $idx === 0 ? 'active' : ''; ?>">
                <img src="<?php echo htmlspecialchars($imgSrc); ?>" class="d-block w-100 carousel-image" alt="<?php echo htmlspecialchars($banner['title']); ?>" style="height: 500px; object-fit: cover;">
                <div class="carousel-caption d-none d-md-block hero-overlay">
                    <h1 class="display-3 fw-bold mb-3"><?php echo htmlspecialchars($banner['title']); ?></h1>
                    <?php if (!empty($banner['subtitle'])): ?>
                    <p class="fs-4 mb-4"><?php echo htmlspecialchars($banner['subtitle']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($banner['link']) && !empty($banner['button_text'])): ?>
                    <a href="<?php echo htmlspecialchars($banner['link']); ?>" class="btn btn-primary-custom btn-lg">
                        <?php echo htmlspecialchars($banner['button_text']); ?>
                    </a>
                    <?php else: ?>
                    <a href="shop.php" class="btn btn-primary-custom btn-lg">Shop Now</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Fallback carousel if no banners exist -->
            <div class="carousel-item active" style="background: linear-gradient(135deg, var(--primary-orange), var(--burnt-orange)); height: 500px; display: flex; align-items: center; justify-content: center;">
                <div class="hero-section hero-overlay text-center">
                    <h1 class="display-3 fw-bold">Welcome to Our Boutique</h1>
                    <p class="fs-4 mb-4">Discover the latest fashion trends</p>
                    <a href="shop.php" class="btn btn-primary-custom btn-lg">Shop Now</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
        <span class="visually-hidden">Next</span>
    </button>
</div>

<!-- Recent Products Slider -->
<?php if (!empty($recentProducts)): ?>
<section class="container my-5">
    <h2 class="text-center mb-5 text-orange fw-bold">🆕 Latest Arrivals</h2>
    
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
                                    $<?php echo number_format($product['discount_price'] ?: $product['price'], 2); ?>
                                </span>
                                <?php if ($product['discount_price']): ?>
                                <span class="text-muted text-decoration-line-through small ms-2">
                                    $<?php echo number_format($product['price'], 2); ?>
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
        
        <button class="carousel-control prev" onclick="scrollSlider(-1)">❮</button>
        <button class="carousel-control next" onclick="scrollSlider(1)">❯</button>
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
});

// Carousel slider functionality
let currentSlide = 0;

function scrollSlider(direction) {
    const slider = document.getElementById('productsSlider');
    const items = document.querySelectorAll('.carousel-item');
    const itemWidth = 280; // Width of carousel item + gap
    
    currentSlide += direction;
    
    // Wrap around
    if (currentSlide < 0) {
        currentSlide = items.length - 4;
    }
    if (currentSlide > items.length - 4) {
        currentSlide = 0;
    }
    
    slider.style.transform = `translateX(-${currentSlide * itemWidth}px)`;
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




