<?php
/**
 * Tabbed Product Carousel & Grid Module for Elementor & Shortcode
 *
 * @package OptimusBytes\WooKit\Modules\Product_Tabs
 */

namespace OptimusBytes\WooKit\Modules\Product_Tabs;

use OptimusBytes\WooKit\Modules\Abstract_Module;

defined('ABSPATH') || exit;

class Product_Tabs_Module extends Abstract_Module {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id          = 'product_tabs';
        $this->title       = __('Tabbed Product Carousel (Elementor & Shortcode)', 'optimus-bytes-woo-kit');
        $this->description = __('Display curated WooCommerce collections (Best Sellers, New Arrivals, Festive Sarees, On Sale) in a high-converting tabbed carousel with instant tab switching, variation swatches, and discount badges.', 'optimus-bytes-woo-kit');
        $this->icon        = '🏷️';
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

        // Register Universal Shortcode
        add_shortcode('obwk_product_tabs', array($this, 'render_shortcode'));
    }

    /**
     * Register Custom Elementor Category
     *
     * @param \Elementor\Elements_Manager $elements_manager
     */
    public function register_elementor_categories($elements_manager) {
        $categories = $elements_manager->get_categories();
        if (!isset($categories['optimus-woo-kit'])) {
            $elements_manager->add_category(
                'optimus-woo-kit',
                array(
                    'title' => __('Optimus Woo Kit', 'optimus-bytes-woo-kit'),
                    'icon'  => 'fa fa-plug',
                )
            );
        }
    }

    /**
     * Register Elementor Widget (Elementor 3.5+)
     *
     * @param \Elementor\Widgets_Manager $widgets_manager
     */
    public function register_elementor_widgets($widgets_manager) {
        if (!$this->is_enabled() || !class_exists('\Elementor\Widget_Base')) {
            return;
        }

        require_once __DIR__ . '/widgets/class-product-tabs-widget.php';
        if (class_exists(__NAMESPACE__ . '\Widgets\Product_Tabs_Widget')) {
            $widgets_manager->register(new Widgets\Product_Tabs_Widget());
        }
    }

    /**
     * Register Elementor Widget (Legacy Elementor Fallback)
     */
    public function register_elementor_widgets_legacy() {
        // Handled via register_elementor_widgets
    }

    /**
     * Enqueue Frontend CSS and JavaScript Assets
     */
    public function enqueue_scripts() {
        if (function_exists('WC')) {
            wp_enqueue_script('wc-add-to-cart');
            wp_enqueue_script('wc-cart-fragments');
        }

        wp_register_style(
            'obwk-product-tabs-style',
            OBWK_PLUGIN_URL . 'assets/css/product-tabs.css',
            array(),
            OBWK_VERSION
        );

        wp_register_script(
            'obwk-product-tabs-script',
            OBWK_PLUGIN_URL . 'assets/js/product-tabs.js',
            array('jquery'),
            OBWK_VERSION,
            true
        );

        wp_localize_script(
            'obwk-product-tabs-script',
            'obwkProductTabs',
            array(
                'ajax_url'       => admin_url('admin-ajax.php'),
                'wc_ajax_url'    => class_exists('\WC_AJAX') ? \WC_AJAX::get_endpoint('%%endpoint%%') : '/?wc-ajax=%%endpoint%%',
                'cart_url'       => function_exists('wc_get_cart_url') ? wc_get_cart_url() : '',
                'i18n_view_cart' => esc_html__('View cart', 'woocommerce'),
            )
        );

        if ($this->is_enabled()) {
            wp_enqueue_style('obwk-product-tabs-style');
            wp_enqueue_script('obwk-product-tabs-script');
        }
    }

    /**
     * Render Shortcode: [obwk_product_tabs]
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
            'tab_style'    => 'pills',
            'limit'        => '8',
            'columns'      => '4',
            'show_badge'   => 'yes',
            'show_rating'  => 'yes',
            'show_swatches'=> 'yes',
            'show_btn'     => 'yes',
            'autoplay'     => 'yes',
            'arrows'       => 'yes',
            'dots'         => 'yes',
        ), $atts, 'obwk_product_tabs');

        wp_enqueue_style('obwk-product-tabs-style');
        wp_enqueue_script('obwk-product-tabs-script');

        require_once __DIR__ . '/widgets/class-product-tabs-widget.php';
        return Widgets\Product_Tabs_Widget::render_tabs_html($atts);
    }
}
