<?php
require_once 'products.php';
$products = getAllProducts();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Music Store - Cửa hàng dụng cụ âm nhạc</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">Music Store</div>
            <ul class="nav-links">
                <li><a href="#home">Trang chủ</a></li>
                <li><a href="#products">Sản phẩm</a></li>
                <li><a href="#about">Giới thiệu</a></li>
                <li><a href="#contact">Liên hệ</a></li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li><a href="cart.php">Giỏ hàng</a></li>
                    <li><a href="logout.php">Đăng xuất</a></li>
                <?php else: ?>
                    <li><a href="login.php">Đăng nhập</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main>
        <section id="hero">
            <h1>Chào mừng đến với Music Store</h1>
            <p>Nơi cung cấp các dụng cụ âm nhạc chất lượng cao</p>
        </section>

        <section id="products">
            <h2>Sản phẩm nổi bật</h2>
            <div class="product-grid">
                <?php foreach($products as $product): ?>
                <div class="product-card">
                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                    <p><?php echo number_format($product['price'], 0, ',', '.'); ?>đ</p>
                    <form action="cart.php" method="POST">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <button type="submit" name="add_to_cart">Mua ngay</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>Liên hệ</h3>
                <p>Email: info@musicstore.com</p>
                <p>Điện thoại: 0123 456 789</p>
            </div>
            <div class="footer-section">
                <h3>Theo dõi chúng tôi</h3>
                <p>Facebook</p>
                <p>Instagram</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Music Store. All rights reserved.</p>
        </div>
    </footer>
</body>
</html> 