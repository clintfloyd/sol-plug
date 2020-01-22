<?php
/**
 * Created by Shipox.
 * User: Shipox
 * Date: 11/8/2017
 * Time: 2:41 PM
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}


class Courier_Type
{
    public function getServiceTypes() {
        $serviceTypes = shipox()->api->getAllServiceTypes();

        if (!$serviceTypes['success']) {
            shipox()->log->write($serviceTypes['message'], 'service-type-error');
            return array();
        }
        $response = array();
        $list = $serviceTypes['data']['list'];

        foreach ($list as $item) {
            if($item['code'] === 'FBS') continue;

            $response[] = array(
                'value' => $item['code'],
                'label' => $item['name'],
            );
        }

        return $response;
    }


    public function toOptionArray()
    {
        return $this->getServiceTypes();
    }

    public function toValueArray()
    {
        $result = array();
        $options = $this->toOptionArray();
        foreach ($options as $option) {
            $result[] = $option['value'];
        }
        return $result;
    }
}