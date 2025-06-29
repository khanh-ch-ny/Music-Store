<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Redirect function with session check
function redirect($url) {
    if (!headers_sent()) {
        header("Location: $url");
        exit();
    } else {
        echo "<script>window.location.href='$url';</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=$url'></noscript>";
        exit();
    }
}

// Display error message
function displayError($message) {
    return "<div class='alert alert-danger'>" . htmlspecialchars($message) . "</div>";
}

// Display success message
function displaySuccess($message) {
    return "<div class='alert alert-success'>" . htmlspecialchars($message) . "</div>";
}

// Get user data with prepared statement
function getUserData($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Get product data with prepared statement
function getProductData($product_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT p.*, c.name as category_name 
                           FROM products p 
                           LEFT JOIN categories c ON p.category_id = c.id 
                           WHERE p.id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Get cart items with prepared statement
function getCartItems($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT c.*, p.name, p.price, p.image_url, p.stock 
                           FROM cart c 
                           LEFT JOIN products p ON c.product_id = p.id 
                           WHERE c.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    return $items;
}

// Calculate cart total with prepared statement
function calculateCartTotal($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT SUM(c.quantity * p.price) as total 
                           FROM cart c 
                           LEFT JOIN products p ON c.product_id = p.id 
                           WHERE c.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'] ?? 0;
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

// Upload image with security checks
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
    
    // Generate unique filename
    $fileName = uniqid() . '.' . $fileType;
    $targetPath = $targetDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $fileName;
    }
    
    return false;
}

// Helper functions
function sanitize($input) {
    global $conn;
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return $conn->real_escape_string(trim($input));
}

// Database functions with error handling
function executeQuery($sql, $params = [], $types = '') {
    global $conn;
    try {
        if (!empty($params)) {
            $stmt = $conn->prepare($sql);
            if ($types) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            return $stmt;
        } else {
            $result = $conn->query($sql);
            if (!$result) {
                throw new Exception("Query failed: " . $conn->error);
            }
            return $result;
        }
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        throw $e;
    }
}

function getRow($sql, $params = [], $types = '') {
    $result = executeQuery($sql, $params, $types);
    if ($result instanceof mysqli_stmt) {
        $result = $result->get_result();
    }
    return $result->fetch_assoc();
}

function getRows($sql, $params = [], $types = '') {
    $result = executeQuery($sql, $params, $types);
    if ($result instanceof mysqli_stmt) {
        $result = $result->get_result();
    }
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

function getUserOrders($user_id) {
    global $conn;
    $orders = getRows("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC", [$user_id], 'i');

    if (empty($orders)) {
        return [];
    }

    $order_ids = array_column($orders, 'id');
    $placeholders = rtrim(str_repeat('?,', count($order_ids)), ',');
    $types = str_repeat('i', count($order_ids));

    $sql_items = "SELECT oi.*, p.name, p.image_url as image
                  FROM order_items oi
                  JOIN products p ON oi.product_id = p.id
                  WHERE oi.order_id IN ($placeholders)";
    
    $items = getRows($sql_items, $order_ids, $types);

    $items_by_order = [];
    foreach ($items as $item) {
        $items_by_order[$item['order_id']][] = $item;
    }

    foreach ($orders as $key => $order) {
        $orders[$key]['products'] = $items_by_order[$order['id']] ?? [];
        $orders[$key]['order_date'] = date('d/m/Y', strtotime($order['created_at']));
        $orders[$key]['total'] = formatPrice($order['total_amount']);
    }

    return $orders;
}

function getOrder($order_id, $user_id) {
    $order = getRow("SELECT * FROM orders WHERE id = ? AND user_id = ?", [$order_id, $user_id], 'ii');

    if (!$order) {
        return null;
    }

    $sql_items = "SELECT oi.*, p.name, p.image_url as image, oi.price
                  FROM order_items oi
                  JOIN products p ON oi.product_id = p.id
                  WHERE oi.order_id = ?";
    
    $items = getRows($sql_items, [$order_id], 'i');

    foreach ($items as &$item) {
        $item['price'] = formatPrice($item['price']);
    }
    
    $order['products'] = $items;
    $order['order_date'] = date('d/m/Y', strtotime($order['created_at']));
    $order['total'] = formatPrice($order['total_amount']);

    $status_map = [
        'pending' => 'Đang chờ xử lý',
        'processing' => 'Đang xử lý',
        'completed' => 'Đã hoàn thành',
        'cancelled' => 'Đã hủy'
    ];
    $order['status_text'] = $status_map[$order['status']] ?? 'Không xác định';

    return $order;
}

// Template functions
function renderError($message) {
    return '<div class="alert alert-danger">' . htmlspecialchars($message) . '</div>';
}

function renderSuccess($message) {
    return '<div class="alert alert-success">' . htmlspecialchars($message) . '</div>';
}

function renderCartItems($items) {
    ob_start();
    if (empty($items)) {
        echo "<p>Your cart is empty.</p>";
    } else {
        foreach ($items as $item) : ?>
            <div class="cart-item" data-cart-item="<?= $item['product_id'] ?>">
                <img src="/assets/images/products/<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="cart-item-image">
                <div class="cart-item-details">
                    <h3><?= htmlspecialchars($item['name']) ?></h3>
                    <p>Price: <?= formatPrice($item['price']) ?></p>
                </div>
                <div class="cart-quantity">
                    <input type="number" class="cart-quantity-input" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock'] ?>" data-product-id="<?= $item['product_id'] ?>">
                </div>
                <div class="cart-item-total">
                    <p>Total: <?= formatPrice($item['price'] * $item['quantity']) ?></p>
                </div>
                <a href="#" class="remove-from-cart" data-product-id="<?= $item['product_id'] ?>">&times;</a>
            </div>
        <?php endforeach;
    }
    return ob_get_clean();
}

function renderHeaderIcons() {
    $cart_item_count = 0;
    if (isLoggedIn()) {
        $cart_item_count = getCartItemCount($_SESSION['user_id']);
    }

    $icons = '';

    // Account Icon
    if (isLoggedIn()) {
        $icons .= '<li><a href="/Music-Store/account.php" aria-label="Tài khoản"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg></a></li>';
    } else {
        $icons .= '<li><a href="/Music-Store/login.php" aria-label="Đăng nhập"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg></a></li>';
    }

    // Search Icon
    $icons .= '<li><button id="open-search-btn" aria-label="Tìm kiếm"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg></button></li>';

    // Cart Icon
    $icons .= '<li>
        <a href="/Music-Store/cart.php" class="cart-icon-wrapper" aria-label="Giỏ hàng">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
            <span class="cart-count">' . $cart_item_count . '</span>
        </a>
    </li>';
    
    if (isLoggedIn()) {
        $icons .= '<li><a href="/Music-Store/logout.php" aria-label="Đăng xuất"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg></a></li>';
    }

    return $icons;
}

function renderNavigationMenu() {
    $danh_muc_items = [
        'Accessories', 'Effects & Pedals', 'Strings', 'Electric Guitar', 
        'Electric Bass', 'Acoustic Guitar', 'Classical Guitar', 'Amplifier', 
        'Cabinet', 'Drums & Percussions', 'Audio & Recording'
    ];
    
    $thuong_hieu_items = [
        'Thương Hiệu Nổi Tiếng', 'Guitars', 'Effects & Pedals', 'Amplifier', 
        'Phụ kiện', 'Khác', 'Drums & Percussions'
    ];

    $arrow_svg = '<svg class="dropdown-arrow" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/></svg>';
    $dropdown_icon_svg = '<svg class="arrow-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/></svg>';

    $html = '<nav class="desktop-nav">';
    $html .= '<ul class="desktop-nav-list">';
    
    // Danh Mục Dropdown
    $html .= '<li class="nav-item has-dropdown">';
    $html .= '<a href="#" class="nav-link">Danh Mục ' . $dropdown_icon_svg . '</a>';
    $html .= '<div class="dropdown-menu">';
    $html .= '<ul class="dropdown-list">';
    foreach ($danh_muc_items as $item) {
        $html .= '<li><a href="#">' . htmlspecialchars($item) . $arrow_svg . '</a></li>';
    }
    $html .= '</ul></div></li>';
    
    // Thương Hiệu Dropdown
    $html .= '<li class="nav-item has-dropdown">';
    $html .= '<a href="#" class="nav-link">Thương Hiệu ' . $dropdown_icon_svg . '</a>';
    $html .= '<div class="dropdown-menu">';
    $html .= '<ul class="dropdown-list">';
    foreach ($thuong_hieu_items as $item) {
         $html .= '<li><a href="#">' . htmlspecialchars($item) . $arrow_svg . '</a></li>';
    }
    $html .= '</ul></div></li>';

    // New simple links
    $html .= '<li class="nav-item"><a href="#" class="nav-link">Summer Sale</a></li>';
    $html .= '<li class="nav-item"><a href="#" class="nav-link">Bài viết</a></li>';

    $html .= '</ul></nav>';
    return $html;
}

// Product Functions
function getProduct($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
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
        return '<p class="text-center-message">Không tìm thấy sản phẩm nào.</p>';
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

function renderCategoriesList($categories) {
    if (empty($categories)) {
        return '';
    }

    $html = '';
    foreach ($categories as $category) {
        // Prepare inline style for background image
        $style = !empty($category['image_url']) 
            ? "background-image: url('" . htmlspecialchars($category['image_url']) . "');" 
            : "background-color: #333;"; // Fallback color if no image

        $html .= '<a href="/Music-Store/products.php?category=' . $category['id'] . '" class="category-card" style="' . $style . '">';
        
        // Content wrapper for positioning text
        $html .= '<div class="category-card-content">';
        $html .= '<h3 class="category-title">' . htmlspecialchars($category['name']) . '</h3>';
        $html .= '<p class="category-description">' . htmlspecialchars($category['description']) . '</p>';
        $html .= '</div>';

        $html .= '</a>';
    }
    
    return $html;
}

function renderFeaturedProducts($products) {
    if (empty($products)) {
        return '<p class="text-center-message">Không có sản phẩm nổi bật.</p>';
    }

    $html = '';
    foreach ($products as $product) {
        $html .= '<div class="product-card">';
        $html .= '<a href="/Music-Store/product.php?id=' . $product['id'] . '" class="product-link">';
        $html .= '<div class="product-image">';
        $html .= '<img src="/Music-Store/assets/images/products/' . $product['image'] . '" alt="' . $product['name'] . '">';
        if ($product['stock'] <= 0) {
            $html .= '<span class="out-of-stock">Hết hàng</span>';
        }
        $html .= '</div>';
        $html .= '<div class="product-info">';
        $html .= '<h3 class="product-title">' . $product['name'] . '</h3>';
        $html .= '<p class="product-category">' . $product['category_name'] . '</p>';
        $html .= '<p class="product-price">' . number_format($product['price']) . ' VNĐ</p>';
        $html .= '</div>';
        $html .= '</a>';
        $html .= '<div class="product-actions">';
        if ($product['stock'] > 0) {
            $html .= '<form method="POST" action="/Music-Store/cart.php" class="add-to-cart-form">';
            $html .= '<input type="hidden" name="product_id" value="' . $product['id'] . '">';
            $html .= '<button type="submit" name="add_to_cart" class="btn btn-primary">';
            $html .= '<i class="fas fa-cart-plus"></i> Thêm vào giỏ';
            $html .= '</button>';
            $html .= '</form>';
        } else {
            $html .= '<p class="out-of-stock">Hết hàng</p>';
        }
        $html .= '</div>';
        $html .= '</div>';
    }
    return $html;
}
?> 