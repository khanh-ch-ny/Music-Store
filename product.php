<?php
require_once 'includes/functions/functions.php';
require_once 'includes/functions/template.php';

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header('Location: products.php');
    exit;
}

// Get product details
$product = getProduct($product_id);

if (!$product) {
    header('Location: products.php');
    exit;
}

// Get product reviews
$reviews = getProductReviews($product_id);

// Prepare template data
$data = [
    'product_id' => $product['id'],
    'product_name' => $product['name'],
    'product_image' => $product['image_url'],
    'product_price' => formatPrice($product['price']),
    'product_description' => $product['description'],
    'product_stock' => $product['stock'],
    'category_name' => $product['category_name'],
    'reviews' => renderReviewsList($reviews),
    'review_form' => isLoggedIn() ? renderReviewForm($product_id) : '<p>Vui lòng <a href="login.php">đăng nhập</a> để đánh giá sản phẩm.</p>',
    'user_links' => renderHeaderIcons(),
    'navigation_menu' => renderNavigationMenu()
];

// Render template
echo renderTemplate('product', $data); 