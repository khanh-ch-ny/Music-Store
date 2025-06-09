<?php
require_once '../includes/functions/functions.php';

// Redirect if not admin
if (!isAdmin()) {
    redirect('../login.php');
}

$error = '';
$success = '';

// Handle product deletion
if (isset($_POST['delete_product'])) {
    $product_id = (int)$_POST['product_id'];
    
    try {
        // Delete product image
        $sql = "SELECT image_url FROM products WHERE id = $product_id";
        $product = getRow($sql);
        if ($product && $product['image_url']) {
            $image_path = '../' . $product['image_url'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }

        // Delete product
        $sql = "DELETE FROM products WHERE id = $product_id";
        executeQuery($sql);
        $success = 'Sản phẩm đã được xóa thành công.';
    } catch (Exception $e) {
        $error = 'Có lỗi xảy ra khi xóa sản phẩm.';
    }
}

// Get all products with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        ORDER BY p.created_at DESC 
        LIMIT $offset, $per_page";
$products = getRows($sql);

// Get total products for pagination
$sql = "SELECT COUNT(*) as total FROM products";
$total_products = getRow($sql)['total'];
$total_pages = ceil($total_products / $per_page);

// Get all categories for filter
$sql = "SELECT * FROM categories ORDER BY name";
$categories = getRows($sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý sản phẩm - Music Store</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="navbar">
            <a href="../index.php" class="logo">Music Store</a>
            <ul class="nav-links">
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="products.php">Sản phẩm</a></li>
                <li><a href="orders.php">Đơn hàng</a></li>
                <li><a href="users.php">Người dùng</a></li>
                <li><a href="../logout.php">Đăng xuất</a></li>
            </ul>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="container">
        <div class="admin-header">
            <h1>Quản lý sản phẩm</h1>
            <a href="add-product.php" class="btn btn-primary">Thêm sản phẩm mới</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Product Filter -->
        <div class="admin-filter">
            <form method="GET" action="products.php" class="filter-form">
                <div class="form-group">
                    <label for="category" class="form-label">Lọc theo danh mục:</label>
                    <select name="category" id="category" class="form-control">
                        <option value="">Tất cả danh mục</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"
                                    <?php echo isset($_GET['category']) && $_GET['category'] == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Lọc</button>
            </form>
        </div>

        <!-- Products Table -->
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Hình ảnh</th>
                        <th>Tên sản phẩm</th>
                        <th>Danh mục</th>
                        <th>Giá</th>
                        <th>Tồn kho</th>
                        <th>Ngày tạo</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>#<?php echo $product['id']; ?></td>
                            <td>
                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                     class="product-thumbnail">
                            </td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                            <td><?php echo formatPrice($product['price']); ?></td>
                            <td>
                                <span class="stock-badge <?php echo $product['stock'] == 0 ? 'out-of-stock' : ($product['stock'] < 5 ? 'low-stock' : ''); ?>">
                                    <?php echo $product['stock']; ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($product['created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="edit-product.php?id=<?php echo $product['id']; ?>" 
                                       class="btn btn-primary btn-sm">
                                        Sửa
                                    </a>
                                    <form method="POST" action="products.php" class="delete-form" 
                                          onsubmit="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?');">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" name="delete_product" class="btn btn-danger btn-sm">
                                            Xóa
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>" class="btn btn-primary">Trang trước</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" 
                       class="btn <?php echo $i == $page ? 'btn-primary active' : 'btn-outline'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>" class="btn btn-primary">Trang sau</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Music Store. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="../assets/js/script.js"></script>
</body>
</html> 