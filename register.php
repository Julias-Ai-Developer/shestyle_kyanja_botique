<?php
$pageTitle = 'Register - Boutique Fashion Store';
require_once 'config/database.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $first_name = sanitizeInput($_POST['first_name'] ?? '');
    $last_name = sanitizeInput($_POST['last_name'] ?? '');
    
    if (empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
        $error = 'All fields are required.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        // Check if email exists
        $email_escaped = mysqli_real_escape_string($conn, $email);
        $checkQuery = "SELECT id FROM users WHERE email = '$email_escaped'";
        $checkResult = mysqli_query($conn, $checkQuery);
        
        if (!$checkResult) {
            $error = 'Database error: ' . mysqli_error($conn);
        } elseif (mysqli_fetch_assoc($checkResult)) {
            $error = 'Email already registered.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $hashedPassword_escaped = mysqli_real_escape_string($conn, $hashedPassword);
            $firstName_escaped = mysqli_real_escape_string($conn, $first_name);
            $lastName_escaped = mysqli_real_escape_string($conn, $last_name);
            
            $insertQuery = "
                INSERT INTO users (email, password, first_name, last_name) 
                VALUES ('$email_escaped', '$hashedPassword_escaped', '$firstName_escaped', '$lastName_escaped')
            ";
            
            if (mysqli_query($conn, $insertQuery)) {
                $success = 'Registration successful! <a href="login.php" class="text-white fw-bold">Login here</a>';
            } else {
                $error = 'Database error: ' . mysqli_error($conn);
            }
        }
    }
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
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body p-5">
                    <h2 class="card-title text-center mb-4 text-orange">Create Account</h2>
                    
                    <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo $success; ?>
                    </div>
                    <?php else: ?>
                    
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary-custom w-100 mb-3">Register</button>
                    </form>
                    
                    <?php endif; ?>
                    
                    <p class="text-center">
                        Already have an account? <a href="login.php" class="text-orange fw-bold">Login here</a>
                    </p>
                    <p class="text-center">
                        <a href="index.php" class="text-secondary">Back to Home</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>