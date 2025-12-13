<?php
$pageTitle = 'About Us - Boutique Fashion Store';
require_once 'config/database.php';
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
    <div class="row align-items-center mb-5">
        <div class="col-lg-6 mb-4">
            <img src="./assets/images/SS.png" class="img-fluid " alt="About Us" style="border-radius:2%"  >
        </div>
        <div class="col-lg-6">
            <h1 class="text-orange mb-4">About Boutique</h1>
            <p class="fs-5 mb-3">
                Welcome to Boutique, your ultimate destination for premium fashion and style. Since our founding, we've been dedicated to bringing you the latest trends, timeless classics, and exclusive pieces that make you feel confident and beautiful.
            </p>
            <p class="fs-5 mb-3">
                Our carefully curated collection features high-quality clothing, accessories, and footwear from both emerging designers and established brands. We believe that fashion should be accessible, inclusive, and sustainable.
            </p>
            <p class="fs-5">
                Every item in our store is handpicked to ensure it meets our standards of quality, style, and value. We're committed to providing you with exceptional customer service and a seamless shopping experience.
            </p>
        </div>
    </div>

    <hr class="my-5">

    <h2 class="text-center text-orange mb-5">Why Choose Us?</h2>
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card h-100 text-center">
                <div class="card-body">
                    <h3 class="text-orange mb-3">🎯 Curated Selection</h3>
                    <p>Carefully selected items that combine quality with style</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 text-center">
                <div class="card-body">
                    <h3 class="text-orange mb-3">💎 Premium Quality</h3>
                    <p>High-quality materials and excellent craftsmanship</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 text-center">
                <div class="card-body">
                    <h3 class="text-orange mb-3">📋 Easy Reservation</h3>
                    <p>Reserve items with flexible payment options - pay 50%, 70%, or 100% upfront</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card h-100 text-center">
                <div class="card-body">
                    <h3 class="text-orange mb-3">💬 Expert Support</h3>
                    <p>Dedicated customer service ready to help</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 text-center">
                <div class="card-body">
                    <h3 class="text-orange mb-3">🔄 Easy Returns</h3>
                    <p>Hassle-free returns within 30 days</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 text-center">
                <div class="card-body">
                    <h3 class="text-orange mb-3">🌍 Sustainable</h3>
                    <p>Committed to eco-friendly fashion</p>
                </div>
            </div>
        </div>
    </div>

    <hr class="my-5">

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <h2 class="text-center text-orange mb-4">Our Story</h2>
            <p class="fs-5 mb-3">
                Founded in 2020, Boutique started as a small boutique with a big dream: to revolutionize the way people shop for fashion. We believed that every person deserves access to high-quality, stylish clothing without compromising on affordability or sustainability.
            </p>
            <p class="fs-5 mb-3">
                What began in a small storefront has grown into a thriving online community of fashion enthusiasts from around the world. Our team works tirelessly to bring you the best in contemporary fashion, from casual wear to formal attire.
            </p>
            <p class="fs-5">
                Today, we're proud to serve thousands of satisfied customers who trust us for their fashion needs. We continue to evolve, innovate, and stay committed to our core values of quality, style, and customer satisfaction.
            </p>
        </div>
    </div>

    <hr class="my-5">

    <div class="text-center">
        <h3 class="text-orange mb-4">Ready to explore our collection?</h3>
        <a href="shop.php" class="btn btn-primary-custom btn-lg">Shop Now</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
