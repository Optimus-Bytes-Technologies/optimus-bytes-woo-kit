<?php
/**
 * Variation Swatches Admin - WooCommerce Attribute Term Meta & Management
 *
 * @package OptimusBytes\WooKit\Modules\Variation_Swatches
 */

namespace OptimusBytes\WooKit\Modules\Variation_Swatches;

defined('ABSPATH') || exit;

class Variation_Swatches_Admin {

    /**
     * Parent Module Instance
     *
     * @var Variation_Swatches_Module
     */
    private $module;

    /**
     * Constructor
     *
     * @param Variation_Swatches_Module $module
     */
    public function __construct(Variation_Swatches_Module $module) {
        $this->module = $module;
    }

    /**
     * Initialize Admin Hooks
     */
    public function init() {
        add_action('admin_init', array($this, 'register_attribute_taxonomy_hooks'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Register Form and Column Hooks for all WooCommerce Product Attribute Taxonomies
     */
    public function register_attribute_taxonomy_hooks() {
        if (!function_exists('wc_get_attribute_taxonomies')) {
            return;
        }

        $attribute_taxonomies = wc_get_attribute_taxonomies();
        if (empty($attribute_taxonomies)) {
            return;
        }

        foreach ($attribute_taxonomies as $tax) {
            $taxonomy = wc_attribute_taxonomy_name($tax->attribute_name);

            // Term Add/Edit Form Fields
            add_action("{$taxonomy}_add_form_fields", array($this, 'render_add_term_fields'));
            add_action("{$taxonomy}_edit_form_fields", array($this, 'render_edit_term_fields'), 10, 2);

            // Term Save Hooks
            add_action("created_{$taxonomy}", array($this, 'save_term_fields'), 10, 2);
            add_action("edited_{$taxonomy}", array($this, 'save_term_fields'), 10, 2);

            // Term List Table Custom Columns
            add_filter("manage_edit-{$taxonomy}_columns", array($this, 'add_attribute_columns'));
            add_filter("manage_{$taxonomy}_custom_column", array($this, 'render_attribute_column'), 10, 3);
        }
    }

    /**
     * Enqueue Admin Scripts and Styles on Product Attribute Screens
     *
     * @param string $hook
     */
    public function enqueue_admin_assets($hook) {
        if (!in_array($hook, array('edit-tags.php', 'term.php'), true)) {
            return;
        }

        $screen = get_current_screen();
        if (!$screen || 'product' !== $screen->post_type || 0 !== strpos($screen->taxonomy, 'pa_')) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');

        wp_enqueue_style(
            'obwk-swatches-admin-style',
            OBWK_PLUGIN_URL . 'assets/css/swatches-admin.css',
            array(),
            OBWK_VERSION
        );

        wp_enqueue_script(
            'obwk-swatches-admin-script',
            OBWK_PLUGIN_URL . 'assets/js/swatches-admin.js',
            array('jquery', 'wp-color-picker'),
            OBWK_VERSION,
            true
        );

        wp_localize_script('obwk-swatches-admin-script', 'obwkSwatchesAdmin', array(
            'choose_image' => __('Choose Swatch Image', 'optimus-bytes-woo-kit'),
            'use_image'    => __('Use Image as Swatch', 'optimus-bytes-woo-kit'),
        ));
    }

    /**
     * Render Swatch Fields on "Add New Term" screen
     *
     * @param string $taxonomy
     */
    public function render_add_term_fields($taxonomy) {
        ?>
        <div class="form-field obwk-swatch-field-wrap">
            <label for="obwk_swatch_type"><?php esc_html_e('Swatch Type', 'optimus-bytes-woo-kit'); ?></label>
            <select name="obwk_swatch_type" id="obwk_swatch_type" class="obwk-swatch-type-select">
                <option value="color"><?php esc_html_e('Color (Single Solid Color)', 'optimus-bytes-woo-kit'); ?></option>
                <option value="dual_color"><?php esc_html_e('Dual Color / Two-Tone (e.g. Body & Border)', 'optimus-bytes-woo-kit'); ?></option>
                <option value="image"><?php esc_html_e('Image Swatch (Fabric / Pattern Thumbnail)', 'optimus-bytes-woo-kit'); ?></option>
                <option value="none"><?php esc_html_e('Default / Text Button', 'optimus-bytes-woo-kit'); ?></option>
            </select>
            <p class="description"><?php esc_html_e('Choose how this attribute term is visually displayed to shoppers.', 'optimus-bytes-woo-kit'); ?></p>
        </div>

        <!-- Primary Color -->
        <div class="form-field obwk-swatch-field-wrap obwk-field-color">
            <label for="obwk_swatch_color"><?php esc_html_e('Primary Color', 'optimus-bytes-woo-kit'); ?></label>
            <input type="text" name="obwk_swatch_color" id="obwk_swatch_color" class="obwk-color-picker" value="" data-default-color="#14b8a6" />
            <p class="description"><?php esc_html_e('Select the primary / main color hex code.', 'optimus-bytes-woo-kit'); ?></p>
        </div>

        <!-- Secondary Color (Dual Color Mode) -->
        <div class="form-field obwk-swatch-field-wrap obwk-field-secondary-color" style="display: none;">
            <label for="obwk_swatch_color_secondary"><?php esc_html_e('Secondary Color (Two-Tone)', 'optimus-bytes-woo-kit'); ?></label>
            <input type="text" name="obwk_swatch_color_secondary" id="obwk_swatch_color_secondary" class="obwk-color-picker" value="" data-default-color="#ec4899" />
            <p class="description"><?php esc_html_e('Select the secondary border/pallu color for a 50/50 diagonal split swatch.', 'optimus-bytes-woo-kit'); ?></p>
        </div>

        <!-- Image Swatch -->
        <div class="form-field obwk-swatch-field-wrap obwk-field-image" style="display: none;">
            <label><?php esc_html_e('Swatch Thumbnail Image', 'optimus-bytes-woo-kit'); ?></label>
            <div class="obwk-image-uploader-wrap">
                <div class="obwk-image-preview" style="display: none;">
                    <img src="" alt="" />
                </div>
                <input type="hidden" name="obwk_swatch_image_id" id="obwk_swatch_image_id" value="" />
                <button type="button" class="button obwk-upload-image-btn"><?php esc_html_e('Upload / Select Image', 'optimus-bytes-woo-kit'); ?></button>
                <button type="button" class="button obwk-remove-image-btn" style="display: none;"><?php esc_html_e('Remove Image', 'optimus-bytes-woo-kit'); ?></button>
            </div>
            <p class="description"><?php esc_html_e('Upload a small thumbnail image representing this fabric, pattern, or color swatch.', 'optimus-bytes-woo-kit'); ?></p>
        </div>

        <!-- Live Preview -->
        <div class="form-field obwk-swatch-field-wrap obwk-live-preview-wrap">
            <label><?php esc_html_e('Live Swatch Preview', 'optimus-bytes-woo-kit'); ?></label>
            <div class="obwk-admin-preview-box">
                <span class="obwk-admin-preview-circle" id="obwk_admin_preview_circle"></span>
                <span class="obwk-admin-preview-text" id="obwk_admin_preview_text"><?php esc_html_e('Preview updates automatically', 'optimus-bytes-woo-kit'); ?></span>
            </div>
        </div>
        <?php
    }

    /**
     * Render Swatch Fields on "Edit Term" screen
     *
     * @param \WP_Term $term
     * @param string $taxonomy
     */
    public function render_edit_term_fields($term, $taxonomy) {
        $term_id         = $term->term_id;
        $swatch_type     = get_term_meta($term_id, 'obwk_swatch_type', true);
        $swatch_color    = get_term_meta($term_id, 'obwk_swatch_color', true);
        $secondary_color = get_term_meta($term_id, 'obwk_swatch_color_secondary', true);
        $image_id        = get_term_meta($term_id, 'obwk_swatch_image_id', true);
        $image_url       = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : '';

        // Fallback to thvs_color if not set yet
        if (empty($swatch_color)) {
            $thvs_color = get_term_meta($term_id, 'thvs_color', true);
            if (!empty($thvs_color)) {
                $swatch_color = $thvs_color;
            }
        }

        // Fallback default from built-in dictionary
        if (empty($swatch_type)) {
            $swatch_type = 'color';
        }
        if (empty($swatch_color)) {
            $swatch_color = Variation_Swatches_Module::resolve_color_hex($term);
            if (strpos($swatch_color, 'gradient') !== false) {
                $swatch_type = 'dual_color';
            }
        }
        ?>
        <tr class="form-field obwk-swatch-field-wrap">
            <th scope="row"><label for="obwk_swatch_type"><?php esc_html_e('Swatch Type', 'optimus-bytes-woo-kit'); ?></label></th>
            <td>
                <select name="obwk_swatch_type" id="obwk_swatch_type" class="obwk-swatch-type-select">
                    <option value="color" <?php selected($swatch_type, 'color'); ?>><?php esc_html_e('Color (Single Solid Color)', 'optimus-bytes-woo-kit'); ?></option>
                    <option value="dual_color" <?php selected($swatch_type, 'dual_color'); ?>><?php esc_html_e('Dual Color / Two-Tone (e.g. Body & Border)', 'optimus-bytes-woo-kit'); ?></option>
                    <option value="image" <?php selected($swatch_type, 'image'); ?>><?php esc_html_e('Image Swatch (Fabric / Pattern Thumbnail)', 'optimus-bytes-woo-kit'); ?></option>
                    <option value="none" <?php selected($swatch_type, 'none'); ?>><?php esc_html_e('Default / Text Button', 'optimus-bytes-woo-kit'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('Choose how this attribute term is visually displayed to shoppers.', 'optimus-bytes-woo-kit'); ?></p>
            </td>
        </tr>

        <!-- Primary Color -->
        <tr class="form-field obwk-swatch-field-wrap obwk-field-color" style="<?php echo ('image' === $swatch_type || 'none' === $swatch_type) ? 'display:none;' : ''; ?>">
            <th scope="row"><label for="obwk_swatch_color"><?php esc_html_e('Primary Color', 'optimus-bytes-woo-kit'); ?></label></th>
            <td>
                <input type="text" name="obwk_swatch_color" id="obwk_swatch_color" class="obwk-color-picker" value="<?php echo esc_attr($swatch_color); ?>" data-default-color="#14b8a6" />
                <p class="description"><?php esc_html_e('Select the primary / main color hex code.', 'optimus-bytes-woo-kit'); ?></p>
            </td>
        </tr>

        <!-- Secondary Color (Dual Color Mode) -->
        <tr class="form-field obwk-swatch-field-wrap obwk-field-secondary-color" style="<?php echo ('dual_color' !== $swatch_type) ? 'display:none;' : ''; ?>">
            <th scope="row"><label for="obwk_swatch_color_secondary"><?php esc_html_e('Secondary Color (Two-Tone)', 'optimus-bytes-woo-kit'); ?></label></th>
            <td>
                <input type="text" name="obwk_swatch_color_secondary" id="obwk_swatch_color_secondary" class="obwk-color-picker" value="<?php echo esc_attr($secondary_color); ?>" data-default-color="#ec4899" />
                <p class="description"><?php esc_html_e('Select the secondary border/pallu color for a 50/50 diagonal split swatch.', 'optimus-bytes-woo-kit'); ?></p>
            </td>
        </tr>

        <!-- Image Swatch -->
        <tr class="form-field obwk-swatch-field-wrap obwk-field-image" style="<?php echo ('image' !== $swatch_type) ? 'display:none;' : ''; ?>">
            <th scope="row"><label><?php esc_html_e('Swatch Thumbnail Image', 'optimus-bytes-woo-kit'); ?></label></th>
            <td>
                <div class="obwk-image-uploader-wrap">
                    <div class="obwk-image-preview" style="<?php echo empty($image_url) ? 'display:none;' : ''; ?>">
                        <img src="<?php echo esc_url($image_url); ?>" alt="" />
                    </div>
                    <input type="hidden" name="obwk_swatch_image_id" id="obwk_swatch_image_id" value="<?php echo esc_attr($image_id); ?>" />
                    <button type="button" class="button obwk-upload-image-btn"><?php esc_html_e('Upload / Select Image', 'optimus-bytes-woo-kit'); ?></button>
                    <button type="button" class="button obwk-remove-image-btn" style="<?php echo empty($image_url) ? 'display:none;' : ''; ?>"><?php esc_html_e('Remove Image', 'optimus-bytes-woo-kit'); ?></button>
                </div>
                <p class="description"><?php esc_html_e('Upload a small thumbnail image representing this fabric, pattern, or color swatch.', 'optimus-bytes-woo-kit'); ?></p>
            </td>
        </tr>

        <!-- Live Preview -->
        <tr class="form-field obwk-swatch-field-wrap obwk-live-preview-wrap">
            <th scope="row"><label><?php esc_html_e('Live Swatch Preview', 'optimus-bytes-woo-kit'); ?></label></th>
            <td>
                <div class="obwk-admin-preview-box">
                    <span class="obwk-admin-preview-circle" id="obwk_admin_preview_circle"></span>
                    <span class="obwk-admin-preview-text" id="obwk_admin_preview_text"><?php esc_html_e('Preview updates automatically', 'optimus-bytes-woo-kit'); ?></span>
                </div>
            </td>
        </tr>
        <?php
    }

    /**
     * Save Swatch Term Meta Fields
     *
     * @param int $term_id
     * @param int $tt_id
     */
    public function save_term_fields($term_id, $tt_id = 0) {
        if (!current_user_can('edit_term', $term_id) && !current_user_can('manage_product_terms')) {
            return;
        }

        // Swatch Type
        if (isset($_POST['obwk_swatch_type'])) {
            $swatch_type = sanitize_key($_POST['obwk_swatch_type']);
            update_term_meta($term_id, 'obwk_swatch_type', $swatch_type);
        }

        // Primary Color
        if (isset($_POST['obwk_swatch_color'])) {
            $swatch_color = sanitize_text_field(wp_unslash($_POST['obwk_swatch_color']));
            update_term_meta($term_id, 'obwk_swatch_color', $swatch_color);
            // Backward compatibility sync
            update_term_meta($term_id, 'thvs_color', $swatch_color);
        }

        // Secondary Color
        if (isset($_POST['obwk_swatch_color_secondary'])) {
            $secondary_color = sanitize_text_field(wp_unslash($_POST['obwk_swatch_color_secondary']));
            update_term_meta($term_id, 'obwk_swatch_color_secondary', $secondary_color);
        }

        // Image Swatch ID
        if (isset($_POST['obwk_swatch_image_id'])) {
            $image_id = absint($_POST['obwk_swatch_image_id']);
            update_term_meta($term_id, 'obwk_swatch_image_id', $image_id);
            // Backward compatibility sync
            update_term_meta($term_id, 'thvs_image', $image_id);
        }
    }

    /**
     * Add Swatch Preview Column to Product Attribute Term List Table
     *
     * @param array $columns
     * @return array
     */
    public function add_attribute_columns($columns) {
        $new_columns = array();
        if (isset($columns['cb'])) {
            $new_columns['cb'] = $columns['cb'];
        }

        $new_columns['obwk_swatch_preview'] = __('Swatch', 'optimus-bytes-woo-kit');

        foreach ($columns as $key => $val) {
            if ('cb' !== $key) {
                $new_columns[$key] = $val;
            }
        }

        return $new_columns;
    }

    /**
     * Render Swatch Preview Column Content
     *
     * @param string $content
     * @param string $column_name
     * @param int $term_id
     * @return string
     */
    public function render_attribute_column($content, $column_name, $term_id) {
        if ('obwk_swatch_preview' !== $column_name) {
            return $content;
        }

        $term = get_term($term_id);
        if (!$term || is_wp_error($term)) {
            return $content;
        }

        $swatch_type = get_term_meta($term_id, 'obwk_swatch_type', true);
        $image_id    = get_term_meta($term_id, 'obwk_swatch_image_id', true);

        if ('image' === $swatch_type && $image_id) {
            $image_url = wp_get_attachment_image_url($image_id, 'thumbnail');
            if ($image_url) {
                return sprintf(
                    '<img src="%s" class="obwk-admin-table-swatch obwk-admin-table-swatch-img" alt="%s" />',
                    esc_url($image_url),
                    esc_attr($term->name)
                );
            }
        }

        $color_css = Variation_Swatches_Module::resolve_color_hex($term);

        return sprintf(
            '<span class="obwk-admin-table-swatch" style="background: %s;" title="%s"></span>',
            esc_attr($color_css),
            esc_attr($term->name)
        );
    }
}
