<?php
/**
 * Created by PhpStorm.
 * User: UmidAkhmedjanov
 * Date: 12/21/2018
 * Time: 2:15 PM
 */
if (!defined('ABSPATH')) {
    return;
}

class Shipox_Cron_Job
{

    private $interval = 60 * 60;
    private $_serviceConfig = array();
    private $_merchantInfo = array();
    private $_merchantConfig = array();

    /**
     * Start the Integration
     */
    public function __construct()
    {
        $this->_serviceConfig = get_option('wing_service_config');
        $this->_merchantInfo = get_option('wing_merchant_address');
        $this->_merchantConfig = get_option('wing_merchant_config');

        add_filter('cron_schedules', array($this, 'crawl_every_n_minutes'));

        if (!wp_next_scheduled('crawl_every_n_minutes')) {
            wp_schedule_event(time(), 'every_n_minutes', 'crawl_every_n_minutes');
        }

        add_action('crawl_every_n_minutes', array($this, 'crawl_feeds'));
    }

    /**
     * @param $schedules
     * @return mixed
     */
    public function crawl_every_n_minutes($schedules)
    {
        $schedules['every_n_minutes'] = array(
            'interval' => $this->interval,
            'display' => __('Every N Minutes', 'aur_domain')
        );

        return $schedules;
    }


    /**
     *  Get Live Events of Soccer
     */
    public function crawl_feeds()
    {
        shipox()->api->checkTokenExpired();
    }
}