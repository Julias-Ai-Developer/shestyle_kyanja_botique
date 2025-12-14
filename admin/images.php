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

// Function to compress and optimize images
function compressImage($source, $destination, $quality = 85) {
    if (!extension_loaded('gd')) {
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
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($source);
            break;
        case IMAGETYPE_GIF:
            $image = imagecreatefromgif($source);
            break;
        case IMAGETYPE_WEBP:
            $image = imagecreatefromwebp($source);
            break;
    }
    
    if ($image === false) {
        return copy($source, $destination);
    }
    
    // Save with compression
    imagejpeg($image, $destination, $quality);
    imagedestroy($image);
    
    return true;
}

$adminId = $_SESSION['admin_id'];
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

$error = '';
$success = '';

// Create uploads directory if it doesn't exist
$uploadsDir = '../uploads/products/';
if (!is_dir($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'upload':
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
                    $newFileName = 'product_' . $productId . '_' . time() . '_' . uniqid() . '.' . $fileExt;
                    $uploadPath = $uploadsDir . $newFileName;
                    
                    if (move_uploaded_file($fileTmp, $uploadPath)) {
                        // Compress image
                        compressImage($uploadPath, $uploadPath, 85);
                        
                        $imageUrl = 'uploads/products/' . $newFileName;
                        $imageUrl_escaped = mysqli_real_escape_string($conn, $imageUrl);
                        
                        // If set as primary, unset other primary images for this product
                        if ($isPrimary) {
                            $updateQuery = "UPDATE product_images SET is_primary = 0 WHERE product_id = $productId";
                            mysqli_query($conn, $updateQuery);
                        }
                        
                        $insertQuery = "INSERT INTO product_images (product_id, image_url, is_primary, display_order)
                                       VALUES ($productId, '$imageUrl_escaped', $isPrimary, $displayOrder)";
                        
                        if (mysqli_query($conn, $insertQuery)) {
                            $success = 'Image uploaded successfully!';
                            
                            // Log activity
                            $action = "Uploaded product image for product ID: $productId";
                            $actionEscaped = mysqli_real_escape_string($conn, $action);
                            $logQuery = "INSERT INTO activity_logs (admin_id, action) VALUES ($adminId, '$actionEscaped')";
                            mysqli_query($conn, $logQuery);
                        } else {
                            $error = 'Error saving image to database: ' . mysqli_error($conn);
                            unlink($uploadPath);
                        }
                    } else {
                        $error = 'Error moving uploaded file. Check folder permissions.';
                    }
                }
            }
            break;
            
        case 'edit':
            $imageId = intval($_POST['image_id'] ?? 0);
            $isPrimary = isset($_POST['edit_is_primary']) ? 1 : 0;
            $displayOrder = intval($_POST['edit_display_order'] ?? 0);
            $replaceImage = !empty($_FILES['edit_image_file']['name']);
            
            if ($imageId <= 0) {
                $error = 'Invalid image ID.';
            } else {
                // Get current image data
                $getImageQuery = "SELECT product_id, image_url FROM product_images WHERE id = $imageId";
                $result = mysqli_query($conn, $getImageQuery);
                $imageData = mysqli_fetch_assoc($result);
                
                if ($imageData) {
                    $imageUrl = $imageData['image_url'];
                    
                    // Handle image replacement if new file uploaded
                    if ($replaceImage) {
                        $file = $_FILES['edit_image_file'];
                        $fileName = $file['name'];
                        $fileTmp = $file['tmp_name'];
                        $fileSize = $file['size'];
                        $fileError = $file['error'];
                        
                        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        $maxSize = 5 * 1024 * 1024; // 5MB
                        
                        if ($fileError !== 0) {
                            $error = 'Error uploading file.';
                            break;
                        } elseif (!in_array($fileExt, $allowedExts)) {
                            $error = 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.';
                            break;
                        } elseif ($fileSize > $maxSize) {
                            $error = 'File size exceeds 5MB limit.';
                            break;
                        } else {
                            // Delete old image file
                            $oldFilePath = '../' . $imageData['image_url'];
                            if (file_exists($oldFilePath)) {
                                unlink($oldFilePath);
                            }
                            
                            // Generate new filename
                            $newFileName = 'product_' . $imageData['product_id'] . '_' . time() . '_' . uniqid() . '.' . $fileExt;
                            $uploadPath = $uploadsDir . $newFileName;
                            
                            if (move_uploaded_file($fileTmp, $uploadPath)) {
                                // Compress image
                                compressImage($uploadPath, $uploadPath, 85);
                                $imageUrl = 'uploads/products/' . $newFileName;
                            } else {
                                $error = 'Error moving uploaded file.';
                                break;
                            }
                        }
                    }
                    
                    // If set as primary, unset other primary images for this product
                    if ($isPrimary) {
                        $updateQuery = "UPDATE product_images SET is_primary = 0 WHERE product_id = {$imageData['product_id']} AND id != $imageId";
                        mysqli_query($conn, $updateQuery);
                    }
                    
                    $imageUrl_escaped = mysqli_real_escape_string($conn, $imageUrl);
                    $updateQuery = "UPDATE product_images SET image_url = '$imageUrl_escaped', is_primary = $isPrimary, display_order = $displayOrder WHERE id = $imageId";
                    
                    if (mysqli_query($conn, $updateQuery)) {
                        $success = $replaceImage ? 'Image replaced successfully!' : 'Image updated successfully!';
                        
                        // Log activity
                        $action = $replaceImage ? "Replaced product image ID: $imageId" : "Updated product image ID: $imageId";
                        $actionEscaped = mysqli_real_escape_string($conn, $action);
                        $logQuery = "INSERT INTO activity_logs (admin_id, action) VALUES ($adminId, '$actionEscaped')";
                        mysqli_query($conn, $logQuery);
                    } else {
                        $error = 'Error updating image: ' . mysqli_error($conn);
                    }
                } else {
                    $error = 'Image not found.';
                }
            }
            break;
            
        case 'delete':
            $imageId = intval($_POST['image_id'] ?? 0);
            
            if ($imageId <= 0) {
                $error = 'Invalid image ID.';
            } else {
                // Get image path
                $getImageQuery = "SELECT image_url FROM product_images WHERE id = $imageId";
                $result = mysqli_query($conn, $getImageQuery);
                $imageData = mysqli_fetch_assoc($result);
                
                if ($imageData) {
                    $deleteQuery = "DELETE FROM product_images WHERE id = $imageId";
                    
                    if (mysqli_query($conn, $deleteQuery)) {
                        // Delete physical file
                        $filePath = '../' . $imageData['image_url'];
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                        
                        $success = 'Image deleted successfully!';
                        
                        // Log activity
                        $action = "Deleted product image ID: $imageId";
                        $actionEscaped = mysqli_real_escape_string($conn, $action);
                        $logQuery = "INSERT INTO activity_logs (admin_id, action) VALUES ($adminId, '$actionEscaped')";
                        mysqli_query($conn, $logQuery);
                    } else {
                        $error = 'Error deleting image: ' . mysqli_error($conn);
                    }
                } else {
                    $error = 'Image not found.';
                }
            }
            break;
    }
}

// Get images with product info
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

// Get total count
$countQuery = "SELECT COUNT(*) as cnt FROM product_images";
$countResult = mysqli_query($conn, $countQuery);
$countRow = mysqli_fetch_assoc($countResult);
$total = $countRow['cnt'];
$totalPages = ceil($total / $perPage);

// Get products for dropdown
$productsQuery = "SELECT id, name FROM products WHERE is_active = 1 ORDER BY name";
$productsResult = mysqli_query($conn, $productsQuery);
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .bg-orange { background-color: #FF6B35 !important; }
        .badge-orange { background-color: #FF6B35; }
        .image-card {
            transition: transform 0.2s;
            cursor: pointer;
        }
        .image-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .image-preview {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
        }
        .file-upload-wrapper {
            border: 2px dashed #ccc;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .file-upload-wrapper:hover {
            border-color: #FF6B35;
            background-color: #fff5f2;
        }
        .file-upload-wrapper input[type="file"] {
            display: none;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid">
        <span class="navbar-brand">🔶 Boutique Admin</span>
        <div class="d-flex align-items-center gap-3">
            <div class="text-light">
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
                    <li class="nav-item"><a class="nav-link" href="products.php">Products</a></li>
                    <li class="nav-item"><a class="nav-link active" href="images.php" style="color: #FF6B35; font-weight: 600; border-left: 4px solid #FF6B35; padding-left: 12px;">Images</a></li>
                    <li class="nav-item"><a class="nav-link" href="workers.php">Workers</a></li>
                    <li class="nav-item"><a class="nav-link" href="orders.php">Orders</a></li>
                    <li class="nav-item"><a class="nav-link" href="customers.php">Customers</a></li>
                    <li class="nav-item"><a class="nav-link" href="settings.php">Settings</a></li>
                </ul>
            </div>
        </nav>

        <main class="col-md-10 ms-sm-auto px-md-4">
            <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
                <h2><i class="fas fa-images"></i> Image Management</h2>
                <div class="badge bg-info">Total Images: <?php echo $total; ?></div>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Upload Form -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-orange text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-upload"></i> Upload New Image</h5>
                    <button type="button" class="btn btn-light btn-sm" data-bs-toggle="collapse" data-bs-target="#uploadForm">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
                <div class="collapse show" id="uploadForm">
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
                                
                                <div class="col-md-3 mb-3">
                                    <label for="display_order" class="form-label">Display Order</label>
                                    <input type="number" class="form-control" id="display_order" name="display_order" value="0" min="0">
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label class="form-label d-block">Primary Image</label>
                                    <div class="form-check form-switch mt-2">
                                        <input type="checkbox" class="form-check-input" id="is_primary" name="is_primary">
                                        <label class="form-check-label" for="is_primary">Set as primary</label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Image File <span class="text-danger">*</span></label>
                                <div class="file-upload-wrapper" onclick="document.getElementById('image_file').click();">
                                    <input type="file" id="image_file" name="image_file" accept="image/jpeg,image/png,image/gif,image/webp" required>
                                    <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                    <p class="file-upload-label mb-0">Click to select image or drag and drop</p>
                                    <small class="text-muted">JPG, PNG, GIF, WebP (Max 5MB)</small>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-orange">
                                <i class="fas fa-upload"></i> Upload Image
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Images Grid -->
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                <?php if (!empty($images)): ?>
                    <?php foreach ($images as $image): ?>
                    <div class="col">
                        <div class="card image-card h-100">
                            <?php 
                            $imgPath = $image['image_url'];
                            $imgSrc = (strpos($imgPath, 'http') === 0) ? $imgPath : '../' . $imgPath;
                            ?>
                            <img src="<?php echo htmlspecialchars($imgSrc); ?>" 
                                 class="image-preview" 
                                 alt="Product image"
                                 onerror="this.src='https://via.placeholder.com/200?text=No+Image'">
                            <div class="card-body">
                                <h6 class="card-title"><?php echo htmlspecialchars($image['product_name']); ?></h6>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <?php if ($image['is_primary']): ?>
                                    <span class="badge bg-success"><i class="fas fa-star"></i> Primary</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">Secondary</span>
                                    <?php endif; ?>
                                    <span class="badge bg-info">Order: <?php echo $image['display_order']; ?></span>
                                </div>
                                <small class="text-muted d-block mb-2">
                                    <i class="fas fa-clock"></i> <?php echo date('M d, Y', strtotime($image['created_at'])); ?>
                                </small>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-primary flex-fill" onclick='editImage(<?php echo json_encode($image); ?>)'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger flex-fill" onclick="deleteImage(<?php echo $image['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-images fa-4x mb-3"></i>
                            <p class="fs-5">No images uploaded yet</p>
                            <p>Upload your first product image above!</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                    </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-orange text-white">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Image</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data" id="editForm">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="image_id" id="edit_image_id">
                    
                    <div class="text-center mb-3">
                        <img id="edit_image_preview" src="" alt="Preview" style="max-width: 100%; max-height: 200px; border-radius: 8px;">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Product</label>
                        <input type="text" class="form-control" id="edit_product_name" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_image_file" class="form-label">Replace Image (Optional)</label>
                        <input type="file" class="form-control" id="edit_image_file" name="edit_image_file" accept="image/jpeg,image/png,image/gif,image/webp">
                        <small class="text-muted">Leave empty to keep current image. JPG, PNG, GIF, WebP (Max 5MB)</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_display_order" class="form-label">Display Order</label>
                        <input type="number" class="form-control" id="edit_display_order" name="edit_display_order" min="0">
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" id="edit_is_primary" name="edit_is_primary">
                            <label class="form-check-label" for="edit_is_primary">Set as Primary Image</label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-orange">
                        <i class="fas fa-save"></i> Update Image
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this image?</p>
                <p class="text-danger"><small><i class="fas fa-info-circle"></i> This action cannot be undone!</small></p>
            </div>
            <div class="modal-footer">
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="image_id" id="delete_image_id">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete Image
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// File input handler for upload form
const fileInput = document.getElementById('image_file');
if (fileInput) {
    fileInput.addEventListener('change', function() {
        const fileName = this.files[0]?.name || 'No file selected';
        const label = document.querySelector('.file-upload-label');
        if (label && this.files[0]) {
            label.innerHTML = '<i class="fas fa-check-circle text-success"></i> ' + fileName;
        }
    });
}

// File input handler for edit form
const editFileInput = document.getElementById('edit_image_file');
if (editFileInput) {
    editFileInput.addEventListener('change', function() {
        if (this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('edit_image_preview').src = e.target.result;
            };
            reader.readAsDataURL(this.files[0]);
        }
    });
}

// Edit image function
function editImage(image) {
    document.getElementById('edit_image_id').value = image.id;
    document.getElementById('edit_product_name').value = image.product_name;
    document.getElementById('edit_display_order').value = image.display_order;
    document.getElementById('edit_is_primary').checked = image.is_primary == 1;
    
    // Reset file input
    document.getElementById('edit_image_file').value = '';
    
    // Set image preview
    const imgPath = image.image_url;
    const imgSrc = imgPath.startsWith('http') ? imgPath : '../' + imgPath;
    document.getElementById('edit_image_preview').src = imgSrc;
    
    const editModal = new bootstrap.Modal(document.getElementById('editModal'));
    editModal.show();
}

// Delete image function
function deleteImage(id) {
    document.getElementById('delete_image_id').value = id;
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