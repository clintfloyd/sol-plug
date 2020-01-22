<?php
/**
 * Created by PhpStorm.
 * User: umidakhm
 * Date: 10/17/2018
 * Time: 3:31 PM
 */

namespace includes;


class Shipox_Settings_Helper
{
    private $_countryId = 229;
    private $_countryCode = 'AE';
    private $_countryName = 'United Arab Emirates';
    private $_currency = 'AED';
    private $_intAvailability = false;
    private $_newModelEnabled = false;
    private $_host = 'my.shipox.com';

    /**
     * @return mixed|void
     */
    public function getMarketplaceSettings()
    {
        return get_option('wing_marketplace_settings');
    }


    /**
     *  Get Marketplace Host
     */
    public function getCountryId()
    {
        $marketplaceSettings = $this->getMarketplaceSettings();
        return isset($marketplaceSettings['country']['id']) ? $marketplaceSettings['country']['id'] : $this->_countryId;
    }


    /**
     *  Get Marketplace Host
     */
    public function getCountryCode()
    {
        $marketplaceSettings = $this->getMarketplaceSettings();
        return isset($marketplaceSettings['country']['description']) ? $marketplaceSettings['country']['description'] : $this->_countryCode;
    }

    /**
     *  Get Marketplace Country Code
     */
    public function getMarketplaceHost()
    {
        $marketplaceSettings = $this->getMarketplaceSettings();
        return isset($marketplaceSettings['host']) ? $marketplaceSettings['host'] : $this->_host;
    }

    /**
     *  Get Marketplace Country Code
     */
    public function getCountryName()
    {
        $marketplaceSettings = $this->getMarketplaceSettings();
        return isset($marketplaceSettings['country']['name']) ? $marketplaceSettings['country']['name'] : $this->_countryName;
    }

    /**
     *  Get Marketplace Currency
     */
    public function getCurrency()
    {
        $marketplaceSettings = $this->getMarketplaceSettings();
        return isset($marketplaceSettings['currency']) ? $marketplaceSettings['currency'] : $this->_currency;
    }

    /**
     *  Get International Availability
     */
    public function getInternationalAvailability()
    {
        $marketplaceSettings = $this->getMarketplaceSettings();
        return isset($marketplaceSettings['disable_international_orders']) ? !$marketplaceSettings['disable_international_orders'] : $this->_intAvailability;
    }

    /**
     *  Get New Model Enabled
     */
    public function getNewModelEnabled()
    {
        $marketplaceSettings = $this->getMarketplaceSettings();
        return isset($marketplaceSettings['new_model_enabled']) ? $marketplaceSettings['new_model_enabled'] : $this->_newModelEnabled;
    }
}