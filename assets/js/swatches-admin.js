/**
 * Optimus Bytes Woo Kit - Variation Swatches Admin JavaScript
 *
 * @package OptimusBytes\WooKit
 */
(function ($) {
    'use strict';

    $(document).ready(function () {
        var $previewCircle = $('#obwk_admin_preview_circle');
        var $previewText   = $('#obwk_admin_preview_text');
        var $typeSelect    = $('#obwk_swatch_type');
        var mediaFrame;

        function updateLivePreview() {
            if (!$previewCircle.length) {
                return;
            }

            var type = $typeSelect.val() || 'color';
            var primaryColor = $('#obwk_swatch_color').val() || '#14b8a6';
            var secondaryColor = $('#obwk_swatch_color_secondary').val() || '#ec4899';
            var imgUrl = $('.obwk-image-preview img').attr('src');

            $previewCircle.attr('style', '');

            if ('color' === type) {
                $previewCircle.css({
                    'background': primaryColor,
                    'background-origin': 'border-box',
                    'background-repeat': 'no-repeat',
                    'display': 'inline-block'
                });
                $previewText.text('Solid Color: ' + primaryColor);
            } else if ('dual_color' === type) {
                var gradientCss = 'linear-gradient(135deg, ' + primaryColor + ' 50%, ' + secondaryColor + ' 50%)';
                $previewCircle.css({
                    'background': gradientCss,
                    'background-origin': 'border-box',
                    'background-repeat': 'no-repeat',
                    'display': 'inline-block'
                });
                $previewText.text('Two-Tone: ' + primaryColor + ' / ' + secondaryColor);
            } else if ('image' === type) {
                if (imgUrl) {
                    $previewCircle.css({
                        'background': 'url(' + imgUrl + ') center center / cover no-repeat',
                        'display': 'inline-block'
                    });
                    $previewText.text('Image Thumbnail Swatch');
                } else {
                    $previewCircle.css('display', 'none');
                    $previewText.text('No image selected yet');
                }
            } else {
                $previewCircle.css('display', 'none');
                $previewText.text('Default Text / Button Swatch');
            }
        }

        // Initialize WordPress Color Pickers
        $('.obwk-color-picker').each(function () {
            var $input = $(this);
            $input.wpColorPicker({
                change: function (event, ui) {
                    setTimeout(updateLivePreview, 10);
                },
                clear: function () {
                    setTimeout(updateLivePreview, 10);
                }
            });
        });

        // Toggle field visibility on type change
        $typeSelect.on('change', function () {
            var selectedType = $(this).val();

            if ('color' === selectedType) {
                $('.obwk-field-color').show();
                $('.obwk-field-secondary-color').hide();
                $('.obwk-field-image').hide();
            } else if ('dual_color' === selectedType) {
                $('.obwk-field-color').show();
                $('.obwk-field-secondary-color').show();
                $('.obwk-field-image').hide();
            } else if ('image' === selectedType) {
                $('.obwk-field-color').hide();
                $('.obwk-field-secondary-color').hide();
                $('.obwk-field-image').show();
            } else {
                $('.obwk-field-color').hide();
                $('.obwk-field-secondary-color').hide();
                $('.obwk-field-image').hide();
            }

            updateLivePreview();
        });

        // Media Library Frame for Image Swatches
        $(document).on('click', '.obwk-upload-image-btn', function (e) {
            e.preventDefault();

            if (mediaFrame) {
                mediaFrame.open();
                return;
            }

            mediaFrame = wp.media({
                title: obwkSwatchesAdmin.choose_image || 'Choose Swatch Image',
                button: {
                    text: obwkSwatchesAdmin.use_image || 'Use Image'
                },
                multiple: false
            });

            mediaFrame.on('select', function () {
                var attachment = mediaFrame.state().get('selection').first().toJSON();
                var url = (attachment.sizes && attachment.sizes.thumbnail) ? attachment.sizes.thumbnail.url : attachment.url;

                $('#obwk_swatch_image_id').val(attachment.id);
                $('.obwk-image-preview img').attr('src', url);
                $('.obwk-image-preview').show();
                $('.obwk-remove-image-btn').show();

                updateLivePreview();
            });

            mediaFrame.open();
        });

        // Remove Image
        $(document).on('click', '.obwk-remove-image-btn', function (e) {
            e.preventDefault();
            $('#obwk_swatch_image_id').val('');
            $('.obwk-image-preview img').attr('src', '');
            $('.obwk-image-preview').hide();
            $(this).hide();
            updateLivePreview();
        });

        // Initial preview render
        updateLivePreview();
    });

})(jQuery);
