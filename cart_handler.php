<?php
require_once 'config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
$size = $_POST['size'] ?? '';
$color = $_POST['color'] ?? '';

// Initialize cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

switch ($action) {
    case 'add':
        if ($productId > 0 && $quantity > 0) {
            $itemKey = generateItemKey($productId, $size, $color);
            
            // Check if item exists
            $found = false;
            foreach ($_SESSION['cart'] as &$item) {
                if (generateItemKey($item['product_id'], $item['size'] ?? '', $item['color'] ?? '') === $itemKey) {
                    $item['quantity'] += $quantity;
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $_SESSION['cart'][] = [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'size' => $size,
                    'color' => $color
                ];
            }
            
            echo json_encode(['success' => true, 'message' => 'Added to cart']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid product']);
        }
        break;
    
    case 'update':
        if ($productId > 0) {
            $itemKey = generateItemKey($productId, $size, $color);
            
            foreach ($_SESSION['cart'] as &$item) {
                if (generateItemKey($item['product_id'], $item['size'] ?? '', $item['color'] ?? '') === $itemKey) {
                    if ($quantity > 0) {
                        $item['quantity'] = $quantity;
                    }
                    break;
                }
            }
            
            echo json_encode(['success' => true, 'message' => 'Cart updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid product']);
        }
        break;
    
    case 'remove':
        if ($productId > 0) {
            $itemKey = generateItemKey($productId, $size, $color);
            
            foreach ($_SESSION['cart'] as $index => $item) {
                if (generateItemKey($item['product_id'], $item['size'] ?? '', $item['color'] ?? '') === $itemKey) {
                    unset($_SESSION['cart'][$index]);
                    break;
                }
            }
            
            // Reindex array
            $_SESSION['cart'] = array_values($_SESSION['cart']);
            
            echo json_encode(['success' => true, 'message' => 'Removed from cart']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid product']);
        }
        break;
    
    case 'clear':
        $_SESSION['cart'] = [];
        echo json_encode(['success' => true, 'message' => 'Cart cleared']);
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function generateItemKey($productId, $size, $color) {
    return md5($productId . '-' . $size . '-' . $color);
}
?>
