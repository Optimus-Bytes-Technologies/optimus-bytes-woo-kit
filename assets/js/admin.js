(function ($) {
    'use strict';

    $(document).ready(function () {
        // Handle Enable / Disable toggle switch
        $(document).on('change', '.obwk-module-toggle', function () {
            const $toggle = $(this);
            const moduleId = $toggle.data('module');
            const isChecked = $toggle.is(':checked');
            const $card = $toggle.closest('.obwk-module-card');
            const $badge = $card.find('.obwk-badge');
            const $badgeText = $card.find('.obwk-badge-text');

            $toggle.prop('disabled', true);
            $badgeText.text(obwkAdmin.i18n.updating);

            $.ajax({
                url: obwkAdmin.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'obwk_toggle_module',
                    nonce: obwkAdmin.nonce,
                    module_id: moduleId,
                    enable: isChecked ? 1 : 0
                },
                success: function (res) {
                    $toggle.prop('disabled', false);

                    if (res && res.success) {
                        if (isChecked) {
                            $card.addClass('is-active');
                            $badge.removeClass('obwk-badge-inactive').addClass('obwk-badge-active');
                            $badgeText.text(obwkAdmin.i18n.active);
                        } else {
                            $card.removeClass('is-active');
                            $badge.removeClass('obwk-badge-active').addClass('obwk-badge-inactive');
                            $badgeText.text(obwkAdmin.i18n.disabled);
                        }
                        showToast(res.data.message || obwkAdmin.i18n.saved, 'success');
                    } else {
                        $toggle.prop('checked', !isChecked);
                        $badgeText.text(isChecked ? obwkAdmin.i18n.disabled : obwkAdmin.i18n.active);
                        showToast((res && res.data && res.data.message) || obwkAdmin.i18n.error, 'error');
                    }
                },
                error: function () {
                    $toggle.prop('disabled', false);
                    $toggle.prop('checked', !isChecked);
                    $badgeText.text(isChecked ? obwkAdmin.i18n.disabled : obwkAdmin.i18n.active);
                    showToast(obwkAdmin.i18n.error, 'error');
                }
            });
        });

        // Toast Notification Function
        function showToast(message, type) {
            $('.obwk-toast-notice').remove();
            const $toast = $('<div class="obwk-toast-notice obwk-toast-' + type + '">' +
                '<span class="obwk-toast-icon">' + (type === 'success' ? '✓' : '✕') + '</span>' +
                '<span class="obwk-toast-msg">' + message + '</span>' +
                '</div>');

            $('body').append($toast);

            setTimeout(function () {
                $toast.addClass('is-visible');
            }, 50);

            setTimeout(function () {
                $toast.removeClass('is-visible');
                setTimeout(function () {
                    $toast.remove();
                }, 300);
            }, 3200);
        }
    });
})(jQuery);
