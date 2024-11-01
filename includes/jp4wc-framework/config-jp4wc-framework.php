<?php
/**
 * Plugin Name: SB Payment for WooCommerce
 * Framework Name: Artisan Workshop FrameWork for WooCommerce
 * Framework Version : 2.0.9
 * Author: Artisan Workshop
 * Author URI: https://wc.artws.info/
 * Text Domain: woo-sbp
 *
 * @category JP4WC_Framework
 * @author Artisan Workshop
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return apply_filters(
    'woo_sbp_framework_config',
    array(
        'description_check_pattern' => __( 'Please check it if you want to use %s.', 'woo-sbp' ),
        'description_payment_pattern' => __( 'Please check it if you want to use the payment method of %s.', 'woo-sbp' ),
        'description_input_pattern' => __( 'Please input %s.', 'woo-sbp' ),
        'description_select_pattern' => __( 'Please select one from these as %s.', 'woo-sbp' ),
        'support_notice_01' => __( 'Need support?', 'woo-sbp' ),
        'support_notice_02' => __( 'If you are having problems with this plugin, talk about them in the <a href="%s" target="_blank" title="Pro Version">Support forum</a>.', 'woo-sbp' ),
        'support_notice_03' => __( 'If you need professional support, please consider about <a href="%1$s" target="_blank" title="Site Construction Support service">Site Construction Support service</a> or <a href="%2$s" target="_blank" title="Maintenance Support service">Maintenance Support service</a>.', 'woo-sbp' ),
        'pro_notice_01' => __( 'Pro version', 'woo-sbp' ),
        'pro_notice_02' => __( 'The pro version is available <a href="%s" target="_blank" title="Support forum">here</a>.', 'woo-sbp' ),
        'pro_notice_03' => __( 'The pro version includes support for bulletin boards. Please consider purchasing the pro version.', 'woo-sbp' ),
        'update_notice_01' => __( 'Finished Latest Update, WordPress and WooCommerce?', 'woo-sbp' ),
        'update_notice_02' => __( 'One the security, latest update is the most important thing. If you need site maintenance support, please consider about <a href="%s" target="_blank" title="Support forum">Site Maintenance Support service</a>.', 'woo-sbp' ),
        'community_info_01' => __( 'Where is the study group of Woocommerce in Japan?', 'woo-sbp' ),
        'community_info_02' => __( '<a href="%s" target="_blank" title="Tokyo WooCommerce Meetup">Tokyo WooCommerce Meetup</a>.', 'woo-sbp' ),
        'community_info_03' => __( '<a href="%s" target="_blank" title="Kansai WooCommerce Meetup">Kansai WooCommerce Meetup</a>.', 'woo-sbp' ),
        'community_info_04' => __('Join Us!', 'woo-sbp' ),
        'author_info_01' => __( 'Created by', 'woo-sbp' ),
        'author_info_02' => __( 'WooCommerce Doc in Japanese', 'woo-sbp' ),
        'framework_version' => JP4WC_SBP_FRAMEWORK_VERSION,
    )
);
