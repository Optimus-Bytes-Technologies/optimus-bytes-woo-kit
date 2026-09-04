<?php
/**
 * Checkout Field Customizer & Manager Module
 *
 * @package OptimusBytes\WooKit\Modules\Checkout_Fields
 */

namespace OptimusBytes\WooKit\Modules\Checkout_Fields;

use OptimusBytes\WooKit\Modules\Abstract_Module;

defined('ABSPATH') || exit;

class Checkout_Fields_Module extends Abstract_Module {

    /**
     * Settings option key
     */
    const OPTION_KEY = 'obwk_checkout_fields_settings';

    /**
     * Admin Handler Instance
     *
     * @var Checkout_Fields_Admin|null
     */
    private $admin_handler = null;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id          = 'checkout_fields';
        $this->title       = __('Checkout Field Customizer', 'optimus-bytes-woo-kit');
        $this->description = __('Customize, reorder, show/hide, make required/optional, and add custom fields to WooCommerce billing, shipping, and order checkout sections.', 'optimus-bytes-woo-kit');
        $this->icon        = '📝';
        $this->category    = __('Conversions & Checkout', 'optimus-bytes-woo-kit');
    }

    /**
     * Get direct configuration URL in Admin
     *
     * @return string
     */
    public function get_configure_url() {
        return admin_url('admin.php?page=obwk-checkout-fields');
    }

    /**
     * Initialize module hooks
     */
    public function init() {
        // Initialize Admin UI & AJAX handlers
        if (is_admin()) {
            $this->admin_handler = new Checkout_Fields_Admin($this);
            $this->admin_handler->init();
        }

        // Only register frontend and order processing hooks if module is enabled
        if (!$this->is_enabled()) {
            return;
        }

        // Checkout & Address Fields Filters
        add_filter('woocommerce_checkout_fields', array($this, 'customize_checkout_fields'), 9999);
        add_filter('woocommerce_billing_fields', array($this, 'customize_billing_fields'), 9999);
        add_filter('woocommerce_shipping_fields', array($this, 'customize_shipping_fields'), 9999);
        add_filter('woocommerce_default_address_fields', array($this, 'customize_default_address_fields'), 9999);

        // Country Locale Filter (prevents WooCommerce address-i18n.js from resetting custom priorities)
        add_filter('woocommerce_get_country_locale', array($this, 'customize_country_locale'), 9999);

        // Ensure Order Notes wrapper outputs if there are active order fields
        add_filter('woocommerce_enable_order_notes_field', array($this, 'filter_enable_order_notes_field'), 9999);

        // Client-side DOM sorting guarantee on checkout page
        add_action('wp_footer', array($this, 'render_frontend_checkout_sort_script'), 99);

        // Custom field type rendering for 'heading' type
        add_filter('woocommerce_form_field_heading', array($this, 'render_heading_field'), 10, 4);

        // Checkout Validation
        add_action('woocommerce_checkout_process', array($this, 'validate_checkout_fields'));

        // Save Custom Fields on Order Creation (HPOS & Legacy Compatible)
        add_action('woocommerce_checkout_create_order', array($this, 'save_custom_order_fields'), 10, 2);
        add_action('woocommerce_checkout_update_order_meta', array($this, 'save_custom_order_meta_legacy'), 10, 2);

        // Display and Edit Custom Fields in Admin Order Edit Screen
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'render_admin_order_billing_fields'), 15);
        add_action('woocommerce_admin_order_data_after_shipping_address', array($this, 'render_admin_order_shipping_fields'), 15);
        add_action('woocommerce_admin_order_data_after_order_details', array($this, 'render_admin_order_additional_fields'), 15);
        add_action('woocommerce_process_shop_order_meta', array($this, 'save_admin_order_custom_fields'), 10, 1);

        // Register Gutenberg Checkout Block Additional Fields API
        add_action('woocommerce_init', array($this, 'register_gutenberg_checkout_fields'), 20);

        // Gutenberg Checkout Block CSS (handles hide/show for block fields)
        add_action('wp_head', array($this, 'render_gutenberg_block_css'), 20);

        // Display Custom Fields in Customer Thank You & My Account Order Details
        add_action('woocommerce_order_details_after_order_table', array($this, 'render_customer_order_details_fields'), 20);

        // Display Custom Fields in Transactional Emails
        add_action('woocommerce_email_after_order_table', array($this, 'render_email_custom_fields'), 20, 4);
    }

    /**
     * Get saved or default checkout fields configuration
     *
     * @param string|null $section 'billing', 'shipping', 'order', or null for all
     * @return array
     */
    public function get_fields_config($section = null) {
        $saved = get_option(self::OPTION_KEY, array());
        $defaults = $this->get_default_fields();

        $config = array(
            'billing'  => isset($saved['billing']) && is_array($saved['billing']) ? $saved['billing'] : $defaults['billing'],
            'shipping' => isset($saved['shipping']) && is_array($saved['shipping']) ? $saved['shipping'] : $defaults['shipping'],
            'order'    => isset($saved['order']) && is_array($saved['order']) ? $saved['order'] : $defaults['order'],
        );

        if ($section) {
            return isset($config[$section]) ? $config[$section] : array();
        }

        return $config;
    }

    /**
     * Get default WooCommerce checkout fields structure
     *
     * @return array
     */
    public function get_default_fields() {
        return array(
            'billing' => array(
                'billing_first_name' => array(
                    'name'          => 'billing_first_name',
                    'label'         => __('First name', 'woocommerce'),
                    'placeholder'   => '',
                    'type'          => 'text',
                    'required'      => true,
                    'enabled'       => true,
                    'class'         => 'form-row-first',
                    'priority'      => 10,
                    'custom'        => false,
                    'show_in_email' => true,
                    'show_in_order' => true,
                ),
                'billing_last_name' => array(
                    'name'          => 'billing_last_name',
                    'label'         => __('Last name', 'woocommerce'),
                    'placeholder'   => '',
                    'type'          => 'text',
                    'required'      => true,
                    'enabled'       => true,
                    'class'         => 'form-row-last',
                    'priority'      => 20,
                    'custom'        => false,
                    'show_in_email' => true,
                    'show_in_order' => true,
                ),
                'billing_company' => array(
                    'name'          => 'billing_company',
                    'label'         => __('Company name', 'woocommerce'),
                    'placeholder'   => '',
                    'type'          => 'text',
                    'required'      => false,
                    'enabled'       => true,
                    'class'         => 'form-row-wide',
                    'priority'      => 30,
                    'custom'        => false,
                    'show_in_email' => true,
                    'show_in_order' => true,
                ),
                'billing_country' => array(
                    'name'          => 'billing_country',
                    'label'         => __('Country / Region', 'woocommerce'),
                    'placeholder'   => '',
                    'type'          => 'country',
                    'required'      => true,
                    'enabled'       => true,
                    'class'         => 'form-row-wide',
                    'priority'      => 40,
                    'custom'        => false,
                    'show_in_email' => true,
                    'show_in_order' => true,
                ),
                'billing_address_1' => array(
                    'name'          => 'billing_address_1',
                    'label'         => __('Street address', 'woocommerce'),
                    'placeholder'   => __('House number and street name', 'woocommerce'),
                    'type'          => 'text',
                    'required'      => true,
                    'enabled'       => true,
                    'class'         => 'form-row-wide',
                    'priority'      => 50,
                    'custom'        => false,
                    'show_in_email' => true,
                    'show_in_order' => true,
                ),
                'billing_address_2' => array(
                    'name'          => 'billing_address_2',
                    'label'         => __('Apartment, suite, unit, etc.', 'woocommerce'),
                    'placeholder'   => __('Apartment, suite, unit, etc. (optional)', 'woocommerce'),
                    'type'          => 'text',
                    'required'      => false,
                    'enabled'       => true,
                    'class'         => 'form-row-wide',
                    'priority'      => 60,
                    'custom'        => false,
                    'show_in_email' => true,
                    'show_in_order' => true,
                ),
                'billing_city' => array(
                    'name'          => 'billing_city',
                    'label'         => __('Town / City', 'woocommerce'),
                    'placeholder'   => '',
                    'type'          => 'text',
                    'required'      => true,
                    'enabled'       => true,
                    'class'         => 'form-row-wide',
                    'priority'      => 70,
                    'custom'        => false,
                    'show_in_email' => true,
                    'show_in_order' => true,
                ),
                'billing_state' => array(
                    'name'          => 'billing_state',
                    'label'         => __('State / County', 'woocommerce'),
                    'placeholder'   => '',
                    'type'          => 'state',
                    'required'      => true,
                    'enabled'       => true,
                    'class'         => 'form-row-first',
                    'priority'      => 80,
                    'custom'        => false,
                    'show_in_email' => true,
                    'show_in_order' => true,
                ),
                'billing_postcode' => array(
                    'name'          => 'billing_postcode',
                    'label'         => __('Postcode / ZIP', 'woocommerce'),
                    'placeholder'   => '',
                    'type'          => 'text',
                    'required'      => true,
                    'enabled'       => true,
                    'class'         => 'form-row-last',
                    'priority'      => 90,
                    'custom'        => false,
                    'show_in_email' => true,
                    'show_in_order' => true,
                ),
                'billing_phone' => array(
                    'name'          => 'billing_phone',
                    'label'         => __('Phone', 'woocommerce'),
                    'placeholder'   => '',
                    'type'          => 'tel',
                    'required'      => true,
                    'enabled'       => true,
                    'class'         => 'form-row-first',
                    'priority'      => 100,
                    'custom'        => false,
                    'show_in_email' => true,
                    'show_in_order' => true,
                ),
                'billing_email' => array(
                    'name'          => 'billing_email',
                    'label'         => __('Email address', 'woocommerce'),
                    'placeholder'   => '',
                    'type'          => 'email',
                    'required'      => true,
                    'enabled'       => true,
                    'class'         => 'form-row-last',
                    'priority'      => 110,
                    'custom'        => false,
                    'show_in_email' => true,
                    'show_in_order' => true,
                ),
            ),
            'shipping' => array(
                'shipping_first_name' => array(
                    'name'          => 'shipping_first_name',
                    'label'         => __('First name', 'woocommerce'),
                    'placeholder'   => '',
                    'type'          => 'text',
                    'required'      => true,
                    'enabled'       => true,
                    'class'         => 'form-row-first',
                    'priority'      => 10,
                    'custom'        => false,
                    'show_in_email' => true,
                    'show_in_order' => true,
                ),
                'shipping_last_name' => array(
                    'name'          => 'shipping_last_name',
                    'label'         => __('Last name', 'woocommerce'),
                    'placeholder'   => '',
                    'type'          => 'text',
                    'required'      => true,
                    'enabled'       => true,
                    'class'         => 'form-row-last',
                    'priority'      => 20,
                    'custom'        => false,
                    'show_in_email' => true,
                    'show_in_order' => true,
                ),
                'shipping_company' => array(
                    'name'          => 'shipping_company',
                    'label'         => __('Company name', 'woocommerce'),
                    'placeholder'   => '',
                    'type'          => 'text',
                    'required'      => false,
                    'enabled'       => true,
                    'class'         => 'form-row-wide',
                    'priority'      => 30,
                    'custom'        => false,
                    'show_in_email' => true,
                    'show_in_order' => true,
                ),
                'shipping_country' => array(
                    'name'          => 'shipping_country',
                    'label'         => __('Country / Region', 'woocommerce'),
                    'placeholder'   => '',
                    'type'          => 'country',
                    'required'      => true,
                    'enabled'       => true,
                    'class'         => 'form-row-wide',
                    'priority'      => 40,
                    'custom'        => false,
                    'show_in_email' => true,
                    'show_in_order' => true,
                ),
                'shipping_address_1' => array(
                    'name'          => 'shipping_address_1',
                    'label'         => __('Street address', 'woocommerce'),
                    'placeholder'   => __('House number and street name', 'woocommerce'),
                    'type'          => 'text',
                    'required'      => true,
                    'enabled'       => true,
                    'class'         => 'form-row-wide',
                    'priority'      => 50,
                    'custom'        => false,
                    'show_in_email' => true,
                    'show_in_order' => true,
                ),
                'shipping_address_2' => array(
                    'name'          => 'shipping_address_2',
                    'label'         => __('Apartment, suite, unit, etc.', 'woocommerce'),
                    'placeholder'   => __('Apartment, suite, unit, etc. (optional)', 'woocommerce'),
                    'type'          => 'text',
                    'required'      => false,
                    'enabled'       => true,
                    'class'         => 'form-row-wide',
                    'priority'      => 60,
                    'custom'        => false,
                    'show_in_email' => true,
                    'show_in_order' => true,
                ),
                'shipping_city' => array(
                    'name'          => 'shipping_city',
                    'label'         => __('Town / City', 'woocommerce'),
                    'placeholder'   => '',
                    'type'          => 'text',
                    'required'      => true,
                    'enabled'       => true,
                    'class'         => 'form-row-wide',
                    'priority'      => 70,
                    'custom'        => false,
                    'show_in_email' => true,
                    'show_in_order' => true,
                ),
                'shipping_state' => array(
                    'name'          => 'shipping_state',
                    'label'         => __('State / County', 'woocommerce'),
                    'placeholder'   => '',
                    'type'          => 'state',
                    'required'      => true,
                    'enabled'       => true,
                    'class'         => 'form-row-first',
                    'priority'      => 80,
                    'custom'        => false,
                    'show_in_email' => true,
                    'show_in_order' => true,
                ),
                'shipping_postcode' => array(
                    'name'          => 'shipping_postcode',
                    'label'         => __('Postcode / ZIP', 'woocommerce'),
                    'placeholder'   => '',
                    'type'          => 'text',
                    'required'      => true,
                    'enabled'       => true,
                    'class'         => 'form-row-last',
                    'priority'      => 90,
                    'custom'        => false,
                    'show_in_email' => true,
                    'show_in_order' => true,
                ),
            ),
            'order' => array(
                'order_comments' => array(
                    'name'          => 'order_comments',
                    'label'         => __('Order notes', 'woocommerce'),
                    'placeholder'   => __('Notes about your order, e.g. special notes for delivery.', 'woocommerce'),
                    'type'          => 'textarea',
                    'required'      => false,
                    'enabled'       => true,
                    'class'         => 'form-row-wide',
                    'priority'      => 10,
                    'custom'        => false,
                    'show_in_email' => true,
                    'show_in_order' => true,
                ),
            ),
        );
    }

    /**
     * Filter all WooCommerce checkout fields
     *
     * @param array $fields
     * @return array
     */
    public function customize_checkout_fields($fields) {
        if (is_admin() && !wp_doing_ajax()) {
            return $fields;
        }

        $config = $this->get_fields_config();

        foreach (array('billing', 'shipping', 'order') as $section) {
            if (!isset($config[$section]) || !is_array($config[$section])) {
                continue;
            }

            $custom_section_fields = array();

            // Reconstruct the section fields according to saved order and configuration
            foreach ($config[$section] as $key => $field_data) {
                // If field is disabled / hidden, omit it from checkout
                if (isset($field_data['enabled']) && false === $field_data['enabled']) {
                    continue;
                }

                $existing = (isset($fields[$section][$key]) && is_array($fields[$section][$key])) ? $fields[$section][$key] : array();
                $type     = !empty($field_data['type']) ? $field_data['type'] : (isset($existing['type']) ? $existing['type'] : 'text');
                $label    = isset($field_data['label']) ? $field_data['label'] : (isset($existing['label']) ? $existing['label'] : '');
                $placeh   = isset($field_data['placeholder']) ? $field_data['placeholder'] : (isset($existing['placeholder']) ? $existing['placeholder'] : '');
                $required = isset($field_data['required']) ? (bool) $field_data['required'] : (!empty($existing['required']));
                $raw_class = !empty($field_data['class']) ? $field_data['class'] : (isset($existing['class']) ? $existing['class'] : 'form-row-wide');
                $class    = is_array($raw_class) ? array_values($raw_class) : array($raw_class);
                $priority = isset($field_data['priority']) ? (int) $field_data['priority'] : (isset($existing['priority']) ? (int) $existing['priority'] : 100);

                // Build normalized field definition
                $custom_section_fields[$key] = array_merge($existing, array(
                    'type'        => $type,
                    'label'       => $label,
                    'placeholder' => $placeh,
                    'required'    => $required,
                    'class'       => $class,
                    'priority'    => $priority,
                ));

                // Add options for select, radio, or checkbox
                if (in_array($type, array('select', 'radio'), true) && !empty($field_data['options']) && is_array($field_data['options'])) {
                    $custom_section_fields[$key]['options'] = $field_data['options'];
                }

                if (!empty($field_data['default'])) {
                    $custom_section_fields[$key]['default'] = $field_data['default'];
                }

                if (!empty($field_data['custom'])) {
                    $custom_section_fields[$key]['custom'] = true;
                }
            }

            // Sort fields by priority
            uasort($custom_section_fields, function ($a, $b) {
                $p_a = isset($a['priority']) ? (int) $a['priority'] : 100;
                $p_b = isset($b['priority']) ? (int) $b['priority'] : 100;
                return $p_a <=> $p_b;
            });

            $fields[$section] = $custom_section_fields;
        }

        return $fields;
    }

    /**
     * Filter WooCommerce billing fields
     *
     * @param array $fields
     * @return array
     */
    public function customize_billing_fields($fields) {
        $config = $this->get_fields_config('billing');
        return $this->apply_section_fields_filter($fields, $config, 'billing');
    }

    /**
     * Filter WooCommerce shipping fields
     *
     * @param array $fields
     * @return array
     */
    public function customize_shipping_fields($fields) {
        $config = $this->get_fields_config('shipping');
        return $this->apply_section_fields_filter($fields, $config, 'shipping');
    }

    /**
     * Filter Default Address Fields to ensure core address validations align with custom required settings
     *
     * @param array $fields
     * @return array
     */
    public function customize_default_address_fields($fields) {
        $billing_config = $this->get_fields_config('billing');

        foreach ($fields as $key => $field) {
            $billing_key = 'billing_' . $key;
            if (isset($billing_config[$billing_key])) {
                $b_field = $billing_config[$billing_key];
                if (isset($b_field['required'])) {
                    $fields[$key]['required'] = (bool) $b_field['required'];
                }
                if (isset($b_field['enabled']) && false === $b_field['enabled']) {
                    $fields[$key]['required'] = false;
                    $fields[$key]['hidden']   = true;
                }
                if (isset($b_field['priority'])) {
                    $fields[$key]['priority'] = (int) $b_field['priority'];
                }
                if (!empty($b_field['class'])) {
                    $fields[$key]['class'] = is_array($b_field['class']) ? array_values($b_field['class']) : array($b_field['class']);
                }
                if (isset($b_field['placeholder']) && '' !== $b_field['placeholder']) {
                    $fields[$key]['placeholder'] = $b_field['placeholder'];
                }
                if (isset($b_field['label']) && '' !== $b_field['label']) {
                    $fields[$key]['label'] = $b_field['label'];
                }
            }
        }

        return $fields;
    }

    /**
     * Customize Country Locale to ensure address-i18n.js in the browser preserves custom field priorities
     *
     * @param array $locale
     * @return array
     */
    public function customize_country_locale($locale) {
        $config = $this->get_fields_config();
        $billing = isset($config['billing']) && is_array($config['billing']) ? $config['billing'] : array();

        if (!isset($locale['default']) || !is_array($locale['default'])) {
            $locale['default'] = array();
        }

        $map = array(
            'first_name' => 'billing_first_name',
            'last_name'  => 'billing_last_name',
            'company'    => 'billing_company',
            'address_1'  => 'billing_address_1',
            'address_2'  => 'billing_address_2',
            'city'       => 'billing_city',
            'state'      => 'billing_state',
            'postcode'   => 'billing_postcode',
            'country'    => 'billing_country',
            'phone'      => 'billing_phone',
        );

        foreach ($map as $short_key => $full_key) {
            if (isset($billing[$full_key])) {
                $f        = $billing[$full_key];
                $priority = isset($f['priority']) ? (int) $f['priority'] : 100;
                $required = !empty($f['required']);
                $hidden   = isset($f['enabled']) && false === $f['enabled'];

                if (!isset($locale['default'][$short_key])) {
                    $locale['default'][$short_key] = array();
                }
                $locale['default'][$short_key]['priority'] = $priority;
                $locale['default'][$short_key]['required'] = $required;
                if ($hidden) {
                    $locale['default'][$short_key]['hidden'] = true;
                }

                // Update all country-specific overrides so they don't overwrite our custom priority
                foreach ($locale as $country_code => &$c_locale) {
                    if ('default' === $country_code || !is_array($c_locale)) {
                        continue;
                    }
                    if (isset($c_locale[$short_key]) && is_array($c_locale[$short_key])) {
                        $c_locale[$short_key]['priority'] = $priority;
                        if ($hidden) {
                            $c_locale[$short_key]['hidden'] = true;
                        }
                    }
                }
                unset($c_locale);
            }
        }

        return $locale;
    }

    /**
     * Ensure Order Notes / Additional Information section is rendered if active order fields exist
     *
     * @param bool $enabled
     * @return bool
     */
    public function filter_enable_order_notes_field($enabled) {
        $config = $this->get_fields_config('order');
        if (!empty($config) && is_array($config)) {
            foreach ($config as $f) {
                if (!isset($f['enabled']) || true === $f['enabled']) {
                    return true;
                }
            }
        }
        return $enabled;
    }

    /**
     * Render frontend checkout sorting script to ensure fields stay in their configured order in DOM
     */
    public function render_frontend_checkout_sort_script() {
        if (!function_exists('is_checkout') || !is_checkout()) {
            return;
        }
        ?>
        <script type="text/javascript">
        jQuery(function($) {
            function obwkEnforceFieldSort() {
                $('.woocommerce-billing-fields__field-wrapper, .woocommerce-shipping-fields__field-wrapper, .woocommerce-additional-fields__field-wrapper').each(function() {
                    var $wrapper = $(this);
                    var $rows = $wrapper.children('.form-row');
                    if ($rows.length > 1) {
                        $rows.sort(function(a, b) {
                            var pA = parseInt($(a).attr('data-priority') || $(a).data('priority') || 100, 10);
                            var pB = parseInt($(b).attr('data-priority') || $(b).data('priority') || 100, 10);
                            return pA - pB;
                        });
                        $rows.detach().appendTo($wrapper);
                    }
                });
            }
            $(document).ready(function() {
                obwkEnforceFieldSort();
                setTimeout(obwkEnforceFieldSort, 50);
                setTimeout(obwkEnforceFieldSort, 200);
                setTimeout(obwkEnforceFieldSort, 600);
            });
            $(document.body).on('country_to_state_changed updated_checkout', function() {
                setTimeout(obwkEnforceFieldSort, 20);
            });
        });
        </script>
        <?php
    }

    /**
     * Helper to apply field filters to address sections
     *
     * @param array $fields
     * @param array $config
     * @param string $prefix
     * @return array
     */
    private function apply_section_fields_filter($fields, $config, $prefix) {
        if (!is_array($fields)) {
            $fields = array();
        }
        if (empty($config) || !is_array($config)) {
            return $fields;
        }

        $result = array();

        foreach ($config as $key => $field_data) {
            // If field is disabled, exclude it
            if (isset($field_data['enabled']) && false === $field_data['enabled']) {
                continue;
            }

            $existing = (isset($fields[$key]) && is_array($fields[$key])) ? $fields[$key] : array();

            $required = isset($field_data['required']) ? (bool) $field_data['required'] : (!empty($existing['required']));
            $raw_class = !empty($field_data['class']) ? $field_data['class'] : (isset($existing['class']) ? $existing['class'] : 'form-row-wide');
            $class    = is_array($raw_class) ? array_values($raw_class) : array($raw_class);
            $priority = isset($field_data['priority']) ? (int) $field_data['priority'] : (isset($existing['priority']) ? (int) $existing['priority'] : 100);

            $result[$key] = array_merge($existing, array(
                'label'       => isset($field_data['label']) ? $field_data['label'] : (isset($existing['label']) ? $existing['label'] : ''),
                'placeholder' => isset($field_data['placeholder']) ? $field_data['placeholder'] : (isset($existing['placeholder']) ? $existing['placeholder'] : ''),
                'required'    => $required,
                'class'       => $class,
                'priority'    => $priority,
            ));

            if (!empty($field_data['type'])) {
                $result[$key]['type'] = $field_data['type'];
            }
            if (!empty($field_data['options']) && is_array($field_data['options'])) {
                $result[$key]['options'] = $field_data['options'];
            }
            if (!empty($field_data['default'])) {
                $result[$key]['default'] = $field_data['default'];
            }
        }

        uasort($result, function ($a, $b) {
            $p_a = isset($a['priority']) ? (int) $a['priority'] : 100;
            $p_b = isset($b['priority']) ? (int) $b['priority'] : 100;
            return $p_a <=> $p_b;
        });

        return $result;
    }

    /**
     * Custom field renderer for 'heading' field type
     *
     * @param string $field
     * @param string $key
     * @param array $args
     * @param string $value
     * @return string
     */
    public function render_heading_field($field, $key, $args, $value) {
        $label = !empty($args['label']) ? $args['label'] : '';
        $desc  = !empty($args['description']) ? '<p class="obwk-heading-desc">' . esc_html($args['description']) . '</p>' : '';
        return '<div class="form-row form-row-wide obwk-checkout-heading-wrap" id="' . esc_attr($key) . '_field"><h3 class="obwk-checkout-heading">' . esc_html($label) . '</h3>' . $desc . '</div>';
    }

    /**
     * Validate Custom and Core Checkout Fields
     */
    public function validate_checkout_fields() {
        $config = $this->get_fields_config();
        $ship_to_different = !empty($_POST['ship_to_different_address']);

        foreach (array('billing', 'shipping', 'order') as $section) {
            // Skip shipping validation if not shipping to different address
            if ('shipping' === $section && !$ship_to_different && !WC()->cart->needs_shipping_address()) {
                continue;
            }

            if (empty($config[$section])) {
                continue;
            }

            foreach ($config[$section] as $key => $field) {
                // If disabled, skip validation
                if (isset($field['enabled']) && false === $field['enabled']) {
                    continue;
                }

                // Heading type requires no input validation
                if (isset($field['type']) && 'heading' === $field['type']) {
                    continue;
                }

                $value = isset($_POST[$key]) ? trim(wp_unslash($_POST[$key])) : '';
                $label = !empty($field['label']) ? $field['label'] : $key;

                // Required check
                if (!empty($field['required']) && '' === $value) {
                    wc_add_notice(
                        sprintf(__('<strong>%s</strong> is a required field.', 'optimus-bytes-woo-kit'), esc_html($label)),
                        'error'
                    );
                    continue;
                }

                // Type-specific validations if value is provided
                if ('' !== $value) {
                    if (isset($field['type']) && 'email' === $field['type'] && !is_email($value)) {
                        wc_add_notice(
                            sprintf(__('Please enter a valid email address for <strong>%s</strong>.', 'optimus-bytes-woo-kit'), esc_html($label)),
                            'error'
                        );
                    }
                }
            }
        }
    }

    /**
     * Save Custom Checkout Fields to Order (HPOS Compatible)
     *
     * @param \WC_Order $order
     * @param array $data
     */
    public function save_custom_order_fields($order, $data) {
        $config = $this->get_fields_config();

        foreach (array('billing', 'shipping', 'order') as $section) {
            if (empty($config[$section])) {
                continue;
            }

            foreach ($config[$section] as $key => $field) {
                // Only save custom fields (core WooCommerce fields are handled natively)
                if (empty($field['custom'])) {
                    continue;
                }

                if (isset($_POST[$key])) {
                    $raw_value = wp_unslash($_POST[$key]);
                    $sanitized = $this->sanitize_field_value($raw_value, isset($field['type']) ? $field['type'] : 'text');

                    // Save as order metadata (HPOS compatible)
                    $order->update_meta_data('_' . $key, $sanitized);
                    $order->update_meta_data($key, $sanitized);
                }
            }
        }
    }

    /**
     * Legacy Hook fallback to save order meta
     *
     * @param int $order_id
     * @param array $posted
     */
    public function save_custom_order_meta_legacy($order_id, $posted) {
        $order = wc_get_order($order_id);
        if ($order && is_a($order, 'WC_Order')) {
            $this->save_custom_order_fields($order, $posted);
            $order->save();
        }
    }

    /**
     * Sanitize Field Value based on type
     *
     * @param mixed $value
     * @param string $type
     * @return mixed
     */
    private function sanitize_field_value($value, $type) {
        switch ($type) {
            case 'textarea':
                return sanitize_textarea_field($value);
            case 'email':
                return sanitize_email($value);
            case 'number':
                return is_numeric($value) ? $value : wc_clean($value);
            case 'checkbox':
                return !empty($value) ? '1' : '0';
            case 'text':
            case 'tel':
            case 'date':
            case 'time':
            case 'select':
            case 'radio':
            default:
                return is_array($value) ? array_map('sanitize_text_field', $value) : sanitize_text_field($value);
        }
    }

    /**
     * Render Custom Billing Fields in Admin Order Edit Screen
     *
     * @param \WC_Order $order
     */
    public function render_admin_order_billing_fields($order) {
        $this->render_admin_order_section_fields($order, 'billing', __('Custom Billing Fields', 'optimus-bytes-woo-kit'));
    }

    /**
     * Render Custom Shipping Fields in Admin Order Edit Screen
     *
     * @param \WC_Order $order
     */
    public function render_admin_order_shipping_fields($order) {
        $this->render_admin_order_section_fields($order, 'shipping', __('Custom Shipping Fields', 'optimus-bytes-woo-kit'));
    }

    /**
     * Render Custom Order / Additional Fields in Admin Order Edit Screen
     *
     * @param \WC_Order $order
     */
    public function render_admin_order_additional_fields($order) {
        $this->render_admin_order_section_fields($order, 'order', __('Additional Order Details', 'optimus-bytes-woo-kit'));
    }

    /**
     * Helper to render editable custom fields on Admin Order Edit page
     *
     * @param \WC_Order $order
     * @param string $section
     * @param string $section_title
     */
    private function render_admin_order_section_fields($order, $section, $section_title) {
        $config = $this->get_fields_config($section);
        if (empty($config)) {
            return;
        }

        $custom_fields = array_filter($config, function ($f) {
            return !empty($f['custom']) && (empty($f['type']) || 'heading' !== $f['type']);
        });

        if (empty($custom_fields)) {
            return;
        }

        echo '<div class="obwk-admin-order-custom-fields" style="margin-top:16px; padding:12px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px;">';
        echo '<h4 style="margin:0 0 10px 0; font-size:13px; color:#1e293b; font-weight:600;">' . esc_html($section_title) . '</h4>';

        wp_nonce_field('obwk_save_order_fields', 'obwk_order_fields_nonce');

        foreach ($custom_fields as $key => $field) {
            $label = !empty($field['label']) ? $field['label'] : $key;
            $val   = $order->get_meta('_' . $key);
            if ('' === $val || null === $val) {
                $val = $order->get_meta($key);
            }

            echo '<div style="margin-bottom:8px;">';
            echo '<label style="display:block; font-weight:600; font-size:12px; margin-bottom:3px; color:#475569;">' . esc_html($label) . ':</label>';

            if (isset($field['type']) && 'textarea' === $field['type']) {
                echo '<textarea name="' . esc_attr($key) . '" style="width:100%; min-height:60px;" class="input-text">' . esc_textarea($val) . '</textarea>';
            } else {
                echo '<input type="text" name="' . esc_attr($key) . '" value="' . esc_attr($val) . '" style="width:100%;" class="input-text" />';
            }

            echo '</div>';
        }

        echo '</div>';
    }

    /**
     * Save Edited Custom Fields from Admin Order Edit Screen
     *
     * @param int $order_id
     */
    public function save_admin_order_custom_fields($order_id) {
        if (!isset($_POST['obwk_order_fields_nonce']) || !wp_verify_nonce($_POST['obwk_order_fields_nonce'], 'obwk_save_order_fields')) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $config = $this->get_fields_config();

        foreach (array('billing', 'shipping', 'order') as $section) {
            if (empty($config[$section])) {
                continue;
            }

            foreach ($config[$section] as $key => $field) {
                if (empty($field['custom'])) {
                    continue;
                }

                if (isset($_POST[$key])) {
                    $sanitized = $this->sanitize_field_value(wp_unslash($_POST[$key]), isset($field['type']) ? $field['type'] : 'text');
                    $order->update_meta_data('_' . $key, $sanitized);
                    $order->update_meta_data($key, $sanitized);
                }
            }
        }

        $order->save();
    }

    /**
     * Display Custom Fields on Customer Thank You & My Account View Order Pages
     *
     * @param \WC_Order $order
     */
    public function render_customer_order_details_fields($order) {
        if (!$order) {
            return;
        }

        $config = $this->get_fields_config();
        $fields_to_show = array();

        foreach (array('billing', 'shipping', 'order') as $section) {
            if (empty($config[$section])) {
                continue;
            }

            foreach ($config[$section] as $key => $field) {
                // Check if configured to show in order details
                if (empty($field['custom']) || empty($field['show_in_order']) || 'heading' === (isset($field['type']) ? $field['type'] : '')) {
                    continue;
                }

                $val = $order->get_meta('_' . $key);
                if ('' === $val || null === $val) {
                    $val = $order->get_meta($key);
                }

                if ('' !== $val && null !== $val) {
                    $label = !empty($field['label']) ? $field['label'] : $key;
                    $display_val = $val;
                    if ('checkbox' === (isset($field['type']) ? $field['type'] : '')) {
                        $display_val = ('1' === (string)$val || 'yes' === (string)$val) ? __('Yes', 'optimus-bytes-woo-kit') : __('No', 'optimus-bytes-woo-kit');
                    }
                    $fields_to_show[$label] = $display_val;
                }
            }
        }

        if (empty($fields_to_show)) {
            return;
        }

        echo '<div class="obwk-customer-order-custom-details" style="margin:24px 0; padding:16px 20px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px;">';
        echo '<h2 class="woocommerce-column__title" style="font-size:18px; margin-bottom:12px; font-weight:600;">' . esc_html__('Additional Information', 'optimus-bytes-woo-kit') . '</h2>';
        echo '<table class="woocommerce-table" style="width:100%; border-collapse:collapse;">';
        foreach ($fields_to_show as $label => $val) {
            echo '<tr>';
            echo '<th style="text-align:left; padding:8px 0; border-bottom:1px solid #e5e7eb; font-weight:600; width:40%;">' . esc_html($label) . ':</th>';
            echo '<td style="text-align:left; padding:8px 0; border-bottom:1px solid #e5e7eb;">' . esc_html($val) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        echo '</div>';
    }

    /**
     * Display Custom Fields in Customer & Admin Transactional Emails
     *
     * @param \WC_Order $order
     * @param bool $sent_to_admin
     * @param bool $plain_text
     * @param \WC_Email $email
     */
    public function render_email_custom_fields($order, $sent_to_admin, $plain_text, $email) {
        if (!$order) {
            return;
        }

        $config = $this->get_fields_config();
        $fields_to_show = array();

        foreach (array('billing', 'shipping', 'order') as $section) {
            if (empty($config[$section])) {
                continue;
            }

            foreach ($config[$section] as $key => $field) {
                if (empty($field['custom']) || empty($field['show_in_email']) || 'heading' === (isset($field['type']) ? $field['type'] : '')) {
                    continue;
                }

                $val = $order->get_meta('_' . $key);
                if ('' === $val || null === $val) {
                    $val = $order->get_meta($key);
                }

                if ('' !== $val && null !== $val) {
                    $label = !empty($field['label']) ? $field['label'] : $key;
                    $display_val = $val;
                    if ('checkbox' === (isset($field['type']) ? $field['type'] : '')) {
                        $display_val = ('1' === (string)$val || 'yes' === (string)$val) ? __('Yes', 'optimus-bytes-woo-kit') : __('No', 'optimus-bytes-woo-kit');
                    }
                    $fields_to_show[$label] = $display_val;
                }
            }
        }

        if (empty($fields_to_show)) {
            return;
        }

        if ($plain_text) {
            echo "\n" . strtoupper(__('Additional Information', 'optimus-bytes-woo-kit')) . "\n\n";
            foreach ($fields_to_show as $label => $val) {
                echo esc_html($label) . ': ' . esc_html($val) . "\n";
            }
            echo "\n";
        } else {
            echo '<div style="margin:20px 0; padding:15px; border:1px solid #e0e0e0; border-radius:6px; background-color:#fcfcfc;">';
            echo '<h3 style="margin:0 0 10px 0; color:#333333; font-size:15px; font-weight:600;">' . esc_html__('Additional Information', 'optimus-bytes-woo-kit') . '</h3>';
            echo '<table cellspacing="0" cellpadding="6" style="width:100%; border-collapse:collapse;">';
            foreach ($fields_to_show as $label => $val) {
                echo '<tr>';
                echo '<th style="text-align:left; border-bottom:1px solid #eeeeee; font-weight:bold; color:#555; width:45%;">' . esc_html($label) . ':</th>';
                echo '<td style="text-align:left; border-bottom:1px solid #eeeeee; color:#333;">' . esc_html($val) . '</td>';
                echo '</tr>';
            }
        }
    }

    /**
     * Register Custom Fields with WooCommerce Gutenberg Checkout Block Extensibility API
     */
    public function register_gutenberg_checkout_fields() {
        if (!function_exists('woocommerce_register_additional_checkout_field')) {
            return;
        }

        $config = $this->get_fields_config();

        foreach (array('billing', 'shipping', 'order') as $section) {
            if (empty($config[$section]) || !is_array($config[$section])) {
                continue;
            }

            foreach ($config[$section] as $key => $field) {
                // Only register user-created custom fields
                if (empty($field['custom'])) {
                    continue;
                }
                if (isset($field['enabled']) && false === $field['enabled']) {
                    continue;
                }

                // Supported locations in WC Blocks: 'address', 'order', 'contact'
                $location = ('order' === $section) ? 'order' : 'address';

                $raw_type = isset($field['type']) ? $field['type'] : 'text';
                $type = 'text';
                if ('checkbox' === $raw_type) {
                    $type = 'checkbox';
                } elseif ('select' === $raw_type || 'radio' === $raw_type) {
                    $type = 'select';
                }

                $clean_name = str_replace(array('billing_', 'shipping_', 'order_'), '', $key);
                $field_id   = 'obwk/' . sanitize_key($clean_name);

                $args = array(
                    'id'       => $field_id,
                    'label'    => !empty($field['label']) ? $field['label'] : $key,
                    'location' => $location,
                    'type'     => $type,
                    'required' => !empty($field['required']),
                );

                if (!empty($field['placeholder'])) {
                    $args['placeholder'] = $field['placeholder'];
                }

                if ('select' === $type && !empty($field['options']) && is_array($field['options'])) {
                    $options_list = array();
                    foreach ($field['options'] as $opt_val => $opt_label) {
                        $options_list[] = array(
                            'value' => (string) $opt_val,
                            'label' => (string) $opt_label,
                        );
                    }
                    $args['options'] = $options_list;
                }

                try {
                    woocommerce_register_additional_checkout_field($args);
                } catch (\Exception $e) {
                    // Field might already be registered
                }
            }
        }
    }

    /**
     * Render Gutenberg Checkout Block CSS to hide fields marked as disabled
     */
    public function render_gutenberg_block_css() {
        if (!function_exists('is_checkout') || !is_checkout()) {
            return;
        }

        $config = $this->get_fields_config();
        $hidden_selectors = array();

        // Check billing company
        if (isset($config['billing']['billing_company']['enabled']) && false === $config['billing']['billing_company']['enabled']) {
            $hidden_selectors[] = '.wc-block-components-address-form__company';
        }
        // Check billing address 2
        if (isset($config['billing']['billing_address_2']['enabled']) && false === $config['billing']['billing_address_2']['enabled']) {
            $hidden_selectors[] = '.wc-block-components-address-form__address_2, .wc-block-components-address-form__address-2';
        }
        // Check order notes
        if (isset($config['order']['order_comments']['enabled']) && false === $config['order']['order_comments']['enabled']) {
            $hidden_selectors[] = '.wc-block-checkout__order-notes, .wc-block-components-checkout-order-notes';
        }

        if (!empty($hidden_selectors)) {
            echo '<style id="obwk-gutenberg-checkout-css">' . implode(', ', $hidden_selectors) . ' { display: none !important; }</style>' . "\n";
        }
    }
}
