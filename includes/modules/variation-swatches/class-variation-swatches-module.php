<?php
/**
 * Variation Swatches for WooCommerce Module
 *
 * @package OptimusBytes\WooKit\Modules\Variation_Swatches
 */

namespace OptimusBytes\WooKit\Modules\Variation_Swatches;

use OptimusBytes\WooKit\Modules\Abstract_Module;

defined('ABSPATH') || exit;

class Variation_Swatches_Module extends Abstract_Module {

    /**
     * Built-in Color Dictionary (Maps common color names/slugs to Hex values)
     *
     * @var array
     */
    private static $color_map = array(
        'red'            => '#dc2626',
        'crimson'        => '#be123c',
        'maroon'         => '#831843',
        'wine'           => '#701a75',
        'pink'           => '#ec4899',
        'rani-pink'      => '#db2777',
        'magenta'        => '#c026d3',
        'purple'         => '#9333ea',
        'violet'         => '#7c3aed',
        'indigo'         => '#4f46e5',
        'blue'           => '#2563eb',
        'royal-blue'     => '#1d4ed8',
        'navy'           => '#1e3a8a',
        'navy-blue'      => '#1e3a8a',
        'sky-blue'       => '#0ea5e9',
        'cyan'           => '#06b6d4',
        'teal'           => '#0d9488',
        'peacock-green'  => '#047857',
        'peacock-blue'   => '#0284c7',
        'green'          => '#16a34a',
        'emerald'        => '#059669',
        'bottle-green'   => '#064e3b',
        'olive'          => '#65a30d',
        'parrot-green'   => '#84cc16',
        'yellow'         => '#eab308',
        'mustard'        => '#ca8a04',
        'gold'           => '#c5a059',
        'golden'         => '#d4af37',
        'zari-gold'      => '#c5a059',
        'silver'         => '#94a3b8',
        'orange'         => '#ea580c',
        'rust'           => '#c2410c',
        'peach'          => '#fbcfe8',
        'coral'          => '#fb7185',
        'brown'          => '#78350f',
        'coffee'         => '#451a03',
        'beige'          => '#f5f5dc',
        'cream'          => '#fef3c7',
        'off-white'      => '#fafaf9',
        'white'          => '#ffffff',
        'grey'           => '#64748b',
        'gray'           => '#64748b',
        'charcoal'       => '#334155',
        'black'          => '#0f172a',
    );

    /**
     * Constructor
     */
    public function __construct() {
        $this->id          = 'variation_swatches';
        $this->title       = __('Variation Swatches for WooCommerce', 'optimus-bytes-woo-kit');
        $this->description = __('Converts plain variation dropdowns into interactive color circles, image thumbnails, and pill button swatches with product loop and quick view support.', 'optimus-bytes-woo-kit');
        $this->icon        = '🎨';
        $this->category    = __('Product & UX', 'optimus-bytes-woo-kit');
    }

    /**
     * Initialize module hooks
     */
    public function init() {
        add_action('customize_register', array($this, 'register_customizer_settings'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Replace WooCommerce dropdown HTML with Swatches
        add_filter('woocommerce_dropdown_variation_attribute_options_html', array($this, 'render_swatches_html'), 10, 2);

        // Product Loop Swatches on Shop / Category cards
        add_action('woocommerce_after_shop_loop_item', array($this, 'render_loop_swatches'), 9);
    }

    /**
     * Register Customizer settings
     *
     * @param \WP_Customize_Manager $wp_customize
     */
    public function register_customizer_settings($wp_customize) {
        $section_id = 'obwk_variation_swatches_section';

        $wp_customize->add_section($section_id, array(
            'title'       => __('Variation Swatches', 'optimus-bytes-woo-kit'),
            'description' => __('Configure visual color circles, button badges, and swatch shapes for product pages, quick view modals, and catalog loops.', 'optimus-bytes-woo-kit'),
            'priority'    => 123,
        ));

        // Enable / Disable
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[variation_swatches_enable]', array(
            'type'              => 'option',
            'default'           => true,
            'sanitize_callback' => 'wp_validate_boolean',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[variation_swatches_enable]', array(
            'label'    => __('Enable Variation Swatches', 'optimus-bytes-woo-kit'),
            'section'  => $section_id,
            'type'     => 'checkbox',
        ));

        // Enable Loop Swatches (Shop / Archive Cards)
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[variation_swatches_loop_enable]', array(
            'type'              => 'option',
            'default'           => true,
            'sanitize_callback' => 'wp_validate_boolean',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[variation_swatches_loop_enable]', array(
            'label'       => __('Show Swatches in Product Catalog Grid', 'optimus-bytes-woo-kit'),
            'description' => __('Displays preview color swatches on shop and category product cards with image switching.', 'optimus-bytes-woo-kit'),
            'section'     => $section_id,
            'type'        => 'checkbox',
        ));

        // Swatch Shape
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[variation_swatches_shape]', array(
            'type'              => 'option',
            'default'           => 'rounded',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[variation_swatches_shape]', array(
            'label'    => __('Swatch Shape (Colors)', 'optimus-bytes-woo-kit'),
            'section'  => $section_id,
            'type'     => 'select',
            'choices'  => array(
                'rounded' => __('Circular / Rounded (Recommended)', 'optimus-bytes-woo-kit'),
                'square'  => __('Square with Soft Corners', 'optimus-bytes-woo-kit'),
            ),
        ));

        // Swatch Size
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[variation_swatches_size]', array(
            'type'              => 'option',
            'default'           => 'medium',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[variation_swatches_size]', array(
            'label'    => __('Swatch Size', 'optimus-bytes-woo-kit'),
            'section'  => $section_id,
            'type'     => 'select',
            'choices'  => array(
                'small'  => __('Small (28px)', 'optimus-bytes-woo-kit'),
                'medium' => __('Medium (34px - Standard)', 'optimus-bytes-woo-kit'),
                'large'  => __('Large (42px - Prominent)', 'optimus-bytes-woo-kit'),
            ),
        ));

        // Show Tooltips
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[variation_swatches_tooltips]', array(
            'type'              => 'option',
            'default'           => true,
            'sanitize_callback' => 'wp_validate_boolean',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[variation_swatches_tooltips]', array(
            'label'       => __('Enable Hover Tooltips', 'optimus-bytes-woo-kit'),
            'description' => __('Shows attribute name (e.g. "Royal Blue") in a tooltip when hovering over swatches.', 'optimus-bytes-woo-kit'),
            'section'     => $section_id,
            'type'        => 'checkbox',
        ));

        // Out of Stock Style
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[variation_swatches_stock_style]', array(
            'type'              => 'option',
            'default'           => 'cross_blur',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[variation_swatches_stock_style]', array(
            'label'    => __('Out of Stock Combination Style', 'optimus-bytes-woo-kit'),
            'section'  => $section_id,
            'type'     => 'select',
            'choices'  => array(
                'cross_blur' => __('Diagonal Strike-Through & Dimmed (Recommended)', 'optimus-bytes-woo-kit'),
                'dimmed'     => __('Dimmed Opacity Only', 'optimus-bytes-woo-kit'),
                'hide'       => __('Hide Unavailable Options', 'optimus-bytes-woo-kit'),
            ),
        ));
    }

    /**
     * Enqueue CSS & JS globally for product pages, loop swatches, and quick view modals
     */
    public function enqueue_scripts() {
        if (!$this->is_enabled()) {
            return;
        }

        wp_enqueue_style(
            'obwk-variation-swatches-style',
            OBWK_PLUGIN_URL . 'assets/css/variation-swatches.css',
            array(),
            OBWK_VERSION
        );

        wp_enqueue_script(
            'obwk-variation-swatches-script',
            OBWK_PLUGIN_URL . 'assets/js/variation-swatches.js',
            array('jquery', 'wp-util'),
            OBWK_VERSION,
            true
        );
    }

    /**
     * Determine swatch type based on attribute name and taxonomy
     *
     * @param string $attribute_name
     * @return string ('color', 'button', 'image')
     */
    public static function get_swatch_type($attribute_name) {
        $clean_name = strtolower(str_replace(array('pa_', '-', '_'), '', $attribute_name));

        if (in_array($clean_name, array('color', 'colour', 'colors', 'colours', 'sareecolor', 'bordercolor'), true)) {
            return 'color';
        }

        return 'button';
    }

    /**
     * Resolve hex color code from term name, slug, or term meta
     *
     * @param \WP_Term|string $term
     * @return string
     */
    public static function resolve_color_hex($term) {
        $slug = is_object($term) ? $term->slug : sanitize_title($term);
        $name = is_object($term) ? strtolower($term->name) : strtolower($term);

        // 1. Check custom term meta if stored
        if (is_object($term) && isset($term->term_id)) {
            $custom_color = get_term_meta($term->term_id, 'obwk_swatch_color', true);
            if (!empty($custom_color)) {
                return $custom_color;
            }
            $thvs_color = get_term_meta($term->term_id, 'thvs_color', true);
            if (!empty($thvs_color)) {
                return $thvs_color;
            }
        }

        // 2. Check built-in color dictionary by slug
        if (isset(self::$color_map[$slug])) {
            return self::$color_map[$slug];
        }

        // 3. Check built-in color dictionary by normalized name
        $name_key = sanitize_title($name);
        if (isset(self::$color_map[$name_key])) {
            return self::$color_map[$name_key];
        }

        // 4. Check if term name itself is a valid hex code
        if (preg_match('/^#([a-f0-9]{3}){1,2}$/i', $name)) {
            return $name;
        }

        // Default fallback (sleek neutral gold/slate)
        return '#a46d35';
    }

    /**
     * Intercept default dropdown options HTML and output visual swatches
     *
     * @param string $html
     * @param array $args
     * @return string
     */
    public function render_swatches_html($html, $args) {
        if (!$this->is_enabled()) {
            return $html;
        }

        $options          = $args['options'];
        $product          = $args['product'];
        $attribute        = $args['attribute'];
        $name             = $args['name'] ? $args['name'] : 'attribute_' . sanitize_title($attribute);
        $selected         = $args['selected'];
        $swatch_type      = self::get_swatch_type($attribute);
        $shape            = $this->get_option('shape', 'rounded');
        $size             = $this->get_option('size', 'medium');
        $enable_tooltips  = (bool) $this->get_option('tooltips', true);
        $stock_style      = $this->get_option('stock_style', 'cross_blur');

        if (empty($options) && !empty($product) && !empty($attribute)) {
            $attributes = $product->get_variation_attributes();
            $options    = isset($attributes[$attribute]) ? $attributes[$attribute] : array();
        }

        if (empty($options)) {
            return $html;
        }

        $swatches_html = '<div class="obwk-swatches-container obwk-swatches-type-' . esc_attr($swatch_type) . ' obwk-shape-' . esc_attr($shape) . ' obwk-size-' . esc_attr($size) . ' obwk-stock-' . esc_attr($stock_style) . '" data-attribute_name="' . esc_attr($name) . '">';

        if (taxonomy_exists($attribute)) {
            // Taxonomy terms
            $terms = wc_get_product_terms($product->get_id(), $attribute, array('fields' => 'all'));

            foreach ($terms as $term) {
                if (!in_array($term->slug, $options, true)) {
                    continue;
                }

                $is_selected = ($selected === $term->slug);
                $term_name   = $term->name;
                $term_slug   = $term->slug;

                if ('color' === $swatch_type) {
                    $color_hex = self::resolve_color_hex($term);
                    $swatches_html .= sprintf(
                        '<span class="obwk-swatch obwk-swatch-color %s" data-value="%s" style="background-color: %s;" role="button" tabindex="0" aria-label="%s"%s><span class="obwk-swatch-inner"></span>%s</span>',
                        $is_selected ? 'is-selected' : '',
                        esc_attr($term_slug),
                        esc_attr($color_hex),
                        esc_attr($term_name),
                        $enable_tooltips ? ' data-tooltip="' . esc_attr($term_name) . '"' : '',
                        $color_hex === '#ffffff' || $color_hex === '#fafaf9' ? '<span class="obwk-swatch-border-guide"></span>' : ''
                    );
                } else {
                    $swatches_html .= sprintf(
                        '<span class="obwk-swatch obwk-swatch-button %s" data-value="%s" role="button" tabindex="0" aria-label="%s"%s><span class="obwk-swatch-label">%s</span></span>',
                        $is_selected ? 'is-selected' : '',
                        esc_attr($term_slug),
                        esc_attr($term_name),
                        $enable_tooltips ? ' data-tooltip="' . esc_attr($term_name) . '"' : '',
                        esc_html($term_name)
                    );
                }
            }
        } else {
            // Custom product attributes (non-taxonomy)
            foreach ($options as $option) {
                $is_selected = ($selected === $option);
                $option_name = $option;

                if ('color' === $swatch_type) {
                    $color_hex = self::resolve_color_hex($option);
                    $swatches_html .= sprintf(
                        '<span class="obwk-swatch obwk-swatch-color %s" data-value="%s" style="background-color: %s;" role="button" tabindex="0" aria-label="%s"%s><span class="obwk-swatch-inner"></span></span>',
                        $is_selected ? 'is-selected' : '',
                        esc_attr($option),
                        esc_attr($color_hex),
                        esc_attr($option_name),
                        $enable_tooltips ? ' data-tooltip="' . esc_attr($option_name) . '"' : ''
                    );
                } else {
                    $swatches_html .= sprintf(
                        '<span class="obwk-swatch obwk-swatch-button %s" data-value="%s" role="button" tabindex="0" aria-label="%s"%s><span class="obwk-swatch-label">%s</span></span>',
                        $is_selected ? 'is-selected' : '',
                        esc_attr($option),
                        esc_attr($option_name),
                        $enable_tooltips ? ' data-tooltip="' . esc_attr($option_name) . '"' : '',
                        esc_html($option_name)
                    );
                }
            }
        }

        $swatches_html .= '</div>';

        // Keep native hidden <select> to ensure 100% WooCommerce JS, Quick View, and AJAX compatibility
        return $swatches_html . '<div class="obwk-hidden-select-wrap" style="display:none !important;">' . $html . '</div>';
    }

    /**
     * Render Mini Color Swatches in the WooCommerce Product Loop (Shop & Category Cards)
     */
    public function render_loop_swatches() {
        if (!$this->is_enabled() || !(bool) $this->get_option('loop_enable', true)) {
            return;
        }

        global $product;
        if (!is_a($product, '\WC_Product') || !$product->is_type('variable')) {
            return;
        }

        $attributes = $product->get_variation_attributes();
        $color_attr_key = '';

        foreach ($attributes as $key => $options) {
            if ('color' === self::get_swatch_type($key)) {
                $color_attr_key = $key;
                break;
            }
        }

        if (empty($color_attr_key) || empty($attributes[$color_attr_key])) {
            return;
        }

        $color_options = $attributes[$color_attr_key];
        $variations    = $product->get_available_variations();
        $shape         = $this->get_option('shape', 'rounded');
        $max_visible   = 5;
        $count         = 0;

        // Map variations by color slug for instant image switching
        $color_images = array();
        foreach ($variations as $var) {
            $attr_name = 'attribute_' . sanitize_title($color_attr_key);
            $var_color = isset($var['attributes'][$attr_name]) ? $var['attributes'][$attr_name] : '';
            if (!empty($var_color) && !empty($var['image']['src']) && !isset($color_images[$var_color])) {
                $color_images[$var_color] = $var['image']['src'];
            }
        }

        $main_image_id  = $product->get_image_id();
        $main_image_url = $main_image_id ? wp_get_attachment_image_url($main_image_id, 'woocommerce_thumbnail') : wc_placeholder_img_src('woocommerce_thumbnail');

        echo '<div class="obwk-loop-swatches obwk-shape-' . esc_attr($shape) . '" data-product-id="' . esc_attr($product->get_id()) . '" data-default-image="' . esc_url($main_image_url) . '">';

        foreach ($color_options as $option) {
            $count++;
            if ($count > $max_visible) {
                $remaining = count($color_options) - $max_visible;
                echo '<a href="' . esc_url(get_permalink($product->get_id())) . '" class="obwk-loop-more-badge" title="' . esc_attr__('View all colors', 'optimus-bytes-woo-kit') . '">+' . esc_html($remaining) . '</a>';
                break;
            }

            $term = taxonomy_exists($color_attr_key) ? get_term_by('slug', $option, $color_attr_key) : $option;
            $term_name = is_object($term) ? $term->name : $option;
            $term_slug = is_object($term) ? $term->slug : $option;
            $color_hex = self::resolve_color_hex($term);
            $img_src   = isset($color_images[$term_slug]) ? $color_images[$term_slug] : $main_image_url;

            echo sprintf(
                '<span class="obwk-loop-swatch" style="background-color: %s;" data-image-src="%s" data-tooltip="%s" title="%s" role="button" tabindex="0"><span class="obwk-loop-swatch-inner"></span></span>',
                esc_attr($color_hex),
                esc_url($img_src),
                esc_attr($term_name),
                esc_attr($term_name)
            );
        }

        echo '</div>';
    }
}
