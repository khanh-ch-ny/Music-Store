<?php
function renderTemplate($template, $data = []) {
    // Add current year to all templates
    $data['current_year'] = date('Y');
    
    // Load template file
    $template_file = "assets/templates/{$template}.html";
    if (!file_exists($template_file)) {
        die("Template file not found: {$template_file}");
    }
    
    // Get template content
    $content = file_get_contents($template_file);
    
    // Replace placeholders with data
    foreach ($data as $key => $value) {
        // Convert non-string values to string
        if (is_array($value)) {
            // Special handling for categories list
            if ($key === 'categories_list') {
                $html = '';
                foreach ($value as $category) {
                    $html .= '<div class="category-card">';
                    $html .= '<i class="fas fa-music"></i>';
                    $html .= '<h3>' . htmlspecialchars($category['name']) . '</h3>';
                    if (isset($category['description'])) {
                        $html .= '<p>' . htmlspecialchars($category['description']) . '</p>';
                    }
                    $html .= '<a href="products.php?category=' . $category['id'] . '" class="btn btn-primary">Xem sản phẩm</a>';
                    $html .= '</div>';
                }
                $value = $html;
            }
            // Special handling for featured products
            else if ($key === 'featured_products') {
                $html = '';
                foreach ($value as $product) {
                    $html .= '<div class="product-card">';
                    $html .= '<a href="product.php?id=' . $product['id'] . '" class="product-link">';
                    if (isset($product['image_url'])) {
                        $html .= '<div class="product-image">';
                        $html .= '<img src="' . htmlspecialchars($product['image_url']) . '" alt="' . htmlspecialchars($product['name']) . '">';
                        $html .= '</div>';
                    }
                    $html .= '<div class="product-info">';
                    $html .= '<h3 class="product-title">' . htmlspecialchars($product['name']) . '</h3>';
                    if (isset($product['category_name'])) {
                        $html .= '<p class="product-category">' . htmlspecialchars($product['category_name']) . '</p>';
                    }
                    if (isset($product['price'])) {
                        $html .= '<p class="product-price">' . formatPrice($product['price']) . '</p>';
                    }
                    $html .= '</div>';
                    $html .= '</a>';
                    $html .= '<div class="product-actions">';
                    if ($product['stock'] > 0) {
                        $html .= '<form method="POST" action="cart.php" class="add-to-cart-form">';
                        $html .= '<input type="hidden" name="product_id" value="' . $product['id'] . '">';
                        $html .= '<input type="hidden" name="quantity" value="1">';
                        $html .= '<button type="submit" name="add_to_cart" class="btn btn-primary">';
                        $html .= '<i class="fas fa-cart-plus"></i> Thêm vào giỏ';
                        $html .= '</button>';
                        $html .= '</form>';
                    } else {
                        $html .= '<span class="out-of-stock">Hết hàng</span>';
                    }
                    $html .= '</div>'; // End product-actions
                    $html .= '</div>'; // End product-card
                }
                $value = $html;
            }
            // If it's an array of products, categories, etc.
            else if (isset($value[0]) && is_array($value[0])) {
                $html = '';
                foreach ($value as $item) {
                    if (isset($item['name'])) {
                        $html .= '<div class="item">';
                        if (isset($item['image_url'])) {
                            $html .= '<img src="' . htmlspecialchars($item['image_url']) . '" alt="' . htmlspecialchars($item['name']) . '">';
                        }
                        $html .= '<h3>' . htmlspecialchars($item['name']) . '</h3>';
                        if (isset($item['price'])) {
                            $html .= '<p class="price">' . formatPrice($item['price']) . '</p>';
                        }
                        if (isset($item['description'])) {
                            $html .= '<p>' . htmlspecialchars($item['description']) . '</p>';
                        }
                        $html .= '</div>';
                    }
                }
                $value = $html;
            } else {
                $value = implode('', $value);
            }
        } elseif (is_object($value)) {
            $value = (string)$value;
        } elseif (!is_string($value)) {
            $value = (string)$value;
        }
        
        $content = str_replace("{{" . $key . "}}", $value, $content);
    }
    
    return $content;
}
