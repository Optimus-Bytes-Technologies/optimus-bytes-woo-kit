(function ($) {
    'use strict';

    $(document).ready(function () {
        const $stickyBar = $('#obwk-sticky-cart-bar');
        if (!$stickyBar.length) {
            return;
        }

        const triggerMode = $stickyBar.data('trigger') || 'on_scroll';
        const $mainForm = $('form.cart');
        const $mainAddToCartBtn = $mainForm.find('.single_add_to_cart_button');
        const $mainQtyInput = $mainForm.find('input.qty');
        const $stickyQtyInput = $stickyBar.find('.obwk-qty-input');
        const $viewCartBtn = $('#obwk-sticky-view-cart');
        const $badge = $('.obwk-cart-count-badge');
        const isVariable = $stickyBar.data('is-variable') === 1;

        // 1. Trigger Handling
        if (triggerMode === 'always_visible') {
            $stickyBar.addClass('is-visible').attr('aria-hidden', 'false');
            $('body').addClass('obwk-sticky-cart-active');
        } else {
            // High-Performance Scroll Observer via IntersectionObserver
            const targetElement = $mainAddToCartBtn.length ? $mainAddToCartBtn[0] : ($mainForm.length ? $mainForm[0] : null);

            if (targetElement && 'IntersectionObserver' in window) {
                const observerOptions = {
                    root: null,
                    threshold: 0,
                    rootMargin: '-80px 0px 0px 0px'
                };

                const observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (!entry.isIntersecting && entry.boundingClientRect.top < 0) {
                            $stickyBar.addClass('is-visible').attr('aria-hidden', 'false');
                            $('body').addClass('obwk-sticky-cart-active');
                        } else {
                            $stickyBar.removeClass('is-visible').attr('aria-hidden', 'true');
                            $('body').removeClass('obwk-sticky-cart-active');
                        }
                    });
                }, observerOptions);

                observer.observe(targetElement);
            } else {
                // Fallback for older browsers
                $(window).on('scroll resize', function () {
                    if ($mainAddToCartBtn.length) {
                        const btnOffset = $mainAddToCartBtn.offset().top + $mainAddToCartBtn.outerHeight();
                        const scrollTop = $(window).scrollTop();

                        if (scrollTop > btnOffset + 50) {
                            $stickyBar.addClass('is-visible').attr('aria-hidden', 'false');
                            $('body').addClass('obwk-sticky-cart-active');
                        } else {
                            $stickyBar.removeClass('is-visible').attr('aria-hidden', 'true');
                            $('body').removeClass('obwk-sticky-cart-active');
                        }
                    }
                });
            }
        }

        // 2. Quantity Stepper Sync
        $stickyBar.on('click', '.obwk-qty-plus', function (e) {
            e.preventDefault();
            let currentVal = parseInt($stickyQtyInput.val(), 10) || 1;
            const maxVal = parseInt($stickyQtyInput.attr('max'), 10) || 9999;
            if (currentVal < maxVal) {
                currentVal += 1;
                $stickyQtyInput.val(currentVal).trigger('change');
                if ($mainQtyInput.length) {
                    $mainQtyInput.val(currentVal).trigger('change');
                }
            }
        });

        $stickyBar.on('click', '.obwk-qty-minus', function (e) {
            e.preventDefault();
            let currentVal = parseInt($stickyQtyInput.val(), 10) || 1;
            const minVal = parseInt($stickyQtyInput.attr('min'), 10) || 1;
            if (currentVal > minVal) {
                currentVal -= 1;
                $stickyQtyInput.val(currentVal).trigger('change');
                if ($mainQtyInput.length) {
                    $mainQtyInput.val(currentVal).trigger('change');
                }
            }
        });

        $stickyQtyInput.on('input change', function () {
            const val = $(this).val();
            if ($mainQtyInput.length) {
                $mainQtyInput.val(val).trigger('change');
            }
        });

        if ($mainQtyInput.length) {
            $mainQtyInput.on('input change', function () {
                $stickyQtyInput.val($(this).val());
            });
        }

        // 3. Live Price & Variation Updates on Variable Products
        if (isVariable) {
            $(document).on('found_variation', 'form.cart', function (event, variation) {
                if (variation && variation.price_html) {
                    $stickyBar.find('.obwk-sticky-price').html(variation.price_html);
                }
                if (variation && variation.image && variation.image.thumb_src) {
                    $stickyBar.find('.obwk-sticky-thumbnail img').attr('src', variation.image.thumb_src);
                }
            });
        }

        // 4. Live WooCommerce Cart Fragments & Badge Update
        $(document.body).on('added_to_cart removed_from_cart wc_fragments_refreshed wc_fragments_loaded', function (event, fragments) {
            let count = null;

            if (fragments && fragments['span.obwk-cart-count-badge']) {
                $badge.replaceWith(fragments['span.obwk-cart-count-badge']);
                count = parseInt($('span.obwk-cart-count-badge').text(), 10);
            } else {
                // If fragment not in payload, increment count on added_to_cart
                const currentCount = parseInt($badge.text(), 10) || 0;
                const addQty = parseInt($stickyQtyInput.val(), 10) || 1;
                count = (event.type === 'added_to_cart') ? (currentCount + addQty) : currentCount;
                $badge.text(count).attr('data-count', count);
            }

            // Animate Badge Bump
            $('.obwk-cart-count-badge').addClass('bump');
            setTimeout(function () {
                $('.obwk-cart-count-badge').removeClass('bump');
            }, 400);

            if (count > 0) {
                $viewCartBtn.removeClass('is-empty').addClass('has-items');
            }
        });

        // 5. Handle "Add to Cart" Button Click
        $('#obwk-sticky-add-cart').on('click', function (e) {
            e.preventDefault();
            const $btn = $(this);

            if ($mainAddToCartBtn.hasClass('disabled') || $mainAddToCartBtn.is(':disabled')) {
                $('html, body').animate({
                    scrollTop: $mainForm.offset().top - 120
                }, 400);
                $mainAddToCartBtn.trigger('click');
                return;
            }

            const qty = parseInt($stickyQtyInput.val(), 10) || 1;
            if ($mainQtyInput.length) {
                $mainQtyInput.val(qty).trigger('change');
            }

            $mainForm.find('input[name="obwk_buy_now"]').remove();

            $btn.addClass('is-loading').prop('disabled', true);
            const originalText = $btn.find('.obwk-btn-label').text();
            $btn.find('.obwk-btn-label').text(obwkStickyCart.i18n.adding);

            // Trigger main Add to Cart button
            $mainAddToCartBtn.trigger('click');

            setTimeout(function () {
                $btn.removeClass('is-loading').prop('disabled', false);
                $btn.find('.obwk-btn-label').text(originalText);
            }, 1200);
        });

        // 6. Handle 1-Click "Buy Now" Direct Checkout Button Click
        $('#obwk-sticky-buy-now').on('click', function (e) {
            e.preventDefault();
            const $btn = $(this);

            if ($mainAddToCartBtn.hasClass('disabled') || $mainAddToCartBtn.is(':disabled')) {
                $('html, body').animate({
                    scrollTop: $mainForm.offset().top - 120
                }, 400);
                $mainAddToCartBtn.trigger('click');
                return;
            }

            $btn.addClass('is-loading').prop('disabled', true);
            $btn.find('.obwk-btn-label').text(obwkStickyCart.i18n.processing);

            const qty = parseInt($stickyQtyInput.val(), 10) || 1;
            if ($mainQtyInput.length) {
                $mainQtyInput.val(qty).trigger('change');
            }

            const productId = $stickyBar.data('product-id');
            const checkoutUrl = obwkStickyCart.checkout_url;

            const formData = $mainForm.serializeArray();
            let hasAddToCart = false;

            for (let i = 0; i < formData.length; i++) {
                if (formData[i].name === 'add-to-cart') {
                    hasAddToCart = true;
                    break;
                }
            }

            if (!hasAddToCart) {
                formData.push({ name: 'add-to-cart', value: productId });
            }
            formData.push({ name: 'obwk_buy_now', value: '1' });

            $.ajax({
                type: 'POST',
                url: $mainForm.attr('action') || window.location.href,
                data: $.param(formData),
                success: function () {
                    window.location.href = checkoutUrl;
                },
                error: function () {
                    window.location.href = checkoutUrl + '?add-to-cart=' + encodeURIComponent(productId) + '&quantity=' + encodeURIComponent(qty);
                }
            });
        });
    });
})(jQuery);
