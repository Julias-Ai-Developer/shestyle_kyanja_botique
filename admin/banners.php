<?php
$pageTitle = 'Banner Management - Admin';
require_once '../config/database.php';

if (!isAdmin()) {
    header('Location: login.php');
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
$uploadsDir = '../uploads/banners/';
if (!is_dir($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
}

// Handle banner upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $subtitle = sanitizeInput($_POST['subtitle'] ?? '');
    $buttonText = sanitizeInput($_POST['button_text'] ?? '');
    $link = sanitizeInput($_POST['link'] ?? '');
    $displayOrder = intval($_POST['display_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($title)) {
        $error = 'Title is required.';
    } elseif (empty($_FILES['banner_image']['name'])) {
        $error = 'Please select a banner image.';
    } else {
        $file = $_FILES['banner_image'];
        $fileName = $file['name'];
        $fileTmp = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileError = $file['error'];
        
        // Validate file
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $maxSize = 10 * 1024 * 1024; // 10MB for banners
        
        if ($fileError !== 0) {
            $error = 'Error uploading file.';
        } elseif (!in_array($fileExt, $allowedExts)) {
            $error = 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.';
        } elseif ($fileSize > $maxSize) {
            $error = 'File size exceeds 10MB limit.';
        } else {
            // Generate unique filename
            $newFileName = 'banner_' . time() . '.' . $fileExt;
            $uploadPath = $uploadsDir . $newFileName;
            
            if (move_uploaded_file($fileTmp, $uploadPath)) {
                // Compress and optimize image
                compressImage($uploadPath, $uploadPath, 80);
                
                $imagePath = 'uploads/banners/' . $newFileName;
                $imagePath_escaped = mysqli_real_escape_string($conn, $imagePath);
                $title_escaped = mysqli_real_escape_string($conn, $title);
                $subtitle_escaped = mysqli_real_escape_string($conn, $subtitle);
                $buttonText_escaped = mysqli_real_escape_string($conn, $buttonText);
                $link_escaped = mysqli_real_escape_string($conn, $link);
                
                $insertQuery = "
                    INSERT INTO banners (title, subtitle, image, link, button_text, is_active, display_order)
                    VALUES ('$title_escaped', '$subtitle_escaped', '$imagePath_escaped', '$link_escaped', '$buttonText_escaped', $isActive, $displayOrder)
                ";
                
                if (mysqli_query($conn, $insertQuery)) {
                    $success = 'Banner uploaded successfully!';
                    
                    // Log activity
                    $action = "Uploaded new carousel banner: $title (File: $newFileName)";
                    $actionEscaped = mysqli_real_escape_string($conn, $action);
                    $logQuery = "INSERT INTO activity_logs (admin_id, action) VALUES ($adminId, '$actionEscaped')";
                    mysqli_query($conn, $logQuery);
                } else {
                    $error = 'Error saving banner to database: ' . mysqli_error($conn);
                    unlink($uploadPath);
                }
            } else {
                $error = 'Error moving uploaded file. Please check folder permissions.';
            }
        }
    }
}

// Handle banner delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $bannerId = intval($_POST['banner_id']);
    
    if ($bannerId > 0) {
        // Get banner image path to delete file
        $getBannerQuery = "SELECT image FROM banners WHERE id = $bannerId";
        $getBannerResult = mysqli_query($conn, $getBannerQuery);
        $banner = mysqli_fetch_assoc($getBannerResult);
        
        if ($banner) {
            // Delete from database
            $deleteQuery = "DELETE FROM banners WHERE id = $bannerId";
            if (mysqli_query($conn, $deleteQuery)) {
                // Delete image file
                $imagePath = '../' . $banner['image'];
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
                $success = 'Banner deleted successfully!';
                
                // Log activity
                $action = "Deleted carousel banner (ID: $bannerId)";
                $actionEscaped = mysqli_real_escape_string($conn, $action);
                $logQuery = "INSERT INTO activity_logs (admin_id, action) VALUES ($adminId, '$actionEscaped')";
                mysqli_query($conn, $logQuery);
            } else {
                $error = 'Error deleting banner: ' . mysqli_error($conn);
            }
        }
    }
}

// Handle banner toggle active status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle') {
    $bannerId = intval($_POST['banner_id']);
    
    if ($bannerId > 0) {
        $toggleQuery = "UPDATE banners SET is_active = NOT is_active WHERE id = $bannerId";
        if (mysqli_query($conn, $toggleQuery)) {
            $success = 'Banner status updated!';
            
            // Log activity
            $action = "Toggled banner active status (ID: $bannerId)";
            $actionEscaped = mysqli_real_escape_string($conn, $action);
            $logQuery = "INSERT INTO activity_logs (admin_id, action) VALUES ($adminId, '$actionEscaped')";
            mysqli_query($conn, $logQuery);
        } else {
            $error = 'Error updating banner: ' . mysqli_error($conn);
        }
    }
}

// Get banners
$bannersQuery = "
    SELECT * FROM banners 
    ORDER BY display_order ASC, created_at DESC 
    LIMIT " . intval($perPage) . " OFFSET " . intval($offset);
$bannersResult = mysqli_query($conn, $bannersQuery);
if (!$bannersResult) {
    die('Database error: ' . mysqli_error($conn));
}
$banners = [];
while ($row = mysqli_fetch_assoc($bannersResult)) {
    $banners[] = $row;
}

// Get total
$countQuery = "SELECT COUNT(*) as cnt FROM banners";
$countResult = mysqli_query($conn, $countQuery);
if (!$countResult) {
    die('Database error: ' . mysqli_error($conn));
}
$countRow = mysqli_fetch_assoc($countResult);
$total = $countRow['cnt'];
$totalPages = ceil($total / $perPage);
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
                    <li class="nav-item"><a class="nav-link" href="images.php">Images</a></li>
                    <li class="nav-item"><a class="nav-link active" href="banners.php" style="color: #FF6B35; font-weight: 600; border-left: 4px solid #FF6B35; padding-left: 12px;">Banners</a></li>
                    <li class="nav-item"><a class="nav-link" href="workers.php">Workers</a></li>
                    <li class="nav-item"><a class="nav-link" href="orders.php">Orders</a></li>
                    <li class="nav-item"><a class="nav-link" href="customers.php">Customers</a></li>
                    <li class="nav-item"><a class="nav-link" href="settings.php">Settings</a></li>
                </ul>
            </div>
        </nav>

        <main class="col-md-10 ms-sm-auto px-md-4">
            <h2 class="page-title">📸 Banner Management (Carousel)</h2>

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

            <!-- Add Banner Form -->
            <div class="card mb-4">
                <div class="card-header bg-orange text-white">
                    <h5 class="mb-0">Add New Carousel Banner</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="upload">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="title" class="form-label">Banner Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" placeholder="e.g., Summer Collection 2024" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="button_text" class="form-label">Button Text</label>
                                <input type="text" class="form-control" id="button_text" name="button_text" placeholder="e.g., Shop Now">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="subtitle" class="form-label">Subtitle/Description</label>
                                <textarea class="form-control" id="subtitle" name="subtitle" rows="2" placeholder="e.g., Up to 50% OFF on selected items"></textarea>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="link" class="form-label">Button Link</label>
                                <input type="text" class="form-control" id="link" name="link" placeholder="e.g., shop.php or /shop.php">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="display_order" class="form-label">Display Order</label>
                                <input type="number" class="form-control" id="display_order" name="display_order" value="0" min="0">
                            </div>
                            
                            <div class="col-md-6 mb-3 mt-4">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" checked>
                                    <label class="form-check-label" for="is_active">
                                        ✅ Active (Show on Homepage)
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="banner_image" class="form-label">Banner Image <span class="text-danger">*</span></label>
                            <div class="file-upload-wrapper" onclick="document.getElementById('banner_image').click();">
                                <input type="file" class="form-control" id="banner_image" name="banner_image" accept="image/jpeg,image/png,image/gif,image/webp" required>
                                <p class="file-upload-label mb-0">
                                    📁 Click to select image or drag and drop
                                </p>
                                <small class="text-muted">Supported: JPG, PNG, GIF, WebP (Max 10MB) | Recommended: 1920x500px</small>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary-custom">
                            <span>📤</span> Upload Banner
                        </button>
                    </form>
                </div>
            </div>

            <!-- Banners List -->
            <div class="card">
                <div class="card-header bg-orange text-white">
                    <h5 class="mb-0">Active Banners</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Preview</th>
                                <th>Title</th>
                                <th>Subtitle</th>
                                <th>Order</th>
                                <th>Status</th>
                                <th>Uploaded</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($banners)): ?>
                                <?php foreach ($banners as $banner): ?>
                                <tr>
                                    <td>
                                        <img src="<?php echo htmlspecialchars($banner['image']); ?>" alt="Banner" style="max-width: 80px; max-height: 50px; border-radius: 4px; object-fit: cover;" onerror="this.src='https://via.placeholder.com/80x50?text=No+Image'">
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($banner['title']); ?></strong>
                                        <?php if (!empty($banner['button_text'])): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($banner['button_text']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?php echo htmlspecialchars(substr($banner['subtitle'], 0, 50)); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $banner['display_order']; ?></span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="banner_id" value="<?php echo $banner['id']; ?>">
                                            <button type="submit" class="btn btn-sm <?php echo $banner['is_active'] ? 'btn-success' : 'btn-secondary'; ?>" title="Click to toggle active status">
                                                <?php echo $banner['is_active'] ? '✅ Active' : '❌ Inactive'; ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?php echo date('M d, Y', strtotime($banner['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="banner_id" value="<?php echo $banner['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this banner? This cannot be undone.');">🗑️ Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        <p style="font-size: 1.5rem;">📭</p>
                                        <p>No banners created yet. Upload your first carousel banner above!</p>
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
const fileInput = document.getElementById('banner_image');
if (fileInput) {
    fileInput.addEventListener('change', function() {
        const fileName = this.files[0]?.name || 'No file selected';
        const wrapper = this.parentElement;
        const label = wrapper.querySelector('.file-upload-label');
        if (label) {
            label.textContent = '✅ ' + fileName;
        }
    });
}
</script>
</body>
</html>
