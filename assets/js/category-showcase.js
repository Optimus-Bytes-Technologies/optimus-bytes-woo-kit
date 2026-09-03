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

            // Wait for Swiper class to be available
            if (typeof Swiper !== 'undefined') {
                new Swiper($swiperEl[0], config);
            } else if (window.elementorFrontend && window.elementorFrontend.utils && window.elementorFrontend.utils.swiper) {
                new window.elementorFrontend.utils.swiper($swiperEl[0], config).then(function (swiperInstance) {
                    $swiperEl[0].swiper = swiperInstance;
                });
            }
        });
    }

    // Standard DOM Ready
    $(document).ready(function () {
        initCategorySlider($(document));
    });

    // Elementor Frontend & Live Editor Hook
    $(window).on('elementor/frontend/init', function () {
        if (typeof elementorFrontend !== 'undefined' && elementorFrontend.hooks) {
            elementorFrontend.hooks.addAction(
                'frontend/element_ready/obwk_category_slider_grid.default',
                function ($scope) {
                    initCategorySlider($scope);
                }
            );
        }
    });

})(jQuery);
