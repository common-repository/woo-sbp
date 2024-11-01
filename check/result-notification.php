<?php
/**
 * Plugin Name: SB Payment Service for WooCommerce
 * Author URI: https://wc.artws.info/
 *
 * @class 		WooSBP
 * @version		1.0.9
 * @author		Artisan Workshop
 */
require( '../../../../wp-blog-header.php' );
global $wpdb;
global $woocommerce;
if(isset($_POST)){
	$result = 'NG';
	$post_data = file_get_contents('php://input');
	$xml_array = simplexml_load_string( $post_data );
	$sps_api_request = (string) $xml_array["id"];
	$transaction_id = (string) $xml_array->sps_transaction_id;

	$args = array(
		'meta_key' => '_transaction_id',
		'meta_value' => $transaction_id,
		'post_type' => 'shop_order',
		'post_status' => 'wc-on-hold'
	);

	//Debug data
	$sbps_cs = new WC_Gateway_SBP_CS();
	$sandbox_mode = $sbps_cs->sandbox_mode;
	if($sandbox_mode == 'yes'){
		$logger = wc_get_logger();
		$logger->debug( $post_data, array( 'source' => 'woo-sbp' ) );
	}

	$orders = get_posts( $args );
	if(isset($orders[0]->ID)){
		$order = wc_get_order($orders[0]->ID);
		if($order){
			if($sps_api_request == 'NT01-00103-701'){//Payment notice
				$order->update_status('processing');
				$result = 'OK';
			}elseif($sps_api_request == 'NT01-00104-701'){//Payment expired cancellation notice
				$order->update_status('cancelled');
				$result = 'OK';
			}
		}
	}
	echo '<?xml version="1.0" encoding="Shift_JIS"?>
<sps-api-response id="NT01-00103-701">
<res_result>'.$result.'</res_result>
<res_err_msg></res_err_msg>
</sps-api-response>';
}
?>