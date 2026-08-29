<?php
/**
 * Abstract Base Module Class
 *
 * @package OptimusBytes\WooKit\Modules
 */

namespace OptimusBytes\WooKit\Modules;

defined('ABSPATH') || exit;

abstract class Abstract_Module {

    /**
     * Module ID
     *
     * @var string
     */
    protected $id = '';

    /**
     * Module Title
     *
     * @var string
     */
    protected $title = '';

    /**
     * Module Description
     *
     * @var string
     */
    protected $description = '';

    /**
     * Module Icon / Emoji
     *
     * @var string
     */
    protected $icon = '⚡';

    /**
     * Module Category
     *
     * @var string
     */
    protected $category = 'General';

    /**
     * Get module unique identifier
     *
     * @return string
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Get module human readable title
     *
     * @return string
     */
    public function get_title() {
        return $this->title;
    }

    /**
     * Get module description
     *
     * @return string
     */
    public function get_description() {
        return $this->description;
    }

    /**
     * Get module icon
     *
     * @return string
     */
    public function get_icon() {
        return $this->icon;
    }

    /**
     * Get module category
     *
     * @return string
     */
    public function get_category() {
        return $this->category;
    }

    /**
     * Get direct configuration URL in Customizer
     *
     * @return string
     */
    public function get_configure_url() {
        return admin_url('customize.php?autofocus[section]=obwk_' . $this->id . '_section');
    }

    /**
     * Check if module is enabled
     *
     * @return bool
     */
    public function is_enabled() {
        $options = get_option(OBWK_SETTINGS_OPTION, array());
        $key     = $this->id . '_enable';

        return isset($options[$key]) ? (bool) $options[$key] : true;
    }

    /**
     * Get a module setting value from optimus_bytes_woo_kit_settings
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get_option($key, $default = '') {
        $options  = get_option(OBWK_SETTINGS_OPTION, array());
        $full_key = $this->id . '_' . $key;

        return isset($options[$full_key]) ? $options[$full_key] : $default;
    }

    /**
     * Update a module setting value in optimus_bytes_woo_kit_settings
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function update_option($key, $value) {
        $options            = get_option(OBWK_SETTINGS_OPTION, array());
        $full_key           = $this->id . '_' . $key;
        $options[$full_key] = $value;
        return update_option(OBWK_SETTINGS_OPTION, $options);
    }

    /**
     * Initialize module hooks and behavior
     */
    abstract public function init();
}
