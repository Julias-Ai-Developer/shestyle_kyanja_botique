<?php
$pageTitle = 'Image Management - Admin';
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

// Function to compress and optimize images (with fallback if GD is not available)
function compressImage($source, $destination, $quality = 85) {
    // Check if GD library is available
    if (!extension_loaded('gd')) {
        // Fallback: just copy the file without compression
        return copy($source, $destination);
    }
    
    $info = getimagesize($source);
    if ($info === false) {
        return false;
    }
    
    $image = false;
    
    switch ($info[2]) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_GIF:
            $image = imagecreatefromgif($source);
            break;
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($source);
            break;
        case IMAGETYPE_WEBP:
            $image = imagecreatefromwebp($source);
            break;
    }
    
    if ($image === false) {
        // Fallback: just copy the file if image creation fails
        return copy($source, $destination);
    }
    
    if ($image === false) {
        // Fallback: just copy the file if image creation fails
        return copy($source, $destination);
    }
    
    // Reduce quality and save
    imagejpeg($image, $destination, $quality);
    imagedestroy($image);
    
    return true;
}

$adminId = $_SESSION['admin_id'];
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$error = '';
$success = '';

// Create uploads directory if it doesn't exist
$uploadsDir = '../uploads/products/';
if (!is_dir($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
}

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload') {
    $productId = intval($_POST['product_id'] ?? 0);
    $isPrimary = isset($_POST['is_primary']) ? 1 : 0;
    $displayOrder = intval($_POST['display_order'] ?? 0);
    
    if ($productId <= 0) {
        $error = 'Product is required.';
    } elseif (empty($_FILES['image_file']['name'])) {
        $error = 'Please select an image file.';
    } else {
        $file = $_FILES['image_file'];
        $fileName = $file['name'];
        $fileTmp = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileError = $file['error'];
        
        // Validate file
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if ($fileError !== 0) {
            $error = 'Error uploading file.';
        } elseif (!in_array($fileExt, $allowedExts)) {
            $error = 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.';
        } elseif ($fileSize > $maxSize) {
            $error = 'File size exceeds 5MB limit.';
        } else {
            // Generate unique filename
            $newFileName = 'product_' . $productId . '_' . time() . '.' . $fileExt;
            $uploadPath = $uploadsDir . $newFileName;
            
            if (move_uploaded_file($fileTmp, $uploadPath)) {
                // Compress and optimize image
                compressImage($uploadPath, $uploadPath, 85);
                
                $imageUrl = 'uploads/products/' . $newFileName;
                $imageUrl_escaped = mysqli_real_escape_string($conn, $imageUrl);
                
                $insertQuery = "
                    INSERT INTO product_images (product_id, image_url, is_primary, display_order)
                    VALUES ($productId, '$imageUrl_escaped', $isPrimary, $displayOrder)
                ";
                
                if (mysqli_query($conn, $insertQuery)) {
                    $success = 'Image uploaded successfully!';
                    
                    // Log activity
                    $action = "Uploaded product image for product ID: $productId (File: $newFileName)";
                    $actionEscaped = mysqli_real_escape_string($conn, $action);
                    $logQuery = "INSERT INTO activity_logs (admin_id, action) VALUES ($adminId, '$actionEscaped')";
                    mysqli_query($conn, $logQuery);
                } else {
                    $error = 'Error saving image to database: ' . mysqli_error($conn);
                    unlink($uploadPath); // Delete uploaded file if DB insert fails
                }
            } else {
                $error = 'Error moving uploaded file. Please check folder permissions.';
            }
        }
    }
}

// Get images
$imagesQuery = "
    SELECT pi.*, p.name as product_name 
    FROM product_images pi 
    JOIN products p ON pi.product_id = p.id
    ORDER BY pi.created_at DESC 
    LIMIT " . intval($perPage) . " OFFSET " . intval($offset);
$imagesResult = mysqli_query($conn, $imagesQuery);
if (!$imagesResult) {
    die('Database error: ' . mysqli_error($conn));
}
$images = [];
while ($row = mysqli_fetch_assoc($imagesResult)) {
    $images[] = $row;
}

// Get total
$countQuery = "SELECT COUNT(*) as cnt FROM product_images";
$countResult = mysqli_query($conn, $countQuery);
if (!$countResult) {
    die('Database error: ' . mysqli_error($conn));
}
$countRow = mysqli_fetch_assoc($countResult);
$total = $countRow['cnt'];
$totalPages = ceil($total / $perPage);

// Get products for dropdown
$productsQuery = "SELECT id, name FROM products WHERE is_active = 1 ORDER BY name";
$productsResult = mysqli_query($conn, $productsQuery);
if (!$productsResult) {
    die('Database error: ' . mysqli_error($conn));
}
$products = [];
while ($row = mysqli_fetch_assoc($productsResult)) {
    $products[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid">
        <span class="navbar-brand">🔶 Boutique Admin</span>
        <div class="d-flex align-items-center gap-3">
            <div class="text-light">
                <small class="d-block">Logged in as:</small>
                <strong><?php echo htmlspecialchars($_SESSION['admin_username']); ?></strong>
                <span class="badge bg-orange ms-2"><?php echo ucfirst(str_replace('_', ' ', $_SESSION['admin_role'])); ?></span>
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
                    <li class="nav-item"><a class="nav-link" href="products.php">Products</a></li>
                    <li class="nav-item"><a class="nav-link active" href="images.php" style="color: #FF6B35; font-weight: 600; border-left: 4px solid #FF6B35; padding-left: 12px;">Images</a></li>
                    <li class="nav-item"><a class="nav-link" href="banners.php">Banners</a></li>
                    <li class="nav-item"><a class="nav-link" href="workers.php">Workers</a></li>
                    <li class="nav-item"><a class="nav-link" href="orders.php">Orders</a></li>
                    <li class="nav-item"><a class="nav-link" href="customers.php">Customers</a></li>
                    <li class="nav-item"><a class="nav-link" href="settings.php">Settings</a></li>
                </ul>
            </div>
        </nav>

        <main class="col-md-10 ms-sm-auto px-md-4">
            <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
                <h2>Image Management</h2>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Add Image Form -->
            <div class="card mb-4">
                <div class="card-header bg-orange text-white">
                    <h5 class="mb-0">Upload New Image</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="upload">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="product_id" class="form-label">Select Product <span class="text-danger">*</span></label>
                                <select class="form-select" id="product_id" name="product_id" required>
                                    <option value="">-- Choose a product --</option>
                                    <?php foreach ($products as $product): ?>
                                    <option value="<?php echo $product['id']; ?>">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="display_order" class="form-label">Display Order</label>
                                <input type="number" class="form-control" id="display_order" name="display_order" value="0" min="0">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="image_file" class="form-label">Image File <span class="text-danger">*</span></label>
                            <div class="file-upload-wrapper" onclick="document.getElementById('image_file').click();">
                                <input type="file" class="form-control" id="image_file" name="image_file" accept="image/jpeg,image/png,image/gif,image/webp" required>
                                <p class="file-upload-label mb-0">
                                    📁 Click to select image or drag and drop
                                </p>
                                <small class="text-muted">Supported: JPG, PNG, GIF, WebP (Max 5MB)</small>
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_primary" name="is_primary">
                            <label class="form-check-label" for="is_primary">
                                ⭐ Set as Primary Image (Featured)
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary-custom">
                            <span>📤</span> Upload Image
                        </button>
                    </form>
                </div>
            </div>

            <!-- Images List -->
            <div class="card">
                <div class="card-header bg-orange text-white">
                    <h5 class="mb-0">Uploaded Images</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Preview</th>
                                <th>Product</th>
                                <th>Status</th>
                                <th>Order</th>
                                <th>Uploaded</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($images)): ?>
                                <?php foreach ($images as $image): ?>
                                <tr>
                                    <td>
                                        <?php 
                                        $imgPath = $image['image_url'];
                                        // Check if it's a local file or external URL
                                        if (strpos($imgPath, 'http') === 0) {
                                            $imgSrc = $imgPath;
                                        } else {
                                            $imgSrc = '../' . $imgPath;
                                        }
                                        ?>
                                        <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="Product image" style="max-width: 60px; max-height: 60px; border-radius: 4px; object-fit: cover;" onerror="this.src='https://via.placeholder.com/60?text=No+Image'">
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($image['product_name']); ?></strong>
                                    </td>
                                    <td>
                                        <?php if ($image['is_primary']): ?>
                                        <span class="badge bg-success">⭐ Primary</span>
                                        <?php else: ?>
                                        <span class="badge bg-secondary">Secondary</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $image['display_order']; ?></span>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?php echo date('M d, Y', strtotime($image['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteImage(<?php echo $image['id']; ?>)">🗑️ Delete</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5">
                                        <p style="font-size: 1.5rem;">📭</p>
                                        <p>No images uploaded yet. Upload your first image above!</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// File input display name
document.getElementById('image_file').addEventListener('change', function() {
    const fileName = this.files[0]?.name || 'No file selected';
    const wrapper = this.parentElement;
    const label = wrapper.querySelector('.file-upload-label');
    label.textContent = '✅ ' + fileName;
});

// Drag and drop
const wrapper = document.querySelector('.file-upload-wrapper');
['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    wrapper.addEventListener(eventName, preventDefaults, false);
});

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

['dragenter', 'dragover'].forEach(eventName => {
    wrapper.addEventListener(eventName, () => {
        wrapper.style.backgroundColor = '#ffe8d8';
    }, false);
});

['dragleave', 'drop'].forEach(eventName => {
    wrapper.addEventListener(eventName, () => {
        wrapper.style.backgroundColor = '#fff8f0';
    }, false);
});

wrapper.addEventListener('drop', (e) => {
    const dt = e.dataTransfer;
    const files = dt.files;
    document.getElementById('image_file').files = files;
    // Trigger change event
    document.getElementById('image_file').dispatchEvent(new Event('change', { bubbles: true }));
}, false);

function deleteImage(imageId) {
    if (confirm('Are you sure you want to delete this image? This action cannot be undone.')) {
        // Implement delete functionality
        alert('Delete functionality to be implemented in the next update');
    }
}
</script>
</body>
</html>

