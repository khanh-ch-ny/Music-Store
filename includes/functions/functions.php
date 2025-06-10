<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

// Redirect function
function redirect($url) {
    header("Location: $url");
    exit();
}

// Display error message
function displayError($message) {
    return "<div class='alert alert-danger'>$message</div>";
}

// Display success message
function displaySuccess($message) {
    return "<div class='alert alert-success'>$message</div>";
}

// Get user data
function getUserData($user_id) {
    $sql = "SELECT * FROM users WHERE id = $user_id";
    return getRow($sql);
}

// Get product data
function getProductData($product_id) {
    $sql = "SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.id = $product_id";
    return getRow($sql);
}

// Get cart items for user
function getCartItems($user_id) {
    $sql = "SELECT c.*, p.name, p.price, p.image_url 
            FROM cart c 
            LEFT JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = $user_id";
    return getRows($sql);
}

// Calculate cart total
function calculateCartTotal($user_id) {
    $sql = "SELECT SUM(c.quantity * p.price) as total 
            FROM cart c 
            LEFT JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = $user_id";
    $result = getRow($sql);
    return $result['total'] ?? 0;
}

// Format price
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . ' đ';
}

// Validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Generate random string
function generateRandomString($length = 10) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', 
        ceil($length/strlen($x)))), 1, $length);
}

// Upload image
function uploadImage($file, $targetDir = '../assets/images/products/') {
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $fileName = basename($file['name']);
    $targetPath = $targetDir . $fileName;
    $fileType = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
    
    // Check if image file is actual image
    if (!getimagesize($file['tmp_name'])) {
        return false;
    }
    
    // Check file size (5MB max)
    if ($file['size'] > 5000000) {
        return false;
    }
    
    // Allow certain file formats
    if ($fileType != "jpg" && $fileType != "png" && $fileType != "jpeg") {
        return false;
    }
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $fileName;
    }
    
    return false;
}

// Helper functions
function sanitize($input) {
    global $conn;
    return $conn->real_escape_string(trim($input));
}

// Database functions
function executeQuery($sql) {
    global $conn;
    $result = $conn->query($sql);
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }
    return $result;
}

function getRow($sql) {
    $result = executeQuery($sql);
    return $result->fetch_assoc();
}

function getRows($sql) {
    $result = executeQuery($sql);
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

// User functions
function getCategory($category_id) {
    $sql = "SELECT * FROM categories WHERE id = $category_id";
    return getRow($sql);
}

function getCategories() {
    $sql = "SELECT * FROM categories ORDER BY name";
    return getRows($sql);
}

function getFeaturedProducts() {
    $sql = "SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.stock > 0 
            ORDER BY p.created_at DESC 
            LIMIT 6";
    return getRows($sql);
}

// Cart functions
function getCartItemCount($user_id) {
    $sql = "SELECT COUNT(*) as count FROM cart WHERE user_id = $user_id";
    $result = getRow($sql);
    return $result['count'];
}

// Template functions
function renderError($message) {
    return '<div class="alert alert-danger">' . htmlspecialchars($message) . '</div>';
}

function renderSuccess($message) {
    return '<div class="alert alert-success">' . htmlspecialchars($message) . '</div>';
}

function renderCartItems($items) {
    if (empty($items)) {
        return '<p class="empty-cart">Giỏ hàng của bạn đang trống.</p>';
    }

    $html = '<div class="cart-items">';
    foreach ($items as $item) {
        $html .= '<div class="cart-item">';
        $html .= '<div class="cart-item-image">';
        $html .= '<img src="' . (isset($item['image_url']) ? $item['image_url'] : 'images/default.png') . '" alt="' . $item['name'] . '">';
        $html .= '</div>';
        $html .= '<div class="cart-item-info">';
        $html .= '<h3>' . $item['name'] . '</h3>';
        $html .= '<p class="price">' . formatPrice($item['price']) . '</p>';
        $html .= '<div class="quantity-controls">';
        $html .= '<form method="POST" action="cart.php" class="update-quantity-form">';
        $html .= '<input type="hidden" name="cart_id" value="' . $item['id'] . '">';
        $html .= '<input type="number" name="quantity" value="' . $item['quantity'] . '" min="1" max="99">';
        $html .= '<button type="submit" name="update_quantity" class="btn btn-sm">Cập nhật</button>';
        $html .= '</form>';
        $html .= '<form method="POST" action="cart.php" class="remove-item-form">';
        $html .= '<input type="hidden" name="cart_id" value="' . $item['id'] . '">';
        $html .= '<button type="submit" name="remove_item" class="btn btn-sm btn-danger">Xóa</button>';
        $html .= '</form>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }
    $html .= '</div>';
    
    return $html;
}

function renderUserLinks() {
    $html = '';
    if (isLoggedIn()) {
        $cart_count = getCartItemCount($_SESSION['user_id']);
        $html .= '<li><a href="cart.php" class="cart-link"><i class="fas fa-shopping-cart"></i> Giỏ hàng <span class="cart-count">' . $cart_count . '</span></a></li>';
        $html .= '<li><a href="profile.php">Tài khoản</a></li>';
        if (isAdmin()) {
            $html .= '<li><a href="admin/index.php">Quản trị</a></li>';
        }
        $html .= '<li><a href="logout.php">Đăng xuất</a></li>';
    } else {
        $html .= '<li><a href="login.php">Đăng nhập</a></li>';
        $html .= '<li><a href="register.php">Đăng ký</a></li>';
    }
    return $html;
}

// Product Functions
function getProduct($id) {
    global $conn;
    $id = (int)$id;
    $sql = "SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.id = $id";
    return getRow($sql);
}

function getProducts($category_id = 0, $sort = 'newest', $page = 1, $per_page = 12) {
    global $conn;
    $offset = ($page - 1) * $per_page;
    
    $where = $category_id > 0 ? "WHERE p.category_id = $category_id" : "";
    
    $order_by = match($sort) {
        'price_asc' => 'p.price ASC',
        'price_desc' => 'p.price DESC',
        'name_asc' => 'p.name ASC',
        default => 'p.created_at DESC'
    };
    
    $sql = "SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            $where 
            ORDER BY $order_by 
            LIMIT $offset, $per_page";
            
    return getRows($sql);
}

function getTotalProducts($category_id = 0) {
    global $conn;
    $where = $category_id > 0 ? "WHERE category_id = $category_id" : "";
    $sql = "SELECT COUNT(*) as total FROM products $where";
    $result = getRow($sql);
    return $result['total'];
}

function getProductReviews($product_id) {
    global $conn;
    $product_id = (int)$product_id;
    $sql = "SELECT r.*, u.username 
            FROM reviews r 
            LEFT JOIN users u ON r.user_id = u.id 
            WHERE r.product_id = $product_id 
            ORDER BY r.created_at DESC";
    return getRows($sql);
}

function renderProductsList($products) {
    if (empty($products)) {
        return '<p class="no-products">Không tìm thấy sản phẩm nào.</p>';
    }

    $html = '';
    foreach ($products as $product) {
        $html .= '<div class="product-card">';
        $html .= '<a href="product.php?id=' . $product['id'] . '" class="product-link">';
        $html .= '<div class="product-image">';
        $html .= '<img src="' . (isset($product['image_url']) ? $product['image_url'] : 'images/default.png') . '" alt="' . $product['name'] . '">';
        $html .= '</div>';
        $html .= '<div class="product-info">';
        $html .= '<h3 class="product-title">' . $product['name'] . '</h3>';
        $html .= '<p class="product-category">' . $product['category_name'] . '</p>';
        $html .= '<p class="product-price">' . formatPrice($product['price']) . '</p>';
        $html .= '</div>';
        $html .= '</a>';
        
        if ($product['stock'] > 0) {
            $html .= '<form method="POST" action="cart.php" class="add-to-cart-form">';
            $html .= '<input type="hidden" name="product_id" value="' . $product['id'] . '">';
            $html .= '<input type="hidden" name="quantity" value="1">';
            $html .= '<button type="submit" name="add_to_cart" class="btn btn-primary">';
            $html .= '<i class="fas fa-cart-plus"></i> Thêm vào giỏ';
            $html .= '</button>';
            $html .= '</form>';
        } else {
            $html .= '<p class="out-of-stock">Hết hàng</p>';
        }
        
        $html .= '</div>';
    }
    
    return $html;
}

function renderReviewsList($reviews) {
    if (empty($reviews)) {
        return '<p class="no-reviews">Chưa có đánh giá nào cho sản phẩm này.</p>';
    }

    $html = '<div class="reviews-list">';
    foreach ($reviews as $review) {
        $html .= '<div class="review-item">';
        $html .= '<div class="review-header">';
        $html .= '<div class="review-user">' . $review['username'] . '</div>';
        $html .= '<div class="review-rating">';
        for ($i = 1; $i <= 5; $i++) {
            $html .= '<i class="fas fa-star ' . ($i <= $review['rating'] ? 'active' : '') . '"></i>';
        }
        $html .= '</div>';
        $html .= '<div class="review-date">' . date('d/m/Y', strtotime($review['created_at'])) . '</div>';
        $html .= '</div>';
        $html .= '<div class="review-content">' . nl2br($review['comment']) . '</div>';
        $html .= '</div>';
    }
    $html .= '</div>';
    
    return $html;
}

function renderReviewForm($product_id) {
    $html = '<form method="POST" action="product.php?id=' . $product_id . '" class="review-form">';
    $html .= '<h3>Viết đánh giá</h3>';
    $html .= '<div class="form-group">';
    $html .= '<label>Đánh giá của bạn:</label>';
    $html .= '<div class="rating-selector">';
    for ($i = 5; $i >= 1; $i--) {
        $html .= '<input type="radio" name="rating" value="' . $i . '" id="star' . $i . '" required>';
        $html .= '<label for="star' . $i . '"><i class="fas fa-star"></i></label>';
    }
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div class="form-group">';
    $html .= '<label for="comment">Nhận xét của bạn:</label>';
    $html .= '<textarea id="comment" name="comment" rows="4" required></textarea>';
    $html .= '</div>';
    $html .= '<button type="submit" name="submit_review" class="btn btn-primary">Gửi đánh giá</button>';
    $html .= '</form>';
    
    return $html;
}
?> 