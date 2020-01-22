<?php
/**
 * Created by Shipox.
 * User: Shipox
 * Date: 11/8/2017
 * Time: 2:41 PM
 */

class Shipox_Frontend_Actions
{

    /**
     * WING_FRONTEND_ACTIONS constructor.
     */
    public function __construct()
    {
        add_action('woocommerce_review_order_before_cart_contents', array($this, 'wing_validate_order'), 10);
        add_action('woocommerce_after_checkout_validation', array($this, 'wing_validate_order'), 10);
        add_filter('woocommerce_available_payment_gateways', array($this, 'wing_available_payment_gateways'));
//        add_action('woocommerce_checkout_order_processed', array($this, 'wing_order_processed'), 10, 1);
        add_action('woocommerce_thankyou', array($this, 'wing_order_processed'), 10, 1);
    }

    /**
     * @param $posted
     */
    public function wing_validate_order($posted)
    {
        $packages = WC()->shipping->get_packages();

        $chosen_methods = WC()->session->get('chosen_shipping_methods', null);

        $is_wing_chosen = false;
        if (is_array($chosen_methods)) {

            foreach ($chosen_methods as $method) {
                if (strpos($method, "wing_") !== false) {
                    $is_wing_chosen = true;
                    break;
                }
            }
        }

        if (is_array($chosen_methods) && $is_wing_chosen) {

            foreach ($packages as $i => $package) {

                if (strpos($chosen_methods[$i], "wing_") === false) {
                    continue;
                }

                $Shipox_Shipping_Method = new Shipox_Shipping_Method();
                $weightLimit = (int)$Shipox_Shipping_Method->settings['weight'];
                $weight = 0;

                foreach ($package['contents'] as $item_id => $values) {
                    $_product = $values['data'];
                    $weight = $weight + (float) $_product->get_weight() * (int) $values['quantity'];
                }

                $weight = wc_get_weight($weight, 'kg');

                if ($weight > $weightLimit) {
                    $message = sprintf(__('Sorry, %d kg exceeds the maximum weight of %d kg for %s', 'wing'), $weight, $weightLimit, $Shipox_Shipping_Method->title);
                    $messageType = "error";
                    if (!wc_has_notice($message, $messageType)) {
                        wc_add_notice($message, $messageType);
                    }
                }
            }
        }
    }

    /**
     * Remove COD Payment Gateway if order is International
     * @param $gateways
     * @return mixed
     */
    public function wing_available_payment_gateways($gateways)
    {
        if(isset(WC()->session)) {
            $chosen_methods = WC()->session->get('chosen_shipping_methods');

            $is_wing_chosen = false;
            if (is_array($chosen_methods)) {

                foreach ($chosen_methods as $method) {
                    if (strpos($method, "wing_") !== false) {
                        $is_wing_chosen = true;
                        break;
                    }
                }
            }

            if (is_array($chosen_methods) && $is_wing_chosen) {
                $packages = WC()->shipping->get_packages();
                $address = isset($packages[0]["destination"]) ? $packages[0]["destination"] : null;
                $country = shipox()->wing['api-helper']->getCountryWingId($address["country"]);

                if(!shipox()->wing['api-helper']->isDomestic($country["id"])) unset($gateways['cod']);
            }

        }

        return $gateways;
    }


    /**
     * @param $order_id
     */
    public function wing_order_processed($order_id) {
        if ( ! $order_id )
            return;

        if( ! get_post_meta( $order_id, '_thankyou_action_done', true ) ) {
            $order = new WC_Order( $order_id );
            $shipping_country = trim(get_post_meta($order_id, '_shipping_country', true));
            $shipping_methods = WC()->session->get( 'chosen_shipping_methods', array() );

            foreach ( $shipping_methods as $chosen_method ) {

                if (strpos($chosen_method, "wing_") !== false) {
                    if ($order->get_status() == 'processing') {
                        $shipping_method_data = explode("_", $chosen_method);
                        $shipping_address = $order->get_address('shipping');
                        $country = shipox()->wing["api-helper"]->getCountryWingId($shipping_country);
                        $customerLatLonAddress = shipox()->wing['api-helper']->getAddressLocation($country, $shipping_address);

                        if (count($shipping_method_data) > 0) {
                            update_post_meta($order->get_id(), '_wing_order_package', $chosen_method);
                            $isNewModel = shipox()->wing['settings-helper']->getNewModelEnabled();
                            if($isNewModel)
                                shipox()->wing['api-helper']->pushOrderToWingWithPackageNewModel($order, $shipping_method_data[1], $customerLatLonAddress, $country);
                            else
                                shipox()->wing['api-helper']->pushOrderToWingWithPackage($order, $shipping_method_data[1], $customerLatLonAddress);
                        }
                    } else {
                        update_post_meta($order->get_id(), '_wing_order_package', $chosen_method);
                        shipox()->log->write(sprintf("Payment Title %s and Order status: %s for Order ID: %s", $order->get_payment_method_title(), $order->get_status(), $order_id), 'payment-hold');
                        $order->add_order_note(sprintf("Shipox: Order is not pushed to Wing because the Payment (Method: %s) is not paid yet.", $order->get_payment_method_title()), 0);
                    }
                }
            }

            $order->update_meta_data( '_thankyou_action_done', true );
        }
    }

}

new Shipox_Frontend_Actions();