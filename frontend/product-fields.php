<?php
/**
 * Cabinet Builder - Product Fields
 * نمایش فرم سفارشی در صفحه محصول
 */

// ==========================================
// هوک‌های پیشنهادی برای قالب‌های مختلف
// ==========================================

function wccb_find_best_hook() {
    $hooks = [
        'woocommerce_single_product_summary' => 31,
        'woodmart_after_product_title' => 10,
        'woodmart_single_product_additional_info' => 10,
        'woocommerce_before_add_to_cart_button' => 10,
        'woocommerce_after_add_to_cart_button' => 10,
        'woocommerce_before_add_to_cart_form' => 10,
    ];
    
    foreach ($hooks as $hook => $priority) {
        if (has_action($hook) || $hook === 'woocommerce_single_product_summary') {
            return ['hook' => $hook, 'priority' => $priority];
        }
    }
    
    return ['hook' => 'woocommerce_single_product_summary', 'priority' => 31];
}

// ==========================================
// بارگذاری استایل و اسکریپت (در هوک درست)
// ==========================================
add_action('wp_enqueue_scripts', 'wccb_enqueue_product_assets');
function wccb_enqueue_product_assets() {
    if (is_product()) {
        $user = wp_get_current_user();
        $is_building_builder = in_array('building_builder', (array) $user->roles) || in_array('maker', (array) $user->roles);
        
        wp_enqueue_style('wccb-style', WCCB_URL . 'assets/style.css', [], WCCB_VERSION);
        wp_enqueue_script('wccb-calculator', WCCB_URL . 'frontend/js/calculator.php', ['jquery'], time(), true);
        wp_localize_script('wccb-calculator', 'wccb_vars', [
            'isBuildingBuilder' => $is_building_builder ? 'true' : 'false',
        ]);
    }
}

// ==========================================
// نمایش فرم محصول
// ==========================================
$best_hook = wccb_find_best_hook();
add_action($best_hook['hook'], 'wccb_product_fields', $best_hook['priority']);

function wccb_product_fields() {
    global $product;
    
    if (!$product) {
        return;
    }
    
    $product_id = $product->get_id();
    
    // Check if Cabinet Builder is enabled
    $enable_cabinet = get_post_meta($product_id, '_wccb_enable_cabinet', true);
    
    if ($enable_cabinet !== '1') {
        return;
    }
    
    // Get product configuration
    $product_config = get_post_meta($product_id, '_wccb_product_config', true);
    
    if (empty($product_config) || !is_array($product_config)) {
        return;
    }
    
    $height = $product_config['height'];
    $panel_width = $product_config['panel_width'];
    $allowed_depths = $product_config['allowed_depths'] ?? [12, 16, 24];
    $first_depth = !empty($allowed_depths) ? $allowed_depths[0] : 12;
    $has_rod = $product_config['has_rod'];
    $rod_count = $product_config['rod_count'];
    $has_shelves = $product_config['has_shelves'];
    $shelves_min = $product_config['shelves_min'];
    $shelves_max = $product_config['shelves_max'];
    $has_drawers = $product_config['has_drawers'];
    $drawer_options = $product_config['drawer_options'] ?? [];
    $max_drawers = $product_config['max_drawers'] ?? 0;
    
    // ایجاد آرایه کشوهای مجاز برای جاوااسکریپت
    $drawer_options_json = json_encode($drawer_options);
    // شروع فرم
// شروع فرم
echo '<form class="cart" action="' . esc_url(apply_filters('woocommerce_add_to_cart_form_action', wc_get_cart_url())) . '" method="post" enctype="multipart/form-data">';

// ✅ اضافه کردن variation_id پیش‌فرض
$variation_id = 0;
if ($product->is_type('variable')) {
    $available_variations = $product->get_available_variations();
    if (!empty($available_variations)) {
        $variation_id = $available_variations[0]['variation_id'];
    }
}
echo '<input type="hidden" name="variation_id" value="' . esc_attr($variation_id) . '">';
    ?>
    <div class="wccb-fields" style="margin:20px 0; padding:20px; border:3px solid #007cba; border-radius:8px; background:#f0f8ff;">
        <h3 style="color:#007cba; margin-top:0;">🔧 Cabinet Builder</h3>
        <p style="color:#333;">Height: <strong><?php echo $height; ?> inches</strong></p>
                
        <!-- Width Slider -->
        <div class="wccb-field" style="margin:15px 0;">
            <label style="font-weight:600; display:inline-block; width:150px;">Width:</label>
            <input type="range" id="wccb_width_slider" min="15" max="40" value="20" step="1" style="width:200px;">
            <span id="wccb_width_display" style="font-weight:bold; font-size:18px; color:#007cba;">20</span> inches
            <input type="hidden" name="wccb_width" id="wccb_width" value="20">
        </div>
<!-- Custom Drawer Warning (قرمز - غیراستاندارد) -->
<?php if ($has_drawers && $max_drawers > 0 && !empty($drawer_options)): ?>
<div id="wccb_width_warning" style="display:none; margin:10px 0; padding:12px 18px; background:#fff3f3; border-left:4px solid #d63638; border-radius:4px; color:#d63638; font-size:14px;">
    <strong>⚠️ Warning:</strong> This width (<span id="wccb_warning_width" style="font-weight:bold;">20</span>") is not standard.<br>
    <span style="color:#555; font-size:13px;">
        Standard sizes: <strong>18"</strong>, <strong>24"</strong>, or <strong>30"</strong>.
        Custom drawer pricing will be applied.
    </span>
    <br>
    <span style="color:#28a745; font-size:13px;">
        💡 <strong>Tip:</strong> Choose a standard size to save money!
    </span>
</div>

<!-- ✅ Standard Width Success (سبز - استاندارد) -->
<div id="wccb_width_success" style="display:none; margin:10px 0; padding:10px 15px; background:#f0f8f0; border-left:4px solid #28a745; border-radius:4px; color:#28a745; font-size:14px;">
    ✅ <strong>Standard Width:</strong> This width (<span id="wccb_success_width" style="font-weight:bold;">18</span>") is standard.
    <span style="color:#555; font-size:13px;">
        Standard drawer pricing will be applied.
    </span>
    <br>
    <span style="color:#28a745; font-size:13px;">
        💡 Great choice! Standard sizes help you save money.
    </span>
</div>
<?php endif; ?>
        <!-- Depth Selection (Panel Width) -->
        <div class="wccb-field" style="margin:15px 0;">
            <label style="font-weight:600; display:inline-block; width:150px;">Depth:</label>
            <div style="display:inline-block;">
                <?php foreach ($allowed_depths as $depth): ?>
                <button type="button" class="wccb-depth-btn <?php echo $depth === $first_depth ? 'active' : ''; ?>" data-depth="<?php echo $depth; ?>" style="padding:6px 15px; border:3px solid <?php echo $depth === $first_depth ? '#007cba' : '#ddd'; ?>; border-radius:5px; background:<?php echo $depth === $first_depth ? '#e7f1ff' : '#fff'; ?>; cursor:pointer; font-weight:600; margin-right:5px;">
                    <?php echo $depth; ?>"
                </button>
                <?php endforeach; ?>
                <input type="hidden" name="wccb_panel_width" id="wccb_panel_width" value="<?php echo $first_depth; ?>">
            </div>
        </div>
        
        <!-- Color -->
        <div class="wccb-field" style="margin:15px 0;">
            <label style="font-weight:600; display:inline-block; width:150px;">Color:</label>
            <button type="button" class="wccb-color-btn active" data-color="white" style="padding:6px 15px; border:3px solid #007cba; border-radius:5px; background:#fff; cursor:pointer; font-weight:600;">White</button>
            <button type="button" class="wccb-color-btn" data-color="gray" style="padding:6px 15px; border:3px solid #ddd; border-radius:5px; background:#808080; color:#fff; cursor:pointer;">Gray</button>
            <button type="button" class="wccb-color-btn" data-color="oak" style="padding:6px 15px; border:3px solid #ddd; border-radius:5px; background:#8B7355; color:#fff; cursor:pointer;">Oak</button>
            <button type="button" class="wccb-color-btn" data-color="other" style="padding:6px 15px; border:3px solid #ddd; border-radius:5px; background:linear-gradient(45deg,#ff6b6b,#ffd93d,#6bcb77,#4d96ff); color:#fff; cursor:pointer;">Other</button>
            <input type="hidden" name="wccb_color" id="wccb_color" value="white">
        </div>
        
        <!-- Shelves -->
        <?php if ($has_shelves): ?>
        <div class="wccb-field" style="margin:15px 0;">
            <label style="font-weight:600; display:inline-block; width:150px;">Shelves:</label>
            <input type="number" name="wccb_shelves" id="wccb_shelves" 
                   min="<?php echo $shelves_min; ?>" max="<?php echo $shelves_max; ?>" 
                   value="<?php echo $shelves_min; ?>" style="width:70px; padding:5px;">
            <span style="font-size:12px; color:#888;">(Min: <?php echo $shelves_min; ?>, Max: <?php echo $shelves_max; ?>)</span>
        </div>
        <?php else: ?>
        <input type="hidden" name="wccb_shelves" value="0">
        <?php endif; ?>
        
        <!-- Drawers -->
        <?php if ($has_drawers && $max_drawers > 0 && !empty($drawer_options)): ?>
        <div class="wccb-field" style="margin:15px 0;">
            <label style="font-weight:600; display:inline-block; width:150px;">Drawers:</label>
            <div id="wccb_drawer_container">
                <div class="wccb-drawer-row" style="display:flex; gap:10px; align-items:center; margin-bottom:8px;">
                    <select name="wccb_drawer_type[]" class="wccb-drawer-type" style="padding:5px; min-width:180px;">
                        <option value="">Select...</option>
                        <!-- گزینه‌ها توسط جاوااسکریپت پر می‌شوند -->
                    </select>
                    <input type="number" name="wccb_drawer_count[]" class="wccb-drawer-count" min="1" max="5" value="1" style="width:60px;">
                    <button type="button" class="wccb-remove-drawer" style="color:#d63638; background:none; border:none; font-size:20px; cursor:pointer;">×</button>
                </div>
            </div>
            <button type="button" id="wccb-add-drawer" class="button" style="margin-top:5px;">+ Add Drawer</button>
            <span style="font-size:12px; color:#888; display:block;">Max: <?php echo $max_drawers; ?> drawers</span>
            <input type="hidden" name="wccb_drawers_data" id="wccb_drawers_data" value="">
        </div>
        <?php else: ?>
        <input type="hidden" name="wccb_drawers_data" value="">
        <?php endif; ?>
        
        <!-- Rod -->
        <?php if ($has_rod): ?>
        <div class="wccb-field" style="margin:15px 0;">
            <label style="font-weight:600; display:inline-block; width:150px;">Rod:</label>
            <span style="font-weight:bold;"><?php echo $rod_count; ?> rod(s) included</span>
            <input type="hidden" name="wccb_has_rod" value="1">
            <input type="hidden" name="wccb_rod_count" value="<?php echo $rod_count; ?>">
        </div>
        <?php else: ?>
        <input type="hidden" name="wccb_has_rod" value="0">
        <?php endif; ?>
        
        <input type="hidden" name="wccb_back_strip" value="1">
        <input type="hidden" name="wccb_height" value="<?php echo $height; ?>">
        <input type="hidden" name="wccb_panel_width" id="wccb_panel_width_hidden" value="<?php echo $first_depth; ?>">
        
<!-- Price -->
<div style="margin-top:20px; padding:15px; background:#e9ecef; border-radius:8px; text-align:center;">
    <?php
    $user = wp_get_current_user();
    $is_builder = in_array('building_builder', (array) $user->roles) || in_array('maker', (array) $user->roles);
    ?>
    <h3 style="margin:0; color:#666; font-size:16px;">
        <?php echo $is_builder ? 'Special Price for You:' : 'Estimated Total Price:'; ?>
    </h3>
    <div id="wccb_price_display">
        <?php if ($is_builder): ?>
        <div style="display:flex; justify-content:center; align-items:center; gap:15px; flex-wrap:wrap;">
            <span id="wccb_regular_price" style="text-decoration:line-through; color:#999; font-size:18px;">$0.00</span>
            <span id="wccb_total_price" style="font-size:28px; font-weight:bold; color:#28a745;">$0.00</span>
            <span style="background:#28a745; color:#fff; padding:2px 12px; border-radius:20px; font-size:12px; font-weight:bold;">Special</span>
        </div>
        <?php else: ?>
        <span id="wccb_total_price" style="font-size:28px; font-weight:bold; color:#28a745;">$0.00</span>
        <?php endif; ?>
    </div>
</div>
        <!-- Add to Cart Button -->
        <div class="wccb-add-to-cart-wrapper" style="margin-top:20px; text-align:center;">
            <button type="submit" name="add-to-cart" value="<?php echo esc_attr($product_id); ?>" class="single_add_to_cart_button button alt wccb-custom-btn" style="width:100%; padding:15px; font-size:18px; border-radius:5px;">
                <?php echo esc_html__('Add to cart', 'woocommerce'); ?>
            </button>
        </div>
    <?php
    // پایان فرم
    echo '</form>';
    ?>
    
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        var maxDrawers = <?php echo $max_drawers; ?>;
        var drawerOptions = <?php echo $drawer_options_json; ?>;
        
        // ==========================================
        // منطق نمایش کشوها بر اساس Depth و Width
        // ==========================================
        function getAvailableDrawers(depth, width) {
            // کشوهای استاندارد بر اساس Depth و Width
            var standardDrawers = {
                '12': {
                    '18': ['A2', 'C2', 'J12'],
                    '24': ['B2', 'D2', 'J212'],
                    '30': ['G2', 'H2', 'J30_12']
                },
                '16': {
                    '18': ['A', 'C', 'J'],
                    '24': ['B', 'D', 'J2'],
                    '30': ['G', 'H', 'J30']
                },
                '24': {
                    '18': ['E', 'E2', 'J24'],
                    '24': ['F', 'F2', 'J224'],
                    '30': ['I', 'I2', 'J30_24']
                }
            };

            // اطلاعات کشوها برای نمایش
            var drawerLabels = {
                'A': 'A (8"×16")',
                'A2': 'A2 (8"×12")',
                'B': 'B (8"×16")',
                'B2': 'B2 (8"×12")',
                'C': 'C (12"×16")',
                'C2': 'C2 (12"×12")',
                'D': 'D (12"×16")',
                'D2': 'D2 (12"×12")',
                'E': 'E (12"×24")',
                'E2': 'E2 (8"×24")',
                'F': 'F (12"×24")',
                'F2': 'F2 (8"×24")',
                'G': 'G (8"×16")',
                'G2': 'G2 (8"×12")',
                'H': 'H (12"×16")',
                'H2': 'H2 (12"×12")',
                'I': 'I (12"×24")',
                'I2': 'I2 (8"×24")',
                'J12': 'J12 (6"×12" Jewelry)',
                'J': 'J (6"×16" Jewelry)',
                'J24': 'J24 (6"×24" Jewelry)',
                'J212': 'J212 (6"×12" Jewelry)',
                'J2': 'J2 (6"×16" Jewelry)',
                'J224': 'J224 (6"×24" Jewelry)',
                'J30_12': 'J30_12 (6"×12" Jewelry)',
                'J30': 'J30 (6"×16" Jewelry)',
                'J30_24': 'J30_24 (6"×24" Jewelry)'
            };

            // اگر عرض استاندارد باشد (18, 24, 30)
            if (width === 18 || width === 24 || width === 30) {
                var drawers = standardDrawers[depth]?.[width] || [];
                // فیلتر بر اساس drawer_options انتخاب‌شده توسط ادمین
                if (drawerOptions.length > 0) {
                    drawers = drawers.filter(function(d) {
                        return drawerOptions.includes(d);
                    });
                }
                return drawers;
            }

            // عرض غیراستاندارد → کشو کاستوم// عرض غیراستاندارد → ۳ گزینه کشو کاستوم با ارتفاع‌های مختلف
            return ['custom_6', 'custom_8', 'custom_12'];
        }
// ==========================================
// بررسی عرض استاندارد برای کشوها
// ==========================================
function isStandardDrawerWidth(width) {
    return width === 18 || width === 24 || width === 30;
}

function updateWidthWarning() {
    var width = parseInt($('#wccb_width').val()) || 20;
    var hasDrawers = <?php echo $has_drawers ? 'true' : 'false'; ?>;
    var drawerOptions = <?php echo json_encode($drawer_options); ?>;
    
    // فقط اگر محصول کشو دارد و کشوهای استاندارد تعریف شده‌اند
    if (hasDrawers && drawerOptions.length > 0) {
        if (isStandardDrawerWidth(width)) {
            // ✅ عرض استاندارد → نمایش هشدار سبز، مخفی کردن هشدار قرمز
            $('#wccb_width_warning').hide();
            $('#wccb_width_success').show();
            $('#wccb_success_width').text(width);
        } else {
            // ⚠️ عرض غیراستاندارد → نمایش هشدار قرمز، مخفی کردن هشدار سبز
            $('#wccb_width_warning').show();
            $('#wccb_warning_width').text(width);
            $('#wccb_width_success').hide();
        }
    }
}
            // ==========================================
            // به‌روزرسانی لیست کشوها
            // ==========================================
            function updateDrawersList() {
                var container = $('#wccb_drawer_container');
                var rows = container.find('.wccb-drawer-row');
                
                // ✅ اگر maxDrawers == 0، هیچ کاری نکن
                if (maxDrawers === 0) {
                    return;
                }
                
                // اگر تعداد سطرها صفر است، یک سطر پیش‌فرض اضافه کن
                if (rows.length === 0) {
                    addDrawerRow();
                    return;
                }
                
                // به‌روزرسانی گزینه‌های هر سطر
                rows.each(function() {
                    updateDrawerRowOptions($(this));
                });
            }
                    
        // ==========================================
        // به‌روزرسانی گزینه‌های یک سطر کشو
        // ==========================================
        function updateDrawerRowOptions($row) {
            var width = parseInt($('#wccb_width').val()) || 20;
            var depth = parseInt($('#wccb_panel_width').val()) || 12;
            var availableDrawers = getAvailableDrawers(depth, width);
            
            var $select = $row.find('.wccb-drawer-type');
            var currentVal = $select.val();
            $select.empty();
            
            // گزینه پیش‌فرض
            $select.append($('<option>', { value: '', text: 'Select drawer...' }));
            
            // اضافه کردن گزینه‌های موجود
            var drawerLabels = {
                'A': 'A (8"×16")',
                'A2': 'A2 (8"×12")',
                'B': 'B (8"×16")',
                'B2': 'B2 (8"×12")',
                'C': 'C (12"×16")',
                'C2': 'C2 (12"×12")',
                'D': 'D (12"×16")',
                'D2': 'D2 (12"×12")',
                'E': 'E (12"×24")',
                'E2': 'E2 (8"×24")',
                'F': 'F (12"×24")',
                'F2': 'F2 (8"×24")',
                'G': 'G (8"×16")',
                'G2': 'G2 (8"×12")',
                'H': 'H (12"×16")',
                'H2': 'H2 (12"×12")',
                'I': 'I (12"×24")',
                'I2': 'I2 (8"×24")',
                'J12': 'J12 (6"×12" Jewelry)',
                'J': 'J (6"×16" Jewelry)',
                'J24': 'J24 (6"×24" Jewelry)',
                'J212': 'J212 (6"×12" Jewelry)',
                'J2': 'J2 (6"×16" Jewelry)',
                'J224': 'J224 (6"×24" Jewelry)',
                'J30_12': 'J30_12 (6"×12" Jewelry)',
                'J30': 'J30 (6"×16" Jewelry)',
                'J30_24': 'J30_24 (6"×24" Jewelry)'
            };
            
        for (var i = 0; i < availableDrawers.length; i++) {
            var key = availableDrawers[i];
            var label = key;
            
            if (key === 'custom_6') {
                label = '🔧 Custom Drawer 6" Height';
            } else if (key === 'custom_8') {
                label = '🔧 Custom Drawer 8" Height';
            } else if (key === 'custom_12') {
                label = '🔧 Custom Drawer 12" Height';
            } else if (key === 'custom') {
                label = '🔧 Custom Drawer (based on width)';
            } else {
                label = drawerLabels[key] || key;
            }
            
            $select.append($('<option>', {
                value: key,
                text: label
            }));
        }
            
            // اگر مقدار قبلی هنوز معتبر است، آن را انتخاب کن
            if (availableDrawers.includes(currentVal)) {
                $select.val(currentVal);
            } else {
                $select.val('');
            }
        }
        
        // ==========================================
        // اضافه کردن سطر جدید کشو
        // ==========================================
        function addDrawerRow() {
            var container = $('#wccb_drawer_container');
            var rowCount = container.find('.wccb-drawer-row').length;
            
            if (rowCount >= maxDrawers) {
                alert('Maximum ' + maxDrawers + ' drawers allowed.');
                return;
            }
            
            var $row = $('<div>', {
                class: 'wccb-drawer-row',
                style: 'display:flex; gap:10px; align-items:center; margin-bottom:8px;'
            });
            
            var $select = $('<select>', {
                name: 'wccb_drawer_type[]',
                class: 'wccb-drawer-type',
                style: 'padding:5px; min-width:180px;'
            });
            $select.append($('<option>', { value: '', text: 'Select drawer...' }));
            $row.append($select);
            
            var $count = $('<input>', {
                type: 'number',
                name: 'wccb_drawer_count[]',
                class: 'wccb-drawer-count',
                min: 1,
                max: 5,
                value: 1,
                style: 'width:60px; padding:5px;'
            });
            $row.append($count);
            
            var $removeBtn = $('<button>', {
                type: 'button',
                class: 'wccb-remove-drawer',
                style: 'color:#d63638; background:none; border:none; font-size:20px; cursor:pointer;',
                html: '×'
            });
            $row.append($removeBtn);
            
            container.append($row);
            
            // به‌روزرسانی گزینه‌های سطر جدید
            updateDrawerRowOptions($row);
            
            // رویدادها
            $row.find('.wccb-drawer-type, .wccb-drawer-count').on('change', updatePrice);
            $row.find('.wccb-remove-drawer').on('click', function() {
                if ($('#wccb_drawer_container .wccb-drawer-row').length > 1) {
                    $(this).closest('.wccb-drawer-row').remove();
                    updatePrice();
                }
            });
            
            updatePrice();
        }
        
        // ==========================================
        // دریافت داده‌های کشوها
        // ==========================================
        function getDrawersData() {
            // شروع با آرایه‌های خالی در هر بار صدا زدن
            var drawers = {};
            var customCounts = {};
            var standardDrawers = {};
            var totalCustomCount = 0;
            
            $('#wccb_drawer_container .wccb-drawer-row').each(function() {
                var type = $(this).find('.wccb-drawer-type').val();
                var count = parseInt($(this).find('.wccb-drawer-count').val()) || 0;
                
                if (type && count > 0) {
                    if (type === 'custom_6' || type === 'custom_8' || type === 'custom_12') {
                        if (!customCounts[type]) {
                            customCounts[type] = 0;
                        }
                        customCounts[type] += count;
                        totalCustomCount += count;
                    } else {
                        if (standardDrawers[type]) {
                            standardDrawers[type] += count;
                        } else {
                            standardDrawers[type] = count;
                        }
                    }
                }
            });
            
            // اضافه کردن کشوهای استاندارد
            for (var key in standardDrawers) {
                drawers[key] = standardDrawers[key];
            }
            
            // اضافه کردن کشوهای کاستوم
            var customHeights = {};
            for (var type in customCounts) {
                var count = customCounts[type];
                if (type === 'custom_6') {
                    customHeights['custom_6'] = count;
                } else if (type === 'custom_8') {
                    customHeights['custom_8'] = count;
                } else if (type === 'custom_12') {
                    customHeights['custom_12'] = count;
                }
            }
            
            if (totalCustomCount > 0) {
                drawers['custom'] = totalCustomCount;
                drawers['custom_heights'] = customHeights;
            }
            
            console.log('🔍 [DEBUG getDrawersData] drawers:', drawers);
            console.log('🔍 [DEBUG getDrawersData] totalCustomCount:', totalCustomCount);
            
            return drawers;
        }
        // ==========================================
        // محاسبه قیمت
        // ==========================================
        function updatePrice() {
            // به‌روزرسانی لیست کشوها
            updateDrawersList();
            updateWidthWarning(); // ✅ اضافه شد
            
            if (typeof calculatePrice === 'function') {
                var userInput = {
                    width: parseInt($('#wccb_width').val()) || 20,
                    color: $('#wccb_color').val() || 'white',
                    shelves: parseInt($('#wccb_shelves').val()) || 0,
                    panelWidth: parseInt($('#wccb_panel_width').val()) || <?php echo $first_depth; ?>,
                    drawers: getDrawersData()
                };
                
                var productConfig = {
                    height: <?php echo $height; ?>,
                    panelWidth: parseInt($('#wccb_panel_width').val()) || <?php echo $first_depth; ?>,
                    hasRod: <?php echo $has_rod ? 'true' : 'false'; ?>,
                    rodCount: <?php echo $rod_count; ?>
                };
                
var result = calculatePrice(productConfig, userInput);
console.log('🔍 [DEBUG updatePrice] result:', result);
var isBuilder = <?php echo current_user_can('building_builder') || current_user_can('maker') ? 'true' : 'false'; ?>;

if (isBuilder && typeof result === 'object' && result.regular !== undefined) {
    $('#wccb_regular_price').text('$' + result.regular.toFixed(2));
    $('#wccb_total_price').text('$' + result.builder.toFixed(2));
} else {
    var price = typeof result === 'object' ? result.final : result;
    $('#wccb_total_price').text('$' + price.toFixed(2));
}
            } else {
                $('#wccb_total_price').text('$0.00');
            }
        }
        
        // ==========================================
        // رویدادها
        // ==========================================
        
        // Width Slider
        $('#wccb_width_slider').on('input', function() {
            var val = parseInt($(this).val());
            $('#wccb_width_display').text(val);
            $('#wccb_width').val(val);
            updateWidthWarning(); // اضافه شد
            updatePrice();
        });
                
        // Color buttons
        $('.wccb-color-btn').on('click', function() {
            $('.wccb-color-btn').css('border', '3px solid #ddd');
            $(this).css('border', '3px solid #007cba');
            $('#wccb_color').val($(this).data('color'));
            updatePrice();
        });
        
        // Depth buttons
        $('.wccb-depth-btn').on('click', function() {
            $('.wccb-depth-btn').css({
                'border': '3px solid #ddd',
                'background': '#fff'
            });
            $(this).css({
                'border': '3px solid #007cba',
                'background': '#e7f1ff'
            });
            var depth = $(this).data('depth');
            $('#wccb_panel_width').val(depth);
            $('#wccb_panel_width_hidden').val(depth);
            updatePrice();
        });
        
        // Shelves
        $('#wccb_shelves').on('change input', updatePrice);
        
        // Add Drawer
        $('#wccb-add-drawer').on('click', addDrawerRow);
        
        // Remove Drawer (delegated)
        $(document).on('click', '.wccb-remove-drawer', function() {
            if ($('#wccb_drawer_container .wccb-drawer-row').length > 1) {
                $(this).closest('.wccb-drawer-row').remove();
                updatePrice();
            }
        });
        
        // Drawer type/count change (delegated)
        $(document).on('change', '.wccb-drawer-type, .wccb-drawer-count', updatePrice);
        
        // Form submit - جمع‌آوری داده‌های کشوها
        $('form.cart').on('submit', function() {
            $('#wccb_drawers_data').val(JSON.stringify(getDrawersData()));
        });
        
        // ==========================================
        // مقداردهی اولیه
        // ==========================================
        
        // اگر کشوها فعال هستند، یک سطر پیش‌فرض اضافه کن
        <?php if ($has_drawers && $max_drawers > 0 && !empty($drawer_options)): ?>
        if ($('#wccb_drawer_container .wccb-drawer-row').length === 0) {
            addDrawerRow();
        }
        <?php endif; ?>
        
        updatePrice();
    });
    </script>
    <?php
}