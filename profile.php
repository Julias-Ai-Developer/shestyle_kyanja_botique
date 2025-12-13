<?php
$pageTitle = 'My Profile - Boutique Fashion Store';
require_once 'config/database.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user details
$userId_int = intval($userId);
$userQuery = "SELECT * FROM users WHERE id = $userId_int";
$userResult = mysqli_query($conn, $userQuery);
if (!$userResult) {
    die('Database error: ' . mysqli_error($conn));
}
$user = mysqli_fetch_assoc($userResult);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = sanitizeInput($_POST['first_name'] ?? '');
    $lastName = sanitizeInput($_POST['last_name'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $city = sanitizeInput($_POST['city'] ?? '');
    $postalCode = sanitizeInput($_POST['postal_code'] ?? '');
    
    if (empty($firstName) || empty($lastName)) {
        $error = 'First name and last name are required.';
    } else {
        $firstName_escaped = mysqli_real_escape_string($conn, $firstName);
        $lastName_escaped = mysqli_real_escape_string($conn, $lastName);
        $phone_escaped = mysqli_real_escape_string($conn, $phone);
        $address_escaped = mysqli_real_escape_string($conn, $address);
        $city_escaped = mysqli_real_escape_string($conn, $city);
        $postalCode_escaped = mysqli_real_escape_string($conn, $postalCode);
        
        $updateQuery = "
            UPDATE users SET first_name = '$firstName_escaped', last_name = '$lastName_escaped', phone = '$phone_escaped', address = '$address_escaped', city = '$city_escaped', postal_code = '$postalCode_escaped'
            WHERE id = $userId_int
        ";
        
        if (mysqli_query($conn, $updateQuery)) {
            $success = 'Profile updated successfully!';
            
            // Refresh user data
            $userResult = mysqli_query($conn, $userQuery);
            $user = mysqli_fetch_assoc($userResult);
        } else {
            $error = 'Update failed: ' . mysqli_error($conn);
        }
    }
}

// Get user orders
$ordersQuery = "SELECT * FROM orders WHERE user_id = $userId_int ORDER BY created_at DESC";
$ordersResult = mysqli_query($conn, $ordersQuery);
if (!$ordersResult) {
    die('Database error: ' . mysqli_error($conn));
}
$orders = [];
while ($row = mysqli_fetch_assoc($ordersResult)) {
    $orders[] = $row;
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
        <div class="col-md-3">
            <div class="list-group">
                <a href="#profile" class="list-group-item list-group-item-action active" data-bs-toggle="list">Profile Info</a>
                <a href="#orders" class="list-group-item list-group-item-action" data-bs-toggle="list">My Orders</a>
                <a href="logout.php" class="list-group-item list-group-item-action text-danger">Logout</a>
            </div>
        </div>
        
        <div class="col-md-9">
            <!-- Profile Section -->
            <div id="profile" class="content-section">
                <h2 class="text-orange mb-4">My Profile</h2>
                
                <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                <small class="text-muted">Contact support to change email</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="city" class="form-label">City</label>
                                    <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="postal_code" class="form-label">Postal Code</label>
                                    <input type="text" class="form-control" id="postal_code" name="postal_code" value="<?php echo htmlspecialchars($user['postal_code'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary-custom">Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Orders Section -->
            <div id="orders" class="content-section d-none">
                <h2 class="text-orange mb-4">My Orders</h2>
                
                <?php if (empty($orders)): ?>
                <div class="alert alert-info">
                    You haven't placed any orders yet. <a href="shop.php">Start shopping</a>
                </div>
                <?php else: ?>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Order #</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><a href="order_details.php?id=<?php echo $order['id']; ?>" class="text-orange"><?php echo htmlspecialchars($order['order_number']); ?></a></td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td>$<?php echo number_format($order['total'], 2); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $order['status'] === 'delivered' ? 'success' : ($order['status'] === 'cancelled' ? 'danger' : 'warning'); ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('[data-bs-toggle="list"]').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        
        document.querySelectorAll('.content-section').forEach(section => {
            section.classList.add('d-none');
        });
        
        document.querySelectorAll('[data-bs-toggle="list"]').forEach(item => {
            item.classList.remove('active');
        });
        
        const target = this.getAttribute('href');
        document.querySelector(target).classList.remove('d-none');
        this.classList.add('active');
    });
});
</script>
</body>
</html>
