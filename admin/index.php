<?php
require_once '../includes/functions/functions.php';

// Redirect if not admin
if (!isAdmin()) {
    redirect('../login.php');
}

// Get statistics
$sql = "SELECT COUNT(*) as total_products FROM products";
$total_products = getRow($sql)['total_products'];

$sql = "SELECT COUNT(*) as total_orders FROM orders";
$total_orders = getRow($sql)['total_orders'];

$sql = "SELECT COUNT(*) as total_users FROM users WHERE role = 'user'";
$total_users = getRow($sql)['total_users'];

$sql = "SELECT SUM(total_amount) as total_revenue FROM orders WHERE status = 'completed'";
$total_revenue = getRow($sql)['total_revenue'] ?? 0;

// Get recent orders
$sql = "SELECT o.*, u.username, u.full_name 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC 
        LIMIT 5";
$recent_orders = getRows($sql);

// Get low stock products
$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.stock < 5 
        ORDER BY p.stock ASC 
        LIMIT 5";
$low_stock_products = getRows($sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Music Store</title>
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
        <h1>Admin Dashboard</h1>

        <!-- Statistics -->
        <div class="admin-grid">
            <div class="admin-card">
                <h3>Tổng sản phẩm</h3>
                <p class="stat-number"><?php echo $total_products; ?></p>
            </div>
            <div class="admin-card">
                <h3>Tổng đơn hàng</h3>
                <p class="stat-number"><?php echo $total_orders; ?></p>
            </div>
            <div class="admin-card">
                <h3>Tổng người dùng</h3>
                <p class="stat-number"><?php echo $total_users; ?></p>
            </div>
            <div class="admin-card">
                <h3>Doanh thu</h3>
                <p class="stat-number"><?php echo formatPrice($total_revenue); ?></p>
            </div>
        </div>

        <!-- Recent Orders -->
        <section class="admin-section">
            <h2>Đơn hàng gần đây</h2>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Khách hàng</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Ngày đặt</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_orders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($order['full_name']); ?>
                                    <br>
                                    <small><?php echo htmlspecialchars($order['username']); ?></small>
                                </td>
                                <td><?php echo formatPrice($order['total_amount']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <a href="order-detail.php?id=<?php echo $order['id']; ?>" 
                                       class="btn btn-primary btn-sm">
                                        Chi tiết
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <a href="orders.php" class="btn btn-primary">Xem tất cả đơn hàng</a>
        </section>

        <!-- Low Stock Products -->
        <section class="admin-section">
            <h2>Sản phẩm sắp hết hàng</h2>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên sản phẩm</th>
                            <th>Danh mục</th>
                            <th>Giá</th>
                            <th>Tồn kho</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($low_stock_products as $product): ?>
                            <tr>
                                <td>#<?php echo $product['id']; ?></td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                <td><?php echo formatPrice($product['price']); ?></td>
                                <td>
                                    <span class="stock-badge <?php echo $product['stock'] == 0 ? 'out-of-stock' : 'low-stock'; ?>">
                                        <?php echo $product['stock']; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="edit-product.php?id=<?php echo $product['id']; ?>" 
                                       class="btn btn-primary btn-sm">
                                        Cập nhật
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <a href="products.php" class="btn btn-primary">Xem tất cả sản phẩm</a>
        </section>
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