<?php
/**
 * Created by Shipox.
 * User: Shipox
 * Date: 11/8/2017
 * Time: 2:41 PM
 */

class Shipox_Shipping_Method extends WC_Shipping_Method
{

    /**
     * Shipox_Shipping_Method constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->id = 'wing';
        $this->method_title = __('Shipox', 'wing');
        $this->enabled = $this->get_option('enabled');
        $this->init();
        $this->title = isset($this->settings['title']) ? $this->settings['title'] : __('Shipox Shipping', 'wing');
    }

    /**
     *
     */
    private function init()
    {
        $this->init_form_fields();
        $this->init_settings();

        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
    }

    /**
     * Define settings field for this shipping
     * @return void
     */
    function init_form_fields()
    {
        $this->form_fields = array(

            'enabled' => array(
                'title' => __('Enable', 'wing'),
                'type' => 'checkbox',
                'description' => __('Enable this shipping.', 'wing'),
                'default' => 'yes'
            ),

            'title' => array(
                'title' => __('Title', 'wing'),
                'type' => 'text',
                'description' => __('Title to be display on site', 'wing'),
                'default' => __('Shipox Shipping', 'wing')
            ),

            'weight' => array(
                'title' => __('Weight (kg)', 'wing'),
                'type' => 'number',
                'description' => __('Maximum allowed weight', 'wing'),
                'default' => 100
            ),

            'availability' => array(
                'title' => __('Methods availability', 'wing'),
                'type' => 'select',
                'default' => 'all',
                'class' => 'availability wc-enhanced-select',
                'options' => array(
                    'all' => __('All allowed countries', 'wing'),
                    'specific' => __('Specific Countries', 'wing')
                )
            ),

            'countries' => array(
                'title' => __('Specific Countries', 'wing'),
                'type' => 'multiselect',
                'class' => 'wc-enhanced-select',
                'css' => 'width: 450px;',
                'default' => '',
                'options' => WC()->countries->get_shipping_countries(),
                'custom_attributes' => array(
                    'data-placeholder' => __('Select some countries', 'wing')
                )
            ),
        );
    }


    /**
     *  Calculate Shipping
     * @param array $package
     */
    public function calculate_shipping($package = array())
    {
        if (!$this->enabled) return;

        $baseLocation = wc_get_base_location();

        $marketplaceCountry = shipox()->wing['settings-helper']->getCountryCode();
        $marketplaceCurrency = shipox()->wing['settings-helper']->getCurrency();

        if (get_woocommerce_currency() !== $marketplaceCurrency || $baseLocation["country"] !== $marketplaceCountry) return;

        $orderConfig = shipox()->wing['options']['order_config'];
        $merchantAddress = shipox()->wing['options']['merchant_address'];

        $address = $package['destination'];

        if ($this->get_option('availability') == 'specific' && !in_array($address["country"], $this->get_option('countries')))
            return;

        $weight = 0;
        foreach ($package['contents'] as $item_id => $values)
            if ($values['quantity'] > 0 && $values['data']->needs_shipping())
                $weight += intval($values['quantity']) * floatval($values['data']->get_weight());

        $weight = wc_get_weight($weight, 'kg');

        if ($orderConfig['order_default_weight'] > 0) {
            $weight = intval($orderConfig['order_default_weight']);
        }

        $country = shipox()->wing['api-helper']->getCountryWingId($address["country"]);
        $marketplaceIntAvailability = shipox()->wing['settings-helper']->getInternationalAvailability();
        $isDomestic = shipox()->wing['api-helper']->isDomestic($country["id"]);

        if ($orderConfig['order_international_availability'] == 0 && !$marketplaceIntAvailability && !$isDomestic)
            return;

        $merchantLatLong = explode(",", $merchantAddress['merchant_lat_long']);

        if (!empty($merchantLatLong)) {
            $customerLatLonAddress = shipox()->wing['api-helper']->getAddressLocation($country, $address);

            if (!empty($customerLatLonAddress)) {
                $priceArray = array();
                $isNewModel = shipox()->wing['settings-helper']->getNewModelEnabled();

                if ($isNewModel) {
                    // New Model
                    $priceRequestData = array(
                        'dimensions.domestic' => $isDomestic,
                        'dimensions.length' => 10,
                        'dimensions.width' => 10,
                        'dimensions.weight' => $weight,
                        'dimensions.unit' => 'METRIC',
                        'from_country_id' => shipox()->wing['settings-helper']->getCountryId(),
                        'to_country_id' => $country["id"],
                        'from_latitude' => trim($merchantLatLong[0]),
                        'from_longitude' => trim($merchantLatLong[1]),
                        'to_latitude' => $customerLatLonAddress[0],
                        'to_longitude' => $customerLatLonAddress[1],
                        'service_types' => implode(",", $orderConfig['order_default_courier_type']),
                    );

                    $priceList = shipox()->api->getPackagePricesV2($priceRequestData);

                    if ($priceList['success']) {
                        $list = $priceList['data']['list'];

                        foreach ($list as $listItem) {
                            $priceItem = $listItem['price'];
                            $name = $listItem['supplier']['name'] . " - " . $listItem['name'];
                            $method = $listItem['id'].'-'.$priceItem['id'].'-'.$weight.'-'.($isDomestic ? '1' : '0');
                            $response['type'] = 'success';
                            $priceArray[$method] = array('label' => $name, 'amount' => $priceItem['total']);
                        }
                    }
                } else {

                    // OLD Model
                    $menuId = shipox()->wing['api-helper']->getPackageType($country['id'], $weight);
                    if ($menuId > 0) {
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

                            foreach ($list as $listItem) {
                                $packages = $listItem['packages'];
                                $name = $listItem['name'];

                                foreach ($packages as $packageItem) {
                                    $label = $packageItem['delivery_label'];
                                    $price = $packageItem['price']['total'];
                                    $method = $packageItem['id'];

                                    $response['type'] = 'success';
                                    $priceArray[$method] = array('label' => $name . " - " . $label, 'amount' => $price);
                                }
                            }
                        }
                    }
                }

            }
        }

        if (isset($priceArray) && !empty($priceArray)) {
            foreach ($priceArray as $key => $value) {
                $rate = array(
                    'id' => $this->id . '_' . $key,
                    'label' => $value['label'],
                    'cost' => $value['amount'],
                    'calc_tax' => 'per_order'
                );
                $this->add_rate($rate);
            }
        }
    }

}


new Shipox_Shipping_Method();