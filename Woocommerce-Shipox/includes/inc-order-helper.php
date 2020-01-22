<?php
/**
 * Created by PhpStorm.
 * User: umidakhm
 * Date: 10/17/2018
 * Time: 3:31 PM
 */

namespace includes;


class Shipox_Order_Helper
{

    /**
     * @param $order
     * @param $shipping_country
     * @return array
     */
    function check_wing_order_create_availability($order, $shipping_country) {
        $baseLocation = wc_get_base_location();
        $orderConfig = shipox()->wing['options']['order_config'];
        $marketplaceCountry = shipox()->wing['settings-helper']->getCountryCode();
        $marketplaceCountryName = shipox()->wing['settings-helper']->getCountryName();
        $marketplaceCurrency = shipox()->wing['settings-helper']->getCurrency();
        $marketplaceIntAvailability = shipox()->wing['settings-helper']->getInternationalAvailability();

        $response = array(
            'success' => false,
            'message' => null,
        );

        if ($baseLocation["country"] == $marketplaceCountry) {
            $currency = $order->get_currency();

            if ($currency == $marketplaceCurrency) {
                if ($orderConfig['order_international_availability'] == 0 && !$marketplaceIntAvailability && $shipping_country !== $marketplaceCountry) {
                    shipox()->log->write($order->get_id() . " - International is turned off", 'error');
                    $response['message'] = __("Shipox: International Order is turned off", 'wing');
                } else {
                    $response['success'] = true;
                }
            } else {
                shipox()->log->write($order->get_id() . " - CURRENCY should be only ". $marketplaceCurrency, 'error');
                $response['message'] = __("Shipox: CURRENCY should be only ". $marketplaceCurrency, 'wing');
            }
        } else {
            shipox()->log->write($order->get_id() . " - Delivery only for ". $marketplaceCountryName, 'error');
            $response['message'] = __("Shipox: Service only within ".$marketplaceCountryName, 'wing');
        }

        return $response;
    }
}