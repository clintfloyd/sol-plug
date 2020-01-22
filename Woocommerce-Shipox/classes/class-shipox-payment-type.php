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

class Payment_Type
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'cash', 'label' => 'Cash'),
            array('value' => 'credit_balance', 'label' => 'Credit Balance')
//            array('value' => 'paypal', 'label' => 'Online Payment')
        );
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