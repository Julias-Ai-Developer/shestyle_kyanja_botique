<?php
// Get the base path
$basePath = dirname(dirname(__FILE__));
require_once $basePath . '/config/database.php';
$cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>She Styles Kyanja | Best Women's Fashion Boutique in Kampala, Uganda</title>

    <meta name="description" content="She Styles Kyanja: Discover the **best boutique in Kyanja, Kampala, Uganda**. Shop premium, trendy women's fashion, elegant dresses, and chic accessories. Your destination for high-end style in Uganda. New arrivals weekly!">

    <meta name="keywords" content="She Styles Kyanja, best boutique in Kyanja, Kampala fashion boutique, women's fashion Uganda, ladies clothing Kampala, trendy outfits Uganda, designer dresses Kampala, premium clothing Uganda, Kyanja fashion store">

    <meta name="author" content="She Styles Kyanja">

    <meta property="og:title" content="She Styles Kyanja | Premier Women's Fashion Boutique in Uganda">
    <meta property="og:description" content="Discover the best boutique in Kyanja, Kampala, Uganda. Shop premium, trendy women's fashion and accessories.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://www.shestyleskyanja.com/">
    <meta property="og:image" content="https://www.yourboutiquewebsite.com/logo-shestyles-kyanja.jpg">
    <meta name="twitter:card" content="summary_large_image">
    <title><?php echo $pageTitle ?? 'SheStyle Boutique - Premium Fashion Store in Kyanja'; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Playfair+Display:wght@600;700&family=Dancing+Script:wght@700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <!-- Top Info Bar -->
    <div class="top-info-bar">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="info-items">
                        <span class="info-item">
                            <i class="far fa-clock"></i> Mon-Sat: 09:00 - 20:00
                        </span>
                        <span class="info-item">
                            <i class="fas fa-map-marker-alt"></i> Kyanja, Kampala
                        </span>
                        <span class="info-item">
                            <i class="fas fa-phone"></i> +256 777 043887
                        </span>
                        <span class="info-item d-none d-lg-inline">
                            <i class="fas fa-envelope"></i> ask@shestyle.com
                        </span>
                    </div>
                </div>
                <div class="col-lg-4 text-end">
                    <div class="social-icons">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top main-navbar">
        <div class="container">
            <a class="navbar-brand-new" href="index.php">
                <span class="brand-icon">🔶</span>
                <span class="brand-text">SheStyle <span class="brand-sub">Boutique</span></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link-new active" href="index.php">HOME</a></li>
                    <li class="nav-item"><a class="nav-link-new" href="shop.php">SHOP</a></li>
                    <li class="nav-item"><a class="nav-link-new" href="about.php">ABOUT US</a></li>
                    <li class="nav-item"><a class="nav-link-new" href="contact.php">CONTACT US</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link-new dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                MY ACCOUNT
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link-new" href="login.php">LOGIN</a></li>
                    <?php endif; ?>
                    <li class="nav-item ms-3">
                        <a href="cart.php" class="btn-cart-new">
                            <i class="fas fa-shopping-cart"></i>
                            <?php if ($cartCount > 0): ?>
                                <span class="cart-badge"><?php echo $cartCount; ?></span>
                            <?php else: ?>
                                <span class="cart-badge">0</span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>