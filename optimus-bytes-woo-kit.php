<?php
/**
 * Plugin Name:       Optimus Bytes Woo Kit
 * Plugin URI:        https://optimusbytes.com/
 * Description:       A modular, high-performance toolkit for WooCommerce and e-commerce stores by Optimus Bytes Technologies.
 * Version:           1.1.0
 * Author:            Optimus Bytes Technologies
 * Author URI:        https://optimusbytes.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       optimus-bytes-woo-kit
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * WC requires at least: 5.0
 * WC tested up to:   9.5
 */

namespace OptimusBytes\WooKit;

defined('ABSPATH') || exit;

// Plugin Constants
define('OBWK_VERSION', '1.1.0');
define('OBWK_PLUGIN_FILE', __FILE__);
define('OBWK_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('OBWK_PLUGIN_URL', plugin_dir_url(__FILE__));
define('OBWK_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('OBWK_SETTINGS_OPTION', 'optimus_bytes_woo_kit_settings');

/**
 * Declare WooCommerce HPOS and Feature Compatibility
 */
add_action('before_woocommerce_init', function () {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        // HPOS (High-Performance Order Storage / Custom Order Tables)
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            __FILE__,
            true
        );
        // Cart and Checkout Blocks Compatibility
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'cart_checkout_blocks',
            __FILE__,
            true
        );
        // Product Block Editor Compatibility
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'product_block_editor',
            __FILE__,
            true
        );
    }
});

/**
 * Autoloader for OptimusBytes\WooKit classes
 */
spl_autoload_register(function ($class) {
    $prefix = 'OptimusBytes\\WooKit\\';
    $base_dir = OBWK_PLUGIN_DIR . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $parts = explode('\\', $relative_class);
    $class_name = array_pop($parts);

    // Convert class name to WordPress naming convention: class-something.php
    $formatted_class_name = 'class-' . strtolower(str_replace('_', '-', $class_name)) . '.php';

    // Subdirectories converting underscores to hyphens (e.g. Announcement_Bar -> announcement-bar)
    $sub_dir = '';
    if (!empty($parts)) {
        $dir_parts = array_map(function ($p) {
            return strtolower(str_replace('_', '-', $p));
        }, $parts);
        $sub_dir = implode('/', $dir_parts) . '/';
    }

    $file = $base_dir . $sub_dir . $formatted_class_name;

    if (file_exists($file)) {
        require_once $file;
    }
});

/**
 * Bootstrap Plugin
 */
function obwk_init() {
    Plugin::instance();
}
add_action('plugins_loaded', __NAMESPACE__ . '\\obwk_init', 10);
