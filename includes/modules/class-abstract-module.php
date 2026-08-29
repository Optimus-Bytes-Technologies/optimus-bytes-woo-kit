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
     * Get direct configuration URL
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
        return (bool) get_theme_mod('obwk_' . $this->id . '_enable', true);
    }

    /**
     * Initialize module hooks and behavior
     */
    abstract public function init();

    /**
     * Get a module setting value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get_option($key, $default = '') {
        return get_theme_mod('obwk_' . $this->id . '_' . $key, $default);
    }
}
