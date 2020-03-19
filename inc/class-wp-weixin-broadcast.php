<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WP_Weixin_Broadcast {

	protected $wechat;

	protected static $core_min_version;
	protected static $core_max_version;

	public function __construct( $wechat, $init_hooks = false ) {
		$this->wechat = $wechat;

		if ( $init_hooks ) {
			// Add translation
			add_action( 'init', array( $this, 'load_textdomain' ), 0, 0 );
			// Add core version checks
			add_action( 'init', array( $this, 'check_wp_weixin_version' ), 0, 0 );
			// Add admin scripts
			add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_scripts' ), 9, 1 );
			// Rearrange menus
			add_action( 'admin_menu', array( $this, 'rearrange_menus' ), 10, 0 );

			// Add query vars
			add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0, 1 );
		}
	}

	/*******************************************************************
	 * Public methods
	 *******************************************************************/

	public static function activate() {
		$result = self::maybe_create_or_upgrade_db();

		if ( ! $result ) {
			// translators: %1$s is the path to the plugin's data directory
			$error_message = __( 'Failed to create the necessary database table(s).', 'wp-weixin-broadcast' );

			die( $error_message ); // WPCS: XSS OK
		}

		set_transient( 'wp_weixin_broadcast_flush', 1, 60 );
		wp_cache_flush();

		if ( ! get_option( 'wp_weixin_broadcast_plugin_version' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';

			$plugin_data = get_plugin_data( WP_WEIXIN_BROADCAST_PLUGIN_FILE );
			$version     = $plugin_data['Version'];

			update_option( 'wp_weixin_broadcast_plugin_version', $version );
		}
	}

	public function rearrange_menus() {
		global $menu, $submenu;

		add_menu_page(
			__( 'WeChat Broadcast', 'wp-weixin-broadcast' ),
			__( 'WeChat Broadcast', 'wp-weixin-broadcast' ),
			'publish_posts',
			'wechat-broadcast-menu',
			'',
			WP_WEIXIN_PLUGIN_URL . 'images/wechat.png',
			100
		);
	}

	public static function deactivate() {
		flush_rewrite_rules();
	}

	public static function uninstall() {
		include_once WP_WEIXIN_BROADCAST_PLUGIN_PATH . 'uninstall.php';
	}

	public static function maybe_create_or_upgrade_db() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = '';

		if ( ! empty( $wpdb->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
		}

		if ( ! empty( $wpdb->collate ) ) {
			$charset_collate .= " COLLATE {$wpdb->collate}";
		}

		$table_name = $wpdb->prefix . 'wp_weixin_broadcast_message_logs';
		$sql        = 'CREATE TABLE ' . $table_name . " (
			id int(20) NOT NULL auto_increment,
			post_id int(20) NOT NULL,
			status ENUM('success', 'warning', 'failure') NOT NULL,         
			type varchar(25) NOT NULL,
			date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			code varchar(10) NOT NULL default 'ok',
			remark longtext,
			PRIMARY KEY (id),
			KEY post_id (post_id)
			)" . $charset_collate . ';';

		dbDelta( $sql );

		$table_name = $wpdb->get_var( "SHOW TABLES LIKE '" . $wpdb->prefix . "wp_weixin_broadcast_message_logs'" );

		if ( $wpdb->prefix . 'wp_weixin_broadcast_message_logs' !== $table_name ) {
			return false;
		}

		return true;
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'wp-weixin-broadcast', false, 'wp-weixin-broadcast/languages' );
	}

	public function check_wp_weixin_version() {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		$core_plugin_data       = get_plugin_data( WP_WEIXIN_PLUGIN_FILE );
		$plugin_data            = get_plugin_data( WP_WEIXIN_BROADCAST_PLUGIN_FILE );
		$core_version           = $core_plugin_data['Version'];
		$current_version        = $plugin_data['Version'];
		self::$core_min_version = defined( 'WP_WEIXIN_PLUGIN_FILE' ) ? $plugin_data[ WP_Weixin::VERSION_REQUIRED_HEADER ] : 0;
		self::$core_max_version = defined( 'WP_WEIXIN_PLUGIN_FILE' ) ? $plugin_data[ WP_Weixin::VERSION_TESTED_HEADER ] : 0;

		if (
			! version_compare( $current_version, self::$core_min_version, '>=' ) ||
			! version_compare( $current_version, self::$core_max_version, '<=' )
		) {
			add_action( 'admin_notices', array( 'WP_Weixin_Broadcast', 'core_version_notice' ) );
			deactivate_plugins( WP_WEIXIN_BROADCAST_PLUGIN_FILE );
		}
	}

	public static function core_version_notice() {
		$class   = 'notice notice-error is-dismissible';
		$message = sprintf(
			// translators: WP Weixin requirements - %1$s is the minimum version, %2$s is the maximum
			__(
				'WP Weixin Broadcast has been disabled: it requires WP Weixin with version between %1$s and %2$s to be activated.',
				'wp-weixin-broadcast'
			),
			self::$core_min_version,
			self::$core_max_version
		);

		echo sprintf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message ); // WPCS: XSS ok
	}

	public function add_query_vars( $vars ) {
		$vars[] = 'tencent_agree';

		return $vars;
	}

	public function add_admin_scripts( $hook ) {
		global $typenow;

		$types = array(
			'WP_Weixin_Article'           => WP_Weixin_Article::POST_TYPE,
			'WP_Weixin_Broadcast_Message' => WP_Weixin_Broadcast_Message::POST_TYPE,
		);
		$hooks = array();

		if ( ! in_array( $typenow, $types, true ) && ! in_array( $hook, $hooks, true ) ) {

			return;
		}

		$debug   = apply_filters( 'wp_weixin_debug', (bool) ( constant( 'WP_DEBUG' ) ) );
		$css_ext = ( $debug ) ? '.css' : '.min.css';
		$version = filemtime( WP_WEIXIN_BROADCAST_PLUGIN_PATH . 'css/admin/main' . $css_ext );

		wp_enqueue_style( 'wp-weixin-broadcast-main-style', WP_WEIXIN_BROADCAST_PLUGIN_URL . 'css/admin/main' . $css_ext, array(), $version );
		wp_enqueue_style( 'wp-weixin-broadcast-select2-style', WP_WEIXIN_BROADCAST_PLUGIN_URL . 'css/admin/select2' . $css_ext, array(), $version );

		$js_ext  = ( $debug ) ? '.js' : '.min.js';
		$version = filemtime( WP_WEIXIN_BROADCAST_PLUGIN_PATH . 'js/admin/main' . $js_ext );
		$params  = array(
			'hook'        => $hook,
			'ajax_url'    => admin_url( 'admin-ajax.php' ),
			'debug'       => $debug,
			'editor_args' => wp_enqueue_code_editor( array( 'type' => 'text/html' ) ),
		);

		wp_enqueue_script( 'wp-theme-plugin-editor' );
		wp_enqueue_style( 'wp-codemirror' );

		if ( 'post.php' === $hook || 'post-new.php' === $hook ) {
			global $post;

			$params['screen']     = 'single';
			$params['postStatus'] = $post->post_status;

		}

		if ( 'edit.php' === $hook ) {
			$params['screen'] = 'list';
		}

		foreach ( $types as $key => $type ) {

			if ( method_exists( $key, 'get_main_script_params' ) ) {
				$extend = call_user_func_array( array( $key, 'get_main_script_params' ), array( $params ) );
				$params = array_merge(
					$params,
					$extend
				);
			}
		}

		$params['locale']  = get_locale();
		$long_locales_list = array(
			'pt-BR',
			'sr-Cyrl',
			'zh-CN',
			'zh-TW',
		);

		if ( function_exists( 'pll_current_language' ) ) {
			// Polylang support
			$params['locale'] = pll_current_language();
		} elseif ( has_filter( 'wpml_current_language' ) ) {
			// WPML support
			$params['locale'] = apply_filters( 'wpml_current_language', null );
		}

		if ( ! in_array( $params['locale'], $long_locales_list, true ) && 2 < strlen( $params['locale'] ) ) {
			$params['locale'] = substr( $params['locale'], 0, 1 );
		}

		wp_enqueue_script( 'wp-weixin-broadcast-main-script', WP_WEIXIN_BROADCAST_PLUGIN_URL . 'js/admin/main' . $js_ext, array( 'jquery' ), $version, true );
		wp_localize_script( 'wp-weixin-broadcast-main-script', 'WP_Weixin_Broadcast', $params );
		wp_enqueue_script( 'wp-weixin-broadcast-sortable-script', WP_WEIXIN_BROADCAST_PLUGIN_URL . 'js/admin/lib/jquery-sortable-min.js', array( 'jquery' ), false, true );
		wp_enqueue_script( 'wp-weixin-broadcast-select2-script', WP_WEIXIN_BROADCAST_PLUGIN_URL . 'js/admin/lib/select2/select2.full' . $js_ext, array( 'jquery' ), false, true );

		if ( file_exists( WP_WEIXIN_BROADCAST_PLUGIN_PATH . 'js/admin/lib/select2/i18n/' . $params['locale'] . '.js' ) ) {
			wp_enqueue_script( 'wp-weixin-broadcast-select2-i18n-script', WP_WEIXIN_BROADCAST_PLUGIN_URL . 'js/admin/lib/select2/i18n/' . $params['locale'] . '.js', array( 'wp-weixin-broadcast-select2-script' ), false, true );
		}

	}

	/*******************************************************************
	 * Protected methods
	 *******************************************************************/

}
