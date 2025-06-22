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
    'user_links' => renderHeaderIcons(),
    'navigation_menu' => renderNavigationMenu()
];

// Render template
echo renderTemplate('home', $data); 