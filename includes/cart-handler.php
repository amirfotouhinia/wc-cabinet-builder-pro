<?php
// ==========================================
// ✅ فایل اصلاح‌شده با اعتبارسنجی کامل
// ==========================================

add_filter('woocommerce_add_cart_item_data', 'wccb_add_cart_item_data', 10, 3);
function wccb_add_cart_item_data($cart_item_data, $product_id, $variation_id) {
    if (isset($_POST['wccb_width'])) {
        // ✅ اعتبارسنجی و پاکسازی داده‌های JSON
        $drawers_raw = [];
        if (isset($_POST['wccb_drawers_data']) && !empty($_POST['wccb_drawers_data'])) {
            $decoded = json_decode(stripslashes($_POST['wccb_drawers_data']), true);
            if (is_array($decoded)) {
                foreach ($decoded as $key => $value) {
                    $key = sanitize_text_field($key);
                    if (is_array($value)) {
                        $drawers_raw[$key] = array_map('intval', $value);
                    } else {
                        $drawers_raw[$key] = intval($value);
                    }
                }
            }
        }
        
        $drawers = is_array($drawers_raw) ? $drawers_raw : [];
        
        // ✅ اعتبارسنجی ابعاد
        $width = isset($_POST['wccb_width']) ? intval($_POST['wccb_width']) : 20;
        $width = max(15, min(40, $width)); // محدوده 15-40
        
        $height = isset($_POST['wccb_height']) ? intval($_POST['wccb_height']) : 72;
        $height = max(16, min(96, $height)); // محدوده 16-96
        
        $shelves = isset($_POST['wccb_shelves']) ? intval($_POST['wccb_shelves']) : 0;
        $shelves = max(0, min(15, $shelves)); // محدوده 0-15
        
        $panel_width = isset($_POST['wccb_panel_width']) ? intval($_POST['wccb_panel_width']) : 12;
        $panel_width = in_array($panel_width, [12, 16, 24]) ? $panel_width : 12;
        
        $cart_item_data['wccb_data'] = [
            'width' => $width,
            'color' => isset($_POST['wccb_color']) ? sanitize_text_field($_POST['wccb_color']) : 'white',
            'shelves' => $shelves,
            'drawers' => $drawers,
            'height' => $height,
            'panel_width' => $panel_width,
            'has_rod' => isset($_POST['wccb_has_rod']) ? intval($_POST['wccb_has_rod']) : 0,
            'rod_count' => isset($_POST['wccb_rod_count']) ? intval($_POST['wccb_rod_count']) : 0,
        ];
        $cart_item_data['wccb_unique_key'] = md5(json_encode($cart_item_data['wccb_data']));
    }
    return $cart_item_data;
}

add_action('woocommerce_before_calculate_totals', 'wccb_set_cart_item_price', 20, 1);
function wccb_set_cart_item_price($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }
    
    foreach ($cart->get_cart() as $cart_item) {
        if (isset($cart_item['wccb_data'])) {
            $data = $cart_item['wccb_data'];
            $product_config = [
                'height' => $data['height'],
                'panel_width' => $data['panel_width'],
                'has_rod' => $data['has_rod'],
                'rod_count' => $data['rod_count'],
                'has_shelves' => true,
                'has_drawers' => !empty($data['drawers']),
            ];
            
            $user_input = [
                'width' => $data['width'],
                'color' => $data['color'],
                'shelves' => $data['shelves'],
                'drawers' => $data['drawers'],
            ];
            
            $price = wccb_calculate_price($product_config, $user_input);
            if ($price > 0) {
                $cart_item['data']->set_price($price);
            }
        }
    }
}

add_filter('woocommerce_cart_item_price', 'wccb_cart_item_price', 10, 3);
function wccb_cart_item_price($price, $cart_item, $cart_item_key) {
    if (isset($cart_item['wccb_data'])) {
        $data = $cart_item['wccb_data'];
        
        $product_config = [
            'height' => $data['height'],
            'panel_width' => $data['panel_width'],
            'has_rod' => $data['has_rod'],
            'rod_count' => $data['rod_count'],
            'has_shelves' => true,
            'has_drawers' => !empty($data['drawers']),
        ];
        
        $user_input = [
            'width' => $data['width'],
            'color' => $data['color'],
            'shelves' => $data['shelves'],
            'drawers' => $data['drawers'],
        ];
        
        $calculated_price = wccb_calculate_price($product_config, $user_input);
        return wc_price($calculated_price);
    }
    return $price;
}

// ✅ تابع AJAX اصلاح‌شده با nonce
add_action('wp_ajax_wccb_calculate_price', 'wccb_ajax_calculate_price');
add_action('wp_ajax_nopriv_wccb_calculate_price', 'wccb_ajax_calculate_price');
function wccb_ajax_calculate_price() {
    // ✅ بررسی Nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wccb_calculate')) {
        wp_send_json_error(['message' => 'Invalid security token']);
        wp_die();
    }
    
    // ✅ اعتبارسنجی ورودی‌ها
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    if ($product_id <= 0) {
        wp_send_json_error(['message' => 'Invalid product ID']);
        wp_die();
    }
    
    $product_config = get_post_meta($product_id, '_wccb_product_config', true);
    if (empty($product_config) || !is_array($product_config)) {
        wp_send_json_error(['message' => 'Product configuration not found']);
        wp_die();
    }
    
    $width = isset($_POST['width']) ? intval($_POST['width']) : 20;
    $width = max(15, min(40, $width));
    
    $shelves = isset($_POST['shelves']) ? intval($_POST['shelves']) : 0;
    $shelves = max(0, min(15, $shelves));
    
    $color = isset($_POST['color']) ? sanitize_text_field($_POST['color']) : 'white';
    $color = in_array($color, ['white', 'gray', 'oak', 'other']) ? $color : 'white';
    
    $drawers = [];
    if (isset($_POST['drawers']) && !empty($_POST['drawers'])) {
        $decoded = json_decode(stripslashes($_POST['drawers']), true);
        if (is_array($decoded)) {
            foreach ($decoded as $key => $value) {
                $key = sanitize_text_field($key);
                if (is_array($value)) {
                    $drawers[$key] = array_map('intval', $value);
                } else {
                    $drawers[$key] = intval($value);
                }
            }
        }
    }
    
    $user_input = [
        'width' => $width,
        'color' => $color,
        'shelves' => $shelves,
        'drawers' => $drawers,
    ];
    
    $price = wccb_calculate_price($product_config, $user_input);
    wp_send_json_success(['price' => $price]);
    wp_die();
}

add_filter('woocommerce_get_item_data', 'wccb_display_cart_item_data', 10, 2);
function wccb_display_cart_item_data($item_data, $cart_item) {
    if (isset($cart_item['wccb_data'])) {
        $data = $cart_item['wccb_data'];
        $item_data[] = ['name' => 'Width', 'value' => $data['width'] . ' inches'];
        $item_data[] = ['name' => 'Color', 'value' => ucfirst($data['color'])];
        $item_data[] = ['name' => 'Shelves', 'value' => $data['shelves'] . ' pcs'];
        
        if (!empty($data['drawers'])) {
            $drawer_text = [];
            $custom_heights = $data['drawers']['custom_heights'] ?? [];
            
            foreach ($data['drawers'] as $type => $count) {
                if ($type === 'custom_heights') {
                    continue;
                }
                
                if ($type === 'custom') {
                    $height_parts = [];
                    if (!empty($custom_heights['custom_6'])) {
                        $height_parts[] = '6" (×' . $custom_heights['custom_6'] . ')';
                    }
                    if (!empty($custom_heights['custom_8'])) {
                        $height_parts[] = '8" (×' . $custom_heights['custom_8'] . ')';
                    }
                    if (!empty($custom_heights['custom_12'])) {
                        $height_parts[] = '12" (×' . $custom_heights['custom_12'] . ')';
                    }
                    
                    if (!empty($height_parts)) {
                        $drawer_text[] = 'Custom Drawer: ' . implode(' + ', $height_parts);
                    } else {
                        $drawer_text[] = 'Custom Drawer × ' . $count;
                    }
                } else {
                    $drawer_text[] = $type . ' × ' . $count;
                }
            }
            
            if (!empty($drawer_text)) {
                $item_data[] = ['name' => 'Drawers', 'value' => implode(' + ', $drawer_text)];
            }
        }
        
        if ($data['has_rod']) {
            $item_data[] = ['name' => 'Rod', 'value' => $data['rod_count'] . ' rod(s)'];
        }
    }
    return $item_data;
}

add_action('woocommerce_checkout_create_order_line_item', 'wccb_save_order_item_meta', 10, 4);
function wccb_save_order_item_meta($item, $cart_item_key, $values, $order) {
    if (isset($values['wccb_data'])) {
        $data = $values['wccb_data'];
        $item->add_meta_data('Width', $data['width'] . ' inches');
        $item->add_meta_data('Color', ucfirst($data['color']));
        $item->add_meta_data('Shelves', $data['shelves'] . ' pcs');
        
        if (!empty($data['drawers'])) {
            $drawer_text = [];
            $custom_heights = $data['drawers']['custom_heights'] ?? [];
            
            foreach ($data['drawers'] as $type => $count) {
                if ($type === 'custom_heights') {
                    continue;
                }
                
                if ($type === 'custom') {
                    $height_parts = [];
                    if (!empty($custom_heights['custom_6'])) {
                        $height_parts[] = '6" (×' . $custom_heights['custom_6'] . ')';
                    }
                    if (!empty($custom_heights['custom_8'])) {
                        $height_parts[] = '8" (×' . $custom_heights['custom_8'] . ')';
                    }
                    if (!empty($custom_heights['custom_12'])) {
                        $height_parts[] = '12" (×' . $custom_heights['custom_12'] . ')';
                    }
                    
                    if (!empty($height_parts)) {
                        $drawer_text[] = 'Custom Drawer: ' . implode(' + ', $height_parts);
                    } else {
                        $drawer_text[] = 'Custom Drawer × ' . $count;
                    }
                }
            }
            
            if (!empty($drawer_text)) {
                $item->add_meta_data('Drawers', implode(' + ', $drawer_text));
            }
        }
        
        if ($data['has_rod']) {
            $item->add_meta_data('Rod', $data['rod_count'] . ' rod(s)');
        }
    }
}

// ==========================================
// به‌روزرسانی مینی کارت
// ==========================================
add_filter('woocommerce_add_to_cart_fragments', 'wccb_refresh_mini_cart', 30, 1);
function wccb_refresh_mini_cart($fragments) {
    foreach (WC()->cart->get_cart() as $cart_item) {
        if (isset($cart_item['wccb_data'])) {
            ob_start();
            woocommerce_mini_cart();
            $fragments['div.widget_shopping_cart_content'] = '<div class="widget_shopping_cart_content">' . ob_get_clean() . '</div>';
            break;
        }
    }
    return $fragments;
}
