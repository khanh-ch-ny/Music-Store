<?php
require_once 'includes/functions/functions.php';
require_once 'includes/functions/template.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$cart_items = getCartItems($user_id);
$cart_total = calculateCartTotal($user_id);

// Prepare template data
$data = [
    'cart_content' => renderCartItems($cart_items)
];

// Render template
echo renderTemplate('cart', $data); 