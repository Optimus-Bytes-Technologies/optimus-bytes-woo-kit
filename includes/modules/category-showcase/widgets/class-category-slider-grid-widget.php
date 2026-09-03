<?php
/**
 * Elementor Widget: Category Slider & Grid
 *
 * @package OptimusBytes\WooKit\Modules\Category_Showcase\Widgets
 */

namespace OptimusBytes\WooKit\Modules\Category_Showcase\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Background;

defined('ABSPATH') || exit;

if (!class_exists('\Elementor\Widget_Base')) {
    return;
}

class Category_Slider_Grid_Widget extends Widget_Base {

    /**
     * Get Widget Name
     *
     * @return string
     */
    public function get_name() {
        return 'obwk_category_slider_grid';
    }

    /**
     * Get Widget Title
     *
     * @return string
     */
    public function get_title() {
        return __('Category Slider & Grid', 'optimus-bytes-woo-kit');
    }

    /**
     * Get Widget Icon
     *
     * @return string
     */
    public function get_icon() {
        return 'eicon-gallery-grid';
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
        return array('category', 'categories', 'slider', 'grid', 'carousel', 'woocommerce', 'product category', 'saree', 'shop');
    }

    /**
     * Get list of product categories for select controls
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
        // TAB: CONTENT - Layout & Design
        // ==========================================
        $this->start_controls_section(
            'section_layout',
            array(
                'label' => __('Layout & Appearance', 'optimus-bytes-woo-kit'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            )
        );

        $this->add_control(
            'layout',
            array(
                'label'   => __('Display Layout', 'optimus-bytes-woo-kit'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'slider',
                'options' => array(
                    'slider' => __('Carousel Slider (Swiper)', 'optimus-bytes-woo-kit'),
                    'grid'   => __('Responsive Grid', 'optimus-bytes-woo-kit'),
                ),
            )
        );

        $this->add_control(
            'skin',
            array(
                'label'   => __('Card Style / Skin', 'optimus-bytes-woo-kit'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'overlay',
                'options' => array(
                    'overlay' => __('Luxury Overlay Card (Modern Saree Look)', 'optimus-bytes-woo-kit'),
                    'classic' => __('Classic Card (Image Top, Info Below)', 'optimus-bytes-woo-kit'),
                    'circle'  => __('Circular Story (Avatar with Gold Ring)', 'optimus-bytes-woo-kit'),
                    'banner'  => __('Minimal Banner Card', 'optimus-bytes-woo-kit'),
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
                    '1' => '1',
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
                    'ratio_3_4'  => __('3:4 (Portrait / Fashion & Sarees)', 'optimus-bytes-woo-kit'),
                    'ratio_1_1'  => __('1:1 (Square)', 'optimus-bytes-woo-kit'),
                    'ratio_4_5'  => __('4:5 (Standard Portrait)', 'optimus-bytes-woo-kit'),
                    'ratio_16_9' => __('16:9 (Landscape)', 'optimus-bytes-woo-kit'),
                    'ratio_auto' => __('Auto (Original Dimensions)', 'optimus-bytes-woo-kit'),
                ),
                'condition' => array(
                    'skin!' => 'circle',
                ),
            )
        );

        $this->add_control(
            'image_size',
            array(
                'label'   => __('Image Resolution', 'optimus-bytes-woo-kit'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'woocommerce_thumbnail',
                'options' => array(
                    'woocommerce_thumbnail' => __('WooCommerce Thumbnail', 'optimus-bytes-woo-kit'),
                    'medium'                => __('Medium (300x300)', 'optimus-bytes-woo-kit'),
                    'large'                 => __('Large (1024x1024)', 'optimus-bytes-woo-kit'),
                    'full'                  => __('Full Original Resolution', 'optimus-bytes-woo-kit'),
                ),
            )
        );

        $this->end_controls_section();

        // ==========================================
        // TAB: CONTENT - Query & Selection
        // ==========================================
        $this->start_controls_section(
            'section_query',
            array(
                'label' => __('Category Selection & Query', 'optimus-bytes-woo-kit'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            )
        );

        $this->add_control(
            'source',
            array(
                'label'   => __('Source', 'optimus-bytes-woo-kit'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'all',
                'options' => array(
                    'all'          => __('All Categories', 'optimus-bytes-woo-kit'),
                    'parents_only' => __('Top-Level Categories Only (No Subcategories)', 'optimus-bytes-woo-kit'),
                    'selected'     => __('Specific Selected Categories', 'optimus-bytes-woo-kit'),
                    'by_parent'    => __('Direct Children of a Specific Parent Category', 'optimus-bytes-woo-kit'),
                ),
            )
        );

        $this->add_control(
            'categories',
            array(
                'label'       => __('Select Categories', 'optimus-bytes-woo-kit'),
                'type'        => Controls_Manager::SELECT2,
                'multiple'    => true,
                'options'     => $this->get_product_categories_options(),
                'condition'   => array(
                    'source' => 'selected',
                ),
                'label_block' => true,
            )
        );

        $this->add_control(
            'parent_category',
            array(
                'label'       => __('Parent Category', 'optimus-bytes-woo-kit'),
                'type'        => Controls_Manager::SELECT,
                'options'     => $this->get_product_categories_options(),
                'condition'   => array(
                    'source' => 'by_parent',
                ),
                'label_block' => true,
            )
        );

        $this->add_control(
            'exclude_categories',
            array(
                'label'       => __('Exclude Categories', 'optimus-bytes-woo-kit'),
                'type'        => Controls_Manager::SELECT2,
                'multiple'    => true,
                'options'     => $this->get_product_categories_options(),
                'condition'   => array(
                    'source!' => 'selected',
                ),
                'label_block' => true,
            )
        );

        $this->add_control(
            'limit',
            array(
                'label'   => __('Total Categories to Display', 'optimus-bytes-woo-kit'),
                'type'    => Controls_Manager::NUMBER,
                'min'     => 1,
                'max'     => 100,
                'step'    => 1,
                'default' => 8,
            )
        );

        $this->add_control(
            'orderby',
            array(
                'label'   => __('Order By', 'optimus-bytes-woo-kit'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'menu_order',
                'options' => array(
                    'menu_order' => __('WooCommerce Drag & Drop Order (Menu Order)', 'optimus-bytes-woo-kit'),
                    'name'       => __('Category Name', 'optimus-bytes-woo-kit'),
                    'count'      => __('Product Count (Popularity)', 'optimus-bytes-woo-kit'),
                    'id'         => __('Term ID', 'optimus-bytes-woo-kit'),
                    'include'    => __('Selected Order in Query', 'optimus-bytes-woo-kit'),
                ),
            )
        );

        $this->add_control(
            'order',
            array(
                'label'   => __('Order Direction', 'optimus-bytes-woo-kit'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'ASC',
                'options' => array(
                    'ASC'  => __('Ascending (A-Z, 0-9)', 'optimus-bytes-woo-kit'),
                    'DESC' => __('Descending (Z-A, 9-0)', 'optimus-bytes-woo-kit'),
                ),
            )
        );

        $this->add_control(
            'hide_empty',
            array(
                'label'        => __('Hide Empty Categories', 'optimus-bytes-woo-kit'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __('Yes', 'optimus-bytes-woo-kit'),
                'label_off'    => __('No', 'optimus-bytes-woo-kit'),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
            'show_count',
            array(
                'label'        => __('Show Product Count Badge', 'optimus-bytes-woo-kit'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __('Yes', 'optimus-bytes-woo-kit'),
                'label_off'    => __('No', 'optimus-bytes-woo-kit'),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
            'count_format',
            array(
                'label'       => __('Count Badge Format', 'optimus-bytes-woo-kit'),
                'type'        => Controls_Manager::TEXT,
                'default'     => '{count} Products',
                'placeholder' => '{count} Sarees',
                'condition'   => array(
                    'show_count' => 'yes',
                ),
            )
        );

        $this->add_control(
            'show_button',
            array(
                'label'        => __('Show "Explore" Action Button / Pill', 'optimus-bytes-woo-kit'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __('Yes', 'optimus-bytes-woo-kit'),
                'label_off'    => __('No', 'optimus-bytes-woo-kit'),
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition'    => array(
                    'skin!' => 'circle',
                ),
            )
        );

        $this->add_control(
            'button_text',
            array(
                'label'       => __('Button Label', 'optimus-bytes-woo-kit'),
                'type'        => Controls_Manager::TEXT,
                'default'     => __('Explore', 'optimus-bytes-woo-kit'),
                'placeholder' => __('Shop Now', 'optimus-bytes-woo-kit'),
                'condition'   => array(
                    'show_button' => 'yes',
                    'skin!'       => 'circle',
                ),
            )
        );

        $this->end_controls_section();

        // ==========================================
        // TAB: CONTENT - Slider Settings (Carousel)
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
                    '1' => '1',
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
                'label'   => __('Space Between Slides (px)', 'optimus-bytes-woo-kit'),
                'type'    => Controls_Manager::SLIDER,
                'range'   => array(
                    'px' => array(
                        'min' => 0,
                        'max' => 50,
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
                'label'        => __('Autoplay Carousel', 'optimus-bytes-woo-kit'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __('Yes', 'optimus-bytes-woo-kit'),
                'label_off'    => __('No', 'optimus-bytes-woo-kit'),
                'return_value' => 'yes',
                'default'      => 'yes',
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
                'step'      => 500,
                'condition' => array(
                    'autoplay' => 'yes',
                ),
            )
        );

        $this->add_control(
            'pause_on_hover',
            array(
                'label'        => __('Pause on Hover', 'optimus-bytes-woo-kit'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __('Yes', 'optimus-bytes-woo-kit'),
                'label_off'    => __('No', 'optimus-bytes-woo-kit'),
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition'    => array(
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
            'arrows_position',
            array(
                'label'     => __('Arrows Position', 'optimus-bytes-woo-kit'),
                'type'      => Controls_Manager::SELECT,
                'default'   => 'outside',
                'options'   => array(
                    'outside' => __('Sides (Outside / Float)', 'optimus-bytes-woo-kit'),
                    'inside'  => __('Inside Carousel Area', 'optimus-bytes-woo-kit'),
                ),
                'condition' => array(
                    'show_arrows' => 'yes',
                ),
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
                'default'      => 'yes',
            )
        );

        $this->end_controls_section();

        // ==========================================
        // TAB: STYLE - Category Card & Items
        // ==========================================
        $this->start_controls_section(
            'section_style_card',
            array(
                'label' => __('Category Card & Container', 'optimus-bytes-woo-kit'),
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_control(
            'card_bg_color',
            array(
                'label'     => __('Card Background Color', 'optimus-bytes-woo-kit'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .obwk-category-card' => 'background-color: {{VALUE}} !important;',
                ),
            )
        );

        $this->add_control(
            'card_hover_bg_color',
            array(
                'label'     => __('Card Hover Background Color', 'optimus-bytes-woo-kit'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .obwk-category-card:hover' => 'background-color: {{VALUE}} !important;',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            array(
                'name'     => 'card_background',
                'label'    => __('Card Background (Gradient/Image)', 'optimus-bytes-woo-kit'),
                'types'    => array('classic', 'gradient'),
                'selector' => '{{WRAPPER}} .obwk-category-card',
            )
        );

        $this->add_control(
            'overlay_color',
            array(
                'label'     => __('Image Overlay Color / Gradient', 'optimus-bytes-woo-kit'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .obwk-category-overlay' => 'background: {{VALUE}} !important;',
                ),
                'condition' => array(
                    'skin' => 'overlay',
                ),
            )
        );

        $this->add_control(
            'img_box_bg_color',
            array(
                'label'     => __('Image Box Background Color', 'optimus-bytes-woo-kit'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .obwk-category-img-wrap' => 'background-color: {{VALUE}} !important;',
                ),
            )
        );

        $this->add_control(
            'info_bg_color',
            array(
                'label'     => __('Content Box Background Color', 'optimus-bytes-woo-kit'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .obwk-category-info' => 'background-color: {{VALUE}};',
                ),
                'condition' => array(
                    'skin' => 'classic',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            array(
                'name'     => 'card_border',
                'selector' => '{{WRAPPER}} .obwk-category-card',
            )
        );

        $this->add_control(
            'card_hover_border_color',
            array(
                'label'     => __('Card Hover Border Color', 'optimus-bytes-woo-kit'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .obwk-category-card:hover' => 'border-color: {{VALUE}} !important;',
                ),
            )
        );

        $this->add_responsive_control(
            'card_padding',
            array(
                'label'      => __('Padding', 'optimus-bytes-woo-kit'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em', '%'),
                'selectors'  => array(
                    '{{WRAPPER}} .obwk-category-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_responsive_control(
            'card_border_radius',
            array(
                'label'      => __('Border Radius', 'optimus-bytes-woo-kit'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'selectors'  => array(
                    '{{WRAPPER}} .obwk-category-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .obwk-category-img-wrap' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            array(
                'name'     => 'card_box_shadow',
                'selector' => '{{WRAPPER}} .obwk-category-card',
            )
        );

        $this->end_controls_section();

        // ==========================================
        // TAB: STYLE - Typography & Colors
        // ==========================================
        $this->start_controls_section(
            'section_style_typography',
            array(
                'label' => __('Title & Badges', 'optimus-bytes-woo-kit'),
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_control(
            'heading_title_style',
            array(
                'label' => __('Category Title', 'optimus-bytes-woo-kit'),
                'type'  => Controls_Manager::HEADING,
            )
        );

        $this->add_control(
            'title_color',
            array(
                'label'     => __('Title Color', 'optimus-bytes-woo-kit'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .obwk-category-title' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'title_hover_color',
            array(
                'label'     => __('Title Hover Color', 'optimus-bytes-woo-kit'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .obwk-category-card:hover .obwk-category-title' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'title_typography',
                'selector' => '{{WRAPPER}} .obwk-category-title',
            )
        );

        $this->add_control(
            'heading_count_style',
            array(
                'label'     => __('Product Count Badge', 'optimus-bytes-woo-kit'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => array(
                    'show_count' => 'yes',
                ),
            )
        );

        $this->add_control(
            'count_text_color',
            array(
                'label'     => __('Badge Text Color', 'optimus-bytes-woo-kit'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .obwk-category-count' => 'color: {{VALUE}};',
                ),
                'condition' => array(
                    'show_count' => 'yes',
                ),
            )
        );

        $this->add_control(
            'count_bg_color',
            array(
                'label'     => __('Badge Background Color', 'optimus-bytes-woo-kit'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .obwk-category-count' => 'background: {{VALUE}};',
                ),
                'condition' => array(
                    'show_count' => 'yes',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'      => 'count_typography',
                'selector'  => '{{WRAPPER}} .obwk-category-count',
                'condition' => array(
                    'show_count' => 'yes',
                ),
            )
        );

        $this->end_controls_section();

        // ==========================================
        // TAB: STYLE - Slider Navigation Arrows & Dots
        // ==========================================
        $this->start_controls_section(
            'section_style_navigation',
            array(
                'label'     => __('Slider Navigation & Dots', 'optimus-bytes-woo-kit'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'layout' => 'slider',
                ),
            )
        );

        $this->add_control(
            'arrow_color',
            array(
                'label'     => __('Arrow Icon Color', 'optimus-bytes-woo-kit'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .obwk-swiper-arrow' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'arrow_bg_color',
            array(
                'label'     => __('Arrow Background Color', 'optimus-bytes-woo-kit'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .obwk-swiper-arrow' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'arrow_hover_bg',
            array(
                'label'     => __('Arrow Hover Background', 'optimus-bytes-woo-kit'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .obwk-swiper-arrow:hover' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'dot_color',
            array(
                'label'     => __('Dot Inactive Color', 'optimus-bytes-woo-kit'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .swiper-pagination-bullet' => 'background-color: {{VALUE}}; opacity: 0.5;',
                ),
            )
        );

        $this->add_control(
            'dot_active_color',
            array(
                'label'     => __('Dot Active Color', 'optimus-bytes-woo-kit'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .swiper-pagination-bullet-active' => 'background-color: {{VALUE}} !important; opacity: 1;',
                ),
            )
        );

        $this->end_controls_section();
    }

    /**
     * Query product categories based on widget settings
     *
     * @param array $settings
     * @return array
     */
    public static function query_categories($settings) {
        $source      = !empty($settings['source']) ? $settings['source'] : 'all';
        $limit       = !empty($settings['limit']) ? intval($settings['limit']) : 8;
        $orderby     = !empty($settings['orderby']) ? $settings['orderby'] : 'menu_order';
        $order       = !empty($settings['order']) ? $settings['order'] : 'ASC';
        $hide_empty  = (!empty($settings['hide_empty']) && 'yes' === $settings['hide_empty']);

        $args = array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => $hide_empty,
            'number'     => $limit,
            'orderby'    => $orderby,
            'order'      => $order,
        );

        if ('parents_only' === $source) {
            $args['parent'] = 0;
        } elseif ('by_parent' === $source && !empty($settings['parent_category'])) {
            $args['parent'] = intval($settings['parent_category']);
        } elseif ('selected' === $source && !empty($settings['categories'])) {
            $args['include'] = (array) $settings['categories'];
            $args['orderby'] = ('include' === $orderby) ? 'include' : $orderby;
        }

        if (!empty($settings['exclude_categories']) && 'selected' !== $source) {
            $args['exclude'] = (array) $settings['exclude_categories'];
        }

        $terms = get_terms($args);
        return (!empty($terms) && !is_wp_error($terms)) ? $terms : array();
    }

    /**
     * Static helper to render Category Showcase HTML (Used by Elementor Widget & Shortcode)
     *
     * @param array $settings
     * @return string
     */
    public static function render_showcase_html($settings) {
        $terms = self::query_categories($settings);

        if (empty($terms)) {
            if (class_exists('\Elementor\Plugin') && \Elementor\Plugin::$instance && isset(\Elementor\Plugin::$instance->editor) && \Elementor\Plugin::$instance->editor->is_edit_mode()) {
                return '<div class="obwk-category-empty-notice">' . esc_html__('No WooCommerce product categories found for current query settings.', 'optimus-bytes-woo-kit') . '</div>';
            }
            return '';
        }

        $layout          = !empty($settings['layout']) ? $settings['layout'] : 'slider';
        $skin            = !empty($settings['skin']) ? $settings['skin'] : 'overlay';
        $aspect_ratio    = !empty($settings['aspect_ratio']) ? $settings['aspect_ratio'] : 'ratio_3_4';
        $image_size      = !empty($settings['image_size']) ? $settings['image_size'] : 'woocommerce_thumbnail';
        $show_count      = (!empty($settings['show_count']) && 'yes' === $settings['show_count']);
        $count_format    = !empty($settings['count_format']) ? $settings['count_format'] : '{count} Products';
        $show_button     = (!empty($settings['show_button']) && 'yes' === $settings['show_button'] && 'circle' !== $skin);
        $button_text     = !empty($settings['button_text']) ? $settings['button_text'] : __('Explore', 'optimus-bytes-woo-kit');
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
        $pause_hover    = (!empty($settings['pause_on_hover']) && 'yes' === $settings['pause_on_hover']);
        $loop           = (!empty($settings['loop']) && 'yes' === $settings['loop']);
        $show_arrows    = (!empty($settings['show_arrows']) && 'yes' === $settings['show_arrows']);
        $arrows_pos     = !empty($settings['arrows_position']) ? $settings['arrows_position'] : 'outside';
        $show_dots      = (!empty($settings['show_dots']) && 'yes' === $settings['show_dots']);

        $slider_config = array(
            'slidesPerView' => $slides_desktop,
            'spaceBetween'  => $space_between,
            'loop'          => $loop,
            'autoplay'      => $autoplay ? array(
                'delay'                => $autoplay_speed,
                'disableOnInteraction' => false,
                'pauseOnMouseEnter'    => $pause_hover,
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

        $wrapper_classes = array(
            'obwk-category-showcase',
            'obwk-showcase-layout-' . sanitize_html_class($layout),
            'obwk-showcase-skin-' . sanitize_html_class($skin),
            'obwk-showcase-aspect-' . sanitize_html_class($aspect_ratio),
            'obwk-arrows-pos-' . sanitize_html_class($arrows_pos),
        );

        if ('grid' === $layout) {
            $wrapper_classes[] = 'obwk-grid-cols-' . $columns_desktop;
            $wrapper_classes[] = 'obwk-grid-tablet-' . $columns_tablet;
            $wrapper_classes[] = 'obwk-grid-mobile-' . $columns_mobile;
        }

        ob_start();
        ?>
        <div class="<?php echo esc_attr(implode(' ', $wrapper_classes)); ?>" data-slider-config="<?php echo esc_attr(wp_json_encode($slider_config)); ?>">
            
            <?php if ('slider' === $layout) : ?>
                <div class="swiper obwk-category-swiper">
                    <div class="swiper-wrapper">
            <?php else : ?>
                <div class="obwk-category-grid">
            <?php endif; ?>

                <?php foreach ($terms as $term) :
                    $term_id      = $term->term_id;
                    $term_name    = $term->name;
                    $term_link    = get_term_link($term);
                    $product_count = $term->count;
                    $thumbnail_id = get_term_meta($term_id, 'thumbnail_id', true);
                    $image_url    = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, $image_size) : wc_placeholder_img_src($image_size);

                    $count_label = str_replace('{count}', number_format_i18n($product_count), $count_format);
                    $card_classes = array(
                        'obwk-category-card',
                        'obwk-card-skin-' . sanitize_html_class($skin),
                    );
                ?>
                    <?php if ('slider' === $layout) : ?>
                        <div class="swiper-slide">
                    <?php endif; ?>

                        <div class="<?php echo esc_attr(implode(' ', $card_classes)); ?>">
                            <a href="<?php echo esc_url($term_link); ?>" class="obwk-category-card-link" aria-label="<?php echo esc_attr($term_name); ?>">
                                
                                <div class="obwk-category-img-wrap">
                                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($term_name); ?>" loading="lazy" class="obwk-category-img" />
                                    <?php if ('overlay' === $skin) : ?>
                                        <div class="obwk-category-overlay"></div>
                                    <?php endif; ?>

                                    <?php if ('circle' !== $skin && 'classic' !== $skin && $show_count) : ?>
                                        <span class="obwk-category-count obwk-count-badge"><?php echo esc_html($count_label); ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="obwk-category-info">
                                    <h3 class="obwk-category-title"><?php echo esc_html($term_name); ?></h3>
                                    
                                    <?php if (('classic' === $skin || 'circle' === $skin) && $show_count) : ?>
                                        <span class="obwk-category-count"><?php echo esc_html($count_label); ?></span>
                                    <?php endif; ?>

                                    <?php if ($show_button) : ?>
                                        <span class="obwk-category-btn">
                                            <?php echo esc_html($button_text); ?>
                                            <svg class="obwk-btn-arrow" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                                        </span>
                                    <?php endif; ?>
                                </div>

                            </a>
                        </div>

                    <?php if ('slider' === $layout) : ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>

            <?php if ('slider' === $layout) : ?>
                    </div>
                </div>

                <?php if ($show_arrows) : ?>
                    <button type="button" class="obwk-swiper-arrow obwk-swiper-prev" aria-label="<?php esc_attr_e('Previous Categories', 'optimus-bytes-woo-kit'); ?>">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                    </button>
                    <button type="button" class="obwk-swiper-arrow obwk-swiper-next" aria-label="<?php esc_attr_e('Next Categories', 'optimus-bytes-woo-kit'); ?>">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </button>
                <?php endif; ?>

                <?php if ($show_dots) : ?>
                    <div class="swiper-pagination obwk-swiper-pagination"></div>
                <?php endif; ?>

            <?php else : ?>
                </div>
            <?php endif; ?>

        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render Widget output on frontend & Elementor editor
     */
    protected function render() {
        $settings = $this->get_settings_for_display();
        echo self::render_showcase_html($settings);
    }
}
