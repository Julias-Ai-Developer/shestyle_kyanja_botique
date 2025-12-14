<?php
$pageTitle = 'Shop - Boutique Fashion Store';
require_once 'config/database.php';

$category = sanitizeInput($_GET['category'] ?? '');
$search = sanitizeInput($_GET['search'] ?? '');
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Build query
$query = "SELECT p.*, pi.image_url, c.name as category_name FROM products p 
          LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
          LEFT JOIN categories c ON p.category_id = c.id WHERE p.is_active = 1";

if ($category) {
    $category_escaped = mysqli_real_escape_string($conn, $category);
    $query .= " AND c.slug = '$category_escaped'";
}

if ($search) {
    $search_escaped = mysqli_real_escape_string($conn, $search);
    $query .= " AND (p.name LIKE '%$search_escaped%' OR p.description LIKE '%$search_escaped%')";
}

// Build count query
$countQuery = str_replace('SELECT p.*', 'SELECT COUNT(*) as cnt', explode('ORDER BY', $query)[0]);
$countResult = mysqli_query($conn, $countQuery);
if (!$countResult) {
    die('Database error: ' . mysqli_error($conn));
}
$countRow = mysqli_fetch_assoc($countResult);
$total = $countRow['cnt'];
$totalPages = ceil($total / $perPage);

// Get products with LIMIT
$query .= " ORDER BY p.created_at DESC LIMIT " . intval($perPage) . " OFFSET " . intval($offset);
$result = mysqli_query($conn, $query);
if (!$result) {
    die('Database error: ' . mysqli_error($conn));
}
$products = [];
while ($row = mysqli_fetch_assoc($result)) {
    $products[] = $row;
}

// Get categories
$categoriesQuery = "SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order";
$categoriesResult = mysqli_query($conn, $categoriesQuery);
if (!$categoriesResult) {
    die('Database error: ' . mysqli_error($conn));
}
$categories = [];
while ($row = mysqli_fetch_assoc($categoriesResult)) {
    $categories[] = $row;
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
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <h5 class="mb-4">Filter Products</h5>
            
            <h6 class="text-orange">Categories</h6>
            <div class="list-group mb-4">
                <a href="shop.php" class="list-group-item list-group-item-action <?php echo !$category ? 'active' : ''; ?>">All Products</a>
                <?php foreach ($categories as $cat): ?>
                <a href="shop.php?category=<?php echo htmlspecialchars($cat['slug']); ?>" 
                   class="list-group-item list-group-item-action <?php echo $category === $cat['slug'] ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($cat['name']); ?>
                </a>
                <?php endforeach; ?>
            </div>
            
            <form method="GET" class="mb-4">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-primary-custom" type="submit" style="padding: 0.75rem 1.5rem;">🔍</button>
                </div>
            </form>
        </div>
        
        <!-- Products -->
        <div class="col-md-9">
            <h3 class="mb-4 text-orange">
                <?php 
                if ($category) {
                    echo htmlspecialchars(ucfirst(str_replace('-', ' ', $category)));
                } elseif ($search) {
                    echo 'Search: ' . htmlspecialchars($search);
                } else {
                    echo 'All Products';
                }
                ?>
            </h3>
            
            <?php if (empty($products)): ?>
            <div class="alert alert-info">No products found.</div>
            <?php else: ?>
            
            <div class="row g-4 mb-4">
                <?php foreach ($products as $product): ?>
                <div class="col-md-4 col-sm-6">
                    <div class="card product-card h-100">
                        <a href="product.php?id=<?php echo $product['id']; ?>" class="text-decoration-none text-dark">
                            <img src="<?php echo htmlspecialchars($product['image_url'] ?? 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400'); ?>" 
                                 class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <div class="card-body">
                                <h6 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h6>
                                <p class="text-muted small"><?php echo htmlspecialchars($product['category_name'] ?? ''); ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <?php if ($product['discount_price']): ?>
                                        <span class="text-orange fw-bold">Ugx<?php echo number_format($product['discount_price'], 2); ?></span>
                                        <del class="text-muted">Ugx<?php echo number_format($product['price'], 2); ?></del>
                                        <?php else: ?>
                                        <span class="text-orange fw-bold">Ugx<?php echo number_format($product['price'], 2); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav>
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="shop.php?page=<?php echo $page - 1; ?><?php echo $category ? '&category=' . htmlspecialchars($category) : ''; ?><?php echo $search ? '&search=' . htmlspecialchars($search) : ''; ?>">Previous</a>
                    </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="shop.php?page=<?php echo $i; ?><?php echo $category ? '&category=' . htmlspecialchars($category) : ''; ?><?php echo $search ? '&search=' . htmlspecialchars($search) : ''; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="shop.php?page=<?php echo $page + 1; ?><?php echo $category ? '&category=' . htmlspecialchars($category) : ''; ?><?php echo $search ? '&search=' . htmlspecialchars($search) : ''; ?>">Next</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
            
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>