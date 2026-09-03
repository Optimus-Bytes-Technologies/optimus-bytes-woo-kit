/**
 * Optimus Bytes Woo Kit - Color Swatch Filter Interactive Scripts
 *
 * @package OptimusBytes\WooKit
 */
(function ($) {
    'use strict';

    function initColorFilterToggles() {
        $(document).off('click.obwkColorFilter', '.obwk-filter-more-btn').on('click.obwkColorFilter', '.obwk-filter-more-btn', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var $widget = $btn.closest('.obwk-color-filter-widget');
            var isExpanded = $widget.hasClass('is-expanded');
            var textMore = $btn.attr('data-text-more') || $btn.data('text-more');
            var textLess = $btn.attr('data-text-less') || $btn.data('text-less');

            if (isExpanded) {
                $widget.removeClass('is-expanded');
                $btn.attr('data-expanded', 'false');
                $btn.find('.obwk-filter-more-text').text(textMore);
                $btn.attr('aria-label', textMore);
            } else {
                $widget.addClass('is-expanded');
                $btn.attr('data-expanded', 'true');
                $btn.find('.obwk-filter-more-text').text(textLess);
                $btn.attr('aria-label', textLess);
            }
        });
    }

    // Document Ready
    $(document).ready(function () {
        initColorFilterToggles();
    });

    // Re-bind on AJAX completions (filter reloads, facets, etc.)
    $(document).ajaxComplete(function () {
        initColorFilterToggles();
    });

})(jQuery);
