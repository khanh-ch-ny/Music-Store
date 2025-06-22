<?php
require_once '../config/config.php';
require_once '../functions/functions.php';

header('Content-Type: text/html; charset=utf-8');

if (!isLoggedIn()) {
    echo '<div class="cart-drawer-empty">Bạn chưa đăng nhập.</div>';
    exit;
}

$user_id = $_SESSION['user_id'];
$cart_items = getCartItems($user_id);
$cart_total = calculateCartTotal($user_id);

if (empty($cart_items)) {
    echo '<div class="cart-drawer-empty">Giỏ hàng của bạn đang trống.</div>';
    echo '<script>document.getElementById("cart-drawer-subtotal").textContent = "0đ";</script>';
    exit;
}
?>
<div class="cart-drawer-list">
<?php foreach ($cart_items as $item): ?>
    <div class="cart-drawer-item" data-product-id="<?= $item['product_id'] ?>">
        <img src="/Music-Store/assets/images/products/<?= htmlspecialchars($item['image_url']) ?>" class="cart-drawer-img" alt="<?= htmlspecialchars($item['name']) ?>">
        <div class="cart-drawer-info">
            <div class="cart-drawer-name"><?= htmlspecialchars($item['name']) ?></div>
            <div class="cart-drawer-price"><?= formatPrice($item['price']) ?></div>
            <div class="cart-drawer-qty">
                <button class="cart-drawer-qty-btn minus" data-product-id="<?= $item['product_id'] ?>">-</button>
                <input type="text" class="cart-drawer-qty-input" value="<?= $item['quantity'] ?>" readonly>
                <button class="cart-drawer-qty-btn plus" data-product-id="<?= $item['product_id'] ?>">+</button>
                <a href="#" class="cart-drawer-remove" data-product-id="<?= $item['product_id'] ?>">Loại bỏ</a>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>
<script>document.getElementById("cart-drawer-subtotal").textContent = "<?= formatPrice($cart_total) ?>";</script> 