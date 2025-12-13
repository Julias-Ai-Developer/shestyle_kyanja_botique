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

$adminId = $_SESSION['admin_id'];
$success = '';
$error = '';

// Get admin info
$adminId_int = intval($adminId);
$adminQuery = "SELECT * FROM admin_users WHERE id = $adminId_int";
$adminResult = mysqli_query($conn, $adminQuery);
if (!$adminResult) {
    die('Database error: ' . mysqli_error($conn));
}
$admin = mysqli_fetch_assoc($adminResult);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $email = sanitizeInput($_POST['email'] ?? '');
        
        if (empty($email)) {
            $error = 'Email is required.';
        } else {
            $email_escaped = mysqli_real_escape_string($conn, $email);
            $updateQuery = "UPDATE admin_users SET email = '$email_escaped' WHERE id = $adminId_int";
            
            if (mysqli_query($conn, $updateQuery)) {
                $success = 'Profile updated successfully!';
                
                // Refresh admin data
                $adminResult = mysqli_query($conn, $adminQuery);
                $admin = mysqli_fetch_assoc($adminResult);
            } else {
                $error = 'Error updating profile: ' . mysqli_error($conn);
            }
        }
    } elseif ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = 'All password fields are required.';
        } elseif (!password_verify($currentPassword, $admin['password'])) {
            $error = 'Current password is incorrect.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match.';
        } elseif (strlen($newPassword) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $hashedPassword_escaped = mysqli_real_escape_string($conn, $hashedPassword);
            $updateQuery = "UPDATE admin_users SET password = '$hashedPassword_escaped' WHERE id = $adminId_int";
            
            if (mysqli_query($conn, $updateQuery)) {
                $success = 'Password changed successfully!';
            } else {
                $error = 'Error changing password: ' . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings - Admin</title>
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
                    <li class="nav-item"><a class="nav-link" href="banners.php">Banners</a></li>
                    <li class="nav-item"><a class="nav-link" href="workers.php">Workers</a></li>
                    <li class="nav-item"><a class="nav-link" href="orders.php">Orders</a></li>
                    <li class="nav-item"><a class="nav-link" href="customers.php">Customers</a></li>
                    <li class="nav-item"><a class="nav-link active" href="settings.php" style="color: #FF6B35; font-weight: 600; border-left: 4px solid #FF6B35; padding-left: 12px;">Settings</a></li>
                </ul>
            </div>
        </nav>

        <main class="col-md-10 ms-sm-auto px-md-4">
            <h2 class="mt-4 mb-4">Settings</h2>
            
            <div class="row">
                <div class="col-md-8">
                    <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Profile Settings -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Profile Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($admin['username']); ?>" disabled>
                                    <small class="text-muted">Cannot be changed</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="role" class="form-label">Role</label>
                                    <input type="text" class="form-control" id="role" value="<?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $admin['role']))); ?>" disabled>
                                </div>
                                
                                <button type="submit" class="btn btn-primary-custom">Save Changes</button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Change Password -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Change Password</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="change_password">
                                
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    <small class="text-muted">Minimum 6 characters</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                
                                <button type="submit" class="btn btn-primary-custom">Change Password</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Account Info</h5>
                        </div>
                        <div class="card-body">
                            <p>
                                <strong>Username:</strong><br>
                                <?php echo htmlspecialchars($admin['username']); ?>
                            </p>
                            <p>
                                <strong>Role:</strong><br>
                                <span class="badge bg-info"><?php echo htmlspecialchars($admin['role']); ?></span>
                            </p>
                            <p>
                                <strong>Status:</strong><br>
                                <span class="badge bg-<?php echo $admin['is_active'] ? 'success' : 'danger'; ?>">
                                    <?php echo $admin['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </p>
                            <p>
                                <strong>Member Since:</strong><br>
                                <?php echo date('M d, Y', strtotime($admin['created_at'])); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
