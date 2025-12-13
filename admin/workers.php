<?php
$pageTitle = 'Workers Management - Admin';
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
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$error = '';
$success = '';

// Handle add/edit worker
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_worker') {
        $firstName = sanitizeInput($_POST['first_name'] ?? '');
        $lastName = sanitizeInput($_POST['last_name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $hireDate = sanitizeInput($_POST['hire_date'] ?? '');
        $roleId = intval($_POST['role_id'] ?? 0);
        $createLogin = isset($_POST['create_login']) ? 1 : 0;
        
        if (empty($firstName) || empty($lastName) || empty($email) || $roleId <= 0) {
            $error = 'All required fields must be filled.';
        } else {
            $firstName_escaped = mysqli_real_escape_string($conn, $firstName);
            $lastName_escaped = mysqli_real_escape_string($conn, $lastName);
            $email_escaped = mysqli_real_escape_string($conn, $email);
            $phone_escaped = mysqli_real_escape_string($conn, $phone);
            $hireDate_escaped = mysqli_real_escape_string($conn, $hireDate);
            
            $insertQuery = "
                INSERT INTO workers (admin_id, worker_role_id, first_name, last_name, email, phone, hire_date)
                VALUES ($adminId, $roleId, '$firstName_escaped', '$lastName_escaped', '$email_escaped', '$phone_escaped', '$hireDate_escaped')
            ";
            
            if (mysqli_query($conn, $insertQuery)) {
                $workerId = mysqli_insert_id($conn);
                $success = 'Worker added successfully!';
                
                // Create login account if checkbox is checked
                if ($createLogin) {
                    // Generate username from first and last name
                    $username = strtolower($firstName_escaped . '_' . $lastName_escaped);
                    
                    // Generate temporary password
                    $tempPassword = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 10);
                    $hashedPassword = password_hash($tempPassword, PASSWORD_BCRYPT);
                    $hashedPassword_escaped = mysqli_real_escape_string($conn, $hashedPassword);
                    
                    $loginQuery = "
                        INSERT INTO admin_users (username, password, email, role, is_active)
                        VALUES ('$username', '$hashedPassword_escaped', '$email_escaped', 'worker', 1)
                    ";
                    
                    if (mysqli_query($conn, $loginQuery)) {
                        $success .= '<br><strong>Login Credentials Created:</strong><br>';
                        $success .= 'Username: <code>' . htmlspecialchars($username) . '</code><br>';
                        $success .= 'Temporary Password: <code>' . htmlspecialchars($tempPassword) . '</code><br>';
                        $success .= '<small class="text-muted">Worker should change password on first login</small>';
                        
                        // Log activity
                        $logQuery = "INSERT INTO activity_logs (admin_id, action) VALUES ($adminId, 'Created login account for worker: $firstName_escaped $lastName_escaped')";
                        mysqli_query($conn, $logQuery);
                    } else {
                        $error = 'Worker added but login account creation failed: ' . mysqli_error($conn);
                    }
                } else {
                    // Log activity
                    $logQuery = "INSERT INTO activity_logs (admin_id, action) VALUES ($adminId, 'Added new worker: $firstName_escaped $lastName_escaped')";
                    mysqli_query($conn, $logQuery);
                }
            } else {
                $error = 'Error adding worker: ' . mysqli_error($conn);
            }
        }
    } elseif ($action === 'add_role') {
        $roleName = sanitizeInput($_POST['role_name'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        
        if (empty($roleName)) {
            $error = 'Role name is required.';
        } else {
            $roleName_escaped = mysqli_real_escape_string($conn, $roleName);
            $description_escaped = mysqli_real_escape_string($conn, $description);
            
            $insertQuery = "
                INSERT INTO worker_roles (name, description)
                VALUES ('$roleName_escaped', '$description_escaped')
            ";
            
            if (mysqli_query($conn, $insertQuery)) {
                $success = 'Role added successfully!';
            } else {
                $error = 'Error adding role: ' . mysqli_error($conn);
            }
        }
    }
}

// Get workers
$workersQuery = "
    SELECT w.*, wr.name as role_name 
    FROM workers w 
    JOIN worker_roles wr ON w.worker_role_id = wr.id
    WHERE w.is_active = 1
    ORDER BY w.created_at DESC 
    LIMIT " . intval($perPage) . " OFFSET " . intval($offset);
$workersResult = mysqli_query($conn, $workersQuery);
if (!$workersResult) {
    die('Database error: ' . mysqli_error($conn));
}
$workers = [];
while ($row = mysqli_fetch_assoc($workersResult)) {
    $workers[] = $row;
}

// Get total
$countQuery = "SELECT COUNT(*) as cnt FROM workers WHERE is_active = 1";
$countResult = mysqli_query($conn, $countQuery);
if (!$countResult) {
    die('Database error: ' . mysqli_error($conn));
}
$countRow = mysqli_fetch_assoc($countResult);
$total = $countRow['cnt'];
$totalPages = ceil($total / $perPage);

// Get all roles
$rolesQuery = "SELECT * FROM worker_roles WHERE is_active = 1 ORDER BY name";
$rolesResult = mysqli_query($conn, $rolesQuery);
if (!$rolesResult) {
    die('Database error: ' . mysqli_error($conn));
}
$roles = [];
while ($row = mysqli_fetch_assoc($rolesResult)) {
    $roles[] = $row;
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
                    <li class="nav-item"><a class="nav-link" href="images.php">Images</a></li>
                    <li class="nav-item"><a class="nav-link active" href="workers.php" style="color: #FF6B35; font-weight: 600; border-left: 4px solid #FF6B35; padding-left: 12px;">Workers</a></li>
                    <li class="nav-item"><a class="nav-link" href="orders.php">Orders</a></li>
                    <li class="nav-item"><a class="nav-link" href="customers.php">Customers</a></li>
                    <li class="nav-item"><a class="nav-link" href="settings.php">Settings</a></li>
                </ul>
            </div>
        </nav>

        <main class="col-md-10 ms-sm-auto px-md-4">
            <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
                <h2>Workers Management</h2>
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

            <!-- Add Worker Card -->
            <div class="card mb-4">
                <div class="card-header bg-orange text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">➕ Add New Worker</h5>
                    <button type="button" class="btn btn-light btn-sm" data-bs-toggle="collapse" data-bs-target="#addWorkerForm" aria-expanded="false">
                        Toggle Form
                    </button>
                </div>
                <div class="collapse" id="addWorkerForm">
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="add_worker">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="phone" name="phone">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="role_id" class="form-label">Role <span class="text-danger">*</span></label>
                                    <select class="form-control" id="role_id" name="role_id" required>
                                        <option value="">-- Select a role --</option>
                                        <?php foreach ($roles as $role): ?>
                                        <option value="<?php echo $role['id']; ?>">
                                            <?php echo htmlspecialchars($role['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="hire_date" class="form-label">Hire Date</label>
                                    <input type="date" class="form-control" id="hire_date" name="hire_date">
                                </div>
                            </div>

                            <div class="mb-3 p-3 bg-light rounded border-left border-orange">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="create_login" name="create_login">
                                    <label class="form-check-label" for="create_login">
                                        <strong>🔐 Create Login Account</strong>
                                        <br>
                                        <small class="text-muted">Auto-generate username and temporary password for this worker</small>
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <span>➕</span> Add Worker
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="workers-tab" data-bs-toggle="tab" data-bs-target="#workers" type="button" role="tab">Workers</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="roles-tab" data-bs-toggle="tab" data-bs-target="#roles" type="button" role="tab">Roles</button>
                </li>
            </ul>

            <div class="tab-content">
                <!-- Workers List -->
                <div class="tab-pane fade show active" id="workers" role="tabpanel">
                    <div class="card">
                        <div class="card-header bg-orange text-white">
                            <h5 class="mb-0">Active Workers</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Role</th>
                                        <th>Hire Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($workers)): ?>
                                        <?php foreach ($workers as $worker): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($worker['first_name'] . ' ' . $worker['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($worker['email']); ?></td>
                                            <td><?php echo htmlspecialchars($worker['phone'] ?? 'N/A'); ?></td>
                                            <td><span class="badge bg-info"><?php echo htmlspecialchars($worker['role_name']); ?></span></td>
                                            <td><?php echo $worker['hire_date'] ? date('M d, Y', strtotime($worker['hire_date'])) : 'N/A'; ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-primary">Edit</button>
                                                <button class="btn btn-sm btn-danger">Deactivate</button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">No workers found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Roles List -->
                <div class="tab-pane fade" id="roles" role="tabpanel">
                    <div class="card">
                        <div class="card-header bg-orange text-white">
                            <h5 class="mb-0">Worker Roles</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Role Name</th>
                                        <th>Description</th>
                                        <th>Workers</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($roles)): ?>
                                        <?php foreach ($roles as $role): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($role['name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($role['description'] ?? 'N/A'); ?></td>
                                            <td>
                                                <?php
                                                $workerCountQuery = "SELECT COUNT(*) as cnt FROM workers WHERE worker_role_id = " . $role['id'];
                                                $workerCountResult = mysqli_query($conn, $workerCountQuery);
                                                $workerCountRow = mysqli_fetch_assoc($workerCountResult);
                                                echo $workerCountRow['cnt'];
                                                ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary">Edit</button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">No roles found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
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
