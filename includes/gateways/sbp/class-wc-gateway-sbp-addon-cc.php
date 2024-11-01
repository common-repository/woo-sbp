<?php
/**
 * SB Payment Gateway
 *
 * Provides a SB Payment Card Payment Gateway.
 *
 * @class 		WC_Gateway_SBP_CC_Addons
 * @extends		WC_Gateway_SBP_CC
 * @version		1.0.7
 * @package		WooCommerce/Classes/Payment
 * @author		Artisan Workshop
 */
class WC_Gateway_SBP_CC_Addons extends WC_Gateway_SBP_CC {

	/**
	 * Constructor for the gateway.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		parent::__construct();

		if ( class_exists( 'WC_Subscriptions_Order' ) ) {
			add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, array( $this, 'scheduled_subscription_payment' ), 10, 2 );
		}
	}
	/**
	 * Check if order contains subscriptions.
	 *
	 * @param  int $order_id
	 * @return bool
	 */
	protected function order_contains_subscription( $order_id ) {
		return function_exists( 'wcs_order_contains_subscription' ) && ( wcs_order_contains_subscription( $order_id ) || wcs_order_contains_renewal( $order_id ) );
	}

	/**
	 * Is $order_id a subscription?
	 * @param  int  $order_id
	 * @return boolean
	 */
	protected function is_subscription( $order_id ) {
		return ( function_exists( 'wcs_order_contains_subscription' ) && ( wcs_order_contains_subscription( $order_id ) || wcs_is_subscription( $order_id ) || wcs_order_contains_renewal( $order_id ) ) );
	}

	/**
	 * Process the subscription.
	 *
	 * @param  WC_Order $order
	 * @param  boolean $subscription
	 * @return
	 */
	protected function process_subscription( $order , $subscription = false) {
		$payment_response = $this->process_subscription_payment( $order, $order->get_total() );
		return $payment_response;
	}

	/**
	 * Process the first payment at Subscription.
	 *
	 * @param  int $order_id
	 * @param  boolean $subscription
	 * @return array
	 */
	public function process_payment( $order_id , $subscription = false) {
		// Processing subscription
		if ( $this->is_subscription( $order_id ) ) {
			// Regular payment with force customer enabled
			return parent::process_payment( $order_id, true );
		} else {
			return parent::process_payment( $order_id );
		}
	}

	/**
	 * process_subscription_payment function.
	 *
	 * @param object WC_order $order
	 * @param int amount (default: 0)
	 * @return bool|WP_Error
	 */
	public function process_subscription_payment( $order = '', $amount = 0 ) {
		if ( 0 == $amount ) {
			// Payment complete
			$order->payment_complete();

			return true;
		}
		$order_id = $order->get_id();
        return parent::process_payment( $order_id, true );
    }

	/**
	 * scheduled_subscription_payment function.
	 *
	 * @param float The amount to charge.
	 * @param WC_Order A WC_Order object created to record the renewal payment.
	 */
	public function scheduled_subscription_payment( $amount_to_charge, $renewal_order ) {
		$result = $this->process_subscription_payment( $renewal_order, $amount_to_charge );

		if ( is_wp_error( $result ) ) {
			$renewal_order->update_status( 'failed', sprintf( __( 'SB Payment Transaction Failed (%s)', 'woocommerce' ), $result->get_error_message() ) );
		}
	}
}
