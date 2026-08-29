=== Optimus Bytes Woo Kit ===
Contributors: optimusbytes
Donate link: https://optimusbytes.com/
Tags: woocommerce, announcement bar, marquee, sticky add to cart, buy now, whatsapp, order on whatsapp, ecommerce, sales booster
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A modular, high-performance toolkit for WooCommerce stores featuring top announcement marquee bars, sticky purchase bars, 1-click buy now checkout, and smart WhatsApp ordering tools by Optimus Bytes Technologies.

== Description ==

**Optimus Bytes Woo Kit** is a modular, lightweight, and high-conversion toolkit designed for WooCommerce stores. It empowers merchants with customer engagement tools, instant checkout triggers, and promotional banners without slowing down your store.

### 🌟 Active Modules Included:

#### 1. 📢 Scrolling Announcement Bar (Marquee Ticker)
* **Standard WordPress Core Hook**: Attached to `wp_body_open` for 100% standalone theme compatibility.
* **Continuous 60fps Marquee**: Seamless infinite loop without blank gaps or jitter.
* **Pause on Hover**: Allows customers to read and click announcement links easily.
* **Custom Message Slots**: Support for multiple messages with individual emojis, text, and optional destination URLs.
* **Theme Styling**: Choose between *Adopt Current Theme Style*, *Luxury Saree Gold & Dark*, *Festive Crimson Red*, *Modern Dark*, or *Clean White*.

#### 2. 🛒 Sticky Floating Add to Cart & Buy Now Bar
* **Smart Scroll Trigger**: Automatically slides in smoothly on mobile & desktop when customers scroll past the main product purchase area (or can be set to Always Fixed).
* **1-Click "Buy Now" Direct Checkout**: Bypasses the cart page and routes users straight to the checkout page for instant purchase completion.
* **View Cart with Live Counter**: Real-time item count badge with animated pop effect synced with WooCommerce cart fragments.
* **Live Bi-directional Sync**: Seamlessly syncs quantities and selected product variations (color, size, fabric) in real-time.
* **Mobile-First & Touch-Optimized**: Designed with compact icon buttons and zero horizontal scroll.

#### 3. 💬 Floating WhatsApp Contact Button
* **Sitewide Floating Button**: Sleek, modern floating WhatsApp button with subtle pulse wave animation and online status dot.
* **Smart Context Prefill**: Automatically prefills customer messages based on the page they are viewing (Product title & URL on product pages, cart support on cart pages, general inquiries on other pages).
* **Page Visibility Rules**: Choose to hide the button on Checkout, Cart, Single Product pages, or specific Page IDs (e.g. 12, 45, 108) to eliminate checkout distraction.
* **Position & Styling**: Configurable bottom-left or bottom-right positioning with custom tooltips and greeting messages.

#### 4. 🛍️ Single Product Page "Order on WhatsApp"
* **Direct WhatsApp Purchase**: Adds a prominent, stylish "Order on WhatsApp" button below the Add to Cart and Buy Now buttons.
* **Real-time Live Sync**: Dynamically updates the prefilled WhatsApp order message as customers change product quantities or select product variations.
* **Pre-formatted Order Message**: Includes Product Title, Selected Variations, Price, SKU, Quantity, and Link.

#### 5. ⚡ Interactive Admin Hub
* **Modular Switchboard**: Turn any module on or off with instant AJAX toggle switches directly from the WordPress Admin menu.
* **No Database Bloat**: All plugin options are stored neatly in `wp_options` under `optimus_bytes_woo_kit_settings`.
* **Zero Legacy Code**: Fully decoupled from active themes — your settings stay intact even if you switch themes.

### 🚀 Developer Friendly & High Performance
* **100% HPOS Compatible**: Fully declared and tested with WooCommerce High-Performance Order Storage (`custom_order_tables`) and WooCommerce Blocks.
* **Modular Architecture**: Easily extendable with new modules extending `Abstract_Module`.
* **Asset Scoping**: CSS and JS are only enqueued on pages where the respective module is enabled and visible.
* **Extensible Hooks**: Full set of filters (`obwk_announcement_bar_hook`, `obwk_whatsapp_phone_number`, `obwk_product_whatsapp_message`, etc.).

== Installation ==

### Automatic Installation (WordPress Dashboard)
1. Log into your WordPress admin dashboard.
2. Go to **Plugins > Add New**.
3. Click **Upload Plugin** and select the `optimus-bytes-woo-kit.zip` file.
4. Click **Install Now** and then **Activate**.

### Configuration
1. Click on **Optimus Woo Kit** in your WordPress Admin sidebar.
2. Use the toggle switches to enable or disable features.
3. Click **Configure Settings** or go to **Appearance > Customize** to adjust settings live.

== Changelog ==

= 1.1.0 =
* Added Scrolling Announcement Bar (Marquee) module with standard `wp_body_open` core hook.
* Added Sticky Floating Add to Cart & Buy Now Bar module.
* Added 1-Click "Buy Now" direct checkout redirection and View Cart counter badge.
* Added mobile-first responsive layout and theme adoption presets.

= 1.0.0 =
* Initial release with Floating WhatsApp Button & Product Page Order on WhatsApp modules.
