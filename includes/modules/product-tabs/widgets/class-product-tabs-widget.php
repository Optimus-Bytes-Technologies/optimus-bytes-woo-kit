<?php
/**
 * Elementor Widget: Tabbed Product Carousel & Grid
 *
 * @package OptimusBytes\WooKit\Modules\Product_Tabs\Widgets
 */

namespace OptimusBytes\WooKit\Modules\Product_Tabs\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Background;

defined('ABSPATH') || exit;

if (!class_exists('\Elementor\Widget_Base')) {
    return;
}

class Product_Tabs_Widget extends Widget_Base {

    /**
     * Get Widget Name
     *
     * @return string
     */
    public function get_name() {
        return 'obwk_product_tabs';
    }

    /**
     * Get Widget Title
     *
     * @return string
     */
    public function get_title() {
        return __('Tabbed Product Carousel', 'optimus-bytes-woo-kit');
    }

    /**
     * Get Widget Icon
     *
     * @return string
     */
    public function get_icon() {
        return 'eicon-tabs';
    }

    /**
     * Get Widget Categories
     *
     * @return array
     */
    public function get_categories() {
        return array('optimus-woo-kit', 'woocommerce-elements', 'general');
    }

    /**
     * Get Widget Keywords
     *
     * @return array
     */
    public function get_keywords() {
        return array('product', 'products', 'tabs', 'tabbed', 'slider', 'carousel', 'grid', 'best seller', 'new arrivals', 'woocommerce', 'saree');
    }

    /**
     * Get Product Categories for Select Controls
     *
     * @return array
     */
    private function get_product_categories_options() {
        $options = array();
        if (!taxonomy_exists('product_cat')) {
            return $options;
        }

        $terms = get_terms(array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
        ));

        if (!empty($terms) && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                $options[$term->term_id] = $term->name . ' (' . $term->count . ')';
            }
        }
        return $options;
    }

    /**
     * Register Widget Controls
     */
    protected function register_controls() {

        // ==========================================
        // TAB: CONTENT - Tabs Management
        // ==========================================
        $this->start_controls_section(
            'section_tabs',
            array(
                'label' => __('Product Tabs', 'optimus-bytes-woo-kit'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            )
        );

        $repeater = new Repeater();

        $repeater->add_control(
            'tab_title',
            array(
                'label'       => __('Tab Label / Title', 'optimus-bytes-woo-kit'),
                'type'        => Controls_Manager::TEXT,
                'default'     => __('🔥 Best Sellers', 'optimus-bytes-woo-kit'),
                'label_block' => true,
            )
        );

        $repeater->add_control(
            'product_source',
            array(
                'label'   => __('Product Source', 'optimus-bytes-woo-kit'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'best_selling',
                'options' => array(
                    'best_selling' => __('🔥 Best Sellers (Total Sales / Popularity)', 'optimus-bytes-woo-kit'),
                    'new_arrivals' => __('✨ New Arrivals (Latest Products)', 'optimus-bytes-woo-kit'),
                    'featured'     => __('⭐ Featured Products', 'optimus-bytes-woo-kit'),
                    'on_sale'      => __('🏷️ On Sale / Special Offers', 'optimus-bytes-woo-kit'),
                    'top_rated'    => __('🏆 Top Rated (Highest Customer Reviews)', 'optimus-bytes-woo-kit'),
                    'category'     => __('📂 Specific Product Category', 'optimus-bytes-woo-kit'),
                ),
            )
        );

        $repeater->add_control(
            'category_id',
            array(
                'label'       => __('Select Category', 'optimus-bytes-woo-kit'),
                'type'        => Controls_Manager::SELECT,
                'options'     => $this->get_product_categories_options(),
                'condition'   => array(
                    'product_source' => 'category',
                ),
                'label_block' => true,
            )
        );

        $repeater->add_control(
            'limit',
            array(
                'label'   => __('Product Limit', 'optimus-bytes-woo-kit'),
                'type'    => Controls_Manager::NUMBER,
                'default' => 8,
                'min'     => 1,
                'max'     => 30,
            )
        );

        $repeater->add_control(
            'orderby',
            array(
                'label'   => __('Order By', 'optimus-bytes-woo-kit'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'default',
                'options' => array(
                    'default'    => __('Default for Source', 'optimus-bytes-woo-kit'),
                    'date'       => __('Date (Newest)', 'optimus-bytes-woo-kit'),
                    'price'      => __('Price (Low to High)', 'optimus-bytes-woo-kit'),
                    'price_desc' => __('Price (High to Low)', 'optimus-bytes-woo-kit'),
                    'rand'       => __('Random Order', 'optimus-bytes-woo-kit'),
                    'title'      => __('Alphabetical (A-Z)', 'optimus-bytes-woo-kit'),
                ),
            )
        );

        $this->add_control(
            'tabs',
            array(
                'label'       => __('Tabs List', 'optimus-bytes-woo-kit'),
                'type'        => Controls_Manager::REPEATER,
                'fields'      => $repeater->get_controls(),
                'default'     => array(
                    array(
                        'tab_title'      => '🔥 Best Sellers',
                        'product_source' => 'best_selling',
                        'limit'          => 8,
                    ),
                    array(
                        'tab_title'      => '✨ New Arrivals',
                        'product_source' => 'new_arrivals',
                        'limit'          => 8,
                    ),
                    array(
                        'tab_title'      => '👑 Featured',
                        'product_source' => 'featured',
                        'limit'          => 8,
                    ),
                    array(
                        'tab_title'      => '🏷️ Special Offers',
                        'product_source' => 'on_sale',
                        'limit'          => 8,
                    ),
                ),
                'title_field' => '{{{ tab_title }}}',
            )
        );

        $this->end_controls_section();

        // ==========================================
        // TAB: CONTENT - Layout & Product Cards
        // ==========================================
        $this->start_controls_section(
            'section_layout',
            array(
                'label' => __('Layout & Product Cards', 'optimus-bytes-woo-kit'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            )
        );

        $this->add_control(
            'layout',
            array(
                'label'   => __('Display Mode', 'optimus-bytes-woo-kit'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'slider',
                'options' => array(
                    'slider' => __('Carousel Slider (Swiper)', 'optimus-bytes-woo-kit'),
                    'grid'   => __('Responsive Grid', 'optimus-bytes-woo-kit'),
                ),
            )
        );

        $this->add_control(
            'tab_style',
            array(
                'label'   => __('Tab Navigation Style', 'optimus-bytes-woo-kit'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'pills',
                'options' => array(
                    'pills'     => __('Luxury Rounded Pills', 'optimus-bytes-woo-kit'),
                    'underline' => __('Minimal Line Accent', 'optimus-bytes-woo-kit'),
                    'bordered'  => __('Bordered Buttons', 'optimus-bytes-woo-kit'),
                ),
            )
        );

        $this->add_control(
            'tab_align',
            array(
                'label'   => __('Tab Alignment', 'optimus-bytes-woo-kit'),
                'type'    => Controls_Manager::CHOOSE,
                'options' => array(
                    'flex-start' => array(
                        'title' => __('Left', 'optimus-bytes-woo-kit'),
                        'icon'  => 'eicon-text-align-left',
                    ),
                    'center'     => array(
                        'title' => __('Center', 'optimus-bytes-woo-kit'),
                        'icon'  => 'eicon-text-align-center',
                    ),
                    'flex-end'   => array(
                        'title' => __('Right', 'optimus-bytes-woo-kit'),
                        'icon'  => 'eicon-text-align-right',
                    ),
                ),
                'default' => 'center',
                'selectors' => array(
                    '{{WRAPPER}} .obwk-tabs-nav' => 'justify-content: {{VALUE}};',
                ),
            )
        );

        $this->add_responsive_control(
            'columns',
            array(
                'label'          => __('Grid Columns', 'optimus-bytes-woo-kit'),
                'type'           => Controls_Manager::SELECT,
                'default'        => '4',
                'tablet_default' => '3',
                'mobile_default' => '2',
                'options'        => array(
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                ),
                'condition'      => array(
                    'layout' => 'grid',
                ),
            )
        );

        $this->add_control(
            'aspect_ratio',
            array(
                'label'   => __('Image Aspect Ratio', 'optimus-bytes-woo-kit'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'ratio_3_4',
                'options' => array(
                    'ratio_3_4'  => __('3:4 (Portrait / Sarees & Fashion)', 'optimus-bytes-woo-kit'),
                    'ratio_1_1'  => __('1:1 (Square)', 'optimus-bytes-woo-kit'),
                    'ratio_4_5'  => __('4:5 (Standard Portrait)', 'optimus-bytes-woo-kit'),
                    'ratio_auto' => __('Auto (Original)', 'optimus-bytes-woo-kit'),
                ),
            )
        );

        $this->add_control(
            'show_secondary_image',
            array(
                'label'        => __('Secondary Image Hover Swap', 'optimus-bytes-woo-kit'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __('Yes', 'optimus-bytes-woo-kit'),
                'label_off'    => __('No', 'optimus-bytes-woo-kit'),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
            'show_badge',
            array(
                'label'        => __('Discount % Off Badge', 'optimus-bytes-woo-kit'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __('Yes', 'optimus-bytes-woo-kit'),
                'label_off'    => __('No', 'optimus-bytes-woo-kit'),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
            'show_rating',
            array(
                'label'        => __('Customer Star Ratings', 'optimus-bytes-woo-kit'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __('Yes', 'optimus-bytes-woo-kit'),
                'label_off'    => __('No', 'optimus-bytes-woo-kit'),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
            'show_swatches',
            array(
                'label'        => __('Variation Color Swatches Dots', 'optimus-bytes-woo-kit'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __('Yes', 'optimus-bytes-woo-kit'),
                'label_off'    => __('No', 'optimus-bytes-woo-kit'),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
            'show_add_to_cart',
            array(
                'label'        => __('Show Add to Cart Button', 'optimus-bytes-woo-kit'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __('Yes', 'optimus-bytes-woo-kit'),
                'label_off'    => __('No', 'optimus-bytes-woo-kit'),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
            'show_whatsapp_btn',
            array(
                'label'        => __('Show WhatsApp Order Icon Button', 'optimus-bytes-woo-kit'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __('Yes', 'optimus-bytes-woo-kit'),
                'label_off'    => __('No', 'optimus-bytes-woo-kit'),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->end_controls_section();

        // ==========================================
        // TAB: CONTENT - Slider Settings
        // ==========================================
        $this->start_controls_section(
            'section_slider_settings',
            array(
                'label'     => __('Slider Settings', 'optimus-bytes-woo-kit'),
                'tab'       => Controls_Manager::TAB_CONTENT,
                'condition' => array(
                    'layout' => 'slider',
                ),
            )
        );

        $this->add_responsive_control(
            'slides_per_view',
            array(
                'label'          => __('Slides to Show', 'optimus-bytes-woo-kit'),
                'type'           => Controls_Manager::SELECT,
                'default'        => '4',
                'tablet_default' => '3',
                'mobile_default' => '2',
                'options'        => array(
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                ),
            )
        );

        $this->add_responsive_control(
            'space_between',
            array(
                'label'   => __('Space Between Products (px)', 'optimus-bytes-woo-kit'),
                'type'    => Controls_Manager::SLIDER,
                'range'   => array(
                    'px' => array(
                        'min' => 0,
                        'max' => 40,
                    ),
                ),
                'default' => array(
                    'size' => 16,
                    'unit' => 'px',
                ),
            )
        );

        $this->add_control(
            'autoplay',
            array(
                'label'        => __('Autoplay', 'optimus-bytes-woo-kit'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __('Yes', 'optimus-bytes-woo-kit'),
                'label_off'    => __('No', 'optimus-bytes-woo-kit'),
                'return_value' => 'yes',
                'default'      => 'no',
            )
        );

        $this->add_control(
            'autoplay_speed',
            array(
                'label'     => __('Autoplay Interval (ms)', 'optimus-bytes-woo-kit'),
                'type'      => Controls_Manager::NUMBER,
                'default'   => 4000,
                'min'       => 1000,
                'max'       => 20000,
                'condition' => array(
                    'autoplay' => 'yes',
                ),
            )
        );

        $this->add_control(
            'loop',
            array(
                'label'        => __('Infinite Loop', 'optimus-bytes-woo-kit'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __('Yes', 'optimus-bytes-woo-kit'),
                'label_off'    => __('No', 'optimus-bytes-woo-kit'),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
            'show_arrows',
            array(
                'label'        => __('Navigation Arrows', 'optimus-bytes-woo-kit'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __('Yes', 'optimus-bytes-woo-kit'),
                'label_off'    => __('No', 'optimus-bytes-woo-kit'),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
            'show_dots',
            array(
                'label'        => __('Pagination Dots', 'optimus-bytes-woo-kit'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __('Yes', 'optimus-bytes-woo-kit'),
                'label_off'    => __('No', 'optimus-bytes-woo-kit'),
                'return_value' => 'yes',
                'default'      => 'no',
            )
        );

        $this->end_controls_section();

        // ==========================================
        // TAB: STYLE - Tab Bar Header
        // ==========================================
        $this->start_controls_section(
            'section_style_tabs',
            array(
                'label' => __('Tab Navigation Bar', 'optimus-bytes-woo-kit'),
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_control(
            'tab_inactive_color',
            array(
                'label'     => __('Tab Text Color (Inactive)', 'optimus-bytes-woo-kit'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .obwk-tab-btn' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'tab_active_color',
            array(
                'label'     => __('Tab Text Color (Active)', 'optimus-bytes-woo-kit'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .obwk-tab-btn.is-active' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'tab_active_bg',
            array(
                'label'     => __('Tab Background (Active)', 'optimus-bytes-woo-kit'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .obwk-tabs-style-pills .obwk-tab-btn.is-active' => 'background: {{VALUE}};',
                    '{{WRAPPER}} .obwk-tabs-style-underline .obwk-tab-btn.is-active::after' => 'background: {{VALUE}};',
                    '{{WRAPPER}} .obwk-tabs-style-bordered .obwk-tab-btn.is-active' => 'border-color: {{VALUE}}; color: {{VALUE}};',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'tab_typography',
                'selector' => '{{WRAPPER}} .obwk-tab-btn',
            )
        );

        $this->end_controls_section();

        // ==========================================
        // TAB: STYLE - Product Cards
        // ==========================================
        $this->start_controls_section(
            'section_style_cards',
            array(
                'label' => __('Product Cards', 'optimus-bytes-woo-kit'),
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_control(
            'card_bg_color',
            array(
                'label'     => __('Card Background Color', 'optimus-bytes-woo-kit'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .obwk-product-card' => 'background-color: {{VALUE}} !important;',
                ),
            )
        );

        $this->add_control(
            'card_hover_bg_color',
            array(
                'label'     => __('Card Hover Background Color', 'optimus-bytes-woo-kit'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .obwk-product-card:hover' => 'background-color: {{VALUE}} !important;',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            array(
                'name'     => 'card_bg',
                'label'    => __('Card Background (Gradient/Image)', 'optimus-bytes-woo-kit'),
                'types'    => array('classic', 'gradient'),
                'selector' => '{{WRAPPER}} .obwk-product-card',
            )
        );

        $this->add_control(
            'info_bg_color',
            array(
                'label'     => __('Product Info Box Background Color', 'optimus-bytes-woo-kit'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .obwk-product-content' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            array(
                'name'     => 'card_border',
                'selector' => '{{WRAPPER}} .obwk-product-card',
            )
        );

        $this->add_control(
            'card_hover_border_color',
            array(
                'label'     => __('Card Hover Border Color', 'optimus-bytes-woo-kit'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .obwk-product-card:hover' => 'border-color: {{VALUE}} !important;',
                ),
            )
        );

        $this->add_responsive_control(
            'card_padding',
            array(
                'label'      => __('Card Padding', 'optimus-bytes-woo-kit'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em', '%'),
                'selectors'  => array(
                    '{{WRAPPER}} .obwk-product-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_responsive_control(
            'card_radius',
            array(
                'label'      => __('Border Radius', 'optimus-bytes-woo-kit'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'selectors'  => array(
                    '{{WRAPPER}} .obwk-product-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .obwk-product-img-box' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} 0 0;',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            array(
                'name'     => 'card_shadow',
                'selector' => '{{WRAPPER}} .obwk-product-card',
            )
        );

        $this->end_controls_section();

        // ==========================================
        // TAB: STYLE - Typography & Colors
        // ==========================================
        $this->start_controls_section(
            'section_style_typography',
            array(
                'label' => __('Typography & Prices', 'optimus-bytes-woo-kit'),
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_control(
            'title_color',
            array(
                'label'     => __('Product Title Color', 'optimus-bytes-woo-kit'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .obwk-product-card-title' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'title_typo',
                'selector' => '{{WRAPPER}} .obwk-product-card-title',
            )
        );

        $this->add_control(
            'price_color',
            array(
                'label'     => __('Sale / Active Price Color', 'optimus-bytes-woo-kit'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .obwk-product-price ins, {{WRAPPER}} .obwk-product-price .amount' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->end_controls_section();
    }

    /**
     * Query WooCommerce Products for a given tab configuration
     *
     * @param array $tab
     * @return array
     */
    public static function query_products_for_tab($tab) {
        $source  = !empty($tab['product_source']) ? $tab['product_source'] : 'best_selling';
        $limit   = !empty($tab['limit']) ? intval($tab['limit']) : 8;
        $orderby = !empty($tab['orderby']) ? $tab['orderby'] : 'default';

        $args = array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'meta_query'     => array(
                array(
                    'key'     => '_stock_status',
                    'value'   => 'instock',
                    'compare' => '=',
                ),
            ),
        );

        // Filter by source
        if ('best_selling' === $source) {
            $args['meta_key'] = 'total_sales';
            $args['orderby']  = 'meta_value_num';
            $args['order']    = 'DESC';
        } elseif ('new_arrivals' === $source) {
            $args['orderby'] = 'date';
            $args['order']   = 'DESC';
        } elseif ('featured' === $source) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_visibility',
                    'field'    => 'name',
                    'terms'    => 'featured',
                ),
            );
        } elseif ('on_sale' === $source) {
            $product_ids_on_sale = wc_get_product_ids_on_sale();
            $args['post__in']    = !empty($product_ids_on_sale) ? $product_ids_on_sale : array(0);
        } elseif ('top_rated' === $source) {
            $args['meta_key'] = '_wc_average_rating';
            $args['orderby']  = 'meta_value_num';
            $args['order']    = 'DESC';
        } elseif ('category' === $source && !empty($tab['category_id'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => intval($tab['category_id']),
                ),
            );
        }

        // Custom orderby overrides
        if ('date' === $orderby) {
            $args['orderby'] = 'date';
            $args['order']   = 'DESC';
        } elseif ('price' === $orderby) {
            $args['meta_key'] = '_price';
            $args['orderby']  = 'meta_value_num';
            $args['order']    = 'ASC';
        } elseif ('price_desc' === $orderby) {
            $args['meta_key'] = '_price';
            $args['orderby']  = 'meta_value_num';
            $args['order']    = 'DESC';
        } elseif ('rand' === $orderby) {
            $args['orderby'] = 'rand';
        } elseif ('title' === $orderby) {
            $args['orderby'] = 'title';
            $args['order']   = 'ASC';
        }

        $query = new \WP_Query($args);
        return $query->posts;
    }

    /**
     * Render Single Product Card HTML
     *
     * @param \WC_Product $product
     * @param array $settings
     * @return string
     */
    public static function render_product_card($product, $settings) {
        if (!$product) {
            return '';
        }

        $product_id         = $product->get_id();
        $title              = $product->get_name();
        $permalink          = $product->get_permalink();
        $image_id           = $product->get_image_id();
        $gallery_image_ids  = $product->get_gallery_image_ids();
        $secondary_image_id = (!empty($gallery_image_ids)) ? $gallery_image_ids[0] : null;

        $show_secondary     = (!empty($settings['show_secondary_image']) && 'yes' === $settings['show_secondary_image']);
        $show_badge         = (!empty($settings['show_badge']) && 'yes' === $settings['show_badge']);
        $show_rating        = (!empty($settings['show_rating']) && 'yes' === $settings['show_rating']);
        $show_swatches      = (!empty($settings['show_swatches']) && 'yes' === $settings['show_swatches']);
        $show_add_to_cart   = (!empty($settings['show_add_to_cart']) && 'yes' === $settings['show_add_to_cart']);
        $show_whatsapp      = (!empty($settings['show_whatsapp_btn']) && 'yes' === $settings['show_whatsapp_btn']);

        // Calculate discount percentage if on sale
        $discount_pct = 0;
        if ($product->is_on_sale()) {
            $regular_price = (float) $product->get_regular_price();
            $sale_price    = (float) $product->get_sale_price();
            if ($regular_price > 0 && $sale_price > 0 && $regular_price > $sale_price) {
                $discount_pct = round((($regular_price - $sale_price) / $regular_price) * 100);
            }
        }

        ob_start();
        ?>
        <div class="obwk-product-card">
            <div class="obwk-product-img-box">
                <a href="<?php echo esc_url($permalink); ?>" class="obwk-product-img-link" aria-label="<?php echo esc_attr($title); ?>">
                    <?php if ($image_id) : ?>
                        <?php echo wp_get_attachment_image($image_id, 'woocommerce_thumbnail', false, array('class' => 'obwk-primary-img')); ?>
                    <?php else : ?>
                        <img src="<?php echo esc_url(wc_placeholder_img_src('woocommerce_thumbnail')); ?>" alt="<?php echo esc_attr($title); ?>" class="obwk-primary-img" />
                    <?php endif; ?>

                    <?php if ($show_secondary && $secondary_image_id) : ?>
                        <?php echo wp_get_attachment_image($secondary_image_id, 'woocommerce_thumbnail', false, array('class' => 'obwk-secondary-img')); ?>
                    <?php endif; ?>
                </a>

                <?php if ($show_badge && $discount_pct > 0) : ?>
                    <span class="obwk-discount-badge"><?php echo esc_html($discount_pct); ?>% <?php esc_html_e('OFF', 'optimus-bytes-woo-kit'); ?></span>
                <?php endif; ?>

                <?php if ($show_whatsapp && class_exists('OptimusBytes\WooKit\Modules\Product_WhatsApp\Product_WhatsApp_Module')) : 
                    $phone_number = get_option('obwk_product_whatsapp_phone', '919876543210');
                    $custom_msg   = sprintf(__('Hi, I am interested in ordering "%s" (%s). Please share more details.', 'optimus-bytes-woo-kit'), $title, $permalink);
                    $wa_url       = 'https://wa.me/' . preg_replace('/[^0-9]/', '', $phone_number) . '?text=' . rawurlencode($custom_msg);
                ?>
                    <a href="<?php echo esc_url($wa_url); ?>" target="_blank" rel="noopener noreferrer" class="obwk-card-wa-btn" title="<?php esc_attr_e('Order on WhatsApp', 'optimus-bytes-woo-kit'); ?>">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.664-.699c.971.53 1.77.814 2.797.815 3.179 0 5.767-2.587 5.768-5.766 0-3.18-2.588-5.767-5.769-5.767zm7.509 5.766c-.001 4.14-3.368 7.508-7.509 7.508-1.31 0-2.311-.328-3.328-.891l-4.703 1.233 1.256-4.588c-.624-1.082-.962-2.102-.962-3.262.001-4.141 3.368-7.509 7.509-7.509s7.537 3.368 7.737 7.509z"/></svg>
                    </a>
                <?php endif; ?>
            </div>

            <div class="obwk-product-content">
                <?php if ($show_rating && $product->get_average_rating() > 0) : ?>
                    <div class="obwk-product-rating">
                        <?php echo wc_get_rating_html($product->get_average_rating()); ?>
                    </div>
                <?php endif; ?>

                <h4 class="obwk-product-card-title">
                    <a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a>
                </h4>

                <div class="obwk-product-price">
                    <?php echo $product->get_price_html(); ?>
                </div>

                <?php 
                // Render Swatches if variable product
                if ($show_swatches && $product->is_type('variable') && class_exists('OptimusBytes\WooKit\Modules\Variation_Swatches\Variation_Swatches_Module')) : 
                    $swatches_module = new \OptimusBytes\WooKit\Modules\Variation_Swatches\Variation_Swatches_Module();
                    if (method_exists($swatches_module, 'get_loop_swatches_html')) {
                        echo $swatches_module->get_loop_swatches_html($product);
                    }
                endif; 
                ?>

                <?php if ($show_add_to_cart) : ?>
                    <div class="obwk-card-actions">
                        <?php woocommerce_template_loop_add_to_cart(); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render Complete Tabbed Product Component HTML
     *
     * @param array $settings
     * @return string
     */
    public static function render_tabs_html($settings) {
        $tabs = !empty($settings['tabs']) ? $settings['tabs'] : array(
            array(
                'tab_title'      => __('🔥 Best Sellers', 'optimus-bytes-woo-kit'),
                'product_source' => 'best_selling',
                'limit'          => 8,
            ),
            array(
                'tab_title'      => __('✨ New Arrivals', 'optimus-bytes-woo-kit'),
                'product_source' => 'new_arrivals',
                'limit'          => 8,
            ),
            array(
                'tab_title'      => __('👑 Featured', 'optimus-bytes-woo-kit'),
                'product_source' => 'featured',
                'limit'          => 8,
            ),
            array(
                'tab_title'      => __('🏷️ Special Offers', 'optimus-bytes-woo-kit'),
                'product_source' => 'on_sale',
                'limit'          => 8,
            ),
        );

        if (empty($tabs)) {
            return '';
        }

        $widget_id       = 'obwk_tabs_' . uniqid();
        $layout          = !empty($settings['layout']) ? $settings['layout'] : 'slider';
        $tab_style       = !empty($settings['tab_style']) ? $settings['tab_style'] : 'pills';
        $aspect_ratio    = !empty($settings['aspect_ratio']) ? $settings['aspect_ratio'] : 'ratio_3_4';
        $columns_desktop = !empty($settings['columns']) ? intval($settings['columns']) : 4;
        $columns_tablet  = !empty($settings['columns_tablet']) ? intval($settings['columns_tablet']) : 3;
        $columns_mobile  = !empty($settings['columns_mobile']) ? intval($settings['columns_mobile']) : 2;

        // Slider specific options
        $slides_desktop = !empty($settings['slides_per_view']) ? intval($settings['slides_per_view']) : 4;
        $slides_tablet  = !empty($settings['slides_per_view_tablet']) ? intval($settings['slides_per_view_tablet']) : 3;
        $slides_mobile  = !empty($settings['slides_per_view_mobile']) ? intval($settings['slides_per_view_mobile']) : 2;
        $space_between  = isset($settings['space_between']['size']) ? intval($settings['space_between']['size']) : 16;
        $autoplay       = (!empty($settings['autoplay']) && 'yes' === $settings['autoplay']);
        $autoplay_speed = !empty($settings['autoplay_speed']) ? intval($settings['autoplay_speed']) : 4000;
        $loop           = (!empty($settings['loop']) && 'yes' === $settings['loop']);
        $show_arrows    = (!empty($settings['show_arrows']) && 'yes' === $settings['show_arrows']);
        $show_dots      = (!empty($settings['show_dots']) && 'yes' === $settings['show_dots']);

        $slider_config = array(
            'slidesPerView' => $slides_desktop,
            'spaceBetween'  => $space_between,
            'loop'          => $loop,
            'observer'      => true,
            'observeParents'=> true,
            'autoplay'      => $autoplay ? array(
                'delay'                => $autoplay_speed,
                'disableOnInteraction' => false,
            ) : false,
            'breakpoints'   => array(
                0   => array(
                    'slidesPerView' => $slides_mobile,
                    'spaceBetween'  => max(8, intval($space_between / 2)),
                ),
                640 => array(
                    'slidesPerView' => $slides_tablet,
                    'spaceBetween'  => $space_between,
                ),
                1024 => array(
                    'slidesPerView' => $slides_desktop,
                    'spaceBetween'  => $space_between,
                ),
            ),
        );

        $container_classes = array(
            'obwk-product-tabs-wrapper',
            'obwk-tabs-style-' . sanitize_html_class($tab_style),
            'obwk-aspect-' . sanitize_html_class($aspect_ratio),
            'obwk-layout-' . sanitize_html_class($layout),
        );

        ob_start();
        ?>
        <div class="<?php echo esc_attr(implode(' ', $container_classes)); ?>" id="<?php echo esc_attr($widget_id); ?>" data-slider-config="<?php echo esc_attr(wp_json_encode($slider_config)); ?>">
            
            <!-- Tab Navigation Header -->
            <div class="obwk-tabs-nav-wrap">
                <ul class="obwk-tabs-nav" role="tablist">
                    <?php foreach ($tabs as $index => $tab) : 
                        $tab_title = !empty($tab['tab_title']) ? $tab['tab_title'] : sprintf(__('Tab %d', 'optimus-bytes-woo-kit'), $index + 1);
                        $is_active = (0 === $index);
                        $tab_id    = $widget_id . '_tab_' . $index;
                        $panel_id  = $widget_id . '_panel_' . $index;
                    ?>
                        <li class="obwk-tab-item" role="presentation">
                            <button type="button" 
                                    class="obwk-tab-btn <?php echo $is_active ? 'is-active' : ''; ?>" 
                                    role="tab" 
                                    id="<?php echo esc_attr($tab_id); ?>" 
                                    data-target="<?php echo esc_attr($panel_id); ?>" 
                                    aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>" 
                                    aria-controls="<?php echo esc_attr($panel_id); ?>">
                                <?php echo esc_html($tab_title); ?>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Tab Content Panels -->
            <div class="obwk-tabs-content">
                <?php foreach ($tabs as $index => $tab) : 
                    $is_active  = (0 === $index);
                    $panel_id   = $widget_id . '_panel_' . $index;
                    $tab_id     = $widget_id . '_tab_' . $index;
                    $posts      = self::query_products_for_tab($tab);
                ?>
                    <div class="obwk-tab-panel <?php echo $is_active ? 'is-active' : ''; ?>" 
                         id="<?php echo esc_attr($panel_id); ?>" 
                         role="tabpanel" 
                         aria-labelledby="<?php echo esc_attr($tab_id); ?>" 
                         style="<?php echo $is_active ? 'display:block;' : 'display:none;'; ?>">
                        
                        <?php if (empty($posts)) : ?>
                            <div class="obwk-tabs-empty">
                                <p><?php esc_html_e('No products found for this collection.', 'optimus-bytes-woo-kit'); ?></p>
                            </div>
                        <?php else : ?>

                            <?php if ('slider' === $layout) : ?>
                                <div class="swiper obwk-product-swiper">
                                    <div class="swiper-wrapper">
                            <?php else : ?>
                                <div class="obwk-product-grid obwk-cols-<?php echo esc_attr($columns_desktop); ?> obwk-cols-tab-<?php echo esc_attr($columns_tablet); ?> obwk-cols-mob-<?php echo esc_attr($columns_mobile); ?>">
                            <?php endif; ?>

                                <?php foreach ($posts as $post) : 
                                    $product = wc_get_product($post->ID);
                                    if (!$product) continue;
                                ?>
                                    <?php if ('slider' === $layout) : ?>
                                        <div class="swiper-slide">
                                    <?php endif; ?>

                                        <?php echo self::render_product_card($product, $settings); ?>

                                    <?php if ('slider' === $layout) : ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>

                            <?php if ('slider' === $layout) : ?>
                                    </div>
                                </div>

                                <?php if ($show_arrows) : ?>
                                    <button type="button" class="obwk-swiper-arrow obwk-swiper-prev" aria-label="<?php esc_attr_e('Previous Products', 'optimus-bytes-woo-kit'); ?>">
                                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                                    </button>
                                    <button type="button" class="obwk-swiper-arrow obwk-swiper-next" aria-label="<?php esc_attr_e('Next Products', 'optimus-bytes-woo-kit'); ?>">
                                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                                    </button>
                                <?php endif; ?>

                                <?php if ($show_dots) : ?>
                                    <div class="swiper-pagination obwk-swiper-pagination"></div>
                                <?php endif; ?>

                            <?php else : ?>
                                </div>
                            <?php endif; ?>

                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>
            </div>

        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render output on frontend & Elementor editor
     */
    protected function render() {
        $settings = $this->get_settings_for_display();
        echo self::render_tabs_html($settings);
    }
}
