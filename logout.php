<?php
require_once 'config/database.php';

// Clear session
session_destroy();

// Redirect to home
header('Location: index.php');
exit;
?>
