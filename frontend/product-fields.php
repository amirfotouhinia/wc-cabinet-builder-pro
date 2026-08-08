<?php
/**
 * Cabinet Builder - Product Fields (اصلاح‌شده)
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
// بارگذاری استایل و اسکریپت
// ==========================================
add_action('wp_enqueue_scripts', 'wccb_enqueue_product_assets');
function wccb_enqueue_product_assets() {
    if (is_product()) {
        $user = wp_get_current_user();
        $is_building_builder = in_array('building_builder', (array) $user->roles) || in_array('maker', (array) $user->roles);
        
        wp_enqueue_style('wccb-style', WCCB_URL . 'assets/style.css', [], WCCB_VERSION);
        wp_enqueue_script('wccb-calculator', WCCB_URL . 'frontend/js/calculator.php', ['jquery'], WCCB_VERSION, true);
        
        wp_localize_script('wccb-calculator', 'wccb_vars', [
            'isBuildingBuilder' => $is_building_builder ? 'true' : 'false',
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wccb_calculate'),
            'debug' => defined('WP_DEBUG') && WP_DEBUG ? 'true' : 'false',
        ]);
    }
}

// ==========================================
// نمایش فرم محصول (اصلاح‌شده)
// ==========================================
$best_hook = wccb_find_best_hook();
add_action($best_hook['hook'], 'wccb_product_fields', $best_hook['priority']);

function wccb_product_fields() {
    global $product;
    
    if (!$product) {
        return;
    }
    
    $product_id = $product->get_id();
    $enable_cabinet = get_post_meta($product_id, '_wccb_enable_cabinet', true);
    
    if ($enable_cabinet !== '1') {
        return;
    }
    
    $product_config = get_post_meta($product_id, '_wccb_product_config', true);
    if (empty($product_config) || !is_array($product_config)) {
        return;
    }
    
    $height = $product_config['height'] ?? 72;
    $panel_width = $product_config['panel_width'] ?? 12;
    $allowed_depths = $product_config['allowed_depths'] ?? [12, 16, 24];
    $first_depth = !empty($allowed_depths) ? $allowed_depths[0] : 12;
    $has_rod = $product_config['has_rod'] ?? false;
    $rod_count = $product_config['rod_count'] ?? 1;
    $has_shelves = $product_config['has_shelves'] ?? false;
    $shelves_min = $product_config['shelves_min'] ?? 0;
    $shelves_max = $product_config['shelves_max'] ?? 0;
    $has_drawers = $product_config['has_drawers'] ?? false;
    $drawer_options = $product_config['drawer_options'] ?? [];
    $max_drawers = $product_config['max_drawers'] ?? 0;
    
    $drawer_options_json = json_encode($drawer_options);
    
    // شروع فرم
    echo '<form class="cart" action="' . esc_url(apply_filters('woocommerce_add_to_cart_form_action', wc_get_cart_url())) . '" method="post" enctype="multipart/form-data">';
    
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
        <p style="color:#333;">Height: <strong><?php echo esc_html($height); ?> inches</strong></p>
        
        <!-- Width Slider -->
        <div class="wccb-field" style="margin:15px 0;">
            <label style="font-weight:600; display:inline-block; width:150px;">Width:</label>
            <input type="range" id="wccb_width_slider" min="15" max="40" value="20" step="1" style="width:200px;">
            <span id="wccb_width_display" style="font-weight:bold; font-size:18px; color:#007cba;">20</span> inches
            <input type="hidden" name="wccb_width" id="wccb_width" value="20">
        </div>
        
        <!-- Custom Drawer Warning -->
        <?php if ($has_drawers && $max_drawers > 0 && !empty($drawer_options)): ?>
        <div id="wccb_width_warning" style="display:none; margin:10px 0; padding:12px 18px; background:#fff3f3; border-left:4px solid #d63638; border-radius:4px; color:#d63638; font-size:14px;">
            <strong>⚠️ Warning:</strong> This width (<span id="wccb_warning_width" style="font-weight:bold;">20</span>") is not standard.<br>
            <span style="color:#555; font-size:13px;">
                Standard sizes: <strong>18"</strong>, <strong>24"</strong>, or <strong>30"</strong>.
                Custom drawer pricing will be applied.
            </span>
        </div>
        
        <div id="wccb_width_success" style="display:none; margin:10px 0; padding:10px 15px; background:#f0f8f0; border-left:4px solid #28a745; border-radius:4px; color:#28a745; font-size:14px;">
            ✅ <strong>Standard Width:</strong> This width (<span id="wccb_success_width" style="font-weight:bold;">18</span>") is standard.
            <span style="color:#555; font-size:13px;">Standard drawer pricing will be applied.</span>
        </div>
        <?php endif; ?>
        
        <!-- Depth Selection -->
        <div class="wccb-field" style="margin:15px 0;">
            <label style="font-weight:600; display:inline-block; width:150px;">Depth:</label>
            <div style="display:inline-block;">
                <?php foreach ($allowed_depths as $depth): ?>
                <button type="button" class="wccb-depth-btn <?php echo $depth === $first_depth ? 'active' : ''; ?>" data-depth="<?php echo esc_attr($depth); ?>" style="padding:6px 15px; border:3px solid <?php echo $depth === $first_depth ? '#007cba' : '#ddd'; ?>; border-radius:5px; background:<?php echo $depth === $first_depth ? '#e7f1ff' : '#fff'; ?>; cursor:pointer; font-weight:600; margin-right:5px;">
                    <?php echo esc_html($depth); ?>"
                </button>
                <?php endforeach; ?>
                <input type="hidden" name="wccb_panel_width" id="wccb_panel_width" value="<?php echo esc_attr($first_depth); ?>">
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
                   min="<?php echo intval($shelves_min); ?>" max="<?php echo intval($shelves_max); ?>" 
                   value="<?php echo intval($shelves_min); ?>" style="width:70px; padding:5px;">
            <span style="font-size:12px; color:#888;">(Min: <?php echo intval($shelves_min); ?>, Max: <?php echo intval($shelves_max); ?>)</span>
        </div>
        <?php else: ?>
        <input type="hidden" name="wccb_shelves" value="0">
        <?php endif; ?>
        
        <!-- ========================================== -->
        <!-- Drawers - ✅ اصلاح‌شده (حذف فیلد تعداد) -->
        <!-- ========================================== -->
        <?php if ($has_drawers && $max_drawers > 0 && !empty($drawer_options)): ?>
        <div class="wccb-field" style="margin:15px 0;">
            <label style="font-weight:600; display:inline-block; width:150px;">Drawers:</label>
            <div id="wccb_drawer_container">
                <div class="wccb-drawer-row" style="display:flex; gap:10px; align-items:center; margin-bottom:8px;">
                    <select name="wccb_drawer_type[]" class="wccb-drawer-type" style="padding:5px; min-width:180px;">
                        <option value="">Select...</option>
                    </select>
                    <!-- ✅ فیلد تعداد حذف شد -->
                    <button type="button" class="wccb-remove-drawer" style="color:#d63638; background:none; border:none; font-size:20px; cursor:pointer;">×</button>
                </div>
            </div>
            <button type="button" id="wccb-add-drawer" class="button" style="margin-top:5px;">+ Add Drawer</button>
            <span style="font-size:12px; color:#888; display:block;">Max: <?php echo intval($max_drawers); ?> drawers</span>
            <input type="hidden" name="wccb_drawers_data" id="wccb_drawers_data" value="">
        </div>
        <?php else: ?>
        <input type="hidden" name="wccb_drawers_data" value="">
        <?php endif; ?>
        
        <!-- Rod -->
        <?php if ($has_rod): ?>
        <div class="wccb-field" style="margin:15px 0;">
            <label style="font-weight:600; display:inline-block; width:150px;">Rod:</label>
            <span style="font-weight:bold;"><?php echo intval($rod_count); ?> rod(s) included</span>
            <input type="hidden" name="wccb_has_rod" value="1">
            <input type="hidden" name="wccb_rod_count" value="<?php echo intval($rod_count); ?>">
        </div>
        <?php else: ?>
        <input type="hidden" name="wccb_has_rod" value="0">
        <?php endif; ?>
        
        <input type="hidden" name="wccb_back_strip" value="1">
        <input type="hidden" name="wccb_height" value="<?php echo intval($height); ?>">
        <input type="hidden" name="wccb_panel_width" id="wccb_panel_width_hidden" value="<?php echo intval($first_depth); ?>">
        
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
    </div>
    
    <?php
    // پایان فرم
    echo '</form>';
    ?>
    
    <script>
    jQuery(document).ready(function($) {
        var maxDrawers = <?php echo intval($max_drawers); ?>;
        var drawerOptions = <?php echo wp_json_encode($drawer_options); ?>;
        var productId = <?php echo intval($product_id); ?>;
        var wccbNonce = '<?php echo wp_create_nonce('wccb_calculate'); ?>';
        var ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
        var isBuildingBuilder = <?php echo $is_builder ? 'true' : 'false'; ?>;
        
        // ==========================================
        // منطق نمایش کشوها بر اساس Depth و Width
        // ==========================================
        function getAvailableDrawers(depth, width) {
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

            var standardWidths = [18, 24, 30];
            if (standardWidths.indexOf(width) !== -1) {
                var drawers = standardDrawers[depth]?.[width] || [];
                if (drawerOptions.length > 0) {
                    drawers = drawers.filter(function(d) {
                        return drawerOptions.indexOf(d) !== -1;
                    });
                }
                return drawers;
            }

            return ['custom_6', 'custom_8', 'custom_12'];
        }
        
        // ==========================================
        // بررسی عرض استاندارد
        // ==========================================
        function isStandardDrawerWidth(width) {
            var standardWidths = [18, 24, 30];
            return standardWidths.indexOf(parseInt(width)) !== -1;
        }
        
        function updateWidthWarning() {
            var width = parseInt($('#wccb_width').val()) || 20;
            var hasDrawers = <?php echo $has_drawers ? 'true' : 'false'; ?>;
            
            if (hasDrawers && drawerOptions.length > 0) {
                if (isStandardDrawerWidth(width)) {
                    $('#wccb_width_warning').hide();
                    $('#wccb_width_success').show();
                    $('#wccb_success_width').text(width);
                } else {
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
            var depth = parseInt($('#wccb_panel_width').val()) || 12;
            var width = parseInt($('#wccb_width').val()) || 20;
            var available = getAvailableDrawers(depth, width);
            var isCustom = available.length === 3 && available[0] === 'custom_6';
            
            $('#wccb_drawer_container .wccb-drawer-type').each(function() {
                var $select = $(this);
                var currentVal = $select.val();
                $select.empty();
                $select.append('<option value="">Select...</option>');
                
                if (isCustom) {
                    $select.append('<option value="custom_6">Custom Drawer 6" Height</option>');
                    $select.append('<option value="custom_8">Custom Drawer 8" Height</option>');
                    $select.append('<option value="custom_12">Custom Drawer 12" Height</option>');
                } else {
                    available.forEach(function(drawer) {
                        $select.append('<option value="' + drawer + '">' + drawer + '</option>');
                    });
                }
                
                if (currentVal) {
                    $select.val(currentVal);
                }
            });
        }
        
        // ==========================================
        // ✅ محاسبه قیمت با AJAX (اصلاح‌شده کامل)
        // ==========================================
        function updatePrice() {
            var width = parseInt($('#wccb_width').val()) || 20;
            var color = $('#wccb_color').val() || 'white';
            var shelves = parseInt($('#wccb_shelves').val()) || 0;
            var panelWidth = parseInt($('#wccb_panel_width').val()) || 12;
            
            // ==========================================
            // ✅ جمع‌آوری داده‌های کشوها (بدون فیلد تعداد)
            // ==========================================
            var drawers = {};
            var hasDrawers = false;
            
            $('#wccb_drawer_container .wccb-drawer-row').each(function() {
                var $row = $(this);
                var type = $row.find('.wccb-drawer-type').val();
                
                // ✅ هر ردیف = ۱ عدد کشو (فیلد تعداد حذف شده)
                if (type && type !== '') {
                    hasDrawers = true;
                    
                    // ✅ کشوهای کاستوم با ارتفاع‌های مختلف
                    if (type === 'custom_6' || type === 'custom_8' || type === 'custom_12') {
                        if (!drawers.custom_heights) {
                            drawers.custom_heights = {};
                        }
                        drawers.custom_heights[type] = (drawers.custom_heights[type] || 0) + 1;
                    } else {
                        // کشوهای استاندارد
                        drawers[type] = (drawers[type] || 0) + 1;
                    }
                }
            });
            
            // ==========================================
            // ✅ ذخیره در فیلد مخفی
            // ==========================================
            if (hasDrawers) {
                $('#wccb_drawers_data').val(JSON.stringify(drawers));
            } else {
                $('#wccb_drawers_data').val('');
            }
            
            // ==========================================
            // ✅ دیباگ در کنسول
            // ==========================================
            console.log('📦 Drawers Data:', $('#wccb_drawers_data').val());
            
            // ==========================================
            // نمایش وضعیت لودینگ
            // ==========================================
            $('#wccb_total_price').text('Loading...');
            
            var requestData = {
                action: 'wccb_calculate_price',
                nonce: wccbNonce,
                product_id: productId,
                width: width,
                color: color,
                shelves: shelves,
                panel_width: panelWidth,
                drawers: JSON.stringify(drawers)
            };
            
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: requestData,
                success: function(response) {
                    console.log('✅ AJAX Response:', response);
                    
                    if (response.success && response.data && typeof response.data.price !== 'undefined') {
                        var price = parseFloat(response.data.price);
                        
                        if (!isNaN(price) && price > 0) {
                            var displayPrice = Math.floor(price * 100) / 100;
                            
                            // ✅ اصلاح‌شده
if (isBuildingBuilder) {
    var regularPrice = price * (3 / 2.8);  // محاسبه قیمت عادی از روی قیمت ویژه
    var builderPrice = price;               // قیمت ویژه همان قیمت دریافتی از سرور است
    $('#wccb_regular_price').text('$' + regularPrice.toFixed(2));
    $('#wccb_total_price').text('$' + builderPrice.toFixed(2));
} else {
    $('#wccb_total_price').text('$' + price.toFixed(2));
}
                        } else {
                            console.error('❌ Invalid price:', price);
                            $('#wccb_total_price').text('Error');
                        }
                    } else {
                        console.error('❌ Response error:', response);
                        $('#wccb_total_price').text('Error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('❌ AJAX Error:', error);
                    $('#wccb_total_price').text('Error');
                }
            });
        }
        
        // ==========================================
        // رویدادها
        // ==========================================
        
        // Slider change
        $('#wccb_width_slider').on('input', function() {
            var val = $(this).val();
            $('#wccb_width_display').text(val);
            $('#wccb_width').val(val);
            updateWidthWarning();
            updateDrawersList();
            updatePrice();
        });
        
        // Depth buttons
        $('.wccb-depth-btn').on('click', function() {
            $('.wccb-depth-btn').removeClass('active').css({
                'border-color': '#ddd',
                'background': '#fff'
            });
            $(this).addClass('active').css({
                'border-color': '#007cba',
                'background': '#e7f1ff'
            });
            $('#wccb_panel_width').val($(this).data('depth'));
            updateDrawersList();
            updatePrice();
        });
        
        // Color buttons
        $('.wccb-color-btn').on('click', function() {
            $('.wccb-color-btn').removeClass('active').css('border-color', '#ddd');
            $(this).addClass('active').css('border-color', '#007cba');
            $('#wccb_color').val($(this).data('color'));
            updatePrice();
        });
        
        // Shelves input
        $('#wccb_shelves').on('change', function() {
            updatePrice();
        });
        
        // ==========================================
        // ✅ Add drawer (اصلاح‌شده)
        // ==========================================
        $('#wccb-add-drawer').on('click', function() {
            var count = $('#wccb_drawer_container .wccb-drawer-row').length;
            if (count < maxDrawers) {
                var $row = $('#wccb_drawer_container .wccb-drawer-row:first').clone();
                $row.find('select').val('');
                // ✅ فیلد تعداد حذف شده
                $('#wccb_drawer_container').append($row);
                updateDrawersList();
                updatePrice();
            } else {
                alert('Maximum ' + maxDrawers + ' drawers allowed.');
            }
        });
        
        // ==========================================
        // ✅ Remove drawer (اصلاح‌شده)
        // ==========================================
        $(document).on('click', '.wccb-remove-drawer', function() {
            if ($('#wccb_drawer_container .wccb-drawer-row').length > 1) {
                $(this).closest('.wccb-drawer-row').remove();
                updateDrawersList();
                updatePrice();
            }
        });
        
        // ==========================================
        // ✅ Change drawer type (اصلاح‌شده)
        // ==========================================
        $(document).on('change', '.wccb-drawer-type', function() {
            updatePrice();
        });
        
        // ==========================================
        // مقداردهی اولیه
        // ==========================================
        updateWidthWarning();
        updateDrawersList();
        updatePrice();
    });
    </script>
    
    <?php
}
