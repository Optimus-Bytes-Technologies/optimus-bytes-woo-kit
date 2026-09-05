<?php
/**
 * Core Plugin Class
 *
 * @package OptimusBytes\WooKit
 */

namespace OptimusBytes\WooKit;

defined('ABSPATH') || exit;

class Plugin {

    /**
     * Singleton instance
     *
     * @var Plugin|null
     */
    private static $instance = null;

    /**
     * Active modules registry
     *
     * @var array
     */
    private $modules = array();

    /**
     * Admin Menu Handler
     *
     * @var Admin\Admin_Menu|null
     */
    private $admin_menu = null;

    /**
     * Get singleton instance
     *
     * @return Plugin
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_textdomain();
        $this->init_modules();
        $this->init_admin();
        $this->register_plugin_action_links();
    }

    /**
     * Load localization files
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'optimus-bytes-woo-kit',
            false,
            dirname(OBWK_PLUGIN_BASENAME) . '/languages'
        );
    }

    /**
     * Initialize pluggable modules
     */
    private function init_modules() {
        // Register Announcement Bar Module
        $this->register_module(new Modules\Announcement_Bar\Announcement_Bar_Module());

        // Register Variation Swatches Module
        $this->register_module(new Modules\Variation_Swatches\Variation_Swatches_Module());

        // Register Color Swatch Filter Widget Module
        $this->register_module(new Modules\Color_Filter\Color_Filter_Module());

        // Register WhatsApp Floating Contact Button Module
        $this->register_module(new Modules\WhatsApp\WhatsApp_Module());

        // Register Single Product Order on WhatsApp Module
        $this->register_module(new Modules\Product_WhatsApp\Product_WhatsApp_Module());

        // Register Sticky Add to Cart & Buy Now Bar Module
        $this->register_module(new Modules\Sticky_Add_To_Cart\Sticky_Add_To_Cart_Module());

        // Register Category Showcase Module (Elementor Slider & Grid)
        $this->register_module(new Modules\Category_Showcase\Category_Showcase_Module());

        // Register Product Tabs Module (Elementor Tabbed Product Carousel)
        $this->register_module(new Modules\Product_Tabs\Product_Tabs_Module());

        // Register Sale Badge & Discount Percentage Module
        $this->register_module(new Modules\Sale_Badge\Sale_Badge_Module());

        // Register Stock Scarcity & Urgency Bar Module
        $this->register_module(new Modules\Stock_Scarcity\Stock_Scarcity_Module());

        /**
         * Action to register additional custom modules
         *
         * @param Plugin $this
         */
        do_action('obwk_register_modules', $this);

        // Initialize all registered modules
        foreach ($this->modules as $module) {
            $module->init();
        }
    }

    /**
     * Initialize Admin Menu and UI
     */
    private function init_admin() {
        if (is_admin()) {
            $this->admin_menu = new Admin\Admin_Menu($this);
            $this->admin_menu->init();
        }
    }

    /**
     * Register a module
     *
     * @param Modules\Abstract_Module $module
     */
    public function register_module($module) {
        if ($module instanceof Modules\Abstract_Module) {
            $this->modules[$module->get_id()] = $module;
        }
    }

    /**
     * Get a specific module by ID
     *
     * @param string $id
     * @return Modules\Abstract_Module|null
     */
    public function get_module($id) {
        return isset($this->modules[$id]) ? $this->modules[$id] : null;
    }

    /**
     * Get all registered modules
     *
     * @return array
     */
    public function get_modules() {
        return $this->modules;
    }

    /**
     * Add Settings / Customize quick link in Plugins page
     */
    private function register_plugin_action_links() {
        add_filter('plugin_action_links_' . OBWK_PLUGIN_BASENAME, function ($links) {
            $dashboard_url = admin_url('admin.php?page=optimus-woo-kit');
            $custom_links  = array(
                '<a href="' . esc_url($dashboard_url) . '">' . esc_html__('Dashboard', 'optimus-bytes-woo-kit') . '</a>',
            );
            return array_merge($custom_links, $links);
        });
    }
}
