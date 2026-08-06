<?php
header('Content-Type: application/javascript');
?>
(function($) {
    <?php
header('Content-Type: application/javascript');
defined('ABSPATH') || exit;

$settings = get_option('wccb_settings', []);
?>

(function ($) {
    'use strict';

    console.log('WCCB Calculator v3.1.0 Loaded');

    const settings = window.wccbSettings || <?php
        echo wp_json_encode(
            $settings,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );
    ?>;

    settings.panel_prices ??= {};
    settings.rod_prices ??= {};
    settings.back_strip_prices ??= {};
    settings.drawer_prices ??= {};
    settings.multipliers ??= {};
    settings.builder_multipliers ??= {};

    function getSetting(group, key, fallback = 0) {

        if (
            settings[group] &&
            settings[group][key] !== undefined
        ) {
            return Number(settings[group][key]);
        }

        return Number(fallback);

    }
function getRodPrice(cabinetWidth) {

    if (cabinetWidth <= 20) {
        return getSetting(
            'rod_prices',
            '15_20',
            3
        );
    }

    if (cabinetWidth <= 30) {
        return getSetting(
            'rod_prices',
            '21_30',
            4
        );
    }

    return getSetting(
        'rod_prices',
        '31_40',
        5
    );

}

    function getRodPrice(cabinetWidth) {
        if (cabinetWidth <= 20) {
            return 3;
        } else if (cabinetWidth <= 30) {
            return 4;
        } else {
            return 5;
        }
    }

function getBackStripPrice(cabinetWidth) {

    if (cabinetWidth <= 20) {
        return getSetting(
            'back_strip_prices',
            '15_20',
            2
        );
    }

    if (cabinetWidth <= 30) {
        return getSetting(
            'back_strip_prices',
            '21_30',
            3
        );
    }

    return getSetting(
        'back_strip_prices',
        '31_40',
        4
    );

}

    function getMultiplier(color) {
        var isBuildingBuilder = window.wccb_vars?.isBuildingBuilder === 'true';
        
        if (isBuildingBuilder) {
            return settings.builderMultipliers[color] || 2.8;
        }
        
        return settings.multipliers[color] || 3;
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

    function getDrawerPrice(drawerType, color, count, cabinetWidth, drawerHeight) {
        if (settings.drawerPrices[drawerType]) {
            var prices = settings.drawerPrices[drawerType];
            var price = prices[color] || prices['white'] || 0;
            return price * count;
        }
        
        if (drawerType === 'custom' && cabinetWidth) {
            var drawerHeight = drawerHeight || 8;
            var basePrice = (cabinetWidth * settings.customDrawerWidthPricePerInch) + (drawerHeight * settings.customDrawerPricePerInch);
            var multiplier = getCustomDrawerMultiplier(cabinetWidth);
            var finalPrice = basePrice * multiplier;
            return finalPrice * count;
        }
        
        return 0;
    }

    window.calculatePrice = function(productConfig, userInput) {
        var height = productConfig.height || 72;
        var panelWidth = userInput.panelWidth || productConfig.panelWidth || 12;
        var cabinetWidth = userInput.width || 20;
        var shelves = userInput.shelves || 0;
        var color = userInput.color || 'white';
        var drawers = userInput.drawers || {};

        var pricePerInch = settings.panelPrices[panelWidth] / 96;

        var panelPrice = getPanelPrice(panelWidth, height);
        var verticalPanels = 2 * panelPrice;

        var horizontalPanelPrice = pricePerInch * cabinetWidth;
        var horizontalPanels = 2 * horizontalPanelPrice;

        var shelfPrice = pricePerInch * cabinetWidth;
        var shelvesTotal = shelves * shelfPrice;

        var screwsForShelves = shelves * settings.screwPricePerShelf;
        var screwsForHorizontal = 2 * settings.screwPricePerShelf;

        var backStripPrice = getBackStripPrice(cabinetWidth);
        var rodTotal = 0;
        if (productConfig.hasRod) {
            rodTotal = getRodPrice(cabinetWidth) * (productConfig.rodCount || 1);
        }

        var drawersTotal = 0;
        var customHeights = drawers['custom_heights'] || {};
        
        var drawerKeys = Object.keys(drawers).filter(function(key) {
            return key !== 'custom_heights' && key !== 'custom';
        });
        
        for (var i = 0; i < drawerKeys.length; i++) {
            var drawerType = drawerKeys[i];
            var count = drawers[drawerType] || 0;
            if (count > 0) {
                drawersTotal += getDrawerPrice(drawerType, color, count);
            }
        }
        
        if (customHeights && typeof customHeights === 'object') {
            var heightTypes = ['custom_6', 'custom_8', 'custom_12'];
            var heightMap = {
                'custom_6': 6,
                'custom_8': 8,
                'custom_12': 12
            };
            
            for (var h = 0; h < heightTypes.length; h++) {
                var heightType = heightTypes[h];
                var count = customHeights[heightType] || 0;
                if (count > 0) {
                    var drawerHeight = heightMap[heightType] || 8;
                    drawersTotal += getDrawerPrice('custom', color, count, cabinetWidth, drawerHeight);
                }
            }
        }

        var cabinetCost = verticalPanels + horizontalPanels + shelvesTotal + screwsForShelves + screwsForHorizontal + backStripPrice + rodTotal;

        var multiplier = getMultiplier(color);
        var finalPrice = (cabinetCost * multiplier) + drawersTotal;

        var regularPrice = (cabinetCost * 3) + drawersTotal;
        var builderPrice = (cabinetCost * 2.8) + drawersTotal;
        
        console.log('🔍 [DEBUG] cabinetCost:', cabinetCost);
        console.log('🔍 [DEBUG] multiplier:', multiplier);
        console.log('🔍 [DEBUG] finalPrice:', finalPrice);
        console.log('🔍 [DEBUG] regularPrice:', regularPrice);
        console.log('🔍 [DEBUG] builderPrice:', builderPrice);
        console.log('🔍 [DEBUG] drawersTotal:', drawersTotal);
        
        var isBuildingBuilder = window.wccb_vars?.isBuildingBuilder === 'true';

        return {
            regular: Math.round(regularPrice * 100) / 100,
            builder: Math.round(builderPrice * 100) / 100,
            final: Math.round(finalPrice * 100) / 100
        };
    };

})(jQuery);
