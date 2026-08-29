(function ($) {
    'use strict';

    $(document).ready(function () {
        const $wrapper = $('#obwk-product-whatsapp');
        if (!$wrapper.length) {
            return;
        }

        const phone = $wrapper.data('phone');
        const defaultTitle = $wrapper.data('product-title');
        const defaultPrice = $wrapper.data('product-price');
        const defaultSku = $wrapper.data('product-sku');
        const productUrl = $wrapper.data('product-url');
        const greeting = $wrapper.data('greeting');
        const showSku = String($wrapper.data('show-sku')) === '1';
        const showPrice = String($wrapper.data('show-price')) === '1';

        let currentVariationData = null;

        function updateWhatsAppLink() {
            const $btn = $wrapper.find('.obwk-product-wa-btn');
            const $qtyInput = $('form.cart input.qty, input[name="quantity"]');
            const quantity = $qtyInput.length ? parseInt($qtyInput.val(), 10) || 1 : 1;

            let title = defaultTitle;
            let price = defaultPrice;
            let sku = defaultSku;
            let selectedAttributes = [];

            // If variable product and variation selected
            if (currentVariationData) {
                if (currentVariationData.sku) {
                    sku = currentVariationData.sku;
                }
                if (currentVariationData.display_price_html) {
                    // Extract text price
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = currentVariationData.display_price_html;
                    price = tempDiv.textContent || tempDiv.innerText || price;
                }

                // Collect attribute values
                $('form.variations_form select[name^="attribute_"]').each(function () {
                    const attrName = $(this).data('attribute_name') || $(this).attr('name');
                    const attrVal = $(this).val();
                    if (attrVal) {
                        const cleanName = attrName.replace('attribute_pa_', '').replace('attribute_', '');
                        selectedAttributes.push(cleanName.charAt(0).toUpperCase() + cleanName.slice(1) + ': ' + attrVal);
                    }
                });
            }

            // Build Message Lines
            const lines = [];
            lines.push(greeting);
            lines.push('🛍️ Product: ' + title);

            if (selectedAttributes.length > 0) {
                lines.push('🎨 Option: ' + selectedAttributes.join(', '));
            }

            if (showPrice && price) {
                lines.push('💰 Price: ' + price);
            }

            if (showSku && sku) {
                lines.push('🔢 SKU: ' + sku);
            }

            lines.push('📦 Quantity: ' + quantity);
            lines.push('🔗 Link: ' + productUrl);
            lines.push('\nPlease let me know how to proceed with the order.');

            const fullMessage = lines.join('\n');
            const waUrl = 'https://wa.me/' + encodeURIComponent(phone) + '?text=' + encodeURIComponent(fullMessage);

            $btn.attr('href', waUrl);
        }

        // Listen for Quantity Changes
        $(document).on('input change', 'form.cart input.qty, input[name="quantity"]', function () {
            updateWhatsAppLink();
        });

        // Listen for WooCommerce Variable Product Events
        $('form.variations_form').on('found_variation', function (event, variation) {
            currentVariationData = variation;
            updateWhatsAppLink();
        });

        $('form.variations_form').on('reset_data', function () {
            currentVariationData = null;
            updateWhatsAppLink();
        });

        // Re-update immediately before clicking the button
        $wrapper.on('click', '.obwk-product-wa-btn', function () {
            updateWhatsAppLink();
        });

        // Initial setup
        updateWhatsAppLink();
    });
})(jQuery);
