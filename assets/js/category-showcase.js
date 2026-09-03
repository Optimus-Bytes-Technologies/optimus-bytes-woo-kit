/**
 * Optimus Bytes Woo Kit - Category Slider & Grid Showcase JavaScript
 *
 * @package OptimusBytes\WooKit
 */
(function ($) {
    'use strict';

    /**
     * Initialize Swiper Carousel for Category Showcase
     *
     * @param {jQuery} $scope Container element
     */
    function initCategorySlider($scope) {
        var $showcase = $scope.hasClass('obwk-category-showcase') ? $scope : $scope.find('.obwk-category-showcase');

        $showcase.each(function () {
            var $this = $(this);

            if (!$this.hasClass('obwk-showcase-layout-slider')) {
                return;
            }

            var $swiperEl = $this.find('.obwk-category-swiper');
            if (!$swiperEl.length) {
                return;
            }

            // Destroy existing instance if re-initializing in Elementor editor
            if ($swiperEl[0].swiper) {
                $swiperEl[0].swiper.destroy(true, true);
            }

            var rawConfig = $this.attr('data-slider-config');
            var config = {};

            try {
                config = rawConfig ? JSON.parse(rawConfig) : {};
            } catch (e) {
                config = {};
            }

            // Bind navigation elements
            var $prevBtn = $this.find('.obwk-swiper-prev');
            var $nextBtn = $this.find('.obwk-swiper-next');
            if ($prevBtn.length && $nextBtn.length) {
                config.navigation = {
                    prevEl: $prevBtn[0],
                    nextEl: $nextBtn[0]
                };
            }

            // Bind pagination
            var $pagination = $this.find('.obwk-swiper-pagination');
            if ($pagination.length) {
                config.pagination = {
                    el: $pagination[0],
                    clickable: true
                };
            }

            // Touch / Keyboard / Grab cursor
            config.grabCursor = true;
            config.watchOverflow = true;

            // Initialize Swiper instance safely
            var SwiperClass = null;
            if (typeof Swiper !== 'undefined') {
                SwiperClass = Swiper;
            } else if (window.elementorFrontend && window.elementorFrontend.utils && window.elementorFrontend.utils.swiper) {
                SwiperClass = window.elementorFrontend.utils.swiper;
            }

            if (SwiperClass) {
                try {
                    var instance = new SwiperClass($swiperEl[0], config);
                    if (instance && typeof instance.then === 'function') {
                        instance.then(function (swiperInst) {
                            $swiperEl[0].swiper = swiperInst;
                        });
                    } else {
                        $swiperEl[0].swiper = instance;
                    }
                } catch (err) {
                    console.warn('OBWK Category Swiper Init Notice:', err);
                }
            }
        });
    }

    // Standard DOM Ready
    $(document).ready(function () {
        initCategorySlider($(document));
    });

    // Elementor Frontend & Live Editor Hook
    function registerElementorCategoryHook() {
        if (typeof elementorFrontend !== 'undefined' && elementorFrontend.hooks) {
            elementorFrontend.hooks.addAction(
                'frontend/element_ready/obwk_category_slider_grid.default',
                function ($scope) {
                    initCategorySlider($scope);
                }
            );
        }
    }

    $(window).on('elementor/frontend/init', registerElementorCategoryHook);
    if (typeof elementorFrontend !== 'undefined' && elementorFrontend.hooks) {
        registerElementorCategoryHook();
    }

})(jQuery);
