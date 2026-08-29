<?php
/**
 * Sticky Floating Add to Cart & Buy Now Bar Module
 *
 * @package OptimusBytes\WooKit\Modules\Sticky_Add_To_Cart
 */

namespace OptimusBytes\WooKit\Modules\Sticky_Add_To_Cart;

use OptimusBytes\WooKit\Modules\Abstract_Module;

defined('ABSPATH') || exit;

class Sticky_Add_To_Cart_Module extends Abstract_Module {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id          = 'sticky_add_to_cart';
        $this->title       = __('Sticky Add to Cart & Buy Now Bar', 'optimus-bytes-woo-kit');
        $this->description = __('Floating purchase bar with options for fixed or on-scroll display, theme inheritance, live price, quantity stepper, and 1-Click Buy Now direct checkout.', 'optimus-bytes-woo-kit');
        $this->icon        = '🛒';
        $this->category    = __('Conversions & Checkout', 'optimus-bytes-woo-kit');
    }

    /**
     * Initialize module hooks
     */
    public function init() {
        add_action('customize_register', array($this, 'register_customizer_settings'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'render_sticky_bar'), 30);

        // Handle 1-Click Buy Now direct checkout redirection
        add_filter('woocommerce_add_to_cart_redirect', array($this, 'handle_buy_now_redirect'), 999, 2);
    }

    /**
     * Handle Buy Now redirect to checkout
     *
     * @param string $url
     * @param \WC_Product $adding_to_cart
     * @return string
     */
    public function handle_buy_now_redirect($url, $adding_to_cart = null) {
        if (isset($_REQUEST['obwk_buy_now']) && '1' === (string) $_REQUEST['obwk_buy_now']) {
            return wc_get_checkout_url();
        }
        return $url;
    }

    /**
     * Register Customizer settings
     *
     * @param \WP_Customize_Manager $wp_customize
     */
    public function register_customizer_settings($wp_customize) {
        $section_id = 'obwk_sticky_add_to_cart_section';

        $wp_customize->add_section($section_id, array(
            'title'       => __('Sticky Add to Cart Bar', 'optimus-bytes-woo-kit'),
            'description' => __('Configure the floating purchase bar on product pages to boost mobile & desktop conversions.', 'optimus-bytes-woo-kit'),
            'priority'    => 122,
        ));

        // Enable / Disable
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[sticky_add_to_cart_enable]', array(
            'type'              => 'option',
            'default'           => true,
            'sanitize_callback' => 'wp_validate_boolean',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[sticky_add_to_cart_enable]', array(
            'label'    => __('Enable Sticky Add to Cart Bar', 'optimus-bytes-woo-kit'),
            'section'  => $section_id,
            'type'     => 'checkbox',
        ));

        // Display Trigger Mode: On Scroll vs Always Fixed
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[sticky_add_to_cart_trigger]', array(
            'type'              => 'option',
            'default'           => 'on_scroll',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[sticky_add_to_cart_trigger]', array(
            'label'       => __('Display Mode (Trigger)', 'optimus-bytes-woo-kit'),
            'description' => __('Choose when the sticky bar should appear.', 'optimus-bytes-woo-kit'),
            'section'     => $section_id,
            'type'        => 'select',
            'choices'     => array(
                'on_scroll'      => __('On Scroll (Slide in after scrolling past main button)', 'optimus-bytes-woo-kit'),
                'always_visible' => __('Always Fixed Sticky (Visible immediately on page load)', 'optimus-bytes-woo-kit'),
            ),
        ));

        // Theme Style (with Adopt Current Theme Style option)
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[sticky_add_to_cart_style]', array(
            'type'              => 'option',
            'default'           => 'inherit_theme',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[sticky_add_to_cart_style]', array(
            'label'       => __('Theme Style', 'optimus-bytes-woo-kit'),
            'description' => __('Choose to inherit your active store theme styling or pick a custom preset.', 'optimus-bytes-woo-kit'),
            'section'     => $section_id,
            'type'        => 'select',
            'choices'     => array(
                'inherit_theme'     => __('Adopt Current Theme Style (Recommended)', 'optimus-bytes-woo-kit'),
                'luxury_saree_gold' => __('Luxury Saree Theme (Dark & Gold Accent)', 'optimus-bytes-woo-kit'),
                'modern_dark'       => __('Modern Dark Slate', 'optimus-bytes-woo-kit'),
                'clean_white'       => __('Clean White Minimal', 'optimus-bytes-woo-kit'),
            ),
        ));

        // Position on Desktop
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[sticky_add_to_cart_position]', array(
            'type'              => 'option',
            'default'           => 'bottom',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[sticky_add_to_cart_position]', array(
            'label'    => __('Desktop Position', 'optimus-bytes-woo-kit'),
            'section'  => $section_id,
            'type'     => 'select',
            'choices'  => array(
                'bottom' => __('Bottom of Screen (Recommended)', 'optimus-bytes-woo-kit'),
                'top'    => __('Top of Screen (Fixed)', 'optimus-bytes-woo-kit'),
            ),
        ));

        // Enable Buy Now Button
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[sticky_add_to_cart_enable_buynow]', array(
            'type'              => 'option',
            'default'           => true,
            'sanitize_callback' => 'wp_validate_boolean',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[sticky_add_to_cart_enable_buynow]', array(
            'label'       => __('Enable 1-Click "Buy Now" Button', 'optimus-bytes-woo-kit'),
            'description' => __('Adds an instant checkout button that bypasses the cart and takes customers straight to payment.', 'optimus-bytes-woo-kit'),
            'section'     => $section_id,
            'type'        => 'checkbox',
        ));

        // Add to Cart Button Text
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[sticky_add_to_cart_cart_btn_text]', array(
            'type'              => 'option',
            'default'           => 'Add to Cart',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[sticky_add_to_cart_cart_btn_text]', array(
            'label'    => __('Add to Cart Button Text', 'optimus-bytes-woo-kit'),
            'section'  => $section_id,
            'type'     => 'text',
        ));

        // Buy Now Button Text
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[sticky_add_to_cart_buynow_btn_text]', array(
            'type'              => 'option',
            'default'           => 'Buy Now',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[sticky_add_to_cart_buynow_btn_text]', array(
            'label'    => __('Buy Now Button Text', 'optimus-bytes-woo-kit'),
            'section'  => $section_id,
            'type'     => 'text',
        ));

        // Show Quantity Selector
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[sticky_add_to_cart_show_qty]', array(
            'type'              => 'option',
            'default'           => true,
            'sanitize_callback' => 'wp_validate_boolean',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[sticky_add_to_cart_show_qty]', array(
            'label'    => __('Show Quantity Stepper', 'optimus-bytes-woo-kit'),
            'section'  => $section_id,
            'type'     => 'checkbox',
        ));
    }

    /**
     * Enqueue CSS and JS assets on single product pages
     */
    public function enqueue_scripts() {
        if (!function_exists('is_product') || !is_product() || !$this->is_enabled()) {
            return;
        }

        wp_enqueue_style(
            'obwk-sticky-cart-style',
            OBWK_PLUGIN_URL . 'assets/css/sticky-add-to-cart.css',
            array(),
            OBWK_VERSION
        );

        wp_enqueue_script(
            'obwk-sticky-cart-script',
            OBWK_PLUGIN_URL . 'assets/js/sticky-add-to-cart.js',
            array('jquery'),
            OBWK_VERSION,
            true
        );

        wp_localize_script('obwk-sticky-cart-script', 'obwkStickyCart', array(
            'checkout_url' => wc_get_checkout_url(),
            'i18n'         => array(
                'adding'     => __('Adding...', 'optimus-bytes-woo-kit'),
                'added'      => __('✓ Added!', 'optimus-bytes-woo-kit'),
                'processing' => __('Processing...', 'optimus-bytes-woo-kit'),
            ),
        ));
    }

    /**
     * Render Sticky Add to Cart markup
     */
    public function render_sticky_bar() {
        if (!function_exists('is_product') || !is_product() || !$this->is_enabled()) {
            return;
        }

        global $product;
        if (!is_a($product, '\WC_Product')) {
            $product = wc_get_product(get_the_ID());
        }

        if (!$product || !$product->is_purchasable() || !$product->is_in_stock()) {
            return;
        }

        $trigger_mode  = $this->get_option('trigger', 'on_scroll');
        $position      = $this->get_option('position', 'bottom');
        $enable_buynow = (bool) $this->get_option('enable_buynow', true);
        $cart_btn_text = $this->get_option('cart_btn_text', __('Add to Cart', 'optimus-bytes-woo-kit'));
        $buynow_btn    = $this->get_option('buynow_btn_text', __('Buy Now', 'optimus-bytes-woo-kit'));
        $show_qty      = (bool) $this->get_option('show_qty', true);
        $theme_style   = $this->get_option('style', 'inherit_theme');

        $product_id    = $product->get_id();
        $product_title = $product->get_name();
        $image_id      = $product->get_image_id();
        $image_url     = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : wc_placeholder_img_src('thumbnail');
        $price_html    = $product->get_price_html();
        $is_variable   = $product->is_type('variable');

        $is_always_visible = ('always_visible' === $trigger_mode);
        $visibility_class  = $is_always_visible ? 'is-visible is-always-fixed' : '';

        ?>
        <div class="obwk-sticky-cart-bar obwk-pos-<?php echo esc_attr($position); ?> obwk-style-<?php echo esc_attr($theme_style); ?> <?php echo esc_attr($visibility_class); ?>" 
             id="obwk-sticky-cart-bar" 
             data-product-id="<?php echo esc_attr($product_id); ?>"
             data-is-variable="<?php echo $is_variable ? '1' : '0'; ?>"
             data-trigger="<?php echo esc_attr($trigger_mode); ?>"
             aria-hidden="<?php echo $is_always_visible ? 'false' : 'true'; ?>">
            <div class="obwk-sticky-cart-inner">
                
                <!-- Product Overview -->
                <div class="obwk-sticky-product-info">
                    <div class="obwk-sticky-thumbnail">
                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($product_title); ?>" width="52" height="52" loading="lazy" />
                    </div>
                    <div class="obwk-sticky-details">
                        <h4 class="obwk-sticky-title" title="<?php echo esc_attr($product_title); ?>"><?php echo esc_html($product_title); ?></h4>
                        <div class="obwk-sticky-price"><?php echo wp_kses_post($price_html); ?></div>
                    </div>
                </div>

                <!-- Actions Container -->
                <div class="obwk-sticky-actions">
                    <?php if ($show_qty && !$product->is_sold_individually()) : ?>
                        <div class="obwk-sticky-qty-stepper">
                            <button type="button" class="obwk-qty-btn obwk-qty-minus" aria-label="<?php esc_attr_e('Decrease quantity', 'optimus-bytes-woo-kit'); ?>">−</button>
                            <input type="number" class="obwk-qty-input" value="1" min="1" max="<?php echo esc_attr($product->get_max_purchase_quantity() > 0 ? $product->get_max_purchase_quantity() : ''); ?>" step="1" aria-label="<?php esc_attr_e('Quantity', 'optimus-bytes-woo-kit'); ?>" />
                            <button type="button" class="obwk-qty-btn obwk-qty-plus" aria-label="<?php esc_attr_e('Increase quantity', 'optimus-bytes-woo-kit'); ?>">+</button>
                        </div>
                    <?php endif; ?>

                    <!-- Add to Cart Button -->
                    <button type="button" class="obwk-sticky-btn obwk-btn-add-to-cart" id="obwk-sticky-add-cart">
                        <svg class="obwk-btn-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                        <span class="obwk-btn-label"><?php echo esc_html($cart_btn_text); ?></span>
                    </button>

                    <!-- Buy Now Button -->
                    <?php if ($enable_buynow) : ?>
                        <button type="button" class="obwk-sticky-btn obwk-btn-buy-now" id="obwk-sticky-buy-now">
                            <svg class="obwk-btn-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                            <span class="obwk-btn-label"><?php echo esc_html($buynow_btn); ?></span>
                        </button>
                    <?php endif; ?>
                </div>

            </div>
        </div>
        <?php
    }
}
