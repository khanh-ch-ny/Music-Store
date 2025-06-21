<?php
require_once 'includes/config/config.php';
require_once 'includes/functions/functions.php';
require_once 'includes/functions/template.php';

// Prepare template data
$data = [
    'title' => 'Về chúng tôi',
    'user_links' => renderUserLinks(),
    'active_about' => true
];

// Render template
echo renderTemplate('about', $data); 