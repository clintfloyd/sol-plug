<?php
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
function fetchrxapi_plugin_setup_menu(){
    add_menu_page( Fetchrxapi_Plugin_Title, Fetchrxapi_Menu_Title, 'manage_options', 'fetchr-plugin', 'fetchrxapi_setting_page' );
    add_action( 'admin_init', 'register_setting_options' );
}

function fetchrxapi_enqueue_styles(){
    wp_enqueue_style( 'fetchrstylesheet', plugins_url( '/css/fetchrstyle.css',__FILE__ ));
}

function wc_order_status_styling() {
}

function custom_bulk_admin_footer() {
    global $post_type;
    if($post_type == 'shop_order') {
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function() {
            jQuery('<option>').val('mark_ship-with-fetchr').text('<?php _e('Mark Fetchr Ship')?>').appendTo("select[name='action']");
        });
    </script>
    <?php
    }
  }

	function add_fetchr_ship_actions_button( $actions, $the_order ) {
    if ( $the_order->has_status( array( 'processing' ) ) ) { // if order is not cancelled yet...
          $actions['ship-with-fetchr'] = array(
              'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=ship-with-fetchr&order_id=' . $the_order->id ), 'woocommerce-mark-order-status' ),
              'name'      => __( 'Ship with Fetchr', 'woocommerce' ),
              'action'    => "view ship-with-fetchr", // setting "view" for proper button CSS
          );
        }
      return $actions;
    }

		function setup_schedule_event()
		{
				if ( ! wp_next_scheduled( 'prefix_hourly_event' ) )
				{
						wp_schedule_event( time(), 'hourly', 'prefix_hourly_event' );
				}
		}

		if (get_option( 'fetchrxapi_is_auto_push') == "1" ){
			add_action( 'prefix_hourly_event', 'hit_fetchrxapi_api' );
		 }

		 function register_fetchrxapi_order_status()
		 {
				 register_post_status( 'wc-fetchr-processing', array(
								 'label'                     => 'Fetchr Processing',
								 'public'                    => true,
								 'exclude_from_search'       => false,
								 'show_in_admin_all_list'    => true,
								 'show_in_admin_status_list' => true,
								 'label_count'               => _n_noop( 'Fetchr Processing <span class="count">(%s)</span>', 'Fetchr Processing <span class="count">(%s)</span>' )
						 )
				 );
				 register_post_status( 		'wc-ship-with-fetchr', array(
								 'label'                     => 'Ship with Fetchr',
								 'public'                    => true,
								 'exclude_from_search'       => false,
								 'show_in_admin_all_list'    => true,
								 'show_in_admin_status_list' => true,
								 'label_count'               => _n_noop( 'Ship with Fetchr <span class="count">(%s)</span>', 'Ship with Fetchr <span class="count">(%s)</span>' )
						 )
				 );
		 }

		 function add_fetchrxapi_processing_to_order_statuses( $order_statuses ) {
		 		$new_order_statuses = array();
		 		// add new order status after processing
		 		foreach ( $order_statuses as $key => $status ) {
		 				$new_order_statuses[ $key ] = $status;
		 				if ( 'wc-processing' === $key )
		 				{
		 						$new_order_statuses['wc-fetchr-processing'] = 'Fetchr Processing';
		 						$new_order_statuses['wc-ship-with-fetchr'] = 'Ship with Fetchr';
		 				}
		 		}
		 		return $new_order_statuses;
		 }

		 function register_setting_options(){
		 		register_setting( 'fetchrxapi-settings-group', 'fetchrxapi_authorization_token' );
		 		register_setting( 'fetchrxapi-settings-group', 'fetchrxapi_address_id' );
		 		register_setting( 'fetchrxapi-settings-group', 'fetchrxapi_fetch_status' );
		 		register_setting( 'fetchrxapi-settings-group', 'fetchrxapi_service_type' );
		 		register_setting( 'fetchrxapi-settings-group', 'fetchrxapi_service_environment' );
		 		register_setting( 'fetchrxapi-settings-group', 'fetchrxapi_is_auto_push' );
		 }
