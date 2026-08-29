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
        $this->description = __('Visual color swatch filter for shop and archive sidebars with category-aware product counts, multi-select, and full query parameter retention.', 'optimus-bytes-woo-kit');
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

        // Hide Empty Terms
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[color_filter_hide_empty]', array(
            'type'              => 'option',
            'default'           => true,
            'sanitize_callback' => 'wp_validate_boolean',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[color_filter_hide_empty]', array(
            'label'       => __('Hide colors with 0 products in current filtered view', 'optimus-bytes-woo-kit'),
            'description' => __('Only shows colors that match the current category, stock status, and active filters.', 'optimus-bytes-woo-kit'),
            'section'     => $section_id,
            'type'        => 'checkbox',
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
     * Extract active taxonomy filters from $_GET query parameters (e.g. filter_size=30ml, filter_fabric=silk)
     *
     * @param string $exclude_taxonomy (Taxonomy to exclude, e.g. pa_color)
     * @return array (taxonomy => array of term_slugs)
     */
    public static function get_active_other_filters($exclude_taxonomy = 'pa_color') {
        $other_filters = array();

        if (empty($_GET) || !is_array($_GET)) {
            return $other_filters;
        }

        foreach ($_GET as $key => $value) {
            if (0 === strpos($key, 'filter_')) {
                $raw_tax = str_replace('filter_', '', $key);
                
                // Exclude stock status and current color taxonomy
                if ('stock_status' === $raw_tax || 'on_sale' === $raw_tax) {
                    continue;
                }

                $tax_name = (0 === strpos($raw_tax, 'pa_')) ? $raw_tax : 'pa_' . $raw_tax;

                if ($tax_name === $exclude_taxonomy) {
                    continue;
                }

                if (taxonomy_exists($tax_name)) {
                    $slugs = explode(',', sanitize_text_field(wp_unslash($value)));
                    $slugs = array_filter(array_map('sanitize_title', $slugs));
                    if (!empty($slugs)) {
                        $other_filters[$tax_name] = $slugs;
                    }
                }
            }
        }

        return $other_filters;
    }

    /**
     * Calculate accurate filtered term product counts based on Category, Subcategories, Stock Status, and all active URL filters
     *
     * @param array $terms
     * @param string $taxonomy
     * @param string $query_type
     * @return array (term_id => int count)
     */
    public static function get_contextual_term_counts($terms, $taxonomy, $query_type = 'or') {
        if (empty($terms)) {
            return array();
        }

        $term_ids = wp_list_pluck($terms, 'term_id');
        $counts   = array();

        // 1. If WooCommerce core layered nav counts are available and accurate
        if (function_exists('wc_get_filtered_term_product_counts')) {
            $wc_counts = wc_get_filtered_term_product_counts($term_ids, $taxonomy, $query_type);
            if (!empty($wc_counts) && is_array($wc_counts)) {
                return $wc_counts;
            }
        }

        // 2. Comprehensive SQL Query respecting Categories, Stock Status, Attributes, Prices
        global $wpdb;
        $current_obj   = get_queried_object();
        $other_filters = self::get_active_other_filters($taxonomy);

        // Resolve Category Term IDs (from queried object or ?product_cat / ?category / ?categories)
        $cat_ids = array();
        if ($current_obj instanceof \WP_Term && 'product_cat' === $current_obj->taxonomy) {
            $cat_ids[] = (int) $current_obj->term_id;
        }

        // Check GET category parameters (?categories=eyes, ?category=eyes, ?product_cat=eyes)
        $cat_get_keys = array('product_cat', 'category', 'categories');
        foreach ($cat_get_keys as $ckey) {
            if (!empty($_GET[$ckey])) {
                $cat_slugs = explode(',', sanitize_text_field(wp_unslash($_GET[$ckey])));
                foreach ($cat_slugs as $cslug) {
                    $cslug = sanitize_title(trim($cslug));
                    if (!empty($cslug)) {
                        $c_term = get_term_by('slug', $cslug, 'product_cat');
                        if ($c_term) {
                            $cat_ids[] = (int) $c_term->term_id;
                        }
                    }
                }
            }
        }

        // Expand category hierarchy (include all subcategories)
        $all_cat_ids = array();
        foreach ($cat_ids as $cid) {
            $all_cat_ids[] = $cid;
            $children = get_term_children($cid, 'product_cat');
            if (!empty($children) && !is_wp_error($children)) {
                $all_cat_ids = array_merge($all_cat_ids, $children);
            }
        }
        $all_cat_ids = array_unique($all_cat_ids);
        $cat_ids_str = !empty($all_cat_ids) ? implode(',', array_map('intval', $all_cat_ids)) : '';

        // Stock status filter (e.g. ?filter_stock_status=instock)
        $stock_filter = '';
        if (!empty($_GET['filter_stock_status'])) {
            $raw_stock = sanitize_text_field(wp_unslash($_GET['filter_stock_status']));
            $allowed_stocks = array('instock', 'outofstock', 'onbackorder');
            $stocks = explode(',', $raw_stock);
            $valid_stocks = array_intersect($stocks, $allowed_stocks);
            if (!empty($valid_stocks)) {
                $stock_filter = "'" . implode("','", array_map('esc_sql', $valid_stocks)) . "'";
            }
        }

        // Price filters
        $min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
        $max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 0;

        // Build other filter taxonomy joins
        $other_joins = '';
        $other_where = '';
        $other_var_where = '';
        $idx = 0;

        foreach ($other_filters as $other_tax => $slugs) {
            $idx++;
            $slugs_in = "'" . implode("','", array_map('esc_sql', $slugs)) . "'";
            
            $other_joins .= "
                INNER JOIN {$wpdb->term_relationships} tr_other_{$idx} ON (p.ID = tr_other_{$idx}.object_id)
                INNER JOIN {$wpdb->term_taxonomy} tt_other_{$idx} ON (tr_other_{$idx}.term_taxonomy_id = tt_other_{$idx}.term_taxonomy_id AND tt_other_{$idx}.taxonomy = '{$other_tax}')
                INNER JOIN {$wpdb->terms} t_other_{$idx} ON (tt_other_{$idx}.term_id = t_other_{$idx}.term_id)
            ";
            $other_where .= " AND t_other_{$idx}.slug IN ({$slugs_in}) ";

            $meta_key_tax = 'attribute_' . $other_tax;
            $other_var_where .= "
                AND EXISTS (
                    SELECT 1 FROM {$wpdb->postmeta} pm_other_{$idx} 
                    WHERE pm_other_{$idx}.post_id = p.ID 
                    AND pm_other_{$idx}.meta_key = '{$meta_key_tax}' 
                    AND (pm_other_{$idx}.meta_value IN ({$slugs_in}) OR pm_other_{$idx}.meta_value = '')
                )
            ";
        }

        // Check if WooCommerce lookup table exists
        $lookup_table = $wpdb->prefix . 'wc_product_meta_lookup';
        $has_lookup = (bool) $wpdb->get_var("SHOW TABLES LIKE '{$lookup_table}'");

        foreach ($terms as $term) {
            $cat_join_clause  = "";
            $cat_where_clause = "";

            if (!empty($cat_ids_str)) {
                $cat_join_clause = "
                    INNER JOIN {$wpdb->term_relationships} tr_cat ON (p.ID = tr_cat.object_id)
                    INNER JOIN {$wpdb->term_taxonomy} tt_cat ON (tr_cat.term_taxonomy_id = tt_cat.term_taxonomy_id AND tt_cat.taxonomy = 'product_cat')
                ";
                $cat_where_clause = " AND tt_cat.term_id IN ({$cat_ids_str}) ";
            }

            $stock_join = "";
            $stock_where = "";
            $price_join = "";
            $price_where = "";

            if ($has_lookup) {
                $stock_join = " INNER JOIN {$lookup_table} ml ON (p.ID = ml.product_id) ";
                if (!empty($stock_filter)) {
                    $stock_where = " AND ml.stock_status IN ({$stock_filter}) ";
                }
                if ($min_price > 0) {
                    $price_where .= " AND ml.min_price >= {$min_price} ";
                }
                if ($max_price > 0) {
                    $price_where .= " AND ml.max_price <= {$max_price} ";
                }
            } else {
                if (!empty($stock_filter)) {
                    $stock_join = " INNER JOIN {$wpdb->postmeta} pm_stock ON (p.ID = pm_stock.post_id AND pm_stock.meta_key = '_stock_status') ";
                    $stock_where = " AND pm_stock.meta_value IN ({$stock_filter}) ";
                }
                if ($min_price > 0 || $max_price > 0) {
                    $price_join = " INNER JOIN {$wpdb->postmeta} pm_price ON (p.ID = pm_price.post_id AND pm_price.meta_key = '_price') ";
                    if ($min_price > 0) {
                        $price_where .= " AND CAST(pm_price.meta_value AS DECIMAL(10,2)) >= {$min_price} ";
                    }
                    if ($max_price > 0) {
                        $price_where .= " AND CAST(pm_price.meta_value AS DECIMAL(10,2)) <= {$max_price} ";
                    }
                }
            }

            // Query 1: Simple products matching this color + stock + category + other filters
            $sql = "
                SELECT COUNT(DISTINCT p.ID) 
                FROM {$wpdb->posts} p
                {$cat_join_clause}
                INNER JOIN {$wpdb->term_relationships} tr_attr ON (p.ID = tr_attr.object_id)
                {$other_joins}
                {$stock_join}
                {$price_join}
                WHERE p.post_type IN ('product')
                AND p.post_status = 'publish'
                {$cat_where_clause}
                AND tr_attr.term_taxonomy_id = %d
                {$other_where}
                {$stock_where}
                {$price_where}
            ";
            $count = (int) $wpdb->get_var($wpdb->prepare($sql, $term->term_taxonomy_id));

            // Query 2: Variable product variations matching this color + stock + category + other filters
            $var_cat_join = "";
            $var_cat_where = "";
            if (!empty($cat_ids_str)) {
                $var_cat_join = "
                    INNER JOIN {$wpdb->term_relationships} tr_cat ON (p.post_parent = tr_cat.object_id)
                    INNER JOIN {$wpdb->term_taxonomy} tt_cat ON (tr_cat.term_taxonomy_id = tt_cat.term_taxonomy_id AND tt_cat.taxonomy = 'product_cat')
                ";
                $var_cat_where = " AND tt_cat.term_id IN ({$cat_ids_str}) ";
            }

            $var_stock_where = "";
            if (!empty($stock_filter)) {
                $var_stock_where = " 
                    AND EXISTS (
                        SELECT 1 FROM {$wpdb->postmeta} pm_vstock 
                        WHERE pm_vstock.post_id = p.ID 
                        AND pm_vstock.meta_key = '_stock_status' 
                        AND pm_vstock.meta_value IN ({$stock_filter})
                    )
                ";
            }

            $var_sql = "
                SELECT COUNT(DISTINCT p.post_parent) 
                FROM {$wpdb->posts} p
                {$var_cat_join}
                INNER JOIN {$wpdb->postmeta} pm_color ON (p.ID = pm_color.post_id AND pm_color.meta_key = %s)
                WHERE p.post_type = 'product_variation'
                AND p.post_status = 'publish'
                {$var_cat_where}
                AND (pm_color.meta_value = %s OR pm_color.meta_value = '')
                {$other_var_where}
                {$var_stock_where}
            ";
            $var_count = (int) $wpdb->get_var($wpdb->prepare($var_sql, 'attribute_' . $taxonomy, $term->slug));

            $counts[$term->term_id] = max($count, $var_count);
        }

        return $counts;
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
            'hide_empty' => (bool) $this->get_option('hide_empty', true),
            'show_clear' => (bool) $this->get_option('show_clear', true),
        ), $atts, 'obwk_color_filter');

        ob_start();
        self::render_filter_html($atts);
        return ob_get_clean();
    }

    /**
     * Helper to render Color Filter HTML with contextual counts and 100% query parameter retention
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
            'hide_empty' => false,
        ));

        if (empty($terms) || is_wp_error($terms)) {
            return;
        }

        $layout     = !empty($args['layout']) ? $args['layout'] : 'grid';
        $shape      = !empty($args['shape']) ? $args['shape'] : 'rounded';
        $show_count = isset($args['show_count']) ? (bool) $args['show_count'] : true;
        $hide_empty = isset($args['hide_empty']) ? (bool) $args['hide_empty'] : true;
        $show_clear = isset($args['show_clear']) ? (bool) $args['show_clear'] : true;
        $title      = !empty($args['title']) ? $args['title'] : '';

        // Calculate contextual counts for current category and all active query filters
        $counts = self::get_contextual_term_counts($terms, $attribute_name, 'or');

        // Clean taxonomy slug without 'pa_' (e.g. 'color')
        $clean_attr_name = sanitize_title(str_replace('pa_', '', $attribute_name));
        $param_key       = 'filter_' . $clean_attr_name;
        $query_type_key  = 'query_type_' . $clean_attr_name;

        // Determine currently active filters for this specific attribute
        $current_color_filter = isset($_GET[$param_key]) ? explode(',', sanitize_text_field(wp_unslash($_GET[$param_key]))) : array();
        $current_color_filter = array_filter(array_map('sanitize_title', $current_color_filter));

        // Get clean base URL without query parameters
        global $wp;
        if (!empty($wp->request)) {
            $base_url = home_url($wp->request);
        } else {
            $raw_uri  = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
            $base_url = strtok($raw_uri, '?');
        }

        // Clone all active GET params while omitting pagination
        $current_all_params = !empty($_GET) && is_array($_GET) ? wp_unslash($_GET) : array();
        unset($current_all_params['paged']);

        ?>
        <div class="obwk-color-filter-widget obwk-filter-layout-<?php echo esc_attr($layout); ?> obwk-filter-shape-<?php echo esc_attr($shape); ?>">
            <?php if (!empty($title)) : ?>
                <h3 class="obwk-filter-title"><?php echo esc_html($title); ?></h3>
            <?php endif; ?>

            <?php if ($show_clear && !empty($current_color_filter)) : 
                // Clear URL retains all other filters (e.g. filter_stock_status, categories, etc.)
                $clear_params = $current_all_params;
                unset($clear_params[$param_key]);
                unset($clear_params[$query_type_key]);
                $clear_url = !empty($clear_params) ? add_query_arg($clear_params, $base_url) : $base_url;
            ?>
                <div class="obwk-filter-clear-wrap">
                    <a href="<?php echo esc_url($clear_url); ?>" class="obwk-filter-clear-btn">
                        ✕ <?php esc_html_e('Clear Color Filter', 'optimus-bytes-woo-kit'); ?>
                    </a>
                </div>
            <?php endif; ?>

            <ul class="obwk-color-filter-list">
                <?php foreach ($terms as $term) : 
                    $term_id     = $term->term_id;
                    $term_slug   = $term->slug;
                    $term_name   = $term->name;
                    $term_count  = isset($counts[$term_id]) ? (int) $counts[$term_id] : 0;
                    $is_active   = in_array($term_slug, $current_color_filter, true);

                    // Skip empty colors in current filter context if hide_empty is enabled and not active
                    if ($hide_empty && $term_count === 0 && !$is_active) {
                        continue;
                    }

                    $color_hex   = class_exists('OptimusBytes\WooKit\Modules\Variation_Swatches\Variation_Swatches_Module') 
                        ? Variation_Swatches_Module::resolve_color_hex($term) 
                        : '#a46d35';

                    // Build toggle URL while preserving ALL existing query parameters (categories, filter_stock_status, filter_size, etc.)
                    $new_color_filter = $current_color_filter;
                    if ($is_active) {
                        $new_color_filter = array_diff($new_color_filter, array($term_slug));
                    } else {
                        $new_color_filter[] = $term_slug;
                    }

                    $link_params = $current_all_params;
                    if (!empty($new_color_filter)) {
                        $link_params[$param_key]      = implode(',', $new_color_filter);
                        $link_params[$query_type_key] = 'or';
                    } else {
                        unset($link_params[$param_key]);
                        unset($link_params[$query_type_key]);
                    }

                    $link_url = !empty($link_params) ? add_query_arg($link_params, $base_url) : $base_url;
                ?>
                    <li class="obwk-filter-item <?php echo $is_active ? 'is-active' : ''; ?> <?php echo ($term_count === 0) ? 'is-count-zero' : ''; ?>">
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
            $hide_empty = isset($instance['hide_empty']) ? (bool) $instance['hide_empty'] : true;
            $show_clear = isset($instance['show_clear']) ? (bool) $instance['show_clear'] : true;

            Color_Filter_Module::render_filter_html(array(
                'title'      => $title,
                'attribute'  => $attribute,
                'layout'     => $layout,
                'shape'      => $shape,
                'show_count' => $show_count,
                'hide_empty' => $hide_empty,
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
            $hide_empty = isset($instance['hide_empty']) ? (bool) $instance['hide_empty'] : true;
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
                <input class="checkbox" type="checkbox" <?php checked($hide_empty); ?> id="<?php echo esc_attr($this->get_field_id('hide_empty')); ?>" name="<?php echo esc_attr($this->get_field_name('hide_empty')); ?>" />
                <label for="<?php echo esc_attr($this->get_field_id('hide_empty')); ?>"><?php esc_html_e('Hide colors with 0 products in category', 'optimus-bytes-woo-kit'); ?></label>
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
            $instance['hide_empty'] = isset($new_instance['hide_empty']) ? (bool) $new_instance['hide_empty'] : false;
            $instance['show_clear'] = isset($new_instance['show_clear']) ? (bool) $new_instance['show_clear'] : false;
            return $instance;
        }
    }
}
