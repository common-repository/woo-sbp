<?php
/**
 * Plugin Name: SB Payment Service for WooCommerce
 * Author URI: https://wc.artws.info/
 *
 * @class 		WooSBP
 * @extends		WC_Gateway_SBP_CC
 * @version		1.0.10
 * @package		WooCommerce/Classes/Payment
 * @author		Artisan Workshop
 */

use ArtisanWorkshop\WooCommerce\PluginFramework\v2_0_10 as Framework;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_Gateway_SBP_CC extends WC_Payment_Gateway {

    /**
     * Credit Cards dealing types
     *
     * @var array
     */
    public $dealings_types = array();

    /**
     * Credit Cards dealing type
     *
     * @var string
     */
    public $dealings_type;

    /**
     * Credit Cards dinners setting
     *
     * @var string
     */
    public $setting_card_d = null;

    /**
     * Credit Cards amex and jcb setting
     *
     * @var string
     */
    public $setting_card_aj = null;

    /**
     * Sandbox mode setting
     *
     * @var string
     */
    public $sandbox_mode;

    /**
     * Framework.
     *
     * @var object
     */
    public $jp4wc_framework;

    /**
     * SB Payment request class.
     *
     * @var object
     */
    public $sbp_request;

    /**
	 * Constructor for the gateway.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		$this->id                = 'sbp_cc';
		$this->has_fields        = false;
		$this->order_button_text = __( 'Proceed to SBPS Credit Card', 'woo-sbp' );

        // Create plugin fields and settings
		$this->init_form_fields();
		$this->init_settings();
		$this->method_title       = __( 'SBPS Credit Card Payment Gateway', 'woo-sbp' );
		$this->method_description = __( 'Allows payments by SBPS Credit Card in Japan.', 'woo-sbp' );
		$this->supports = array(
			'subscriptions',
			'products',
			'subscription_cancellation',
			'subscription_reactivation',
			'subscription_suspension',
			'subscription_amount_changes',
			'subscription_payment_method_change_customer',
			'subscription_payment_method_change_admin',
			'subscription_date_changes',
			'multiple_subscriptions',
			'tokenization',
			'refunds',
			'default_credit_card_form'
		);
		// Set the number of payments by credit card
		$this->dealings_types = array(
			'10'	=> __( '1 time payment', 'woo-sbp' ),
			'61'	=> __( 'Installment payment', 'woo-sbp' ),
			'21'	=> __( 'Bonus One time', 'woo-sbp' ),
			'80'	=> __( 'Revolving payment', 'woo-sbp' ),
		);
        // When no save setting error at chackout page
		if(is_null($this->title)){
			$this->title = __( 'Please set this payment at Control Panel! ', 'woo-sbp' ).$this->method_title;
		}
		// Get setting values
		foreach ( $this->settings as $key => $val ) $this->$key = $val;

		// Load plugin checkout credit Card icon
		if(isset($this->setting_card_vm)){
			if($this->setting_card_vm =='yes' and $this->setting_card_d =='yes' and $this->setting_card_aj =='yes'){
				$this->icon = plugins_url( 'images/sbp-cards.png' , __FILE__ );
			}elseif($this->setting_card_vm =='yes' and $this->setting_card_d =='no' and $this->setting_card_aj =='no'){
				$this->icon = plugins_url( 'images/sbp-cards-v-m.png' , __FILE__ );
			}elseif($this->setting_card_vm =='yes' and $this->setting_card_d =='yes' and $this->setting_card_aj =='no'){
				$this->icon = plugins_url( 'images/sbp-cards-v-m-d.png' , __FILE__ );
			}elseif($this->setting_card_vm =='yes' and $this->setting_card_d =='no' and $this->setting_card_aj =='yes'){
				$this->icon = plugins_url( 'images/sbp-cards-v-m-a-j.png' , __FILE__ );
			}elseif($this->setting_card_vm =='no' and $this->setting_card_d =='no' and $this->setting_card_aj =='yes'){
				$this->icon = plugins_url( 'images/sbp-cards-a-j.png' , __FILE__ );
			}elseif($this->setting_card_vm =='no' and $this->setting_card_d =='yes' and $this->setting_card_aj =='no'){
				$this->icon = plugins_url( 'images/sbp-cards-d.png' , __FILE__ );
			}elseif($this->setting_card_vm =='no' and $this->setting_card_d =='yes' and $this->setting_card_aj =='yes'){
				$this->icon = plugins_url( 'images/sbp-cards-d-a-j.png' , __FILE__ );
			}
		}

		// Actions
		add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'sbp_token_scripts_method' ) );
		add_action( 'woocommerce_order_status_completed', array( $this, 'order_status_completed_sbp_cc' ));

        //
		$this->jp4wc_framework = new Framework\JP4WC_Plugin();
		//
        include_once( 'includes/class-wc-gateway-sbp-request.php' );
        $this->sbp_request = new WC_Gateway_SBP_Request();
	}
	/**
	* Initialize Gateway Settings Form Fields.
	*/
	function init_form_fields() {
		$this->form_fields = array(
			'enabled'     => array(
				'title'       => __( 'Enable/Disable', 'woo-sbp' ),
				'label'       => __( 'Enable SBPS Credit Card Payment', 'woo-sbp' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no'
			),
			'title'       => array(
				'title'       => __( 'Title', 'woo-sbp' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woo-sbp' ),
				'default'     => __( 'Credit Card (SBPS)', 'woo-sbp' )
			),
			'description' => array(
				'title'       => __( 'Description', 'woo-sbp' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'woo-sbp' ),
				'default'     => __( 'Pay with your credit card via SBPS.', 'woo-sbp' )
			),
			'order_button_text' => array(
				'title'       => __( 'Order Button Text', 'woo-sbp' ),
				'type'        => 'text',
				'description' => __( 'This controls the description which the user sees during checkout.', 'woo-sbp' ),
				'default'     => __( 'Proceed to SBPS Credit Card', 'woo-sbp' )
			),
			'sandbox_mode' => array(
				'title'       => __( 'Sandbox Mode', 'woo-sbp' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable Sandbox Mode', 'woo-sbp' ),
				'desc_tip'    => true,
				'default'     => 'no',
				'description' => sprintf( __( 'If you use %s, please check it.', 'woo-sbp' ),__( 'Sandbox Mode', 'woo-sbp' )),
			),
			'store_card_info' => array(
				'title'       => __( 'Store Card Infomation', 'woo-sbp' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable Store Card Infomation', 'woo-sbp' ),
				'default'     => 'no',
				'description' => __( 'Store user Credit Card information in SB Payment Service.(Option)', 'woo-sbp' ),
			),
			'setting_card_vm' => array(
				'title'       => __( 'Set Credit Card', 'woo-sbp' ),
				'id'              => 'wc-sbp-cc-vm',
				'type'        => 'checkbox',
				'label'       => __( 'VISA & MASTER', 'woo-sbp' ),
				'default'     => 'yes',
			),
			'setting_card_aj' => array(
				'id'              => 'wc-sbp-cc-aj',
				'type'        => 'checkbox',
				'label'       => __( 'AMEX & JCB', 'woo-sbp' ),
				'default'     => 'yes',
				'description' => __( 'Please check them you are able to use Credit Card', 'woo-sbp' ),
			),
			'setting_card_d' => array(
				'id'              => 'wc-sbp-cc-d',
				'type'        => 'checkbox',
				'label'       => __( 'DINNERS', 'woo-sbp' ),
				'default'     => 'yes',
			),
			'payment_action' => array(
				'title'       => __( 'Payment Action', 'woo-sbp' ),
				'type'        => 'select',
				'class'       => 'wc-enhanced-select',
				'description' => __( 'Choose whether you wish to capture funds immediately or authorize payment only.', 'woocommerce' ),
				'default'     => 'sale',
				'desc_tip'    => true,
				'options'     => array(
					'sale'          => __( 'Capture', 'woocommerce' ),
					'authorization' => __( 'Authorize', 'woocommerce' )
				)
			),
			'dealings_type' => array(
				'title'       => __( 'Payment Method', 'woo-sbp' ),
				'type'        => 'multiselect',
				'class'       => 'wc-multi-select',
				'description' => __( 'Choose whether you wish to capture funds immediately or authorize payment only.', 'woo-sbp' ),
				'options'     => array(
					'10'	=> __( '1 time payment', 'woo-sbp' ),
					'61'	=> __( 'Installment payment', 'woo-sbp' ),
					'21'	=> __( 'Bonus One time', 'woo-sbp' ),
					'80'	=> __( 'Revolving payment', 'woo-sbp' ),
				)
			),
			'divide_times' => array(
				'title'       => __( 'Number of payments', 'woo-sbp' ),
				'type'        => 'multiselect',
				'class'       => 'wc-multi-select',
				'description' => __( 'Please select from here if you choose installment payment. (Multiple selection possible).', 'woo-sbp' ),
				'desc_tip'    => true,
				'options'     => array(
					'3'		=> '3'.__( 'times', 'woo-sbp' ),
					'5'		=> '5'.__( 'times', 'woo-sbp' ),
					'6'		=> '6'.__( 'times', 'woo-sbp' ),
					'10'	=> '10'.__( 'times', 'woo-sbp' ),
					'12'	=> '12'.__( 'times', 'woo-sbp' ),
					'15'	=> '15'.__( 'times', 'woo-sbp' ),
					'18'	=> '18'.__( 'times', 'woo-sbp' ),
					'20'	=> '20'.__( 'times', 'woo-sbp' ),
					'24'	=> '24'.__( 'times', 'woo-sbp' ),
				)
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
      	<?php }
		$customer_id = get_current_user_id();
        $cc_num = '';
        if(get_current_user_id() != 0){
            $cc_num = $this->cc_ref( $customer_id );
        }
        if(!empty($cc_num)):
		?>
        <!-- Show set stored Credit Card data -->
        <fieldset>
            <input type="radio" name="sbp-use-stored-payment-info" id="sbp-use-stored-payment-info-yes" value="yes" checked="checked" onclick="document.getElementById('sbp-new-info').style.display='none'; document.getElementById('sbp-stored-info').style.display='block'"; /><?php echo __( 'Pay with a previously paid credit card.', 'woo-sbp' );?>
            <div id="sbp-stored-info">
                <fieldset>
                    <?php echo __( 'Stored card number : ', 'woo-sbp' ).$cc_num;?>
                </fieldset>
            </div>
        </fieldset>
        <fieldset>
            <input type="radio" name="sbp-use-stored-payment-info" id="sbp-use-stored-payment-info-no" value="no" onclick="document.getElementById('sbp-stored-info').style.display='none'; document.getElementById('sbp-new-info').style.display='block'"; />
            <label for="sbp-use-stored-payment-info-no"  style="display: inline;"><?php _e( 'Use a new payment method', 'woo-sbp' ) ?></label>
        </fieldset>
<?php endif; ?>
        <!-- Show input boxes for new data --><?php if(empty($cc_num)): ?>
        <div id="sbp-new-info">
<?php else: ?>
        <div id="sbp-new-info" style="display:none">
        <?PHP endif;
		$payment_gateway_cc = new WC_Payment_Gateway_CC();
		$payment_gateway_cc->id = $this->id;
		$payment_gateway_cc->form();

		echo '</div>';
		if($this->dealings_type){
			$payment_method = $this->dealings_type;
		}else{
			$payment_method = null;
		}
		if(!is_null($payment_method) and $payment_method != array ( 0 => 10 )){
			echo '<fieldset style="padding-left: 40px;">'.__( 'Payment method : ', 'woo-sbp' ).'<select name="divide_times">';
			$installment_payment = false;
			$divide_times = $this->divide_times;
			$payment_method_name = $this->dealings_types;
			foreach($this->dealings_type as $key => $value){
				if($value == 61){
					$installment_payment = true;
				}else{
					echo '<option value="'.$value.'">'.$payment_method_name[$value].'</option>';
				}
			}
			if($installment_payment){
				foreach($divide_times as $key => $value){
					echo '<option value="'.$value.'">'.$value.__( 'times', 'woo-sbp' ).'</option>';
				}
			}
			echo '</select></fieldset>';
		}

		$wc_sbp_options = get_option('wc_sbp_options');
		$merchant_id = $wc_sbp_options['mid'];
		$service_id = $wc_sbp_options['sid'];
		?>
<script type="text/javascript">
document.getElementById("sbp_cc-card-cvc").addEventListener("input", doSubmit);
function doSubmit(){
// Token generation logic call
	var cr = document.getElementById('sbp_cc-card-number').value ;
	cr = cr.replace(/ /g, '');
	var cs = document.getElementById('sbp_cc-card-cvc').value ;
	var exp_my = document.getElementById('sbp_cc-card-expiry').value ;
	exp_my = exp_my.replace(/ /g, '');
	exp_my = exp_my.replace('/', '');
	var exp_m = exp_my.substr(0,2);
	var exp_y = exp_my.substr(2).substr(-2);
	var ce = '20'+exp_y+exp_m;
	com_sbps_system.generateToken({
		merchantId : "<?php echo $merchant_id;?>",
		serviceId : "<?php echo $service_id;?>",
		ccNumber : cr,
		ccExpiration : ce,
		securityCode : cs
	}, afterGenerateToken);
}
var afterGenerateToken = function(response) {
	if (response.result == "OK") {
		document.getElementById('token').value = response.tokenResponse.token;
		document.getElementById('tokenKey').value = response.tokenResponse.tokenKey;
		document.getElementById('cardBrandCode').value = response.tokenResponse.cardBrandCode;
	} else {
//		document.getElementById('token').value = response.errorCode;
//		alert('トークン取得に失敗しました。');
	}
}
jQuery(function(){
	jQuery('#place_order').focus(function (){
		jQuery('#sbp_cc-card-number').prop("disabled", true);
		jQuery('#sbp_cc-card-expiry').prop("disabled", true);
		jQuery('#sbp_cc-card-cvc').prop("disabled", true);
	});
	jQuery('#place_order').blur(function (){
		jQuery('#sbp_cc-card-number').prop("disabled", false);
		jQuery('#sbp_cc-card-expiry').prop("disabled", false);
		jQuery('#sbp_cc-card-cvc').prop("disabled", false);
	});
	jQuery('#place_order').click(function (){
        jQuery('#sbp_cc-card-number').val('');
        jQuery('#sbp_cc-card-expiry').val('');
	});
    jQuery("input[name='sbp-use-stored-payment-info']:radio").change( function(){
        jQuery('#sbp_cc-card-number').val('');
        jQuery('#sbp_cc-card-expiry').val('');
        jQuery('#sbp_cc-card-cvc').val('');
        jQuery('#token').val('');
        jQuery('#tokenKey').val('');
    });
});
</script>
<input type="hidden" id="token" name="token" value="">
<input type="hidden" id="tokenKey" name="tokenKey" value="">
<input type="hidden" id="cardBrandCode" name="cardBrandCode" value="">
	<?php 
	}

    /**
     * Credit card information reference request
     *
     * @param  string Customer ID
     * @return mixed
     */
    public function cc_ref( $customer_id ){
        //Check test mode
        $sandbox_mode = $this->sandbox_mode;
        // Set check stored card information
        $data['cust_code'] = $customer_id;
        $data['response_info_type'] = 2;

        $api_request = 'MG02-00104-101';
        $xml_apply_array = $this->sbp_request->result_send_sbp_api($data, $api_request, $sandbox_mode, $this->debug);
        $card_num = '';
        if( isset($xml_apply_array->res_result) && $xml_apply_array->res_result == 'OK' ){
            $card_num = $this->sbp_request->des_ede3_do_decrypt( $xml_apply_array->res_pay_method_info->cc_number, $sandbox_mode );
        }
        return $card_num;
    }

    /**
	 * Process the payment and return the result.
     *
     * @param  int Order ID
     * @param  boolean $subscription
     * @return mixed
	 */
	function process_payment( $order_id , $subscription = false) {
		$order = new WC_Order( $order_id );
		$user = $order->get_user();
		if( isset( $user ) && get_current_user_id() != 0 ){
			$customer_id   = get_current_user_id();
		}else{
			$customer_id   = $order_id.'-user';
		}

		//Check test mode
		$sandbox_mode = $this->sandbox_mode;
		
		$data = array();
		if(get_post_meta( $order_id, 'sbp_tracking_id', true )){
			$data['tracking_id'] = get_post_meta( $order_id, 'sbp_tracking_id', true );
		}
		$data = array(
			'cust_code' => $customer_id,
			'order_id' => $order_id,
			'item_id' => 'woo-item',
			'amount' => $order->get_total(),
		);
		//Set Divide times
		$divide_times = $this->get_post('divide_times');
		if($divide_times == 10 or $divide_times == 21 or $divide_times == 80){
			$data['pay_method_info']['dealings_type'] = $divide_times;
		}elseif($divide_times == 61){
			$data['pay_method_info']['dealings_type'] = 61;
			$data['pay_method_info']['divide_times'] = $divide_times;			
		}else{
            $data['pay_method_info']['dealings_type'] = 10;
        }
		//Set token
		$tokens = WC_Payment_Tokens::get_customer_tokens( get_current_user_id(), $this->id );
		if( $this->store_card_info == 'yes' and $this->get_post('sbp-use-stored-payment-info') == 'yes' ){
            $data['pay_option_manage']['cust_manage_flg'] = 0;
		}else{
			$data['pay_option_manage']['token'] = $this->get_post('token');
			$data['pay_option_manage']['token_key'] = $this->get_post('tokenKey');
            //Store Customer Information
            if( $this->store_card_info == 'yes' || $subscription == true ){
                $data['pay_option_manage']['cust_manage_flg'] = 1;
            }else{
                $data['pay_option_manage']['cust_manage_flg'] = 0;
            }
		}
		// 3DES encryption flag
		$data['encrypted_flg'] = 1;

		// Set Request time
        if( get_post_meta( $order_id, 'sbp_tracking_id', true ) ){
            $api_request = 'ST01-00133-101';//One time token with reorder
        }else{
            $api_request = 'ST01-00131-101';//One time token
        }
		$xml_apply_array = $this->sbp_request->result_send_sbp_api($data, $api_request, $sandbox_mode, $this->debug);

		if( $xml_apply_array->res_result == 'OK' ){// Successful settlement request (Payment Request)
		    if(empty($tokens) && $this->store_card_info == 'yes'){
                // Save Token for WooCommerce
                // Set check stored card information
                $customer_data['cust_code'] = $customer_id;
                $customer_data['sps_cust_info_return_flg'] = 1;
                $customer_data['response_info_type'] = 2;
                $customer_data['pay_option_manage']['cardbrand_return_flg'] = 1;

                $check_api_request = 'MG02-00104-101';
                $stored_card_array = $this->sbp_request->result_send_sbp_api($customer_data, $check_api_request, $sandbox_mode, $this->debug);
                if( $stored_card_array->res_result == 'OK' ){
                    $cc_expiration = $this->sbp_request->des_ede3_do_decrypt($stored_card_array->res_pay_method_info->cc_expiration, $sandbox_mode);
                    $cc_number = $this->sbp_request->des_ede3_do_decrypt($stored_card_array->res_pay_method_info->cc_number, $sandbox_mode);
                    $token = new WC_Payment_Token_CC();
                    $token->set_token( $stored_card_array->res_sps_info->res_sps_cust_no.'-'.$stored_card_array->res_sps_info->res_sps_payment_no );
                    $token->set_gateway_id( $this->id );
                    $token->set_last4( substr( $cc_number, -4 ) );
                    $token_year = substr( $cc_expiration ,0 ,4 );
                    $token_month = trim(substr( $cc_expiration ,4 ));
                    $card_code = $this->sbp_request->des_ede3_do_decrypt($stored_card_array->res_pay_method_info->cardbrand_code, $sandbox_mode);
                    if( $card_code == 'J' ){
                        $card_code = 'jcb';
                    }elseif( $card_code == 'V' ){
                        $card_code = 'visa';
                    }elseif( $card_code == 'M' ){
                        $card_code = 'mastercard';
                    }elseif( $card_code == 'A' ){
                        $card_code = 'amex';
                    }elseif( $card_code == 'D' ){
                        $card_code = 'dinners';
                    }else{
                        $card_code = 'others';
                    }
                    $token->set_default(true);
                    $token->set_card_type( $card_code );
                    $token->set_expiry_month( $token_month );
                    $token->set_expiry_year( $token_year );
                    $token->set_user_id( get_current_user_id() );
                    if($token->save() == true){
                        $order->add_order_note( __( 'Succeeded registration of token.', 'woo-sbp' ) );
                    }
                }else{
                    $order->add_order_note( __( 'Token registration failed because registration credit information could not be obtained.', 'woo-sbp' ) );
                }
            }
            // Confirmation request
			$apply_data = array();
			$apply_data['sps_transaction_id'] = $xml_apply_array->res_sps_transaction_id;
			$apply_data['tracking_id'] = $xml_apply_array->res_tracking_id;
			if($this->store_card_info == 'yes'){
				$apply_data['sps_cust_no'] = $xml_apply_array->res_sps_cust_no;
				$apply_data['payment_cust_no'] = $xml_apply_array->res_sps_payment_no;
			}
			$apply_api_request = 'ST02-00101-101';
			$xml_confirmation_array = $this->sbp_request->result_send_sbp_api($apply_data, $apply_api_request, $sandbox_mode, $this->debug);
			if($xml_confirmation_array->res_result == 'OK'){//Successful confirmation request
				update_post_meta( $order_id, 'sbp_tracking_id', strval($xml_apply_array->res_tracking_id) );
				if($this->payment_action == 'sale'){
					$sale_data = array();
					$sale_data['sps_transaction_id'] = $xml_apply_array->res_sps_transaction_id;
					$sale_data['tracking_id'] = $xml_apply_array->res_tracking_id;
					$sale_data['processing_datetime'] = date('YmdHis');
					$sale_data['pay_option_manage']['amount'] = $order->get_total();
					$sale_api_request = 'ST02-00201-101';
					$xml_sale_array = $this->sbp_request->result_send_sbp_api($sale_data, $sale_api_request, $sandbox_mode, $this->debug);
					if($xml_sale_array->res_result == 'OK' or $xml_sale_array->res_err_code == '10137999'){//Successful Sale request
						// Payment complete
						$order->payment_complete(strval($xml_apply_array->res_sps_transaction_id));
						// Return thank you redirect
						return array (
							'result'   => 'success',
							'redirect' => $this->get_return_url( $order ),
						);
					}else{//Failed sale request
						$order->set_status('cancelled',sprintf( __( 'Error Code : %s, please check it.', 'woo-sbp' ),$xml_sale_array->res_err_code).' '.__( 'Failed sale request', 'woo-sbp' ));
						$order->save();
					}
				}else{
					// Payment complete
					$order->payment_complete(strval($xml_apply_array->res_sps_transaction_id));
					// Return thank you redirect
					return array (
						'result'   => 'success',
						'redirect' => $this->get_return_url( $order ),
					);
				}
			}else{//Failed confirmation request
				$order->add_order_note(sprintf( __( 'Error Code : %s, please check it.', 'woo-sbp' ),$xml_confirmation_array->res_err_code).' '.__( 'Failed confirmation request.', 'woo-sbp' ));
			}
		}elseif($xml_apply_array->res_result == 'NG'){// Failed settlement request (Payment Request)
			$order->add_order_note(sprintf( __( 'Error Code : %s, please check it.', 'woo-sbp' ),$xml_apply_array->res_err_code).' '.__( 'Failed settlement request.', 'woo-sbp' ));
			if(isset($xml_apply_array->res_tracking_id)){
				$order->add_order_note(sprintf( __( 'Tracking ID : %s, please check it.', 'woo-sbp' ),$xml_apply_array->res_err_code).' '.__( 'Failed settlement request.', 'woo-sbp' ));
				update_post_meta( $order_id, 'sbp_tracking_id', strval($xml_apply_array->res_tracking_id) );
			}else{
				$description = __( 'Error at payment and no tracking number.', 'woo-sbp' );
				$order->set_status('cancelled', $description);
				$order->save();
			}
		}else{// Failed settlement request (at connect)
			$order->add_order_note( __( 'Failed settlement request.', 'woo-sbp' ).' '.__( 'Failed settlement request.', 'woo-sbp' ));
		}
	}

	/**
	 * Completed Payment
     * @param  int $order_id
	 */
	function order_status_completed_sbp_cc( $order_id ){
		$order = wc_get_order( $order_id );
		$payment_method = $order->get_payment_method();
		$transaction_id = $order->get_transaction_id();
		if( isset($payment_method) and isset($transaction_id) and $payment_method == $this->id and $this->payment_action != 'sale' ){
			//Check test mode
			$sandbox_mode = $this->sandbox_mode;

			// Set SBPS transaction ID
			$data['sps_transaction_id'] = $transaction_id;
			// Set Request time
			date_default_timezone_set('Asia/Tokyo');
			$data['processing_datetime'] = date('Ymd000000');
			//
			$data['pay_option_manage']['amount'] = $order->get_total();
			//
			$data['request_date'] = date('YmdHis');
			$send_arrays['sps-api-request'] = 'ST02-00201-101';//Sales requirement

			//
			$send_arrays['data'] = $data;
			$send_arrays['data']['sps_hashcode'] = sha1($this->sbp_request->make_hash($send_arrays, $sandbox_mode));

            //Save debug send data.
            $message = 'sps-api-request : ' . $send_arrays['sps-api-request'] . "\n" . $this->jp4wc_framework->jp4wc_array_to_message($send_arrays['data']) . 'This is send data.';
            $this->jp4wc_framework->jp4wc_debug_log( $message, $this->debug, 'woo-sbp', WC_SBP_VERSION, JP4WC_SBP_FRAMEWORK_VERSION);

            //Make XML Data
			$xml_data = $this->sbp_request->make_xml($send_arrays, $sandbox_mode);
			//Send request to SBP API
			$xml_apply_array = $this->sbp_request->get_sbp_request($xml_data, $sandbox_mode, $this->debug);

			if($xml_apply_array->res_result == 'OK'){// Success Sales requirement request (When complete)
				$order->add_order_note(__( 'Success Sales requirement request.', 'woo-sbp' ));
			}elseif($xml_apply_array->res_result == 'NG'){// Failed Sales requirement request (When complete)
			    if(strpos($xml_apply_array->res_err_code,'10103') !== false){
                    $order->add_order_note(sprintf( __( 'The required item code %s was insufficient.', 'woo-sbp' ),substr($xml_apply_array->res_err_code,-3)).' '.__( 'Failed Sales requirement request', 'woo-sbp' ));
                }else{
                    $order->add_order_note(sprintf( __( 'Error Code : %s, please check it.', 'woo-sbp' ),$xml_apply_array->res_err_code).' '.__( 'Failed Sales requirement request', 'woo-sbp' ));
                }
			}else{
				$order->add_order_note(__( 'Something Error happened at Sales requirement request.', 'woo-sbp' ));
			}

		}

	}

    /**
     * Check payment details for valid format
     */
	function validate_fields() {
        if (is_null($this->get_post('token')) || is_null($this->get_post('tokenKey'))) {
            wc_add_notice( __( 'Sorry, now failed to get the token. Please check Internet environment. If there are no problems with the Internet environment and you fail repeatedly, please contact the store.', 'woocommerce-for-paygent-payment-main'), $notice_type = 'error' );
            return false;
        }
	}

	/**
	 * Process a refund if supported
	 * @param  int $order_id
	 * @param  float $amount
	 * @param  string $reason
	 * @return  boolean | mixed True or false based on success, or a WP_Error object
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = wc_get_order( $order_id );

		//Check test mode
		$sandbox_mode = $this->sandbox_mode;
		// Set SBPS transaction ID
		$data['sps_transaction_id'] = $order->get_transaction_id();
		// Set Request time
		date_default_timezone_set('Asia/Tokyo');
		$data['processing_datetime'] = date('Ymd000000');
		if($amount == $order->get_total()){
			$data['request_date'] = date('YmdHis');
			$send_arrays['sps-api-request'] = 'ST02-00303-101';//Total Amount refund			
		}else{
			$data['pay_option_manage']['amount'] = $amount;
			// Set request Date
			$data['request_date'] = date('YmdHis');
			$send_arrays['sps-api-request'] = 'ST02-00307-101';//Partial Amount refund
		}

		$send_arrays['data'] = $data;
		$send_arrays['data']['sps_hashcode'] = sha1($this->sbp_request->make_hash($send_arrays, $sandbox_mode));

        //Save debug send data.
        $message = 'sps-api-request : ' . $send_arrays['sps-api-request'] . "\n" . $this->jp4wc_framework->jp4wc_array_to_message($send_arrays['data']) . 'This is send data.';
        $this->jp4wc_framework->jp4wc_debug_log( $message, $this->debug, 'woo-sbp', WC_SBP_VERSION, JP4WC_SBP_FRAMEWORK_VERSION);

		//Make XML Data
		$xml_data = $this->sbp_request->make_xml($send_arrays, $sandbox_mode);
		//Send request to SBP API
		$xml_apply_array = $this->sbp_request->get_sbp_request($xml_data, $sandbox_mode, $this->debug);

		if($xml_apply_array->res_result == 'OK'){// Success refund request
			$order->add_order_note(__( 'Success refund request.', 'woo-sbp' ));
			return true;
		}elseif($xml_apply_array->res_result == 'NG'){// Failed refund request
			return new WP_Error( 'sbp_refund_error', sprintf( __( 'Error Code : %s, please check it.', 'woo-sbp' ),$xml_apply_array->res_err_code).' '.__( 'Failed refund request', 'woo-sbp' ) );
		}else{
			return new WP_Error( 'sbp_refund_error', __( 'Something Error happened at refund request.', 'woo-sbp' ) );
		}
	}

	/**
	 * Get post data if set
     * @param  string $name
     * @return  mixed
	 */
	private function get_post( $name ) {
		if ( isset( $_POST[ $name ] ) ) {
			return sanitize_text_field( $_POST[ $name ] );
		}
		return null;
	}

	/**
	 * Read SB Payment Service Token javascript
	 */
	public function sbp_token_scripts_method() {
		$sandbox_mode = $this->sandbox_mode;
		if($sandbox_mode == 'yes'){
			$sbp_token_js_link = JP4WC_SBP_SANDBOX_TOKEN_URL;
		}else{
			$sbp_token_js_link = JP4WC_SBP_TOKEN_URL;
		}
		if(is_checkout() || is_wc_endpoint_url( 'add-payment-method' )){
			wp_enqueue_script(
				'sbp-token',
				$sbp_token_js_link,
				array(),
				WC_SBP_VERSION,
				false
			);
		}
	}
}

/**
 * Add the gateway to woocommerce
 */
function add_wc_sbp_cc_gateway( $methods ) {
	$subscription_support_enabled = false;
	if ( class_exists( 'WC_Subscriptions_Order' ) && function_exists( 'wcs_create_renewal_order' ) ) {
		$subscription_support_enabled = true;
	}
	if ( $subscription_support_enabled ) {
		$methods[] = 'WC_Gateway_SBP_CC_Addons';
	}else{
        $methods[] = 'WC_Gateway_SBP_CC';
    }
	return $methods;
}

add_filter( 'woocommerce_payment_gateways', 'add_wc_sbp_cc_gateway' );

/**
 * Edit the available gateway to woocommerce
 */
function edit_sbp_available_gateways( $methods ) {
	if ( isset($currency) ) {
	}else{
		$currency = get_woocommerce_currency();
	}
	if($currency !='JPY'){
		unset($methods['sbp_cc']);
	}
	return $methods;
}

add_filter( 'woocommerce_available_payment_gateways', 'edit_sbp_available_gateways' );
