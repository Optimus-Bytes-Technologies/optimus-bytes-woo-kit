/**
 * Optimus Bytes Woo Kit - Checkout Field Customizer Admin Script
 *
 * @package OptimusBytes\WooKit
 */

(function ($) {
    'use strict';

    $(document).ready(function () {
        const config = window.obwkCheckoutFields || {};
        const ajaxUrl = config.ajax_url || window.ajaxurl;
        const nonce = config.nonce || '';
        const i18n = config.i18n || {};

        // 1. Initialize Sortable on Tables
        function initSortable() {
            $('.obwk-sortable-body').each(function () {
                const $tbody = $(this);
                if ($tbody.hasClass('ui-sortable')) {
                    $tbody.sortable('destroy');
                }
                $tbody.sortable({
                    handle: '.obwk-drag-handle',
                    placeholder: 'ui-sortable-placeholder',
                    axis: 'y',
                    cursor: 'grabbing',
                    opacity: 0.85,
                    stop: function () {
                        // Mark unsaved state or visual indicator if desired
                    }
                });
            });
        }
        initSortable();

        // 2. Tab Navigation
        $('.obwk-tab-btn').on('click', function () {
            const $btn = $(this);
            const targetTab = $btn.data('tab');

            $('.obwk-tab-btn').removeClass('is-active');
            $btn.addClass('is-active');

            $('.obwk-tab-pane').removeClass('is-active');
            $('#tab-pane-' + targetTab).addClass('is-active');
        });

        // 3. Inline Row Toggles & Changes
        $(document).on('change', '.obwk-field-toggle-enabled', function () {
            const $chk = $(this);
            const $row = $chk.closest('.obwk-field-row');
            if ($chk.is(':checked')) {
                $row.removeClass('is-disabled-row');
            } else {
                $row.addClass('is-disabled-row');
            }
        });

        // 4. Toast Notification Utility
        let toastTimeout = null;
        function showToast(message, type) {
            const $toast = $('#obwk-toast');
            clearTimeout(toastTimeout);
            $toast.removeClass('is-success is-error');
            if (type === 'error') {
                $toast.addClass('is-error');
            } else {
                $toast.addClass('is-success');
            }
            $toast.html(message).fadeIn(200);
            toastTimeout = setTimeout(function () {
                $toast.fadeOut(300);
            }, 3500);
        }

        // 5. Save Section Fields Configuration
        $(document).on('click', '.obwk-save-section-btn', function (e) {
            e.preventDefault();
            const $btn = $(this);
            const section = $btn.data('section');
            const $table = $('.obwk-fields-table[data-section="' + section + '"]');
            const fields = {};

            $btn.prop('disabled', true).addClass('is-loading');

            $table.find('.obwk-field-row').each(function () {
                const $row = $(this);
                const key = $row.data('field-key');
                let fieldData = {};
                try {
                    fieldData = JSON.parse($row.attr('data-field-json')) || {};
                } catch (err) {
                    fieldData = {};
                }

                const isEnabled = $row.find('.obwk-field-toggle-enabled').is(':checked');
                const isRequired = $row.find('.obwk-field-toggle-required').is(':checked');
                const rowWidth = $row.find('.obwk-row-width-select').val() || 'form-row-wide';

                fieldData.enabled = isEnabled;
                fieldData.required = isRequired;
                fieldData.class = rowWidth;

                fields[key] = fieldData;
            });

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'obwk_save_checkout_fields',
                    nonce: nonce,
                    section: section,
                    fields: fields
                },
                success: function (res) {
                    $btn.prop('disabled', false).removeClass('is-loading');
                    if (res && res.success) {
                        showToast(res.data.message || i18n.saved || 'Saved successfully!', 'success');
                    } else {
                        showToast((res && res.data && res.data.message) || i18n.error, 'error');
                    }
                },
                error: function () {
                    $btn.prop('disabled', false).removeClass('is-loading');
                    showToast(i18n.error, 'error');
                }
            });
        });

        // 6. Modal Functions: Open, Close, Field Type Switching
        const $modal = $('#obwk-field-modal');
        const $form = $('#obwk-field-form');
        const $optionsWrapper = $('#modal-options-wrapper');
        const $optionsList = $('#modal-options-list');

        function openModal() {
            $modal.fadeIn(150);
            $('body').css('overflow', 'hidden');
        }

        function closeModal() {
            $modal.fadeOut(150);
            $('body').css('overflow', '');
            $form[0].reset();
            $optionsList.empty();
            $optionsWrapper.hide();
            $('#modal-is-edit').val('0');
            $('#modal-field-name').prop('readonly', false);
        }

        $('#modal-close-btn, #modal-cancel-btn').on('click', function (e) {
            e.preventDefault();
            closeModal();
        });

        $modal.on('click', function (e) {
            if ($(e.target).is('#obwk-field-modal')) {
                closeModal();
            }
        });

        $('#modal-field-type').on('change', function () {
            const type = $(this).val();
            if (type === 'select' || type === 'radio') {
                $optionsWrapper.slideDown(150);
                if ($optionsList.children().length === 0) {
                    addOptionRow('', '');
                }
            } else {
                $optionsWrapper.slideUp(150);
            }
        });

        function addOptionRow(key, label) {
            const rowHtml = `
                <div class="obwk-option-item">
                    <input type="text" class="obwk-form-control obwk-opt-key" placeholder="Option Key (e.g. standard)" value="${escapeHtml(key)}" />
                    <input type="text" class="obwk-form-control obwk-opt-label" placeholder="Option Label (e.g. Standard Delivery)" value="${escapeHtml(label)}" />
                    <button type="button" class="obwk-remove-option-btn" title="Remove Option">&times;</button>
                </div>
            `;
            $optionsList.append(rowHtml);
        }

        $('#modal-add-option-btn').on('click', function (e) {
            e.preventDefault();
            addOptionRow('', '');
        });

        $(document).on('click', '.obwk-remove-option-btn', function (e) {
            e.preventDefault();
            $(this).closest('.obwk-option-item').remove();
        });

        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#039;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        // 7. Add New Field Button
        $('.obwk-add-field-btn').on('click', function () {
            const section = $(this).data('section');
            closeModal();

            $('#modal-heading').text(i18n.add_field || 'Add New Checkout Field');
            $('#modal-field-section').val(section);
            $('#modal-is-edit').val('0');
            $('#modal-is-custom').val('1');
            $('#modal-field-name').prop('readonly', false).val('');
            $('#modal-field-label').val('');
            $('#modal-field-placeholder').val('');
            $('#modal-field-default').val('');
            $('#modal-field-type').val('text').trigger('change');
            $('#modal-field-class').val('form-row-wide');
            $('#modal-field-required').prop('checked', false);
            $('#modal-field-enabled').prop('checked', true);
            $('#modal-field-show-order').prop('checked', true);
            $('#modal-field-show-email').prop('checked', true);

            openModal();
        });

        // 8. Edit Field Button
        $(document).on('click', '.obwk-edit-field-btn', function (e) {
            e.preventDefault();
            const $row = $(this).closest('.obwk-field-row');
            const $table = $row.closest('.obwk-fields-table');
            const section = $table.data('section');
            let field = {};
            try {
                field = JSON.parse($row.attr('data-field-json')) || {};
            } catch (err) {
                field = {};
            }

            closeModal();

            $('#modal-heading').text(i18n.edit_field || 'Edit Checkout Field');
            $('#modal-field-section').val(section);
            $('#modal-is-edit').val('1');
            $('#modal-is-custom').val(field.custom ? '1' : '0');
            $('#modal-field-name').val(field.name || '').prop('readonly', true);
            $('#modal-field-label').val(field.label || '');
            $('#modal-field-placeholder').val(field.placeholder || '');
            $('#modal-field-default').val(field.default || '');
            $('#modal-field-type').val(field.type || 'text').trigger('change');
            
            const fieldClass = (Array.isArray(field.class) ? field.class[0] : field.class) || 'form-row-wide';
            $('#modal-field-class').val(fieldClass);

            $('#modal-field-required').prop('checked', !!field.required);
            $('#modal-field-enabled').prop('checked', field.enabled !== false);
            $('#modal-field-show-order').prop('checked', field.show_in_order !== false);
            $('#modal-field-show-email').prop('checked', field.show_in_email !== false);

            if (field.options && (field.type === 'select' || field.type === 'radio')) {
                $optionsList.empty();
                $.each(field.options, function (k, v) {
                    addOptionRow(k, v);
                });
            }

            openModal();
        });

        // 9. Submit Field Form (AJAX Add / Edit)
        $form.on('submit', function (e) {
            e.preventDefault();

            const section = $('#modal-field-section').val();
            const name = $.trim($('#modal-field-name').val());
            const label = $.trim($('#modal-field-label').val());
            const type = $('#modal-field-type').val();
            const placeholder = $('#modal-field-placeholder').val();
            const defaultVal = $('#modal-field-default').val();
            const fieldClass = $('#modal-field-class').val();
            const required = $('#modal-field-required').is(':checked') ? 1 : 0;
            const enabled = $('#modal-field-enabled').is(':checked') ? 1 : 0;
            const showOrder = $('#modal-field-show-order').is(':checked') ? 1 : 0;
            const showEmail = $('#modal-field-show-email').is(':checked') ? 1 : 0;
            const isCustom = $('#modal-is-custom').val();

            if (!name) {
                alert(i18n.field_key_required || 'Please enter a valid Field Name.');
                return;
            }
            if (!label) {
                alert(i18n.label_required || 'Please enter a Field Label.');
                return;
            }

            const options = [];
            if (type === 'select' || type === 'radio') {
                $optionsList.find('.obwk-option-item').each(function () {
                    const k = $.trim($(this).find('.obwk-opt-key').val());
                    const v = $.trim($(this).find('.obwk-opt-label').val());
                    if (k) {
                        options.push({ key: k, label: v || k });
                    }
                });
            }

            const $submitBtn = $('#modal-submit-btn');
            $submitBtn.prop('disabled', true).text(i18n.saving || 'Saving...');

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'obwk_add_custom_checkout_field',
                    nonce: nonce,
                    section: section,
                    name: name,
                    label: label,
                    type: type,
                    placeholder: placeholder,
                    default: defaultVal,
                    class: fieldClass,
                    required: required,
                    enabled: enabled,
                    show_in_order: showOrder,
                    show_in_email: showEmail,
                    custom: isCustom,
                    options: options
                },
                success: function (res) {
                    $submitBtn.prop('disabled', false).text('Save Field');
                    if (res && res.success) {
                        closeModal();
                        showToast(res.data.message || i18n.saved, 'success');
                        setTimeout(function () {
                            location.reload();
                        }, 500);
                    } else {
                        alert((res && res.data && res.data.message) || i18n.error);
                    }
                },
                error: function () {
                    $submitBtn.prop('disabled', false).text('Save Field');
                    alert(i18n.error || 'Server error occurred.');
                }
            });
        });

        // 10. Delete Custom Field
        $(document).on('click', '.obwk-delete-field-btn', function (e) {
            e.preventDefault();
            if (!confirm(i18n.confirm_delete || 'Are you sure you want to delete this field?')) {
                return;
            }

            const $row = $(this).closest('.obwk-field-row');
            const $table = $row.closest('.obwk-fields-table');
            const section = $table.data('section');
            const key = $row.data('field-key');

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'obwk_delete_custom_checkout_field',
                    nonce: nonce,
                    section: section,
                    name: key
                },
                success: function (res) {
                    if (res && res.success) {
                        $row.fadeOut(250, function () {
                            $(this).remove();
                            // Update count badge
                            const count = $table.find('.obwk-field-row').length;
                            $('#count-' + section).text(count);
                        });
                        showToast(res.data.message || 'Field deleted.', 'success');
                    } else {
                        alert((res && res.data && res.data.message) || i18n.error);
                    }
                },
                error: function () {
                    alert(i18n.error);
                }
            });
        });

        // 11. Reset Section Defaults
        $('.obwk-reset-section-btn').on('click', function (e) {
            e.preventDefault();
            if (!confirm(i18n.confirm_reset || 'Are you sure you want to reset this section back to defaults?')) {
                return;
            }

            const section = $(this).data('section');

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'obwk_reset_checkout_fields',
                    nonce: nonce,
                    section: section
                },
                success: function (res) {
                    if (res && res.success) {
                        showToast(res.data.message || i18n.reset_success, 'success');
                        setTimeout(function () {
                            location.reload();
                        }, 500);
                    } else {
                        alert((res && res.data && res.data.message) || i18n.error);
                    }
                },
                error: function () {
                    alert(i18n.error);
                }
            });
        });

        // 12. Apply Quick Preset
        $('.obwk-apply-preset-btn').on('click', function (e) {
            e.preventDefault();
            const $btn = $(this);
            const section = $btn.data('section');
            const name = $btn.data('name');
            const label = $btn.data('label');
            const placeholder = $btn.data('placeholder') || '';
            const type = $btn.data('type') || 'text';
            const fieldClass = $btn.data('class') || 'form-row-wide';
            const required = $btn.data('required') || 0;
            let options = [];
            const rawOptions = $btn.data('options');

            if (rawOptions && typeof rawOptions === 'object') {
                $.each(rawOptions, function (k, v) {
                    options.push({ key: k, label: v });
                });
            }

            $btn.prop('disabled', true).text('Adding...');

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'obwk_add_custom_checkout_field',
                    nonce: nonce,
                    section: section,
                    name: name,
                    label: label,
                    type: type,
                    placeholder: placeholder,
                    class: fieldClass,
                    required: required,
                    enabled: 1,
                    show_in_order: 1,
                    show_in_email: 1,
                    custom: 1,
                    options: options
                },
                success: function (res) {
                    $btn.prop('disabled', false).text('Added!');
                    if (res && res.success) {
                        showToast(res.data.message || 'Preset added successfully!', 'success');
                        setTimeout(function () {
                            // Switch to the section tab and reload
                            location.reload();
                        }, 500);
                    } else {
                        alert((res && res.data && res.data.message) || i18n.error);
                        $btn.text('+ Add to ' + section.charAt(0).toUpperCase() + section.slice(1));
                    }
                },
                error: function () {
                    $btn.prop('disabled', false).text('+ Add');
                    alert(i18n.error);
                }
            });
        });
    });

})(jQuery);
