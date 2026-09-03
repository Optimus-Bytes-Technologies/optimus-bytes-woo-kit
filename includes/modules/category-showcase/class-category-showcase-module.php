<?php
/**
 * WooCommerce Category Showcase Module (Slider & Grid for Elementor & Shortcode)
 *
 * @package OptimusBytes\WooKit\Modules\Category_Showcase
 */

namespace OptimusBytes\WooKit\Modules\Category_Showcase;

use OptimusBytes\WooKit\Modules\Abstract_Module;

defined('ABSPATH') || exit;

class Category_Showcase_Module extends Abstract_Module {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id          = 'category_showcase';
        $this->title       = __('Category Slider & Grid (Elementor & Shortcode)', 'optimus-bytes-woo-kit');
        $this->description = __('Showcase WooCommerce product categories in a modern responsive carousel slider or grid with luxury skins, product counts, custom ordering, and Elementor live preview.', 'optimus-bytes-woo-kit');
        $this->icon        = '🗂️';
        $this->category    = __('Product & UX', 'optimus-bytes-woo-kit');
    }

    /**
     * Initialize Module Hooks
     */
    public function init() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Register Elementor Category and Widget
        add_action('elementor/elements/categories_registered', array($this, 'register_elementor_categories'));
        add_action('elementor/widgets/register', array($this, 'register_elementor_widgets'));
        add_action('elementor/widgets/widgets_registered', array($this, 'register_elementor_widgets_legacy'));

        // Register Shortcode
        add_shortcode('obwk_categories', array($this, 'render_shortcode'));
    }

    /**
     * Register Custom Elementor Widget Category
     *
     * @param \Elementor\Elements_Manager $elements_manager
     */
    public function register_elementor_categories($elements_manager) {
        $elements_manager->add_category(
            'optimus-woo-kit',
            array(
                'title' => __('Optimus Woo Kit', 'optimus-bytes-woo-kit'),
                'icon'  => 'fa fa-plug',
            )
        );
    }

    /**
     * Register Elementor Widget (Elementor 3.5+)
     *
     * @param \Elementor\Widgets_Manager $widgets_manager
     */
    public function register_elementor_widgets($widgets_manager) {
        if (!$this->is_enabled()) {
            return;
        }

        require_once __DIR__ . '/widgets/class-category-slider-grid-widget.php';
        $widgets_manager->register(new Widgets\Category_Slider_Grid_Widget());
    }

    /**
     * Register Elementor Widget (Legacy Elementor Fallback)
     */
    public function register_elementor_widgets_legacy() {
        if (!$this->is_enabled()) {
            return;
        }

        if (class_exists('\Elementor\Plugin') && did_action('elementor/loaded')) {
            require_once __DIR__ . '/widgets/class-category-slider-grid-widget.php';
            if (class_exists(__NAMESPACE__ . '\Widgets\Category_Slider_Grid_Widget')) {
                \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new Widgets\Category_Slider_Grid_Widget());
            }
        }
    }

    /**
     * Enqueue Frontend CSS and JavaScript
     */
    public function enqueue_scripts() {
        // Enqueue Swiper from Elementor/WordPress if available, or lightweight bundle
        if (!wp_script_is('swiper', 'enqueued') && !wp_script_is('swiper', 'registered')) {
            wp_register_script(
                'swiper',
                OBWK_PLUGIN_URL . 'assets/js/vendor/swiper-bundle.min.js',
                array(),
                '11.0.0',
                true
            );
        }

        wp_register_style(
            'obwk-category-showcase-style',
            OBWK_PLUGIN_URL . 'assets/css/category-showcase.css',
            array(),
            OBWK_VERSION
        );

        wp_register_script(
            'obwk-category-showcase-script',
            OBWK_PLUGIN_URL . 'assets/js/category-showcase.js',
            array('jquery', 'swiper'),
            OBWK_VERSION,
            true
        );

        // Auto enqueue when enabled
        if ($this->is_enabled()) {
            wp_enqueue_style('obwk-category-showcase-style');
            wp_enqueue_script('obwk-category-showcase-script');
        }
    }

    /**
     * Render Shortcode: [obwk_categories]
     *
     * @param array $atts
     * @return string
     */
    public function render_shortcode($atts) {
        if (!$this->is_enabled()) {
            return '';
        }

        $atts = shortcode_atts(array(
            'layout'       => 'slider',
            'skin'         => 'overlay',
            'columns'      => '4',
            'limit'        => '8',
            'source'       => 'all',
            'parent'       => '0',
            'orderby'      => 'menu_order',
            'order'        => 'ASC',
            'hide_empty'   => 'yes',
            'show_count'   => 'yes',
            'show_button'  => 'yes',
            'button_text'  => __('Explore', 'optimus-bytes-woo-kit'),
            'aspect_ratio' => 'ratio_3_4',
            'autoplay'     => 'yes',
            'loop'         => 'yes',
            'arrows'       => 'yes',
            'dots'         => 'yes',
        ), $atts, 'obwk_categories');

        wp_enqueue_style('obwk-category-showcase-style');
        wp_enqueue_script('obwk-category-showcase-script');

        require_once __DIR__ . '/widgets/class-category-slider-grid-widget.php';
        return Widgets\Category_Slider_Grid_Widget::render_showcase_html($atts);
    }
}
