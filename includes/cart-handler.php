<?php
// ==========================================
// ✅ فایل اصلاح‌شده با دیباگ کامل و پشتیبانی از کشوها و panel_width
// ==========================================

// ==========================================
// 🔍 دیباگ اولیه
// ==========================================
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('========== 🚀 WCCB HANDLER INIT ==========');
    error_log('📝 POST Data: ' . print_r($_POST, true));
}

// ==========================================
// ✅ اضافه کردن داده‌ها به سبد خرید
// ==========================================
add_filter('woocommerce_add_cart_item_data', 'wccb_add_cart_item_data', 10, 3);
function wccb_add_cart_item_data($cart_item_data, $product_id, $variation_id) {
    if (isset($_POST['wccb_width'])) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('🔍 [ADD TO CART] ===== START =====');
            error_log('🔍 [ADD TO CART] Product ID: ' . $product_id);
            error_log('🔍 [ADD TO CART] POST Data: ' . print_r($_POST, true));
        }
        
        // ✅ اعتبارسنجی و پاکسازی داده‌های JSON برای کشوها
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
        
        // ✅ دیباگ: ثبت داده‌های کشوها
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('🔍 [ADD TO CART] Drawers Data: ' . print_r($drawers, true));
        }
        
        // ✅ اعتبارسنجی ابعاد
        $width = isset($_POST['wccb_width']) ? intval($_POST['wccb_width']) : 20;
        $width = max(15, min(40, $width));
        
        $height = isset($_POST['wccb_height']) ? intval($_POST['wccb_height']) : 72;
        $height = max(16, min(96, $height));
        
        $shelves = isset($_POST['wccb_shelves']) ? intval($_POST['wccb_shelves']) : 0;
        $shelves = max(0, min(15, $shelves));
        
        $panel_width = isset($_POST['wccb_panel_width']) ? intval($_POST['wccb_panel_width']) : 12;
        $panel_width = in_array($panel_width, [12, 16, 24]) ? $panel_width : 12;
        
        // ✅ دیباگ: ثبت panel_width
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('🔍 [ADD TO CART] Panel Width from POST: ' . $panel_width);
            error_log('🔍 [ADD TO CART] All POST keys: ' . print_r(array_keys($_POST), true));
        }
        
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
        
        // ✅ دیباگ: ثبت داده‌های نهایی
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('🔍 [ADD TO CART] Final wccb_data: ' . print_r($cart_item_data['wccb_data'], true));
            error_log('🔍 [ADD TO CART] ===== END =====');
        }
        
        $cart_item_data['wccb_unique_key'] = md5(json_encode($cart_item_data['wccb_data']));
    }
    return $cart_item_data;
}

// ==========================================
// ✅ تنظیم قیمت در سبد خرید
// ==========================================
add_action('woocommerce_before_calculate_totals', 'wccb_set_cart_item_price', 20, 1);
function wccb_set_cart_item_price($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }
    
    // ✅ جلوگیری از اجرای مکرر
    static $already_ran = false;
    if ($already_ran) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('🔍 [CART] Skipping duplicate execution');
        }
        return;
    }
    $already_ran = true;
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('🔍 [CART] ===== START CALCULATION =====');
        error_log('🔍 [CART] Cart items count: ' . count($cart->get_cart()));
    }
    
    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        if (isset($cart_item['wccb_data'])) {
            $data = $cart_item['wccb_data'];
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('🔍 [CART] Item Key: ' . $cart_item_key);
                error_log('🔍 [CART] Product ID: ' . $cart_item['product_id']);
                error_log('🔍 [CART] Full Data: ' . print_r($data, true));
                error_log('🔍 [CART] Panel Width from data: ' . $data['panel_width']);
                error_log('🔍 [CART] Drawers Data: ' . print_r($data['drawers'], true));
            }
            
            // ✅ دریافت Product Config از متادیتا
            $product_id = $cart_item['product_id'];
            $product_config = get_post_meta($product_id, '_wccb_product_config', true);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('🔍 [CART] Product Config from meta: ' . print_r($product_config, true));
            }
            
            // ✅ اگر Product Config موجود نبود، از داده‌های پیش‌فرض استفاده کن
            if (empty($product_config) || !is_array($product_config)) {
                $product_config = [
                    'height' => $data['height'],
                    'panel_width' => $data['panel_width'],
                    'has_rod' => $data['has_rod'] ?? 0,
                    'rod_count' => $data['rod_count'] ?? 0,
                    'has_shelves' => true,
                    'has_drawers' => !empty($data['drawers']),
                ];
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('🔍 [CART] Using default product config');
                }
            } else {
                // ✅ اطمینان از اینکه panel_width از داده‌های کاربر استفاده می‌شه
                $product_config['panel_width'] = $data['panel_width'];
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('🔍 [CART] Updated product config panel_width to: ' . $data['panel_width']);
                }
            }
            
            $user_input = [
                'width' => $data['width'],
                'color' => $data['color'],
                'shelves' => $data['shelves'],
                'panel_width' => $data['panel_width'],
                'drawers' => $data['drawers'],
            ];
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('🔍 [CART] Final Product Config: ' . print_r($product_config, true));
                error_log('🔍 [CART] Final User Input: ' . print_r($user_input, true));
            }
            
            $price = wccb_calculate_price($product_config, $user_input);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('🔍 [CART] Calculated Price: ' . $price);
            }
            
            if ($price > 0) {
                $cart_item['data']->set_price($price);
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('🔍 [CART] Price set to: ' . $price);
                }
            } else {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('🔍 [CART] ⚠️ Price is zero or negative!');
                }
            }
        }
    }
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('🔍 [CART] ===== END CALCULATION =====');
    }
}

// ==========================================
// ✅ نمایش قیمت در سبد خرید
// ==========================================
add_filter('woocommerce_cart_item_price', 'wccb_cart_item_price', 10, 3);
function wccb_cart_item_price($price, $cart_item, $cart_item_key) {
    if (isset($cart_item['wccb_data'])) {
        $data = $cart_item['wccb_data'];
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('🔍 [CART PRICE] ===== START =====');
            error_log('🔍 [CART PRICE] Item Key: ' . $cart_item_key);
            error_log('🔍 [CART PRICE] Panel Width: ' . $data['panel_width']);
        }
        
        // ✅ استفاده از Product Config واقعی
        $product_id = $cart_item['product_id'];
        $product_config = get_post_meta($product_id, '_wccb_product_config', true);
        
        if (empty($product_config) || !is_array($product_config)) {
            $product_config = [
                'height' => $data['height'],
                'panel_width' => $data['panel_width'],
                'has_rod' => $data['has_rod'] ?? 0,
                'rod_count' => $data['rod_count'] ?? 0,
                'has_shelves' => true,
                'has_drawers' => !empty($data['drawers']),
            ];
        } else {
            $product_config['panel_width'] = $data['panel_width'];
        }
        
        $user_input = [
            'width' => $data['width'],
            'color' => $data['color'],
            'shelves' => $data['shelves'],
            'panel_width' => $data['panel_width'],
            'drawers' => $data['drawers'],
        ];
        
        $calculated_price = wccb_calculate_price($product_config, $user_input);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('🔍 [CART PRICE] Calculated: ' . $calculated_price);
            error_log('🔍 [CART PRICE] ===== END =====');
        }
        
        return wc_price($calculated_price);
    }
    return $price;
}

// ==========================================
// ✅ AJAX محاسبه قیمت
// ==========================================
add_action('wp_ajax_wccb_calculate_price', 'wccb_ajax_calculate_price');
add_action('wp_ajax_nopriv_wccb_calculate_price', 'wccb_ajax_calculate_price');
function wccb_ajax_calculate_price() {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('🔍 [AJAX] ===== START =====');
        error_log('🔍 [AJAX] POST Data: ' . print_r($_POST, true));
    }
    
    // ✅ بررسی Nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wccb_calculate')) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('🔍 [AJAX] ⚠️ Invalid nonce');
        }
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
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('🔍 [AJAX] Product Config from meta: ' . print_r($product_config, true));
    }
    
    $width = isset($_POST['width']) ? intval($_POST['width']) : 20;
    $width = max(15, min(40, $width));
    
    $shelves = isset($_POST['shelves']) ? intval($_POST['shelves']) : 0;
    $shelves = max(0, min(15, $shelves));
    
    $color = isset($_POST['color']) ? sanitize_text_field($_POST['color']) : 'white';
    $color = in_array($color, ['white', 'gray', 'oak', 'other']) ? $color : 'white';
    
    // ✅ دریافت panel_width از درخواست AJAX
    $panel_width = isset($_POST['panel_width']) ? intval($_POST['panel_width']) : 12;
    $panel_width = in_array($panel_width, [12, 16, 24]) ? $panel_width : 12;
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('🔍 [AJAX] Panel Width: ' . $panel_width);
    }
    
    // ✅ دریافت داده‌های کشوها
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
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('🔍 [AJAX] Drawers Data: ' . print_r($drawers, true));
    }
    
    // ✅ به‌روزرسانی product_config با panel_width
    $product_config['panel_width'] = $panel_width;
    
    $user_input = [
        'width' => $width,
        'color' => $color,
        'shelves' => $shelves,
        'panel_width' => $panel_width,
        'drawers' => $drawers,
    ];
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('🔍 [AJAX] Final Product Config: ' . print_r($product_config, true));
        error_log('🔍 [AJAX] Final User Input: ' . print_r($user_input, true));
    }
    
    $price = wccb_calculate_price($product_config, $user_input);
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('🔍 [AJAX] Calculated Price: ' . $price);
        error_log('🔍 [AJAX] ===== END =====');
    }
    
    wp_send_json_success(['price' => $price]);
    wp_die();
}

// ==========================================
// ✅ نمایش داده‌ها در سبد خرید
// ==========================================
add_filter('woocommerce_get_item_data', 'wccb_display_cart_item_data', 10, 2);
function wccb_display_cart_item_data($item_data, $cart_item) {
    if (isset($cart_item['wccb_data'])) {
        $data = $cart_item['wccb_data'];
        $item_data[] = ['name' => 'Width', 'value' => $data['width'] . ' inches'];
        $item_data[] = ['name' => 'Color', 'value' => ucfirst($data['color'])];
        $item_data[] = ['name' => 'Shelves', 'value' => $data['shelves'] . ' pcs'];
        $item_data[] = ['name' => 'Panel Depth', 'value' => $data['panel_width'] . ' inches'];
        
        if (!empty($data['drawers'])) {
            $drawer_text = [];
            $custom_heights = isset($data['drawers']['custom_heights']) && is_array($data['drawers']['custom_heights']) 
                ? $data['drawers']['custom_heights'] 
                : [];
            
            foreach ($data['drawers'] as $type => $count) {
                if ($type === 'custom_heights') {
                    continue;
                }
                
                if (strpos($type, 'custom_') === 0) {
                    $height_label = str_replace('custom_', '', $type) . '"';
                    $drawer_text[] = 'Custom Drawer ' . $height_label . ' (×' . $count . ')';
                } else {
                    $drawer_text[] = $type . ' (×' . $count . ')';
                }
            }
            
            if (!empty($custom_heights)) {
                $height_map = [
                    'custom_6' => '6"',
                    'custom_8' => '8"',
                    'custom_12' => '12"',
                ];
                
                foreach ($custom_heights as $type => $count) {
                    if ($count > 0) {
                        $height_label = $height_map[$type] ?? $type;
                        $drawer_text[] = 'Custom Drawer ' . $height_label . ' (×' . $count . ')';
                    }
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

// ==========================================
// ✅ ذخیره متا دیتا در سفارش
// ==========================================
add_action('woocommerce_checkout_create_order_line_item', 'wccb_save_order_item_meta', 10, 4);
function wccb_save_order_item_meta($item, $cart_item_key, $values, $order) {
    if (isset($values['wccb_data'])) {
        $data = $values['wccb_data'];
        $item->add_meta_data('Width', $data['width'] . ' inches');
        $item->add_meta_data('Color', ucfirst($data['color']));
        $item->add_meta_data('Shelves', $data['shelves'] . ' pcs');
        $item->add_meta_data('Panel Depth', $data['panel_width'] . ' inches');
        
        if (!empty($data['drawers'])) {
            $drawer_text = [];
            $custom_heights = isset($data['drawers']['custom_heights']) && is_array($data['drawers']['custom_heights']) 
                ? $data['drawers']['custom_heights'] 
                : [];
            
            foreach ($data['drawers'] as $type => $count) {
                if ($type === 'custom_heights') {
                    continue;
                }
                
                if (strpos($type, 'custom_') === 0) {
                    $height_label = str_replace('custom_', '', $type) . '"';
                    $drawer_text[] = 'Custom Drawer ' . $height_label . ' (×' . $count . ')';
                } else {
                    $drawer_text[] = $type . ' (×' . $count . ')';
                }
            }
            
            if (!empty($custom_heights)) {
                $height_map = [
                    'custom_6' => '6"',
                    'custom_8' => '8"',
                    'custom_12' => '12"',
                ];
                
                foreach ($custom_heights as $type => $count) {
                    if ($count > 0) {
                        $height_label = $height_map[$type] ?? $type;
                        $drawer_text[] = 'Custom Drawer ' . $height_label . ' (×' . $count . ')';
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

// ==========================================
// ✅ تابع کمکی برای دیباگ (در صورت نیاز)
// ==========================================
if (!function_exists('wccb_debug_log')) {
    function wccb_debug_log($message, $data = null) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $log = '🔍 [WCCB DEBUG] ' . $message;
            if ($data !== null) {
                $log .= ': ' . print_r($data, true);
            }
            error_log($log);
        }
    }
}
