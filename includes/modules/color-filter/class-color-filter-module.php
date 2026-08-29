<?php
/**
 * Color Swatch Filter Widget & Shortcode Module
 *
 * @package OptimusBytes\WooKit\Modules\Color_Filter
 */

namespace OptimusBytes\WooKit\Modules\Color_Filter;

use OptimusBytes\WooKit\Modules\Abstract_Module;
use OptimusBytes\WooKit\Modules\Variation_Swatches\Variation_Swatches_Module;

defined('ABSPATH') || exit;

class Color_Filter_Module extends Abstract_Module {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id          = 'color_filter';
        $this->title       = __('Color Swatch Filter Widget & Shortcode', 'optimus-bytes-woo-kit');
        $this->description = __('Visual color swatch filter for shop and archive sidebars with multi-select support, product counts, and clean URLs.', 'optimus-bytes-woo-kit');
        $this->icon        = '🎯';
        $this->category    = __('Product & UX', 'optimus-bytes-woo-kit');
    }

    /**
     * Initialize module hooks
     */
    public function init() {
        add_action('customize_register', array($this, 'register_customizer_settings'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('widgets_init', array($this, 'register_widget'));

        // Shortcode support
        add_shortcode('obwk_color_filter', array($this, 'render_shortcode'));
    }

    /**
     * Register widget
     */
    public function register_widget() {
        if (class_exists('OptimusBytes\WooKit\Modules\Color_Filter\OBWK_Color_Filter_Widget')) {
            register_widget('OptimusBytes\WooKit\Modules\Color_Filter\OBWK_Color_Filter_Widget');
        }
    }

    /**
     * Register Customizer settings
     *
     * @param \WP_Customize_Manager $wp_customize
     */
    public function register_customizer_settings($wp_customize) {
        $section_id = 'obwk_color_filter_section';

        $wp_customize->add_section($section_id, array(
            'title'       => __('Color Swatch Filter Widget', 'optimus-bytes-woo-kit'),
            'description' => __('Configure visual color filter appearance for shop sidebars and archive pages.', 'optimus-bytes-woo-kit'),
            'priority'    => 124,
        ));

        // Enable / Disable
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[color_filter_enable]', array(
            'type'              => 'option',
            'default'           => true,
            'sanitize_callback' => 'wp_validate_boolean',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[color_filter_enable]', array(
            'label'    => __('Enable Color Swatch Filter', 'optimus-bytes-woo-kit'),
            'section'  => $section_id,
            'type'     => 'checkbox',
        ));

        // Default Layout
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[color_filter_layout]', array(
            'type'              => 'option',
            'default'           => 'grid',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[color_filter_layout]', array(
            'label'    => __('Default Filter Layout', 'optimus-bytes-woo-kit'),
            'section'  => $section_id,
            'type'     => 'select',
            'choices'  => array(
                'grid' => __('Visual Swatch Grid (Color Circles)', 'optimus-bytes-woo-kit'),
                'list' => __('Vertical List (Color Dot + Name + Count)', 'optimus-bytes-woo-kit'),
            ),
        ));

        // Swatch Shape
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[color_filter_shape]', array(
            'type'              => 'option',
            'default'           => 'rounded',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[color_filter_shape]', array(
            'label'    => __('Swatch Shape', 'optimus-bytes-woo-kit'),
            'section'  => $section_id,
            'type'     => 'select',
            'choices'  => array(
                'rounded' => __('Circular / Rounded (Recommended)', 'optimus-bytes-woo-kit'),
                'square'  => __('Square with Soft Corners', 'optimus-bytes-woo-kit'),
            ),
        ));

        // Show Product Count
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[color_filter_show_count]', array(
            'type'              => 'option',
            'default'           => true,
            'sanitize_callback' => 'wp_validate_boolean',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[color_filter_show_count]', array(
            'label'    => __('Show Product Count Badge', 'optimus-bytes-woo-kit'),
            'section'  => $section_id,
            'type'     => 'checkbox',
        ));

        // Show Clear Button
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[color_filter_show_clear]', array(
            'type'              => 'option',
            'default'           => true,
            'sanitize_callback' => 'wp_validate_boolean',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[color_filter_show_clear]', array(
            'label'    => __('Show "Clear Filter" Button when active', 'optimus-bytes-woo-kit'),
            'section'  => $section_id,
            'type'     => 'checkbox',
        ));
    }

    /**
     * Enqueue CSS
     */
    public function enqueue_scripts() {
        if (!function_exists('is_shop') && !function_exists('is_product_taxonomy')) {
            return;
        }

        if ((is_shop() || is_product_taxonomy() || is_product_category() || is_product_tag()) && $this->is_enabled()) {
            wp_enqueue_style(
                'obwk-color-filter-style',
                OBWK_PLUGIN_URL . 'assets/css/color-filter.css',
                array(),
                OBWK_VERSION
            );
        }
    }

    /**
     * Render Shortcode: [obwk_color_filter]
     *
     * @param array $atts
     * @return string
     */
    public function render_shortcode($atts) {
        if (!$this->is_enabled()) {
            return '';
        }

        $atts = shortcode_atts(array(
            'title'      => __('Filter by Color', 'optimus-bytes-woo-kit'),
            'attribute'  => 'pa_color',
            'layout'     => $this->get_option('layout', 'grid'),
            'shape'      => $this->get_option('shape', 'rounded'),
            'show_count' => (bool) $this->get_option('show_count', true),
            'show_clear' => (bool) $this->get_option('show_clear', true),
        ), $atts, 'obwk_color_filter');

        ob_start();
        self::render_filter_html($atts);
        return ob_get_clean();
    }

    /**
     * Helper to render Color Filter HTML
     *
     * @param array $args
     */
    public static function render_filter_html($args) {
        $attribute_name = !empty($args['attribute']) ? $args['attribute'] : 'pa_color';
        if (substr($attribute_name, 0, 3) !== 'pa_') {
            $attribute_name = 'pa_' . $attribute_name;
        }

        if (!taxonomy_exists($attribute_name)) {
            return;
        }

        $terms = get_terms(array(
            'taxonomy'   => $attribute_name,
            'hide_empty' => true,
        ));

        if (empty($terms) || is_wp_error($terms)) {
            return;
        }

        $layout     = !empty($args['layout']) ? $args['layout'] : 'grid';
        $shape      = !empty($args['shape']) ? $args['shape'] : 'rounded';
        $show_count = isset($args['show_count']) ? (bool) $args['show_count'] : true;
        $show_clear = isset($args['show_clear']) ? (bool) $args['show_clear'] : true;
        $title      = !empty($args['title']) ? $args['title'] : '';

        // Determine currently active filters from query parameter
        $param_key = 'filter_' . sanitize_title(str_replace('pa_', '', $attribute_name));
        $current_filter = isset($_GET[$param_key]) ? explode(',', sanitize_text_field(wp_unslash($_GET[$param_key]))) : array();
        $current_filter = array_map('sanitize_title', $current_filter);

        // Base URL for links
        $current_url = remove_query_arg('paged');

        ?>
        <div class="obwk-color-filter-widget obwk-filter-layout-<?php echo esc_attr($layout); ?> obwk-filter-shape-<?php echo esc_attr($shape); ?>">
            <?php if (!empty($title)) : ?>
                <h3 class="obwk-filter-title"><?php echo esc_html($title); ?></h3>
            <?php endif; ?>

            <?php if ($show_clear && !empty($current_filter)) : 
                $clear_url = remove_query_arg(array($param_key, 'query_type_' . sanitize_title(str_replace('pa_', '', $attribute_name))), $current_url);
            ?>
                <div class="obwk-filter-clear-wrap">
                    <a href="<?php echo esc_url($clear_url); ?>" class="obwk-filter-clear-btn">
                        ✕ <?php esc_html_e('Clear Color Filter', 'optimus-bytes-woo-kit'); ?>
                    </a>
                </div>
            <?php endif; ?>

            <ul class="obwk-color-filter-list">
                <?php foreach ($terms as $term) : 
                    $term_slug   = $term->slug;
                    $term_name   = $term->name;
                    $term_count  = $term->count;
                    $is_active   = in_array($term_slug, $current_filter, true);
                    $color_hex   = class_exists('OptimusBytes\WooKit\Modules\Variation_Swatches\Variation_Swatches_Module') 
                        ? Variation_Swatches_Module::resolve_color_hex($term) 
                        : '#a46d35';

                    // Build toggle URL (Multi-select)
                    $new_filter = $current_filter;
                    if ($is_active) {
                        $new_filter = array_diff($new_filter, array($term_slug));
                    } else {
                        $new_filter[] = $term_slug;
                    }

                    if (!empty($new_filter)) {
                        $link_url = add_query_arg(array(
                            $param_key => implode(',', $new_filter),
                            'query_type_' . sanitize_title(str_replace('pa_', '', $attribute_name)) => 'or',
                        ), $current_url);
                    } else {
                        $link_url = remove_query_arg(array($param_key, 'query_type_' . sanitize_title(str_replace('pa_', '', $attribute_name))), $current_url);
                    }
                ?>
                    <li class="obwk-filter-item <?php echo $is_active ? 'is-active' : ''; ?>">
                        <a href="<?php echo esc_url($link_url); ?>" 
                           class="obwk-filter-link" 
                           title="<?php echo esc_attr($term_name . ' (' . $term_count . ')'); ?>" 
                           aria-label="<?php echo esc_attr($term_name . ' (' . $term_count . ')'); ?>"
                           data-tooltip="<?php echo esc_attr($term_name . ' (' . $term_count . ')'); ?>">
                            
                            <span class="obwk-filter-swatch-dot" style="background-color: <?php echo esc_attr($color_hex); ?>;">
                                <?php if ($color_hex === '#ffffff' || $color_hex === '#fafaf9') : ?>
                                    <span class="obwk-filter-border-guide"></span>
                                <?php endif; ?>
                                <?php if ($is_active) : ?>
                                    <span class="obwk-filter-check">✓</span>
                                <?php endif; ?>
                            </span>

                            <?php if ('list' === $layout) : ?>
                                <span class="obwk-filter-label"><?php echo esc_html($term_name); ?></span>
                                <?php if ($show_count) : ?>
                                    <span class="obwk-filter-count">(<?php echo esc_html($term_count); ?>)</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }
}

if (class_exists('\WP_Widget')) {
    /**
     * Standard WordPress Widget for Color Swatch Filter
     */
    class OBWK_Color_Filter_Widget extends \WP_Widget {

        public function __construct() {
            parent::__construct(
                'obwk_color_filter_widget',
                __('Optimus: Color Swatch Filter', 'optimus-bytes-woo-kit'),
                array(
                    'description' => __('Display interactive visual color swatches to filter WooCommerce products on shop pages.', 'optimus-bytes-woo-kit'),
                )
            );
        }

        public function widget($args, $instance) {
            if (!function_exists('is_shop') || (!is_shop() && !is_product_taxonomy() && !is_product_category())) {
                return;
            }

            echo $args['before_widget'];

            $title      = !empty($instance['title']) ? $instance['title'] : __('Filter by Color', 'optimus-bytes-woo-kit');
            $attribute  = !empty($instance['attribute']) ? $instance['attribute'] : 'pa_color';
            $layout     = !empty($instance['layout']) ? $instance['layout'] : 'grid';
            $shape      = !empty($instance['shape']) ? $instance['shape'] : 'rounded';
            $show_count = isset($instance['show_count']) ? (bool) $instance['show_count'] : true;
            $show_clear = isset($instance['show_clear']) ? (bool) $instance['show_clear'] : true;

            Color_Filter_Module::render_filter_html(array(
                'title'      => $title,
                'attribute'  => $attribute,
                'layout'     => $layout,
                'shape'      => $shape,
                'show_count' => $show_count,
                'show_clear' => $show_clear,
            ));

            echo $args['after_widget'];
        }

        public function form($instance) {
            $title      = !empty($instance['title']) ? $instance['title'] : __('Filter by Color', 'optimus-bytes-woo-kit');
            $attribute  = !empty($instance['attribute']) ? $instance['attribute'] : 'pa_color';
            $layout     = !empty($instance['layout']) ? $instance['layout'] : 'grid';
            $shape      = !empty($instance['shape']) ? $instance['shape'] : 'rounded';
            $show_count = isset($instance['show_count']) ? (bool) $instance['show_count'] : true;
            $show_clear = isset($instance['show_clear']) ? (bool) $instance['show_clear'] : true;
            ?>
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_html_e('Title:', 'optimus-bytes-woo-kit'); ?></label>
                <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
            </p>
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('attribute')); ?>"><?php esc_html_e('Color Attribute Slug:', 'optimus-bytes-woo-kit'); ?></label>
                <input class="widefat" id="<?php echo esc_attr($this->get_field_id('attribute')); ?>" name="<?php echo esc_attr($this->get_field_name('attribute')); ?>" type="text" value="<?php echo esc_attr($attribute); ?>" />
                <small><?php esc_html_e('Default: pa_color', 'optimus-bytes-woo-kit'); ?></small>
            </p>
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('layout')); ?>"><?php esc_html_e('Layout Style:', 'optimus-bytes-woo-kit'); ?></label>
                <select class="widefat" id="<?php echo esc_attr($this->get_field_id('layout')); ?>" name="<?php echo esc_attr($this->get_field_name('layout')); ?>">
                    <option value="grid" <?php selected($layout, 'grid'); ?>><?php esc_html_e('Visual Grid (Color Circles)', 'optimus-bytes-woo-kit'); ?></option>
                    <option value="list" <?php selected($layout, 'list'); ?>><?php esc_html_e('Vertical List (Dots + Names)', 'optimus-bytes-woo-kit'); ?></option>
                </select>
            </p>
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('shape')); ?>"><?php esc_html_e('Shape:', 'optimus-bytes-woo-kit'); ?></label>
                <select class="widefat" id="<?php echo esc_attr($this->get_field_id('shape')); ?>" name="<?php echo esc_attr($this->get_field_name('shape')); ?>">
                    <option value="rounded" <?php selected($shape, 'rounded'); ?>><?php esc_html_e('Circular / Rounded', 'optimus-bytes-woo-kit'); ?></option>
                    <option value="square" <?php selected($shape, 'square'); ?>><?php esc_html_e('Square with Soft Corners', 'optimus-bytes-woo-kit'); ?></option>
                </select>
            </p>
            <p>
                <input class="checkbox" type="checkbox" <?php checked($show_count); ?> id="<?php echo esc_attr($this->get_field_id('show_count')); ?>" name="<?php echo esc_attr($this->get_field_name('show_count')); ?>" />
                <label for="<?php echo esc_attr($this->get_field_id('show_count')); ?>"><?php esc_html_e('Show product counts', 'optimus-bytes-woo-kit'); ?></label>
            </p>
            <p>
                <input class="checkbox" type="checkbox" <?php checked($show_clear); ?> id="<?php echo esc_attr($this->get_field_id('show_clear')); ?>" name="<?php echo esc_attr($this->get_field_name('show_clear')); ?>" />
                <label for="<?php echo esc_attr($this->get_field_id('show_clear')); ?>"><?php esc_html_e('Show "Clear Filter" button', 'optimus-bytes-woo-kit'); ?></label>
            </p>
            <?php
        }

        public function update($new_instance, $old_instance) {
            $instance = array();
            $instance['title']      = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
            $instance['attribute']  = (!empty($new_instance['attribute'])) ? sanitize_text_field($new_instance['attribute']) : 'pa_color';
            $instance['layout']     = (!empty($new_instance['layout'])) ? sanitize_text_field($new_instance['layout']) : 'grid';
            $instance['shape']      = (!empty($new_instance['shape'])) ? sanitize_text_field($new_instance['shape']) : 'rounded';
            $instance['show_count'] = isset($new_instance['show_count']) ? (bool) $new_instance['show_count'] : false;
            $instance['show_clear'] = isset($new_instance['show_clear']) ? (bool) $new_instance['show_clear'] : false;
            return $instance;
        }
    }
}
