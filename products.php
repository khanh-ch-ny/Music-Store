<?php
require_once 'includes/functions/functions.php';
require_once 'includes/functions/template.php';

// Get filters from URL
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'newest';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;

// Get categories for filter
$categories = getCategories();
$category_options = '';
foreach ($categories as $cat) {
    $selected = $category_id === $cat['id'] ? 'selected' : '';
    $category_options .= "<option value='{$cat['id']}' {$selected}>{$cat['name']}</option>";
}

// Get products with filters
$products = getProducts($category_id, $sort, $page, $per_page);
$total_products = getTotalProducts($category_id);
$total_pages = ceil($total_products / $per_page);

// Generate pagination
$pagination = '';
if ($total_pages > 1) {
    $pagination .= '<div class="pagination-links">';
    if ($page > 1) {
        $pagination .= "<a href='?page=" . ($page - 1) . "&category={$category_id}&sort={$sort}' class='btn btn-outline'>Trang trước</a>";
    }
    $pagination .= "<span class='current-page'>Trang {$page} / {$total_pages}</span>";
    if ($page < $total_pages) {
        $pagination .= "<a href='?page=" . ($page + 1) . "&category={$category_id}&sort={$sort}' class='btn btn-outline'>Trang sau</a>";
    }
    $pagination .= '</div>';
}

// Prepare template data
$data = [
    'category_options' => $category_options,
    'products_list' => renderProductsList($products),
    'pagination' => $pagination,
    'user_links' => renderUserLinks()
];

// Render template
echo renderTemplate('products', $data); 