<?php
$pageTitle = 'Contact Us - Boutique Fashion Store';
require_once 'config/database.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $subject = sanitizeInput($_POST['subject'] ?? '');
    $message = sanitizeInput($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // In production, you would send an email or save to database
        // For now, just show success message
        $success = 'Thank you for contacting us! We will get back to you soon.';
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
    <h1 class="text-center text-orange mb-5">Get in Touch</h1>
    
    <div class="row">
        <!-- Contact Form -->
        <div class="col-lg-7 mb-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Send us a Message</h5>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone">
                        </div>
                        
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject *</label>
                            <input type="text" class="form-control" id="subject" name="subject" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">Message *</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary-custom w-100">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Contact Information -->
        <div class="col-lg-5">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="text-orange mb-3">📍 Visit Our Store</h5>
                    <p>
                        Kyanja along Kungu Road<br>
                        Next to Harusi Driving School<br>
                        Kyanja
                    </p>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="text-orange mb-3">📞 Call Us</h5>
                    <p>
                        <strong>Main:</strong> +256 (0)777 043887<br>
                        <strong>Support:</strong> +1 (555) 987-6543<br>
                        <strong>Mon - Fri:</strong> 7:30AM - 9:30PM EST<br>
                        <strong>Sat - Sun:</strong> 7:30AM - 9:30PM EST
                    </p>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="text-orange mb-3">✉️ Email Us</h5>
                    <p>
                        <strong>General:</strong> info@shestylesboutique.com<br>
                        <strong>Support:</strong> support@shestylesboutique.com<br>
                        <strong>Orders:</strong> orders@shestylesboutique.com
                    </p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <h5 class="text-orange mb-3">🕐 Business Hours</h5>
                    <p>
                        <strong>Monday - Friday:</strong> 7:30 AM - 9:30 PM<br>
                        <strong>Saturday:</strong> 10:00 AM - 4:00 PM<br>
                        <strong>Sunday:</strong> Closed<br>
                        <small class="text-muted">Closed on holidays</small>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <hr class="my-5">

    <h2 class="text-center text-orange mb-4">Follow Us</h2>
    <div class="text-center mb-5">
        <a href="#" class="btn btn-outline-secondary btn-sm me-2">Facebook</a>
        <a href="#" class="btn btn-outline-secondary btn-sm me-2">Instagram</a>
        <a href="#" class="btn btn-outline-secondary btn-sm me-2">Twitter</a>
        <a href="#" class="btn btn-outline-secondary btn-sm">Pinterest</a>
    </div>

    <div class="text-center">
        <h4 class="text-orange mb-3">Frequently Asked Questions</h4>
        <p class="mb-3">Check out our <a href="#" class="text-orange">FAQ page</a> for answers to common questions.</p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
