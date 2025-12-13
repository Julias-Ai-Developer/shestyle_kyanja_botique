<?php
// This script adds the new worker roles to the database
// Run once and then delete this file

require_once dirname(dirname(__FILE__)) . '/config/database.php';

// Check if roles already exist
$checkQuery = "SELECT COUNT(*) as cnt FROM worker_roles WHERE name IN ('Boutique Manager', 'Boutique Assistant')";
$result = mysqli_query($conn, $checkQuery);
$row = mysqli_fetch_assoc($result);

if ($row['cnt'] > 0) {
    echo "Roles already exist in the database.";
} else {
    // First, delete old roles if they exist
    $deleteQuery = "DELETE FROM worker_roles WHERE name IN ('Sales Associate', 'Store Manager', 'Inventory Manager', 'Visual Merchandiser')";
    mysqli_query($conn, $deleteQuery);
    
    // Add new roles
    $rolesData = [
        [
            'name' => 'Boutique Manager',
            'description' => 'Full administrative access to store operations, staff management, and inventory',
            'permissions' => json_encode([
                'view_dashboard' => true,
                'manage_products' => true,
                'manage_inventory' => true,
                'manage_orders' => true,
                'manage_staff' => true,
                'view_reports' => true,
                'manage_banners' => true
            ])
        ],
        [
            'name' => 'Boutique Assistant',
            'description' => 'Support staff with access to product management, orders, and basic inventory',
            'permissions' => json_encode([
                'view_dashboard' => true,
                'manage_products' => true,
                'manage_inventory' => true,
                'manage_orders' => true,
                'manage_staff' => false,
                'view_reports' => false,
                'manage_banners' => false
            ])
        ]
    ];
    
    foreach ($rolesData as $role) {
        $name = mysqli_real_escape_string($conn, $role['name']);
        $description = mysqli_real_escape_string($conn, $role['description']);
        $permissions = mysqli_real_escape_string($conn, $role['permissions']);
        
        $query = "INSERT INTO worker_roles (name, description, permissions, is_active) 
                 VALUES ('$name', '$description', '$permissions', 1)";
        
        if (mysqli_query($conn, $query)) {
            echo "✓ Role '$role[name]' added successfully!<br>";
        } else {
            echo "✗ Error adding role '$role[name]': " . mysqli_error($conn) . "<br>";
        }
    }
    
    echo "<br><strong>All roles have been added successfully!</strong>";
}

mysqli_close($conn);
?>
