<?php
/**
 * Created by Shipoxy.
 * User: Shipox
 * Date: 11/8/2017
 * Time: 2:41 PM
 */

class Shipox_Backend_Actions
{

    /**
     * API_HELPER constructor.
     */
    public function __construct()
    {
        add_action('woocommerce_order_status_changed', array($this, 'wing_status_changed_action'), 10, 3);
        add_action('woocommerce_admin_order_data_after_order_details', array($this, 'wing_order_metabox'));
        add_action('woocommerce_process_shop_order_meta', array($this, 'wing_order_save_metabox'), PHP_INT_MAX, 3);
    }


    /**
     * Create Wing order by Order WC Actions
     * @param $order_id
     * @param $from_status
     * @param $to_status
     */

    function wing_status_changed_action($order_id, $from_status, $to_status)
    {
        $serviceConfig = shipox()->wing['options']['service_config'];
        $orderConfig = shipox()->wing['options']['order_config'];
        $order = new WC_Order($order_id);
        $shipping_method = get_post_meta($order->get_id(), '_wing_order_package');
        $shipping_method[] = "0";
        $shipping_method = reset($shipping_method);
        $orderNumber = get_post_meta($order->get_id(), '_wing_order_number');

        if ($serviceConfig['auto_push'] == "wc-" . $to_status && strpos($shipping_method, "wing_") == false && empty($orderNumber)) {
            $shipping_country = trim(get_post_meta($order->get_id(), '_shipping_country', true));
            $availability = shipox()->wing['order-helper']->check_wing_order_create_availability($order, $shipping_country);

            if ($availability['success']) {
                $isNewModel = shipox()->wing['settings-helper']->getNewModelEnabled();
                if($isNewModel) {
                    // New Model
                    $priceList = shipox()->wing["api-helper"]->getWingPackageV2($order, $shipping_country);
                    $toLatLon = $priceList['data']['lat_lon'];
                    $customerLatLon = explode(",", $toLatLon);

                    if ($priceList['success']) {
                        $packageList = $priceList['data']['list'];
                        $auto_selected_package = shipox()->wing['api-helper']->getProperPackageV2($orderConfig['order_default_courier_type'], $packageList);

                        if($auto_selected_package) {
                            $weight = $priceList['data']['weight'];
                            $isDomestic = $priceList['data']['is_domestic'];
                            $country = shipox()->wing["api-helper"]->getCountryWingId($shipping_country);
                            $packageString = $auto_selected_package['id'].'-'.$auto_selected_package['price']['id'].'-'.$weight.'-'.($isDomestic ? '1' : '0');

                            shipox()->wing['api-helper']->pushOrderToWingWithPackageNewModel($order, $packageString, $customerLatLon, $country);
                        } else {
                            $order->add_order_note("Shipox: Could not find proper package!", 0);
                            shipox()->log->write($packageList, 'package-error');
                            shipox()->log->write("Default Courier Type: " . $orderConfig['order_default_courier_type'], 'package-error');
                        }

                    } else {
                        $order->add_order_note($priceList['message'], 0);
                        shipox()->log->write($priceList, 'package-error');
                        shipox()->log->write("Shipping Country: " . $shipping_country, 'package-error');
                    }

                } else {
                    // Old Model

                    $packages = shipox()->wing["api-helper"]->getWingPackages($order, $shipping_country);

                    if ($packages['success']) {
                        $toLatLon = $packages['data']['lat_lon'];
                        $customerLatLon = explode(",", $toLatLon);
                        $packageList = $packages['data']['list'];

                        $auto_selected_package_id = shipox()->wing['api-helper']->getProperPackage($orderConfig['order_default_courier_type'], $packageList);

                        if (intval($auto_selected_package_id) > 0) {
                            shipox()->wing['api-helper']->pushOrderToWingWithPackage($order, $auto_selected_package_id, $customerLatLon);
                        } else {
                            $order->add_order_note("Shipox: Could not find proper package!", 0);
                            shipox()->log->write($packageList, 'package-error');
                            shipox()->log->write("Default Courier Type: " . $orderConfig['order_default_courier_type'], 'package-error');
                        }
                    } else {
                        $order->add_order_note($packages['message'], 0);
                        shipox()->log->write($packages, 'package-error');
                        shipox()->log->write("Shipping Country: " . $shipping_country, 'package-error');
                    }
                }

            } else {
                $order->add_order_note($availability['message'], 0);
                shipox()->log->write($order, 'availability-error');
                shipox()->log->write("Shipping Country: " . $shipping_country, 'availability-error');
            }
        } elseif($to_status == "cancelled"  && !empty($orderNumber)) {
            $data = array(
                'note' => "Order Cancelled by Customer",
                'reason' => "Order Cancelled by Customer",
                'status' => 'cancelled'
            );

            $orderId = get_post_meta($order->get_id(), '_wing_order_id', true);
            $response = shipox()->api->updateOrderStatus($orderId, $data);

            if ($response['success']) {
                $order->add_order_note("Shipox: " . $orderNumber[0] . " is cancelled successfully", 1);
            } else {
                $order->add_order_note($response['data']['message'], 0);
                shipox()->log->write($response, 'order-error');
            }
        }
    }

    /**
     * @param $order
     */
    function wing_order_metabox($order)
    {
        echo "<br class=\"clear\" />";

        $orderNumber = get_post_meta($order->get_id(), '_wing_order_number');
        $orderId = get_post_meta($order->get_id(), '_wing_order_id');
        $shipping_method = get_post_meta($order->get_id(), '_wing_order_package');
        $shipping_method[] = "0";
        $shipping_method = reset($shipping_method);

        $selectedPackage = null;
        $isPackagesAvailable = false;
        $packages = null;
        $packageOptions = array(
            0 => "Select Package"
        );
        $toLatLon = null;
        $errorMessage = null;

        if (empty($orderNumber)) {
            $shipping_country = trim(get_post_meta($order->get_id(), '_shipping_country', true));

            $availability = shipox()->wing['order-helper']->check_wing_order_create_availability($order, $shipping_country);
            if ($availability['success']) {

                $isNewModel = shipox()->wing['settings-helper']->getNewModelEnabled();

                if($isNewModel) {


                    $priceList = shipox()->wing["api-helper"]->getWingPackageV2($order, $shipping_country);

                    if ($priceList['success']) {
                        $isPackagesAvailable = true;
                        $list = $priceList['data']['list'];
                        $weight = $priceList['data']['weight'];
                        $isDomestic = $priceList['data']['is_domestic'];
                        $toLatLon = $priceList['data']['lat_lon'];

                        foreach ($list as $listItem) {
                            $priceItem = $listItem['price'];
                            $name = $listItem['supplier']['name'] . " - " . $listItem['name'];
                            $method = $listItem['id'].'-'.$priceItem['id'].'-'.$weight.'-'.($isDomestic ? '1' : '0');
                            $currency = $priceItem['currency']['code'];
                            $response['type'] = 'success';
                            $packageOptions['wing_' .$method] = $name . " (" . $priceItem['total'] . " " . $currency . ")";
                        }
                    } else $errorMessage = $packages['message'];

                } else {
                    $packages = shipox()->wing["api-helper"]->getWingPackages($order, $shipping_country);

                    if ($packages['success']) {
                        $isPackagesAvailable = true;
                        $selectedPackage = strpos($shipping_method, "wing_") != false ? $shipping_method : null;

                        $toLatLon = $packages['data']['lat_lon'];
                        foreach ($packages['data']['list'] as $listItem) {
                            $packageList = $listItem['packages'];
                            $name = $listItem['name'];

                            foreach ($packageList as $packageItem) {
                                $label = $packageItem['delivery_label'];
                                $price = $packageItem['price']['total'];
                                $currency = $packageItem['price']['currency']['code'];
                                $packageOptions['wing_' . $packageItem['id']] = $name . " - " . $label . " (" . $price . " " . $currency . ")";
                            }
                        }
                    } else $errorMessage = $packages['message'];
                }


            } else $errorMessage = $availability['message'];



            echo "<h4>Shipox ";
            echo $isPackagesAvailable ? "<a href=\"#\" class=\"edit_address\">Edit</a>" : null;
            echo "</h4>";

            if ($isPackagesAvailable) {

                echo "<div class=\"address\">";
                echo "<a href=\"#\" class=\"edit_address wing_create_order_link\">".__("Create Shipox Order", 'wing')."</a>";
                echo "</div>";

                echo "<div class=\"edit_address\">";
                woocommerce_wp_select(array(
                    'id' => 'wing_package',
                    'label' => 'Packages:',
                    'value' => $selectedPackage,
                    'class' => 'wing-select-field',
                    'options' => $packageOptions,
                    'wrapper_class' => 'form-field-wide'
                ));
                woocommerce_wp_hidden_input(array(
                    'id' => 'wing_custom_lat_lon',
                    'value' => $toLatLon,
                    'wrapper_class' => 'form-field-wide'
                ));
                echo "</div>";
            } else {
                echo "<div class='wing-error'>" . $errorMessage . "</div>";
            }
        } else {
            $airwaybill = shipox()->wing['api-helper']->getAirwaybill($orderId[0]);

            echo "<h4 style='margin-bottom: 10px'>Shipox</h4>";

            echo "<div class=\"address\">";
            echo __("Order Number: #" . $orderNumber[0], 'wing');
            echo "<br class=\"clear\" />";
            echo "<a target=\"_blank\" href='" . shipox()->wing['api-helper']->getTrackingURl() . "/track?id=" . $orderNumber[0] . "'>Track Order</a>";
            if($airwaybill) {
                echo "<br class=\"clear\" />";
                echo "<a target=\"_blank\" href='" . $airwaybill . "'>Download Airwaybill</a>";
            }
            echo "</div>";
        }

    }


    /**
     *
     * @param $order_id
     */
    function wing_order_save_metabox($order_id)
    {
        $order = new WC_Order($order_id);

        if ($order->get_status() == 'processing') {
            $shippingMethod = wc_clean($_POST['wing_package']);
            $shipping_country = trim(get_post_meta($order_id, '_shipping_country', true));
            $package_data = explode("_", $shippingMethod);
            $toLatLon = wc_clean($_POST['wing_custom_lat_lon']);
            $customerLatLong = explode(",", $toLatLon);
            $country = shipox()->wing["api-helper"]->getCountryWingId($shipping_country);

            if (count($package_data) > 0) {
                update_post_meta($order->get_id(), '_wing_order_package', $shippingMethod);
                $isNewModel = shipox()->wing['settings-helper']->getNewModelEnabled();
                if($isNewModel)
                    shipox()->wing['api-helper']->pushOrderToWingWithPackageNewModel($order, $package_data[1], $customerLatLong, $country);
                else
                    shipox()->wing['api-helper']->pushOrderToWingWithPackage($order, $package_data[1], $customerLatLong);
            }
        }

    }


}

new Shipox_Backend_Actions();