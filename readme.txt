=== Optimus Bytes Woo Kit ===
Contributors: optimusbytes
Donate link: https://optimusbytes.com/
Tags: woocommerce, whatsapp, whatsapp button, order on whatsapp, ecommerce, floating whatsapp, sales booster
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A modular, high-performance toolkit for WooCommerce stores featuring smart WhatsApp contact and direct ordering tools by Optimus Bytes Technologies.

== Description ==

**Optimus Bytes Woo Kit** is a modular, lightweight, and high-conversion toolkit designed for WooCommerce stores. It empowers merchants with customer engagement tools, direct chat channels, and streamlined order conversions without bloating your store.

### 🌟 Active Modules Included:

#### 1. 💬 Floating WhatsApp Contact Button
* **Sitewide Floating Button**: Sleek, modern floating WhatsApp button with subtle pulse wave animation and online indicator.
* **Smart Context Prefill**: Automatically prefills customer messages based on the page they are viewing (Product title & URL on product pages, cart support on cart pages, general inquiries on other pages).
* **Page Visibility Rules**: Choose to hide the button on Checkout, Cart, Single Product pages, or specific Page IDs (e.g. 12, 45, 108) to reduce distraction during checkout.
* **Position & Styling**: Configurable bottom-left or bottom-right positioning with custom tooltips and greeting messages.

#### 2. 🛍️ Single Product Page "Order on WhatsApp"
* **Direct WhatsApp Purchase**: Adds a prominent, stylish "Order on WhatsApp" button directly under the "Add to Cart" and "Buy Now" buttons.
* **Real-time Live Sync**: Dynamically updates the prefilled WhatsApp order message as customers change product quantities (`+` / `-`) or select product variations (color, size, pattern).
* **Pre-formatted Order Message**: Includes Product Title, Selected Variations, Price, SKU, Quantity, and Link.
* **Custom Themes**: Choose between WhatsApp Green Solid, Outline, or Luxury Saree Gold & Green styles.

#### 3. ⚡ Interactive Admin Hub
* **Modular Switchboard**: Turn any module on or off with instant AJAX toggle switches directly from the WordPress Admin menu.
* **No Database Bloat**: All plugin options are stored neatly in `wp_options` under `optimus_bytes_woo_kit_settings`.
* **Zero Legacy Code**: Fully decoupled from active themes — your settings stay intact even if you switch themes.

### 🚀 Developer Friendly & High Performance
* **100% HPOS Compatible**: Fully declared and tested with WooCommerce High-Performance Order Storage (`custom_order_tables`) and WooCommerce Blocks.
* **Modular Architecture**: Easily extendable with new modules extending `Abstract_Module`.
* **Asset Scoping**: CSS and JS are only enqueued on pages where the respective module is enabled and visible.
* **Extensible Hooks**: Full set of filters (`obwk_whatsapp_phone_number`, `obwk_product_whatsapp_message`, `obwk_whatsapp_should_display`, etc.).

== Installation ==

### Automatic Installation (WordPress Dashboard)
1. Log into your WordPress admin dashboard.
2. Go to **Plugins > Add New**.
3. Click **Upload Plugin** and select the `optimus-bytes-woo-kit.zip` file.
4. Click **Install Now** and then **Activate**.

### Manual Installation (FTP / File Manager)
1. Upload the `optimus-bytes-woo-kit` folder to your `/wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** menu in WordPress.

### Configuration
1. Click on **Optimus Woo Kit** in your WordPress Admin sidebar.
2. Use the toggle switches to enable or disable features.
3. Click **Configure Settings** or go to **Appearance > Customize > Floating WhatsApp Button** / **Product Page WhatsApp Button** to adjust your phone number, messages, and layout.

== Frequently Asked Questions ==

= Does this plugin require WooCommerce? =
While the Floating WhatsApp Contact button works sitewide, WooCommerce is recommended to take full advantage of product page dynamic ordering, cart detection, and HPOS features.

= Where are the plugin settings stored? =
All settings are stored in the `wp_options` table under the single, dedicated option name: `optimus_bytes_woo_kit_settings`.

= Is it compatible with WooCommerce HPOS (High-Performance Order Storage)? =
Yes! The plugin explicitly declares full compatibility with WooCommerce HPOS (`custom_order_tables`), Cart/Checkout Blocks (`cart_checkout_blocks`), and the Product Block Editor (`product_block_editor`).

= Does the "Order on WhatsApp" button support Variable Products? =
Yes. The included client script dynamically listens to WooCommerce variation selection events and quantity inputs to format the exact selected attributes, SKU, price, and quantity in real-time.

= Can I hide the floating WhatsApp button on the checkout page? =
Yes. In **Appearance > Customize > Floating WhatsApp Button**, check the **"Hide on Checkout Page"** option. You can also hide it on the cart page, product pages, or specify comma-separated Page IDs.

= How do I add my WhatsApp phone number? =
Go to **Optimus Woo Kit > Configure Settings** (or **Appearance > Customize > Floating WhatsApp Button**), and enter your phone number with your country code (e.g. `+91 98765 43210`). The plugin automatically strips formatting and generates valid `https://wa.me/` links.

== Screenshots ==

1. Optimus Woo Kit Admin Dashboard with interactive module switches.
2. Floating WhatsApp button with animated pulse and online badge on frontend.
3. Single product page "Order on WhatsApp" button positioned under Add to Cart.
4. WordPress Customizer settings panel with live preview and page visibility controls.

== Changelog ==

= 1.0.0 =
* Initial release of Optimus Bytes Woo Kit.
* Added Floating WhatsApp Contact Button module with smart contextual prefill.
* Added Single Product "Order on WhatsApp" module with dynamic quantity and variation tracking.
* Added Page Visibility rules (hide on checkout, cart, products, or custom page IDs).
* Added WordPress Admin Menu & Modules Dashboard with instant AJAX toggle switches.
* Declared full compatibility with WooCommerce HPOS, Blocks, and PHP 7.4 - 8.3.
* Implemented standalone `optimus_bytes_woo_kit_settings` storage in `wp_options`.

== Upgrade Notice ==

= 1.0.0 =
Initial release. Enjoy the modular WooCommerce growth toolkit from Optimus Bytes Technologies.
