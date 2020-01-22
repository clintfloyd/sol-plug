<?php
/**
 * Plugin Name: Fetchr Shipping
 * Plugin URI: http://fetchr.us
 * Description: Fetchr Shipping Plugin is responsible to build connection between Fetchr Shipping system and WooCommerce store.
 * Version: X1.0
 * Author: Fetchr
 * Author URI: http://www.fetchr.us
 */

/*
* 2018 Fetchr
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@fetchr.us so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade Fetchr Shipping to newer
* versions in the future. If you wish to customize Fetchr Shipping for your
* needs please refer to http://www.fetchr.us for more information.
*
*  @author Fetchr <integration@fetchr.us>
*  @author Danish Kamal <d.kamal@fetchr.us>
*  @copyright  2018 Fetchr
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  Fetchr.us
*/

// Report all errors except E_NOTICE
// error_reporting(E_ALL ^ E_NOTICE);


if ( !function_exists( 'add_action' ) ) {
    echo 'You can not load plugin files directly';
    exit;
}
define( 'Fetchrxapi_Plugin_Dir', plugin_dir_path( __FILE__ ) );
register_activation_hook( __FILE__, array( 'fetchr', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'fetchr', 'plugin_deactivation' ) );

require_once( Fetchrxapi_Plugin_Dir . 'fetchr.config.php' );
require_once( Fetchrxapi_Plugin_Dir . 'function.fetchr.php' );

add_action( 'admin_menu', 'fetchrxapi_plugin_setup_menu');

add_action( 'admin_enqueue_scripts', 'fetchrxapi_enqueue_styles' );

add_action('admin_head', 'wc_order_status_styling');

add_action('admin_footer-edit.php', 'custom_bulk_admin_footer');

add_filter( 'woocommerce_admin_order_actions', 'add_fetchr_ship_actions_button', PHP_INT_MAX, 2 );

add_action( 'wp', 'setup_schedule_event' );

add_action( 'init', 'register_fetchrxapi_order_status' );

add_filter( 'wc_order_statuses', 'add_fetchrxapi_processing_to_order_statuses' );

function fetchrxapi_setting_page() {
    ?>
    <div class="fetchrxapi_modal">
      <div class="fetchrxapi_modal__container">
        <div class="fetchrxapi_modal__featured">
          <div class="fetchrxapi_modal__circle"></div>
          <img class="fetchrxapi_modal__logo"/>
        </div>
        <div class="fetchrxapi_modal__content">
          <h2><?php echo esc_attr(Fetchrxapi_Plugin_Settings)?></h2>
            <ul class="fetchrxapi_form-list">
              <form method="post" action="options.php">
                <?php settings_fields( 'fetchrxapi-settings-group' ); ?>
                <?php do_settings_sections( 'fetchrxapi-settings-group' ); ?>
              <li class="fetchrxapi_form-list__row">
                <label>Authorization Token</label>
                <input class="fetchrxapi_input" type="text" name="fetchrxapi_authorization_token" value="<?php echo esc_attr( get_option('fetchrxapi_authorization_token') ); ?>" required="" />
              </li>
              <li class="fetchrxapi_form-list__row">
                <label>Address ID</label>
                <input class="fetchrxapi_input" type="text" name="fetchrxapi_address_id" value="<?php echo esc_attr( get_option('fetchrxapi_address_id') ); ?>" required="" />
              </li>
              <li class="fetchrxapi_form-list__row">
                <label>Service Type</label>
                <select name="fetchrxapi_service_type" class="fetchrxapi_input" >
                    <option value="dropship" <?php if (get_option('fetchrxapi_service_type') == "dropship"): ?> selected="selected" <?php endif; ?>>Dropship</option>
                    <option value="fulfillment" <?php if (get_option('fetchrxapi_service_type') == "fulfillment"): ?> selected="selected" <?php endif; ?>> Fulfillment</option>
                </select>
              </li>
              <li class="fetchrxapi_form-list__row">
                <label>Environment Type</label>
                <select name="fetchrxapi_service_environment" class="fetchrxapi_input">
                    <option value="sandbox" <?php if (get_option('fetchrxapi_service_environment') == "sandbox"): ?> selected="selected" <?php endif; ?>>Sandbox</option>
                    <option value="production" <?php if (get_option('fetchrxapi_service_environment') == "production"): ?> selected="selected" <?php endif; ?>> Production</option>
                </select>
              </li>
              <li class="fetchrxapi_form-list__row">
                <label>Auto Push?</label>
                <select name="fetchrxapi_is_auto_push" class="fetchrxapi_input">
                    <option value="1" <?php if (get_option('fetchrxapi_is_auto_push') == "1"): ?> selected="selected" <?php endif; ?>> Yes</option>
                    <option value="0" <?php if (get_option('fetchrxapi_is_auto_push') == "0"): ?> selected="selected" <?php endif; ?>> No</option>
                </select>
              </li>
              <li>
                <div class="fetchrxapi_button_clearboth"><input type="submit" name="save_settings" id="save_settings" class="fetchrxapi_button" value="Save Settings"></div>
                </form>
                <form method="post" action="">
                <div><input type="submit" name="push_orders" id="push_orders" class="fetchrxapi_button" value="Push Orders"></div>
                </form>
              </li>
            </ul>
        </div>
      </div>
    </div>
    <?php

if (isset($_POST['push_orders']) ){
   hit_fetchrxapi_api();
}
}

function hit_fetchrxapi_api()
{
  switch (get_option( 'fetchrxapi_service_environment' )) {
    case 'sandbox':
        $environment = Fetchrxapi_Sandbox_Environment;
        break;
    case 'production':
        $environment = Fetchrxapi_Production_Environment;
        break;
      }
    if ( get_option("fetchrxapi_fetch_status") )
    {
        $where = array( get_option("fetchrxapi_fetch_status") );
    }
    else
    {
        // $where = array("wc-processing");

      if (get_option( 'fetchrxapi_is_auto_push') == "1" ){
        $where = array( "wc-processing" );
      }
      if (isset($_POST['push_orders'])) {
        $where = array( "wc-ship-with-fetchr" );
      }
        //$where = array_keys( wc_get_order_statuses() );
    }
    $orders = get_posts( array(
              'numberposts'       => -1,
            'post_type'   => 'shop_order',
            'post_status' => $where
        )
    );
    // var_dump($orders);
    // exit;
    foreach ($orders as $order)
    {
        $shipping_country = get_post_meta($order->ID,'_shipping_country',true);
        $order_wc = new WC_Order( $order->ID );

        $products = $order_wc->get_items();

        if( get_option( 'fetchrxapi_service_type') == "fulfillment" )
        {
            // fulfillment service
            fetchrxapi_fulfillment ($order,$order_wc,$products,$environment);
        }
        else
        {
            // delivery service
            fetchrxapi_delivery ($order,$order_wc,$products,$environment);
        }
    }
}

function fetchrxapi_delivery ($order,$order_wc,$products,$environment)
{
$description = '';
$weight = 0;
foreach ($products as $product) {
  $description = $description . $product['name'].' - Qty: '.$product['qty'].', ';
    $getProductDetail = wc_get_product( $product['product_id'] );
    $weight += $getProductDetail->get_weight();
}
  $order_id = (string)$order_wc->get_order_number();

        if($order_wc->payment_method == "cod"){
            $payment_method = "COD";
            $grand_total = $order_wc->get_total();
        }else{
            $payment_method = "CC";
            $grand_total = $order_wc->get_total();
        }

    $data = array(
        'client_address_id' => get_option('fetchrxapi_address_id'),
        'data' => array(array(
            'order_reference' => $order_id,
            'name' => $order_wc->shipping_first_name.' '.$order_wc->shipping_last_name,
            'email' => $order_wc->billing_email,
            'phone_number' => $order_wc->billing_phone,
            'alternate_phone' => '',
            'receiver_country' => WC()->countries->countries[$order_wc->shipping_country],
            'receiver_city' => $order_wc->shipping_city,
            'address' => $order_wc->shipping_company.' '.$order_wc->shipping_address_1.' '.$order_wc->shipping_address_2,
            'payment_type' => $payment_method,
            'total_amount' => $grand_total,
            'order_package_type' => '',
            'bag_count' => '',
            'weight' => $weight,
            'description' => $description,
            'comments' => $order_wc->customer_message.' '.$order_wc->customer_note,
            'latitude' => '',
            'longitude' => '')));

    $url = $environment.'order/';

    $data_string = json_encode($data, JSON_UNESCAPED_UNICODE);
        $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $headers[] = 'Authorization: '.get_option('fetchrxapi_authorization_token');
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'xcaller: WordPress X1.0';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    $results = curl_exec($ch);
        curl_close ($ch);
        ?>
        <div class="fetchrxapi_response"><?php print_r($results);?></div>
<?php
        $results = json_decode($results);
    if ($results->data['0']->status == "success")
    {
        // Change Status Here to Processing
        $order_wc->update_status( 'wc-fetchr-processing' );
        // Create/update a custom field Airway bill number and Airway bill link

        if ( ! update_post_meta ($order->ID, 'Fetchr Tracking No', $results->data['0']->tracking_no ))
        {
            add_post_meta($order->ID, 'Fetchr Tracking No', $results->data['0']->tracking_no, true );
        }
                if ( ! update_post_meta ($order->ID, 'Fetchr AWB Link', $results->data['0']->awb_link ))
        {
            add_post_meta($order->ID, 'Fetchr AWB Link', $results->data['0']->awb_link, true );
        }
    }
}

function fetchrxapi_fulfillment ($order,$order_wc,$products,$environment)
{
  $order_id = (string)$order_wc->get_order_number();

    $item_list  = array ();

    foreach ($products as $product)
    {
        if($product['variation_id'] != 0){
            $product_obj = new WC_Product($product['variation_id']);
        }else{
            $product_obj = new WC_Product($product['product_id']);
        }
        $sku = $product_obj->get_sku();
        $n_product = array (
            'name'                    => $product['name'],
            'sku'                     => $sku,
            'quantity'            => $product['qty'],
            'price_per_unit'        => $product_obj->price,
                        'processing_fee'        => '',
        );
        array_push($item_list,$n_product);

    }

    if($order_wc->payment_method == "cod"){
        $payment_method = "COD";
        $grand_total = $order_wc->get_total();
    }else{
        $payment_method = "CC";
        $grand_total = $order_wc->get_total();
    }
    $datalist = array('data' => array(array(
        'items' => $item_list,
                'warehouse_location' => array(
                    'id' => get_option('fetchrxapi_address_id')
                ),
        'details' => array(
            'discount'                  => $order_wc->get_total_discount(),
                        'extra_fee'                     => $order_wc->get_total_shipping(),
            'payment_type'          => $payment_method,
            'order_reference'       => $order_id,
            'customer_name'             => $order_wc->shipping_first_name.' '.$order_wc->shipping_last_name,
            'customer_phone'          => $order_wc->billing_phone,
            'customer_email'          => $order_wc->billing_email,
                        'customer_address'      => $order_wc->shipping_company.' '.$order_wc->shipping_address_1.' '.$order_wc->shipping_address_2,
                        'customer_latitude'     => '',
                'customer_longitude'    => '',
            'customer_city'           => $order_wc->shipping_city,
            'customer_country'      => WC()->countries->countries[$order_wc->shipping_country]
        )
    )));
    $data_string        = json_encode($datalist, JSON_UNESCAPED_UNICODE);
        //var_dump($data_string);
        $url = $environment."fulfillment/";
        $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $headers[] = 'Authorization: '.get_option('fetchrxapi_authorization_token');
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'xcaller: WordPress X1.0';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    $results = curl_exec($ch);
        curl_close ($ch);
        ?>
        <div class="fetchrxapi_response"><?php print_r($results);?></div>
        <?php
        $results = json_decode($results);
    if ($results->data['0']->status == "success")
    {
        // Change Status Here to Processing
        $order_wc->update_status( 'wc-fetchr-processing' );

        if ( ! update_post_meta ($order_id, 'Fetchr Fulfillment Tracking No', $results->data['0']->tracking_no ))
        {
            add_post_meta($order_id, 'Fetchr Fulfillment Tracking No', $results->data['0']->tracking_no, true );
        }

    }

} // END of function
