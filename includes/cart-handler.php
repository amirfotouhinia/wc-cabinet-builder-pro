<?php
add_filter('woocommerce_add_cart_item_data', 'wccb_add_cart_item_data', 10, 3);
function wccb_add_cart_item_data($cart_item_data, $product_id, $variation_id) {
    if (isset($_POST['wccb_width'])) {
        $drawers_raw = json_decode(stripslashes($_POST['wccb_drawers_data']), true) ?: [];
        $drawers = is_array($drawers_raw) ? $drawers_raw : [];
        
        // ==========================================
        // دیباگ: بررسی داده‌های دریافتی از فرم
        // ==========================================
        error_log('🔍 [DEBUG ADD_TO_CART] drawers_raw: ' . print_r($drawers_raw, true));
        error_log('🔍 [DEBUG ADD_TO_CART] drawers: ' . print_r($drawers, true));
                
        $cart_item_data['wccb_data'] = [
            'width' => intval($_POST['wccb_width']),
            'color' => sanitize_text_field($_POST['wccb_color']),
            'shelves' => intval($_POST['wccb_shelves'] ?? 0),
            'drawers' => $drawers,
            'height' => intval($_POST['wccb_height']),
            'panel_width' => intval($_POST['wccb_panel_width']),
            'has_rod' => intval($_POST['wccb_has_rod'] ?? 0),
            'rod_count' => intval($_POST['wccb_rod_count'] ?? 0),
        ];
        $cart_item_data['wccb_unique_key'] = md5(json_encode($cart_item_data['wccb_data']));
    }
    return $cart_item_data;
}

add_action('woocommerce_before_calculate_totals', 'wccb_set_cart_item_price', 20, 1);
function wccb_set_cart_item_price($cart) {
    foreach ($cart->get_cart() as $cart_item) {
        if (isset($cart_item['wccb_data'])) {
            $data = $cart_item['wccb_data'];
            error_log('🔍 [DEBUG CART] Drawers Data: ' . print_r($data['drawers'], true));
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
            $cart_item['data']->set_price($price);
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

add_action('wp_ajax_wccb_calculate_price', 'wccb_ajax_calculate_price');
add_action('wp_ajax_nopriv_wccb_calculate_price', 'wccb_ajax_calculate_price');
function wccb_ajax_calculate_price() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wccb_calculate')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
    }
    
    $product_id = intval($_POST['product_id']);
    $product_config = get_post_meta($product_id, '_wccb_product_config', true);
    
    if (empty($product_config)) {
        wp_send_json_error(['message' => 'Product configuration not found']);
    }
    
    $user_input = [
        'width' => intval($_POST['width']),
        'color' => sanitize_text_field($_POST['color']),
        'shelves' => intval($_POST['shelves'] ?? 0),
        'drawers' => json_decode(stripslashes($_POST['drawers']), true) ?: [],
    ];
    
    $price = wccb_calculate_price($product_config, $user_input);
    wp_send_json_success(['price' => $price]);
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
                    // ✅ نمایش تعداد هر ارتفاع کشو
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
// ✅ به‌روزرسانی مینی کارت
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