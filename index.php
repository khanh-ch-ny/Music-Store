<?php
require_once 'includes/functions/functions.php';
require_once 'includes/functions/template.php';

// Get category filter
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Get products
$products = getProducts($category_id);

// Get categories
$categories = getCategories();

// Get featured products
$featured_products = getFeaturedProducts();

// Prepare template data
$data = [
    'products' => $products,
    'categories_list' => $categories,
    'featured_products' => $featured_products,
    'current_category' => $category_id,
    'cart_link' => isLoggedIn() ? 'cart.php' : 'login.php',
    'user_links' => renderUserLinks()
];

// Render template
echo renderTemplate('index', $data); 