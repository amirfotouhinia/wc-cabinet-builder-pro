<?php
add_action('admin_menu', 'wccb_add_admin_menu');

function wccb_add_admin_menu() {
    add_options_page(
        'Cabinet Builder Settings',
        'Cabinet Builder',
        'manage_options',
        'wc-cabinet-settings',
        'wccb_settings_page'
    );
}

function wccb_settings_page() {
    // ✅ بررسی Nonce و اعتبارسنجی
    if (isset($_POST['wccb_save'])) {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wccb_settings')) {
            wp_die('Security check failed');
        }
        
        $settings = get_option('wccb_settings', wccb_get_default_settings());
        
        // 1. قیمت پنل‌ها
        $panel_widths = [12, 16, 24];
        foreach ($panel_widths as $width) {
            $settings['panel_prices'][$width] = isset($_POST["panel_{$width}"]) ? floatval($_POST["panel_{$width}"]) : 0;
        }
        
        // 2. قیمت Rod
        $settings['rod_prices']['15_20'] = isset($_POST['rod_15_20']) ? floatval($_POST['rod_15_20']) : 3;
        $settings['rod_prices']['21_30'] = isset($_POST['rod_21_30']) ? floatval($_POST['rod_21_30']) : 4;
        $settings['rod_prices']['31_40'] = isset($_POST['rod_31_40']) ? floatval($_POST['rod_31_40']) : 5;
        
        // 3. قیمت Back Strip
        $settings['back_strip_prices']['15_20'] = isset($_POST['back_strip_15_20']) ? floatval($_POST['back_strip_15_20']) : 2;
        $settings['back_strip_prices']['21_30'] = isset($_POST['back_strip_21_30']) ? floatval($_POST['back_strip_21_30']) : 3;
        $settings['back_strip_prices']['31_40'] = isset($_POST['back_strip_31_40']) ? floatval($_POST['back_strip_31_40']) : 4;
        
        // 4. پیچ‌ها
        $settings['screw_price_per_shelf'] = isset($_POST['screw_price']) ? floatval($_POST['screw_price']) : 2;
        
        // 5. ضرایب فروش
        $colors = ['white', 'gray', 'oak', 'other'];
        foreach ($colors as $color) {
            $settings['multipliers'][$color] = isset($_POST["multiplier_{$color}"]) ? floatval($_POST["multiplier_{$color}"]) : 3;
        }
        
        // 5.5. ضرایب Building Builder
        foreach ($colors as $color) {
            $settings['builder_multipliers'][$color] = isset($_POST["builder_{$color}"]) ? floatval($_POST["builder_{$color}"]) : 0;
        }
        
        // 6. قیمت کشوهای استاندارد
        $standard_drawers = [
            'A', 'A2', 'B', 'B2', 'C', 'C2', 'D', 'D2', 'E', 'E2', 'F', 'F2',
            'G', 'G2', 'H', 'H2', 'I', 'I2',
            'J', 'J2', 'J24', 'J224', 'J12', 'J212',
            'J30_12', 'J30', 'J30_24'
        ];
        
        foreach ($standard_drawers as $drawer) {
            $settings['drawer_prices'][$drawer] = [
                'white' => isset($_POST["drawer_{$drawer}_white"]) ? floatval($_POST["drawer_{$drawer}_white"]) : 0,
                'gray_oak' => isset($_POST["drawer_{$drawer}_gray_oak"]) ? floatval($_POST["drawer_{$drawer}_gray_oak"]) : 0,
                'other' => isset($_POST["drawer_{$drawer}_other"]) ? floatval($_POST["drawer_{$drawer}_other"]) : 0,
            ];
        }
        
        // 7. ضرایب کشوهای کاستوم
        $settings['custom_drawer_multipliers']['15_20'] = isset($_POST['custom_drawer_15_20']) ? floatval($_POST['custom_drawer_15_20']) : 1;
        $settings['custom_drawer_multipliers']['21_30'] = isset($_POST['custom_drawer_21_30']) ? floatval($_POST['custom_drawer_21_30']) : 1.5;
        $settings['custom_drawer_multipliers']['31_40'] = isset($_POST['custom_drawer_31_40']) ? floatval($_POST['custom_drawer_31_40']) : 2;
        
        update_option('wccb_settings', $settings);
        echo '<div class="notice notice-success"><p>Settings saved successfully.</p></div>';
    }
    
    $settings = get_option('wccb_settings', wccb_get_default_settings());
    ?>
    <div class="wrap">
        <h1>Cabinet Builder Settings</h1>
        <form method="post">
            <?php wp_nonce_field('wccb_settings'); ?>
            
            <h2>1. Panel Prices (per 96" height)</h2>
            <table class="form-table">
                <tr>
                    <th>Panel Width 12"</th>
                    <td><input type="number" step="0.01" name="panel_12" value="<?php echo esc_attr($settings['panel_prices'][12] ?? 16); ?>"> USD</td>
                </tr>
                <tr>
                    <th>Panel Width 16"</th>
                    <td><input type="number" step="0.01" name="panel_16" value="<?php echo esc_attr($settings['panel_prices'][16] ?? 20); ?>"> USD</td>
                </tr>
                <tr>
                    <th>Panel Width 24"</th>
                    <td><input type="number" step="0.01" name="panel_24" value="<?php echo esc_attr($settings['panel_prices'][24] ?? 30); ?>"> USD</td>
                </tr>
            </table>
            
            <hr>
            
            <h2>2. Rod Prices (based on cabinet width)</h2>
            <table class="form-table">
                <tr>
                    <th>15-20 inches</th>
                    <td><input type="number" step="0.01" name="rod_15_20" value="<?php echo esc_attr($settings['rod_prices']['15_20'] ?? 3); ?>"> USD</td>
                </tr>
                <tr>
                    <th>21-30 inches</th>
                    <td><input type="number" step="0.01" name="rod_21_30" value="<?php echo esc_attr($settings['rod_prices']['21_30'] ?? 4); ?>"> USD</td>
                </tr>
                <tr>
                    <th>31-40 inches</th>
                    <td><input type="number" step="0.01" name="rod_31_40" value="<?php echo esc_attr($settings['rod_prices']['31_40'] ?? 5); ?>"> USD</td>
                </tr>
            </table>
            
            <hr>
            
            <h2>3. Back Strip Wood Prices</h2>
            <table class="form-table">
                <tr>
                    <th>15-20 inches</th>
                    <td><input type="number" step="0.01" name="back_strip_15_20" value="<?php echo esc_attr($settings['back_strip_prices']['15_20'] ?? 2); ?>"> USD</td>
                </tr>
                <tr>
                    <th>21-30 inches</th>
                    <td><input type="number" step="0.01" name="back_strip_21_30" value="<?php echo esc_attr($settings['back_strip_prices']['21_30'] ?? 3); ?>"> USD</td>
                </tr>
                <tr>
                    <th>31-40 inches</th>
                    <td><input type="number" step="0.01" name="back_strip_31_40" value="<?php echo esc_attr($settings['back_strip_prices']['31_40'] ?? 4); ?>"> USD</td>
                </tr>
            </table>
            
            <hr>
            
            <h2>4. Screws (Cam & Dowel) per Shelf</h2>
            <table class="form-table">
                <tr>
                    <th>Price per shelf</th>
                    <td><input type="number" step="0.01" name="screw_price" value="<?php echo esc_attr($settings['screw_price_per_shelf'] ?? 2); ?>"> USD</td>
                </tr>
            </table>
            
            <hr>
            
            <h2>5. Sales Multipliers (by color)</h2>
            <p>Applied to total cost. Higher multiplier = higher profit.</p>
            <table class="form-table">
                <tr>
                    <th>White</th>
                    <td><input type="number" step="0.01" name="multiplier_white" value="<?php echo esc_attr($settings['multipliers']['white'] ?? 3); ?>"> ×</td>
                </tr>
                <tr>
                    <th>Gray</th>
                    <td><input type="number" step="0.01" name="multiplier_gray" value="<?php echo esc_attr($settings['multipliers']['gray'] ?? 4.5); ?>"> ×</td>
                </tr>
                <tr>
                    <th>Oak</th>
                    <td><input type="number" step="0.01" name="multiplier_oak" value="<?php echo esc_attr($settings['multipliers']['oak'] ?? 4.5); ?>"> ×</td>
                </tr>
                <tr>
                    <th>Other Colors</th>
                    <td><input type="number" step="0.01" name="multiplier_other" value="<?php echo esc_attr($settings['multipliers']['other'] ?? 6); ?>"> ×</td>
                </tr>
            </table>
            
            <hr>

            <h2>5.5 Building Builder Multipliers (B2B / Wholesale)</h2>
            <p>Applied to users with role: <strong>Building builder</strong></p>
            <p class="description">These multipliers override regular multipliers for building builder users.</p>
            
            <table class="form-table">
                <tr>
                    <th>White</th>
                    <td><input type="number" step="0.01" name="builder_white" value="<?php echo esc_attr($settings['builder_multipliers']['white'] ?? 2.8); ?>" style="width:80px;"> ×</td>
                </tr>
                <tr>
                    <th>Gray</th>
                    <td><input type="number" step="0.01" name="builder_gray" value="<?php echo esc_attr($settings['builder_multipliers']['gray'] ?? 4.2); ?>" style="width:80px;"> ×</td>
                </tr>
                <tr>
                    <th>Oak</th>
                    <td><input type="number" step="0.01" name="builder_oak" value="<?php echo esc_attr($settings['builder_multipliers']['oak'] ?? 4.2); ?>" style="width:80px;"> ×</td>
                </tr>
                <tr>
                    <th>Other Colors</th>
                    <td><input type="number" step="0.01" name="builder_other" value="<?php echo esc_attr($settings['builder_multipliers']['other'] ?? 5.6); ?>" style="width:80px;"> ×</td>
                </tr>
            </table>
            <p class="description">💡 Building builder users get special discounted prices.</p>
            
            <hr>
            
            <h2>6. Standard Drawer Prices</h2>
            <p>Prices for standard drawers (no multiplier applied).</p>
            <?php
            $standard_drawers = [
                'A' => ['18"', '8"', '16"'],
                'A2' => ['18"', '8"', '12"'],
                'B' => ['24"', '8"', '16"'],
                'B2' => ['24"', '8"', '12"'],
                'C' => ['18"', '12"', '16"'],
                'C2' => ['18"', '12"', '12"'],
                'D' => ['24"', '12"', '16"'],
                'D2' => ['24"', '12"', '12"'],
                'E' => ['18"', '12"', '24"'],
                'E2' => ['18"', '8"', '24"'],
                'F' => ['24"', '12"', '24"'],
                'F2' => ['24"', '8"', '24"'],
                'G' => ['30"', '8"', '16"'],
                'G2' => ['30"', '8"', '12"'],
                'H' => ['30"', '12"', '16"'],
                'H2' => ['30"', '12"', '12"'],
                'I' => ['30"', '12"', '24"'],
                'I2' => ['30"', '8"', '24"'],
                'J12' => ['18"', '6"', '12"'],
                'J' => ['18"', '6"', '16"'],
                'J24' => ['18"', '6"', '24"'],
                'J212' => ['24"', '6"', '12"'],
                'J2' => ['24"', '6"', '16"'],
                'J224' => ['24"', '6"', '24"'],
                'J30_12' => ['30"', '6"', '12"'],
                'J30' => ['30"', '6"', '16"'],
                'J30_24' => ['30"', '6"', '24"'],
            ];
            ?>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Width</th>
                        <th>Height</th>
                        <th>Depth</th>
                        <th>White</th>
                        <th>Gray/Oak</th>
                        <th>Other</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($standard_drawers as $drawer => $specs): ?>
                    <tr>
                        <td><strong><?php echo $drawer; ?></strong></td>
                        <td><?php echo $specs[0]; ?></td>
                        <td><?php echo $specs[1]; ?></td>
                        <td><?php echo $specs[2]; ?></td>
                        <td><input type="number" step="0.01" name="drawer_<?php echo $drawer; ?>_white" value="<?php echo esc_attr($settings['drawer_prices'][$drawer]['white'] ?? 0); ?>" style="width:60px;"></td>
                        <td><input type="number" step="0.01" name="drawer_<?php echo $drawer; ?>_gray_oak" value="<?php echo esc_attr($settings['drawer_prices'][$drawer]['gray_oak'] ?? 0); ?>" style="width:60px;"></td>
                        <td><input type="number" step="0.01" name="drawer_<?php echo $drawer; ?>_other" value="<?php echo esc_attr($settings['drawer_prices'][$drawer]['other'] ?? 0); ?>" style="width:60px;"></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <hr>

            <h2>7. Custom Drawer Multipliers</h2>
            <p>These multipliers are applied to custom drawers (non-standard widths: 15-17, 19-23, 25-29, 31-40).</p>
            <p class="description">Only applies when width is NOT 18", 24", or 30".</p>
            
            <table class="form-table">
                <tr>
                    <th>Width Range 15" - 20"</th>
                    <td>
                        <input type="number" step="0.01" name="custom_drawer_15_20" 
                               value="<?php echo esc_attr($settings['custom_drawer_multipliers']['15_20'] ?? 1); ?>" style="width:80px;"> ×
                        <span class="description">(Base price × multiplier)</span>
                    </td>
                </tr>
                <tr>
                    <th>Width Range 21" - 30"</th>
                    <td>
                        <input type="number" step="0.01" name="custom_drawer_21_30" 
                               value="<?php echo esc_attr($settings['custom_drawer_multipliers']['21_30'] ?? 1.5); ?>" style="width:80px;"> ×
                        <span class="description">(Base price × multiplier)</span>
                    </td>
                </tr>
                <tr>
                    <th>Width Range 31" - 40"</th>
                    <td>
                        <input type="number" step="0.01" name="custom_drawer_31_40" 
                               value="<?php echo esc_attr($settings['custom_drawer_multipliers']['31_40'] ?? 2); ?>" style="width:80px;"> ×
                        <span class="description">(Base price × multiplier)</span>
                    </td>
                </tr>
            </table>
            <p class="description">💡 Example: If custom drawer base price is $100 and multiplier is 1.5, final price = $150</p>
            
            <hr>
            
            <p><input type="submit" name="wccb_save" class="button-primary" value="Save Settings"></p>
        </form>
    </div>
    <?php
}
