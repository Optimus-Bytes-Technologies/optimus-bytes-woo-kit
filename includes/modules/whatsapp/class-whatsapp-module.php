<?php
/**
 * WhatsApp Floating Contact Button Module
 *
 * @package OptimusBytes\WooKit\Modules\WhatsApp
 */

namespace OptimusBytes\WooKit\Modules\WhatsApp;

use OptimusBytes\WooKit\Modules\Abstract_Module;

defined('ABSPATH') || exit;

class WhatsApp_Module extends Abstract_Module {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id          = 'whatsapp';
        $this->title       = __('Floating WhatsApp Contact Button', 'optimus-bytes-woo-kit');
        $this->description = __('Floating WhatsApp contact button on all store pages with smart message prefilling for products, cart, and general queries.', 'optimus-bytes-woo-kit');
        $this->icon        = '💬';
        $this->category    = __('Customer Support', 'optimus-bytes-woo-kit');
    }

    /**
     * Initialize module
     */
    public function init() {
        add_action('customize_register', array($this, 'register_customizer_settings'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'render_floating_button'), 25);
    }

    /**
     * Register WordPress Customizer settings under plugin option optimus_bytes_woo_kit_settings
     *
     * @param \WP_Customize_Manager $wp_customize
     */
    public function register_customizer_settings($wp_customize) {
        $section_id = 'obwk_whatsapp_section';

        $wp_customize->add_section($section_id, array(
            'title'       => __('Floating WhatsApp Button', 'optimus-bytes-woo-kit'),
            'description' => __('Configure the floating WhatsApp contact button for your store.', 'optimus-bytes-woo-kit'),
            'priority'    => 120,
        ));

        // Enable / Disable
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[whatsapp_enable]', array(
            'type'              => 'option',
            'default'           => true,
            'sanitize_callback' => 'wp_validate_boolean',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[whatsapp_enable]', array(
            'label'    => __('Enable Floating WhatsApp Button', 'optimus-bytes-woo-kit'),
            'section'  => $section_id,
            'type'     => 'checkbox',
        ));

        // Phone Number
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[whatsapp_number]', array(
            'type'              => 'option',
            'default'           => '+91 98765 43210',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[whatsapp_number]', array(
            'label'       => __('WhatsApp Phone Number', 'optimus-bytes-woo-kit'),
            'description' => __('Include country code (e.g. +91 9876543210)', 'optimus-bytes-woo-kit'),
            'section'     => $section_id,
            'type'        => 'text',
        ));

        // Button Tooltip / Label
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[whatsapp_label]', array(
            'type'              => 'option',
            'default'           => 'Chat with us',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[whatsapp_label]', array(
            'label'    => __('Button Label / Tooltip', 'optimus-bytes-woo-kit'),
            'section'  => $section_id,
            'type'     => 'text',
        ));

        // Position
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[whatsapp_position]', array(
            'type'              => 'option',
            'default'           => 'bottom-left',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[whatsapp_position]', array(
            'label'    => __('Button Position', 'optimus-bytes-woo-kit'),
            'section'  => $section_id,
            'type'     => 'select',
            'choices'  => array(
                'bottom-left'  => __('Bottom Left (Recommended)', 'optimus-bytes-woo-kit'),
                'bottom-right' => __('Bottom Right', 'optimus-bytes-woo-kit'),
            ),
        ));

        // Default Message
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[whatsapp_default_msg]', array(
            'type'              => 'option',
            'default'           => 'Hi SMV Sarees! I would like to inquire about your saree collections.',
            'sanitize_callback' => 'sanitize_textarea_field',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[whatsapp_default_msg]', array(
            'label'    => __('Default Greeting Message (General Pages)', 'optimus-bytes-woo-kit'),
            'section'  => $section_id,
            'type'     => 'textarea',
        ));
    }

    /**
     * Enqueue styles
     */
    public function enqueue_scripts() {
        if (!$this->is_enabled()) {
            return;
        }

        wp_enqueue_style(
            'obwk-whatsapp-style',
            OBWK_PLUGIN_URL . 'assets/css/whatsapp.css',
            array(),
            OBWK_VERSION
        );
    }

    /**
     * Render the floating WhatsApp button in footer
     */
    public function render_floating_button() {
        if (!$this->is_enabled()) {
            return;
        }

        $raw_number = $this->get_option('number', '+91 98765 43210');
        $raw_number = apply_filters('obwk_whatsapp_phone_number', $raw_number);

        // Extract digits only for wa.me URL
        $phone_digits = preg_replace('/[^0-9]/', '', $raw_number);
        if (empty($phone_digits)) {
            return;
        }

        $label    = $this->get_option('label', __('Chat with us', 'optimus-bytes-woo-kit'));
        $position = $this->get_option('position', 'bottom-left');

        // Dynamic context-aware messaging
        if (function_exists('is_product') && is_product()) {
            $product      = wc_get_product(get_the_ID());
            $product_name = $product ? $product->get_name() : get_the_title();
            $product_url  = get_permalink();
            $message      = sprintf(
                __('Hi SMV Sarees! I am interested in "%s" (%s). Could you please share more details?', 'optimus-bytes-woo-kit'),
                $product_name,
                $product_url
            );
        } elseif (function_exists('is_cart') && is_cart()) {
            $message = __('Hi SMV Sarees! I need some assistance with my shopping cart.', 'optimus-bytes-woo-kit');
        } elseif (function_exists('is_checkout') && is_checkout()) {
            $message = __('Hi SMV Sarees! I need assistance with checkout and payment.', 'optimus-bytes-woo-kit');
        } else {
            $default_msg = $this->get_option('default_msg', __('Hi SMV Sarees! I would like to inquire about your saree collections.', 'optimus-bytes-woo-kit'));
            $message     = $default_msg;
        }

        $message = apply_filters('obwk_whatsapp_message', $message);
        $wa_url  = 'https://wa.me/' . rawurlencode($phone_digits) . '?text=' . rawurlencode($message);

        // Output HTML
        ?>
        <div class="obwk-whatsapp-floating obwk-wa-pos-<?php echo esc_attr($position); ?>" id="obwk-whatsapp-floating">
            <a href="<?php echo esc_url($wa_url); ?>" 
               class="obwk-wa-btn" 
               target="_blank" 
               rel="noopener noreferrer" 
               aria-label="<?php esc_attr_e('Contact us on WhatsApp', 'optimus-bytes-woo-kit'); ?>"
               title="<?php echo esc_attr($label); ?>">
                <span class="obwk-wa-pulse" aria-hidden="true"></span>
                <span class="obwk-wa-icon-wrapper" aria-hidden="true">
                    <svg class="obwk-wa-icon" viewBox="0 0 448 512" width="26" height="26" fill="currentColor" aria-hidden="true" focusable="false">
                        <path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7.9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/>
                    </svg>
                </span>
                <?php if (!empty($label)) : ?>
                    <span class="obwk-wa-label">
                        <span class="obwk-wa-status-dot" aria-hidden="true"></span>
                        <span class="obwk-wa-label-text"><?php echo esc_html($label); ?></span>
                    </span>
                <?php endif; ?>
            </a>
        </div>
        <?php
    }
}
