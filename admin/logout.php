<?php
require_once '../config/database.php';

// Clear admin session
session_destroy();

// Redirect to admin login
header('Location: login.php');
exit;
?>
