<?php
/**
 * Stock Scarcity & Urgency Bar Module
 *
 * @package OptimusBytes\WooKit\Modules\Stock_Scarcity
 */

namespace OptimusBytes\WooKit\Modules\Stock_Scarcity;

use OptimusBytes\WooKit\Modules\Abstract_Module;

defined('ABSPATH') || exit;

class Stock_Scarcity_Module extends Abstract_Module {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id          = 'stock_scarcity';
        $this->title       = __('Stock Scarcity & Urgency Bar', 'optimus-bytes-woo-kit');
        $this->description = __('Boost buyer conversions with real-time low-stock alerts, an animated inventory progress bar, and instant variation swatch switching.', 'optimus-bytes-woo-kit');
        $this->icon        = '🔥';
        $this->category    = __('Conversions & Merchandising', 'optimus-bytes-woo-kit');
    }

    /**
     * Initialize module hooks
     */
    public function init() {
        // Register Customizer settings
        add_action('customize_register', array($this, 'register_customizer_settings'));

        // Product Loop Scarcity Display (Shop page, Categories, Archives, Tabs)
        add_filter('woocommerce_get_price_html', array($this, 'append_loop_scarcity_to_price'), 30, 2);

        // Provide variation stock metadata to frontend JavaScript for live swatch switching
        add_filter('woocommerce_available_variation', array($this, 'add_variation_stock_data'), 10, 3);

        // Enqueue Frontend Assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Setup frontend hooks on 'wp' action where Customizer preview filters are fully active
        add_action('wp', array($this, 'setup_frontend_hooks'));
    }

    /**
     * Setup Frontend Single Product Hooks
     *
     * Runs on 'wp' action where:
     * 1. WP_Customize_Manager has initialized preview filters on options.
     * 2. Live preview values for module toggle and positions reflect immediately.
     */
    public function setup_frontend_hooks() {
        if (is_admin()) {
            return;
        }

        if (!$this->is_enabled()) {
            return;
        }

        // Single Product Scarcity Bar Hook
        $position = $this->get_option('position', 'before_cart_button');
        switch ($position) {
            case 'after_price':
                // Directly below woocommerce_template_single_price (priority 10)
                add_action('woocommerce_single_product_summary', array($this, 'render_stock_scarcity_bar'), 12);
                break;
            case 'before_cart_form':
                // Above add-to-cart form and variation swatches
                add_action('woocommerce_before_add_to_cart_form', array($this, 'render_stock_scarcity_bar'), 10);
                break;
            case 'after_cart_button':
                // Below add-to-cart button
                add_action('woocommerce_after_add_to_cart_button', array($this, 'render_stock_scarcity_bar'), 15);
                break;
            case 'after_cart_form':
                // Below add-to-cart form
                add_action('woocommerce_after_add_to_cart_form', array($this, 'render_stock_scarcity_bar'), 20);
                break;
            case 'before_cart_button':
            default:
                // Above add-to-cart button
                add_action('woocommerce_before_add_to_cart_button', array($this, 'render_stock_scarcity_bar'), 15);
                break;
        }
    }

    /**
     * Enqueue Frontend Assets
     */
    public function enqueue_scripts() {
        if (is_admin() || !$this->is_enabled()) {
            return;
        }

        wp_enqueue_style(
            'obwk-stock-scarcity',
            OBWK_PLUGIN_URL . 'assets/css/stock-scarcity.css',
            array(),
            OBWK_VERSION
        );

        $is_preview = function_exists('is_customize_preview') && is_customize_preview();
        $is_prod    = function_exists('is_product') && is_product();

        if ($is_prod || $is_preview) {
            wp_enqueue_script(
                'obwk-stock-scarcity',
                OBWK_PLUGIN_URL . 'assets/js/stock-scarcity.js',
                array('jquery'),
                OBWK_VERSION,
                true
            );

            // Pass settings to JS for live variation recalculation
            wp_localize_script('obwk-stock-scarcity', 'obwkStockScarcity', array(
                'display_levels'       => $this->get_option('display_levels', 'all_levels'),
                'display_style'        => $this->get_option('display_style', 'full_card'),
                'show_unmanaged_stock' => (bool) $this->get_option('show_unmanaged_stock', false),
                'threshold'            => (int) $this->get_option('threshold', 5),
                'medium_threshold'     => (int) $this->get_option('medium_threshold', 15),
                'initial_qty'          => (int) $this->get_option('initial_qty', 20),
                'msg_template'         => $this->get_low_stock_message(),
                'single_item_msg'      => $this->get_single_item_message(),
                'medium_msg'           => $this->get_medium_stock_message(),
                'in_stock_msg'         => $this->get_in_stock_message(),
                'show_progress_bar'    => (bool) $this->get_option('show_progress_bar', true),
            ));
        }

        // Inject custom tier colors (Text, Background, Border) and bar styling
        $custom_css = $this->get_custom_colors_css();
        if (!empty($custom_css)) {
            wp_add_inline_style('obwk-stock-scarcity', $custom_css);
        }
    }

    /**
     * Generate custom CSS for configured tier colors (Text, Background, Border)
     *
     * @return string
     */
    public function get_custom_colors_css() {
        $custom_css = '';

        // 1. In Stock (Green / High)
        $in_stock_text   = $this->get_option('in_stock_text_color', '');
        $in_stock_bg     = $this->get_option('in_stock_bg_color', '');
        $in_stock_border = $this->get_option('in_stock_border_color', '');

        if (!empty($in_stock_text)) {
            $custom_css .= ".obwk-stock-scarcity-wrap.is-high .obwk-stock-message, .obwk-stock-scarcity-wrap.is-high .obwk-stock-number, .obwk-loop-scarcity-badge.is-high, .obwk-loop-scarcity-badge.is-high .obwk-status-text, .obwk-loop-scarcity-badge.is-high .obwk-stock-number { color: " . esc_attr($in_stock_text) . " !important; }";
            $custom_css .= ".obwk-stock-pulse-dot.is-high, .obwk-stock-scarcity-wrap.is-high .obwk-stock-pulse-dot { background-color: " . esc_attr($in_stock_text) . " !important; }";
            $custom_css .= ".obwk-stock-pulse-dot.is-high::after, .obwk-stock-scarcity-wrap.is-high .obwk-stock-pulse-dot::after { background-color: " . esc_attr($in_stock_text) . " !important; opacity: 0.4; }";
        }
        if (!empty($in_stock_bg)) {
            $custom_css .= ".obwk-stock-scarcity-wrap.is-high.obwk-style-card, .obwk-loop-scarcity-badge.is-high { background: " . esc_attr($in_stock_bg) . " !important; }";
        }
        if (!empty($in_stock_border)) {
            $custom_css .= ".obwk-stock-scarcity-wrap.is-high.obwk-style-card, .obwk-loop-scarcity-badge.is-high { border-color: " . esc_attr($in_stock_border) . " !important; }";
        }

        // 2. Medium Stock (Orange / Amber)
        $medium_text   = $this->get_option('medium_text_color', '');
        $medium_bg     = $this->get_option('medium_bg_color', '');
        $medium_border = $this->get_option('medium_border_color', '');

        if (!empty($medium_text)) {
            $custom_css .= ".obwk-stock-scarcity-wrap.is-medium .obwk-stock-message, .obwk-stock-scarcity-wrap.is-medium .obwk-stock-number, .obwk-loop-scarcity-badge.is-medium, .obwk-loop-scarcity-badge.is-medium .obwk-status-text, .obwk-loop-scarcity-badge.is-medium .obwk-stock-number { color: " . esc_attr($medium_text) . " !important; }";
            $custom_css .= ".obwk-stock-pulse-dot.is-medium, .obwk-stock-scarcity-wrap.is-medium .obwk-stock-pulse-dot { background-color: " . esc_attr($medium_text) . " !important; }";
            $custom_css .= ".obwk-stock-pulse-dot.is-medium::after, .obwk-stock-scarcity-wrap.is-medium .obwk-stock-pulse-dot::after { background-color: " . esc_attr($medium_text) . " !important; opacity: 0.4; }";
        }
        if (!empty($medium_bg)) {
            $custom_css .= ".obwk-stock-scarcity-wrap.is-medium.obwk-style-card, .obwk-loop-scarcity-badge.is-medium { background: " . esc_attr($medium_bg) . " !important; }";
        }
        if (!empty($medium_border)) {
            $custom_css .= ".obwk-stock-scarcity-wrap.is-medium.obwk-style-card, .obwk-loop-scarcity-badge.is-medium { border-color: " . esc_attr($medium_border) . " !important; }";
        }

        // 3. Low Stock Urgency (Red / Coral)
        $low_text   = $this->get_option('low_text_color', '');
        $low_bg     = $this->get_option('low_bg_color', '');
        $low_border = $this->get_option('low_border_color', '');

        if (!empty($low_text)) {
            $custom_css .= ".obwk-stock-scarcity-wrap.is-low .obwk-stock-message, .obwk-stock-scarcity-wrap.is-low .obwk-stock-number, .obwk-stock-scarcity-wrap.is-critical .obwk-stock-message, .obwk-stock-scarcity-wrap.is-critical .obwk-stock-number, .obwk-loop-scarcity-badge.is-low, .obwk-loop-scarcity-badge.is-low .obwk-status-text, .obwk-loop-scarcity-badge.is-low .obwk-stock-number, .obwk-loop-scarcity-badge.is-critical, .obwk-loop-scarcity-badge.is-critical .obwk-status-text, .obwk-loop-scarcity-badge.is-critical .obwk-stock-number { color: " . esc_attr($low_text) . " !important; }";
            $custom_css .= ".obwk-stock-pulse-dot.is-low, .obwk-stock-scarcity-wrap.is-low .obwk-stock-pulse-dot, .obwk-stock-pulse-dot.is-critical, .obwk-stock-scarcity-wrap.is-critical .obwk-stock-pulse-dot { background-color: " . esc_attr($low_text) . " !important; }";
            $custom_css .= ".obwk-stock-pulse-dot.is-low::after, .obwk-stock-scarcity-wrap.is-low .obwk-stock-pulse-dot::after, .obwk-stock-pulse-dot.is-critical::after, .obwk-stock-scarcity-wrap.is-critical .obwk-stock-pulse-dot::after { background-color: " . esc_attr($low_text) . " !important; opacity: 0.4; }";
        }
        if (!empty($low_bg)) {
            $custom_css .= ".obwk-stock-scarcity-wrap.is-low.obwk-style-card, .obwk-stock-scarcity-wrap.is-critical.obwk-style-card, .obwk-loop-scarcity-badge.is-low, .obwk-loop-scarcity-badge.is-critical { background: " . esc_attr($low_bg) . " !important; }";
        }
        if (!empty($low_border)) {
            $custom_css .= ".obwk-stock-scarcity-wrap.is-low.obwk-style-card, .obwk-stock-scarcity-wrap.is-critical.obwk-style-card, .obwk-loop-scarcity-badge.is-low, .obwk-loop-scarcity-badge.is-critical { border-color: " . esc_attr($low_border) . " !important; }";
        }

        // 4. Custom Progress Bar Fill Color Override
        $bar_color = $this->get_option('bar_color', '');
        if (!empty($bar_color)) {
            $custom_css .= ".obwk-stock-progress-fill { background: " . esc_attr($bar_color) . " !important; }";
        }

        return $custom_css;
    }

    /**
     * Append stock metadata to WooCommerce variation JSON payload
     *
     * @param array $data
     * @param \WC_Product_Variable $product
     * @param \WC_Product_Variation $variation
     * @return array
     */
    public function add_variation_stock_data($data, $product, $variation) {
        $mode = $this->get_option('mode', 'real');

        if ('simulated' === $mode) {
            $sim_stock = (($variation->get_id() % 20) + 2);
            $data['obwk_stock_qty'] = $sim_stock;
            $data['obwk_manage_stock'] = true;
        } else {
            if ($variation->managing_stock()) {
                $data['obwk_stock_qty'] = (int) $variation->get_stock_quantity();
                $data['obwk_manage_stock'] = true;
            } elseif ($product->managing_stock()) {
                $data['obwk_stock_qty'] = (int) $product->get_stock_quantity();
                $data['obwk_manage_stock'] = true;
            } else {
                $data['obwk_stock_qty'] = null;
                $data['obwk_manage_stock'] = false;
            }
        }

        return $data;
    }

    /**
     * Calculate stock scarcity & tier level data for a product
     *
     * Supports:
     * - 'high': Green (In Stock, plenty)
     * - 'medium': Orange / Amber (Limited stock)
     * - 'low': Red (Low stock urgency)
     *
     * @param \WC_Product $product
     * @return array
     */
    public function get_product_scarcity_data($product) {
        $mode             = $this->get_option('mode', 'real');
        $display_levels   = $this->get_option('display_levels', 'all_levels');
        $show_unmanaged   = (bool) $this->get_option('show_unmanaged_stock', false);
        $threshold        = (int) $this->get_option('threshold', 5);
        $medium_threshold = (int) $this->get_option('medium_threshold', 15);
        if ($medium_threshold <= $threshold) {
            $medium_threshold = $threshold + 10;
        }
        $initial_qty      = (int) $this->get_option('initial_qty', 20);
        if ($initial_qty <= 0) {
            $initial_qty = max(20, $medium_threshold);
        }

        $stock_qty   = null;
        $is_managed  = false;
        $should_show = false;
        $is_variable = $product->is_type('variable');

        if ('simulated' === $mode) {
            // Stable deterministic simulated stock between 2 and 22 based on product ID
            $stock_qty   = (($product->get_id() % 20) + 2);
            $is_managed  = true;
            $should_show = $product->is_in_stock();
        } else {
            if ($is_variable) {
                if ($product->managing_stock()) {
                    $stock_qty   = (int) $product->get_stock_quantity();
                    $is_managed  = true;
                    $should_show = ($stock_qty > 0);
                } else {
                    $lowest_var_stock = null;
                    if (method_exists($product, 'get_children')) {
                        $children = $product->get_children();
                        if (!empty($children)) {
                            foreach ($children as $child_id) {
                                $var_obj = wc_get_product($child_id);
                                if ($var_obj && $var_obj->is_in_stock() && $var_obj->managing_stock()) {
                                    $v_qty = (int) $var_obj->get_stock_quantity();
                                    if ($v_qty > 0 && ($lowest_var_stock === null || $v_qty < $lowest_var_stock)) {
                                        $lowest_var_stock = $v_qty;
                                    }
                                }
                            }
                        }
                    }

                    if ($lowest_var_stock !== null) {
                        $stock_qty   = $lowest_var_stock;
                        $is_managed  = true;
                        $should_show = ($stock_qty > 0);
                    } else {
                        // Unmanaged variations
                        $stock_qty   = null;
                        $is_managed  = false;
                        $should_show = $show_unmanaged && ('all_levels' === $display_levels) && function_exists('is_product') && is_product() && $product->is_in_stock();
                    }
                }
            } else {
                if ($product->managing_stock()) {
                    $stock_qty   = (int) $product->get_stock_quantity();
                    $is_managed  = true;
                    $should_show = ($stock_qty > 0);
                } else {
                    // Unmanaged stock but in stock
                    $stock_qty   = null;
                    $is_managed  = false;
                    $should_show = $show_unmanaged && ('all_levels' === $display_levels) && $product->is_in_stock();
                }
            }
        }

        // Determine stock tier level: 'low' (red), 'medium' (orange), 'high' (green)
        $level = 'high';
        if ($stock_qty !== null) {
            if ($stock_qty <= $threshold) {
                $level = 'low';
            } elseif ($stock_qty <= $medium_threshold) {
                $level = 'medium';
            } else {
                $level = 'high';
            }
        } elseif (!$is_managed && $product->is_in_stock()) {
            $level = 'high';
            if (!$show_unmanaged) {
                $should_show = false;
            }
        }

        // If low_only mode is chosen, only show when level is 'low'
        if ('low_only' === $display_levels && 'low' !== $level) {
            $should_show = false;
        }

        $percent = 100;
        if ($stock_qty !== null) {
            $base_max = max($initial_qty, $stock_qty);
            $percent  = min(100, max(8, round(($stock_qty / $base_max) * 100)));
        }

        return array(
            'stock'       => $stock_qty,
            'level'       => $level, // 'high' (green), 'medium' (orange), 'low' (red)
            'percent'     => $percent,
            'should_show' => $should_show,
            'is_variable' => $is_variable,
            'is_critical' => ($stock_qty !== null && $stock_qty <= 2),
            'is_managed'  => $is_managed,
        );
    }

    /**
     * Get In Stock Message Template (Green)
     *
     * @return string
     */
    public function get_in_stock_message() {
        $msg = $this->get_option('in_stock_message', '');
        return (!empty($msg) && trim($msg) !== '') ? $msg : __('✅ In Stock ({stock} available)', 'optimus-bytes-woo-kit');
    }

    /**
     * Get Medium Stock Message Template (Orange)
     *
     * @return string
     */
    public function get_medium_stock_message() {
        $msg = $this->get_option('medium_message', '');
        if (empty($msg) || trim($msg) === '') {
            $msg = $this->get_option('medium_stock_message', '');
        }
        return (!empty($msg) && trim($msg) !== '') ? $msg : __('⚡ Limited stock: Only {stock} left!', 'optimus-bytes-woo-kit');
    }

    /**
     * Get Low Stock Urgency Message Template (Red)
     *
     * @return string
     */
    public function get_low_stock_message() {
        $msg = $this->get_option('message', '');
        if (empty($msg) || trim($msg) === '') {
            $msg = $this->get_option('low_stock_message', '');
        }
        if (empty($msg) || trim($msg) === '') {
            $msg = $this->get_option('low_message', '');
        }
        return (!empty($msg) && trim($msg) !== '') ? $msg : __('🔥 Hurry! Only {stock} items left in stock!', 'optimus-bytes-woo-kit');
    }

    /**
     * Get Single Item Remaining Message Template (Critical Red)
     *
     * @return string
     */
    public function get_single_item_message() {
        $msg = $this->get_option('single_item_message', '');
        return (!empty($msg) && trim($msg) !== '') ? $msg : __('🚨 Only 1 left in stock - order soon!', 'optimus-bytes-woo-kit');
    }

    /**
     * Format Scarcity Message for a given tier level and stock count
     *
     * @param string $level 'high', 'medium', or 'low'
     * @param int|null $stock
     * @param bool $wrap_number Whether to wrap the stock number in <strong class="obwk-stock-number">
     * @return string
     */
    public function format_scarcity_message($level, $stock = null, $wrap_number = true) {
        $template = '';

        if ('high' === $level) {
            $template = $this->get_in_stock_message();
        } elseif ('medium' === $level) {
            $template = $this->get_medium_stock_message();
        } else {
            // Low stock
            if (1 === $stock) {
                $template = $this->get_single_item_message();
            } else {
                $template = $this->get_low_stock_message();
            }
        }

        if ($stock !== null) {
            $stock_str = $wrap_number
                ? '<span class="obwk-stock-number">' . esc_html($stock) . '</span>'
                : esc_html($stock);
            $message = str_replace('{stock}', $stock_str, $template);
            $message = trim(preg_replace('/\s+/', ' ', $message));
        } else {
            // Unmanaged stock or unknown count: clean up {stock} and surrounding parentheticals
            $message = preg_replace('/\s*\(\{stock\}[^)]*\)/', '', $template);
            $message = str_replace(array('{stock}', '()'), '', $message);
            $message = trim(preg_replace('/\s+/', ' ', $message));

            if (empty($message)) {
                $message = __('✅ In Stock', 'optimus-bytes-woo-kit');
            }
        }

        return $message;
    }

    /**
     * Render the Stock Scarcity & Urgency Bar on Single Product
     */
    public function render_stock_scarcity_bar() {
        if (!$this->is_enabled()) {
            return;
        }

        global $product;
        if (!$product || !is_a($product, 'WC_Product') || !$product->is_in_stock()) {
            return;
        }

        $data              = $this->get_product_scarcity_data($product);
        $display_style     = $this->get_option('display_style', 'full_card');
        $show_progress_bar = (bool) $this->get_option('show_progress_bar', true);
        $box_style         = $this->get_option('box_style', 'card');

        $is_hidden = !$data['should_show'] || ($data['stock'] === null && !$data['is_variable'] && $data['is_managed']);

        $message = $this->format_scarcity_message($data['level'], $data['stock'], true);

        $wrapper_classes = array(
            'obwk-stock-scarcity-wrap',
            'obwk-style-' . sanitize_html_class($box_style),
            'obwk-display-' . sanitize_html_class($display_style),
            'is-' . sanitize_html_class($data['level']),
        );
        if ($data['is_critical']) {
            $wrapper_classes[] = 'is-critical';
        }
        ?>
        <div class="<?php echo esc_attr(implode(' ', $wrapper_classes)); ?>" id="obwk-stock-scarcity-wrap" style="<?php echo $is_hidden ? 'display:none;' : ''; ?>" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
            <div class="obwk-stock-alert-header">
                <span class="obwk-stock-pulse-dot is-<?php echo esc_attr($data['level']); ?>"></span>
                <span class="obwk-stock-message"><?php echo wp_kses_post($message); ?></span>
            </div>

            <?php if ('stocks_only' !== $display_style && $show_progress_bar) : ?>
                <div class="obwk-stock-progress-track">
                    <div class="obwk-stock-progress-fill is-<?php echo esc_attr($data['level']); ?>" style="width: <?php echo esc_attr($data['percent']); ?>%;">
                        <span class="obwk-stock-shimmer"></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Append Scarcity Badge below price on shop and category loops
     *
     * @param string $price_html
     * @param \WC_Product|null $product
     * @return string
     */
    public function append_loop_scarcity_to_price($price_html, $product = null) {
        if (is_admin() || empty($price_html)) {
            return $price_html;
        }

        if (!$this->is_enabled()) {
            return $price_html;
        }

        if (!(bool) $this->get_option('show_in_loop', true)) {
            return $price_html;
        }

        // Only in loops: not the main single product price
        $is_main_single = function_exists('is_product') && is_product() && (function_exists('wc_get_loop_prop') && !wc_get_loop_prop('is_shortcode')) && empty($GLOBALS['woocommerce_loop']['name']);
        if ($is_main_single) {
            return $price_html;
        }

        if (!$product && function_exists('wc_get_product')) {
            global $post;
            if ($post) {
                $product = wc_get_product($post);
            }
        }

        if (!$product || !is_a($product, 'WC_Product') || !$product->is_in_stock()) {
            return $price_html;
        }

        $data = $this->get_product_scarcity_data($product);
        if (!$data['should_show']) {
            return $price_html;
        }

        $level        = $data['level']; // 'high', 'medium', 'low'
        $loop_display = $this->get_option('loop_display', 'new_line');
        $message      = $this->format_scarcity_message($level, $data['stock'], true);

        $is_critical = $data['is_critical'];
        $wrap_class  = 'obwk-loop-scarcity-wrap is-' . sanitize_html_class($loop_display);
        $badge_class = 'obwk-loop-scarcity-badge is-' . sanitize_html_class($level) . ($is_critical ? ' is-critical' : '');

        $badge = '<span class="' . esc_attr($wrap_class) . '">'
               . '<span class="' . esc_attr($badge_class) . '">'
               . '<span class="obwk-status-text">' . wp_kses_post($message) . '</span>'
               . '</span>'
               . '</span>';

        return $price_html . $badge;
    }

    /**
     * Render Compact Scarcity Badge in Product Loop Card (Fallback action)
     */
    public function render_loop_scarcity_badge() {
        if (!$this->is_enabled()) {
            return;
        }

        global $product;
        if (!$product || !is_a($product, 'WC_Product') || !$product->is_in_stock()) {
            return;
        }

        $data = $this->get_product_scarcity_data($product);
        if (!$data['should_show']) {
            return;
        }

        $level        = $data['level'];
        $loop_display = $this->get_option('loop_display', 'new_line');
        $message      = $this->format_scarcity_message($level, $data['stock'], true);
        $wrap_class   = 'obwk-loop-scarcity-wrap is-' . sanitize_html_class($loop_display);
        $badge_class  = 'obwk-loop-scarcity-badge is-' . sanitize_html_class($level) . ($data['is_critical'] ? ' is-critical' : '');
        ?>
        <span class="<?php echo esc_attr($wrap_class); ?>">
            <span class="<?php echo esc_attr($badge_class); ?>">
                <span class="obwk-status-text"><?php echo wp_kses_post($message); ?></span>
            </span>
        </span>
        <?php
    }

    /**
     * Register Customizer Settings
     *
     * @param \WP_Customize_Manager $wp_customize
     */
    public function register_customizer_settings($wp_customize) {
        $section_id = 'obwk_stock_scarcity_section';

        $wp_customize->add_section($section_id, array(
            'title'       => __('Stock Scarcity & Urgency', 'optimus-bytes-woo-kit'),
            'description' => __('Create buying urgency with real-time low-stock alerts, animated progress bars, multi-tier stock status (Green, Orange, Red), and variation syncing.', 'optimus-bytes-woo-kit'),
            'priority'    => 126,
        ));

        // 1. Enable / Disable Module
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[stock_scarcity_enable]', array(
            'type'              => 'option',
            'default'           => true,
            'transport'         => 'refresh',
            'sanitize_callback' => 'wp_validate_boolean',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[stock_scarcity_enable]', array(
            'label'    => __('Enable Stock Scarcity Bar', 'optimus-bytes-woo-kit'),
            'section'  => $section_id,
            'type'     => 'checkbox',
        ));

        // 2. Inventory Mode (Real vs Simulated)
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[stock_scarcity_mode]', array(
            'type'              => 'option',
            'default'           => 'real',
            'transport'         => 'refresh',
            'sanitize_callback' => 'sanitize_key',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[stock_scarcity_mode]', array(
            'label'       => __('Stock Calculation Mode', 'optimus-bytes-woo-kit'),
            'description' => __('"Real Inventory" uses actual WooCommerce stock levels. "Simulated Urgency" creates realistic stock levels for unmanaged inventory.', 'optimus-bytes-woo-kit'),
            'section'     => $section_id,
            'type'        => 'select',
            'choices'     => array(
                'real'      => __('Real Inventory (Recommended)', 'optimus-bytes-woo-kit'),
                'simulated' => __('Simulated / Social Urgency (For Unmanaged Stock)', 'optimus-bytes-woo-kit'),
            ),
        ));

        // 3. Stock Levels to Display
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[stock_scarcity_display_levels]', array(
            'type'              => 'option',
            'default'           => 'all_levels',
            'transport'         => 'refresh',
            'sanitize_callback' => 'sanitize_key',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[stock_scarcity_display_levels]', array(
            'label'       => __('Stock Levels to Display', 'optimus-bytes-woo-kit'),
            'description' => __('Choose whether to display all stock status levels (Green/Orange/Red) or low stock urgency only.', 'optimus-bytes-woo-kit'),
            'section'     => $section_id,
            'type'        => 'select',
            'choices'     => array(
                'all_levels' => __('All Stock Levels (Green = In Stock, Orange = Medium, Red = Low)', 'optimus-bytes-woo-kit'),
                'low_only'   => __('Low Stock Only (Urgency Alert Only)', 'optimus-bytes-woo-kit'),
            ),
        ));

        // 4. Show Status for Unmanaged Inventory
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[stock_scarcity_show_unmanaged_stock]', array(
            'type'              => 'option',
            'default'           => false,
            'transport'         => 'refresh',
            'sanitize_callback' => 'wp_validate_boolean',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[stock_scarcity_show_unmanaged_stock]', array(
            'label'       => __('Show "In Stock" for Unmanaged Inventory', 'optimus-bytes-woo-kit'),
            'description' => __('When disabled (default), products or variations without stock management enabled in WooCommerce will not display stock status.', 'optimus-bytes-woo-kit'),
            'section'     => $section_id,
            'type'        => 'checkbox',
        ));

        // 5. Display Style / Layout (Full Card vs Stocks Only Badge)
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[stock_scarcity_display_style]', array(
            'type'              => 'option',
            'default'           => 'full_card',
            'transport'         => 'refresh',
            'sanitize_callback' => 'sanitize_key',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[stock_scarcity_display_style]', array(
            'label'       => __('Display Style / Layout', 'optimus-bytes-woo-kit'),
            'description' => __('Choose between a full alert card with progress bar or clean stock status badge/pill only.', 'optimus-bytes-woo-kit'),
            'section'     => $section_id,
            'type'        => 'select',
            'choices'     => array(
                'full_card'   => __('Full Urgency Card (Alert & Progress Bar)', 'optimus-bytes-woo-kit'),
                'stocks_only' => __('Stock Badge Only (Clean Pill / Text Only - No Bar)', 'optimus-bytes-woo-kit'),
            ),
        ));

        // 5. Low Stock Threshold (Red)
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[stock_scarcity_threshold]', array(
            'type'              => 'option',
            'default'           => 5,
            'transport'         => 'refresh',
            'sanitize_callback' => 'absint',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[stock_scarcity_threshold]', array(
            'label'       => __('Low Stock Threshold (Red Alert)', 'optimus-bytes-woo-kit'),
            'description' => __('Display critical low stock urgency (Red) when remaining stock is less than or equal to this number.', 'optimus-bytes-woo-kit'),
            'section'     => $section_id,
            'type'        => 'number',
            'input_attrs' => array('min' => 1, 'max' => 50, 'step' => 1),
        ));

        // 6. Medium Stock Threshold (Orange)
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[stock_scarcity_medium_threshold]', array(
            'type'              => 'option',
            'default'           => 15,
            'transport'         => 'refresh',
            'sanitize_callback' => 'absint',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[stock_scarcity_medium_threshold]', array(
            'label'       => __('Medium Stock Threshold (Orange Alert)', 'optimus-bytes-woo-kit'),
            'description' => __('Stock between Low Threshold and this number is displayed as Medium Stock (Orange/Amber). Above this is In Stock (Green).', 'optimus-bytes-woo-kit'),
            'section'     => $section_id,
            'type'        => 'number',
            'input_attrs' => array('min' => 2, 'max' => 100, 'step' => 1),
        ));

        // 7. Initial Stock Quantity Scale (For Progress Bar Width)
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[stock_scarcity_initial_qty]', array(
            'type'              => 'option',
            'default'           => 20,
            'transport'         => 'refresh',
            'sanitize_callback' => 'absint',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[stock_scarcity_initial_qty]', array(
            'label'       => __('Progress Bar Total Stock Capacity', 'optimus-bytes-woo-kit'),
            'description' => __('Represents 100% bar capacity (e.g. 20). If 5 items are left out of 20, the bar is 25% filled.', 'optimus-bytes-woo-kit'),
            'section'     => $section_id,
            'type'        => 'number',
            'input_attrs' => array('min' => 2, 'max' => 100, 'step' => 1),
        ));

        // 8. In Stock Message Template (Green)
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[stock_scarcity_in_stock_message]', array(
            'type'              => 'option',
            'default'           => __('✅ In Stock ({stock} available)', 'optimus-bytes-woo-kit'),
            'transport'         => 'refresh',
            'sanitize_callback' => 'wp_kses_post',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[stock_scarcity_in_stock_message]', array(
            'label'       => __('In Stock Message Template (Green)', 'optimus-bytes-woo-kit'),
            'description' => __('Displayed when stock is plentiful. Use {stock} for available count.', 'optimus-bytes-woo-kit'),
            'section'     => $section_id,
            'type'        => 'text',
        ));

        // 8a. In Stock Text Color
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[stock_scarcity_in_stock_text_color]', array(
            'type'              => 'option',
            'default'           => '',
            'transport'         => 'refresh',
            'sanitize_callback' => 'sanitize_hex_color',
        ));
        $wp_customize->add_control(new \WP_Customize_Color_Control($wp_customize, OBWK_SETTINGS_OPTION . '[stock_scarcity_in_stock_text_color]', array(
            'label'       => __('In Stock Text Color', 'optimus-bytes-woo-kit'),
            'description' => __('Default: Green (#15803d)', 'optimus-bytes-woo-kit'),
            'section'     => $section_id,
        )));

        // 8b. In Stock Background Color
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[stock_scarcity_in_stock_bg_color]', array(
            'type'              => 'option',
            'default'           => '',
            'transport'         => 'refresh',
            'sanitize_callback' => 'sanitize_hex_color',
        ));
        $wp_customize->add_control(new \WP_Customize_Color_Control($wp_customize, OBWK_SETTINGS_OPTION . '[stock_scarcity_in_stock_bg_color]', array(
            'label'       => __('In Stock Background Color', 'optimus-bytes-woo-kit'),
            'description' => __('Default: Soft Green (#f0fdf4)', 'optimus-bytes-woo-kit'),
            'section'     => $section_id,
        )));

        // 8c. In Stock Border Color
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[stock_scarcity_in_stock_border_color]', array(
            'type'              => 'option',
            'default'           => '',
            'transport'         => 'refresh',
            'sanitize_callback' => 'sanitize_hex_color',
        ));
        $wp_customize->add_control(new \WP_Customize_Color_Control($wp_customize, OBWK_SETTINGS_OPTION . '[stock_scarcity_in_stock_border_color]', array(
            'label'       => __('In Stock Border Color', 'optimus-bytes-woo-kit'),
            'description' => __('Default: Light Green (#86efac)', 'optimus-bytes-woo-kit'),
            'section'     => $section_id,
        )));

        // 9. Medium Stock Message Template (Orange)
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[stock_scarcity_medium_message]', array(
            'type'              => 'option',
            'default'           => __('⚡ Limited stock: Only {stock} left!', 'optimus-bytes-woo-kit'),
            'transport'         => 'refresh',
            'sanitize_callback' => 'wp_kses_post',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[stock_scarcity_medium_message]', array(
            'label'       => __('Medium Stock Message Template (Orange)', 'optimus-bytes-woo-kit'),
            'description' => __('Displayed when stock is at medium level. Use {stock} for remaining count.', 'optimus-bytes-woo-kit'),
            'section'     => $section_id,
            'type'        => 'text',
        ));

        // 9a. Medium Stock Text Color
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[stock_scarcity_medium_text_color]', array(
            'type'              => 'option',
            'default'           => '',
            'transport'         => 'refresh',
            'sanitize_callback' => 'sanitize_hex_color',
        ));
        $wp_customize->add_control(new \WP_Customize_Color_Control($wp_customize, OBWK_SETTINGS_OPTION . '[stock_scarcity_medium_text_color]', array(
            'label'       => __('Medium Stock Text Color', 'optimus-bytes-woo-kit'),
            'description' => __('Default: Amber (#92400e)', 'optimus-bytes-woo-kit'),
            'section'     => $section_id,
        )));

        // 9b. Medium Stock Background Color
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[stock_scarcity_medium_bg_color]', array(
            'type'              => 'option',
            'default'           => '',
            'transport'         => 'refresh',
            'sanitize_callback' => 'sanitize_hex_color',
        ));
        $wp_customize->add_control(new \WP_Customize_Color_Control($wp_customize, OBWK_SETTINGS_OPTION . '[stock_scarcity_medium_bg_color]', array(
            'label'       => __('Medium Stock Background Color', 'optimus-bytes-woo-kit'),
            'description' => __('Default: Soft Amber (#fffbeb)', 'optimus-bytes-woo-kit'),
            'section'     => $section_id,
        )));

        // 9c. Medium Stock Border Color
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[stock_scarcity_medium_border_color]', array(
            'type'              => 'option',
            'default'           => '',
            'transport'         => 'refresh',
            'sanitize_callback' => 'sanitize_hex_color',
        ));
        $wp_customize->add_control(new \WP_Customize_Color_Control($wp_customize, OBWK_SETTINGS_OPTION . '[stock_scarcity_medium_border_color]', array(
            'label'       => __('Medium Stock Border Color', 'optimus-bytes-woo-kit'),
            'description' => __('Default: Light Amber (#fcd34d)', 'optimus-bytes-woo-kit'),
            'section'     => $section_id,
        )));

        // 10. Low Stock Message Template (Red)
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[stock_scarcity_message]', array(
            'type'              => 'option',
            'default'           => __('🔥 Hurry! Only {stock} items left in stock!', 'optimus-bytes-woo-kit'),
            'transport'         => 'refresh',
            'sanitize_callback' => 'wp_kses_post',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[stock_scarcity_message]', array(
            'label'       => __('Low Stock Message Template (Red)', 'optimus-bytes-woo-kit'),
            'description' => __('Use {stock} placeholder where the remaining count appears.', 'optimus-bytes-woo-kit'),
            'section'     => $section_id,
            'type'        => 'text',
        ));

        // 10a. Low Stock Text Color
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[stock_scarcity_low_text_color]', array(
            'type'              => 'option',
            'default'           => '',
            'transport'         => 'refresh',
            'sanitize_callback' => 'sanitize_hex_color',
        ));
        $wp_customize->add_control(new \WP_Customize_Color_Control($wp_customize, OBWK_SETTINGS_OPTION . '[stock_scarcity_low_text_color]', array(
            'label'       => __('Low Stock Text Color', 'optimus-bytes-woo-kit'),
            'description' => __('Default: Coral/Red (#9a3412)', 'optimus-bytes-woo-kit'),
            'section'     => $section_id,
        )));

        // 10b. Low Stock Background Color
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[stock_scarcity_low_bg_color]', array(
            'type'              => 'option',
            'default'           => '',
            'transport'         => 'refresh',
            'sanitize_callback' => 'sanitize_hex_color',
        ));
        $wp_customize->add_control(new \WP_Customize_Color_Control($wp_customize, OBWK_SETTINGS_OPTION . '[stock_scarcity_low_bg_color]', array(
            'label'       => __('Low Stock Background Color', 'optimus-bytes-woo-kit'),
            'description' => __('Default: Soft Coral (#fff7ed)', 'optimus-bytes-woo-kit'),
            'section'     => $section_id,
        )));

        // 10c. Low Stock Border Color
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[stock_scarcity_low_border_color]', array(
            'type'              => 'option',
            'default'           => '',
            'transport'         => 'refresh',
            'sanitize_callback' => 'sanitize_hex_color',
        ));
        $wp_customize->add_control(new \WP_Customize_Color_Control($wp_customize, OBWK_SETTINGS_OPTION . '[stock_scarcity_low_border_color]', array(
            'label'       => __('Low Stock Border Color', 'optimus-bytes-woo-kit'),
            'description' => __('Default: Light Coral (#fed7aa)', 'optimus-bytes-woo-kit'),
            'section'     => $section_id,
        )));

        // 11. Single Item Remaining Message (Critical Red)
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[stock_scarcity_single_item_message]', array(
            'type'              => 'option',
            'default'           => __('🚨 Only 1 left in stock - order soon!', 'optimus-bytes-woo-kit'),
            'transport'         => 'refresh',
            'sanitize_callback' => 'wp_kses_post',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[stock_scarcity_single_item_message]', array(
            'label'       => __('High Urgency Message (When Stock = 1)', 'optimus-bytes-woo-kit'),
            'section'     => $section_id,
            'type'        => 'text',
        ));

        // 12. Show Animated Progress Bar
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[stock_scarcity_show_progress_bar]', array(
            'type'              => 'option',
            'default'           => true,
            'transport'         => 'refresh',
            'sanitize_callback' => 'wp_validate_boolean',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[stock_scarcity_show_progress_bar]', array(
            'label'    => __('Show Animated Visual Progress Bar', 'optimus-bytes-woo-kit'),
            'section'  => $section_id,
            'type'     => 'checkbox',
        ));

        // 13. Position on Single Product Page (including woocommerce_after_add_to_cart_form)
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[stock_scarcity_position]', array(
            'type'              => 'option',
            'default'           => 'before_cart_button',
            'transport'         => 'refresh',
            'sanitize_callback' => 'sanitize_key',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[stock_scarcity_position]', array(
            'label'    => __('Position on Single Product Page', 'optimus-bytes-woo-kit'),
            'section'  => $section_id,
            'type'     => 'select',
            'choices'  => array(
                'before_cart_button' => __('Above Add to Cart Button (Recommended)', 'optimus-bytes-woo-kit'),
                'after_cart_button'  => __('Below Add to Cart Button', 'optimus-bytes-woo-kit'),
                'after_cart_form'    => __('Below Add to Cart Form (woocommerce_after_add_to_cart_form)', 'optimus-bytes-woo-kit'),
                'after_price'        => __('Below Product Price', 'optimus-bytes-woo-kit'),
                'before_cart_form'   => __('Above Cart Form & Variations', 'optimus-bytes-woo-kit'),
            ),
        ));

        // 14. Box Visual Style
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[stock_scarcity_box_style]', array(
            'type'              => 'option',
            'default'           => 'card',
            'transport'         => 'refresh',
            'sanitize_callback' => 'sanitize_key',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[stock_scarcity_box_style]', array(
            'label'    => __('Card Container Style', 'optimus-bytes-woo-kit'),
            'section'  => $section_id,
            'type'     => 'select',
            'choices'  => array(
                'card'    => __('Card Container (Subtle Tint & Border)', 'optimus-bytes-woo-kit'),
                'minimal' => __('Minimal Inline (No Border Box)', 'optimus-bytes-woo-kit'),
            ),
        ));

        // 15. Show in Loop / Catalog Cards
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[stock_scarcity_show_in_loop]', array(
            'type'              => 'option',
            'default'           => true,
            'transport'         => 'refresh',
            'sanitize_callback' => 'wp_validate_boolean',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[stock_scarcity_show_in_loop]', array(
            'label'       => __('Show Scarcity Badge on Catalog Loops', 'optimus-bytes-woo-kit'),
            'description' => __('Displays stock urgency alert below the price on Shop page, Category archives, and search results.', 'optimus-bytes-woo-kit'),
            'section'     => $section_id,
            'type'        => 'checkbox',
        ));

        // 16. Catalog Loop Badge Placement (New Line vs Inline)
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[stock_scarcity_loop_display]', array(
            'type'              => 'option',
            'default'           => 'new_line',
            'transport'         => 'refresh',
            'sanitize_callback' => 'sanitize_key',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[stock_scarcity_loop_display]', array(
            'label'       => __('Catalog Loop Badge Placement', 'optimus-bytes-woo-kit'),
            'description' => __('Fixes layout inconsistencies by guaranteeing the badge starts on a clean new line below the price, or sits inline beside it.', 'optimus-bytes-woo-kit'),
            'section'     => $section_id,
            'type'        => 'select',
            'choices'     => array(
                'new_line' => __('Below Price (Always New Line - Clean & Consistent)', 'optimus-bytes-woo-kit'),
                'inline'   => __('Inline Beside Price', 'optimus-bytes-woo-kit'),
            ),
        ));

        // 17. Custom Progress Bar Fill Color
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[stock_scarcity_bar_color]', array(
            'type'              => 'option',
            'default'           => '',
            'transport'         => 'refresh',
            'sanitize_callback' => 'sanitize_hex_color',
        ));
        $wp_customize->add_control(new \WP_Customize_Color_Control($wp_customize, OBWK_SETTINGS_OPTION . '[stock_scarcity_bar_color]', array(
            'label'       => __('Progress Bar Color (Optional Override)', 'optimus-bytes-woo-kit'),
            'description' => __('Leave empty for automatic smart colors (Green for In Stock, Orange for Medium, Red for Low).', 'optimus-bytes-woo-kit'),
            'section'     => $section_id,
        )));
    }
}
