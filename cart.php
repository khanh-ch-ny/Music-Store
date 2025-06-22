<?php
require_once 'includes/config/config.php';
require_once 'includes/functions/functions.php';
require_once 'includes/functions/template.php';

// This page is for displaying the cart. Actions are handled by AJAX.

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('/Music-Store/login.php');
}

$user_id = $_SESSION['user_id'];

// Get cart items and total
$cart_items = getCartItems($user_id);
$cart_total = calculateCartTotal($user_id);

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