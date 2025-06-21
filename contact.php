<?php
require_once 'includes/config/config.php';
require_once 'includes/functions/functions.php';
require_once 'includes/functions/template.php';

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_message = 'Vui lòng điền đầy đủ các trường bắt buộc.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Địa chỉ email không hợp lệ.';
    } else {
        // For a real application, you would send an email here.
        // For now, we just set a success message.
        $success_message = 'Cảm ơn bạn đã liên hệ! Chúng tôi sẽ trả lời bạn sớm nhất có thể.';
    }
}

// Prepare template data
$data = [
    'title' => 'Liên hệ',
    'user_links' => renderUserLinks(),
    'active_contact' => true,
    'error_message' => $error_message ? renderErrorMessage($error_message) : '',
    'success_message' => $success_message ? renderSuccessMessage($success_message) : '',
];

// Render template
echo renderTemplate('contact', $data); 