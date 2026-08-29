<?php
/**
 * Single Product Order on WhatsApp Module
 *
 * @package OptimusBytes\WooKit\Modules\Product_WhatsApp
 */

namespace OptimusBytes\WooKit\Modules\Product_WhatsApp;

use OptimusBytes\WooKit\Modules\Abstract_Module;

defined('ABSPATH') || exit;

class Product_WhatsApp_Module extends Abstract_Module {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id          = 'product_whatsapp';
        $this->title       = __('Product Page "Order on WhatsApp"', 'optimus-bytes-woo-kit');
        $this->description = __('Adds an "Order on WhatsApp" direct purchase button on every product page below the Add to Cart and Buy Now buttons with real-time product details, price, SKU, and selected quantity.', 'optimus-bytes-woo-kit');
        $this->icon        = '🛍️';
        $this->category    = __('Sales & Conversions', 'optimus-bytes-woo-kit');
    }

    /**
     * Initialize module
     */
    public function init() {
        add_action('customize_register', array($this, 'register_customizer_settings'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Hook button on single product page
        $hook = $this->get_option('hook_position', 'woocommerce_after_add_to_cart_button');
        if (empty($hook)) {
            $hook = 'woocommerce_after_add_to_cart_button';
        }

        add_action($hook, array($this, 'render_order_on_whatsapp_button'), 25);
    }

    /**
     * Register WordPress Customizer settings under plugin option optimus_bytes_woo_kit_settings
     *
     * @param \WP_Customize_Manager $wp_customize
     */
    public function register_customizer_settings($wp_customize) {
        $section_id = 'obwk_product_whatsapp_section';

        $wp_customize->add_section($section_id, array(
            'title'       => __('Product Page WhatsApp Button', 'optimus-bytes-woo-kit'),
            'description' => __('Configure the "Order on WhatsApp" button displayed on single product pages.', 'optimus-bytes-woo-kit'),
            'priority'    => 121,
        ));

        // Enable / Disable
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[product_whatsapp_enable]', array(
            'type'              => 'option',
            'default'           => true,
            'sanitize_callback' => 'wp_validate_boolean',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[product_whatsapp_enable]', array(
            'label'    => __('Enable "Order on WhatsApp" Button', 'optimus-bytes-woo-kit'),
            'section'  => $section_id,
            'type'     => 'checkbox',
        ));

        // Button Text
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[product_whatsapp_btn_text]', array(
            'type'              => 'option',
            'default'           => 'Order on WhatsApp',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[product_whatsapp_btn_text]', array(
            'label'    => __('Button Text', 'optimus-bytes-woo-kit'),
            'section'  => $section_id,
            'type'     => 'text',
        ));

        // Phone Number (fallback to global WhatsApp number if empty)
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[product_whatsapp_number]', array(
            'type'              => 'option',
            'default'           => '+91 98765 43210',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[product_whatsapp_number]', array(
            'label'       => __('WhatsApp Phone Number for Orders', 'optimus-bytes-woo-kit'),
            'description' => __('Leave as business number (e.g. +91 9876543210)', 'optimus-bytes-woo-kit'),
            'section'     => $section_id,
            'type'        => 'text',
        ));

        // Button Style
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[product_whatsapp_btn_style]', array(
            'type'              => 'option',
            'default'           => 'solid',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[product_whatsapp_btn_style]', array(
            'label'    => __('Button Style Theme', 'optimus-bytes-woo-kit'),
            'section'  => $section_id,
            'type'     => 'select',
            'choices'  => array(
                'solid'   => __('WhatsApp Green Solid (Recommended)', 'optimus-bytes-woo-kit'),
                'outline' => __('WhatsApp Green Outline', 'optimus-bytes-woo-kit'),
                'luxury'  => __('Luxury Saree Theme Accent (Gold & Green)', 'optimus-bytes-woo-kit'),
            ),
        ));

        // Button Width Layout
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[product_whatsapp_btn_width]', array(
            'type'              => 'option',
            'default'           => 'full',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[product_whatsapp_btn_width]', array(
            'label'    => __('Button Width', 'optimus-bytes-woo-kit'),
            'section'  => $section_id,
            'type'     => 'select',
            'choices'  => array(
                'full' => __('Full Width (Matches Add to Cart Width)', 'optimus-bytes-woo-kit'),
                'auto' => __('Auto Width (Inline)', 'optimus-bytes-woo-kit'),
            ),
        ));

        // Subtext Tagline
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[product_whatsapp_tagline]', array(
            'type'              => 'option',
            'default'           => '⚡ Instant chat & direct ordering with weavers / store team',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[product_whatsapp_tagline]', array(
            'label'       => __('Trust Subtext Tagline', 'optimus-bytes-woo-kit'),
            'description' => __('Small trust note below button (leave blank to hide)', 'optimus-bytes-woo-kit'),
            'section'     => $section_id,
            'type'        => 'text',
        ));

        // Greeting Message Prefix
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[product_whatsapp_greeting]', array(
            'type'              => 'option',
            'default'           => 'Hello SMV Sarees, I would like to order:',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[product_whatsapp_greeting]', array(
            'label'    => __('Message Greeting Header', 'optimus-bytes-woo-kit'),
            'section'  => $section_id,
            'type'     => 'text',
        ));

        // Show SKU Toggle
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[product_whatsapp_show_sku]', array(
            'type'              => 'option',
            'default'           => true,
            'sanitize_callback' => 'wp_validate_boolean',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[product_whatsapp_show_sku]', array(
            'label'    => __('Include Product SKU in message', 'optimus-bytes-woo-kit'),
            'section'  => $section_id,
            'type'     => 'checkbox',
        ));

        // Show Price Toggle
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[product_whatsapp_show_price]', array(
            'type'              => 'option',
            'default'           => true,
            'sanitize_callback' => 'wp_validate_boolean',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[product_whatsapp_show_price]', array(
            'label'    => __('Include Product Price in message', 'optimus-bytes-woo-kit'),
            'section'  => $section_id,
            'type'     => 'checkbox',
        ));
    }

    /**
     * Enqueue styles and dynamic scripts on Single Product pages
     */
    public function enqueue_scripts() {
        if (!function_exists('is_product') || !is_product() || !$this->is_enabled()) {
            return;
        }

        wp_enqueue_style(
            'obwk-product-whatsapp-style',
            OBWK_PLUGIN_URL . 'assets/css/product-whatsapp.css',
            array(),
            OBWK_VERSION
        );

        wp_enqueue_script(
            'obwk-product-whatsapp-script',
            OBWK_PLUGIN_URL . 'assets/js/product-whatsapp.js',
            array('jquery'),
            OBWK_VERSION,
            true
        );
    }

    /**
     * Render the "Order on WhatsApp" Button
     */
    public function render_order_on_whatsapp_button() {
        if (!$this->is_enabled()) {
            return;
        }

        global $product;
        if (!is_a($product, '\WC_Product')) {
            $product = wc_get_product(get_the_ID());
        }

        if (!$product) {
            return;
        }

        // Retrieve phone number (falls back to global whatsapp number if empty)
        $raw_number = $this->get_option('number', '');
        if (empty($raw_number)) {
            $options    = get_option(OBWK_SETTINGS_OPTION, array());
            $raw_number = isset($options['whatsapp_number']) ? $options['whatsapp_number'] : '+91 98765 43210';
        }
        $raw_number = apply_filters('obwk_product_whatsapp_phone_number', $raw_number, $product);

        $phone_digits = preg_replace('/[^0-9]/', '', $raw_number);
        if (empty($phone_digits)) {
            return;
        }

        $btn_text   = $this->get_option('btn_text', __('Order on WhatsApp', 'optimus-bytes-woo-kit'));
        $btn_style  = $this->get_option('btn_style', 'solid');
        $btn_width  = $this->get_option('btn_width', 'full');
        $tagline    = $this->get_option('tagline', __('⚡ Instant chat & direct ordering with weavers / store team', 'optimus-bytes-woo-kit'));
        $greeting   = $this->get_option('greeting', __('Hello SMV Sarees, I would like to order:', 'optimus-bytes-woo-kit'));
        $show_sku   = (bool) $this->get_option('show_sku', true);
        $show_price = (bool) $this->get_option('show_price', true);

        // Product Details
        $product_title = $product->get_name();
        $product_sku   = $product->get_sku();
        $product_url   = get_permalink($product->get_id());
        $currency_code = get_woocommerce_currency_symbol();
        $product_price = $currency_code . ' ' . $product->get_price();

        // Build default initial message (Quantity = 1)
        $message_lines = array();
        $message_lines[] = $greeting;
        $message_lines[] = "🛍️ Product: " . $product_title;

        if ($show_price && !empty($product->get_price())) {
            $message_lines[] = "💰 Price: " . $product_price;
        }

        if ($show_sku && !empty($product_sku)) {
            $message_lines[] = "🔢 SKU: " . $product_sku;
        }

        $message_lines[] = "📦 Quantity: 1";
        $message_lines[] = "🔗 Link: " . $product_url;
        $message_lines[] = "
Please let me know how to proceed with the order.";

        $initial_message = implode("
", $message_lines);
        $initial_message = apply_filters('obwk_product_whatsapp_message', $initial_message, $product);

        $initial_wa_url = 'https://wa.me/' . rawurlencode($phone_digits) . '?text=' . rawurlencode($initial_message);

        // Render HTML
        ?>
        <div class="obwk-product-whatsapp-wrapper obwk-btn-width-<?php echo esc_attr($btn_width); ?> obwk-btn-style-<?php echo esc_attr($btn_style); ?>" 
             id="obwk-product-whatsapp"
             data-phone="<?php echo esc_attr($phone_digits); ?>"
             data-product-title="<?php echo esc_attr($product_title); ?>"
             data-product-price="<?php echo esc_attr($product_price); ?>"
             data-product-sku="<?php echo esc_attr($product_sku); ?>"
             data-product-url="<?php echo esc_url($product_url); ?>"
             data-greeting="<?php echo esc_attr($greeting); ?>"
             data-show-sku="<?php echo $show_sku ? '1' : '0'; ?>"
             data-show-price="<?php echo $show_price ? '1' : '0'; ?>">
            
            <a href="<?php echo esc_url($initial_wa_url); ?>" 
               class="obwk-product-wa-btn" 
               target="_blank" 
               rel="noopener noreferrer" 
               aria-label="<?php echo esc_attr($btn_text); ?>">
                <span class="obwk-pwa-icon" aria-hidden="true">
                    <svg viewBox="0 0 448 512" width="20" height="20" fill="currentColor" aria-hidden="true" focusable="false">
                        <path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7.9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/>
                    </svg>
                </span>
                <span class="obwk-pwa-text"><?php echo esc_html($btn_text); ?></span>
            </a>

            <?php if (!empty($tagline)) : ?>
                <div class="obwk-pwa-tagline">
                    <span><?php echo esc_html($tagline); ?></span>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
