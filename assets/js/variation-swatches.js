(function ($) {
    'use strict';

    // Initialize swatches on any given form
    function initFormSwatches($form) {
        if (!$form.length || $form.data('obwk_swatches_inited')) {
            return;
        }
        $form.data('obwk_swatches_inited', true);

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

        // 1. Sync on events
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
     * Compute in-stock and out-of-stock variations
     */
    function computeStockMatrix($form, variationsData) {
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

        $form.find('.obwk-swatches-container').each(function () {
            const $container = $(this);
            const attributeName = $container.data('attribute_name');

            $container.find('.obwk-swatch').each(function () {
                const $swatch = $(this);
                const swatchVal = String($swatch.data('value'));

                const testSelection = Object.assign({}, currentSelected);
                testSelection[attributeName] = swatchVal;

                const matchingVariations = variationsData.filter(function (variation) {
                    if (!variation.attributes) {
                        return true;
                    }
                    for (const attrKey in testSelection) {
                        const selectedAttrVal = testSelection[attrKey];
                        const varAttrVal = variation.attributes[attrKey];
                        if (varAttrVal !== '' && varAttrVal !== undefined && varAttrVal !== selectedAttrVal) {
                            return false;
                        }
                    }
                    return true;
                });

                if (matchingVariations.length === 0) {
                    markSwatchOutOfStock($swatch, true, 'unavailable');
                } else {
                    const hasInStock = matchingVariations.some(function (v) {
                        return v.is_in_stock && v.is_purchasable;
                    });
                    if (hasInStock) {
                        markSwatchOutOfStock($swatch, false);
                    } else {
                        markSwatchOutOfStock($swatch, true, 'out_of_stock');
                    }
                }
            });
        });
    }

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

    // Delegated Swatch Click Handler (Works for Single Product, Page Builders & Quick View Modals)
    $(document).on('click', '.obwk-swatch:not(.is-disabled)', function (e) {
        e.preventDefault();
        const $swatch = $(this);
        const $form = $swatch.closest('form.variations_form');
        const $container = $swatch.closest('.obwk-swatches-container');
        const attributeName = $container.data('attribute_name');
        const $select = $container.siblings('.obwk-hidden-select-wrap').find('select[name="' + attributeName + '"]');
        const value = $swatch.data('value');

        if ($swatch.hasClass('is-selected')) {
            $swatch.removeClass('is-selected');
            $select.val('').trigger('change');
        } else {
            $container.find('.obwk-swatch').removeClass('is-selected');
            $swatch.addClass('is-selected');
            $select.val(value).trigger('change');
        }

        setTimeout(function () {
            let varData = $form.data('product_variations');
            computeStockMatrix($form, varData);
        }, 50);
    });

    // =========================================================================
    // Product Loop Swatches (Hover & Click Image Switcher on Shop Grid)
    // =========================================================================
    $(document).on('mouseenter click', '.obwk-loop-swatch', function (e) {
        const $swatch = $(this);
        const $wrapper = $swatch.closest('.obwk-loop-swatches');
        const newImgSrc = $swatch.data('image-src') || $swatch.attr('data-image-src');
        
        if (!newImgSrc) {
            return;
        }

        $wrapper.find('.obwk-loop-swatch').removeClass('is-active');
        $swatch.addClass('is-active');

        // Find product card image
        const $card = $swatch.closest('.obwk-product-card, .product, .thunk-product, .product-inner, .grid-item, li.product, article.product');
        const $img = $card.find('.obwk-primary-img, img.wp-post-image, img.attachment-woocommerce_thumbnail, .thunk-product-image img, .woocommerce-LoopProduct-link img, .obwk-product-img-box img').first();

        if ($img.length && $img.attr('src') !== newImgSrc) {
            if (!$img.data('obwk_orig_src')) {
                $img.data('obwk_orig_src', $img.attr('src'));
                $img.data('obwk_orig_srcset', $img.attr('srcset') || '');
            }
            $img.css('transition', 'opacity 0.2s ease').css('opacity', 0.5);
            setTimeout(function () {
                $img.attr('src', newImgSrc).removeAttr('srcset').css('opacity', 1);
            }, 100);
        }
    });

    // Reset loop image on mouse leave if desired
    $(document).on('mouseleave', '.obwk-loop-swatches', function () {
        const $wrapper = $(this);
        const defaultImg = $wrapper.data('default-image') || $wrapper.attr('data-default-image');
        const $card = $wrapper.closest('.obwk-product-card, .product, .thunk-product, .product-inner, .grid-item, li.product, article.product');
        const $img = $card.find('.obwk-primary-img, img.wp-post-image, img.attachment-woocommerce_thumbnail, .thunk-product-image img, .woocommerce-LoopProduct-link img, .obwk-product-img-box img').first();

        if ($img.length && $img.data('obwk_orig_src')) {
            $img.attr('src', $img.data('obwk_orig_src'));
            if ($img.data('obwk_orig_srcset')) {
                $img.attr('srcset', $img.data('obwk_orig_srcset'));
            }
            $wrapper.find('.obwk-loop-swatch').removeClass('is-active');
        }
    });

    // Initialize all variations forms on page load
    $(document).ready(function () {
        $('form.variations_form').each(function () {
            initFormSwatches($(this));
        });
    });

    // Re-initialize when Theme Quick View Modals or AJAX Popups open
    $(document).ajaxComplete(function () {
        $('form.variations_form').each(function () {
            initFormSwatches($(this));
        });
    });

    // Specific theme Quick View event triggers
    $(document).on('th_quick_view_open thnew_quick_view_open quickview_loaded quick_view_loaded wc_quick_view_loaded elementor/popup/show', function () {
        setTimeout(function () {
            $('form.variations_form').each(function () {
                initFormSwatches($(this));
            });
        }, 150);
    });

})(jQuery);
