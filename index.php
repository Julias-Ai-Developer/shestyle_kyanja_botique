
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

<!-- Hero Carousel Section -->
<section class="hero-carousel-wrapper">
    <div class="hero-carousel-container" id="heroCarousel">
        <div class="hero-slide" style="background-image: url('assets/images/Vintage shirts.jpg');">
            <div class="hero-banner-overlay">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-lg-6">
                            <div class="hero-content">
                                <h1 class="hero-title">Welcome to SheStyle Boutique</h1>
                                <p class="hero-description">
                                    Discover premium fashion clothing and accessories that elevate your style. 
                                    Our curated collection features the latest trends in women's fashion, 
                                    designed to make you look and feel confident.
                                </p>
                                <a href="shop.php" class="btn-hero-cta">View Products</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="hero-slide" style="background-image: url('assets/images/download (2).jpg');">
            <div class="hero-banner-overlay">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-lg-6">
                            <div class="hero-content">
                                <h1 class="hero-title">Welcome to SheStyle Boutique</h1>
                                <p class="hero-description">
                                    Discover premium fashion clothing and accessories that elevate your style. 
                                    Our curated collection features the latest trends in women's fashion, 
                                    designed to make you look and feel confident.
                                </p>
                                <a href="shop.php" class="btn-hero-cta">View Products</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="hero-slide" style="background-image: url('assets/images/download (5).jpg');">
            <div class="hero-banner-overlay">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-lg-6">
                            <div class="hero-content">
                                <h1 class="hero-title">Welcome to SheStyle Boutique</h1>
                                <p class="hero-description">
                                    Discover premium fashion clothing and accessories that elevate your style. 
                                    Our curated collection features the latest trends in women's fashion, 
                                    designed to make you look and feel confident.
                                </p>
                                <a href="shop.php" class="btn-hero-cta">View Products</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="hero-slide" style="background-image: url('assets/images/Unisex vintage shirts 😍 check us out on 📌IG_ glennysvintagewears.jpg');">
            <div class="hero-banner-overlay">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-lg-6">
                            <div class="hero-content">
                                <h1 class="hero-title">Welcome to SheStyle Boutique</h1>
                                <p class="hero-description">
                                    Discover premium fashion clothing and accessories that elevate your style. 
                                    Our curated collection features the latest trends in women's fashion, 
                                    designed to make you look and feel confident.
                                </p>
                                <a href="shop.php" class="btn-hero-cta">View Products</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Floating WhatsApp Button -->
<div class="whatsapp-float-container">
    <a href="https://wa.me/256700000000" target="_blank" class="whatsapp-float" aria-label="Contact us on WhatsApp">
        <span class="whatsapp-text">Need Help?</span>
        <div class="whatsapp-icon-wrapper">
            <i class="fab fa-whatsapp"></i>
            <span class="whatsapp-badge">1</span>
        </div>
    </a>
</div>

<style>
.hero-carousel-wrapper {
    position: relative;
    overflow: hidden;
}

.hero-carousel-container {
    display: flex;
    transition: transform 1s ease-in-out;
}

.hero-slide {
    min-width: 100%;
    min-height: 500px;
    background-size: 50% 100%;
    background-position: right center;
    background-repeat: no-repeat;
    background-color: #228B22;
    display: flex;
    align-items: center;
    position: relative;
}

.hero-banner-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(to right, #228B22 0%, #228B22 30%, rgba(34, 139, 34, 0.8) 45%, rgba(34, 139, 34, 0.4) 60%, rgba(34, 139, 34, 0.1) 75%, transparent 90%);
    display: flex;
    align-items: center;
}

.hero-content {
    position: relative;
    z-index: 2;
    padding: 40px 0;
}

.hero-title {
    font-family: 'Dancing Script', cursive;
    font-size: 4rem;
    font-weight: 700;
    color: white;
    margin-bottom: 20px;
    line-height: 1.3;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.hero-description {
    font-size: 1.1rem;
    color: white;
    margin-bottom: 30px;
    line-height: 1.8;
    max-width: 500px;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
}

.btn-hero-cta {
    display: inline-block;
    background: white;
    color: var(--primary-orange);
    padding: 15px 40px;
    border-radius: 50px;
    font-weight: 700;
    font-size: 1.1rem;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.btn-hero-cta:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.3);
    color: var(--burnt-orange);
}

/* WhatsApp Floating Button */
.whatsapp-float-container {
    position: fixed;
    bottom: 30px;
    right: 30px;
    z-index: 1000;
}

.whatsapp-float {
    display: flex;
    align-items: center;
    gap: 12px;
    background-color: white;
    padding: 12px 20px;
    border-radius: 50px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    transition: all 0.3s ease;
    animation: pulse-whatsapp 2s infinite;
}

.whatsapp-text {
    color: #333;
    font-weight: 600;
    font-size: 0.95rem;
    white-space: nowrap;
}

.whatsapp-icon-wrapper {
    position: relative;
    width: 50px;
    height: 50px;
    background-color: #25D366;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.whatsapp-icon-wrapper i {
    color: white;
    font-size: 28px;
}

.whatsapp-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background-color: #ff0000;
    color: white;
    border-radius: 50%;
    width: 22px;
    height: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 700;
    border: 2px solid white;
}

.whatsapp-float:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
}

.whatsapp-float:hover .whatsapp-icon-wrapper {
    background-color: #128C7E;
}

@keyframes pulse-whatsapp {
    0%, 100% {
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }
    50% {
        box-shadow: 0 4px 25px rgba(37, 211, 102, 0.4);
    }
}

@media (max-width: 768px) {
    .whatsapp-float-container {
        bottom: 20px;
        right: 20px;
    }
    
    .whatsapp-float {
        padding: 10px 15px;
        gap: 10px;
    }
    
    .whatsapp-text {
        font-size: 0.85rem;
    }
    
    .whatsapp-icon-wrapper {
        width: 45px;
        height: 45px;
    }
    
    .whatsapp-icon-wrapper i {
        font-size: 24px;
    }
}
</style>


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
    
    // Hero Carousel Auto-Slide (Bouncing Back and Forth)
    const heroCarousel = document.getElementById('heroCarousel');
    if (heroCarousel) {
        let currentHeroSlide = 0;
        let direction = 1; // 1 for forward, -1 for backward
        const heroSlides = document.querySelectorAll('.hero-slide');
        const totalHeroSlides = heroSlides.length;
        
        function slideHeroCarousel() {
            currentHeroSlide += direction;
            
            // Reverse direction at the ends
            if (currentHeroSlide >= totalHeroSlides - 1) {
                direction = -1;
            } else if (currentHeroSlide <= 0) {
                direction = 1;
            }
            
            const offset = -currentHeroSlide * 100;
            heroCarousel.style.transform = `translateX(${offset}%)`;
        }
        
        // Auto-slide every 5 seconds
        setInterval(slideHeroCarousel, 5000);
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




