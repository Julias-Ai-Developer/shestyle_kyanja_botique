<?php
require_once '../config/database.php';

if (!isAdmin()) {
    header('Location: login.php');
    exit;
}

// Restrict access to non-admin users
if ($_SESSION['admin_role'] === 'worker') {
    header('Location: worker-dashboard.php');
    exit;
}

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Handle AJAX category addition separately
        if ($_POST['action'] === 'add_category') {
            header('Content-Type: application/json');
            
            if (empty($_POST['category_name'])) {
                echo json_encode(['success' => false, 'message' => 'Category name is required!']);
                exit;
            }
            
            $category_name = mysqli_real_escape_string($conn, trim($_POST['category_name']));
            $category_slug = mysqli_real_escape_string($conn, strtolower(str_replace(' ', '-', trim($_POST['category_name']))));
            $category_description = mysqli_real_escape_string($conn, trim($_POST['category_description'] ?? ''));
            
            // Check if category already exists
            $checkQuery = "SELECT id FROM categories WHERE name = '$category_name'";
            $checkResult = mysqli_query($conn, $checkQuery);
            if (mysqli_num_rows($checkResult) > 0) {
                echo json_encode(['success' => false, 'message' => 'Category already exists!']);
                exit;
            }
            
            $insertCatQuery = "INSERT INTO categories (name, slug, description, is_active) 
                              VALUES ('$category_name', '$category_slug', '$category_description', 1)";
            
            if (mysqli_query($conn, $insertCatQuery)) {
                $new_category_id = mysqli_insert_id($conn);
                echo json_encode([
                    'success' => true, 
                    'message' => 'Category added successfully!',
                    'category' => [
                        'id' => $new_category_id,
                        'name' => $category_name
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error adding category: ' . mysqli_error($conn)]);
            }
            exit;
        }
        
        // Handle other actions
        switch ($_POST['action']) {
            case 'add_product':
                // Validate required fields
                if (empty($_POST['product_name']) || empty($_POST['category_id']) || empty($_POST['price'])) {
                    $message = "Please fill in all required fields!";
                    $messageType = "danger";
                    break;
                }
                
                $name = mysqli_real_escape_string($conn, trim($_POST['product_name']));
                $slug = mysqli_real_escape_string($conn, strtolower(str_replace(' ', '-', trim($_POST['product_name']))));
                $category_id = intval($_POST['category_id']);
                $description = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));
                $price = floatval($_POST['price']);
                $discount_price = !empty($_POST['discount_price']) ? floatval($_POST['discount_price']) : null;
                $stock_quantity = intval($_POST['stock_quantity'] ?? 0);
                $sizes = mysqli_real_escape_string($conn, trim($_POST['sizes'] ?? ''));
                $colors = mysqli_real_escape_string($conn, trim($_POST['colors'] ?? ''));
                $is_featured = isset($_POST['is_featured']) ? 1 : 0;
                $is_active = intval($_POST['is_active'] ?? 1);
                
                // Check if category exists
                $checkCat = mysqli_query($conn, "SELECT id FROM categories WHERE id = '$category_id'");
                if (mysqli_num_rows($checkCat) == 0) {
                    $message = "Invalid category selected!";
                    $messageType = "danger";
                    break;
                }
                
                $insertQuery = "INSERT INTO products (category_id, name, slug, description, price, discount_price, stock_quantity, sizes, colors, is_featured, is_active) 
                               VALUES ('$category_id', '$name', '$slug', '$description', '$price', " . ($discount_price ? "'$discount_price'" : "NULL") . ", '$stock_quantity', '$sizes', '$colors', '$is_featured', '$is_active')";
                
                if (mysqli_query($conn, $insertQuery)) {
                    $message = "Product added successfully!";
                    $messageType = "success";
                } else {
                    $message = "Error adding product: " . mysqli_error($conn);
                    $messageType = "danger";
                }
                break;
                
            case 'edit_product':
                // Validate required fields
                if (empty($_POST['product_id']) || empty($_POST['product_name']) || empty($_POST['category_id']) || empty($_POST['price'])) {
                    $message = "Please fill in all required fields!";
                    $messageType = "danger";
                    break;
                }
                
                $id = intval($_POST['product_id']);
                $name = mysqli_real_escape_string($conn, trim($_POST['product_name']));
                $slug = mysqli_real_escape_string($conn, strtolower(str_replace(' ', '-', trim($_POST['product_name']))));
                $category_id = intval($_POST['category_id']);
                $description = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));
                $price = floatval($_POST['price']);
                $discount_price = !empty($_POST['discount_price']) ? floatval($_POST['discount_price']) : null;
                $stock_quantity = intval($_POST['stock_quantity'] ?? 0);
                $sizes = mysqli_real_escape_string($conn, trim($_POST['sizes'] ?? ''));
                $colors = mysqli_real_escape_string($conn, trim($_POST['colors'] ?? ''));
                $is_featured = isset($_POST['is_featured']) ? 1 : 0;
                $is_active = intval($_POST['is_active'] ?? 1);
                
                // Check if category exists
                $checkCat = mysqli_query($conn, "SELECT id FROM categories WHERE id = '$category_id'");
                if (mysqli_num_rows($checkCat) == 0) {
                    $message = "Invalid category selected!";
                    $messageType = "danger";
                    break;
                }
                
                $updateQuery = "UPDATE products SET 
                               category_id = '$category_id',
                               name = '$name',
                               slug = '$slug',
                               description = '$description',
                               price = '$price',
                               discount_price = " . ($discount_price ? "'$discount_price'" : "NULL") . ",
                               stock_quantity = '$stock_quantity',
                               sizes = '$sizes',
                               colors = '$colors',
                               is_featured = '$is_featured',
                               is_active = '$is_active'
                               WHERE id = '$id'";
                
                if (mysqli_query($conn, $updateQuery)) {
                    $message = "Product updated successfully!";
                    $messageType = "success";
                } else {
                    $message = "Error updating product: " . mysqli_error($conn);
                    $messageType = "danger";
                }
                break;
                
            case 'delete_product':
                if (empty($_POST['product_id'])) {
                    $message = "Invalid product ID!";
                    $messageType = "danger";
                    break;
                }
                
                $id = intval($_POST['product_id']);
                $deleteQuery = "DELETE FROM products WHERE id = '$id'";
                
                if (mysqli_query($conn, $deleteQuery)) {
                    $message = "Product deleted successfully!";
                    $messageType = "success";
                } else {
                    $message = "Error deleting product: " . mysqli_error($conn);
                    $messageType = "danger";
                }
                break;
                
            case 'add_category':
                header('Content-Type: application/json');
                
                if (empty($_POST['category_name'])) {
                    echo json_encode(['success' => false, 'message' => 'Category name is required!']);
                    exit;
                }
                
                $category_name = mysqli_real_escape_string($conn, trim($_POST['category_name']));
                $category_slug = mysqli_real_escape_string($conn, strtolower(str_replace(' ', '-', trim($_POST['category_name']))));
                $category_description = mysqli_real_escape_string($conn, trim($_POST['category_description'] ?? ''));
                
                // Check if category already exists
                $checkQuery = "SELECT id FROM categories WHERE name = '$category_name'";
                $checkResult = mysqli_query($conn, $checkQuery);
                if (mysqli_num_rows($checkResult) > 0) {
                    echo json_encode(['success' => false, 'message' => 'Category already exists!']);
                    exit;
                }
                
                $insertCatQuery = "INSERT INTO categories (name, slug, description, is_active) 
                                  VALUES ('$category_name', '$category_slug', '$category_description', 1)";
                
                if (mysqli_query($conn, $insertCatQuery)) {
                    $new_category_id = mysqli_insert_id($conn);
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Category added successfully!',
                        'category' => [
                            'id' => $new_category_id,
                            'name' => $category_name
                        ]
                    ]);
                    exit;
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error adding category: ' . mysqli_error($conn)]);
                    exit;
                }
                break;
        }
    }
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get products
$productsQuery = "
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.created_at DESC 
    LIMIT " . intval($perPage) . " OFFSET " . intval($offset);
$productsResult = mysqli_query($conn, $productsQuery);
if (!$productsResult) {
    die('Database error: ' . mysqli_error($conn));
}
$products = [];
while ($row = mysqli_fetch_assoc($productsResult)) {
    $products[] = $row;
}

// Get total
$countQuery = "SELECT COUNT(*) as cnt FROM products";
$countResult = mysqli_query($conn, $countQuery);
if (!$countResult) {
    die('Database error: ' . mysqli_error($conn));
}
$countRow = mysqli_fetch_assoc($countResult);
$total = $countRow['cnt'];
$totalPages = ceil($total / $perPage);

// Get categories
$categoriesQuery = "SELECT * FROM categories WHERE is_active = 1 ORDER BY name ASC";
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
    <title>Products - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .bg-orange { background-color: #FF6B35 !important; }
        .text-orange { color: #FF6B35 !important; }
        .btn-orange { background-color: #FF6B35; border-color: #FF6B35; color: white; }
        .btn-orange:hover { background-color: #e55a28; border-color: #e55a28; color: white; }
        .sidebar { min-height: 100vh; }
        .badge-orange { background-color: #FF6B35; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid">
        <span class="navbar-brand">🔶 Boutique Admin</span>
        <div class="d-flex align-items-center gap-3">
            <div class="text-light">
                <small class="d-block">Logged in as:</small>
                <strong><?php echo htmlspecialchars($_SESSION['admin_username']); ?></strong>
                <span class="badge badge-orange ms-2"><?php echo ucfirst(str_replace('_', ' ', $_SESSION['admin_role'])); ?></span>
            </div>
            <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 d-md-block bg-light sidebar p-0">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link active" href="products.php" style="color: #FF6B35; font-weight: 600; border-left: 4px solid #FF6B35; padding-left: 12px;">Products</a></li>
                    <li class="nav-item"><a class="nav-link" href="images.php">Images</a></li>
                    <li class="nav-item"><a class="nav-link" href="workers.php">Workers</a></li>
                    <li class="nav-item"><a class="nav-link" href="orders.php">Orders</a></li>
                    <li class="nav-item"><a class="nav-link" href="customers.php">Customers</a></li>
                    <li class="nav-item"><a class="nav-link" href="settings.php">Settings</a></li>
                </ul>
            </div>
        </nav>

        <main class="col-md-10 ms-sm-auto px-md-4">
            <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
                <h2><i class="fas fa-box"></i> Products Management</h2>
                <input type="text" id="searchProduct" class="form-control form-control-sm" style="width: 250px;" placeholder="🔍 Search products...">
            </div>

            <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Add Product Form -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-orange text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-plus-circle"></i> Add New Product</h5>
                    <button type="button" class="btn btn-light btn-sm" data-bs-toggle="collapse" data-bs-target="#addProductForm">
                        <i class="fas fa-chevron-down"></i> Toggle Form
                    </button>
                </div>
                <div class="collapse show" id="addProductForm">
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="add_product">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="product_name" class="form-label">Product Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="product_name" name="product_name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <select class="form-select category-select" id="category_id" name="category_id" required>
                                            <option value="">-- Select a category --</option>
                                            <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#addCategoryModal" title="Add New Category">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">Can't find your category? Click the + button to add new</small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3" placeholder="Enter product description..."></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="price" class="form-label">Price (UGX) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0" class="form-control" id="price" name="price" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="discount_price" class="form-label">Discount Price (UGX)</label>
                                    <input type="number" step="0.01" min="0" class="form-control" id="discount_price" name="discount_price" placeholder="Optional">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="stock_quantity" class="form-label">Stock Quantity <span class="text-danger">*</span></label>
                                    <input type="number" min="0" class="form-control" id="stock_quantity" name="stock_quantity" value="0" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="sizes" class="form-label">Sizes</label>
                                    <input type="text" class="form-control" id="sizes" name="sizes" placeholder="e.g., S, M, L, XL">
                                    <small class="text-muted">Comma-separated. Leave empty if not applicable</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="colors" class="form-label">Colors</label>
                                    <input type="text" class="form-control" id="colors" name="colors" placeholder="e.g., Red, Blue, Green">
                                    <small class="text-muted">Comma-separated. Leave empty if not applicable</small>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="is_active" class="form-label">Status <span class="text-danger">*</span></label>
                                    <select class="form-select" id="is_active" name="is_active" required>
                                        <option value="1" selected>Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label d-block">Featured Product</label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1">
                                        <label class="form-check-label" for="is_featured">Mark as featured on homepage</label>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex">
                                <button type="submit" class="btn btn-orange">
                                    <i class="fas fa-plus"></i> Add Product
                                </button>
                                <button type="reset" class="btn btn-secondary">
                                    <i class="fas fa-undo"></i> Reset Form
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Products Table -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="productsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Product Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Discount</th>
                                    <th>Stock</th>
                                    <th>Featured</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($products) > 0): ?>
                                    <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td><?php echo $product['id']; ?></td>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                                        <td>UGX <?php echo number_format($product['price'], 0); ?></td>
                                        <td>
                                            <?php if ($product['discount_price']): ?>
                                                <span class="badge bg-success">UGX <?php echo number_format($product['discount_price'], 0); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $product['stock_quantity'] > 10 ? 'success' : ($product['stock_quantity'] > 0 ? 'warning' : 'danger'); ?>">
                                                <?php echo $product['stock_quantity']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($product['is_featured']): ?>
                                                <i class="fas fa-star text-warning" title="Featured"></i>
                                            <?php else: ?>
                                                <i class="far fa-star text-muted" title="Not Featured"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $product['is_active'] ? 'success' : 'secondary'; ?>">
                                                <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick='editProduct(<?php echo json_encode($product); ?>)'>
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteProduct(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">
                                            <i class="fas fa-box-open fa-3x mb-3"></i>
                                            <p>No products found. Add your first product above!</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <?php if ($totalPages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                    <li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a></li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                    <li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-orange text-white">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Product</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="edit_product">
                    <input type="hidden" name="product_id" id="edit_product_id">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_product_name" class="form-label">Product Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_product_name" name="product_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_category_id" class="form-label">Category <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <select class="form-select category-select" id="edit_category_id" name="category_id" required>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#addCategoryModal" title="Add New Category">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="edit_price" class="form-label">Price (UGX) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" id="edit_price" name="price" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_discount_price" class="form-label">Discount Price (UGX)</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="edit_discount_price" name="discount_price">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_stock_quantity" class="form-label">Stock Quantity <span class="text-danger">*</span></label>
                            <input type="number" min="0" class="form-control" id="edit_stock_quantity" name="stock_quantity" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_sizes" class="form-label">Sizes</label>
                            <input type="text" class="form-control" id="edit_sizes" name="sizes">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_colors" class="form-label">Colors</label>
                            <input type="text" class="form-control" id="edit_colors" name="colors">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_is_active" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_is_active" name="is_active" required>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label d-block">Featured Product</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="edit_is_featured" name="is_featured" value="1">
                                <label class="form-check-label" for="edit_is_featured">Mark as featured</label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-orange">
                        <i class="fas fa-save"></i> Update Product
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this product?</p>
                <p class="fw-bold" id="deleteProductName"></p>
                <p class="text-danger"><small>This action cannot be undone!</small></p>
            </div>
            <div class="modal-footer">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="delete_product">
                    <input type="hidden" name="product_id" id="delete_product_id">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete Product
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-orange text-white">
                <h5 class="modal-title"><i class="fas fa-tag"></i> Add New Category</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="categoryAlert" style="display: none;"></div>
                
                <form id="addCategoryForm">
                    <input type="hidden" name="action" value="add_category">
                    
                    <div class="mb-3">
                        <label for="category_name" class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="category_name" name="category_name" required placeholder="e.g., Dresses, Shoes, Accessories">
                    </div>
                    
                    <div class="mb-3">
                        <label for="category_description" class="form-label">Description</label>
                        <textarea class="form-control" id="category_description" name="category_description" rows="3" placeholder="Optional description for this category"></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> The category will be automatically activated and available for selection.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="saveCategoryBtn" class="btn btn-orange">
                    <i class="fas fa-plus"></i> <span>Add Category</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize Select2 on category dropdowns
    $('.category-select').select2({
        theme: 'bootstrap-5',
        placeholder: '-- Select a category --',
        allowClear: true,
        width: '100%'
    });
    
    // Reinitialize Select2 when modal is shown
    $('#editModal').on('shown.bs.modal', function () {
        $('#edit_category_id').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#editModal'),
            width: '100%'
        });
    });
    
    // Handle Add Category Form Submission via AJAX
    $('#saveCategoryBtn').on('click', function(e) {
        e.preventDefault();
        
        const btn = $(this);
        const btnText = btn.find('span');
        const originalText = btnText.text();
        
        // Get form data
        const categoryName = $('#category_name').val().trim();
        const categoryDescription = $('#category_description').val().trim();
        
        // Validate
        if (!categoryName) {
            showCategoryAlert('Please enter a category name!', 'danger');
            return;
        }
        
        // Disable button and show loading
        btn.prop('disabled', true);
        btnText.html('Adding...');
        btn.prepend('<i class="fas fa-spinner fa-spin me-2"></i>');
        
        // Send AJAX request
        $.ajax({
            url: window.location.pathname,
            type: 'POST',
            data: {
                action: 'add_category',
                category_name: categoryName,
                category_description: categoryDescription
            },
            success: function(response) {
                console.log('Response:', response);
                
                // Handle response
                let data;
                if (typeof response === 'string') {
                    try {
                        data = JSON.parse(response);
                    } catch(e) {
                        console.error('Parse error:', e);
                        showCategoryAlert('Invalid server response', 'danger');
                        return;
                    }
                } else {
                    data = response;
                }
                
                if (data.success) {
                    // Create new option
                    const newOption = $('<option>', {
                        value: data.category.id,
                        text: data.category.name
                    });
                    
                    // Add to both dropdowns
                    $('#category_id').append(newOption.clone());
                    $('#edit_category_id').append(newOption.clone());
                    
                    // Select the newly added category
                    $('#category_id').val(data.category.id).trigger('change');
                    
                    // Show success message
                    showCategoryAlert(data.message, 'success');
                    
                    // Close modal after 1.5 seconds
                    setTimeout(function() {
                        $('#addCategoryModal').modal('hide');
                        $('#addCategoryForm')[0].reset();
                        $('#categoryAlert').hide();
                        showMainAlert('Category "' + data.category.name + '" added and selected!', 'success');
                    }, 1500);
                } else {
                    showCategoryAlert(data.message, 'danger');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                showCategoryAlert('Server error: ' + error, 'danger');
            },
            complete: function() {
                btn.prop('disabled', false);
                btn.find('.fa-spinner').remove();
                btnText.text(originalText);
            }
        });
    });
    
    // Reset modal when closed
    $('#addCategoryModal').on('hidden.bs.modal', function () {
        $('#addCategoryForm')[0].reset();
        $('#categoryAlert').hide().removeClass('alert-success alert-danger alert-warning');
    });
    
    // Allow Enter key to submit category form
    $('#category_name, #category_description').on('keypress', function(e) {
        if (e.which === 13 && !e.shiftKey) {
            e.preventDefault();
            $('#saveCategoryBtn').click();
        }
    });
});

// Show alert in category modal
function showCategoryAlert(message, type) {
    const alertDiv = $('#categoryAlert');
    alertDiv.removeClass('alert-success alert-danger alert-warning');
    alertDiv.addClass('alert alert-' + type);
    alertDiv.html('<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '"></i> ' + message);
    alertDiv.show();
    
    // Auto-hide after 3 seconds for success messages
    if (type === 'success') {
        setTimeout(function() {
            alertDiv.fadeOut();
        }, 3000);
    }
}

// Show alert on main page
function showMainAlert(message, type) {
    const alertHTML = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Insert after the page title
    $('.d-flex.justify-content-between.align-items-center').after(alertHTML);
    
    // Auto-dismiss after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut(function() {
            $(this).remove();
        });
    }, 5000);
}

// Search functionality
document.getElementById('searchProduct').addEventListener('keyup', function() {
    const searchValue = this.value.toLowerCase();
    const tableRows = document.querySelectorAll('#productsTable tbody tr');
    
    tableRows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchValue) ? '' : 'none';
    });
});

// Edit product
function editProduct(product) {
    document.getElementById('edit_product_id').value = product.id;
    document.getElementById('edit_product_name').value = product.name;
    
    // Set category using Select2
    $('#edit_category_id').val(product.category_id).trigger('change');
    
    document.getElementById('edit_description').value = product.description || '';
    document.getElementById('edit_price').value = product.price;
    document.getElementById('edit_discount_price').value = product.discount_price || '';
    document.getElementById('edit_stock_quantity').value = product.stock_quantity;
    document.getElementById('edit_sizes').value = product.sizes || '';
    document.getElementById('edit_colors').value = product.colors || '';
    document.getElementById('edit_is_active').value = product.is_active;
    document.getElementById('edit_is_featured').checked = product.is_featured == 1;
    
    const editModal = new bootstrap.Modal(document.getElementById('editModal'));
    editModal.show();
}

// Delete product
function deleteProduct(id, name) {
    document.getElementById('delete_product_id').value = id;
    document.getElementById('deleteProductName').textContent = name;
    
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
}

// Auto-dismiss alerts
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);
</script>
</body>
</html>