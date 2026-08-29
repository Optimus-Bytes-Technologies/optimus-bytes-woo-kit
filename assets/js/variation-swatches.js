(function ($) {
    'use strict';

    $(document).ready(function () {
        const $forms = $('form.variations_form');
        if (!$forms.length) {
            return;
        }

        $forms.each(function () {
            const $form = $(this);
            initSwatches($form);
        });

        function initSwatches($form) {
            // Get product variations data
            let variationsData = $form.data('product_variations');
            if (!variationsData) {
                const rawData = $form.attr('data-product_variations');
                if (rawData) {
                    try {
                        variationsData = JSON.parse(rawData);
                    } catch (e) {
                        variationsData = false;
                    }
                }
            }

            // 1. Swatch Click Handler
            $form.on('click', '.obwk-swatch:not(.is-disabled)', function (e) {
                e.preventDefault();
                const $swatch = $(this);
                const $container = $swatch.closest('.obwk-swatches-container');
                const attributeName = $container.data('attribute_name');
                const $select = $container.siblings('.obwk-hidden-select-wrap').find('select[name="' + attributeName + '"]');
                const value = $swatch.data('value');

                if ($swatch.hasClass('is-selected')) {
                    // Deselect on second click
                    $swatch.removeClass('is-selected');
                    $select.val('').trigger('change');
                } else {
                    // Select swatch
                    $container.find('.obwk-swatch').removeClass('is-selected');
                    $swatch.addClass('is-selected');
                    $select.val(value).trigger('change');
                }

                // Recalculate stock and availability
                setTimeout(function () {
                    computeStockMatrix($form, variationsData);
                }, 50);
            });

            // 2. Sync with WooCommerce events
            $form.on('woocommerce_variation_has_changed', function () {
                syncSelectedStates($form);
                computeStockMatrix($form, variationsData);
            });

            $form.on('reset_data', function () {
                $form.find('.obwk-swatch').removeClass('is-selected');
                computeStockMatrix($form, variationsData);
            });

            $form.on('update_variation_values check_variations', function () {
                syncSelectedStates($form);
                computeStockMatrix($form, variationsData);
            });

            // Initial calculation
            setTimeout(function () {
                syncSelectedStates($form);
                computeStockMatrix($form, variationsData);
            }, 100);
        }

        /**
         * Synchronize .is-selected class with underlying hidden select inputs
         */
        function syncSelectedStates($form) {
            $form.find('.obwk-swatches-container').each(function () {
                const $container = $(this);
                const attributeName = $container.data('attribute_name');
                const $select = $container.siblings('.obwk-hidden-select-wrap').find('select[name="' + attributeName + '"]');
                const selectedVal = $select.val();

                $container.find('.obwk-swatch').removeClass('is-selected');
                if (selectedVal) {
                    $container.find('.obwk-swatch[data-value="' + selectedVal + '"]').addClass('is-selected');
                }
            });
        }

        /**
         * Intelligent Stock Matrix Engine:
         * Calculates in-stock, out-of-stock, and unavailable combinations in real-time
         */
        function computeStockMatrix($form, variationsData) {
            // Collect currently selected attributes
            const currentSelected = {};
            $form.find('.obwk-swatches-container').each(function () {
                const $container = $(this);
                const attributeName = $container.data('attribute_name');
                const $select = $container.siblings('.obwk-hidden-select-wrap').find('select[name="' + attributeName + '"]');
                const val = $select.val();
                if (val) {
                    currentSelected[attributeName] = val;
                }
            });

            // If variations data is not available, fallback to WooCommerce select options disabled state
            if (!variationsData || !variationsData.length) {
                $form.find('.obwk-swatches-container').each(function () {
                    const $container = $(this);
                    const attributeName = $container.data('attribute_name');
                    const $select = $container.siblings('.obwk-hidden-select-wrap').find('select[name="' + attributeName + '"]');

                    $container.find('.obwk-swatch').each(function () {
                        const $swatch = $(this);
                        const val = $swatch.data('value');
                        const $opt = $select.find('option[value="' + val + '"]');

                        if (!$opt.length || $opt.prop('disabled')) {
                            markSwatchOutOfStock($swatch, true);
                        } else {
                            markSwatchOutOfStock($swatch, false);
                        }
                    });
                });
                return;
            }

            // Loop through each attribute container
            $form.find('.obwk-swatches-container').each(function () {
                const $container = $(this);
                const attributeName = $container.data('attribute_name');

                $container.find('.obwk-swatch').each(function () {
                    const $swatch = $(this);
                    const swatchVal = String($swatch.data('value'));

                    // Construct test selection: current selections + this hypothetical swatch
                    const testSelection = Object.assign({}, currentSelected);
                    testSelection[attributeName] = swatchVal;

                    // Filter matching variations
                    const matchingVariations = variationsData.filter(function (variation) {
                        if (!variation.attributes) {
                            return true;
                        }

                        // Check if variation matches all test attributes
                        for (const attrKey in testSelection) {
                            const selectedAttrVal = testSelection[attrKey];
                            const varAttrVal = variation.attributes[attrKey];

                            // In WooCommerce, an empty string attribute means "Any [Attribute]"
                            if (varAttrVal !== '' && varAttrVal !== undefined && varAttrVal !== selectedAttrVal) {
                                return false;
                            }
                        }
                        return true;
                    });

                    if (matchingVariations.length === 0) {
                        // No variation exists with this combination
                        markSwatchOutOfStock($swatch, true, 'unavailable');
                    } else {
                        // Check if ANY matching variation is in stock and purchasable
                        const hasInStock = matchingVariations.some(function (v) {
                            return v.is_in_stock && v.is_purchasable;
                        });

                        if (hasInStock) {
                            markSwatchOutOfStock($swatch, false);
                        } else {
                            // Variation exists but is out of stock
                            markSwatchOutOfStock($swatch, true, 'out_of_stock');
                        }
                    }
                });
            });
        }

        /**
         * Helper to apply or remove out-of-stock / disabled classes and tooltips
         */
        function markSwatchOutOfStock($swatch, isOutOfStock, reason) {
            const originalTooltip = $swatch.data('orig-tooltip') || $swatch.attr('data-tooltip') || $swatch.attr('aria-label') || '';
            if (!$swatch.data('orig-tooltip') && originalTooltip) {
                $swatch.data('orig-tooltip', originalTooltip);
            }

            if (isOutOfStock) {
                $swatch.addClass('is-disabled');
                if (reason === 'out_of_stock') {
                    $swatch.addClass('is-out-of-stock').removeClass('is-unavailable');
                    if (originalTooltip) {
                        $swatch.attr('data-tooltip', originalTooltip + ' (Out of stock)');
                    }
                } else {
                    $swatch.addClass('is-unavailable').removeClass('is-out-of-stock');
                    if (originalTooltip) {
                        $swatch.attr('data-tooltip', originalTooltip + ' (Unavailable)');
                    }
                }
            } else {
                $swatch.removeClass('is-disabled is-out-of-stock is-unavailable');
                if ($swatch.data('orig-tooltip')) {
                    $swatch.attr('data-tooltip', $swatch.data('orig-tooltip'));
                }
            }
        }
    });
})(jQuery);
