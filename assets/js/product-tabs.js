/**
 * Optimus Bytes Woo Kit - Tabbed Product Carousel & Grid JavaScript
 *
 * @package OptimusBytes\WooKit
 */
(function ($) {
    'use strict';

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
