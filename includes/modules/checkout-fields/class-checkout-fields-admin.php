<?php
/**
 * Checkout Field Customizer Admin Manager & AJAX Handlers
 *
 * @package OptimusBytes\WooKit\Modules\Checkout_Fields
 */

namespace OptimusBytes\WooKit\Modules\Checkout_Fields;

defined('ABSPATH') || exit;

class Checkout_Fields_Admin {

    /**
     * Parent Module Instance
     *
     * @var Checkout_Fields_Module
     */
    private $module;

    /**
     * Constructor
     *
     * @param Checkout_Fields_Module $module
     */
    public function __construct(Checkout_Fields_Module $module) {
        $this->module = $module;
    }

    /**
     * Initialize Admin Hooks
     */
    public function init() {
        add_action('admin_menu', array($this, 'register_admin_submenus'), 25);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // AJAX actions
        add_action('wp_ajax_obwk_save_checkout_fields', array($this, 'handle_save_fields_ajax'));
        add_action('wp_ajax_obwk_add_custom_checkout_field', array($this, 'handle_add_field_ajax'));
        add_action('wp_ajax_obwk_delete_custom_checkout_field', array($this, 'handle_delete_field_ajax'));
        add_action('wp_ajax_obwk_reset_checkout_fields', array($this, 'handle_reset_fields_ajax'));
    }

    /**
     * Register Submenu under Optimus Woo Kit
     */
    public function register_admin_submenus() {
        add_submenu_page(
            'optimus-woo-kit',
            __('Checkout Fields Manager', 'optimus-bytes-woo-kit'),
            __('Checkout Fields', 'optimus-bytes-woo-kit'),
            'manage_options',
            'obwk-checkout-fields',
            array($this, 'render_admin_page')
        );
    }

    /**
     * Enqueue Admin Assets on Checkout Fields page
     *
     * @param string $hook
     */
    public function enqueue_admin_assets($hook) {
        if ('optimus-woo-kit_page_obwk-checkout-fields' !== $hook && 'toplevel_page_obwk-checkout-fields' !== $hook) {
            return;
        }

        // jQuery UI Sortable for drag-and-drop
        wp_enqueue_script('jquery-ui-sortable');

        // Module Admin Styles
        wp_enqueue_style(
            'obwk-checkout-fields-admin-style',
            OBWK_PLUGIN_URL . 'assets/css/checkout-fields-admin.css',
            array(),
            OBWK_VERSION
        );

        // Module Admin Scripts
        wp_enqueue_script(
            'obwk-checkout-fields-admin-script',
            OBWK_PLUGIN_URL . 'assets/js/checkout-fields-admin.js',
            array('jquery', 'jquery-ui-sortable'),
            OBWK_VERSION,
            true
        );

        wp_localize_script('obwk-checkout-fields-admin-script', 'obwkCheckoutFields', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('obwk_checkout_fields_nonce'),
            'i18n'     => array(
                'saving'             => __('Saving changes...', 'optimus-bytes-woo-kit'),
                'saved'              => __('Checkout fields saved successfully!', 'optimus-bytes-woo-kit'),
                'error'              => __('An error occurred. Please try again.', 'optimus-bytes-woo-kit'),
                'confirm_delete'     => __('Are you sure you want to delete this custom field? This cannot be undone.', 'optimus-bytes-woo-kit'),
                'confirm_reset'      => __('Are you sure you want to reset this section back to standard WooCommerce defaults? Any custom modifications will be lost.', 'optimus-bytes-woo-kit'),
                'reset_success'      => __('Fields restored to default successfully!', 'optimus-bytes-woo-kit'),
                'field_key_required' => __('Please enter a valid, unique Field Name/Key.', 'optimus-bytes-woo-kit'),
                'label_required'     => __('Please enter a Field Label.', 'optimus-bytes-woo-kit'),
            ),
        ));
    }

    /**
     * AJAX: Save Field Configurations (Order, Reorder, Enable/Disable, Required, Width, Labels)
     */
    public function handle_save_fields_ajax() {
        check_ajax_referer('obwk_checkout_fields_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized permission.', 'optimus-bytes-woo-kit')));
        }

        $section = isset($_POST['section']) ? sanitize_key($_POST['section']) : '';
        $raw_fields = isset($_POST['fields']) ? (array) $_POST['fields'] : array();

        if (!in_array($section, array('billing', 'shipping', 'order'), true)) {
            wp_send_json_error(array('message' => __('Invalid checkout section.', 'optimus-bytes-woo-kit')));
        }

        $current_config = $this->module->get_fields_config();
        $updated_section = array();

        $priority_counter = 10;

        foreach ($raw_fields as $field_key => $field_data) {
            $key = sanitize_key($field_key);
            if (empty($key)) {
                continue;
            }

            // Existing field fallback
            $existing = isset($current_config[$section][$key]) ? $current_config[$section][$key] : array();

            $label       = isset($field_data['label']) ? sanitize_text_field(wp_unslash($field_data['label'])) : (isset($existing['label']) ? $existing['label'] : '');
            $placeholder = isset($field_data['placeholder']) ? sanitize_text_field(wp_unslash($field_data['placeholder'])) : (isset($existing['placeholder']) ? $existing['placeholder'] : '');
            $type        = !empty($field_data['type']) ? sanitize_key($field_data['type']) : (isset($existing['type']) ? $existing['type'] : 'text');
            $required    = isset($field_data['required']) ? filter_var($field_data['required'], FILTER_VALIDATE_BOOLEAN) : false;
            $enabled     = isset($field_data['enabled']) ? filter_var($field_data['enabled'], FILTER_VALIDATE_BOOLEAN) : true;
            $class       = !empty($field_data['class']) ? sanitize_text_field($field_data['class']) : 'form-row-wide';
            $default_val = isset($field_data['default']) ? sanitize_text_field(wp_unslash($field_data['default'])) : (isset($existing['default']) ? $existing['default'] : '');
            $is_custom   = isset($field_data['custom']) ? filter_var($field_data['custom'], FILTER_VALIDATE_BOOLEAN) : (!empty($existing['custom']));
            $show_email  = isset($field_data['show_in_email']) ? filter_var($field_data['show_in_email'], FILTER_VALIDATE_BOOLEAN) : true;
            $show_order  = isset($field_data['show_in_order']) ? filter_var($field_data['show_in_order'], FILTER_VALIDATE_BOOLEAN) : true;

            $options = array();
            if (isset($field_data['options']) && is_array($field_data['options'])) {
                foreach ($field_data['options'] as $opt_val => $opt_label) {
                    $opt_k = sanitize_text_field(wp_unslash($opt_val));
                    $opt_v = sanitize_text_field(wp_unslash($opt_label));
                    if ('' !== $opt_k) {
                        $options[$opt_k] = $opt_v;
                    }
                }
            } elseif (isset($existing['options'])) {
                $options = $existing['options'];
            }

            $updated_section[$key] = array(
                'name'          => $key,
                'label'         => $label,
                'placeholder'   => $placeholder,
                'type'          => $type,
                'required'      => $required,
                'enabled'       => $enabled,
                'class'         => $class,
                'priority'      => $priority_counter,
                'default'       => $default_val,
                'options'       => $options,
                'custom'        => $is_custom,
                'show_in_email' => $show_email,
                'show_in_order' => $show_order,
            );

            $priority_counter += 10;
        }

        $current_config[$section] = $updated_section;
        update_option(Checkout_Fields_Module::OPTION_KEY, $current_config);

        wp_send_json_success(array(
            'message' => __('Checkout fields saved successfully.', 'optimus-bytes-woo-kit'),
            'section' => $section,
        ));
    }

    /**
     * AJAX: Add or Edit a Custom Field
     */
    public function handle_add_field_ajax() {
        check_ajax_referer('obwk_checkout_fields_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized permission.', 'optimus-bytes-woo-kit')));
        }

        $section = isset($_POST['section']) ? sanitize_key($_POST['section']) : 'billing';
        $key     = isset($_POST['name']) ? sanitize_key($_POST['name']) : '';
        $label   = isset($_POST['label']) ? sanitize_text_field(wp_unslash($_POST['label'])) : '';

        if (!in_array($section, array('billing', 'shipping', 'order'), true)) {
            wp_send_json_error(array('message' => __('Invalid section.', 'optimus-bytes-woo-kit')));
        }

        if (empty($key)) {
            wp_send_json_error(array('message' => __('Field name/key cannot be empty.', 'optimus-bytes-woo-kit')));
        }

        // Auto prefix key if missing section prefix
        if ('billing' === $section && 0 !== strpos($key, 'billing_')) {
            $key = 'billing_' . $key;
        } elseif ('shipping' === $section && 0 !== strpos($key, 'shipping_')) {
            $key = 'shipping_' . $key;
        } elseif ('order' === $section && 0 !== strpos($key, 'order_')) {
            $key = 'order_' . $key;
        }

        if (empty($label)) {
            wp_send_json_error(array('message' => __('Field label cannot be empty.', 'optimus-bytes-woo-kit')));
        }

        $type        = isset($_POST['type']) ? sanitize_key($_POST['type']) : 'text';
        $placeholder = isset($_POST['placeholder']) ? sanitize_text_field(wp_unslash($_POST['placeholder'])) : '';
        $default_val = isset($_POST['default']) ? sanitize_text_field(wp_unslash($_POST['default'])) : '';
        $class       = isset($_POST['class']) ? sanitize_text_field($_POST['class']) : 'form-row-wide';
        $required    = !empty($_POST['required']);
        $enabled     = isset($_POST['enabled']) ? filter_var($_POST['enabled'], FILTER_VALIDATE_BOOLEAN) : true;
        $show_email  = isset($_POST['show_in_email']) ? filter_var($_POST['show_in_email'], FILTER_VALIDATE_BOOLEAN) : true;
        $show_order  = isset($_POST['show_in_order']) ? filter_var($_POST['show_in_order'], FILTER_VALIDATE_BOOLEAN) : true;

        // Process options for select/radio
        $options = array();
        if (isset($_POST['options']) && is_array($_POST['options'])) {
            foreach ($_POST['options'] as $item) {
                if (isset($item['key']) && isset($item['label'])) {
                    $opt_k = sanitize_text_field(wp_unslash($item['key']));
                    $opt_v = sanitize_text_field(wp_unslash($item['label']));
                    if ('' !== $opt_k) {
                        $options[$opt_k] = $opt_v;
                    }
                }
            }
        }

        $config = $this->module->get_fields_config();
        $is_edit = isset($config[$section][$key]);
        $existing_custom = isset($config[$section][$key]['custom']) ? $config[$section][$key]['custom'] : true;

        // Calculate priority
        $priority = $is_edit && isset($config[$section][$key]['priority'])
            ? (int) $config[$section][$key]['priority']
            : (count($config[$section]) + 1) * 10;

        $config[$section][$key] = array(
            'name'          => $key,
            'label'         => $label,
            'placeholder'   => $placeholder,
            'type'          => $type,
            'required'      => $required,
            'enabled'       => $enabled,
            'class'         => $class,
            'priority'      => $priority,
            'default'       => $default_val,
            'options'       => $options,
            'custom'        => $is_edit ? $existing_custom : true,
            'show_in_email' => $show_email,
            'show_in_order' => $show_order,
        );

        update_option(Checkout_Fields_Module::OPTION_KEY, $config);

        wp_send_json_success(array(
            'message' => $is_edit
                ? __('Field updated successfully.', 'optimus-bytes-woo-kit')
                : __('Custom field created successfully.', 'optimus-bytes-woo-kit'),
            'field'   => $config[$section][$key],
            'section' => $section,
            'key'     => $key,
        ));
    }

    /**
     * AJAX: Delete a Custom Field
     */
    public function handle_delete_field_ajax() {
        check_ajax_referer('obwk_checkout_fields_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized permission.', 'optimus-bytes-woo-kit')));
        }

        $section = isset($_POST['section']) ? sanitize_key($_POST['section']) : '';
        $key     = isset($_POST['name']) ? sanitize_key($_POST['name']) : '';

        $config = $this->module->get_fields_config();

        if (!isset($config[$section][$key])) {
            wp_send_json_error(array('message' => __('Field not found.', 'optimus-bytes-woo-kit')));
        }

        // Prevent deleting core WooCommerce fields
        if (empty($config[$section][$key]['custom'])) {
            wp_send_json_error(array('message' => __('Core WooCommerce fields cannot be deleted. You can hide them by disabling the switch instead.', 'optimus-bytes-woo-kit')));
        }

        unset($config[$section][$key]);
        update_option(Checkout_Fields_Module::OPTION_KEY, $config);

        wp_send_json_success(array(
            'message' => __('Custom field deleted successfully.', 'optimus-bytes-woo-kit'),
            'section' => $section,
            'key'     => $key,
        ));
    }

    /**
     * AJAX: Reset Section or All Fields to Defaults
     */
    public function handle_reset_fields_ajax() {
        check_ajax_referer('obwk_checkout_fields_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized permission.', 'optimus-bytes-woo-kit')));
        }

        $section = isset($_POST['section']) ? sanitize_key($_POST['section']) : 'all';
        $defaults = $this->module->get_default_fields();
        $current  = $this->module->get_fields_config();

        if ('all' === $section) {
            update_option(Checkout_Fields_Module::OPTION_KEY, $defaults);
        } elseif (isset($defaults[$section])) {
            $current[$section] = $defaults[$section];
            update_option(Checkout_Fields_Module::OPTION_KEY, $current);
        } else {
            wp_send_json_error(array('message' => __('Invalid section.', 'optimus-bytes-woo-kit')));
        }

        wp_send_json_success(array(
            'message' => __('Checkout fields restored to default successfully.', 'optimus-bytes-woo-kit'),
            'section' => $section,
        ));
    }

    /**
     * Render the Checkout Fields Admin Management Page
     */
    public function render_admin_page() {
        $config = $this->module->get_fields_config();
        $is_module_enabled = $this->module->is_enabled();

        $sections = array(
            'billing'  => array(
                'title' => __('Billing Fields', 'optimus-bytes-woo-kit'),
                'icon'  => '💳',
                'desc'  => __('Manage fields displayed in the Billing Address section of checkout.', 'optimus-bytes-woo-kit'),
            ),
            'shipping' => array(
                'title' => __('Shipping Fields', 'optimus-bytes-woo-kit'),
                'icon'  => '🚚',
                'desc'  => __('Manage fields displayed in the Shipping Address section of checkout.', 'optimus-bytes-woo-kit'),
            ),
            'order'    => array(
                'title' => __('Additional / Order Fields', 'optimus-bytes-woo-kit'),
                'icon'  => '📦',
                'desc'  => __('Manage fields in the Additional Information / Order Notes section.', 'optimus-bytes-woo-kit'),
            ),
        );
        ?>
        <div class="wrap obwk-admin-wrap obwk-checkout-fields-wrap">
            <!-- Top Header Card -->
            <div class="obwk-header-card">
                <div class="obwk-header-content">
                    <div class="obwk-logo-badge">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    </div>
                    <div>
                        <h1 class="obwk-title"><?php esc_html_e('Checkout Field Customizer', 'optimus-bytes-woo-kit'); ?></h1>
                        <p class="obwk-subtitle"><?php esc_html_e('Reorder, show/hide, make required, and create custom fields for WooCommerce checkout.', 'optimus-bytes-woo-kit'); ?></p>
                    </div>
                </div>
                <div class="obwk-header-actions">
                    <span class="obwk-badge <?php echo $is_module_enabled ? 'obwk-badge-active' : 'obwk-badge-inactive'; ?>">
                        <span class="obwk-dot"></span> <?php echo $is_module_enabled ? esc_html__('Module Active', 'optimus-bytes-woo-kit') : esc_html__('Module Disabled', 'optimus-bytes-woo-kit'); ?>
                    </span>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=optimus-woo-kit')); ?>" class="obwk-btn obwk-btn-outline">
                        ← <?php esc_html_e('All Modules', 'optimus-bytes-woo-kit'); ?>
                    </a>
                </div>
            </div>

            <!-- Main Layout: Navigation Tabs & Content -->
            <div class="obwk-tabs-container">
                <div class="obwk-tab-nav">
                    <button type="button" class="obwk-tab-btn is-active" data-tab="billing">
                        <span class="obwk-tab-icon">💳</span>
                        <span class="obwk-tab-label"><?php esc_html_e('Billing Fields', 'optimus-bytes-woo-kit'); ?></span>
                        <span class="obwk-tab-count" id="count-billing"><?php echo count($config['billing']); ?></span>
                    </button>
                    <button type="button" class="obwk-tab-btn" data-tab="shipping">
                        <span class="obwk-tab-icon">🚚</span>
                        <span class="obwk-tab-label"><?php esc_html_e('Shipping Fields', 'optimus-bytes-woo-kit'); ?></span>
                        <span class="obwk-tab-count" id="count-shipping"><?php echo count($config['shipping']); ?></span>
                    </button>
                    <button type="button" class="obwk-tab-btn" data-tab="order">
                        <span class="obwk-tab-icon">📦</span>
                        <span class="obwk-tab-label"><?php esc_html_e('Additional / Order Fields', 'optimus-bytes-woo-kit'); ?></span>
                        <span class="obwk-tab-count" id="count-order"><?php echo count($config['order']); ?></span>
                    </button>
                    <button type="button" class="obwk-tab-btn" data-tab="presets">
                        <span class="obwk-tab-icon">💡</span>
                        <span class="obwk-tab-label"><?php esc_html_e('Quick Field Presets', 'optimus-bytes-woo-kit'); ?></span>
                    </button>
                </div>

                <!-- Tab Panes -->
                <div class="obwk-tab-content">
                    <?php foreach ($sections as $section_key => $section_info) : ?>
                        <div class="obwk-tab-pane <?php echo 'billing' === $section_key ? 'is-active' : ''; ?>" id="tab-pane-<?php echo esc_attr($section_key); ?>">
                            <div class="obwk-pane-header">
                                <div>
                                    <h2 class="obwk-pane-title"><?php echo esc_html($section_info['title']); ?></h2>
                                    <p class="obwk-pane-desc"><?php echo esc_html($section_info['desc']); ?></p>
                                </div>
                                <div class="obwk-pane-actions">
                                    <button type="button" class="obwk-btn obwk-btn-outline obwk-reset-section-btn" data-section="<?php echo esc_attr($section_key); ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path><path d="M3 3v5h5"></path></svg>
                                        <?php esc_html_e('Reset Defaults', 'optimus-bytes-woo-kit'); ?>
                                    </button>
                                    <button type="button" class="obwk-btn obwk-btn-secondary obwk-add-field-btn" data-section="<?php echo esc_attr($section_key); ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                        <?php esc_html_e('Add New Field', 'optimus-bytes-woo-kit'); ?>
                                    </button>
                                    <button type="button" class="obwk-btn obwk-btn-primary obwk-save-section-btn" data-section="<?php echo esc_attr($section_key); ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                                        <?php esc_html_e('Save Changes', 'optimus-bytes-woo-kit'); ?>
                                    </button>
                                </div>
                            </div>

                            <!-- Table of Fields -->
                            <div class="obwk-table-wrap">
                                <table class="obwk-fields-table" data-section="<?php echo esc_attr($section_key); ?>">
                                    <thead>
                                        <tr>
                                            <th class="col-drag" width="40" title="<?php esc_attr_e('Drag to Reorder', 'optimus-bytes-woo-kit'); ?>">↕</th>
                                            <th class="col-status" width="80"><?php esc_html_e('Visible', 'optimus-bytes-woo-kit'); ?></th>
                                            <th class="col-required" width="90"><?php esc_html_e('Required', 'optimus-bytes-woo-kit'); ?></th>
                                            <th class="col-label"><?php esc_html_e('Field Label & Key', 'optimus-bytes-woo-kit'); ?></th>
                                            <th class="col-type" width="120"><?php esc_html_e('Type', 'optimus-bytes-woo-kit'); ?></th>
                                            <th class="col-width" width="140"><?php esc_html_e('Width Layout', 'optimus-bytes-woo-kit'); ?></th>
                                            <th class="col-actions" width="110"><?php esc_html_e('Actions', 'optimus-bytes-woo-kit'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody class="obwk-sortable-body">
                                        <?php if (!empty($config[$section_key])) : ?>
                                            <?php foreach ($config[$section_key] as $f_key => $field) : ?>
                                                <?php $this->render_field_row($section_key, $f_key, $field); ?>
                                            <?php endforeach; ?>
                                        <?php else : ?>
                                            <tr class="obwk-no-fields-row">
                                                <td colspan="7"><?php esc_html_e('No fields in this section. Click "Add New Field" or "Reset Defaults".', 'optimus-bytes-woo-kit'); ?></td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Presets Pane -->
                    <div class="obwk-tab-pane" id="tab-pane-presets">
                        <div class="obwk-pane-header">
                            <div>
                                <h2 class="obwk-pane-title"><?php esc_html_e('Popular 1-Click Field Presets', 'optimus-bytes-woo-kit'); ?></h2>
                                <p class="obwk-pane-desc"><?php esc_html_e('Instantly add commonly requested Indian & International e-commerce checkout fields.', 'optimus-bytes-woo-kit'); ?></p>
                            </div>
                        </div>

                        <div class="obwk-presets-grid">
                            <!-- Preset 1: GST / Tax ID -->
                            <div class="obwk-preset-card">
                                <div class="obwk-preset-icon">📑</div>
                                <div class="obwk-preset-body">
                                    <h4 class="obwk-preset-title"><?php esc_html_e('GSTIN / Tax ID Number', 'optimus-bytes-woo-kit'); ?></h4>
                                    <p class="obwk-preset-desc"><?php esc_html_e('Capture business GST/VAT numbers in Billing for B2B invoicing.', 'optimus-bytes-woo-kit'); ?></p>
                                    <div class="obwk-preset-meta">
                                        <span class="obwk-tag">Billing</span>
                                        <span class="obwk-tag">Text</span>
                                        <span class="obwk-tag">Optional</span>
                                    </div>
                                </div>
                                <button type="button" class="obwk-btn obwk-btn-primary obwk-apply-preset-btn"
                                        data-section="billing"
                                        data-name="billing_gst_number"
                                        data-label="<?php esc_attr_e('GSTIN / VAT Number', 'optimus-bytes-woo-kit'); ?>"
                                        data-placeholder="<?php esc_attr_e('e.g. 29AAAAA0000A1Z5', 'optimus-bytes-woo-kit'); ?>"
                                        data-type="text"
                                        data-class="form-row-wide"
                                        data-required="0">
                                    + <?php esc_html_e('Add to Billing', 'optimus-bytes-woo-kit'); ?>
                                </button>
                            </div>

                            <!-- Preset 2: Landmark -->
                            <div class="obwk-preset-card">
                                <div class="obwk-preset-icon">📍</div>
                                <div class="obwk-preset-body">
                                    <h4 class="obwk-preset-title"><?php esc_html_e('Landmark / Location Directions', 'optimus-bytes-woo-kit'); ?></h4>
                                    <p class="obwk-preset-desc"><?php esc_html_e('Help courier agents locate the delivery address quickly.', 'optimus-bytes-woo-kit'); ?></p>
                                    <div class="obwk-preset-meta">
                                        <span class="obwk-tag">Shipping</span>
                                        <span class="obwk-tag">Text</span>
                                        <span class="obwk-tag">Optional</span>
                                    </div>
                                </div>
                                <button type="button" class="obwk-btn obwk-btn-primary obwk-apply-preset-btn"
                                        data-section="shipping"
                                        data-name="shipping_landmark"
                                        data-label="<?php esc_attr_e('Nearby Landmark', 'optimus-bytes-woo-kit'); ?>"
                                        data-placeholder="<?php esc_attr_e('e.g. Near City Temple / Opposite Metro Pillar 45', 'optimus-bytes-woo-kit'); ?>"
                                        data-type="text"
                                        data-class="form-row-wide"
                                        data-required="0">
                                    + <?php esc_html_e('Add to Shipping', 'optimus-bytes-woo-kit'); ?>
                                </button>
                            </div>

                            <!-- Preset 3: Alternate Phone Number -->
                            <div class="obwk-preset-card">
                                <div class="obwk-preset-icon">📞</div>
                                <div class="obwk-preset-body">
                                    <h4 class="obwk-preset-title"><?php esc_html_e('Alternate Phone / WhatsApp Number', 'optimus-bytes-woo-kit'); ?></h4>
                                    <p class="obwk-preset-desc"><?php esc_html_e('Backup contact number for delivery updates and verification.', 'optimus-bytes-woo-kit'); ?></p>
                                    <div class="obwk-preset-meta">
                                        <span class="obwk-tag">Billing</span>
                                        <span class="obwk-tag">Tel</span>
                                        <span class="obwk-tag">Optional</span>
                                    </div>
                                </div>
                                <button type="button" class="obwk-btn obwk-btn-primary obwk-apply-preset-btn"
                                        data-section="billing"
                                        data-name="billing_alt_phone"
                                        data-label="<?php esc_attr_e('Alternate Phone / WhatsApp', 'optimus-bytes-woo-kit'); ?>"
                                        data-placeholder="<?php esc_attr_e('e.g. +91 98765 43210', 'optimus-bytes-woo-kit'); ?>"
                                        data-type="tel"
                                        data-class="form-row-wide"
                                        data-required="0">
                                    + <?php esc_html_e('Add to Billing', 'optimus-bytes-woo-kit'); ?>
                                </button>
                            </div>

                            <!-- Preset 4: Preferred Delivery Date -->
                            <div class="obwk-preset-card">
                                <div class="obwk-preset-icon">📅</div>
                                <div class="obwk-preset-body">
                                    <h4 class="obwk-preset-title"><?php esc_html_e('Preferred Delivery Date', 'optimus-bytes-woo-kit'); ?></h4>
                                    <p class="obwk-preset-desc"><?php esc_html_e('Let customers select their preferred delivery date via calendar.', 'optimus-bytes-woo-kit'); ?></p>
                                    <div class="obwk-preset-meta">
                                        <span class="obwk-tag">Order</span>
                                        <span class="obwk-tag">Date</span>
                                        <span class="obwk-tag">Optional</span>
                                    </div>
                                </div>
                                <button type="button" class="obwk-btn obwk-btn-primary obwk-apply-preset-btn"
                                        data-section="order"
                                        data-name="order_delivery_date"
                                        data-label="<?php esc_attr_e('Preferred Delivery Date', 'optimus-bytes-woo-kit'); ?>"
                                        data-placeholder=""
                                        data-type="date"
                                        data-class="form-row-wide"
                                        data-required="0">
                                    + <?php esc_html_e('Add to Order', 'optimus-bytes-woo-kit'); ?>
                                </button>
                            </div>

                            <!-- Preset 5: Gift Message -->
                            <div class="obwk-preset-card">
                                <div class="obwk-preset-icon">🎁</div>
                                <div class="obwk-preset-body">
                                    <h4 class="obwk-preset-title"><?php esc_html_e('Gift Message / Note', 'optimus-bytes-woo-kit'); ?></h4>
                                    <p class="obwk-preset-desc"><?php esc_html_e('Allow buyers to include a custom greeting message for gifts.', 'optimus-bytes-woo-kit'); ?></p>
                                    <div class="obwk-preset-meta">
                                        <span class="obwk-tag">Order</span>
                                        <span class="obwk-tag">Textarea</span>
                                        <span class="obwk-tag">Optional</span>
                                    </div>
                                </div>
                                <button type="button" class="obwk-btn obwk-btn-primary obwk-apply-preset-btn"
                                        data-section="order"
                                        data-name="order_gift_message"
                                        data-label="<?php esc_attr_e('Gift Message', 'optimus-bytes-woo-kit'); ?>"
                                        data-placeholder="<?php esc_attr_e('Add your personalized greeting card message here...', 'optimus-bytes-woo-kit'); ?>"
                                        data-type="textarea"
                                        data-class="form-row-wide"
                                        data-required="0">
                                    + <?php esc_html_e('Add to Order', 'optimus-bytes-woo-kit'); ?>
                                </button>
                            </div>

                            <!-- Preset 6: How did you hear about us -->
                            <div class="obwk-preset-card">
                                <div class="obwk-preset-icon">📣</div>
                                <div class="obwk-preset-body">
                                    <h4 class="obwk-preset-title"><?php esc_html_e('How did you hear about us?', 'optimus-bytes-woo-kit'); ?></h4>
                                    <p class="obwk-preset-desc"><?php esc_html_e('Survey dropdown to track marketing channels (Instagram, Google, Friend).', 'optimus-bytes-woo-kit'); ?></p>
                                    <div class="obwk-preset-meta">
                                        <span class="obwk-tag">Order</span>
                                        <span class="obwk-tag">Select</span>
                                        <span class="obwk-tag">Optional</span>
                                    </div>
                                </div>
                                <button type="button" class="obwk-btn obwk-btn-primary obwk-apply-preset-btn"
                                        data-section="order"
                                        data-name="order_marketing_source"
                                        data-label="<?php esc_attr_e('How did you hear about us?', 'optimus-bytes-woo-kit'); ?>"
                                        data-placeholder="<?php esc_attr_e('Select an option', 'optimus-bytes-woo-kit'); ?>"
                                        data-type="select"
                                        data-class="form-row-wide"
                                        data-required="0"
                                        data-options='{"instagram":"Instagram","google":"Google Search","facebook":"Facebook","friends":"Friend / Family Referral","other":"Other"}'>
                                    + <?php esc_html_e('Add to Order', 'optimus-bytes-woo-kit'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Field Add / Edit Modal -->
            <div class="obwk-modal-overlay" id="obwk-field-modal" style="display:none;">
                <div class="obwk-modal-dialog">
                    <div class="obwk-modal-header">
                        <h3 class="obwk-modal-title" id="modal-heading"><?php esc_html_e('Add New Checkout Field', 'optimus-bytes-woo-kit'); ?></h3>
                        <button type="button" class="obwk-modal-close" id="modal-close-btn">&times;</button>
                    </div>
                    <form id="obwk-field-form">
                        <div class="obwk-modal-body">
                            <input type="hidden" id="modal-field-section" name="section" value="billing" />
                            <input type="hidden" id="modal-is-edit" name="is_edit" value="0" />
                            <input type="hidden" id="modal-is-custom" name="custom" value="1" />

                            <div class="obwk-form-row">
                                <div class="obwk-form-col">
                                    <label class="obwk-form-label" for="modal-field-type"><?php esc_html_e('Field Type', 'optimus-bytes-woo-kit'); ?></label>
                                    <select class="obwk-form-control" id="modal-field-type" name="type">
                                        <option value="text"><?php esc_html_e('Text', 'optimus-bytes-woo-kit'); ?></option>
                                        <option value="number"><?php esc_html_e('Number', 'optimus-bytes-woo-kit'); ?></option>
                                        <option value="tel"><?php esc_html_e('Phone / Tel', 'optimus-bytes-woo-kit'); ?></option>
                                        <option value="email"><?php esc_html_e('Email Address', 'optimus-bytes-woo-kit'); ?></option>
                                        <option value="textarea"><?php esc_html_e('Textarea (Multi-line)', 'optimus-bytes-woo-kit'); ?></option>
                                        <option value="select"><?php esc_html_e('Select (Dropdown)', 'optimus-bytes-woo-kit'); ?></option>
                                        <option value="radio"><?php esc_html_e('Radio Buttons', 'optimus-bytes-woo-kit'); ?></option>
                                        <option value="checkbox"><?php esc_html_e('Checkbox', 'optimus-bytes-woo-kit'); ?></option>
                                        <option value="date"><?php esc_html_e('Date Picker', 'optimus-bytes-woo-kit'); ?></option>
                                        <option value="time"><?php esc_html_e('Time Picker', 'optimus-bytes-woo-kit'); ?></option>
                                        <option value="heading"><?php esc_html_e('Section Heading / Separator', 'optimus-bytes-woo-kit'); ?></option>
                                    </select>
                                </div>
                                <div class="obwk-form-col">
                                    <label class="obwk-form-label" for="modal-field-name">
                                        <?php esc_html_e('Field Key / Name', 'optimus-bytes-woo-kit'); ?> <span class="req">*</span>
                                    </label>
                                    <input type="text" class="obwk-form-control" id="modal-field-name" name="name" placeholder="e.g. gst_number" required />
                                    <span class="obwk-field-hint"><?php esc_html_e('Must be lowercase with underscores (e.g. landmark).', 'optimus-bytes-woo-kit'); ?></span>
                                </div>
                            </div>

                            <div class="obwk-form-row">
                                <div class="obwk-form-col">
                                    <label class="obwk-form-label" for="modal-field-label">
                                        <?php esc_html_e('Field Label', 'optimus-bytes-woo-kit'); ?> <span class="req">*</span>
                                    </label>
                                    <input type="text" class="obwk-form-control" id="modal-field-label" name="label" placeholder="<?php esc_attr_e('Label shown to customers', 'optimus-bytes-woo-kit'); ?>" required />
                                </div>
                                <div class="obwk-form-col">
                                    <label class="obwk-form-label" for="modal-field-placeholder"><?php esc_html_e('Placeholder Text', 'optimus-bytes-woo-kit'); ?></label>
                                    <input type="text" class="obwk-form-control" id="modal-field-placeholder" name="placeholder" placeholder="<?php esc_attr_e('Placeholder hint in input box', 'optimus-bytes-woo-kit'); ?>" />
                                </div>
                            </div>

                            <div class="obwk-form-row">
                                <div class="obwk-form-col">
                                    <label class="obwk-form-label" for="modal-field-class"><?php esc_html_e('Width / Layout', 'optimus-bytes-woo-kit'); ?></label>
                                    <select class="obwk-form-control" id="modal-field-class" name="class">
                                        <option value="form-row-wide"><?php esc_html_e('100% Full Width (form-row-wide)', 'optimus-bytes-woo-kit'); ?></option>
                                        <option value="form-row-first"><?php esc_html_e('50% Half Width - Left (form-row-first)', 'optimus-bytes-woo-kit'); ?></option>
                                        <option value="form-row-last"><?php esc_html_e('50% Half Width - Right (form-row-last)', 'optimus-bytes-woo-kit'); ?></option>
                                    </select>
                                </div>
                                <div class="obwk-form-col">
                                    <label class="obwk-form-label" for="modal-field-default"><?php esc_html_e('Default Value', 'optimus-bytes-woo-kit'); ?></label>
                                    <input type="text" class="obwk-form-control" id="modal-field-default" name="default" placeholder="<?php esc_attr_e('Optional initial value', 'optimus-bytes-woo-kit'); ?>" />
                                </div>
                            </div>

                            <!-- Options repeater for Select / Radio -->
                            <div class="obwk-options-group" id="modal-options-wrapper" style="display:none;">
                                <label class="obwk-form-label"><?php esc_html_e('Options (Key : Label pairs)', 'optimus-bytes-woo-kit'); ?></label>
                                <div id="modal-options-list"></div>
                                <button type="button" class="obwk-btn obwk-btn-outline obwk-btn-sm" id="modal-add-option-btn">
                                    + <?php esc_html_e('Add Option', 'optimus-bytes-woo-kit'); ?>
                                </button>
                            </div>

                            <!-- Checkboxes for Settings -->
                            <div class="obwk-form-row obwk-checkbox-grid">
                                <label class="obwk-checkbox-label">
                                    <input type="checkbox" id="modal-field-required" name="required" value="1" />
                                    <span><?php esc_html_e('Mandatory / Required Field', 'optimus-bytes-woo-kit'); ?></span>
                                </label>
                                <label class="obwk-checkbox-label">
                                    <input type="checkbox" id="modal-field-enabled" name="enabled" value="1" checked />
                                    <span><?php esc_html_e('Enabled (Show on Checkout)', 'optimus-bytes-woo-kit'); ?></span>
                                </label>
                                <label class="obwk-checkbox-label">
                                    <input type="checkbox" id="modal-field-show-order" name="show_in_order" value="1" checked />
                                    <span><?php esc_html_e('Show in Admin Order & Thank You Page', 'optimus-bytes-woo-kit'); ?></span>
                                </label>
                                <label class="obwk-checkbox-label">
                                    <input type="checkbox" id="modal-field-show-email" name="show_in_email" value="1" checked />
                                    <span><?php esc_html_e('Show in Transactional Emails', 'optimus-bytes-woo-kit'); ?></span>
                                </label>
                            </div>
                        </div>
                        <div class="obwk-modal-footer">
                            <button type="button" class="obwk-btn obwk-btn-outline" id="modal-cancel-btn"><?php esc_html_e('Cancel', 'optimus-bytes-woo-kit'); ?></button>
                            <button type="submit" class="obwk-btn obwk-btn-primary" id="modal-submit-btn"><?php esc_html_e('Save Field', 'optimus-bytes-woo-kit'); ?></button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Toast Notification Element -->
            <div id="obwk-toast" class="obwk-toast" style="display:none;"></div>
        </div>
        <?php
    }

    /**
     * Render a single row in the fields table
     *
     * @param string $section
     * @param string $key
     * @param array $field
     */
    private function render_field_row($section, $key, $field) {
        $is_enabled  = !isset($field['enabled']) || true === $field['enabled'];
        $is_required = !empty($field['required']);
        $is_custom   = !empty($field['custom']);
        $label       = isset($field['label']) ? $field['label'] : $key;
        $type        = !empty($field['type']) ? $field['type'] : 'text';
        $class       = !empty($field['class']) ? (is_array($field['class']) ? reset($field['class']) : $field['class']) : 'form-row-wide';

        $field_json = wp_json_encode($field);
        ?>
        <tr class="obwk-field-row <?php echo $is_enabled ? '' : 'is-disabled-row'; ?>" data-field-key="<?php echo esc_attr($key); ?>" data-field-json="<?php echo esc_attr($field_json); ?>">
            <td class="col-drag">
                <span class="obwk-drag-handle" title="<?php esc_attr_e('Drag to Reorder', 'optimus-bytes-woo-kit'); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="5" r="1"></circle><circle cx="9" cy="12" r="1"></circle><circle cx="9" cy="19" r="1"></circle><circle cx="15" cy="5" r="1"></circle><circle cx="15" cy="12" r="1"></circle><circle cx="15" cy="19" r="1"></circle></svg>
                </span>
            </td>
            <td class="col-status">
                <label class="obwk-switch obwk-switch-sm" title="<?php esc_attr_e('Show or Hide field on checkout', 'optimus-bytes-woo-kit'); ?>">
                    <input type="checkbox" class="obwk-field-toggle-enabled" <?php checked($is_enabled, true); ?> />
                    <span class="obwk-slider"></span>
                </label>
            </td>
            <td class="col-required">
                <label class="obwk-switch obwk-switch-sm" title="<?php esc_attr_e('Make required or optional', 'optimus-bytes-woo-kit'); ?>">
                    <input type="checkbox" class="obwk-field-toggle-required" <?php checked($is_required, true); ?> />
                    <span class="obwk-slider"></span>
                </label>
            </td>
            <td class="col-label">
                <div class="obwk-label-info">
                    <span class="obwk-field-label-text"><?php echo esc_html($label); ?></span>
                    <span class="obwk-field-key-text"><code><?php echo esc_html($key); ?></code></span>
                    <?php if ($is_custom) : ?>
                        <span class="obwk-badge-custom"><?php esc_html_e('Custom', 'optimus-bytes-woo-kit'); ?></span>
                    <?php else : ?>
                        <span class="obwk-badge-core"><?php esc_html_e('Core', 'optimus-bytes-woo-kit'); ?></span>
                    <?php endif; ?>
                </div>
            </td>
            <td class="col-type">
                <span class="obwk-type-badge"><?php echo esc_html(ucfirst($type)); ?></span>
            </td>
            <td class="col-width">
                <select class="obwk-row-width-select">
                    <option value="form-row-wide" <?php selected($class, 'form-row-wide'); ?>><?php esc_html_e('100% Full', 'optimus-bytes-woo-kit'); ?></option>
                    <option value="form-row-first" <?php selected($class, 'form-row-first'); ?>><?php esc_html_e('50% Left', 'optimus-bytes-woo-kit'); ?></option>
                    <option value="form-row-last" <?php selected($class, 'form-row-last'); ?>><?php esc_html_e('50% Right', 'optimus-bytes-woo-kit'); ?></option>
                </select>
            </td>
            <td class="col-actions">
                <div class="obwk-row-actions">
                    <button type="button" class="obwk-action-btn obwk-edit-field-btn" title="<?php esc_attr_e('Edit Field', 'optimus-bytes-woo-kit'); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                    </button>
                    <?php if ($is_custom) : ?>
                        <button type="button" class="obwk-action-btn obwk-delete-field-btn" title="<?php esc_attr_e('Delete Custom Field', 'optimus-bytes-woo-kit'); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                        </button>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php
    }
}
