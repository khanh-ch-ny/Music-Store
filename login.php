<?php
require_once 'includes/config/config.php';
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
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = 'Vui lòng điền đầy đủ thông tin đăng nhập.';
    } else {
        // Get user data using prepared statement
        $sql = "SELECT * FROM users WHERE username = ?";
        $user = getRow($sql, [$username], 's');

        if ($user && password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Redirect based on user role
            if ($user['role'] === 'admin') {
                redirect('admin/index.php');
            } else {
                redirect('index.php');
            }
        } else {
            $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
        }
    }
}

// Prepare template data
$data = [
    'error_message' => $error ? renderError($error) : '',
    'success_message' => $success ? renderSuccess($success) : '',
    'username' => isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''
];

// Render template
echo renderTemplate('login', $data); 