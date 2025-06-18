<?php
require_once 'includes/config/db.php';
require_once 'includes/functions/functions.php';
require_once 'includes/functions/template.php';

// Get featured products
$featured_products = getFeaturedProducts();

// Get categories
$categories = getCategories();

// Get products
$products = getProducts();

// Prepare template data
$data = [
    'title' => 'Trang chủ',
    'active_home' => true,
    'products_list' => renderProductsList($products),
    'categories_list' => renderCategoriesList($categories),
    'featured_products' => renderFeaturedProducts($featured_products),
    'user_links' => isLoggedIn() ? 
        '<li><a href="/Music-Store/cart.php">Giỏ hàng</a></li><li><a href="/Music-Store/logout.php">Đăng xuất</a></li>' : 
        '<li><a href="/Music-Store/login.php">Đăng nhập</a></li><li><a href="/Music-Store/register.php">Đăng ký</a></li>'
];

// Render template
echo renderTemplate('home', $data); 