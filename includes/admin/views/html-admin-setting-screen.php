<?php global $woocommerce; ?>
<div class="wrap">
	<h2><?php echo  __( 'General Setting', 'woo-sbp' );?></h2>
	<div class="wc-sbp-settings metabox-holder">
		<div class="wc-sbp-sidebar">
			<div class="wc-sbp-credits">
				<h3 class="hndle"><?php echo __( 'SBPS for WooCommerce', 'woo-sbp' ) . ' ' . WC_SBP_VERSION;?></h3>
				<div class="inside">
					<!-- <hr />-->
					<?php $this->jp4wc_plugin->jp4wc_update_notice();?>
					<hr />
					<?php $this->jp4wc_plugin->jp4wc_community_info();?>
					<hr />
					<h4 class="inner"><?php echo __( 'Do you like this plugin?', 'woo-sbp' );?></h4>
					<p class="inner"><a href="https://ja.wordpress.org/support/plugin/woo-sbp/reviews?rate=5#new-post" target="_blank" title="' . __( 'Rate it 5', 'woo-sbp' ) . '"><?php echo __( 'Rate it 5', 'woo-sbp' )?> </a><?php echo __( 'on WordPress.org', 'woo-sbp' ); ?><br />
					</p>
					<hr />
					<p class="wc-sbp-link inner"><?php echo __( 'Created by', 'woo-sbp' );?> <a href="https://wc.artws.info/?utm_source=wc4jp-settings&utm_medium=link&utm_campaign=created-by" target="_blank" title="Artisan Workshop"><img src="<?php echo WC_SBP_PLUGIN_URL;?>assets/images/woo-logo.png" title="Artsain Workshop" alt="Artsain Workshop" class="wc4jp-logo" /></a><br />
					<a href="https://docs.artws.info/?utm_source=wc4jp-settings&utm_medium=link&utm_campaign=created-by" target="_blank"><?php echo __( 'WooCommerce Doc in Japanese', 'woo-sbp' );?></a>
					</p>
				</div>
			</div>
		</div>
		<form id="wc-sbp-setting-form" method="post" action="" enctype="multipart/form-data">
			<div id="main-sortables" class="meta-box-sortables ui-sortable">
<?php
	//Display Setting Screen
	settings_fields( 'wc_sbp' );
	$this->jp4wc_plugin->do_settings_sections( 'wc_sbp' );
?>
			<p class="submit">
<?php
	submit_button( '', 'primary', 'save_wc_sbp_options', false );
?>
			</p>
			</div>
		</form>
		<div class="clear"></div>
	</div>
	<script type="text/javascript">
	//<![CDATA[
	jQuery(document).ready( function ($) {
		// close postboxes that should be closed
		$('.if-js-closed').removeClass('if-js-closed').addClass('closed');
		// postboxes setup
		postboxes.add_postbox_toggles('wc_sbp');
	});
	//]]>
	</script>
</div>