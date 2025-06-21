<?php
require_once 'includes/config/config.php';
require_once 'includes/functions/functions.php';
require_once 'includes/functions/template.php';

if (isLoggedIn()) {
    redirect('/Music-Store/index.php');
}

$error_message = '';
$success_message = '';
$token = sanitize($_GET['token'] ?? '');
$valid_token = false;

if (empty($token)) {
    $error_message = 'Token không hợp lệ hoặc đã hết hạn.';
} else {
    $reset_request = getRow("SELECT * FROM password_resets WHERE token = ? AND expires_at > ?", [$token, time()], 'si');
    if ($reset_request) {
        $valid_token = true;
    } else {
        $error_message = 'Token không hợp lệ hoặc đã hết hạn.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($password) || empty($confirm_password)) {
        $error_message = 'Vui lòng nhập mật khẩu mới.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Mật khẩu xác nhận không khớp.';
    } elseif (strlen($password) < 6) {
        $error_message = 'Mật khẩu phải có ít nhất 6 ký tự.';
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $email = $reset_request['email'];
        
        // Update user's password
        executeQuery("UPDATE users SET password = ? WHERE email = ?", [$hashed_password, $email], 'ss');
        
        // Delete the token
        executeQuery("DELETE FROM password_resets WHERE email = ?", [$email], 's');

        $success_message = 'Mật khẩu của bạn đã được đặt lại thành công. Bây giờ bạn có thể đăng nhập.';
        $valid_token = false; // Hide form after successful reset
    }
}

// Prepare template data
$data = [
    'title' => 'Đặt lại mật khẩu',
    'user_links' => renderUserLinks(),
    'token' => $token,
    'valid_token' => $valid_token,
    'error_message' => $error_message ? renderErrorMessage($error_message) : '',
    'success_message' => $success_message ? renderSuccessMessage($success_message) : '',
];

// In the template, we'll need to use `{{#if valid_token}}` to show the form.
// I will modify the template for that.
echo renderTemplate('reset-password', $data); 