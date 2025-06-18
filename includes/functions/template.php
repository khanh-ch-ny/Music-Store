<?php
// Template rendering function
function renderTemplate($template, $data = []) {
    // Add current year to all templates
    $data['current_year'] = date('Y');
    
    // Read the main layout
    $layout = file_get_contents(__DIR__ . '/../../assets/templates/layouts/main.html');
    
    // Read the page content
    $content = file_get_contents(__DIR__ . '/../../assets/templates/pages/' . $template . '.html');
    
    // Replace content placeholder in layout
    $layout = str_replace('{{content}}', $content, $layout);
    
    // Process partials
    $layout = preg_replace_callback('/{{> ([^}]+)}}/', function($matches) {
        $partial = file_get_contents(__DIR__ . '/../../assets/templates/partials/' . $matches[1] . '.html');
        return $partial;
    }, $layout);
    
    // Replace template variables
    foreach ($data as $key => $value) {
        $layout = str_replace('{{' . $key . '}}', $value, $layout);
    }
    
    // Handle conditional statements
    $layout = preg_replace_callback('/{{#if ([^}]+)}}(.*?){{\/if}}/s', function($matches) use ($data) {
        $condition = $matches[1];
        $content = $matches[2];
        return isset($data[$condition]) && $data[$condition] ? $content : '';
    }, $layout);
    
    // Handle loops
    $layout = preg_replace_callback('/{{#each ([^}]+)}}(.*?){{\/each}}/s', function($matches) use ($data) {
        $array = $data[$matches[1]] ?? [];
        $template = $matches[2];
        $result = '';
        
        foreach ($array as $item) {
            $itemTemplate = $template;
            foreach ($item as $key => $value) {
                $itemTemplate = str_replace('{{' . $key . '}}', $value, $itemTemplate);
            }
            $result .= $itemTemplate;
        }
        
        return $result;
    }, $layout);
    
    // Remove any remaining template variables
    $layout = preg_replace('/{{[^}]+}}/', '', $layout);
    
    return $layout;
}

// Helper function to render error messages
function renderErrorMessage($message) {
    return '<div class="alert alert-danger">' . htmlspecialchars($message) . '</div>';
}

// Helper function to render success messages
function renderSuccessMessage($message) {
    return '<div class="alert alert-success">' . htmlspecialchars($message) . '</div>';
}

// Helper function to render form errors
function renderFormErrors($errors) {
    if (empty($errors)) {
        return '';
    }
    
    $html = '<div class="alert alert-danger"><ul>';
    foreach ($errors as $error) {
        $html .= '<li>' . htmlspecialchars($error) . '</li>';
    }
    $html .= '</ul></div>';
    
    return $html;
}
