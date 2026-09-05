<?php
/**
 * Sale Badge & Discount Percentage Module
 *
 * @package OptimusBytes\WooKit\Modules\Sale_Badge
 */

namespace OptimusBytes\WooKit\Modules\Sale_Badge;

use OptimusBytes\WooKit\Modules\Abstract_Module;

defined('ABSPATH') || exit;

class Sale_Badge_Module extends Abstract_Module {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id          = 'sale_badge';
        $this->title       = __('Sale Badge & Discount Percentage', 'optimus-bytes-woo-kit');
        $this->description = __('Replaces standard "Sale!" badge with dynamic discount percentages (e.g. -25% or 25% OFF), savings amounts, custom templates, custom shapes, and colors for simple and variable products.', 'optimus-bytes-woo-kit');
        $this->icon        = '🏷️';
        $this->category    = __('WooCommerce Shop & Products', 'optimus-bytes-woo-kit');
    }

    /**
     * Initialize module hooks
     */
    public function init() {
        // Register Customizer settings
        add_action('customize_register', array($this, 'register_customizer_settings'));

        // Only hook frontend if module is enabled
        if (!$this->is_enabled()) {
            return;
        }

        // Enqueue frontend styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));

        // Filter WooCommerce Sale Flash badge (priority 25 to run after themes)
        add_filter('woocommerce_sale_flash', array($this, 'filter_sale_badge'), 25, 3);
    }

    /**
     * Enqueue module stylesheet and custom inline styles
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            'obwk-sale-badge',
            OBWK_PLUGIN_URL . 'assets/css/sale-badge.css',
            array(),
            OBWK_VERSION
        );

        // Custom colors dynamic inline CSS
        $bg_color   = $this->get_option('bg_color', '');
        $text_color = $this->get_option('text_color', '');

        $custom_css = '';
        if (!empty($bg_color)) {
            $custom_css .= ".woocommerce span.onsale.obwk-sale-badge, .woocommerce ul.products li.product .onsale.obwk-sale-badge { background-color: " . esc_attr($bg_color) . " !important; border-color: " . esc_attr($bg_color) . " !important; } ";
        }
        if (!empty($text_color)) {
            $custom_css .= ".woocommerce span.onsale.obwk-sale-badge, .woocommerce ul.products li.product .onsale.obwk-sale-badge { color: " . esc_attr($text_color) . " !important; } ";
        }

        if (!empty($custom_css)) {
            wp_add_inline_style('obwk-sale-badge', $custom_css);
        }
    }

    /**
     * Filter the sale badge HTML
     *
     * @param string $html
     * @param \WP_Post $post
     * @param \WC_Product $product
     * @return string
     */
    public function filter_sale_badge($html, $post = null, $product = null) {
        if (!$product) {
            if (!empty($post) && function_exists('wc_get_product')) {
                $product = wc_get_product($post);
            } elseif (!empty($GLOBALS['product']) && is_a($GLOBALS['product'], 'WC_Product')) {
                $product = $GLOBALS['product'];
            }
        }

        if (!$product || !is_a($product, 'WC_Product') || !$product->is_on_sale()) {
            return $html;
        }

        // Check display locations: accurately distinguish main single product from loops
        $is_single_main = function_exists('is_product') && is_product() && (function_exists('wc_get_loop_prop') && !wc_get_loop_prop('is_shortcode')) && empty($GLOBALS['woocommerce_loop']['name']);
        $show_on_single = (bool) $this->get_option('show_single', true);
        $show_on_loop   = (bool) $this->get_option('show_loop', true);

        if ($is_single_main && !$show_on_single) {
            return $html;
        }
        if (!$is_single_main && !$show_on_loop) {
            return $html;
        }

        // Calculate discount data
        $data = $this->calculate_discount_data($product);
        $percentage   = $data['percentage'];
        $saved_amount = $data['saved_amount'];
        $has_range    = $data['has_range'];

        // Minimum discount threshold check
        $min_discount = (int) $this->get_option('min_discount', 0);
        if ($percentage < $min_discount) {
            return $html;
        }

        // Badge label formatting
        $format          = $this->get_option('format', 'percent_off');
        $variable_prefix = (bool) $this->get_option('variable_prefix', false);
        $badge_text      = '';

        switch ($format) {
            case 'compact':
                $badge_text = sprintf('-%s%%', $percentage);
                break;

            case 'percent_off':
                $badge_text = sprintf(__('%s%% OFF', 'optimus-bytes-woo-kit'), $percentage);
                break;

            case 'save_percent':
                $badge_text = sprintf(__('Save %s%%', 'optimus-bytes-woo-kit'), $percentage);
                break;

            case 'save_amount':
                $badge_text = sprintf(__('Save %s', 'optimus-bytes-woo-kit'), wp_strip_all_tags(wc_price($saved_amount)));
                break;

            case 'custom':
                $template = $this->get_option('custom_template', '{percent}% OFF');
                $badge_text = str_replace(
                    array('{percent}', '{amount}'),
                    array($percentage, wp_strip_all_tags(wc_price($saved_amount))),
                    $template
                );
                break;

            default:
                $badge_text = sprintf(__('%s%% OFF', 'optimus-bytes-woo-kit'), $percentage);
                break;
        }

        // Add "Up to " prefix if variable product with varying discounts
        if ($has_range && $variable_prefix) {
            $badge_text = __('Up to ', 'optimus-bytes-woo-kit') . $badge_text;
        }

        // Styling classes
        $shape    = $this->get_option('shape', 'rounded');
        $position = $this->get_option('position', 'top_right');

        $classes = array(
            'onsale',
            'obwk-sale-badge',
            'obwk-shape-' . sanitize_html_class($shape),
            'obwk-pos-' . sanitize_html_class($position),
        );

        return '<span class="' . esc_attr(implode(' ', $classes)) . '">' . esc_html($badge_text) . '</span>';
    }

    /**
     * Calculate discount percentage and savings for simple and variable products
     *
     * @param \WC_Product $product
     * @return array
     */
    public function calculate_discount_data($product) {
        $percentage   = 0;
        $saved_amount = 0.0;
        $has_range    = false;

        // 1. Variable Product
        if ($product->is_type('variable')) {
            $prices = $product->get_variation_prices();
            $variable_mode = $this->get_option('variable_mode', 'max');

            if (!empty($prices['regular_price']) && !empty($prices['sale_price'])) {
                $percentages = array();
                $savings     = array();

                foreach ($prices['regular_price'] as $var_id => $reg_price) {
                    $reg_val  = (float) $reg_price;
                    $sale_val = isset($prices['sale_price'][$var_id]) ? (float) $prices['sale_price'][$var_id] : 0;

                    if ($reg_val > 0 && $sale_val > 0 && $reg_val > $sale_val) {
                        $pct  = round((($reg_val - $sale_val) / $reg_val) * 100);
                        $diff = $reg_val - $sale_val;

                        $percentages[] = $pct;
                        $savings[]     = $diff;
                    }
                }

                if (!empty($percentages)) {
                    $min_pct = min($percentages);
                    $max_pct = max($percentages);
                    $has_range = ($min_pct !== $max_pct);

                    if ('min' === $variable_mode) {
                        $percentage   = $min_pct;
                        $saved_amount = !empty($savings) ? min($savings) : 0;
                    } else {
                        $percentage   = $max_pct;
                        $saved_amount = !empty($savings) ? max($savings) : 0;
                    }
                }
            }
        }
        // 2. Simple, External, and standard products
        else {
            $regular_price = (float) $product->get_regular_price();
            $sale_price    = (float) $product->get_sale_price();

            if ($regular_price > 0 && $sale_price > 0 && $regular_price > $sale_price) {
                $percentage   = round((($regular_price - $sale_price) / $regular_price) * 100);
                $saved_amount = $regular_price - $sale_price;
            }
        }

        return array(
            'percentage'   => $percentage,
            'saved_amount' => $saved_amount,
            'has_range'    => $has_range,
        );
    }

    /**
     * Register WordPress Customizer settings
     *
     * @param \WP_Customize_Manager $wp_customize
     */
    public function register_customizer_settings($wp_customize) {
        $section_id = 'obwk_sale_badge_section';

        $wp_customize->add_section($section_id, array(
            'title'       => __('Sale Badge & Discounts', 'optimus-bytes-woo-kit'),
            'description' => __('Configure dynamic discount percentages, savings badges, shapes, and colors.', 'optimus-bytes-woo-kit'),
            'priority'    => 125,
        ));

        // 1. Enable / Disable Module
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[sale_badge_enable]', array(
            'type'              => 'option',
            'default'           => true,
            'sanitize_callback' => 'wp_validate_boolean',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[sale_badge_enable]', array(
            'label'    => __('Enable Percentage Sale Badge', 'optimus-bytes-woo-kit'),
            'section'  => $section_id,
            'type'     => 'checkbox',
        ));

        // 2. Badge Display Format
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[sale_badge_format]', array(
            'type'              => 'option',
            'default'           => 'percent_off',
            'sanitize_callback' => 'sanitize_key',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[sale_badge_format]', array(
            'label'       => __('Badge Display Format', 'optimus-bytes-woo-kit'),
            'description' => __('Choose how the discount will be displayed to shoppers.', 'optimus-bytes-woo-kit'),
            'section'     => $section_id,
            'type'        => 'select',
            'choices'     => array(
                'compact'      => __('-25% (Compact Modern)', 'optimus-bytes-woo-kit'),
                'percent_off'  => __('25% OFF (Classic E-Commerce)', 'optimus-bytes-woo-kit'),
                'save_percent' => __('Save 25% (Savings Percent)', 'optimus-bytes-woo-kit'),
                'save_amount'  => __('Save ₹500 (Monetary Savings)', 'optimus-bytes-woo-kit'),
                'custom'       => __('Custom Format Template', 'optimus-bytes-woo-kit'),
            ),
        ));

        // 3. Custom Template
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[sale_badge_custom_template]', array(
            'type'              => 'option',
            'default'           => 'FLAT {percent}% OFF',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[sale_badge_custom_template]', array(
            'label'       => __('Custom Badge Template', 'optimus-bytes-woo-kit'),
            'description' => __('Use {percent} and {amount} placeholders (e.g. "FLAT {percent}% OFF").', 'optimus-bytes-woo-kit'),
            'section'     => $section_id,
            'type'        => 'text',
        ));

        // 4. Variable Products Calculation Mode
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[sale_badge_variable_mode]', array(
            'type'              => 'option',
            'default'           => 'max',
            'sanitize_callback' => 'sanitize_key',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[sale_badge_variable_mode]', array(
            'label'       => __('Variable Products Discount Mode', 'optimus-bytes-woo-kit'),
            'description' => __('For products with varying variation discounts, which percentage to show.', 'optimus-bytes-woo-kit'),
            'section'     => $section_id,
            'type'        => 'select',
            'choices'     => array(
                'max' => __('Maximum Discount (e.g. 40% OFF)', 'optimus-bytes-woo-kit'),
                'min' => __('Minimum Discount (e.g. 20% OFF)', 'optimus-bytes-woo-kit'),
            ),
        ));

        // 5. Variable Prefix "Up to"
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[sale_badge_variable_prefix]', array(
            'type'              => 'option',
            'default'           => false,
            'sanitize_callback' => 'wp_validate_boolean',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[sale_badge_variable_prefix]', array(
            'label'       => __('Add "Up to " Prefix for Variations', 'optimus-bytes-woo-kit'),
            'description' => __('Prepends "Up to " (e.g. "Up to 40% OFF") when variation discounts vary.', 'optimus-bytes-woo-kit'),
            'section'     => $section_id,
            'type'        => 'checkbox',
        ));

        // 6. Minimum Discount Threshold
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[sale_badge_min_discount]', array(
            'type'              => 'option',
            'default'           => 0,
            'sanitize_callback' => 'absint',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[sale_badge_min_discount]', array(
            'label'       => __('Minimum Discount % to Show', 'optimus-bytes-woo-kit'),
            'description' => __('Only show percentage badge if discount is at least this amount (e.g. 5). Set 0 to show all.', 'optimus-bytes-woo-kit'),
            'section'     => $section_id,
            'type'        => 'number',
            'input_attrs' => array('min' => 0, 'max' => 99, 'step' => 1),
        ));

        // 7. Display on Shop / Category Loop
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[sale_badge_show_loop]', array(
            'type'              => 'option',
            'default'           => true,
            'sanitize_callback' => 'wp_validate_boolean',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[sale_badge_show_loop]', array(
            'label'    => __('Show Badge on Shop / Category Loops', 'optimus-bytes-woo-kit'),
            'section'  => $section_id,
            'type'     => 'checkbox',
        ));

        // 8. Display on Single Product Page
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[sale_badge_show_single]', array(
            'type'              => 'option',
            'default'           => true,
            'sanitize_callback' => 'wp_validate_boolean',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[sale_badge_show_single]', array(
            'label'    => __('Show Badge on Single Product Page', 'optimus-bytes-woo-kit'),
            'section'  => $section_id,
            'type'     => 'checkbox',
        ));

        // 9. Badge Shape / Border Radius
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[sale_badge_shape]', array(
            'type'              => 'option',
            'default'           => 'rounded',
            'sanitize_callback' => 'sanitize_key',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[sale_badge_shape]', array(
            'label'    => __('Badge Shape', 'optimus-bytes-woo-kit'),
            'section'  => $section_id,
            'type'     => 'select',
            'choices'  => array(
                'rounded' => __('Soft Rounded (4px - Theme Match)', 'optimus-bytes-woo-kit'),
                'pill'    => __('Pill (Full Rounded)', 'optimus-bytes-woo-kit'),
                'square'  => __('Square (0px Sharp)', 'optimus-bytes-woo-kit'),
            ),
        ));

        // 10. Position
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[sale_badge_position]', array(
            'type'              => 'option',
            'default'           => 'top_right',
            'sanitize_callback' => 'sanitize_key',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[sale_badge_position]', array(
            'label'    => __('Badge Position', 'optimus-bytes-woo-kit'),
            'section'  => $section_id,
            'type'     => 'select',
            'choices'  => array(
                'top_right' => __('Top Right', 'optimus-bytes-woo-kit'),
                'top_left'  => __('Top Left', 'optimus-bytes-woo-kit'),
            ),
        ));

        // 11. Custom Background Color
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[sale_badge_bg_color]', array(
            'type'              => 'option',
            'default'           => '',
            'sanitize_callback' => 'sanitize_hex_color',
        ));
        $wp_customize->add_control(new \WP_Customize_Color_Control($wp_customize, OBWK_SETTINGS_OPTION . '[sale_badge_bg_color]', array(
            'label'       => __('Badge Background Color (Optional)', 'optimus-bytes-woo-kit'),
            'description' => __('Leave blank to inherit your theme accent color.', 'optimus-bytes-woo-kit'),
            'section'     => $section_id,
        )));

        // 12. Custom Text Color
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[sale_badge_text_color]', array(
            'type'              => 'option',
            'default'           => '',
            'sanitize_callback' => 'sanitize_hex_color',
        ));
        $wp_customize->add_control(new \WP_Customize_Color_Control($wp_customize, OBWK_SETTINGS_OPTION . '[sale_badge_text_color]', array(
            'label'       => __('Badge Text Color (Optional)', 'optimus-bytes-woo-kit'),
            'description' => __('Leave blank to inherit default white color.', 'optimus-bytes-woo-kit'),
            'section'     => $section_id,
        )));
    }
}
