<?php
require_once 'includes/config/config.php';
require_once 'includes/functions/functions.php';
require_once 'includes/functions/template.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('/Music-Store/login.php');
}

$user_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $fullname = sanitize($_POST['fullname']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $address = sanitize($_POST['address']);

        if (empty($fullname) || empty($email)) {
            $error_message = 'Họ và tên và email là bắt buộc.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Email không hợp lệ.';
        } else {
            $sql = "UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? WHERE id = ?";
            executeQuery($sql, [$fullname, $email, $phone, $address, $user_id], 'ssssi');
            $success_message = 'Thông tin cá nhân đã được cập nhật.';
        }
    } elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        $user_data = getUserData($user_id);

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error_message = 'Vui lòng điền đầy đủ thông tin mật khẩu.';
        } elseif (!password_verify($current_password, $user_data['password'])) {
            $error_message = 'Mật khẩu hiện tại không đúng.';
        } elseif ($new_password !== $confirm_password) {
            $error_message = 'Mật khẩu xác nhận không khớp.';
        } elseif (strlen($new_password) < 6) {
            $error_message = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password = ? WHERE id = ?";
            executeQuery($sql, [$hashed_password, $user_id], 'si');
            $success_message = 'Mật khẩu đã được thay đổi thành công.';
        }
    }
}

// Get user data and orders
$user = getUserData($user_id);
$orders = getUserOrders($user_id);

// Prepare template data
$data = [
    'title' => 'Tài khoản của tôi',
    'user_links' => renderUserLinks(),
    'active_account' => true,
    'user' => $user,
    'orders' => $orders,
    'error_message' => $error_message ? renderErrorMessage($error_message) : '',
    'success_message' => $success_message ? renderSuccessMessage($success_message) : '',
];

// Render template
echo renderTemplate('account', $data); 