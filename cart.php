<?php
require_once 'includes/config/config.php';
require_once 'includes/functions/functions.php';
require_once 'includes/functions/template.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_to_cart'])) {
        $product_id = (int)$_POST['product_id'];
        $quantity = (int)$_POST['quantity'];
        
        // Check if product exists and has enough stock
        $product = getProductData($product_id);
        if (!$product) {
            $error = 'Sản phẩm không tồn tại.';
        } elseif ($product['stock'] < $quantity) {
            $error = 'Số lượng sản phẩm trong kho không đủ.';
        } else {
            // Check if product already in cart
            $sql = "SELECT * FROM cart WHERE user_id = ? AND product_id = ?";
            $cart_item = getRow($sql, [$user_id, $product_id], 'ii');
            
            if ($cart_item) {
                // Update quantity if product already in cart
                $new_quantity = $cart_item['quantity'] + $quantity;
                if ($new_quantity > $product['stock']) {
                    $error = 'Số lượng sản phẩm trong kho không đủ.';
                } else {
                    $sql = "UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?";
                    executeQuery($sql, [$new_quantity, $user_id, $product_id], 'iii');
                    $success = 'Đã cập nhật giỏ hàng.';
                }
            } else {
                // Add new item to cart
                $sql = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)";
                executeQuery($sql, [$user_id, $product_id, $quantity], 'iii');
                $success = 'Đã thêm sản phẩm vào giỏ hàng.';
            }
        }
    } elseif (isset($_POST['update_quantity'])) {
        $product_id = (int)$_POST['product_id'];
        $quantity = (int)$_POST['quantity'];
        
        // Check if product exists and has enough stock
        $product = getProductData($product_id);
        if (!$product) {
            $error = 'Sản phẩm không tồn tại.';
        } elseif ($product['stock'] < $quantity) {
            $error = 'Số lượng sản phẩm trong kho không đủ.';
        } else {
            $sql = "UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?";
            executeQuery($sql, [$quantity, $user_id, $product_id], 'iii');
            $success = 'Đã cập nhật số lượng sản phẩm.';
        }
    } elseif (isset($_POST['remove_item'])) {
        $product_id = (int)$_POST['product_id'];
        $sql = "DELETE FROM cart WHERE user_id = ? AND product_id = ?";
        executeQuery($sql, [$user_id, $product_id], 'ii');
        $success = 'Đã xóa sản phẩm khỏi giỏ hàng.';
    }
}

// Get cart items and total
$cart_items = getCartItems($user_id);
$cart_total = calculateCartTotal($user_id);

// Prepare template data
$data = [
    'error_message' => $error ? renderError($error) : '',
    'success_message' => $success ? renderSuccess($success) : '',
    'cart_content' => renderCartItems($cart_items),
    'cart_total' => formatPrice($cart_total),
    'user_links' => renderUserLinks()
];

// Render template
echo renderTemplate('cart', $data); 