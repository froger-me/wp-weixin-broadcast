<?php
/*
Plugin Name: WP Weixin Broadcast
Plugin URI: https://github.com/froger-me/wp-weixin-broadcast
Description: WeChat Broadcast for WordPress
Version: 1.3.11
Author: Alexandre Froger
Author URI: https://froger.me/
Text Domain: wp-weixin-broadcast
Domain Path: /languages
WC tested up to: 4.0.0
WP Weixin minimum required version: 1.3.5
WP Weixin tested up to: 1.3.11
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! defined( 'WP_WEIXIN_BROADCAST_PLUGIN_FILE' ) ) {
	define( 'WP_WEIXIN_BROADCAST_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'WP_WEIXIN_BROADCAST_PLUGIN_PATH' ) ) {
	define( 'WP_WEIXIN_BROADCAST_PLUGIN_PATH', plugin_dir_path( WP_WEIXIN_BROADCAST_PLUGIN_FILE ) );
}

if ( ! defined( 'WP_WEIXIN_BROADCAST_PLUGIN_URL' ) ) {
	define( 'WP_WEIXIN_BROADCAST_PLUGIN_URL', plugin_dir_url( WP_WEIXIN_BROADCAST_PLUGIN_FILE ) );
}

require_once WP_WEIXIN_BROADCAST_PLUGIN_PATH . 'inc/class-wp-weixin-broadcast.php';

register_activation_hook( WP_WEIXIN_BROADCAST_PLUGIN_FILE, array( 'WP_Weixin_Broadcast', 'activate' ) );
register_deactivation_hook( WP_WEIXIN_BROADCAST_PLUGIN_FILE, array( 'WP_Weixin_Broadcast', 'deactivate' ) );
register_uninstall_hook( WP_WEIXIN_BROADCAST_PLUGIN_FILE, array( 'WP_Weixin_Broadcast', 'uninstall' ) );

function wp_weixin_broadcast_extension( $wechat, $wp_weixin_settings, $wp_weixin, $wp_weixin_auth, $wp_weixin_responder, $wp_weixin_menu ) {

	if ( wp_weixin_get_option( 'enabled' ) ) {
		require_once WP_WEIXIN_BROADCAST_PLUGIN_PATH . 'inc/class-wp-weixin-article.php';
		require_once WP_WEIXIN_BROADCAST_PLUGIN_PATH . 'inc/class-wp-weixin-broadcast-message.php';
		require_once WP_WEIXIN_BROADCAST_PLUGIN_PATH . 'inc/class-wp-weixin-broadcast-logger.php';

		$wp_weixin_broadcast         = new WP_Weixin_Broadcast( $wechat, true );
		$wp_weixin_broadcast_logger  = new WP_Weixin_Broadcast_Logger( $wechat, WP_Weixin_Broadcast_Message::POST_TYPE, true );
		$wp_weixin_broadcast_message = new WP_Weixin_Broadcast_Message( $wechat, $wp_weixin_broadcast_logger, true );
		$wp_weixin_article           = new WP_Weixin_Article( $wechat, true );
	}
}

function wp_weixin_broadcast_run() {
	add_action( 'wp_weixin_extensions', 'wp_weixin_broadcast_extension', 0, 6 );
}
add_action( 'plugins_loaded', 'wp_weixin_broadcast_run', 0, 0 );
