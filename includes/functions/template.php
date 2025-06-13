<?php
// Template rendering function
function renderTemplate($template, $data = []) {
    // Add current year to all templates
    $data['current_year'] = date('Y');
    
    // Get template file path
    $template_file = __DIR__ . '/../../assets/templates/' . $template . '.html';
    
    // Check if template exists
    if (!file_exists($template_file)) {
        throw new Exception("Template file not found: $template");
    }
    
    // Read template content
    $content = file_get_contents($template_file);
    
    // Replace template variables
    foreach ($data as $key => $value) {
        $content = str_replace('{{' . $key . '}}', $value, $content);
    }
    
    // Handle conditional statements
    $content = preg_replace_callback('/{{#if\s+(.*?)}}(.*?){{\/if}}/s', function($matches) use ($data) {
        $condition = trim($matches[1]);
        $content = $matches[2];
        
        // Simple condition evaluation
        if (strpos($condition, '>') !== false) {
            list($var, $val) = explode('>', $condition);
            $var = trim($var);
            $val = trim($val);
            return isset($data[$var]) && $data[$var] > $val ? $content : '';
        }
        
        return isset($data[$condition]) && $data[$condition] ? $content : '';
    }, $content);
    
    // Handle loops
    $content = preg_replace_callback('/{{#each\s+(.*?)}}(.*?){{\/each}}/s', function($matches) use ($data) {
        $items = $data[$matches[1]] ?? [];
        $template = $matches[2];
        $result = '';
        
        foreach ($items as $item) {
            $item_content = $template;
            foreach ($item as $key => $value) {
                $item_content = str_replace('{{' . $key . '}}', $value, $item_content);
            }
            $result .= $item_content;
        }
        
        return $result;
    }, $content);
    
    // Remove any remaining template variables
    $content = preg_replace('/{{.*?}}/', '', $content);
    
    return $content;
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
