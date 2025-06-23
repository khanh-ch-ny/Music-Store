<?php
require_once 'includes/config/config.php';
require_once 'includes/functions/functions.php';
require_once 'includes/functions/template.php';

session_start();

// Lấy dữ liệu giỏ hàng cho cả khách đã đăng nhập và chưa đăng nhập
if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $cart_items = getCartItems($user_id);
    $cart_total = calculateCartTotal($user_id);
} else {
    $cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
    $cart_total = 0;
    foreach ($cart_items as $item) {
        $cart_total += $item['price'] * $item['quantity'];
    }
}

// Prepare template data
$data = [
    'title' => 'Giỏ hàng',
    'cart_items' => $cart_items,
    'cart_total' => $cart_total,
    'user_links' => renderHeaderIcons(),
    'navigation_menu' => renderNavigationMenu()
];

// Render template
echo renderTemplate('cart', $data); 