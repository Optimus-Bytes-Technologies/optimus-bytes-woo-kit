<?php
/**
 * Scrolling Announcement Bar Module (Standard WordPress Hook)
 *
 * @package OptimusBytes\WooKit\Modules\Announcement_Bar
 */

namespace OptimusBytes\WooKit\Modules\Announcement_Bar;

use OptimusBytes\WooKit\Modules\Abstract_Module;

defined('ABSPATH') || exit;

class Announcement_Bar_Module extends Abstract_Module {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id          = 'announcement_bar';
        $this->title       = __('Scrolling Announcement Bar (Marquee)', 'optimus-bytes-woo-kit');
        $this->description = __('Sitewide top announcement bar with continuous smooth marquee ticker, pause on hover, custom links, and multi-message support.', 'optimus-bytes-woo-kit');
        $this->icon        = '📢';
        $this->category    = __('Marketing & Banners', 'optimus-bytes-woo-kit');
    }

    /**
     * Initialize module hooks
     */
    public function init() {
        add_action('customize_register', array($this, 'register_customizer_settings'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Standard WordPress 5.2+ body open hook
        $hook = apply_filters('obwk_announcement_bar_hook', 'wp_body_open');
        add_action($hook, array($this, 'render_announcement_bar'), 1);
    }

    /**
     * Check if announcement bar should display on current page
     *
     * @return bool
     */
    public function should_display() {
        if (!$this->is_enabled()) {
            return false;
        }

        $visibility = $this->get_option('visibility', 'all_pages');

        if ('homepage_only' === $visibility && !is_front_page() && !is_home()) {
            return false;
        }

        if ('hide_checkout_cart' === $visibility) {
            if ((function_exists('is_checkout') && is_checkout()) || (function_exists('is_cart') && is_cart())) {
                return false;
            }
        }

        return apply_filters('obwk_announcement_bar_should_display', true);
    }

    /**
     * Register Customizer settings
     *
     * @param \WP_Customize_Manager $wp_customize
     */
    public function register_customizer_settings($wp_customize) {
        $section_id = 'obwk_announcement_bar_section';

        $wp_customize->add_section($section_id, array(
            'title'       => __('Announcement Bar (Top Marquee)', 'optimus-bytes-woo-kit'),
            'description' => __('Configure top announcement bar messages, marquee scrolling speed, and themes.', 'optimus-bytes-woo-kit'),
            'priority'    => 119,
        ));

        // Enable / Disable
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[announcement_bar_enable]', array(
            'type'              => 'option',
            'default'           => true,
            'sanitize_callback' => 'wp_validate_boolean',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[announcement_bar_enable]', array(
            'label'    => __('Enable Announcement Bar', 'optimus-bytes-woo-kit'),
            'section'  => $section_id,
            'type'     => 'checkbox',
        ));

        // Theme Style
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[announcement_bar_style]', array(
            'type'              => 'option',
            'default'           => 'inherit_theme',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[announcement_bar_style]', array(
            'label'       => __('Theme Style', 'optimus-bytes-woo-kit'),
            'section'     => $section_id,
            'type'        => 'select',
            'choices'     => array(
                'inherit_theme'     => __('Adopt Current Theme Style (Recommended)', 'optimus-bytes-woo-kit'),
                'luxury_saree_gold' => __('Luxury Saree Gold & Dark', 'optimus-bytes-woo-kit'),
                'festive_crimson'   => __('Festive Crimson Red & Gold', 'optimus-bytes-woo-kit'),
                'modern_dark'       => __('Modern Dark Slate', 'optimus-bytes-woo-kit'),
                'clean_white'       => __('Clean White Minimal', 'optimus-bytes-woo-kit'),
            ),
        ));

        // Scroll Speed
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[announcement_bar_speed]', array(
            'type'              => 'option',
            'default'           => 'normal',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[announcement_bar_speed]', array(
            'label'    => __('Marquee Scroll Speed', 'optimus-bytes-woo-kit'),
            'section'  => $section_id,
            'type'     => 'select',
            'choices'  => array(
                'normal' => __('Normal (32s loop)', 'optimus-bytes-woo-kit'),
                'slow'   => __('Slow & Smooth (45s loop)', 'optimus-bytes-woo-kit'),
                'fast'   => __('Fast Ticker (20s loop)', 'optimus-bytes-woo-kit'),
            ),
        ));

        // Pause on Hover
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[announcement_bar_pause_hover]', array(
            'type'              => 'option',
            'default'           => true,
            'sanitize_callback' => 'wp_validate_boolean',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[announcement_bar_pause_hover]', array(
            'label'    => __('Pause Scrolling on Hover', 'optimus-bytes-woo-kit'),
            'section'  => $section_id,
            'type'     => 'checkbox',
        ));

        // Page Visibility
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[announcement_bar_visibility]', array(
            'type'              => 'option',
            'default'           => 'all_pages',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[announcement_bar_visibility]', array(
            'label'    => __('Page Visibility', 'optimus-bytes-woo-kit'),
            'section'  => $section_id,
            'type'     => 'select',
            'choices'  => array(
                'all_pages'          => __('All Store Pages (Sitewide)', 'optimus-bytes-woo-kit'),
                'homepage_only'      => __('Homepage Only', 'optimus-bytes-woo-kit'),
                'hide_checkout_cart' => __('Hide on Cart & Checkout Pages', 'optimus-bytes-woo-kit'),
            ),
        ));

        // ==========================================
        // Bulk Unlimited Messages Editor
        // ==========================================
        $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[announcement_bar_bulk_messages]', array(
            'type'              => 'option',
            'default'           => '',
            'sanitize_callback' => 'sanitize_textarea_field',
        ));
        $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[announcement_bar_bulk_messages]', array(
            'label'       => __('Bulk / Unlimited Messages (Optional)', 'optimus-bytes-woo-kit'),
            'description' => __('Enter one message per line in format: Icon | Text | URL (Optional). Example:
🚚 | Free Shipping Above ₹999 | /shop
✨ | Flat 10% OFF Code: WELCOME10 |
If left empty, slots below are used.', 'optimus-bytes-woo-kit'),
            'section'     => $section_id,
            'type'        => 'textarea',
        ));

        // ==========================================
        // Individual Message Slots (1 to 6)
        // ==========================================
        for ($i = 1; $i <= 6; $i++) {
            $default_icon = ($i === 1) ? '🚚' : (($i === 2) ? '✨' : (($i === 3) ? '🥻' : ''));
            $default_text = ($i === 1)
                ? 'Free Shipping Across India on Orders Above ₹999'
                : (($i === 2)
                    ? 'Special Offer: Flat 10% OFF on First Order | Use Code: WELCOME10'
                    : (($i === 3)
                        ? '100% Authentic Handloom Silk Sarees Direct From Weavers'
                        : ''));

            // Icon
            $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[announcement_bar_msg_' . $i . '_icon]', array(
                'type'              => 'option',
                'default'           => $default_icon,
                'sanitize_callback' => 'sanitize_text_field',
            ));
            $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[announcement_bar_msg_' . $i . '_icon]', array(
                'label'    => sprintf(__('Message %d: Icon / Emoji', 'optimus-bytes-woo-kit'), $i),
                'section'  => $section_id,
                'type'     => 'text',
            ));

            // Text
            $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[announcement_bar_msg_' . $i . '_text]', array(
                'type'              => 'option',
                'default'           => $default_text,
                'sanitize_callback' => 'sanitize_text_field',
            ));
            $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[announcement_bar_msg_' . $i . '_text]', array(
                'label'    => sprintf(__('Message %d: Text', 'optimus-bytes-woo-kit'), $i),
                'section'  => $section_id,
                'type'     => 'text',
            ));

            // Link
            $wp_customize->add_setting(OBWK_SETTINGS_OPTION . '[announcement_bar_msg_' . $i . '_link]', array(
                'type'              => 'option',
                'default'           => '',
                'sanitize_callback' => 'esc_url_raw',
            ));
            $wp_customize->add_control(OBWK_SETTINGS_OPTION . '[announcement_bar_msg_' . $i . '_link]', array(
                'label'    => sprintf(__('Message %d: Link URL (Optional)', 'optimus-bytes-woo-kit'), $i),
                'section'  => $section_id,
                'type'     => 'url',
            ));
        }
    }

    /**
     * Enqueue CSS
     */
    public function enqueue_scripts() {
        if (!$this->should_display()) {
            return;
        }

        wp_enqueue_style(
            'obwk-announcement-bar-style',
            OBWK_PLUGIN_URL . 'assets/css/announcement-bar.css',
            array(),
            OBWK_VERSION
        );
    }

    /**
     * Get compiled list of announcement messages
     *
     * @return array
     */
    public function get_messages() {
        $messages = array();

        // 1. Check Bulk Multi-line editor first
        $bulk_text = $this->get_option('bulk_messages', '');
        if (!empty(trim($bulk_text))) {
            $lines = explode("
", str_replace("", "", trim($bulk_text)));
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }

                $parts = explode('|', $line);
                if (count($parts) >= 2) {
                    $icon = trim($parts[0]);
                    $text = trim($parts[1]);
                    $link = isset($parts[2]) ? trim($parts[2]) : '';
                } else {
                    $icon = '';
                    $text = trim($parts[0]);
                    $link = '';
                }

                if (!empty($text)) {
                    $messages[] = array(
                        'icon' => $icon,
                        'text' => $text,
                        'link' => $link,
                    );
                }
            }
        }

        // 2. Individual Message Slots (1 to 6)
        if (empty($messages)) {
            for ($i = 1; $i <= 6; $i++) {
                $default_icon = ($i === 1) ? '🚚' : (($i === 2) ? '✨' : (($i === 3) ? '🥻' : ''));
                $default_text = ($i === 1)
                    ? __('Free Shipping Across India on Orders Above ₹999', 'optimus-bytes-woo-kit')
                    : (($i === 2)
                        ? __('Special Offer: Flat 10% OFF on First Order | Use Code: WELCOME10', 'optimus-bytes-woo-kit')
                        : (($i === 3)
                            ? __('100% Authentic Handloom Silk Sarees Direct From Weavers', 'optimus-bytes-woo-kit')
                            : ''));

                $text = $this->get_option('msg_' . $i . '_text', $default_text);
                $icon = $this->get_option('msg_' . $i . '_icon', $default_icon);
                $link = $this->get_option('msg_' . $i . '_link', '');

                if (!empty($text)) {
                    $messages[] = array(
                        'icon' => $icon,
                        'text' => $text,
                        'link' => $link,
                    );
                }
            }
        }

        return apply_filters('obwk_announcement_messages', $messages);
    }

    /**
     * Render announcement bar markup
     */
    public function render_announcement_bar() {
        if (!$this->should_display()) {
            return;
        }

        $messages = $this->get_messages();
        if (empty($messages)) {
            return;
        }

        $theme_style  = $this->get_option('style', 'inherit_theme');
        $speed        = $this->get_option('speed', 'normal');
        $pause_hover  = (bool) $this->get_option('pause_hover', true);

        $pause_class = $pause_hover ? 'is-pause-on-hover' : '';
        ?>
        <div class="obwk-announcement-bar obwk-style-<?php echo esc_attr($theme_style); ?> obwk-speed-<?php echo esc_attr($speed); ?> <?php echo esc_attr($pause_class); ?>" 
             role="region" 
             aria-label="<?php esc_attr_e('Announcements', 'optimus-bytes-woo-kit'); ?>">
            <div class="obwk-announcement-track">
                <?php for ($i = 0; $i < 2; $i++) : // Duplicated groups for seamless 60fps infinite loop without blank gaps ?>
                    <div class="obwk-announcement-group" <?php echo $i === 1 ? 'aria-hidden="true"' : ''; ?>>
                        <?php foreach ($messages as $item) : ?>
                            <div class="obwk-announcement-item">
                                <?php if (!empty($item['icon'])) : ?>
                                    <span class="obwk-announcement-icon" aria-hidden="true"><?php echo esc_html($item['icon']); ?></span>
                                <?php endif; ?>
                                
                                <?php if (!empty($item['link'])) : ?>
                                    <a href="<?php echo esc_url($item['link']); ?>" class="obwk-announcement-link">
                                        <?php echo esc_html($item['text']); ?>
                                    </a>
                                <?php else : ?>
                                    <span class="obwk-announcement-text"><?php echo esc_html($item['text']); ?></span>
                                <?php endif; ?>

                                <span class="obwk-announcement-separator" aria-hidden="true">✦</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
        <?php
    }
}
