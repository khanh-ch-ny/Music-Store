<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/functions/functions.php';
require_once 'includes/functions/template.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = sanitize($_POST['full_name']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);

    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Vui lòng điền đầy đủ thông tin bắt buộc.';
    } elseif ($password !== $confirm_password) {
        $error = 'Mật khẩu xác nhận không khớp.';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ.';
    } else {
        // Check if username or email already exists
        $sql = "SELECT id FROM users WHERE username = '$username' OR email = '$email'";
        $result = executeQuery($sql);

        if ($result->num_rows > 0) {
            $error = 'Tên đăng nhập hoặc email đã tồn tại.';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user
            $sql = "INSERT INTO users (username, email, password, full_name, phone, address) 
                    VALUES ('$username', '$email', '$hashed_password', '$full_name', '$phone', '$address')";
            
            try {
                executeQuery($sql);
                $success = 'Đăng ký thành công! Vui lòng đăng nhập để tiếp tục.';
            } catch (Exception $e) {
                $error = 'Có lỗi xảy ra. Vui lòng thử lại sau.';
            }
        }
    }
}

// Prepare template data
$data = [
    'error_message' => $error ? renderError($error) : '',
    'success_message' => $success ? renderSuccess($success) : '',
    'username' => isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '',
    'email' => isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '',
    'full_name' => isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : '',
    'phone' => isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '',
    'address' => isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''
];

// Render template
echo renderTemplate('register', $data); 