<?php
/**
 * Created by Shipox.
 * User: Shipox
 * Date: 11/8/2017
 * Time: 2:41 PM
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

final class Shipox
{
    public $version = '4.0.0';
    protected static $_instance = null;
    public $wing = null;
    public $api = null;
    public $log = null;
    public $sentry = null;

    /**
     * @return null|Shipox
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Shipox constructor.
     */
    public function __construct()
    {
        $this->define_constants();
        $this->includes();
        $this->init_classes();
        $this->init_hooks();
    }

    /**
     *  Initialize Assets
     */
    public function load_admin_scripts()
    {
        wp_register_style('shipox_admin_css', WP_PLUGIN_URL . '/shipox/assets/css/admin-style.css', false, $this->version);
        wp_enqueue_style('shipox_admin_css');

        wp_register_script('shipox_admin_ajax', WP_PLUGIN_URL . '/shipox/assets/js/shipox_ajax.js', array('jquery'), '1.0.0', true);
        wp_localize_script('shipox_admin_ajax', 'shipoxAjax', array('ajax_url' => admin_url('admin-ajax.php'), 'ajax_nonce' => wp_create_nonce('shipox-wp-woocommerse-plugin')));
        wp_enqueue_script('shipox_admin_ajax');
    }

    /**
     * Define Wing Constants.
     */
    private function define_constants()
    {
        $upload_dir = wp_upload_dir(null, false);

        $this->define('SHIPOX_DEV_SITE_URL', 'https://my-staging.shipox.com');
        $this->define('SHIPOX_LIVE_SITE_URL', 'https://my.shipox.com');

        $this->define('SHIPOX_DEV_API_URL', 'https://myapi.wing.ae');
        $this->define('SHIPOX_LIVE_API_URL', 'https://liveapi.wing.ae');

        $this->define('SHIPOX_ABSPATH', dirname(SHIPOX_PLUGIN_FILE) . '/');
        $this->define('SHIPOX_PLUGIN_BASENAME', plugin_basename(SHIPOX_PLUGIN_FILE));
        $this->define('SHIPOX_VERSION', $this->version);
        $this->define('SHIPOX_SEPARATOR', '|');
        $this->define('SHIPOX_LOGS', $upload_dir['basedir'] . '/shipox-logs/');
    }

    /**
     * Define constant if not already set.
     *
     * @param string $name Constant name.
     * @param string|bool $value Constant value.
     */
    private function define($name, $value)
    {
        if (!defined($name)) {
            define($name, $value);
        }
    }

    /**
     *  Includes
     */
    private function includes()
    {
        include_once(SHIPOX_ABSPATH . 'includes/inc-logs.php');
        include_once(SHIPOX_ABSPATH . 'enum/enum-log-statuses.php');
        include_once(SHIPOX_ABSPATH . 'classes/class-shipox-options.php');
        include_once(SHIPOX_ABSPATH . 'classes/class-shipox-menu-type.php');
        include_once(SHIPOX_ABSPATH . 'classes/class-shipox-courier-type.php');
        include_once(SHIPOX_ABSPATH . 'classes/class-shipox-payment-type.php');
        include_once(SHIPOX_ABSPATH . 'classes/class-shipox-package-type.php');
        include_once(SHIPOX_ABSPATH . 'classes/class-shipox-shipping-options.php');
        include_once(SHIPOX_ABSPATH . 'classes/class-shipox-vehicle-type.php');
        include_once(SHIPOX_ABSPATH . 'classes/class-shipox-status-mapping.php');
        include_once(SHIPOX_ABSPATH . 'includes/inc-api-helper.php');
        include_once(SHIPOX_ABSPATH . 'includes/inc-order-helper.php');
        include_once(SHIPOX_ABSPATH . 'includes/inc-settings-helper.php');
        include_once(SHIPOX_ABSPATH . 'includes/inc-backend-actions.php');
        include_once(SHIPOX_ABSPATH . 'includes/inc-frontend-actions.php');
        include_once(SHIPOX_ABSPATH . 'classes/class-shipox-api-client.php');
        include_once(SHIPOX_ABSPATH . 'classes/class-shipox-cron-job.php');
        include_once(SHIPOX_ABSPATH . 'includes/inc-shipox-install.php');
    }

    /**
     *  Init Classes
     */
    private function init_classes()
    {
        $this->wing['options'] = $this->init_options();
        $this->log = new Shipox_Log();
        $this->wing['menu-type'] = new Menu_Type();
        $this->wing['courier-type'] = new Courier_Type();
        $this->wing['payment-type'] = new Payment_Type();
        $this->wing['package-type'] = new Package_Type();
        $this->wing['shipping-options'] = new Shipping_Options();
        $this->wing['statuses'] = new Status_Mapping();
        $this->wing['vehicle-type'] = new Vehicle_Type();
        $this->api = new Shipox_Api_Client();
        $this->wing['api-helper'] = new Shipox_API_Helper();
        $this->wing['order-helper'] = new \includes\Shipox_Order_Helper();
        $this->wing['settings-helper'] = new \includes\Shipox_Settings_Helper();

        new Shipox_Cron_Job();
    }

    /**
     * Hook into actions and filters.
     * @since 2.0
     */
    private function init_hooks()
    {
        register_activation_hook(SHIPOX_PLUGIN_FILE, array('Shipox_Install', 'install'));

        $this->init();

        add_action('admin_enqueue_scripts', array($this, 'load_admin_scripts'));
    }

    /**
     *  Init Function
     */
    public function init()
    {
        add_action('woocommerce_shipping_init', array($this, 'init_shipping_method'));
        add_action('woocommerce_shipping_methods', array($this, 'add_shipping_method'));

        $merchantConfig = $this->wing['options']['merchant_config'];
        $serviceConfig = $this->wing['options']['service_config'];
    }


    /**
     *  Add Shipping Method
     */
    public function init_shipping_method()
    {
        if (!class_exists('Shipox_Shipping_Method')) {
            include_once(SHIPOX_ABSPATH . 'classes/class-shipox-shipping-method.php');
        }
    }

    /**
     * @param $methods
     * @return array
     */
    function add_shipping_method($methods)
    {
        if (class_exists('Shipox_Shipping_Method')) {
            $methods[] = 'Shipox_Shipping_Method';
        }

        return $methods;
    }

    /**
     * Init Wing Options
     * @return array
     */
    private function init_options()
    {
        $options = array(
            'service_config' => get_option('wing_service_config'),
            'merchant_config' => get_option('wing_merchant_config'),
            'merchant_address' => get_option('wing_merchant_address'),
            'order_config' => get_option('wing_order_config'),
            'marketplace_settings' => get_option('wing_marketplace_settings'),
        );

        return $options;
    }
}
