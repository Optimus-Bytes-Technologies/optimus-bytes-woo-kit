/**
 * Optimus Bytes Woo Kit - Stock Scarcity & Urgency Bar JS
 * Real-time variation swatch syncing with 3-tier stock levels:
 * - Green: In Stock
 * - Orange: Medium Stock
 * - Red: Low Stock Urgency
 *
 * @package OptimusBytes\WooKit
 */

(function ($) {
    'use strict';

    var ObwkStockScarcity = {
        init: function () {
            this.$wrap = $('#obwk-stock-scarcity-wrap');
            if (!this.$wrap.length) {
                return;
            }

            this.initialHtml = this.$wrap.html();
            this.initialDisplay = this.$wrap.css('display');
            this.initialClasses = this.$wrap.attr('class');
            this.bindEvents();
        },

        bindEvents: function () {
            var self = this;
            var $form = $('form.variations_form');

            if ($form.length) {
                // When a variation is selected (swatch or dropdown)
                $form.on('found_variation', function (event, variation) {
                    self.handleVariationChange(variation);
                });

                // When variation selection is reset/cleared
                $form.on('reset_data', function () {
                    self.resetToInitial();
                });
            }
        },

        handleVariationChange: function (variation) {
            if (!variation) {
                return;
            }

            var threshold          = parseInt(obwkStockScarcity.threshold, 10) || 5;
            var mediumThreshold    = parseInt(obwkStockScarcity.medium_threshold, 10) || 15;
            var initialQty         = parseInt(obwkStockScarcity.initial_qty, 10) || 20;
            var displayLevels      = obwkStockScarcity.display_levels || 'all_levels';
            var showUnmanagedStock = !!obwkStockScarcity.show_unmanaged_stock;
            var stockQty           = null;
            var manageStock        = false;

            // Check our custom injected variation stock data first
            if (typeof variation.obwk_manage_stock !== 'undefined' && variation.obwk_manage_stock) {
                manageStock = true;
                stockQty    = parseInt(variation.obwk_stock_qty, 10);
            }
            // Fallback to WooCommerce native variation max_qty if stock managed
            else if (typeof variation.max_qty !== 'undefined' && variation.max_qty !== '') {
                var maxQty = parseInt(variation.max_qty, 10);
                if (!isNaN(maxQty) && maxQty > 0) {
                    manageStock = true;
                    stockQty    = maxQty;
                }
            }

            // If variation is out of stock, hide bar
            if (!variation.is_in_stock) {
                this.$wrap.slideUp(180);
                return;
            }

            // If stock is not maintained and unmanaged stock display is disabled
            if (!manageStock || stockQty === null) {
                if (!showUnmanagedStock) {
                    this.$wrap.slideUp(180);
                    return;
                }
            }

            // Determine tier level: 'low', 'medium', 'high'
            var level = 'high';
            if (manageStock && stockQty !== null) {
                if (stockQty <= threshold) {
                    level = 'low';
                } else if (stockQty <= mediumThreshold) {
                    level = 'medium';
                } else {
                    level = 'high';
                }
            } else {
                level = 'high';
            }

            // If merchant chose low stock urgency only, hide for medium or high
            if (displayLevels === 'low_only' && level !== 'low') {
                this.$wrap.slideUp(180);
                return;
            }

            // Update UI
            this.updateBarUI(stockQty, initialQty, level);
            this.$wrap.slideDown(200);
        },

        updateBarUI: function (stock, initialQty, level) {
            var baseMax = Math.max(initialQty, stock || 1);
            var percent = 100;
            if (stock !== null) {
                percent = Math.min(100, Math.max(8, Math.round((stock / baseMax) * 100)));
            }

            var stockDisplay = (stock !== null) ? '<span class="obwk-stock-number">' + stock + '</span>' : '';
            var msg = '';

            if (level === 'high') {
                if (stock !== null) {
                    msg = obwkStockScarcity.in_stock_msg.replace('{stock}', stockDisplay);
                } else {
                    var cleanInStock = obwkStockScarcity.in_stock_msg.replace(/\s*\(\{stock\}[^)]*\)/g, '').replace('{stock}', '').replace('()', '').trim();
                    msg = cleanInStock.length > 0 ? cleanInStock : '✅ In Stock';
                }
            } else if (level === 'medium') {
                msg = obwkStockScarcity.medium_msg.replace('{stock}', stockDisplay);
            } else {
                // Low stock
                if (stock === 1) {
                    msg = obwkStockScarcity.single_item_msg.replace('{stock}', stockDisplay || '<span class="obwk-stock-number">1</span>');
                } else {
                    msg = obwkStockScarcity.msg_template.replace('{stock}', stockDisplay);
                }
            }

            // Update text and progress fill width
            this.$wrap.find('.obwk-stock-message').html(msg);
            this.$wrap.find('.obwk-stock-progress-fill').css('width', percent + '%');

            // Reset level and critical classes
            this.$wrap.removeClass('is-high is-medium is-low is-critical');
            this.$wrap.find('.obwk-stock-pulse-dot').removeClass('is-high is-medium is-low is-critical');
            this.$wrap.find('.obwk-stock-progress-fill').removeClass('is-high is-medium is-low is-critical');

            // Apply active level class
            this.$wrap.addClass('is-' + level);
            this.$wrap.find('.obwk-stock-pulse-dot').addClass('is-' + level);
            this.$wrap.find('.obwk-stock-progress-fill').addClass('is-' + level);

            // Critical urgency check (stock <= 2)
            if (level === 'low' && stock !== null && stock <= 2) {
                this.$wrap.addClass('is-critical');
                this.$wrap.find('.obwk-stock-pulse-dot').addClass('is-critical');
                this.$wrap.find('.obwk-stock-progress-fill').addClass('is-critical');
            }
        },

        resetToInitial: function () {
            if (this.initialDisplay === 'none') {
                this.$wrap.slideUp(180);
            } else {
                this.$wrap.attr('class', this.initialClasses);
                this.$wrap.html(this.initialHtml).slideDown(200);
            }
        }
    };

    $(document).ready(function () {
        ObwkStockScarcity.init();
    });

})(jQuery);
