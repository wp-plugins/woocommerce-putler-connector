<?php
/*
 * Plugin Name: WooCommerce Putler Connector
 * Plugin URI: http://putler.com/connector/woocommerce/
 * Description: Track WooCommerce transactions data with Putler. Insightful reporting that grows your business.
 * Version: 2.3
 * Author: putler, storeapps
 * Author URI: http://putler.com/
 * License: GPL 3.0
*/

add_action( 'plugins_loaded', 'woocommerce_putler_connector_pre_init' );

function woocommerce_putler_connector_pre_init () {

	// Simple check for WooCommerce being active...
	if ( class_exists('WooCommerce') ) {

		// Init admin menu for settings etc if we are in admin
		if ( is_admin() ) {
			woocommerce_putler_connector_init();
		} 

		// If configuration not done, can't track anything...
		if ( null != get_option('putler_connector_settings', null) ) {
			// On these events, send order data to Putler
			if ( is_admin() && ( !defined( 'DOING_AJAX' ) || !DOING_AJAX ) ) { 
                add_action( 'post_updated', 'woocommerce_putler_connector_order_updated');
            } else {
                add_action( 'woocommerce_order_status_changed', 'woocommerce_putler_connector_post_order' );
            }
		}
	}
}

function woocommerce_putler_connector_init() {
	
	include_once 'classes/class.putler-connector.php';
	$GLOBALS['putler_connector'] = Putler_Connector::getInstance();

    include_once 'classes/class.putler-connector-woocommerce.php';
    if ( !isset( $GLOBALS['woocommerce_putler_connector'] ) ) {
    	$GLOBALS['woocommerce_putler_connector'] = new WooCommerce_Putler_Connector();
	}
}

function woocommerce_putler_connector_order_updated( $post_id ) {

	//Flag for woo2.2+
	$order_status_new = $_POST['order_status'];

    if (version_compare ( WOOCOMMERCE_VERSION, '2.2.0', '<' )) {
    	$order_status_old = get_the_terms( $post_id,'shop_order_status');
    	$order_status_old = $order_status_old[0]->slug;
    } else {
    	$order_status_old = get_post_status( $post_id );
    }
	
	if ( get_post_type( $post_id ) === 'shop_order' &&  $order_status_old == $order_status_new ) {
		woocommerce_putler_connector_post_order($post_id);
	}
}

function woocommerce_putler_connector_post_order( $order_id ) {
	woocommerce_putler_connector_init();
	if (method_exists($GLOBALS['putler_connector'], 'post_order') ) {
		$GLOBALS['putler_connector']->post_order( $order_id );	
	}
}

