<?php
/**
 * Plugin Name: SB Payment Service for WooCommerce
 * Author URI: https://wc.artws.info/
 *
 * @class 		WooSBP
 * @extends		WC_Gateway_SBP_CS
 * @version		1.0.7
 * @package		WooCommerce/Classes/Payment
 * @author		Artisan Workshop
 */

use ArtisanWorkshop\WooCommerce\PluginFramework\v2_0_10 as Framework;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_Gateway_SBP_CS extends WC_Payment_Gateway {

    /**
     * Framework.
     *
     * @var object
     */
    public $jp4wc_framework;

    /**
     * Set debug
     *
     * @var string
     */
    public $debug;

    /**
	 * Constructor for the gateway.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		$this->id                = 'sbp_cs';
		$this->has_fields        = false;
		$this->order_button_text = __( 'Proceed to SBPS Convenience Store Payment.', 'woo-sbp' );

        // Create plugin fields and settings
		$this->init_form_fields();
		$this->init_settings();
		$this->method_title       = __( 'SBPS Convenience Store Payment Gateway', 'woo-sbp' );
		$this->method_description = __( 'Allows payments by SBPS Convenience Store in Japan.', 'woo-sbp' );

        // When no save setting error at chackout page
		if(is_null($this->title)){
			$this->title = __( 'Please set this payment at Control Panel! ', 'woo-sbp' ).$this->method_title;
		}
		// Get setting values
		foreach ( $this->settings as $key => $val ) $this->$key = $val;

        // Set Convenience Store
		$this->cs_stores = array();
		if(isset($this->setting_cs_se) and $this->setting_cs_se =='yes') $this->cs_stores = array_merge($this->cs_stores, array('001' => __( 'Seven Eleven', 'woo-sbp' )));
		if(isset($this->setting_cs_lm) and $this->setting_cs_lm =='yes') $this->cs_stores = array_merge($this->cs_stores, array('002' => __( 'Lawson', 'woo-sbp' )));
		if(isset($this->setting_cs_f) and $this->setting_cs_f =='yes') $this->cs_stores = array_merge($this->cs_stores, array('016' => __( 'Family Mart', 'woo-sbp' )));
		if(isset($this->setting_cs_sm) and $this->setting_cs_sm =='yes') $this->cs_stores = array_merge($this->cs_stores, array('018' => __( 'Seicomart', 'woo-sbp' )));
		if(isset($this->setting_cs_ms) and $this->setting_cs_ms =='yes') $this->cs_stores = array_merge($this->cs_stores, array('005' => __( 'Mini Stop', 'woo-sbp' )));

        // Description of the payment method at Convenience Store
        $this->description_cs_store = apply_filters('woo_sbp_description_cs_store', array(
            /* Seven Eleven */'001' => __( 'Please pay at the Seven-Eleven store.</br >Please present the printed "Payment slip" or "Payment slip number" at the cashier before making payment.', 'woo-sbp' ),
            /* lawson */'002' => __( 'Please apply at the Loppi terminal installed at Lawson/Ministop stores.</br>Please bring the "reception number" and "phone number" you made a note of, and then select the "Persons with various numbers" button from the top screen of the Loppi terminal to proceed with payment.', 'woo-sbp' ),
            /* Family Mart */'016' => __( 'Please complete the procedure at the Famiport terminal installed at the FamilyMart store.</br>Please bring down the "Company Code" and "Order Number" that you made a note of and then select the "Payment (pay at convenience store)" button on the top screen of the Famiport to proceed with the payment.', 'woo-sbp' ),
            /* Seicomart */'018' => __( 'Please say "Internet payment" at the cash register.</br>An operation screen will be displayed at the store cash register. Operate the touch panel and enter the "reception number" you made a note of to proceed with payment.', 'woo-sbp' ),
            /* Mini Stop */'005' => __( 'Please apply at the Loppi terminal installed at Lawson/Ministop stores.</br>Please bring the "reception number" and "phone number" you made a note of, and then select the "Persons with various numbers" button from the top screen of the Loppi terminal to proceed with payment.', 'woo-sbp' ),
        ));

		// Actions
		add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	    // Customize Emails
	    add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
	    add_action( 'woocommerce_thankyou_sbp_cs', array( $this, 'cs_detail' ), 10 );

        // Set framework
	    $this->jp4wc_framework = new Framework\JP4WC_Plugin();
	}
	/**
	* Initialize Gateway Settings Form Fields.
	*/
	function init_form_fields() {

		$this->form_fields = array(
			'enabled'     => array(
				'title'       => __( 'Enable/Disable', 'woo-sbp' ),
				'label'       => __( 'Enable SBPS Convenience Store Payment', 'woo-sbp' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no'
			),
			'title'       => array(
				'title'       => __( 'Title', 'woo-sbp' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woo-sbp' ),
				'default'     => __( 'Convenience Store (SBPS)', 'woo-sbp' )
			),
			'description' => array(
				'title'       => __( 'Description', 'woo-sbp' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'woo-sbp' ),
				'default'     => __( 'Pay with your Convenience Store via SBPS.', 'woo-sbp' )
			),
			'order_button_text' => array(
				'title'       => __( 'Order Button Text', 'woo-sbp' ),
				'type'        => 'text',
				'description' => __( 'This controls the description which the user sees during checkout.', 'woo-sbp' ),
				'default'     => __( 'Proceed to SBPS Convenience Store Payment', 'woo-sbp' )
			),
			'sandbox_mode' => array(
				'title'       => __( 'Sandbox Mode', 'woo-sbp' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable Sandbox Mode', 'woo-sbp' ),
				'desc_tip'    => true,
				'default'     => 'no',
				'description' => sprintf( __( 'If you use %s, please check it.', 'woo-sbp' ),__( 'Sandbox Mode', 'woo-sbp' )),
			),
			'setting_cs_se' => array(
				'title'       => __( 'Set Convenience Store', 'woo-sbp' ),
				'id'              => 'wc-gmopg-cs-se',
				'type'        => 'checkbox',
				'label'       => __( 'Seven Eleven', 'woo-sbp' ),
				'default'     => 'yes',
			),
			'setting_cs_lm' => array(
				'id'              => 'wc-gmopg-cs-lm',
				'type'        => 'checkbox',
				'label'       => __( 'Lawson', 'woo-sbp' ),
				'default'     => 'yes',
			),
			'setting_cs_f' => array(
				'id'              => 'wc-gmopg-cs-f',
				'type'        => 'checkbox',
				'label'       => __( 'Family Mart', 'woo-sbp' ),
				'default'     => 'yes',
			),
			'setting_cs_sm' => array(
				'id'              => 'wc-gmopg-cs-sm',
				'type'        => 'checkbox',
				'label'       => __( 'Seicomart', 'woo-sbp' ),
				'default'     => 'yes',
			),
			'setting_cs_ms' => array(
				'id'              => 'wc-gmopg-cs-ctd',
				'type'        => 'checkbox',
				'label'       => __( 'Mini Stop', 'woo-sbp' ),
				'default'     => 'yes',
			),
			'payment_limit_days'       => array(
				'title'       => __( 'Set Payment limit date', 'woo-sbp' ),
				'type'        => 'number',
				'description' => __( 'Payment limit days can be set between the next day (1 day) and 59 days.', 'woo-sbp' ),
				'default'     => 14
			),
			'payment_limit_description'       => array(
				'title'       => __( 'Explain Payment limit date', 'woo-sbp' ),
				'type'        => 'textarea',
				'description' => __( 'Explain Payment limite date in New order E-mail.', 'woo-sbp' ),
				'default'     => __( 'The payment deadline is 14 days from completed the order.', 'woo-sbp' )
			),
			'use_welnet'     => array(
				'title'       => __( 'Welnet Service', 'woo-sbp' ),
				'label'       => __( 'Use Welnet Service', 'woo-sbp' ),
				'type'        => 'checkbox',
				'description' => __( 'If you are applying after April 2019, please do not check here.', 'woo-sbp' ),
				'default'     => 'no'
			),
            'debug' => array(
                'title'   => __( 'Debug Mode', 'woo-sbp' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable Debug Mode', 'woo-sbp' ),
                'default' => 'no',
                'description' => __( 'Save debug data using WooCommerce logging.', 'woo-sbp' ),
            ),
		);
	}

	/**
	 * UI - Payment page fields for SB Payment Service.
	 */
	function payment_fields() {
		// Description of payment method from settings
		if ( $this->description ) { ?>
			<p><?php echo $this->description; ?></p>
		<?php } ?>
		<fieldset  style="padding-left: 40px;">
		<p><?php _e( 'Please select Convenience Store where you want to pay', 'woo-sbp' );?></p>
		<?php $this->cs_select(); ?>
		</fieldset>
		<?php
    }

	function cs_select() {
		?><select name="Convenience">
		<?php foreach($this->cs_stores as $num => $value){?>
			<option value="<?php echo $num; ?>"><?php echo $value;?></option>
		<?php }?>
		</select><?php 
	}

	/**
	 * Process the payment and return the result.
	 */
	function process_payment( $order_id ) {
		include_once( 'includes/class-wc-gateway-sbp-request.php' );

        if(version_compare( WC_VERSION, '3.6', '>=' )){
            $wc4jp_countries = new WC_Countries;
            $states = $wc4jp_countries->get_states();
        }else{
            global $states;
        }

		$order = wc_get_order( $order_id );
		$user = wp_get_current_user();
		if(0 != $user->ID){
			$customer_id   = $user->ID;
		}else{
			$customer_id   = $order_id.'-user';
		}
		$send_data = array();

		//Check test mode
		$sandbox_mode = $this->sandbox_mode;
		
		$data = array(
			'cust_code' => $customer_id,
			'order_id' => $order_id,
			'item_id' => 'woo-item',
			'amount' => $order->get_total(),
		);

		$sbp_request = new WC_Gateway_SBP_Request();
		date_default_timezone_set('Asia/Tokyo');

		//
		$data['pay_method_info']['issue_type'] = 0;
		$data['pay_method_info']['last_name'] = $order->get_billing_last_name();
		$data['pay_method_info']['first_name'] = $order->get_billing_first_name();
		$data['pay_method_info']['first_zip'] = substr($order->get_billing_postcode(),0,3);
		$data['pay_method_info']['second_zip'] = substr($order->get_billing_postcode(),4,4);
		$data['pay_method_info']['add1'] = $states['JP'][$order->get_billing_state()];
		$data['pay_method_info']['add2'] = $order->get_billing_city().$order->get_billing_address_1();
		$data['pay_method_info']['add3'] = $order->get_billing_address_2();
		$data['pay_method_info']['tel'] = $order->get_billing_phone();
		$data['pay_method_info']['mail'] = $order->get_billing_email();
		$data['pay_method_info']['seiyakudate'] = date('Ymd');
		$data['pay_method_info']['webcvstype'] = $this->get_post('Convenience');
		$data['pay_method_info']['bill_date'] = date('Ymd', strtotime('+'.$this->payment_limit_days.' day'));
		// 3DES encryption flag
		$data['encrypted_flg'] = 1;
		// Set Request time
		$data['request_date'] = date('YmdHis');
		$data['limit_second'] = 600;
		if((isset($this->use_welnet) and $this->use_welnet == 'yes')){
			$send_arrays['sps-api-request'] = 'ST01-00107-701';
            $data['pay_method_info']['bill_date'] = date('Ymd', strtotime('+'.$this->payment_limit_days.' day')).'2359';
		}else{
			$send_arrays['sps-api-request'] = 'ST01-00101-701';//Payment request
		}
		$send_arrays['data'] = $data;

//$order->add_order_note($sbp_request->make_hash($send_arrays, $sandbox_mode));
		$send_arrays['data']['sps_hashcode'] = sha1($sbp_request->make_hash($send_arrays, $sandbox_mode));

        //Save debug send data.
        $message = 'sps-api-request : ' . $send_arrays['sps-api-request'] . "\n" . $this->jp4wc_framework->jp4wc_array_to_message($send_arrays['data']) . 'This is send data.';
        $this->jp4wc_framework->jp4wc_debug_log( $message, $this->debug, 'woo-sbp', WC_SBP_VERSION, JP4WC_SBP_FRAMEWORK_VERSION);

		//Make XML Data
		$xml_data = $sbp_request->make_cs_xml($send_arrays, $sandbox_mode);
		//Send request to SBP API
		$xml_apply_array = $sbp_request->get_sbp_request( $xml_data , $sandbox_mode, $this->debug );

		if($xml_apply_array->res_result == 'OK'){// Successful settlement request (Payment Request)
			// Set Transaction ID
			$order->set_transaction_id(strval($xml_apply_array->res_sps_transaction_id));
			// Set 
			$invoice_no = $sbp_request->des_ede3_do_decrypt( $xml_apply_array->res_pay_method_info->invoice_no, $sandbox_mode );
			$cvs_pay_data1 = $sbp_request->des_ede3_do_decrypt( $xml_apply_array->res_pay_method_info->cvs_pay_data1, $sandbox_mode );
			$cvs_pay_data2 = $sbp_request->des_ede3_do_decrypt( $xml_apply_array->res_pay_method_info->cvs_pay_data2, $sandbox_mode );
			$order->update_meta_data('sbp_invoice_no', strval($invoice_no));
			$order->update_meta_data('sbp_cvs_pay_data1', strval($cvs_pay_data1));
			$order->update_meta_data('sbp_cvs_pay_data2', strval($cvs_pay_data2));
			$order->update_meta_data('sbp_cvs_id', strval($this->get_post('Convenience')));
			// Mark as on-hold (we're awaiting the payment)
			$order->update_status( 'on-hold', __( 'Awaiting Convenience store payment', 'woo-sbp' ) );

			// Reduce stock levels
            wc_reduce_stock_levels( $order_id );

			// Remove cart
			WC()->cart->empty_cart();

			// Return thank you redirect
			return array (
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			);
		}else{
			$order->add_order_note(sprintf( __( 'Error Code : %s, please check it.', 'woo-sbp' ),$xml_apply_array->res_err_code).' '.__( 'Failed settlement request', 'woo-sbp' ));
		}
	}

    /**
     * Check payment details for valid format
     */
	function validate_fields() {
	    //Phone validate
	    $billing_phone = $this->get_post('billing_phone');
	    if(!preg_match('/^([0-9]{9,11})$/', $billing_phone)){
            wc_add_notice( __( 'The phone number must be numeric and only 9 to 11 digits.', 'woo-sbp' ) , 'error' );
        }
        $ship_to_different_address = $this->get_post('ship_to_different_address');
	    $shipping_phone = $this->get_post('shipping_phone');
        if(isset($ship_to_different_address) and !preg_match('/^([0-9]{9,11})$/', $shipping_phone)){
            wc_add_notice( __( 'The phone number must be numeric and only 9 to 11 digits.', 'woo-sbp' ) , 'error' );
        }
        //Address validate
        $address_data['billing_first_name'] = $this->get_post('billing_first_name');
        $address_data['billing_last_name'] = $this->get_post('billing_last_name');
        $address_data['billing_city'] = $this->get_post('billing_city');
        $address_data['billing_address_1'] = $this->get_post('billing_address_1');
        $address_data['billing_address_2'] = $this->get_post('billing_address_2');
        if(isset($ship_to_different_address)){
            $address_data['shipping_first_name'] = $this->get_post('shipping_first_name');
            $address_data['shipping_last_name'] = $this->get_post('shipping_last_name');
            $address_data['shipping_city'] = $this->get_post('shipping_city');
            $address_data['shipping_address_1'] = $this->get_post('shipping_address_1');
            $address_data['shipping_address_2'] = $this->get_post('shipping_address_2');
        }

        $names = array(
            'billing_first_name' => __( 'Billing\'s ', 'woo-sbp' ).__( 'First name', 'woocommerce' ),
            'billing_last_name' => __( 'Billing\'s ', 'woo-sbp' ).__( 'Last name', 'woocommerce' ),
            'billing_city' => __( 'Billing\'s ', 'woo-sbp' ).__( 'City', 'woocommerce' ),
            'billing_address_1' => __( 'Billing\'s ', 'woo-sbp' ).__( 'Address', 'woocommerce' ),
            'billing_address_2' => __( 'Billing\'s ', 'woo-sbp' ).__( 'Address', 'woocommerce' ),
            'shipping_first_name' => __( 'Shipping\'s ', 'woo-sbp' ).__( 'First name', 'woocommerce' ),
            'shipping_last_name' => __( 'Shipping\'s ', 'woo-sbp' ).__( 'Last name', 'woocommerce' ),
            'shipping_city' => __( 'Shipping\'s ', 'woo-sbp' ).__( 'City', 'woocommerce' ),
            'shipping_address_1' => __( 'Shipping\'s ', 'woo-sbp' ).__( 'Address', 'woocommerce' ),
            'shipping_address_2' => __( 'Shipping\'s ', 'woo-sbp' ).__( 'Address', 'woocommerce' ),
        );

        foreach($address_data as $key => $value){
            if(preg_match("/[a-zA-Z0-9\-]/", $value)){
                $name = $names[$key];
                wc_add_notice( sprintf(__( '%s must be full-width characters.', 'woo-sbp' ), $name) , 'error' );
            }
            if(strpos($key,'name') !== false and mb_strlen( $value ) >= 10){
                wc_add_notice( sprintf(__( '%s must be less than 20 characters.', 'woo-sbp' ), $name) , 'error' );
            }elseif(strpos($key,'name') === false and mb_strlen($value) >= 50){
                wc_add_notice( sprintf(__( '%s must be less than 100 characters.', 'woo-sbp' ), $name) , 'error' );
            }
        }
	}

	/**
     * Add content to the WC emails For Convenient Infomation.
     *
     * @access public
     * @param WC_Order $order
     * @param bool $sent_to_admin
     * @param bool $plain_text
     * @return void
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ){
	    $payment_method = $order->get_payment_method();
    	if ( ! $sent_to_admin && 'sbp_cs' === $payment_method && 'on-hold' === $order->status ) {
			$order_id = $order->get_id();
			if ( $this->instructions ) {
				echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
			}
			$this->sbp_cs_details( $order_id );
		}
	}

	public function cs_detail( $order_id ){
		$this->sbp_cs_details( $order_id );
	}
	/**
	 * Get Convinience Store Payment details and place into a list format
	*/
	private function sbp_cs_details( $order_id = '' ) {
		$cvs_array = $this->cs_stores;
		$order = wc_get_order( $order_id );
		$csv_id = get_post_meta($order_id, 'sbp_cvs_id', true);
		$sbp_cvs_pay_data1 = get_post_meta($order_id, 'sbp_cvs_pay_data1', true);
		$sbp_cvs_pay_data2 = get_post_meta($order_id, 'sbp_cvs_pay_data2', true);
		echo '<h2>' . __( 'Convenience store payment details', 'woo-sbp' ) . '</h2>' . PHP_EOL;
		echo '<ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">';
		if($csv_id == '001'){// Seven Eleven
			if($this->use_welnet == 'yes'){
				echo '<li>' . __( 'Payment form URL', 'woo-sbp' ) . '<br /><strong>' . $sbp_cvs_pay_data1 . '</strong></li>' . PHP_EOL;
			}else{
				echo '<li>' . __( 'Payment slip number', 'woo-sbp' ) . '<br /><strong>' . $sbp_cvs_pay_data1 . '</strong></li>' . PHP_EOL;
				echo '<li>' . __( 'Payment form URL', 'woo-sbp' ) . '<br /><strong><a href="'. $sbp_cvs_pay_data2 .'" target="_blank">' . $sbp_cvs_pay_data2 . '</a></strong></li>' . PHP_EOL;
			}
		}elseif($csv_id == '002' or $csv_id == '005'){//Lawson & Mini Stop
			if($this->use_welnet == 'yes'){
				echo '<li>' . __( 'Customer Number', 'woo-sbp' ) . '<br /><strong>' . $sbp_cvs_pay_data1 . '</strong></li>' . PHP_EOL;
				echo '<li>' . __( 'Authorization number', 'woo-sbp' ) . '<br /><strong>' . $sbp_cvs_pay_data2 . '</strong></li>' . PHP_EOL;
			}else{
				if(empty($sbp_cvs_pay_data2) == false){
					echo '<li>' . __( 'Receipt number (8 digits) - Confirmation number (9 digits)', 'woo-sbp' ) . '<br /><strong>' . $sbp_cvs_pay_data1 . '</strong></li>' . PHP_EOL;
					echo '<li>' . __( 'Payment form URL', 'woo-sbp' ) . '<br /><strong><a href="'. $sbp_cvs_pay_data2 .'" target="_blank">' . $sbp_cvs_pay_data2 . '</a></strong></li>' . PHP_EOL;
				}else{
					echo '<li>' . __( 'Loppi reception number', 'woo-sbp' ) . '<br /><strong>' . $sbp_cvs_pay_data1 . '</strong></li>' . PHP_EOL;
				}
			}
		}elseif($csv_id == '016'){//Family Mart
			if($this->use_welnet == 'yes'){
				echo '<li>' . __( 'Authorization number', 'woo-sbp' ) . '<br /><strong>' . $sbp_cvs_pay_data1 . '</strong></li>' . PHP_EOL;
				echo '<li>' . __( 'Customer Number', 'woo-sbp' ) . '<br /><strong>' . $sbp_cvs_pay_data2 . '</strong></li>' . PHP_EOL;
			}else{
				echo '<li>' . __( 'Company code', 'woo-sbp' ) . '<br /><strong>' . $sbp_cvs_pay_data1 . '</strong></li>' . PHP_EOL;
				echo '<li>' . __( 'Order Number', 'woo-sbp' ) . '<br /><strong>' . $sbp_cvs_pay_data2 . '</strong></li>' . PHP_EOL;
			}
		}elseif($csv_id == '018'){//Seico Mart
			if($this->use_welnet == 'yes'){
				echo '<li>' . __( 'Online payment number', 'woo-sbp' ) . '<br /><strong>' . $sbp_cvs_pay_data1 . '</strong></li>' . PHP_EOL;
			}else{
				echo '<li>' . __( 'Receipt number', 'woo-sbp' ) . '<br /><strong>' . $sbp_cvs_pay_data1 . '</strong></li>' . PHP_EOL;
			}
		}
		if(isset($csv_id)){
		    $add_cvs_descripsion = apply_filters('woo_sbp_add_cvs_descripsion', __( '</br>Please note the payment deadline.</br>Please keep the receipt in a safe place.</br>If you have any questions regarding procedures at the store, please contact the store staff.</br>Payment is cash only. You cannot pay with a credit card.', 'woo-sbp' ) );
		    echo '<li>' . __( 'Payment method at store', 'woo-sbp' ) . '<br /><strong>' . $this->description_cs_store[$csv_id] . $add_cvs_descripsion .'</strong></li>';
        }
		echo '</ul>';
	}

	/**
	 * Process a refund if supported
	 * @param  int $order_id
	 * @param  float $amount
	 * @param  string $reason
	 * @return  boolean True or false based on success, or a WP_Error object
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = wc_get_order( $order_id );
	}

	/**
	 * Get post data if set
	 */
	private function get_post( $name ) {
		if ( isset( $_POST[ $name ] ) ) {
			return sanitize_text_field( $_POST[ $name ] );
		}
		return null;
	}
}

/**
 * Add the gateway to woocommerce
 */
function add_wc_sbp_cs_gateway( $methods ) {
	$methods[] = 'WC_Gateway_SBP_CS';
	return $methods;
}

add_filter( 'woocommerce_payment_gateways', 'add_wc_sbp_cs_gateway' );

/**
 * Edit the available gateway to woocommerce
 */
function edit_sbp_cs_available_gateways( $methods ) {
	if ( isset($currency) ) {
	}else{
		$currency = get_woocommerce_currency();
	}
	if($currency !='JPY'){
		unset($methods['sbp_cs']);
	}
	return $methods;
}

add_filter( 'woocommerce_available_payment_gateways', 'edit_sbp_cs_available_gateways' );
