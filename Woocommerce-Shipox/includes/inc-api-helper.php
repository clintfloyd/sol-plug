<?php
/**
 * Created by Shipox.
 * User: Shipox
 * Date: 11/8/2017
 * Time: 2:41 PM
 */

class Shipox_API_Helper
{

    /**
     * API_HELPER constructor.
     */
    public function __construct()
    {
        add_action('wp_ajax_get_shipox_token', array($this, 'get_shipox_token'));
    }

    /**
     * @param $countryId
     * @return bool
     */
    public function isDomestic($countryId)
    {
        $marketplaceCountryId = shipox()->wing['settings-helper']->getCountryId();
        return $countryId == $marketplaceCountryId ? true : false;
    }


    /**
     * @return string
     */
    public function getTrackingURl()
    {
//        $serviceConfig = shipox()->wing['options']['service_config'];
        $marketplaceHost = shipox()->wing['settings-helper']->getMarketplaceHost();

//        if ($serviceConfig['test_mode'] == 1) {
//            return WING_DEV_SITE_URL;
//        }
        return 'https://' . $marketplaceHost;
    }

    /**
     * Authenticate to Wing and get Token
     */
    public function get_shipox_token()
    {
        $returned_data = array('success' => false);

        check_ajax_referer('shipox-wp-woocommerse-plugin', 'nonce');

        $merchant_email = stripslashes($_POST['merchantEmail']);
        $merchant_password = stripslashes($_POST['merchantPassword']);

        if (!empty($merchant_email) && !empty($merchant_password)) {
            $request = array(
                'username' => $merchant_email,
                'password' => $merchant_password,
                'remember_me' => true
            );


            shipox()->log->write($request, 'error');

            $response = shipox()->api->authenticate($request);

            shipox()->log->write($response, 'error');


            if ($response['success']) {
                $options = shipox()->wing['options']['merchant_config'];
                $options['merchant_token'] = $response['data']['id_token'];
                $options['merchant_username'] = $merchant_email;
                $options['merchant_password'] = $merchant_password;
                $options['last_login_date'] = time();

                update_option('wing_merchant_config', $options);

                $this->updateCustomerMarketplace();

                $returned_data['success'] = true;
                $returned_data['token'] = $options['merchant_token'];
            } else {
                shipox()->log->write($response['data']['code'] . ": " . $response['data']['message'], 'error');
                $returned_data = array('message' => $response['data']['message']);
            }
        }

        echo json_encode($returned_data);
        exit;
    }

    /**
     * Get Country ID From WING
     * @param $countryCode
     * @return int
     */
    function getCountryWingId($countryCode)
    {
        $result = shipox()->api->wingCountries($countryCode);

        foreach ($result['data'] as $country) {
            if (is_array($country) && $country['code'] == $countryCode) {
                return $country;
                break;
            }
        }

        return false;
    }

    /**
     * @param int $totalWeight
     * @param $countryId
     * @return int
     */
    function getPackageType($countryId, $totalWeight = 0)
    {
        $marketplaceCountryId = shipox()->wing['settings-helper']->getCountryId();

        $requestPackage = array(
            "from_country_id" => $marketplaceCountryId,
            "to_country_id" => $countryId,
        );

        $result = shipox()->api->wingPackageMenuList('?' . http_build_query($requestPackage));

        if ($result['success']) {
            $list = $result['data'];
            foreach ($list['list'] as $package) {
                if ($package["weight"] >= $totalWeight) {
                    return $package["menu_id"];
                }
            }
        }

        shipox()->log->write($totalWeight, 'package-error');
        shipox()->log->write($result, 'package-error');

        return 0;
    }

    /**
     * @param $courierTypes
     * @param $packageList
     * @return int
     */
    function getProperPackage($courierTypes, $packageList)
    {
        foreach ($packageList as $itemByVehicles) {
            $packages = $itemByVehicles['packages'];

            foreach ($packages as $packageItem) {
                if (in_array($packageItem['courier_type'], $courierTypes, true)) {
                    return $packageItem['id'];
                }
            }
        }
        return 0;
    }

    /**
     * @param $courierTypes
     * @param $packageList
     * @return int
     */
    function getProperPackageV2($courierTypes, $packageList)
    {
        foreach ($packageList as $package) {
            $courierType = $package['courier_type'];

            if (in_array($courierType['type'], $courierTypes, true)) {
                return $package;
            }
        }
        return null;
    }

    /**
     * @param $paymentOption
     * @param $price
     * @return int
     */
    function getCustomService($paymentOption, $price)
    {
        //if($paymentOption == 'credit_balance')
        //  return 0;

        return $price;
    }

    /**
     * @param $city
     * @param int $cityId
     * @param null $region
     * @return bool
     */
    public function getCitiesLatLon($city, $cityId = 0, $region = null)
    {
        $request = shipox()->api;

        if ($cityId > 0) {
            $result = $request->getCity($cityId);

            if ($result['status'] == 'success') {
                return $result['data'];
            }

        } else {
            $result = $request->getCityList(true);
            foreach ($result as $item) {
                if (stripos($item['name'], $city) > -1) {
                    return $item;
                    break;
                }
                if (!is_null($region) && stripos($item['name'], $region) > -1) {
                    return $item;
                    break;
                }
            }
        }

        return false;
    }

    /**
     * @param $country
     * @param $shippingAddress
     * @param int $cityId
     * @return array
     * @deprecated
     */
    function getAddressLatLon($country, $shippingAddress, $cityId = 0)
    {
        $responseArray = array();

        $city = $shippingAddress["city"];
        $region = $shippingAddress["state"];

        if ($this->isDomestic($country['id'])) {
            // For Domestic Orders

            $wingCity = $this->getCitiesLatLon($city, $cityId, $region);

            if ($wingCity) {
                $responseArray[0] = $wingCity['latitude'];
                $responseArray[1] = $wingCity['longitude'];
            }
        } else {
            // For International Orders
            if (!is_null($country['lat']) && !is_null($country['lng'])) {
                $responseArray[0] = $country['lat'];
                $responseArray[1] = $country['lng'];
            }
        }

        return $responseArray;
    }

    /**
     * @param $country
     * @param $shippingAddress
     * @return null
     */
    function getAddressLocation($country, $shippingAddress)
    {
        $countries = WC()->countries->get_allowed_countries();
        $domestic = $this->isDomestic($country['id']);
        $responseArray = array();

        $request = array(
            'address' => $shippingAddress["address_1"] . " " . $shippingAddress["address_2"],
            'city' => $shippingAddress["city"],
            'country' => $domestic ? $country['name'] : $countries[$shippingAddress['country']],
            'provinceOrState' => $shippingAddress["state"],
            'domestic' => $domestic,
        );

        $location = shipox()->api->getLocationByAddress($request);
        if ($location['success']) {
            if (!is_null($location['data']['lat']) && !is_null($location['data']['lon'])) {
                $responseArray[0] = $location['data']['lat'];
                $responseArray[1] = $location['data']['lon'];
            }

        }

        return $responseArray;
    }

    /**
     *
     * @param $order_wc
     * @param $country
     * @return array
     */
    function getWingPackages($order_wc, $country)
    {
        $orderConfig = shipox()->wing['options']['order_config'];
        $merchantAddress = shipox()->wing['options']['merchant_address'];

        $response = array(
            'success' => false,
            'message' => null,
            'data' => null,
        );
        $shipping_address = $order_wc->get_address('shipping');
        $products = $order_wc->get_items();

        $weight = 0;
        foreach ($products as $product) {

            if ($product['variation_id'] != 0) {
//                shipox()->log->write($product['variation_id'], 'order-create-product');

                $product_obj = new WC_Product_Variation($product['variation_id']);
            } else {
                $product_obj = new WC_Product($product['product_id']);
            }

            $product_weight = (float)$product_obj->get_weight();

            $quantity = $product['qty'];

            $weight += $product_weight * $quantity;
        }

        $weight = wc_get_weight($weight, 'kg');

        if ($orderConfig['order_default_weight'] > 0) {
            $weight = intval($orderConfig['order_default_weight']);
        }

        $order_wc->add_order_note(sprintf("Shipox: Total Weight: %s", $weight), 0);

        $countryObject = $this->getCountryWingId($country);
        $menuId = $this->getPackageType($countryObject['id'], $weight);

        if ($menuId > 0) {
            $merchantLatLong = explode(",", $merchantAddress['merchant_lat_long']);

            if (!empty($merchantLatLong)) {
                $customerLatLonAddress = $this->getAddressLocation($countryObject, $shipping_address);

                if (!empty($customerLatLonAddress)) {
                    $priceRequestData = array(
                        "service" => 'LOGISTICS',
                        "from_lat" => trim($merchantLatLong[0]),
                        "to_lat" => $customerLatLonAddress[0],
                        "from_lon" => trim($merchantLatLong[1]),
                        "to_lon" => $customerLatLonAddress[1],
                        "menu_id" => $menuId,
                    );

                    $priceList = shipox()->api->wingCalcPrices('?' . http_build_query($priceRequestData));

                    if ($priceList['success']) {
                        $list = $priceList['data']['list'];

                        if (is_array($list) && !empty($list)) {
                            $response['success'] = true;
                            $response['data'] = array(
                                'list' => $list,
                                'lat_lon' => $customerLatLonAddress[0] . "," . $customerLatLonAddress[1],
                            );
                        }
                    } else $response['message'] = 'Shipox: Error with Pricing Packages';
                } else $response['message'] = 'Shipox: Shipping City/Province is entered wrong';
            } else $response['message'] = 'Shipox: Merchant Address Location didn\'t configured properly';
        } else $response['message'] = 'Shipox: Could not find proper Menu';

        return $response;
    }


    function getWingPackageV2($order_wc, $country) {
        $orderConfig = shipox()->wing['options']['order_config'];
        $merchantAddress = shipox()->wing['options']['merchant_address'];

        $response = array(
            'success' => false,
            'message' => null,
            'data' => null,
        );
        $shipping_address = $order_wc->get_address('shipping');
        $products = $order_wc->get_items();

        $weight = 0;
        foreach ($products as $product) {

            if ($product['variation_id'] != 0) {
                $product_obj = new WC_Product_Variation($product['variation_id']);
            } else {
                $product_obj = new WC_Product($product['product_id']);
            }

            $product_weight = (float)$product_obj->get_weight();

            $quantity = $product['qty'];

            $weight += $product_weight * $quantity;
        }

        $weight = wc_get_weight($weight, 'kg');

        if ($orderConfig['order_default_weight'] > 0) {
            $weight = intval($orderConfig['order_default_weight']);
        }

        $order_wc->add_order_note(sprintf("Shipox: Total Weight: %s", $weight), 0);

        $countryObject = $this->getCountryWingId($country);
        $merchantLatLong = explode(",", $merchantAddress['merchant_lat_long']);

        if (!empty($merchantLatLong)) {
            $customerLatLonAddress = $this->getAddressLocation($countryObject, $shipping_address);

            if (!empty($customerLatLonAddress)) {
                $isDomestic = $this->isDomestic($countryObject["id"]);

                $priceRequestData = array(
                    'dimensions.domestic' => $isDomestic,
                    'dimensions.length' => 10,
                    'dimensions.width' => 10,
                    'dimensions.weight' => $weight,
                    'dimensions.unit' => 'METRIC',
                    'from_country_id' => shipox()->wing['settings-helper']->getCountryId(),
                    'to_country_id' => $countryObject["id"],
                    'from_latitude' => trim($merchantLatLong[0]),
                    'from_longitude' => trim($merchantLatLong[1]),
                    'to_latitude' => $customerLatLonAddress[0],
                    'to_longitude' => $customerLatLonAddress[1],
                    'service_types' => implode(",", $orderConfig['order_default_courier_type']),
                );

                $priceList = shipox()->api->getPackagePricesV2($priceRequestData);

                if ($priceList['success']) {
                    $list = $priceList['data']['list'];

                    if (is_array($list) && !empty($list)) {
                        $response['success'] = true;
                        $response['data'] = array(
                            'list' => $list,
                            'lat_lon' => $customerLatLonAddress[0] . "," . $customerLatLonAddress[1],
                            'weight' => $weight,
                            'is_domestic' => $isDomestic,
                        );
                    }
                } else $response['message'] = 'Shipox: Error with Pricing Packages';
            } else $response['message'] = 'Shipox: Shipping City/Province is entered wrong';
        } else $response['message'] = 'Shipox: Merchant Address Location didn\'t configured properly';

        return $response;
    }

    /**pushOrderToWingWithPackage
     * @param $order_wc
     * @param $package
     * @param $customerLatLong
     */
    function pushOrderToWingWithPackage($order_wc, $package, $customerLatLong)
    {
        $orderConfig = shipox()->wing['options']['order_config'];
        $merchantAddress = shipox()->wing['options']['merchant_address'];
        $shipping_address = $order_wc->get_address('shipping');
        $merchantLatLong = explode(",", $merchantAddress['merchant_lat_long']);

        $products = $order_wc->get_items();

        $orderItems = null;
        foreach ($products as $product) {
            $orderItems .= $product['name'] . ' - Qty: ' . $product['qty'] . ', ';
        }

        if (intval($package) > 0 && !empty($merchantLatLong) && !empty($customerLatLong)) {

            $requestData = array();

            // Order ID As a Reference
            $requestData['reference_id'] = $order_wc->get_id() . "/" . $order_wc->get_order_number();

            //Charge Items COD
            $requestData['charge_items'] = array();

            $wingCod = $order_wc->get_subtotal() + $order_wc->get_total_tax() - $order_wc->get_discount_total();

            if ($order_wc->get_payment_method() == "cod") {
                $requestData['payer'] = 'recipient';
                $requestData['parcel_value'] = $order_wc->get_total();

                $requestData['charge_items'] = array(
                    array(
                        'charge_type' => "cod",
                        'charge' => $wingCod // Round Up the COD by requesting Finance
                    ),
                    array(
                        'charge_type' => "service_custom",
                        'charge' => $this->getCustomService($orderConfig['order_default_payment_option'], $order_wc->get_total() - $wingCod)
                    )
                );
            } else {
                $requestData['payer'] = 'sender';

                $requestData['charge_items'] = array(
                    array(
                        'charge_type' => "cod",
                        'charge' => 0
                    ),
                    array(
                        'charge_type' => "service_custom",
                        'charge' => 0
                    )
                );
            }

            //  PickUp Time
            $requestData['pickup_time_now'] = false;

            //  Request Details
            $requestData['request_details'] = $orderItems;

            //  PhotoItems
            $requestData['photo_items'] = array();

            //  PackageInfo
            $requestData['package'] = array('id' => $package);

            // Must provide this as true to overcome the our cut off times
            $requestData['force_create'] = true;

            //  Locations
            $requestData['locations'][] = array(
                'pickup' => true,
                'lat' => trim($merchantLatLong[0]),
                'lon' => trim($merchantLatLong[1]),
                'address' => substr($merchantAddress['merchant_street'], 0, 145) .' ' .$merchantAddress['merchant_address'],
                'details' => '',
                'phone' => $merchantAddress['merchant_phone'],
                'email' => $merchantAddress['merchant_contact_email'],
                'contact_name' => $merchantAddress['merchant_contact_name'],
                'address_city' => $merchantAddress['merchant_city'],
                'address_street' => substr($merchantAddress['merchant_street'], 0, 145)
            );

            $requestData['locations'][] = array(
                'pickup' => false,
                'lat' => trim($customerLatLong[0]),
                'lon' => trim($customerLatLong[1]),
                'address' => $shipping_address['address_1'] . ' ' . $shipping_address['address_2'] . ' ' . $shipping_address['city'] . ' ' . $shipping_address['country'],
                'details' => $order_wc->get_customer_note(),
                'phone' => $order_wc->get_billing_phone(),
                'email' => $order_wc->get_billing_email(),
                'address_city' => $shipping_address['city'],
                'address_street' => substr($shipping_address['address_1'] . ' ' . $shipping_address['address_2'], 0, 145),
                'contact_name' => $shipping_address['first_name'] . ' ' . $shipping_address['last_name'],
            );

            //Note
            $requestData['note'] = home_url() . ', ' . $orderItems;

            //Payment Type
            $requestData['payment_type'] = $orderConfig['order_default_payment_option'];

            //If Recipient Not Available
            $requestData['recipient_not_available'] = 'do_not_deliver';

            $response = shipox()->api->wingCreateOrder($requestData);

            if ($response['success']) {
                $responseData = $response['data'];

                update_post_meta($order_wc->get_id(), '_wing_order_number', $responseData['order_number']);
                update_post_meta($order_wc->get_id(), '_wing_order_id', $responseData['id']);
                update_post_meta($order_wc->get_id(), '_wing_status', $responseData['status']);

                $order_wc->add_order_note("Shipox: Wing Order number is: #" . $responseData['order_number'], 1);
            } else {
                shipox()->log->write($requestData, 'order-create-error');
                shipox()->log->write($response, 'order-create-error');
                $order_wc->add_order_note(sprintf("Shipox: Order Creation Error: %s", $response['data']['message']), 0);
            }
        }
    }


    /**
     * @param $order_wc
     * @param $package
     * @param $customerLatLong
     * @param $to_country
     */
    function pushOrderToWingWithPackageNewModel($order_wc, $package, $customerLatLong, $to_country)
    {
        $orderConfig = shipox()->wing['options']['order_config'];
        $merchantAddress = shipox()->wing['options']['merchant_address'];
        $shipping_address = $order_wc->get_address('shipping');
        $merchantLatLong = explode(",", $merchantAddress['merchant_lat_long']);
        $package_price = explode("-", $package);

        $products = $order_wc->get_items();

        $orderItems = null;
        foreach ($products as $product) {
            $orderItems .= $product['name'] . ' - Qty: ' . $product['qty'] . ', ';
        }

        if (intval($package) > 0 && !empty($merchantLatLong) && !empty($customerLatLong)) {
            $requestData = array();

            // Order ID As a Reference
            $requestData['reference_id'] = $order_wc->get_id() . "/" . $order_wc->get_order_number();

            //Charge Items COD
            $requestData['charge_items'] = array();

            $wingCod = $order_wc->get_subtotal() + $order_wc->get_total_tax() - $order_wc->get_discount_total();

            if ($order_wc->get_payment_method() == "cod") {
                $requestData['payer'] = 'recipient';
                $requestData['parcel_value'] = $order_wc->get_total();

                $requestData['charge_items'] = array(
                    array(
                        'charge_type' => "cod",
                        'charge' => $wingCod // Round Up the COD by requesting Finance
                    ),
                    array(
                        'charge_type' => "service_custom",
                        'charge' => $this->getCustomService($orderConfig['order_default_payment_option'], $order_wc->get_total() - $wingCod)
                    )
                );
            } else {
                $requestData['payer'] = 'sender';

                $requestData['charge_items'] = array(
                    array(
                        'charge_type' => "cod",
                        'charge' => 0
                    ),
                    array(
                        'charge_type' => "service_custom",
                        'charge' => 0
                    )
                );
            }

            //  PickUp Time
            $requestData['pickup_time_now'] = false;

            //  Request Details
            $requestData['request_details'] = $orderItems;

            //  PhotoItems
            $requestData['photo_items'] = array();

            $requestData['package_type'] = array(
                'id' => $package_price[0],

                'package_price' => array(
                    'id' => $package_price[1],
                ));

            // Must provide this as true to overcome the our cut off times
            $requestData['force_create'] = true;

            //  Locations
            $requestData['sender_data'] = array(
                'address_type' => 'business',
                'name' => $merchantAddress['merchant_contact_name'],
                'email' => $merchantAddress['merchant_contact_email'],
                'phone' => $merchantAddress['merchant_phone'],
                'address' => $merchantAddress['merchant_address'],
                'details' => '',
                'country' => array('id' => shipox()->wing['settings-helper']->getCountryId()),
                'city' => array('name' => $merchantAddress['merchant_city']),
                'street' => substr($merchantAddress['merchant_street'], 0, 145),
                'lat' => trim($merchantLatLong[0]),
                'lon' => trim($merchantLatLong[1]),
            );

            $requestData['recipient_data'] = array(
                'address_type' => 'residential',
                'name' => $shipping_address['first_name'] . ' ' . $shipping_address['last_name'],
                'phone' => $order_wc->get_billing_phone(),
                'email' => $order_wc->get_billing_email(),

                'address' => $shipping_address['address_1'] . ' ' . $shipping_address['address_2'] . ' ' . $shipping_address['city'] . ' ' . $shipping_address['country'],
                'details' => $order_wc->get_customer_note(),
                'country' =>  array('id' => $to_country['id']),
                'city' =>  array('name' => $shipping_address['city']),
                'street' => substr($shipping_address['address_1'] . ' ' . $shipping_address['address_2'], 0, 145),

                'lat' => trim($customerLatLong[0]),
                'lon' => trim($customerLatLong[1]),
            );

            $requestData['dimensions'] = array(
                'width' => 10,
                'length' => 10,
                'height' => 10,
                'weight' => $package_price[2],
                'unit' => 'METRIC',
                'domestic' => $package_price[3] == 1 ? true : false,
            );

            //Note
            $requestData['note'] = home_url() . ', ' . $orderItems;

            //Payment Type
            $requestData['payment_type'] = $orderConfig['order_default_payment_option'];

            //If Recipient Not Available
            $requestData['recipient_not_available'] = 'do_not_deliver';

            $response = shipox()->api->wingCreateOrderV2($requestData);

            if ($response['success']) {
                $responseData = $response['data'];

                update_post_meta($order_wc->get_id(), '_wing_order_number', $responseData['order_number']);
                update_post_meta($order_wc->get_id(), '_wing_order_id', $responseData['id']);
                update_post_meta($order_wc->get_id(), '_wing_status', $responseData['status']);

                $order_wc->add_order_note("Shipox: Wing Order number is: #" . $responseData['order_number'], 1);
            } else {
                shipox()->log->write($requestData, 'order-create-error');
                shipox()->log->write($response, 'order-create-error');
                $order_wc->add_order_note(sprintf("Shipox: Order Creation Error: %s", $response['data']['message']), 0);
            }
        }
    }


    /**
     * @param $wingOrderId
     * @return bool
     */
    function getAirwaybill($wingOrderId)
    {
        $response = shipox()->api->getAirwaybill($wingOrderId);

        if ($response['success'])
            return $response['data']['value'];

        return false;
    }


    public function updateCustomerMarketplace()
    {
        $response = shipox()->api->getCustomerMarketplace();

        $options = array();
        if ($response['success']) {
            $marketplace = $response['data'];
            $options['currency'] = $marketplace['currency'];
            $options['custom'] = $marketplace['custom'];
            $options['decimal_point'] = isset($marketplace['setting']['settings']['decimalPoint']) ? $marketplace['setting']['settings']['decimalPoint'] : 2;
            $options['disable_international_orders'] = isset($marketplace['setting']['settings']['disableInternationalOrders']) ? $marketplace['setting']['settings']['disableInternationalOrders'] : false;
            $options['new_model_enabled'] = isset($marketplace['setting']['settings']['newModelEnabled']) ? $marketplace['setting']['settings']['newModelEnabled'] : false;
            $options['host'] = isset($marketplace['setting']['settings']['customerDomain']) ? $marketplace['setting']['settings']['customerDomain'] : 'my.wing.ae';
            $options['country'] = $marketplace['country'];

            update_option('wing_marketplace_settings', $options);
        }
    }
}