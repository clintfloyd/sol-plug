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

class Status_Mapping
{
    public function statusList() {
        $array = array();

        $array['unassigned'] = 'New';
        $array['assigned_to_courier'] = 'Assigned To Courier';
        $array['accepted'] = 'Assigned To Driver';
        $array['driver_rejected'] = 'Assigned To Driver';
        $array['on_his_way'] = 'Driver On Pickup';
        $array['arrived'] = 'Parcel in Sorting Facility';
        $array['picked_up'] = 'Parcel Picked Up';
        $array['pick_up_failed'] = 'Pick up Failed';
        $array['in_sorting_facility'] = 'Parcel in Sorting Facility';
        $array['out_for_delivery'] = 'Parcel out For Delivery';
        $array['in_transit'] = 'In Transit';
        $array['to_be_returned'] = 'To Be Returned';
        $array['completed'] = 'Parcel Delivered';
        $array['delivery_failed'] = 'Delivery Failed';
        $array['cancelled'] = 'Delivery Cancelled';
        $array['driver_cancelled'] = 'Driver Cancelled';
        $array['returned_to_origin'] = 'river Cancelled';

        return $array;
    }
}