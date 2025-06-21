<?php
require_once 'includes/config/config.php';
require_once 'includes/functions/functions.php';
require_once 'includes/functions/template.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('/Music-Store/index.php');
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Vui lòng nhập một địa chỉ email hợp lệ.';
    } else {
        $user = getRow("SELECT * FROM users WHERE email = ?", [$email], 's');

        if ($user) {
            // In a real application, you would send an email.
            // For now, we generate a token and save it.
            $token = bin2hex(random_bytes(32));
            $expires = time() + 3600; // 1 hour expiration

            // Remove any old tokens for this email
            executeQuery("DELETE FROM password_resets WHERE email = ?", [$email], 's');
            
            // Insert new token
            $sql = "INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)";
            executeQuery($sql, [$email, $token, $expires], 'ssi');

            // In a real app, you'd email this link:
            // $reset_link = APP_URL . '/reset-password.php?token=' . $token;
            // mail($email, 'Password Reset Request', 'Click here to reset your password: ' . $reset_link);
        }

        // Always show a success message to prevent user enumeration
        $success_message = 'Nếu email của bạn tồn tại trong hệ thống, chúng tôi đã gửi một liên kết đặt lại mật khẩu.';
    }
}

// Prepare template data
$data = [
    'title' => 'Quên mật khẩu',
    'user_links' => renderUserLinks(),
    'error_message' => $error_message ? renderErrorMessage($error_message) : '',
    'success_message' => $success_message ? renderSuccessMessage($success_message) : '',
];

// Render template
echo renderTemplate('forgot-password', $data); 