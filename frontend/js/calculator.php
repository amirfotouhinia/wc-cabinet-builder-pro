<?php
header('Content-Type: application/javascript');
?>
(function($) {
    'use strict';

    console.log('WCCB Calculator loaded!');

    var settings = {
        panelPrices: { 12: 16, 16: 20, 24: 30 },
        rodPrices: { '15_20': 3, '21_30': 4, '31_40': 5 },
        backStripPrices: { '15_20': 2, '21_30': 3, '31_40': 4 },
        screwPricePerShelf: 2,
        multipliers: { 'white': 3, 'gray': 4.5, 'oak': 4.5, 'other': 6 },
        builderMultipliers: { 'white': 2.8, 'gray': 4.2, 'oak': 4.2, 'other': 5.6 },
        customDrawerPricePerInch: 5,
        customDrawerWidthPricePerInch: 3,
        drawerPrices: {
            'A':    { 'white': 80, 'gray_oak': 85, 'other': 90 },
            'A2':   { 'white': 70, 'gray_oak': 75, 'other': 80 },
            'B':    { 'white': 80, 'gray_oak': 85, 'other': 90 },
            'B2':   { 'white': 80, 'gray_oak': 85, 'other': 90 },
            'C':    { 'white': 110, 'gray_oak': 115, 'other': 120 },
            'C2':   { 'white': 95, 'gray_oak': 100, 'other': 105 },
            'D':    { 'white': 110, 'gray_oak': 115, 'other': 120 },
            'D2':   { 'white': 95, 'gray_oak': 100, 'other': 105 },
            'E':    { 'white': 140, 'gray_oak': 145, 'other': 150 },
            'E2':   { 'white': 120, 'gray_oak': 125, 'other': 130 },
            'F':    { 'white': 150, 'gray_oak': 155, 'other': 160 },
            'F2':   { 'white': 130, 'gray_oak': 135, 'other': 140 },
            'G':    { 'white': 90, 'gray_oak': 95, 'other': 100 },
            'G2':   { 'white': 85, 'gray_oak': 90, 'other': 95 },
            'H':    { 'white': 120, 'gray_oak': 125, 'other': 130 },
            'H2':   { 'white': 105, 'gray_oak': 110, 'other': 115 },
            'I':    { 'white': 160, 'gray_oak': 165, 'other': 170 },
            'I2':   { 'white': 140, 'gray_oak': 145, 'other': 150 },
            'J12':  { 'white': 55, 'gray_oak': 60, 'other': 65 },
            'J':    { 'white': 60, 'gray_oak': 65, 'other': 70 },
            'J24':  { 'white': 70, 'gray_oak': 75, 'other': 80 },
            'J212': { 'white': 55, 'gray_oak': 60, 'other': 65 },
            'J2':   { 'white': 60, 'gray_oak': 65, 'other': 70 },
            'J224': { 'white': 70, 'gray_oak': 75, 'other': 80 },
            'J30_12': { 'white': 60, 'gray_oak': 65, 'other': 70 },
            'J30':    { 'white': 65, 'gray_oak': 70, 'other': 75 },
            'J30_24': { 'white': 75, 'gray_oak': 80, 'other': 85 }
        }
    };

    function getPanelPrice(panelWidth, height) {
        var price96 = settings.panelPrices[panelWidth] || 16;
        return (price96 / 96) * height;
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
            return 2;
        } else if (cabinetWidth <= 30) {
            return 3;
        } else {
            return 4;
        }
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