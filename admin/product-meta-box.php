<?php
if (!defined('ABSPATH')) {
    exit;
}

// ==========================================
// افزودن متاباکس
// ==========================================
add_action('add_meta_boxes', 'wccb_add_product_meta_box');
function wccb_add_product_meta_box() {
    add_meta_box(
        'wccb_product_components',
        'Cabinet Builder Pro',
        'wccb_render_product_meta_box',
        'product',
        'normal',
        'high'
    );
}

// ==========================================
// رندر متاباکس
// ==========================================
function wccb_render_product_meta_box($post) {
    wp_nonce_field('wccb_product_meta', 'wccb_product_nonce');
    
    $enable_cabinet = get_post_meta($post->ID, '_wccb_enable_cabinet', true);
    $product_config = get_post_meta($post->ID, '_wccb_product_config', true);
    
    if (empty($product_config) || !is_array($product_config)) {
        $product_config = [
            'height' => 72,
            'panel_width' => 12,
            'allowed_depths' => [12, 16, 24],
            'has_rod' => false,
            'rod_count' => 1,
            'has_shelves' => false,
            'shelves_min' => 0,
            'shelves_max' => 0,
            'has_drawers' => false,
            'drawer_options' => [],
            'max_drawers' => 0,
        ];
    }
    
    $drawer_types = [
        'A' => 'A (18"×8"×16")',
        'A2' => 'A2 (18"×8"×12")',
        'B' => 'B (24"×8"×16")',
        'B2' => 'B2 (24"×8"×12")',
        'C' => 'C (18"×12"×16")',
        'C2' => 'C2 (18"×12"×12")',
        'D' => 'D (24"×12"×16")',
        'D2' => 'D2 (24"×12"×12")',
        'E' => 'E (18"×12"×24")',
        'E2' => 'E2 (18"×8"×24")',
        'F' => 'F (24"×12"×24")',
        'F2' => 'F2 (24"×8"×24")',
        'G' => 'G (30"×8"×16")',
        'G2' => 'G2 (30"×8"×12")',
        'H' => 'H (30"×12"×16")',
        'H2' => 'H2 (30"×12"×12")',
        'I' => 'I (30"×12"×24")',
        'I2' => 'I2 (30"×8"×24")',
        'J12' => 'J12 (18"×6"×12" Jewelry)',
        'J' => 'J (18"×6"×16" Jewelry)',
        'J24' => 'J24 (18"×6"×24" Jewelry)',
        'J212' => 'J212 (24"×6"×12" Jewelry)',
        'J2' => 'J2 (24"×6"×16" Jewelry)',
        'J224' => 'J224 (24"×6"×24" Jewelry)',
        'J30_12' => 'J30_12 (30"×6"×12" Jewelry)',
        'J30' => 'J30 (30"×6"×16" Jewelry)',
        'J30_24' => 'J30_24 (30"×6"×24" Jewelry)',
    ];
    
    $selected_drawers = $product_config['drawer_options'] ?? [];
    ?>
    <style>
        .wccb-section {
            background: #f5f5f5;
            padding: 15px;
            margin: 15px 0;
            border-left: 4px solid #007cba;
        }
        .wccb-section h4 {
            margin-top: 0;
        }
        .wccb-row {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin: 10px 0;
            align-items: center;
        }
        .wccb-row label {
            display: inline-block;
            min-width: 150px;
            font-weight: 600;
        }
        .wccb-row select,
        .wccb-row input[type="number"] {
            width: 150px;
        }
        .wccb-hint {
            color: #888;
            font-size: 12px;
            font-style: italic;
        }
        .wccb-drawer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 8px;
            margin: 10px 0;
        }
        .wccb-drawer-grid label {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 4px 8px;
            background: #f9f9f9;
            border-radius: 4px;
            font-weight: normal !important;
            min-width: auto !important;
        }
        .wccb-sub-section {
            margin-left: 30px;
            padding: 10px 15px;
            background: #fff;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
    </style>
    
    <div class="wccb-meta-box">
        <!-- Enable/Disable -->
        <p>
            <label style="font-weight: bold; font-size: 14px;">
                <input type="checkbox" name="wccb_enable_cabinet" value="1" <?php checked($enable_cabinet, '1'); ?> style="transform: scale(1.2); margin-right: 8px;">
                <strong>Enable Cabinet Builder</strong>
            </label>
        </p>
        <p class="wccb-hint">When enabled: Custom form appears on product page. Price calculated automatically.</p>
        
        <hr>
        
        <!-- Product Configuration -->
        <div class="wccb-section">
            <h4>Product Configuration</h4>
            
            <!-- Height -->
            <div class="wccb-row">
                <label>Height:</label>
                <select name="wccb_height">
                    <option value="16" <?php selected($product_config['height'], 16); ?>>16" (Hanging)</option>
                    <option value="48" <?php selected($product_config['height'], 48); ?>>48" (Hanging)</option>
                    <option value="72" <?php selected($product_config['height'], 72); ?>>72" (Hanging)</option>
                    <option value="84" <?php selected($product_config['height'], 84); ?>>84" (Start From Floor)</option>
                    <option value="96" <?php selected($product_config['height'], 96); ?>>96" (Start From Floor)</option>
                </select>
                <span class="wccb-hint">Select cabinet height</span>
            </div>
            
            <!-- ========================================== -->
            <!-- ✅ Panel Width (Depth) - فیلد جدید -->
            <!-- ========================================== -->
            <div class="wccb-row">
                <label>Panel Width (Depth):</label>
                <select name="wccb_panel_width">
                    <option value="12" <?php selected($product_config['panel_width'], 12); ?>>12"</option>
                    <option value="16" <?php selected($product_config['panel_width'], 16); ?>>16"</option>
                    <option value="24" <?php selected($product_config['panel_width'], 24); ?>>24"</option>
                </select>
                <span class="wccb-hint">Select the panel width (depth) for this product</span>
            </div>
            
            <!-- Available Depths -->
            <div class="wccb-row">
                <label>Available Depths:</label>
                <div style="display:flex; gap:15px; align-items:center; flex-wrap:wrap;">
                    <?php $allowed_depths = $product_config['allowed_depths'] ?? [12, 16, 24]; ?>
                    <label style="min-width:auto !important; font-weight:normal !important; display:flex; align-items:center; gap:5px;">
                        <input type="checkbox" name="wccb_allowed_depths[]" value="12" <?php checked(in_array(12, $allowed_depths)); ?>>
                        12"
                    </label>
                    <label style="min-width:auto !important; font-weight:normal !important; display:flex; align-items:center; gap:5px;">
                        <input type="checkbox" name="wccb_allowed_depths[]" value="16" <?php checked(in_array(16, $allowed_depths)); ?>>
                        16"
                    </label>
                    <label style="min-width:auto !important; font-weight:normal !important; display:flex; align-items:center; gap:5px;">
                        <input type="checkbox" name="wccb_allowed_depths[]" value="24" <?php checked(in_array(24, $allowed_depths)); ?>>
                        24"
                    </label>
                </div>
                <span class="wccb-hint">Select which depths are available for this product</span>
            </div>
            
            <!-- Rod -->
            <div class="wccb-row">
                <label>
                    <input type="checkbox" name="wccb_has_rod" value="1" <?php checked($product_config['has_rod'], true); ?>>
                    Has Rod
                </label>
                <?php if ($product_config['has_rod']): ?>
                <div class="wccb-sub-section">
                    <label>Rod Count:</label>
                    <select name="wccb_rod_count">
                        <option value="1" <?php selected($product_config['rod_count'], 1); ?>>1</option>
                        <option value="2" <?php selected($product_config['rod_count'], 2); ?>>2</option>
                    </select>
                </div>
                <?php else: ?>
                <input type="hidden" name="wccb_rod_count" value="1">
                <?php endif; ?>
            </div>
            
            <!-- Shelves -->
            <div class="wccb-row">
                <label>
                    <input type="checkbox" name="wccb_has_shelves" value="1" <?php checked($product_config['has_shelves'], true); ?>>
                    Has Shelves
                </label>
                <?php if ($product_config['has_shelves']): ?>
                <div class="wccb-sub-section">
                    <label>Min:</label>
                    <input type="number" name="wccb_shelves_min" value="<?php echo esc_attr($product_config['shelves_min']); ?>" min="0" max="15" style="width:60px;">
                    <label>Max:</label>
                    <input type="number" name="wccb_shelves_max" value="<?php echo esc_attr($product_config['shelves_max']); ?>" min="0" max="15" style="width:60px;">
                    <span class="wccb-hint">(0-15)</span>
                </div>
                <?php else: ?>
                <input type="hidden" name="wccb_shelves_min" value="0">
                <input type="hidden" name="wccb_shelves_max" value="0">
                <?php endif; ?>
            </div>
            
            <!-- Drawers -->
            <div class="wccb-row">
                <label>
                    <input type="checkbox" name="wccb_has_drawers" value="1" <?php checked($product_config['has_drawers'], true); ?>>
                    Has Drawers
                </label>
                <?php if ($product_config['has_drawers']): ?>
                <div class="wccb-sub-section">
                    <label>Max Drawers:</label>
                    <input type="number" name="wccb_max_drawers" value="<?php echo esc_attr($product_config['max_drawers']); ?>" min="1" max="10" style="width:60px;">
                </div>
                <?php else: ?>
                <input type="hidden" name="wccb_max_drawers" value="0">
                <?php endif; ?>
            </div>
            
            <!-- Drawer Options -->
            <?php if ($product_config['has_drawers']): ?>
            <div style="margin-top:15px; border-top:1px solid #ddd; padding-top:15px; margin-left:30px;">
                <h4>Available Drawer Types</h4>
                <p class="wccb-hint">Select which drawer types users can choose from:</p>
                
                <div class="wccb-drawer-grid">
                    <?php foreach ($drawer_types as $key => $label): ?>
                    <label>
                        <input type="checkbox" name="wccb_drawer_options[]" value="<?php echo esc_attr($key); ?>" <?php checked(in_array($key, $selected_drawers)); ?>>
                        <?php echo esc_html($label); ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php else: ?>
            <input type="hidden" name="wccb_drawer_options[]" value="">
            <?php endif; ?>
        </div>
        
        <p class="wccb-hint">💡 Prices are managed in <strong>Settings → Cabinet Builder</strong> globally.</p>
    </div>
    <?php
}

// ==========================================
// ذخیره متاباکس (اصلاح‌شده)
// ==========================================
add_action('save_post_product', 'wccb_save_product_meta_box');
function wccb_save_product_meta_box($post_id) {
    // ✅ دیباگ کامل
    error_log('🔍 WCCB Save - STARTED for post ID: ' . $post_id);
    error_log('🔍 WCCB Save - POST Data: ' . print_r($_POST, true));
    
    // ✅ بررسی Nonce با جزئیات بیشتر
    if (!isset($_POST['wccb_product_nonce'])) {
        error_log('❌ WCCB Save - Nonce not found in POST!');
        return;
    }
    
    if (!wp_verify_nonce($_POST['wccb_product_nonce'], 'wccb_product_meta')) {
        error_log('❌ WCCB Save - Nonce verification failed!');
        return;
    }
    
    error_log('🔍 WCCB Save - POST Data: ' . print_r($_POST, true));
    // ✅ بررسی Nonce
    if (!isset($_POST['wccb_product_nonce']) || !wp_verify_nonce($_POST['wccb_product_nonce'], 'wccb_product_meta')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // ✅ ذخیره Enable/Disable
    $enable_cabinet = isset($_POST['wccb_enable_cabinet']) ? '1' : '0';
    update_post_meta($post_id, '_wccb_enable_cabinet', $enable_cabinet);
    
    // ✅ دریافت مقدار panel_width از POST
    $panel_width = isset($_POST['wccb_panel_width']) ? intval($_POST['wccb_panel_width']) : 12;
    $panel_width = in_array($panel_width, [12, 16, 24]) ? $panel_width : 12;
    
    // ✅ ذخیره تنظیمات محصول با اعتبارسنجی
    $allowed_depths = [];
    if (isset($_POST['wccb_allowed_depths']) && is_array($_POST['wccb_allowed_depths'])) {
        $allowed_depths = array_map('intval', $_POST['wccb_allowed_depths']);
        $allowed_depths = array_filter($allowed_depths, function($v) {
            return in_array($v, [12, 16, 24]);
        });
    }
    
    $drawer_options = [];
    if (isset($_POST['wccb_drawer_options']) && is_array($_POST['wccb_drawer_options'])) {
        $drawer_options = array_map('sanitize_text_field', $_POST['wccb_drawer_options']);
    }
    
    $product_config = [
        'height' => isset($_POST['wccb_height']) ? intval($_POST['wccb_height']) : 72,
        'panel_width' => $panel_width,  // ✅ مقدار صحیح از POST
        'allowed_depths' => $allowed_depths,
        'has_rod' => isset($_POST['wccb_has_rod']),
        'rod_count' => isset($_POST['wccb_rod_count']) ? intval($_POST['wccb_rod_count']) : 1,
        'has_shelves' => isset($_POST['wccb_has_shelves']),
        'shelves_min' => isset($_POST['wccb_shelves_min']) ? intval($_POST['wccb_shelves_min']) : 0,
        'shelves_max' => isset($_POST['wccb_shelves_max']) ? intval($_POST['wccb_shelves_max']) : 0,
        'has_drawers' => isset($_POST['wccb_has_drawers']),
        'drawer_options' => $drawer_options,
        'max_drawers' => isset($_POST['wccb_max_drawers']) ? intval($_POST['wccb_max_drawers']) : 0,
    ];
    
    update_post_meta($post_id, '_wccb_product_config', $product_config);
}
