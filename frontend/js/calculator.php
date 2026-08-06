<?php
header('Content-Type: application/javascript');
defined('ABSPATH') || exit;
?>

(function ($) {
    'use strict';

    // ✅ دریافت تنظیمات از متغیرهای سراسری
    const settings = window.wccbSettings || {};

    // ✅ مقداردهی پیش‌فرض با بررسی دقیق
    const defaultSettings = {
        panel_prices: {},
        rod_prices: {},
        back_strip_prices: {},
        drawer_prices: {},
        multipliers: {},
        builder_multipliers: {},
        screw_price_per_shelf: 0.5,
        custom_drawer_width_price_per_inch: 0.5,
        custom_drawer_height_price_per_inch: 0.3
    };

    // ✅ ترکیب تنظیمات با پیش‌فرض‌ها
    Object.keys(defaultSettings).forEach(function(key) {
        if (typeof settings[key] === 'undefined') {
            settings[key] = defaultSettings[key];
        }
    });

    // ✅ تابع کمکی برای دریافت تنظیمات با fallback
    function getSetting(group, key, fallback) {
        if (settings[group] && typeof settings[group][key] !== 'undefined' && settings[group][key] !== null) {
            return Number(settings[group][key]);
        }
        return Number(fallback);
    }

    // ✅ تابع دریافت قیمت هر اینچ پنل
    function getPanelPricePerInch(panelWidth) {
        var panelWidthStr = String(panelWidth);
        var panelPrice96 = getSetting('panel_prices', panelWidthStr, 16);
        return panelPrice96 / 96;
    }

    function getPanelPrice(panelWidth, height) {
        return getPanelPricePerInch(panelWidth) * height;
    }

    function getRodPrice(cabinetWidth) {
        if (cabinetWidth <= 20) {
            return getSetting('rod_prices', '15_20', 3);
        }
        if (cabinetWidth <= 30) {
            return getSetting('rod_prices', '21_30', 4);
        }
        return getSetting('rod_prices', '31_40', 5);
    }

    function getBackStripPrice(cabinetWidth) {
        if (cabinetWidth <= 20) {
            return getSetting('back_strip_prices', '15_20', 2);
        }
        if (cabinetWidth <= 30) {
            return getSetting('back_strip_prices', '21_30', 3);
        }
        return getSetting('back_strip_prices', '31_40', 4);
    }

    // ✅ تابع اصلاح‌شده با بررسی دقیق undefined
    function getMultiplier(color) {
        var isBuildingBuilder = (
            typeof window.wccb_vars !== 'undefined' &&
            window.wccb_vars !== null &&
            window.wccb_vars.isBuildingBuilder === 'true'
        );
        
        if (isBuildingBuilder) {
            if (settings.builder_multipliers && typeof settings.builder_multipliers[color] !== 'undefined' && settings.builder_multipliers[color] !== null) {
                return Number(settings.builder_multipliers[color]);
            }
            return 2.8;
        }
        
        if (settings.multipliers && typeof settings.multipliers[color] !== 'undefined' && settings.multipliers[color] !== null) {
            return Number(settings.multipliers[color]);
        }
        return 3;
    }

    function getCustomDrawerMultiplier(cabinetWidth) {
        var multipliers = {
            '15_20': 0.7,
            '21_30': 0.8,
            '31_40': 0.9
        };
        
        if (cabinetWidth <= 20) {
            return multipliers['15_20'] || 0.7;
        } else if (cabinetWidth <= 30) {
            return multipliers['21_30'] || 0.8;
        } else {
            return multipliers['31_40'] || 0.9;
        }
    }

    // ✅ تابع کمکی برای دریافت تعداد با بررسی دقیق
    function getCountValue(value) {
        if (typeof value === 'undefined' || value === null) {
            return 0;
        }
        var num = Number(value);
        return isNaN(num) ? 0 : num;
    }

    function getDrawerPrice(drawerType, color, count, cabinetWidth, drawerHeight) {
        // کشوهای استاندارد
        if (settings.drawer_prices && settings.drawer_prices[drawerType]) {
            var prices = settings.drawer_prices[drawerType];
            var price = prices[color] || prices['white'] || 0;
            return price * count;
        }
        
        // کشوهای سفارشی
        if (drawerType === 'custom' && cabinetWidth) {
            if (typeof drawerHeight === 'undefined' || drawerHeight === null) {
                drawerHeight = 8;
            }
            
            var widthPricePerInch = settings.custom_drawer_width_price_per_inch || 0.5;
            var heightPricePerInch = settings.custom_drawer_height_price_per_inch || 0.3;
            var basePrice = (cabinetWidth * widthPricePerInch) + (drawerHeight * heightPricePerInch);
            var multiplier = getCustomDrawerMultiplier(cabinetWidth);
            var finalPrice = basePrice * multiplier;
            return finalPrice * count;
        }
        
        return 0;
    }

    // ✅ تابع اصلی محاسبه قیمت (اصلاح‌شده)
    window.calculatePrice = function(productConfig, userInput) {
        var height = productConfig.height || 72;
        var panelWidth = userInput.panelWidth || productConfig.panelWidth || 12;
        var cabinetWidth = userInput.width || 20;
        var shelves = userInput.shelves || 0;
        var color = userInput.color || 'white';
        var drawers = userInput.drawers || {};

        var screwPricePerShelf = settings.screw_price_per_shelf || 0.5;

        var panelPricePerInch = getPanelPricePerInch(panelWidth);
        var panelPrice = getPanelPrice(panelWidth, height);
        var verticalPanels = 2 * panelPrice;

        var horizontalPanelPrice = panelPricePerInch * cabinetWidth;
        var horizontalPanels = 2 * horizontalPanelPrice;

        var shelfPrice = panelPricePerInch * cabinetWidth;
        var shelvesTotal = shelves * shelfPrice;

        var screwsForShelves = shelves * screwPricePerShelf;
        var screwsForHorizontal = 2 * screwPricePerShelf;

        var backStripPrice = getBackStripPrice(cabinetWidth);
        var rodTotal = 0;
        if (productConfig.hasRod) {
            rodTotal = getRodPrice(cabinetWidth) * (productConfig.rodCount || 1);
        }

        // ✅ محاسبه کشوها با اعتبارسنجی
        var drawersTotal = 0;
        var customHeights = drawers['custom_heights'] || {};
        
        var drawerKeys = Object.keys(drawers).filter(function(key) {
            return key !== 'custom_heights' && key !== 'custom';
        });
        
        for (var i = 0; i < drawerKeys.length; i++) {
            var drawerType = drawerKeys[i];
            var count = getCountValue(drawers[drawerType]);
            if (count > 0) {
                drawersTotal += getDrawerPrice(drawerType, color, count);
            }
        }
        
        // کشوهای سفارشی با ارتفاع‌های مختلف
        if (customHeights && typeof customHeights === 'object') {
            var heightTypes = ['custom_6', 'custom_8', 'custom_12'];
            var heightMap = {
                'custom_6': 6,
                'custom_8': 8,
                'custom_12': 12
            };
            
            for (var h = 0; h < heightTypes.length; h++) {
                var heightType = heightTypes[h];
                var count = getCountValue(customHeights[heightType]);
                if (count > 0) {
                    var drawerHeight = heightMap[heightType] || 8;
                    drawersTotal += getDrawerPrice('custom', color, count, cabinetWidth, drawerHeight);
                }
            }
        }

        var cabinetCost = verticalPanels + horizontalPanels + shelvesTotal + 
                         screwsForShelves + screwsForHorizontal + 
                         backStripPrice + rodTotal;

        var multiplier = getMultiplier(color);
        var finalPrice = (cabinetCost * multiplier) + drawersTotal;

        var regularPrice = (cabinetCost * 3) + drawersTotal;
        var builderPrice = (cabinetCost * 2.8) + drawersTotal;
        
        // ✅ دیباگ فقط در حالت توسعه
        if (window.wccb_vars && window.wccb_vars.debug === 'true') {
            console.log('🔍 [DEBUG] ===== WCCB Calculator Debug =====');
            console.log('🔍 [DEBUG] panelWidth:', panelWidth);
            console.log('🔍 [DEBUG] panelPricePerInch:', panelPricePerInch);
            console.log('🔍 [DEBUG] cabinetCost:', cabinetCost);
            console.log('🔍 [DEBUG] multiplier:', multiplier);
            console.log('🔍 [DEBUG] drawersTotal:', drawersTotal);
            console.log('🔍 [DEBUG] finalPrice:', finalPrice);
            console.log('🔍 [DEBUG] regularPrice:', regularPrice);
            console.log('🔍 [DEBUG] builderPrice:', builderPrice);
            console.log('🔍 [DEBUG] =====================================');
        }

        return {
            regular: Math.round(regularPrice * 100) / 100,
            builder: Math.round(builderPrice * 100) / 100,
            final: Math.round(finalPrice * 100) / 100
        };
    };

})(jQuery);
