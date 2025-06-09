<?php
require_once 'includes/functions/functions.php';
require_once 'includes/functions/template.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$cart_items = getCartItems($user_id);
$cart_total = calculateCartTotal($user_id);

// Redirect if cart is empty
if (empty($cart_items)) {
    redirect('cart.php');
}

$error = '';
$success = '';

// Get user data
$user = getUserData($user_id);

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = sanitize($_POST['payment_method']);
    $shipping_address = sanitize($_POST['shipping_address']);
    $phone = sanitize($_POST['phone']);

    if (empty($payment_method) || empty($shipping_address) || empty($phone)) {
        $error = 'Vui lòng điền đầy đủ thông tin thanh toán.';
    } else {
        try {
            // Start transaction
            $conn->begin_transaction();

            // Create order
            $sql = "INSERT INTO orders (user_id, total_amount, status) 
                    VALUES ($user_id, $cart_total, 'pending')";
            executeQuery($sql);
            $order_id = $conn->insert_id;

            // Add order items
            foreach ($cart_items as $item) {
                $product_id = $item['product_id'];
                $quantity = $item['quantity'];
                $price = $item['price'];

                $sql = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                        VALUES ($order_id, $product_id, $quantity, $price)";
                executeQuery($sql);

                // Update product stock
                $sql = "UPDATE products 
                        SET stock = stock - $quantity 
                        WHERE id = $product_id";
                executeQuery($sql);
            }

            // Clear cart
            $sql = "DELETE FROM cart WHERE user_id = $user_id";
            executeQuery($sql);

            // Commit transaction
            $conn->commit();

            $success = 'Đặt hàng thành công! Cảm ơn bạn đã mua hàng.';
            
            // Redirect to order confirmation
            redirect("order-confirmation.php?id=$order_id");
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $error = 'Có lỗi xảy ra. Vui lòng thử lại sau.';
        }
    }
}

// Prepare template data
$data = [
    'error_message' => $error ? renderError($error) : '',
    'success_message' => $success ? renderSuccess($success) : '',
    'cart_items' => renderCartItems($cart_items),
    'cart_total' => formatPrice($cart_total),
    'user_full_name' => htmlspecialchars($user['full_name']),
    'user_email' => htmlspecialchars($user['email']),
    'user_phone' => htmlspecialchars($user['phone']),
    'user_address' => htmlspecialchars($user['address'])
];

// Render template
echo renderTemplate('payment', $data); 