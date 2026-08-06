<?php
/**
 * Plugin Name: WooCommerce Cabinet Builder Pro
 * Description: Smart price calculator for custom cabinets with advanced panel-based pricing
 * Version: 3.1
 * Author: ITUNIFY
 * Text Domain: wc-cabinet
 * Requires Plugins: woocommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

// ==================== CONSTANTS ====================
define('WCCB_PATH', plugin_dir_path(__FILE__));
define('WCCB_URL', plugin_dir_url(__FILE__));
define('WCCB_VERSION', '3.1');

// ==================== DEFAULT SETTINGS ====================
function wccb_get_default_settings() {
    return [
        'panel_prices' => [
            12 => 16,
            16 => 20,
            24 => 30,
        ],
        'rod_prices' => [
            '15_20' => 3,
            '21_30' => 4,
            '31_40' => 5,
        ],
        'back_strip_prices' => [
            '15_20' => 2,
            '21_30' => 3,
            '31_40' => 4,
        ],
        'screw_price_per_shelf' => 2,
        'drawer_prices' => [
            'A'    => ['white' => 80, 'gray_oak' => 85, 'other' => 90],
            'A2'   => ['white' => 70, 'gray_oak' => 75, 'other' => 80],
            'C'    => ['white' => 110, 'gray_oak' => 115, 'other' => 120],
            'C2'   => ['white' => 95, 'gray_oak' => 100, 'other' => 105],
            'E'    => ['white' => 140, 'gray_oak' => 145, 'other' => 150],
            'E2'   => ['white' => 120, 'gray_oak' => 125, 'other' => 130],
            'J12'  => ['white' => 55, 'gray_oak' => 60, 'other' => 65],
            'J'    => ['white' => 60, 'gray_oak' => 65, 'other' => 70],
            'J24'  => ['white' => 70, 'gray_oak' => 75, 'other' => 80],
            'B'    => ['white' => 80, 'gray_oak' => 85, 'other' => 90],
            'B2'   => ['white' => 80, 'gray_oak' => 85, 'other' => 90],
            'D'    => ['white' => 110, 'gray_oak' => 115, 'other' => 120],
            'D2'   => ['white' => 95, 'gray_oak' => 100, 'other' => 105],
            'F'    => ['white' => 150, 'gray_oak' => 155, 'other' => 160],
            'F2'   => ['white' => 130, 'gray_oak' => 135, 'other' => 140],
            'J212' => ['white' => 55, 'gray_oak' => 60, 'other' => 65],
            'J2'   => ['white' => 60, 'gray_oak' => 65, 'other' => 70],
            'J224' => ['white' => 70, 'gray_oak' => 75, 'other' => 80],
            'G'    => ['white' => 90, 'gray_oak' => 95, 'other' => 100],
            'G2'   => ['white' => 85, 'gray_oak' => 90, 'other' => 95],
            'H'    => ['white' => 120, 'gray_oak' => 125, 'other' => 130],
            'H2'   => ['white' => 105, 'gray_oak' => 110, 'other' => 115],
            'I'    => ['white' => 160, 'gray_oak' => 165, 'other' => 170],
            'I2'   => ['white' => 140, 'gray_oak' => 145, 'other' => 150],
            'J30_12' => ['white' => 60, 'gray_oak' => 65, 'other' => 70],
            'J30'    => ['white' => 65, 'gray_oak' => 70, 'other' => 75],
            'J30_24' => ['white' => 75, 'gray_oak' => 80, 'other' => 85],
        ],
        'multipliers' => [
            'white' => 3,
            'gray' => 4.5,
            'oak' => 4.5,
            'other' => 6,
        ],
        'builder_multipliers' => [
            'white' => 2.8,
            'gray' => 4.2,
            'oak' => 4.2,
            'other' => 5.6,
        ],
        'custom_drawer_multipliers' => [
            '15_20' => 1,
            '21_30' => 1.5,
            '31_40' => 2,
        ],
        'custom_drawer_price_per_inch' => 5,
        'custom_drawer_width_price_per_inch' => 3,
    ];
}

register_activation_hook(__FILE__, 'wccb_install_defaults');
function wccb_install_defaults() {
    if (!get_option('wccb_settings')) {
        add_option('wccb_settings', wccb_get_default_settings());
    }
}

add_action('admin_init', 'wccb_check_woocommerce');
function wccb_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>WooCommerce Cabinet Builder Pro requires WooCommerce.</p></div>';
        });
        deactivate_plugins(plugin_basename(__FILE__));
    }
}

// ==========================================
// توابع محاسباتی اصلی (اصلاح‌شده)
// ==========================================

function wccb_get_panel_price($panel_width, $height) {
    $settings = get_option('wccb_settings', wccb_get_default_settings());
    $price_96 = $settings['panel_prices'][$panel_width] ?? 16;
    return ($price_96 / 96) * $height;
}

function wccb_get_rod_price($cabinet_width) {
    $settings = get_option('wccb_settings', wccb_get_default_settings());
    $base_price = 3;
    $price_per_inch = (5 - 3) / (40 - 15);
    $price = $base_price + ($cabinet_width - 15) * $price_per_inch;
    return round($price, 2);
}

function wccb_get_back_strip_price($cabinet_width) {
    $settings = get_option('wccb_settings', wccb_get_default_settings());
    $base_price = 2;
    $price_per_inch = (4 - 2) / (40 - 15);
    $price = $base_price + ($cabinet_width - 15) * $price_per_inch;
    return round($price, 2);
}

// ✅ تابع اصلاح‌شده با پشتیبانی از count و اعتبارسنجی
function wccb_get_drawer_price($drawer_type, $color, $count = 1, $cabinet_width = null, $drawer_height = 8) {
    $settings = get_option('wccb_settings', wccb_get_default_settings());
    
    // اعتبارسنجی ورودی‌ها
    $count = max(1, intval($count));
    $drawer_height = max(4, intval($drawer_height));
    $color = sanitize_text_field($color);
    
    if (isset($settings['drawer_prices'][$drawer_type])) {
        $prices = $settings['drawer_prices'][$drawer_type];
        $price = $prices[$color] ?? $prices['white'] ?? 0;
        return $price * $count;
    }
    
    if ($drawer_type === 'custom' && $cabinet_width !== null) {
        $base_price = wccb_get_custom_drawer_base_price($cabinet_width, $drawer_height);
        $multiplier = wccb_get_custom_drawer_multiplier($cabinet_width);
        return ($base_price * $multiplier) * $count;
    }
    
    return 0;
}

// ✅ تابع اصلاح‌شده با استفاده از wp_get_current_user
function wccb_get_multiplier($color) {
    $settings = get_option('wccb_settings', wccb_get_default_settings());
    $user = wp_get_current_user();
    
    // بررسی نقش‌های کاربر
    if (in_array('building_builder', (array) $user->roles) || in_array('maker', (array) $user->roles)) {
        $builder_multipliers = $settings['builder_multipliers'] ?? [
            'white' => 2.8,
            'gray' => 4.2,
            'oak' => 4.2,
            'other' => 5.6,
        ];
        return $builder_multipliers[$color] ?? 2.8;
    }
    
    if (in_array('wholesale', (array) $user->roles)) {
        return 1;
    }
    
    // بررسی تخفیف‌ها
    global $product;
    if ($product && $product->is_on_sale()) {
        return ($settings['multipliers'][$color] ?? 3) * 0.9;
    }
    
    return $settings['multipliers'][$color] ?? 3;
}

// ==========================================
// توابع کشوهای کاستوم
// ==========================================

function wccb_get_custom_drawer_base_price($cabinet_width, $drawer_height = 8) {
    $settings = get_option('wccb_settings', wccb_get_default_settings());
    $price_per_inch_width = $settings['custom_drawer_width_price_per_inch'] ?? 3;
    $price_per_inch_height = $settings['custom_drawer_price_per_inch'] ?? 5;
    return ($cabinet_width * $price_per_inch_width) + ($drawer_height * $price_per_inch_height);
}

function wccb_get_custom_drawer_multiplier($cabinet_width) {
    $settings = get_option('wccb_settings', wccb_get_default_settings());
    $multipliers = $settings['custom_drawer_multipliers'] ?? [
        '15_20' => 1,
        '21_30' => 1.5,
        '31_40' => 2,
    ];
    
    if ($cabinet_width <= 20) {
        return $multipliers['15_20'] ?? 1;
    } elseif ($cabinet_width <= 30) {
        return $multipliers['21_30'] ?? 1.5;
    } else {
        return $multipliers['31_40'] ?? 2;
    }
}

// ✅ تابع اصلاح‌شده با مدیریت بهتر خطاها
function wccb_calculate_price($product_config, $user_input) {
    $settings = get_option('wccb_settings', wccb_get_default_settings());
    
    $height = $product_config['height'] ?? 72;
    $panel_width = $product_config['panel_width'] ?? 12;
    $cabinet_width = $user_input['width'] ?? 20;
    $shelves = $user_input['shelves'] ?? 0;
    $color = $user_input['color'] ?? 'white';
    $drawers = $user_input['drawers'] ?? [];
    
    $panel_price = wccb_get_panel_price($panel_width, $height);
    $vertical_panels = 2 * $panel_price;
    
    $price_per_inch = $settings['panel_prices'][$panel_width] / 96;
    $horizontal_panel_price = $price_per_inch * $cabinet_width;
    $horizontal_panels = 2 * $horizontal_panel_price;
    
    $shelf_price = $price_per_inch * $cabinet_width;
    $shelves_total = $shelves * $shelf_price;
    
    $screws_for_shelves = $shelves * $settings['screw_price_per_shelf'];
    $screws_for_horizontal = 2 * $settings['screw_price_per_shelf'];
    
    $back_strip_price = wccb_get_back_strip_price($cabinet_width);
    
    $rod_total = 0;
    if (!empty($product_config['has_rod'])) {
        $rod_total = wccb_get_rod_price($cabinet_width) * ($product_config['rod_count'] ?? 1);
    }
    
    // محاسبه کشوها (اصلاح‌شده)
    $drawers_total = 0;
    if (!empty($product_config['has_drawers']) && !empty($drawers)) {
        $custom_heights = $drawers['custom_heights'] ?? [];
        
        foreach ($drawers as $drawer_type => $count) {
            if ($drawer_type === 'custom_heights') {
                continue;
            }
            
            // اعتبارسنجی count
            $count = intval($count);
            if ($count <= 0) {
                continue;
            }
            
            if ($drawer_type === 'custom') {
                $drawer_height = 8;
                if (!empty($custom_heights['custom_6'])) {
                    $drawer_height = 6;
                } elseif (!empty($custom_heights['custom_8'])) {
                    $drawer_height = 8;
                } elseif (!empty($custom_heights['custom_12'])) {
                    $drawer_height = 12;
                }
                $drawers_total += wccb_get_drawer_price('custom', $color, $count, $cabinet_width, $drawer_height);
            } else {
                $drawers_total += wccb_get_drawer_price($drawer_type, $color, $count);
            }
        }
    }
    
    $cabinet_cost = $vertical_panels + $horizontal_panels + $shelves_total + $screws_for_shelves + $screws_for_horizontal + $back_strip_price + $rod_total;
    $multiplier = wccb_get_multiplier($color);
    $cabinet_final_price = $cabinet_cost * $multiplier;
    $final_price = $cabinet_final_price + $drawers_total;
    
    // لاگ برای بررسی (فعال در حالت DEBUG)
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('🔍 [DEBUG] Cabinet Width: ' . $cabinet_width);
        error_log('🔍 [DEBUG] Color: ' . $color);
        error_log('🔍 [DEBUG] Multiplier: ' . $multiplier);
        error_log('🔍 [DEBUG] Cabinet Cost: ' . $cabinet_cost);
        error_log('🔍 [DEBUG] Final Price: ' . $final_price);
    }
    
    return round($final_price, 2);
}

// ==========================================
// تنظیم قیمت پیش‌فرض (با پشتیبانی از محصولات متغیر)
// ==========================================
add_action('save_post_product', 'wccb_set_default_price', 10, 3);
function wccb_set_default_price($post_id, $post, $update) {
    $enable_cabinet = get_post_meta($post_id, '_wccb_enable_cabinet', true);
    
    if ($enable_cabinet === '1') {
        $price = get_post_meta($post_id, '_regular_price', true);
        if (empty($price) || $price == 0) {
            update_post_meta($post_id, '_regular_price', 1);
            update_post_meta($post_id, '_price', 1);
        }
    }
}

// ✅ پشتیبانی از واریاسیون‌ها
add_action('woocommerce_variation_options_pricing', 'wccb_set_variation_default_price', 10, 3);
function wccb_set_variation_default_price($loop, $variation_data, $variation) {
    $enable_cabinet = get_post_meta($variation->ID, '_wccb_enable_cabinet', true);
    if ($enable_cabinet === '1') {
        update_post_meta($variation->ID, '_regular_price', 1);
        update_post_meta($variation->ID, '_price', 1);
    }
}

add_filter('woocommerce_is_purchasable', 'wccb_force_purchasable', 10, 2);
function wccb_force_purchasable($purchasable, $product) {
    if (is_admin()) {
        return $purchasable;
    }
    
    $product_id = $product->get_id();
    $enable_cabinet = get_post_meta($product_id, '_wccb_enable_cabinet', true);
    
    if ($enable_cabinet === '1') {
        return true;
    }
    
    return $purchasable;
}

// ==========================================
// مخفی کردن قیمت در آرشیو
// ==========================================
add_filter('woocommerce_get_price_html', 'wccb_hide_price_in_archive', 10, 2);
function wccb_hide_price_in_archive($price, $product) {
    if (!is_product()) {
        $product_id = $product->get_id();
        $enable_cabinet = get_post_meta($product_id, '_wccb_enable_cabinet', true);
        
        if ($enable_cabinet === '1') {
            return '<span class="wccb-custom-price">Custom Price</span>';
        }
    }
    return $price;
}

// ==========================================
// تغییر دکمه افزودن به سبد خرید در آرشیو
// ==========================================
add_filter('woocommerce_loop_add_to_cart_link', 'wccb_remove_add_to_cart_in_archive', 10, 2);
function wccb_remove_add_to_cart_in_archive($button, $product) {
    $product_id = $product->get_id();
    $enable_cabinet = get_post_meta($product_id, '_wccb_enable_cabinet', true);
    
    if ($enable_cabinet === '1') {
        $button_text = __('Select Options', 'woocommerce');
        $button = '<a href="' . esc_url($product->get_permalink()) . '" class="button wccb-select-options">' . $button_text . '</a>';
    }
    
    return $button;
}

// ==================== LOAD FILES ====================
add_action('plugins_loaded', 'wccb_load_files');
function wccb_load_files() {
    require_once WCCB_PATH . 'includes/cart-handler.php';
    
    if (is_admin()) {
        require_once WCCB_PATH . 'admin/settings.php';
        require_once WCCB_PATH . 'admin/product-meta-box.php';
    }
    
    if (!is_admin()) {
        require_once WCCB_PATH . 'frontend/product-fields.php';
    }
}

// ==========================================
// حذف دکمه Add to Cart پیش‌فرض
// ==========================================
add_action('woocommerce_single_product_summary', 'wccb_remove_default_add_to_cart', 1);
function wccb_remove_default_add_to_cart() {
    global $product;
    if (!$product) return;
    
    $enable_cabinet = get_post_meta($product->get_id(), '_wccb_enable_cabinet', true);
    if ($enable_cabinet === '1') {
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
    }
}
