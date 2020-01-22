<?php
/**
 * Plugin Name: Shipox
 * Plugin URI: https://www.shipox.com
 * Description: Official Shipox plugin for WooCommerce
 * Version: 4.0.0
 * Author: Shipox
 * Author URI: https://www.shipox.com
 * Requires at least: 4.4
 * Tested up to: 5.2.2
 * WC requires at least: 3.0.0
 * WC tested up to: 3.7.0
 *
 * Text Domain: shipox
 * Domain Path: /i18n/languages/
 *
 * @package Shipox
 * @category Core
 * @author Umid Akhmedjanov
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define WC_PLUGIN_FILE.
if (!defined('SHIPOX_PLUGIN_FILE')) {
    define('SHIPOX_PLUGIN_FILE', __FILE__);
}

// Define WC_PLUGIN_FILE.
if (!defined('SHIPOX_SLUG')) {
    define('SHIPOX_SLUG', 'wing');
}

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

    // Include the main WooCommerce class.
    if (!class_exists('Shipox')) {
        include_once dirname(__FILE__) . '/classes/class-shipox.php';
    }

    /**
     * Main instance of Shipox.
     *
     * Returns the main instance of WING to prevent the need to use globals.
     *
     * @since  2.0
     * @return Shipox
     */
    function shipox()
    {
        return Shipox::instance();
    }

    // Global for backwards compatibility.
    $GLOBALS['shipox'] = shipox();

}