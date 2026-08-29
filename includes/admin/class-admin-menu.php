<?php
/**
 * Admin Menu and Modules Dashboard
 *
 * @package OptimusBytes\WooKit\Admin
 */

namespace OptimusBytes\WooKit\Admin;

use OptimusBytes\WooKit\Plugin;

defined('ABSPATH') || exit;

class Admin_Menu {

    /**
     * Plugin Instance
     *
     * @var Plugin
     */
    private $plugin;

    /**
     * Constructor
     *
     * @param Plugin $plugin
     */
    public function __construct(Plugin $plugin) {
        $this->plugin = $plugin;
    }

    /**
     * Initialize Admin Menu Hooks
     */
    public function init() {
        add_action('admin_menu', array($this, 'register_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Register Admin Menus
     */
    public function register_admin_menu() {
        // SVG Icon for Menu
        $svg_icon = 'data:image/svg+xml;base64,' . base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#a0a5aa" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>'
        );

        // Main Menu Page
        add_menu_page(
            __('Optimus Woo Kit', 'optimus-bytes-woo-kit'),
            __('Optimus Woo Kit', 'optimus-bytes-woo-kit'),
            'manage_options',
            'optimus-woo-kit',
            array($this, 'render_dashboard_page'),
            $svg_icon,
            58
        );

        // Submenu: Modules Dashboard
        add_submenu_page(
            'optimus-woo-kit',
            __('Modules & Features', 'optimus-bytes-woo-kit'),
            __('Modules', 'optimus-bytes-woo-kit'),
            'manage_options',
            'optimus-woo-kit',
            array($this, 'render_dashboard_page')
        );

        // Submenu: WhatsApp Configuration Shortcut
        add_submenu_page(
            'optimus-woo-kit',
            __('WhatsApp Button Settings', 'optimus-bytes-woo-kit'),
            __('WhatsApp Button', 'optimus-bytes-woo-kit'),
            'manage_options',
            'customize.php?autofocus[section]=obwk_whatsapp_section'
        );
    }

    /**
     * Enqueue Admin Assets for Optimus Woo Kit pages
     *
     * @param string $hook
     */
    public function enqueue_admin_assets($hook) {
        if ('toplevel_page_optimus-woo-kit' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'obwk-admin-style',
            OBWK_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            OBWK_VERSION
        );
    }

    /**
     * Render the Modules Dashboard Page
     */
    public function render_dashboard_page() {
        $modules = $this->plugin->get_modules();

        // Sample upcoming modules to display in the kit roadmap
        $upcoming_modules = array(
            array(
                'title'       => __('Sticky Add to Cart Bar', 'optimus-bytes-woo-kit'),
                'description' => __('Floating product purchase bar on single product pages when scrolling past the main buy button to boost conversion rates.', 'optimus-bytes-woo-kit'),
                'icon'        => '🛒',
                'badge'       => __('Available Soon', 'optimus-bytes-woo-kit'),
                'category'    => __('Conversion', 'optimus-bytes-woo-kit'),
            ),
            array(
                'title'       => __('Live Sales Notification Popups', 'optimus-bytes-woo-kit'),
                'description' => __('Social proof popups displaying recent purchases in real-time to build customer trust and purchase urgency.', 'optimus-bytes-woo-kit'),
                'icon'        => '🔥',
                'badge'       => __('Available Soon', 'optimus-bytes-woo-kit'),
                'category'    => __('Marketing', 'optimus-bytes-woo-kit'),
            ),
            array(
                'title'       => __('Direct WhatsApp Order Inquiries', 'optimus-bytes-woo-kit'),
                'description' => __('Add instant "Order via WhatsApp" button on product pages for customers who prefer chatting with staff directly.', 'optimus-bytes-woo-kit'),
                'icon'        => '💬',
                'badge'       => __('Available Soon', 'optimus-bytes-woo-kit'),
                'category'    => __('Customer Care', 'optimus-bytes-woo-kit'),
            ),
        );
        ?>
        <div class="wrap obwk-admin-wrap">
            <!-- Header Banner -->
            <div class="obwk-header-card">
                <div class="obwk-header-content">
                    <div class="obwk-logo-badge">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                    </div>
                    <div>
                        <h1 class="obwk-title"><?php esc_html_e('Optimus Bytes Woo Kit', 'optimus-bytes-woo-kit'); ?></h1>
                        <p class="obwk-subtitle"><?php esc_html_e('Modular eCommerce Suite & Growth Toolkit for WooCommerce — Built by Optimus Bytes Technologies', 'optimus-bytes-woo-kit'); ?></p>
                    </div>
                </div>
                <div class="obwk-header-actions">
                    <span class="obwk-pill-version">v<?php echo esc_html(OBWK_VERSION); ?></span>
                    <a href="https://optimusbytes.com/" target="_blank" rel="noopener noreferrer" class="obwk-btn obwk-btn-outline">
                        <?php esc_html_e('Optimus Bytes Tech', 'optimus-bytes-woo-kit'); ?> ↗
                    </a>
                </div>
            </div>

            <!-- Dashboard Subtitle -->
            <div class="obwk-section-heading">
                <h2><?php esc_html_e('Available Modules & Utilities', 'optimus-bytes-woo-kit'); ?></h2>
                <p><?php esc_html_e('Configure and manage modules below. Click on any module to open its live settings.', 'optimus-bytes-woo-kit'); ?></p>
            </div>

            <!-- Active Modules Grid -->
            <div class="obwk-modules-grid">
                <?php foreach ($modules as $module) : ?>
                    <?php
                    $is_enabled   = $module->is_enabled();
                    $config_url   = $module->get_configure_url();
                    $badge_class  = $is_enabled ? 'obwk-badge-active' : 'obwk-badge-inactive';
                    $badge_text   = $is_enabled ? __('Active', 'optimus-bytes-woo-kit') : __('Disabled', 'optimus-bytes-woo-kit');
                    ?>
                    <div class="obwk-module-card <?php echo $is_enabled ? 'is-active' : ''; ?>">
                        <div class="obwk-card-header">
                            <div class="obwk-module-icon">
                                <?php echo esc_html($module->get_icon()); ?>
                            </div>
                            <div class="obwk-card-meta">
                                <span class="obwk-badge <?php echo esc_attr($badge_class); ?>">
                                    <span class="obwk-dot"></span> <?php echo esc_html($badge_text); ?>
                                </span>
                                <span class="obwk-category-tag"><?php echo esc_html($module->get_category()); ?></span>
                            </div>
                        </div>

                        <div class="obwk-card-body">
                            <h3 class="obwk-module-title"><?php echo esc_html($module->get_title()); ?></h3>
                            <p class="obwk-module-desc"><?php echo esc_html($module->get_description()); ?></p>
                        </div>

                        <div class="obwk-card-footer">
                            <a href="<?php echo esc_url($config_url); ?>" class="obwk-btn obwk-btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                <?php esc_html_e('Configure Settings', 'optimus-bytes-woo-kit'); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Upcoming Extensible Modules -->
                <?php foreach ($upcoming_modules as $upcoming) : ?>
                    <div class="obwk-module-card is-upcoming">
                        <div class="obwk-card-header">
                            <div class="obwk-module-icon">
                                <?php echo esc_html($upcoming['icon']); ?>
                            </div>
                            <div class="obwk-card-meta">
                                <span class="obwk-badge obwk-badge-upcoming">
                                    <?php echo esc_html($upcoming['badge']); ?>
                                </span>
                                <span class="obwk-category-tag"><?php echo esc_html($upcoming['category']); ?></span>
                            </div>
                        </div>

                        <div class="obwk-card-body">
                            <h3 class="obwk-module-title"><?php echo esc_html($upcoming['title']); ?></h3>
                            <p class="obwk-module-desc"><?php echo esc_html($upcoming['description']); ?></p>
                        </div>

                        <div class="obwk-card-footer">
                            <button type="button" class="obwk-btn obwk-btn-disabled" disabled>
                                <?php esc_html_e('Modular Ready', 'optimus-bytes-woo-kit'); ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
}
