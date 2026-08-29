=== Optimus Bytes Woo Kit ===
Contributors: optimusbytes
Donate link: https://optimusbytes.com/
Tags: woocommerce, variation swatches, sticky add to cart, announcement bar, whatsapp
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.3.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

All-in-one WooCommerce toolkit for variation swatches, announcement marquee bar, sticky buy now bar, color filter, and WhatsApp order buttons.

== Description ==

Optimus Bytes Woo Kit is a modular, lightweight, and high-conversion toolkit designed for WooCommerce stores. It empowers merchants with visual product swatches, shop sidebar color filters, promotional announcement bars, sticky purchase triggers, and direct WhatsApp customer ordering.

### 🌟 Active Modules Included:

#### 1. 🎨 Variation Swatches for WooCommerce (Product Pages, Loop Cards & Quick View)
* **Product Catalog Loop Swatches**: Displays mini color swatches on shop and category product cards with instant hover/click image switching.
* **Theme Quick View Modal Support**: Automatically initializes and renders visual swatches inside theme quick view popups.
* **Visual Color & Button Swatches**: Transforms plain variation dropdowns into interactive color circles, image thumbnails, and clean button/pill badges.
* **Smart Color Resolver**: Automatically recognizes dozens of color names and Indian saree hues (Royal Blue, Rani Pink, Maroon, Peacock Green, Zari Gold, Mustard, Bottle Green, etc.) and renders accurate colors without manual setup.
* **Real-time Out-of-Stock Detection**: Automatically disables and strikes through unavailable variation combinations with clean diagonal lines.
* **Hover Tooltips**: Floating labels displaying term names when hovering over swatches.
* **100% Core & AJAX Compatible**: Synchronizes seamlessly with WooCommerce's variation scripts, price changes, and gallery images.

#### 2. 🎯 Color Swatch Filter Widget & Shortcode
* **Visual Color Filtering for Shop Sidebars**: Replaces plain text attribute filter lists with visual Color Circles (Grid mode) or List items with Color Dots.
* **Category & Subcategory Aware Counts**: Accurately counts matching products in the currently viewed category tree, stock status, and active filters.
* **WordPress Widget & Shortcode**: Place anywhere using standard WordPress widgets or shortcode `[obwk_color_filter]`.
* **WooCommerce Query Integration**: Multi-select filtering supporting `OR` queries (`?filter_color=maroon,royal-blue`), preserving active price ranges and categories.
* **Instant "Clear Filter" Button**: Quick action button to reset active color selections.

#### 3. 📢 Scrolling Announcement Bar (Marquee Ticker)
* **Standard WordPress Core Hook**: Attached to `wp_body_open` for 100% standalone theme compatibility.
* **Continuous 60fps Marquee**: Seamless infinite loop without blank gaps or jitter.
* **Pause on Hover**: Allows customers to read and click announcement links easily.
* **Custom Message Slots**: Support for 6 dedicated message slots and an unlimited bulk multi-line editor.
* **Theme Styling**: Choose between *Adopt Current Theme Style*, *Luxury Saree Gold & Dark*, *Festive Crimson Red*, *Modern Dark*, or *Clean White*.

#### 4. 🛒 Sticky Floating Add to Cart & Buy Now Bar
* **Smart Scroll Trigger**: Automatically slides in smoothly on mobile & desktop when customers scroll past the main product purchase area (or can be set to Always Fixed).
* **1-Click "Buy Now" Direct Checkout**: Bypasses the cart page and routes users straight to checkout for instant payment.
* **View Cart with Live Counter**: Real-time item count badge with animated pop effect synced with WooCommerce cart fragments.
* **Mobile-First & Touch-Optimized**: Designed with compact icon buttons and zero horizontal scroll.

#### 5. 💬 Floating WhatsApp Contact Button
* **Sitewide Floating Button**: Sleek, modern floating WhatsApp button with subtle pulse wave animation and online status dot.
* **Smart Context Prefill**: Automatically prefills customer messages based on the page they are viewing.
* **Page Visibility Rules**: Choose to hide the button on Checkout, Cart, Single Product pages, or specific Page IDs.

#### 6. 🛍️ Single Product Page "Order on WhatsApp"
* **Direct WhatsApp Purchase**: Adds a prominent, stylish "Order on WhatsApp" button below the Add to Cart and Buy Now form.
* **Real-time Live Sync**: Dynamically updates the prefilled WhatsApp order message as customers change product quantities or select product variations.

#### 7. ⚡ Interactive Admin Hub
* **Modular Switchboard**: Turn any module on or off with instant AJAX toggle switches directly from the WordPress Admin menu.
* **No Database Bloat**: All plugin options are stored neatly in `wp_options` under `optimus_bytes_woo_kit_settings`.
* **Zero Legacy Code**: Fully decoupled from active themes — your settings stay intact even if you switch themes.

### 🚀 Developer Friendly & High Performance
* **100% HPOS Compatible**: Fully declared and tested with WooCommerce High-Performance Order Storage (`custom_order_tables`) and WooCommerce Blocks.
* **Modular Architecture**: Easily extendable with new modules extending `Abstract_Module`.
* **Asset Scoping**: CSS and JS are only enqueued on pages where the respective module is enabled.

== Installation ==

### Automatic Installation (WordPress Dashboard)
1. Log into your WordPress admin dashboard.
2. Go to **Plugins > Add New**.
3. Click **Upload Plugin** and select the `optimus-bytes-woo-kit.zip` file.
4. Click **Install Now** and then **Activate**.

### Configuration
1. Click on **Optimus Woo Kit** in your WordPress Admin sidebar.
2. Use the toggle switches to enable or disable features.
3. Click **Configure Settings** or go to **Appearance > Customize** or **Appearance > Widgets** to adjust settings live.

== Frequently Asked Questions ==

= Does this plugin support WooCommerce High-Performance Order Storage (HPOS)? =
Yes! Optimus Bytes Woo Kit is 100% compatible with HPOS (Custom Order Tables) and WooCommerce Cart & Checkout Blocks.

= Can I use this plugin with any WordPress theme? =
Yes! The plugin uses standard WordPress and WooCommerce core APIs and works with Astra, Storefront, Flatsome, Kadence, GeneratePress, OceanWP, TH Shop Mania, and custom themes.

= Where are the plugin settings stored? =
All module options are stored cleanly in `wp_options` under a single option key: `optimus_bytes_woo_kit_settings`.

== Changelog ==

= 1.3.0 =
* Added Product Loop Swatches on shop/category cards with live image switching on hover/click.
* Added Universal Quick View modal swatch initialization and compatibility.
* Added Color Swatch Filter Widget & Shortcode module with multi-select support, grid & list layouts, and instant filter reset button.

= 1.2.0 =
* Added Variation Swatches for WooCommerce with visual color circles, button badges, and out-of-stock strike-through detection.
* Added Smart Color Resolver for automatic saree color detection.

= 1.1.0 =
* Added Scrolling Announcement Bar (Marquee) module with standard `wp_body_open` core hook.
* Added Sticky Floating Add to Cart & Buy Now Bar module.

= 1.0.0 =
* Initial release with Floating WhatsApp Button & Product Page Order on WhatsApp modules.
