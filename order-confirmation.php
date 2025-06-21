<?php
require_once 'includes/config/config.php';
require_once 'includes/functions/functions.php';
require_once 'includes/functions/template.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('/Music-Store/login.php');
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id <= 0) {
    redirect('/Music-Store/account.php#orders');
}

// Get order details
$order = getOrder($order_id, $user_id);

// If order doesn't exist or doesn't belong to the user, redirect
if (!$order) {
    redirect('/Music-Store/account.php#orders');
}

// Prepare template data
$data = [
    'title' => 'Xác nhận đơn hàng',
    'user_links' => renderUserLinks(),
    'order' => $order,
];

// Render template
echo renderTemplate('order-confirmation', $data); 