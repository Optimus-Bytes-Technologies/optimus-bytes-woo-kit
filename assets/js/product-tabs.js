/**
 * Optimus Bytes Woo Kit - Tabbed Product Carousel & Grid JavaScript
 *
 * @package OptimusBytes\WooKit
 */
(function ($) {
    'use strict';

    // Global safety guard for theme/plugin cart variables
    if (typeof window.storeOneCart === 'undefined') {
        window.storeOneCart = { cartFoatVisible: false, cartOpen: 'simple-open' };
    }

    /**
     * Initialize Product Tabs & Swiper Carousels
     *
     * @param {jQuery} $scope Container element
     */
    function initProductTabs($scope) {
        var $wrappers = $scope.hasClass('obwk-product-tabs-wrapper') ? $scope : $scope.find('.obwk-product-tabs-wrapper');

        $wrappers.each(function () {
            var $wrapper = $(this);
            var rawConfig = $wrapper.attr('data-slider-config');
            var baseConfig = {};

            try {
                baseConfig = rawConfig ? JSON.parse(rawConfig) : {};
            } catch (e) {
                baseConfig = {};
            }

            // Initialize Swipers inside each panel
            $wrapper.find('.obwk-tab-panel').each(function () {
                var $panel = $(this);
                var $swiperEl = $panel.find('.obwk-product-swiper');

                if (!$swiperEl.length) {
                    return;
                }

                if ($swiperEl[0].swiper) {
                    $swiperEl[0].swiper.destroy(true, true);
                }

                var panelConfig = $.extend(true, {}, baseConfig);

                var $prevBtn = $panel.find('.obwk-swiper-prev');
                var $nextBtn = $panel.find('.obwk-swiper-next');
                if ($prevBtn.length && $nextBtn.length) {
                    panelConfig.navigation = {
                        prevEl: $prevBtn[0],
                        nextEl: $nextBtn[0]
                    };
                }

                var $pagination = $panel.find('.obwk-swiper-pagination');
                if ($pagination.length) {
                    panelConfig.pagination = {
                        el: $pagination[0],
                        clickable: true
                    };
                }

                panelConfig.grabCursor = true;
                panelConfig.watchOverflow = true;
                panelConfig.observer = true;
                panelConfig.observeParents = true;

                // Initialize Swiper safely
                var SwiperClass = null;
                if (typeof Swiper !== 'undefined') {
                    SwiperClass = Swiper;
                } else if (window.elementorFrontend && window.elementorFrontend.utils && window.elementorFrontend.utils.swiper) {
                    SwiperClass = window.elementorFrontend.utils.swiper;
                }

                if (SwiperClass) {
                    try {
                        var instance = new SwiperClass($swiperEl[0], panelConfig);
                        if (instance && typeof instance.then === 'function') {
                            instance.then(function (inst) {
                                $swiperEl[0].swiper = inst;
                            });
                        } else {
                            $swiperEl[0].swiper = instance;
                        }
                    } catch (err) {
                        console.warn('OBWK Product Tabs Swiper Init Notice:', err);
                    }
                }
            });

            // Tab Click Switcher
            $wrapper.on('click', '.obwk-tab-btn', function (e) {
                e.preventDefault();
                var $btn = $(this);
                var targetId = $btn.attr('data-target');

                if ($btn.hasClass('is-active')) {
                    return;
                }

                // Update tab buttons
                $wrapper.find('.obwk-tab-btn').removeClass('is-active').attr('aria-selected', 'false');
                $btn.addClass('is-active').attr('aria-selected', 'true');

                // Switch Panels
                $wrapper.find('.obwk-tab-panel').hide().removeClass('is-active');
                var $targetPanel = $('#' + targetId);
                $targetPanel.fadeIn(200).addClass('is-active');

                // Recalculate Swiper inside target panel
                var targetSwiper = $targetPanel.find('.obwk-product-swiper');
                if (targetSwiper.length && targetSwiper[0].swiper) {
                    setTimeout(function () {
                        targetSwiper[0].swiper.update();
                    }, 50);
                }
            });
        });
    }

    // AJAX Add to Cart Handler for Carousel Cards
    $(document).on('click', '.obwk-card-actions .ajax_add_to_cart, .obwk-card-actions .add_to_cart_button', function (e) {
        var $btn = $(this);

        // If variable, external, or grouped product, let browser follow link to product page
        if ($btn.hasClass('product_type_variable') || $btn.hasClass('product_type_external') || $btn.hasClass('product_type_grouped')) {
            return;
        }

        var productId = $btn.data('product_id') || $btn.attr('data-product_id');
        if (!productId) {
            return;
        }

        e.preventDefault();

        if ($btn.hasClass('loading')) {
            return;
        }

        $btn.removeClass('added').addClass('loading');

        var data = {
            'product_id': productId,
            'quantity': $btn.data('quantity') || 1
        };

        // Determine AJAX endpoint
        var ajaxUrl = (typeof obwkProductTabs !== 'undefined' && obwkProductTabs.wc_ajax_url)
            ? obwkProductTabs.wc_ajax_url.replace('%%endpoint%%', 'add_to_cart')
            : (window.wc_add_to_cart_params && window.wc_add_to_cart_params.wc_ajax_url
                ? window.wc_add_to_cart_params.wc_ajax_url.replace('%%endpoint%%', 'add_to_cart')
                : '/?wc-ajax=add_to_cart');

        $.ajax({
            type: 'POST',
            url: ajaxUrl,
            data: data,
            dataType: 'json',
            success: function (response) {
                $btn.removeClass('loading');

                if (response.error && response.product_url) {
                    window.location = response.product_url;
                    return;
                }

                $btn.addClass('added');

                // Trigger standard WooCommerce and 3rd-party cart refresh events
                $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $btn]);
                $(document).trigger('added_to_cart', [response.fragments, response.cart_hash, $btn]);
                $(document.body).trigger('wc_fragment_refresh');
                $(document.body).trigger('wc_fragments_refreshed');

                // Replace fragments manually if available
                if (response.fragments) {
                    $.each(response.fragments, function (key, value) {
                        $(key).replaceWith(value);
                    });
                }
            },
            error: function () {
                $btn.removeClass('loading');
            }
        });
    });

    // Remove loading state on WooCommerce fragment refresh
    $(document.body).on('added_to_cart wc_fragments_refreshed', function (e, fragments, cart_hash, $button) {
        if ($button && $button.length) {
            $button.removeClass('loading').addClass('added');
        } else {
            $('.obwk-card-actions .add_to_cart_button.loading').removeClass('loading').addClass('added');
        }
    });

    $(document.body).on('wc_fragments_ajax_error', function () {
        $('.obwk-card-actions .add_to_cart_button.loading').removeClass('loading');
    });

    // Standard DOM Ready
    $(document).ready(function () {
        initProductTabs($(document));
    });

    // Elementor Live Editor Integration
    function registerElementorProductTabsHook() {
        if (typeof elementorFrontend !== 'undefined' && elementorFrontend.hooks) {
            elementorFrontend.hooks.addAction(
                'frontend/element_ready/obwk_product_tabs.default',
                function ($scope) {
                    initProductTabs($scope);
                }
            );
        }
    }

    $(window).on('elementor/frontend/init', registerElementorProductTabsHook);
    if (typeof elementorFrontend !== 'undefined' && elementorFrontend.hooks) {
        registerElementorProductTabsHook();
    }

})(jQuery);
