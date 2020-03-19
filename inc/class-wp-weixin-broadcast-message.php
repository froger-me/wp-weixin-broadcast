<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WP_Weixin_Broadcast_Message {
	public static $log_types;
	public static $log_statuses;

	protected $wechat;
	protected $logger;
	protected $broadcast_message_id;

	protected static $screen_alter_map;
	protected static $broadcastable_post_types;

	const POST_TYPE = 'wechat_broadcast_msg';
	const CAPS      = array(
		'create_wechat_broadcast_message',
		'edit_wechat_broadcast_message',
		'read_wechat_broadcast_message',
		'delete_wechat_broadcast_message',
		'edit_wechat_broadcast_messages',
		'edit_others_wechat_broadcast_messages',
		'publish_wechat_broadcast_messages',
		'read_wechat_broadcast_messages',
	);

	public function __construct( $wechat, $wp_weixin_broadcast_logger, $init_hooks = false ) {
		$this->logger                   = $wp_weixin_broadcast_logger;
		$this->wechat                   = $wechat;
		self::$log_types                = array(
			'add_rich_media'      => __( 'Add Rich Media material', 'wp-weixin-broadcast' ),
			'add_image'           => __( 'Add Image material', 'wp-weixin-broadcast' ),
			'preview'             => __( 'Send Preview', 'wp-weixin-broadcast' ),
			'direct_broadcast'    => __( 'Send Broadcast', 'wp-weixin-broadcast' ),
			'scheduled_broadcast' => __( 'Send Scheduled Broadcast', 'wp-weixin-broadcast' ),
			'remote_job_result'   => __( 'Broadcast Job result', 'wp-weixin-broadcast' ),
			'delete_item'         => __( 'Delete Rich Media Material', 'wp-weixin-broadcast' ),
		);
		self::$log_statuses             = array(
			'success' => __( 'success', 'wp-weixin-broadcast' ),
			'warning' => __( 'warning', 'wp-weixin-broadcast' ),
			'failure' => __( 'failure', 'wp-weixin-broadcast' ),
		);
		self::$screen_alter_map         = array(
			'Publish <b>immediately</b>' => __( 'Broadcast <b>immediately</b>', 'wp-weixin-broadcast' ),
			'Published'                  => __( 'Broadcasted', 'wp-weixin-broadcast' ),
			// translators: %s is the date
			'Published on: %s'           => __( 'Broadcasted on: %s', 'wp-weixin-broadcast' ),
			// translators: %s is the date
			'Publish on: %s'             => __( 'Broadcast on: %s', 'wp-weixin-broadcast' ),
			'Publish'                    => __( 'Broadcast Now', 'wp-weixin-broadcast' ),
		);
		self::$broadcastable_post_types = apply_filters(
			'wp_weixin_broadcast_broadcastable_post_types',
			array(
				WP_Weixin_Article::POST_TYPE,
			)
		);

		if ( $init_hooks ) {
			// Register WeChat Broadcast Post Type
			add_action( 'init', array( $this, 'register_wechat_broadcast_message' ), 0, 0 );
			// Add capabilities
			add_action( 'admin_init', array( $this, 'add_caps' ), 10, 0 );
			// Add WeChat Preview button
			add_action( 'post_submitbox_minor_actions', array( $this, 'add_preview_button' ), 10, 1 );
			// Add metaboxes
			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 10, 0 );
			// Add Broadcast methods settings
			add_action( 'wp_weixin_broadcast_targets_type_settings', array( $this, 'broadcast_methods_settings' ), 0, 2 );
			// Add Ignore Reprint status
			add_action( 'post_submitbox_start', array( $this, 'send_ignore_reprint_html' ), 10, 1 );
			// Save Broadcast Message
			add_action( 'save_post', array( $this, 'save_broadcast_message' ), 10, 1 );
			// Delete Broacast Message
			add_action( 'delete_post', array( $this, 'broadcast_content_delete' ), 10, 1 );
			// Handle broadcasting a scheduled Broadcast Message
			add_action( 'publish_future_post', array( $this, 'do_broadcast_scheduled' ), 10, 1 );
			// Change the title of the Publish metabox
			add_action( 'add_meta_boxes_' . self::POST_TYPE, array( $this, 'change_publish_meta_box' ), 9999, 0 );
			// Add admin notice for inconsistant articles structure
			add_action( 'admin_notices', array( $this, 'admin_notice' ), 10, 0 );
			// Add various filters and actions on Broadcast Message admin screens only
			add_action( 'current_screen', array( $this, 'current_screen_callback' ), 10, 1 );
			// Get list of WeChat Articles
			add_action( 'wp_ajax_wp_weixin_broadcast_get_items', array( $this, 'get_broadcastable_items_json' ), 10, 0 );
			// Get list of WeChat user group tags
			add_action( 'wp_ajax_wp_weixin_broadcast_get_tags', array( $this, 'get_remote_group_tags_json' ), 10, 0 );
			// Get list of WeChat user group tags
			add_action( 'wp_ajax_wp_weixin_broadcast_get_wp_wechat_users', array( $this, 'get_wp_wechat_users_json' ), 10, 0 );
			// Send preview to WeChat
			add_action( 'wp_ajax_wp_weixin_broadcast_preview', array( $this, 'do_preview' ), 10, 0 );
			// Get broadcast status
			add_action( 'wp_ajax_wp_weixin_broadcast_status', array( $this, 'broadcast_status' ), 10, 0 );
			// Delete broadcasted articles
			add_action( 'wp_ajax_wp_weixin_broadcast_delete_remote_material', array( $this, 'delete_remote' ), 10, 0 );
			// Add the data to the custom column
			add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'post_column_data' ), 10, 2 );
			// Handle WeChat Broadcast vonfimration response
			add_action( 'wp_weixin_responder', array( $this, 'handle_wechat_response' ), 10, 1 );

			// Prevent showing duplicate button
			add_filter( 'wp_weixin_broadcast_no_duplicate_post_types', array( $this, 'no_duplicate' ), 10, 1 );
			// Remove Quick Edit
			add_filter( 'post_row_actions', array( $this, 'remove_actions' ), 10, 2 );
			// Remove edit from bulk actions
			add_filter( 'bulk_actions-edit-' . self::POST_TYPE, array( $this, 'remove_from_bulk_actions' ), 10, 1 );
			// Change post change message notices
			add_filter( 'post_updated_messages', array( $this, 'updated_messages' ), 10, 1 );
			// Add the custom columns
			add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'post_columns' ), 10, 1 );
			// Handle empty titles
			add_filter( 'wp_insert_post_data', array( $this, 'default_post_title' ), 10, 1 );
		}
	}

	/*******************************************************************
	 * Public methods
	 *******************************************************************/

	public static function get_main_script_params( $params ) {
		global $post, $typenow;

		$can_broadcast = false;

		if ( $post ) {
			$msg_id        = get_post_meta( $post->ID, 'wp_weixin_broadcast_msg_id', true );
			$can_broadcast = ( ! $msg_id ) && ( 'future' !== $post->status );
		}

		return array(
			'postL10n'                          => array(
				'publish'         => __( 'Broadcast Now', 'wp-weixin-broadcast' ),
				'publishOn'       => __( 'Broadcast on:', 'wp-weixin-broadcast' ),
				'publishOnFuture' => __( 'Schedule Broadcast for:', 'wp-weixin-broadcast' ),
				'publishOnPast'   => __( 'Broadcasted on:', 'wp-weixin-broadcast' ),
				'published'       => __( 'Broadcasted', 'wp-weixin-broadcast' ),
			),
			'isBroadcastMessage'                => (
				self::POST_TYPE === $typenow ||
				( $post && self::POST_TYPE === $post->post_type )
			),
			'broadcastMessageItemPlaceholder'   => __( 'Drop Here', 'wp-weixin-broadcast' ),
			'broadcastMessageItemRemoveConfirm' => __( 'Are you sure you want to remove this item?', 'wp-weixin-broadcast' ),
			'broadcastMessageSubmitConfirm'     => __( "Are you sure you want to do this?\n\nIf you proceed, WordPress will attempt to call the WeChat interface and broadcast the message to the followers matching the selected criteria.\n\nYou will not be able to edit this WeChat Broadcast anymore, and the daily/monthly quota of broadcasts will be decreased for all the followers who successfully received the message.", 'wp-weixin-broadcast' ),
			'broadcastSelectTagPlaceholder'     => __( 'Choose a tag', 'wp-weixin-broadcast' ),
			'broadcastSelectArticlePlaceholder' => __( 'Search Items...', 'wp-weixin-broadcast' ),
			'broadcastSelectWPUsersPlaceholder' => __( 'Select WordPress users (default to all)', 'wp-weixin-broadcast' ),
			'canBroadcast'                      => $can_broadcast,
		);
	}

	public function register_wechat_broadcast_message() {
		$labels       = array(
			'name'                  => _x( 'WeChat Broadcasts', 'Post Type General Name', 'wp-weixin-broadcast' ),
			'singular_name'         => _x( 'WeChat Broadcast', 'Post Type Singular Name', 'wp-weixin-broadcast' ),
			'menu_name'             => __( 'WeChat Broadcasts', 'wp-weixin-broadcast' ),
			'name_admin_bar'        => __( 'WeChat Broadcast', 'wp-weixin-broadcast' ),
			'archives'              => __( 'WeChat Broadcast Archives', 'wp-weixin-broadcast' ),
			'attributes'            => __( 'WeChat Broadcast Attributes', 'wp-weixin-broadcast' ),
			'parent_item_colon'     => __( 'Parent WeChat Broadcast:', 'wp-weixin-broadcast' ),
			'all_items'             => __( 'All WeChat Broadcasts', 'wp-weixin-broadcast' ),
			'add_new_item'          => __( 'Add New WeChat Broadcast', 'wp-weixin-broadcast' ),
			'add_new'               => __( 'Add New', 'wp-weixin-broadcast' ),
			'new_item'              => __( 'New WeChat Broadcast', 'wp-weixin-broadcast' ),
			'edit_item'             => __( 'Edit WeChat Broadcast', 'wp-weixin-broadcast' ),
			'update_item'           => __( 'Update WeChat Broadcast', 'wp-weixin-broadcast' ),
			'view_item'             => __( 'View WeChat Broadcast', 'wp-weixin-broadcast' ),
			'view_items'            => __( 'View WeChat Broadcasts', 'wp-weixin-broadcast' ),
			'search_items'          => __( 'Search WeChat Broadcast', 'wp-weixin-broadcast' ),
			'not_found'             => __( 'Not found', 'wp-weixin-broadcast' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'wp-weixin-broadcast' ),
			'featured_image'        => __( 'Featured Image', 'wp-weixin-broadcast' ),
			'set_featured_image'    => __( 'Set featured image', 'wp-weixin-broadcast' ),
			'remove_featured_image' => __( 'Remove featured image', 'wp-weixin-broadcast' ),
			'use_featured_image'    => __( 'Use as featured image', 'wp-weixin-broadcast' ),
			'insert_into_item'      => __( 'Insert into WeChat Broadcast', 'wp-weixin-broadcast' ),
			'uploaded_to_this_item' => __( 'Uploaded to this WeChat Broadcast', 'wp-weixin-broadcast' ),
			'items_list'            => __( 'WeChat Broadcasts list', 'wp-weixin-broadcast' ),
			'items_list_navigation' => __( 'WeChat Broadcasts list navigation', 'wp-weixin-broadcast' ),
			'filter_items_list'     => __( 'Filter WeChat Broadcasts list', 'wp-weixin-broadcast' ),
		);
		$capabilities = array(
			'edit_post'          => 'edit_wechat_broadcast_message',
			'read_post'          => 'read_wechat_broadcast_message',
			'delete_post'        => 'delete_wechat_broadcast_message',
			'edit_posts'         => 'edit_wechat_broadcast_messages',
			'edit_others_posts'  => 'edit_others_wechat_broadcast_messages',
			'publish_posts'      => 'publish_wechat_broadcast_messages',
			'read_private_posts' => 'read_private_wechat_broadcast_messages',
		);
		$args         = array(
			'label'               => __( 'WeChat Broadcast', 'wp-weixin-broadcast' ),
			'description'         => __( 'A set of WeChat Materials to broadcast to followers', 'wp-weixin-broadcast' ),
			'labels'              => $labels,
			'supports'            => array( 'title' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => 'wechat-broadcast-menu',
			'menu_position'       => 90,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'rewrite'             => false,
			'capability_type'     => 'post',
			'capabilities'        => $capabilities,
			'map_meta_cap'        => true,
			'show_in_rest'        => false,
		);

		register_post_type( self::POST_TYPE, $args );
	}

	public function add_caps() {
		$roles = apply_filters(
			'wp_weixin_broadcast_broadcast_message_roles',
			array(
				'administrator',
				'editor',
			)
		);

		foreach ( $roles as $role ) {
			$caps = apply_filters( 'wp_weixin_broadcast_broadcast_message_caps', self::CAPS, $role );
			$role = get_role( $role );

			foreach ( $caps as $cap ) {
				$role->add_cap( $cap );
			}
		}
	}

	public function change_publish_meta_box() {
		global $post;

		if ( $post ) {
			$publish_callback_args = null;

			if ( post_type_supports( self::POST_TYPE, 'revisions' ) && 'auto-draft' !== $post->post_status ) {
				$revisions = wp_get_post_revisions( $post->ID );

				if ( 1 < count( $revisions ) ) {
					reset( $revisions );

					$publish_callback_args = array(
						'revisions_count' => count( $revisions ),
						'revision_id'     => key( $revisions ),
					);

					add_meta_box( 'revisionsdiv', __( 'Revisions' ), 'post_revisions_meta_box', null, 'normal', 'core' );
				}
			}
		}

		remove_meta_box( 'submitdiv', 'post', 'side' );
		add_meta_box(
			'submitdiv',
			__( 'Broadcast', 'wp-weixin-broadcast' ),
			'post_submit_meta_box',
			null,
			'side',
			'high'
		);
	}

	public function add_preview_button( $post ) {

		if ( self::POST_TYPE !== $post->post_type ) {

			return;
		}

		$broadcasted = ! empty( get_post_meta( $post->ID, 'wp_weixin_broadcast_msg_id', true ) );
		$latest_info = '';

		if ( $broadcasted ) {
			$latest_info = $this->get_latest_broadcast_info( $post->ID );
		}

		include WP_WEIXIN_BROADCAST_PLUGIN_PATH . 'inc/templates/admin/broadcast-preview-button.php';
	}

	public function add_meta_boxes() {
		$metaboxes = array(
			'content' => array(
				'title'    => __( 'Build Message', 'wp-weixin-broadcast' ),
				'context'  => 'normal',
				'callback' => array( $this, 'message_meta_box' ),
				'priority' => 'high',
			),
		);

		foreach ( $metaboxes as $id => $metabox ) {
			add_meta_box(
				$id,
				$metabox['title'],
				$metabox['callback'],
				self::POST_TYPE,
				$metabox['context'],
				$metabox['priority']
			);
		}
	}

	public function message_meta_box( $post ) {
		$items                     = get_post_meta( $post->ID, 'wp_weixin_broadcast_items', true );
		$items_count               = 0;
		$cards_data                = array();
		$preview_options           = get_option( 'wp_weixin_broadcast_preview_options' );
		$preview_followers_ids     = '';
		$preview_followers_id_type = 'wxname';
		$broadcast_to              = get_post_meta( $post->ID, 'wp_weixin_broadcast_target_type', true );
		$user_id                   = get_current_user_id();
		$tencent_agree_meta        = get_user_meta( $user_id, 'wp_weixin_broadcast_tencent_agree', true );
		$tencent_agree_url         = filter_input( INPUT_GET, 'tencent_agree' );
		$job_sent                  = (bool) get_post_meta( $post->ID, 'wp_weixin_broadcast_msg_data_id', true );
		$job_complete              = (bool) get_post_meta( $post->ID, 'wp_weixin_broadcast_job_complete', true );
		$deleted_items             = get_post_meta( $post->ID, 'wp_weixin_broadcast_deleted_items', true );

		if ( 1 === absint( $tencent_agree_url ) ) {
			update_user_meta( $user_id, 'wp_weixin_broadcast_tencent_agree', 1 );

			$tencent_agree_meta = 1;
		}

		if ( 1 !== absint( $tencent_agree_meta ) ) {
			$action = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_STRING );

			if ( $action ) {
				$slug = 'post.php?post=' . $post->ID . '&action=' . $action . '&tencent_agree=1';
			} else {
				$slug = 'post-new.php?post_type=wechat_broadcast_msg&tencent_agree=1';
			}

			$url_agree    = admin_url( $slug );
			$url_disagree = admin_url();
		}

		if ( ! $broadcast_to || empty( $broadcast_to ) ) {
			$broadcast_to = 'all';
		}

		if ( ! is_array( $deleted_items ) ) {
			$deleted_items = array( $deleted_items );
		}

		if ( $preview_options ) {

			if ( isset( $preview_options['preview_followers_ids'] ) && ! empty( $preview_options['preview_followers_ids'] ) ) {
				$preview_followers_ids = implode( ', ', $preview_options['preview_followers_ids'] );
			}

			if ( isset( $preview_options['preview_followers_id_type'] ) ) {
				$preview_followers_id_type = $preview_options['preview_followers_id_type'];
			}
		}

		if ( ! empty( $items ) ) {
			$item_ids = array_keys( $items );
			$deleted  = __( 'WARNING - ITEM DELETED', 'wp-weixin-broadcast' );
			$in_trash = __( '[TRASH] ', 'wp-weixin-broadcast' );

			foreach ( $item_ids as $item_id ) {
				$item               = get_post( absint( $item_id ) );
				$default_image_urls = apply_filters( 'wp_weixin_broadcast_default_pic_urls', array(
					'full'  => WP_WEIXIN_BROADCAST_PLUGIN_URL . 'images/placeholder-preview-full.png',
					'thumb' => WP_WEIXIN_BROADCAST_PLUGIN_URL . 'images/placeholder-preview-thumb.png',
				), $item );
				$deleted_image_urls = apply_filters( 'wp_weixin_broadcast_deleted_item_pic_urls', array(
					'full'  => WP_WEIXIN_BROADCAST_PLUGIN_URL . 'images/placeholder-deleted-full.png',
					'thumb' => WP_WEIXIN_BROADCAST_PLUGIN_URL . 'images/placeholder-deleted-thumb.png',
				), $item );

				$card = array(
					'id'       => $item_id,
					'title'    => $item->post_title,
					'type'     => $item->post_type,
					'output'   => apply_filters( 'wp_weixin_broadcast_card_output', 'card-article', $item_id ),
					'is_dirty' => false,
				);

				if ( ! $item || 'trash' === $item->post_status ) {
					$card['background_thumb'] = $deleted_image_urls['thumb'];
					$card['background_full']  = $deleted_image_urls['full'];
					$card['title']            = ( $item ) ? $in_trash . $item->post_title : $deleted;
					$card['is_dirty']         = true;
				} else {
					$thumb_cover              = get_the_post_thumbnail_url( $item, array( '48', '48' ) );
					$full_cover               = get_the_post_thumbnail_url( $item, array( '256', '144' ) );
					$card['background_thumb'] = ( $thumb_cover ) ? $thumb_cover : $default_image_urls['thumb'];
					$card['background_full']  = ( $full_cover ) ? $full_cover : $default_image_urls['full'];
				}

				$cards_data[] = $card;
			}
		}

		$cards_data = apply_filters( 'wp_weixin_broadcast_message_cards_data', $cards_data, $post );

		foreach ( self::$broadcastable_post_types as $key => $type ) {
			$count        = wp_count_posts( $type );
			$items_count += isset( $count->publish ) ? (int) $count->publish : 0;
		}

		$is_item_broadcastable     = in_array( WP_Weixin_Article::POST_TYPE, self::$broadcastable_post_types, true );
		$broadcast_allowed_methods = apply_filters(
			'wp_weixin_broadcast_target_types',
			array(
				array(
					'label' => __( 'All users', 'wp-weixin-broadcast' ),
					'value' => 'all',
				),
				array(
					'label' => __( 'Select by tag', 'wp-weixin-broadcast' ),
					'value' => 'tag',
				),
				array(
					'label' => __( 'WordPress WeChat users', 'wp-weixin-broadcast' ),
					'value' => 'users',
				),
			)
		);

		include WP_WEIXIN_BROADCAST_PLUGIN_PATH . 'inc/templates/admin/broadcast-message-builder.php';
	}

	public function broadcast_methods_settings( $post, $broadcast_to ) {
		$broadcast_to_tag      = get_post_meta( $post->ID, 'wp_weixin_broadcast_target_tag', true );
		$broadcast_to_wp_users = get_post_meta( $post->ID, 'wp_weixin_broadcast_target_users', true );

		if ( ! empty( $broadcast_to_tag ) ) {
			$tags = json_decode( get_transient( 'wp_weixin_broadcast_user_tags' ), true );

			if ( ! $tags || empty( $tags ) ) {
				$tags = $this->wechat->tags();

				if ( $tags && ! empty( $tags ) ) {
					$tags[0]['name'] = __( 'Starred', 'wp-weixin-broadcast' );
				}

				set_transient( 'wp_weixin_broadcast_user_tags', wp_json_encode( $tags, true ), 3600 );
			}

			foreach ( $tags as $index => $tag ) {

				if ( absint( $broadcast_to_tag ) === absint( $tag['id'] ) ) {
					$tag_value = $tag;

					break;
				}
			}
		}

		if ( ! empty( $broadcast_to_wp_users ) ) {

			foreach ( $broadcast_to_wp_users as $openid ) {
				$user_info[] = array( 'openid' => $openid );
			}

			$remote_info_results = json_decode( get_transient( 'wp_weixin_broadcast_remote_user_info' ), true );

			if ( ! $remote_info_results || empty( $remote_info_results ) ) {

				if ( 1 < count( $user_info ) ) {
					$remote_info_results = $this->wechat->user_batch( $user_info );
				} else {
					$remote_info_results = array( $this->wechat->user( $user_info[0]['openid'] ) );
				}

				set_transient( 'wp_weixin_broadcast_remote_user_info', wp_json_encode( $remote_info_results, true ), 3600 );
			}

			if ( $remote_info_results && ! empty( $remote_info_results ) ) {

				foreach ( $remote_info_results as $remote_user_info ) {

					if ( $remote_user_info['subscribe'] ) {
						$wp_user_values[] = array(
							'id'      => $remote_user_info['openid'],
							'text'    => $remote_user_info['nickname'],
							'gender'  => $remote_user_info['sex'],
							'city'    => $remote_user_info['city'],
							'country' => $remote_user_info['country'],
							'thumb'   => $remote_user_info['headimgurl'],
						);
					}
				}
			}
		}

		include WP_WEIXIN_BROADCAST_PLUGIN_PATH . 'inc/templates/admin/broadcast-message-builder-methods-settings.php';
	}

	public function send_ignore_reprint_html( $post ) {

		if ( self::POST_TYPE === $post->post_type ) {
			$send_ignore_reprint = $this->is_send_ignore_reprint( $post->ID );

			include WP_WEIXIN_BROADCAST_PLUGIN_PATH . 'inc/templates/admin/send-ignore-reprint.php';
		}
	}

	public function is_send_ignore_reprint( $post_id ) {

		if ( ! $post_id ) {
			global $post;

			$post_id = $post->ID;
		}

		$field = get_post_meta( $post_id, 'wp_weixin_broadcast_send_ignore_reprint', true );

		return (bool) $field;
	}

	public function save_broadcast_message( $post_id ) {

		if (
			( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
			self::POST_TYPE !== get_post_type( $post_id )
		) {

			return;
		}

		$nonce    = filter_input( INPUT_POST, 'wechat_broadcast_message_content_nonce', FILTER_SANITIZE_STRING );
		$item_ids = filter_input( INPUT_POST, 'wechat_broadcast_item_ids', FILTER_SANITIZE_STRING );

		if (
			! $nonce ||
			! wp_verify_nonce( $nonce, 'wechat_broadcast_message_content_nonce' ) ||
			false === $item_ids ||
			null === $item_ids
		) {

			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {

			return;
		}

		do_action( 'wp_weixin_broadcast_before_save_broadcast_message', $post_id );

		$this->broadcast_message_id = $post_id;

		$send_ignore_reprint = filter_input( INPUT_POST, 'wechat_broadcast_send_ignore_reprint', FILTER_SANITIZE_STRING );
		$preview_ids         = filter_input( INPUT_POST, 'wechat_broadcast_preview_followers_ids', FILTER_SANITIZE_STRING );
		$preview_id_type     = filter_input( INPUT_POST, 'wechat_broadcast_preview_followers_id_type', FILTER_SANITIZE_STRING );
		$doing_preview       = filter_input( INPUT_POST, 'wechat_broadcast_doing_preview', FILTER_VALIDATE_INT );
		$broadcast_to        = filter_input( INPUT_POST, 'wechat_broadcast_target_type', FILTER_SANITIZE_STRING );
		$targets             = filter_input( INPUT_POST, 'wechat_broadcast_target_' . $broadcast_to, FILTER_SANITIZE_STRING );
		$do_broadcast        = filter_input( INPUT_POST, 'wechat_message_do_broadcast', FILTER_SANITIZE_STRING );
		$message_type        = filter_input( INPUT_POST, 'wechat_message_type', FILTER_SANITIZE_STRING );
		$items_metadata      = $this->save_broadcast_items_metadata( $item_ids );
		$message             = false;

		$this->save_preview_options( $preview_ids, $preview_id_type );
		$this->save_send_ignore_reprint( $send_ignore_reprint );
		$this->save_broadcast_targets( $broadcast_to, $targets );
		$this->save_broadcast_message_type( $message_type );

		if ( ! absint( $doing_preview ) ) {

			if ( $this->has_broadcast_items_changed( $items_metadata ) ) {
				$message_type = empty( $message_type ) ? 'mpnews' : $message_type;
				$message      = $this->build_message( $item_ids, $message_type );
			}
		}

		do_action( 'wp_weixin_broadcast_after_save_broadcast_message', $post_id );

		if ( absint( $do_broadcast ) && $message ) {
			$this->do_broadcast( $broadcast_to, $message );
		}
	}

	public function do_broadcast( $target_type, $message ) {
		do_action( 'wp_weixin_broadcast_before_broadcast', $this->broadcast_message_id );

		$media_id         = get_post_meta( $this->broadcast_message_id, 'wp_weixin_broadcast_media_id', true );
		$clientmsgid      = $this->init_clientmsgid();
		$targets          = $this->get_broadcast_targets( $target_type );
		$target           = ( 'tag' !== $target_type && 'all' !== $target_type ) ? 'openids' : $target_type;
		$params           = array(
			'send_ignore_reprint' => $this->is_send_ignore_reprint(),
			'clientmsgid'         => $clientmsgid,
			'type'                => $message['type'],
			$target               => $targets,
		);
		$broadcast_result = $this->broadcast( $message['reference'], $params );

		$this->after_broadcast( $broadcast_result );
		$this->logger->log_broadcast_response(
			$this->broadcast_message_id,
			array( 'media_id' => $media_id ),
			$broadcast_result
		);
	}

	public function broadcast_content_delete( $post_id ) {

		if ( self::POST_TYPE !== get_post_type( $post_id ) ) {

			return;
		}

		$this->logger->delete_log_by_post_id( $post_id );
	}

	public function do_broadcast_scheduled( $post_id ) {
		do_action( 'wp_weixin_broadcast_before_scheduled_broadcast', $post_id );

		$this->broadcast_message_id = $post_id;
		$items_metadata             = $this->get_broadcast_items_metadata();

		if ( ! empty( $items_metadata ) ) {
			$media_id    = get_post_meta( $this->broadcast_message_id, 'wp_weixin_broadcast_media_id', true );
			$target_type = get_post_meta( $post_id, 'wp_weixin_broadcast_target_type', true );

			if ( $this->has_broadcast_items_changed( $items_metadata ) ) {
				$message_type = get_post_meta( $post_id, 'wp_weixin_broadcast_message_type', true );
				$message_type = empty( $message_type ) ? 'mpnews' : $message_type;
				$message      = $this->build_message( $post_ids, $message_type );
			}

			if ( $media_id ) {
				$clientmsgid      = $this->init_clientmsgid();
				$targets          = $this->get_broadcast_targets( $target_type );
				$target           = ( 'tag' !== $target_type && 'all' !== $target_type ) ? 'openids' : $target_type;
				$params           = array(
					'send_ignore_reprint' => $this->is_send_ignore_reprint(),
					'clientmsgid'         => $clientmsgid,
					'type'                => $message['type'],
					$target               => $targets,
				);
				$broadcast_result = $this->broadcast( $message['reference'], $params );

				$this->after_broadcast( $broadcast_result );
				$this->logger->log_broadcast_response( $post_id, array( 'media_id' => $media_id ), $broadcast_result, true );
			}
		}
	}

	public function no_duplicate( $types ) {
		$types[] = self::POST_TYPE;

		return $types;
	}

	public function remove_actions( $actions = array(), $post = null ) {

		if ( $post && self::POST_TYPE === $post->post_type ) {

			if ( isset( $actions['inline hide-if-no-js'] ) ) {
				unset( $actions['inline hide-if-no-js'] );
			}

			if ( isset( $actions['edit'] ) ) {
				unset( $actions['edit'] );
			}
		}

		return $actions;
	}

	public function current_screen_callback( $screen ) {

		if ( is_object( $screen ) && self::POST_TYPE === $screen->post_type ) {
			add_filter( 'gettext', array( $this, 'change_text' ), 99, 3 );
		}
	}

	public function remove_from_bulk_actions( $actions ) {
		unset( $actions['edit'] );

		return $actions;
	}

	public function change_text( $translated_text, $untranslated_text, $domain ) {

		if ( isset( self::$screen_alter_map[ $untranslated_text ] ) ) {
			$translated_text = self::$screen_alter_map[ $untranslated_text ];
		}

		return $translated_text;
	}

	public function updated_messages( $messages ) {
		$screen = get_current_screen();

		if ( is_object( $screen ) && self::POST_TYPE === $screen->post_type ) {
			global $post;

			/* translators: Publish box date format, see https://secure.php.net/date */
			$scheduled_date              = date_i18n( __( 'M j, Y @ H:i' ), strtotime( $post->post_date ) );
			$messages[ self::POST_TYPE ] = array(
				0  => '',
				1  => __( 'Broadcast updated.', 'wp-weixin-broadcast' ),
				4  => __( 'Broadcast updated.', 'wp-weixin-broadcast' ),
				/* translators: %s: date and time of the revision */
				6  => __( 'Broadcast published.', 'wp-weixin-broadcast' ),
				7  => __( 'Broadcast saved.', 'wp-weixin-broadcast' ),
				8  => __( 'Broadcast submitted.', 'wp-weixin-broadcast' ),
				/* translators: %s: date and time of the schedule */
				9  => sprintf( __( 'Broadcast scheduled for: %s.', 'wp-weixin-broadcast' ), '<strong>' . $scheduled_date . '</strong>' ),
				10 => __( 'Broadcast draft updated.', 'wp-weixin-broadcast' ),
			);
		}

		return $messages;
	}

	public function admin_notice() {
		global $post;

		$screen = get_current_screen();

		if ( ! is_object( $screen ) || self::POST_TYPE !== $screen->id ) {

			return;
		}

		if ( $post && self::POST_TYPE === $post->post_type ) {
			$class    = 'notice notice-error dirty-notice hidden';
			$message  = '<strong>' . __( 'Broadcast Message Inconsistent', 'wp-weixin-broadcast' ) . '</strong>';
			$message .= '<br/>' . __( 'The Broadcast Message has missing data (some items have either been deleted, sent to trash, or have a missing Cover Image).', 'wp-weixin-broadcast' );
			$message .= '<br/>' . __( 'Fix the inconsistencies before proceeding with Preview or sending the broadcast.', 'wp-weixin-broadcast' );

			if ( 'future' === $post->post_status ) {
				$class   = 'notice notice-warning';
				$message = __( '<strong>Schedule:</strong><br/>The WeChat Broadcast is currently scheduled and will be processed with the latest version of the items structure saved in the Broadcast Message.<br/>If some images included in the original items are missing during the broadcast, they will be replaced with a default image.<br/>If you do not want to proceed with the scheduled broadcast, use the "Move to Trash" link.', 'wp-weixin-broadcast' );
			}

			echo sprintf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message ); // WPCS: XSS ok
		}
	}

	public function post_columns( $columns ) {
		$pristine    = $columns;
		$new_columns = array(
			'last_action'        => __( 'Last Broadcast Log type', 'wp-weixin-broadcast' ),
			'last_action_result' => __( 'Log result', 'wp-weixin-broadcast' ),
		);

		$columns  = array_slice( $pristine, 0, 2, true );
		$columns += $new_columns;
		$columns += array_slice( $pristine, 2, count( $pristine ) - 1, true );

		return $columns;
	}

	public function post_column_data( $column, $post_id ) {
		$post_type = get_post_type( $post_id );

		if ( self::POST_TYPE !== $post_type ) {

			return;
		}

		$action_meta  = get_post_meta( $post_id, 'wp_weixin_broadcast_last_operation', true );
		$action       = __( 'N/A', 'wp-weixin-broadcast' );
		$result       = $action;
		$result_class = '';
		$action_class = '';

		if ( is_array( $action_meta ) ) {

			if ( isset( $action_meta['action'] ) && isset( self::$log_types[ $action_meta['action'] ] ) ) {
				$action       = self::$log_types[ $action_meta['action'] ];
				$action_class = 'wp-weixin-broadcast-' . $action_meta['action'] . '-action';
			}

			if ( isset( $action_meta['result'] ) && isset( self::$log_statuses[ $action_meta['result'] ] ) ) {
				$result       = esc_html( self::$log_statuses[ $action_meta['result'] ] );
				$result_class = 'status ' . $action_meta['result'];
			}
		}

		switch ( $column ) {
			case 'last_action':
				echo '<span class="' . esc_attr( $action_class ) . '">' . esc_html( $action ) . '</span>';
				break;
			case 'last_action_result':
				echo '<span class="' . esc_attr( $result_class ) . '">' . esc_html( $result ) . '</span>';
				break;
		}
	}

	public function default_post_title( $data ) {

		if ( self::POST_TYPE === $data['post_type'] ) {

			if ( empty( trim( $data['post_title'] ) ) ) {
				global $wpdb;

				$result = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT COUNT( * ) AS num_posts FROM {$wpdb->posts} WHERE post_type = %s AND post_status != 'trash' AND post_status != 'auto-draft'",
						self::POST_TYPE
					)
				);
				$count  = $result->num_posts;

				// translators: %s is the Broadcast number
				$data['post_title'] = sprintf( __( 'Broadcast #%s', 'wp-weixin-broadcast' ), $count + 1 );
			}
		}

		return $data;
	}

	public function get_broadcastable_items_json() {
		$nonce  = filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_STRING );
		$term   = filter_input( INPUT_POST, 'q', FILTER_SANITIZE_STRING );
		$page   = filter_input( INPUT_POST, 'page', FILTER_VALIDATE_INT );
		$return = array( 'items' => array() );

		if ( ! wp_verify_nonce( $nonce, 'wechat_broadcast_message_content_nonce' ) ) {
			$error = new WP_Error( __METHOD__, __( 'Invalid parameters ', 'wp-weixin' ) . $nonce );

			wp_send_json_error( $error );
		}

		$result = new WP_Query(
			array(
				's'              => $term,
				'post_type'      => self::$broadcastable_post_types,
				'post_status'    => 'publish',
				'orderby'        => 'date',
				'posts_per_page' => 50,
				'paged'          => $page,
				'offset'         => 50 * ( $page - 1 ),
			)
		);

		if ( $result->have_posts() ) {

			while ( $result->have_posts() ) {
				$result->the_post();

				$thumb_cover        = get_the_post_thumbnail_url( $result->post, array( '48', '48' ) );
				$full_cover         = get_the_post_thumbnail_url( $result->post, array( '256', '144' ) );
				$default_image_urls = apply_filters( 'wp_weixin_broadcast_default_pic_urls', array(
					'full'  => WP_WEIXIN_BROADCAST_PLUGIN_URL . 'images/placeholder-preview-full.png',
					'thumb' => WP_WEIXIN_BROADCAST_PLUGIN_URL . 'images/placeholder-preview-thumb.png',
				), $result->post->ID );
				$return['items'][]  = array(
					'id'         => $result->post->ID,
					'title'      => $result->post->post_title,
					'type'       => $result->post->post_type,
					'output'     => apply_filters( 'wp_weixin_broadcast_card_output', 'card-article', $result->post->ID ),
					'thumbCover' => ( $thumb_cover ) ? $thumb_cover : $default_image_urls['thumb'],
					'fullCover'  => ( $full_cover ) ? $full_cover : $default_image_urls['full'],
				);
			}
		}

		$return['more'] = ( $page < $result->max_num_pages );

		wp_send_json_success( $return );
	}

	public function get_remote_group_tags_json() {
		$nonce  = filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_STRING );
		$term   = filter_input( INPUT_POST, 'q', FILTER_SANITIZE_STRING );
		$tags   = json_decode( get_transient( 'wp_weixin_broadcast_user_tags' ), true );
		$return = array();

		if ( ! wp_verify_nonce( $nonce, 'wechat_broadcast_message_content_nonce' ) ) {
			$error = new WP_Error( __METHOD__, __( 'Invalid parameters ', 'wp-weixin' ) . $nonce );

			wp_send_json_error( $error );
		}

		if ( ! $tags || empty( $tags ) ) {
			wp_weixin_ajax_safe();

			$tags = $this->wechat->tags();

			if ( $tags && ! empty( $tags ) ) {
				$tags[0]['name'] = __( 'Starred', 'wp-weixin-broadcast' );
			}

			set_transient( 'wp_weixin_broadcast_user_tags', wp_json_encode( $tags, true ), 3600 );
		}

		if ( $term && ! empty( $term ) && ! empty( $tags ) ) {

			foreach ( $tags as $index => $tag ) {

				if ( false !== strpos( trim( strtolower( $tag['name'] ) ), trim( strtolower( $term ) ) ) ) {
					$return[] = $tag;
				}
			}
		} else {
			$return = $tags;
		}

		wp_send_json_success( $return );
	}

	public function get_wp_wechat_users_json() {
		$nonce     = filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_STRING );
		$term      = filter_input( INPUT_POST, 'q', FILTER_SANITIZE_STRING );
		$page      = filter_input( INPUT_POST, 'page', FILTER_VALIDATE_INT );
		$user_info = array();
		$return    = array( 'users' => array() );

		if ( ! wp_verify_nonce( $nonce, 'wechat_broadcast_message_content_nonce' ) ) {
			$error = new WP_Error( __METHOD__, __( 'Invalid parameters ', 'wp-weixin' ) . $nonce );

			wp_send_json_error( $error );
		}

		add_action( 'pre_user_query', array( $this, 'load_meta' ) );

		$user_query = new WP_User_Query(
			array(
				'search'         => '*' . $term . '*',
				'search_columns' => array(
					'display_name',
					'user_email',
				),
				'meta_key'       => 'wx_openid-' . get_current_blog_id(),
				'number'         => 100,
				'paged'          => $page,
				'offset'         => 100 * ( $page - 1 ),
			)
		);

		foreach ( $user_query->get_results() as $user ) {
			$user_info[] = array( 'openid' => $user->openid );
		}

		remove_action( 'pre_user_query', array( $this, 'load_meta' ) );
		wp_weixin_ajax_safe();

		$remote_info_results = $this->wechat->user_batch( $user_info );

		if ( $remote_info_results && ! empty( $remote_info_results ) ) {

			foreach ( $remote_info_results as $remote_user_info ) {

				if ( $remote_user_info['subscribe'] ) {
					$return['users'][] = array(
						'id'      => $remote_user_info['openid'],
						'text'    => $remote_user_info['nickname'],
						'gender'  => $remote_user_info['sex'],
						'city'    => $remote_user_info['city'],
						'country' => $remote_user_info['country'],
						'thumb'   => $remote_user_info['headimgurl'],
					);
				}
			}
		}

		$return['more'] = ( $page < ceil( $user_query->get_total() / 100 ) );

		wp_send_json_success( $return );
	}

	public function load_meta( $query ) {
		global $wpdb;

		$query->query_fields .= ', openid.meta_value AS openid';
		$query->query_from   .= ' LEFT JOIN ' . $wpdb->usermeta . ' openid ON ' . $wpdb->users . '.ID = ';
		$query->query_from   .= 'openid.user_id and openid.meta_key = \'wx_openid-' . get_current_blog_id() . '\'';
	}

	public function do_preview() {
		$nonce                     = filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_STRING );
		$post_ids_string           = filter_input( INPUT_POST, 'ids', FILTER_SANITIZE_STRING );
		$post_id                   = filter_input( INPUT_POST, 'postId', FILTER_SANITIZE_STRING );
		$post_id                   = is_numeric( $post_id ) ? absint( $post_id ) : false;
		$post_ids                  = explode( ',', $post_ids_string );
		$preview_followers_ids     = filter_input( INPUT_POST, 'previewFollowerIds', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY );
		$preview_followers_id_type = filter_input( INPUT_POST, 'previewFollowerIdType', FILTER_SANITIZE_STRING );
		$message_type              = filter_input( INPUT_POST, 'wechat_message_type', FILTER_SANITIZE_STRING );
		$return                    = array();

		if ( ! wp_verify_nonce( $nonce, 'wechat_broadcast_message_content_nonce' ) ) {
			$error = new WP_Error( __METHOD__, __( 'Invalid parameters ', 'wp-weixin' ) . $nonce );

			wp_send_json_error( $error );
		}

		if ( ! $post_id || ! get_post( $post_id ) ) {
			$error = new WP_Error( __METHOD__, __( 'Invalid parameters: invalid post_id ', 'wp-weixin-broadcast' ) . $post_id );

			wp_send_json_error( $error );
		}

		if ( ! empty( $post_ids ) ) {

			foreach ( $post_ids as $index => $maybe_id ) {

				if ( ! is_numeric( $maybe_id ) ) {
					$error = new WP_Error( __METHOD__, __( 'Invalid parameters: invalid post_ids string', 'wp-weixin-broadcast' ) );

					wp_send_json_error( $error );
				} else {
					$post_ids[ $index ] = absint( $maybe_id );
				}
			}
		} else {
			$error = new WP_Error( __METHOD__, __( 'Invalid parameters: empty post_ids string', 'wp-weixin-broadcast' ) );

			wp_send_json_error( $error );
		}

		wp_weixin_ajax_safe();
		do_action( 'wp_weixin_broadcast_before_preview', $post_id );

		$this->broadcast_message_id = $post_id;
		$message_type               = empty( $message_type ) ? 'mpnews' : $message_type;

		$message   = $this->build_message( $post_ids, $message_type );
		$responses = $this->preview(
			$message['reference'],
			$preview_followers_ids,
			$message_type,
			$preview_followers_id_type
		);

		$this->save_preview_options( implode( ',', $preview_followers_ids ), $preview_followers_id_type );
		$this->after_preview( $responses );
		$this->logger->log_preview_responses( $this->broadcast_message_id, $message, $responses );

		wp_send_json_success( $responses );
	}

	protected function build_message( $post_ids, $type ) {

		if ( method_exists( $this, 'build_' . $type . '_message' ) ) {
			$args = array( $post_ids );

			return call_user_func_array( array( $this, 'build_' . $type . '_message' ), $args );
		} else {

			return $this->build_mpnews_message( $post_ids );
		}
	}

	public function broadcast_status() {
		$nonce   = filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_STRING );
		$post_id = filter_input( INPUT_POST, 'postId', FILTER_SANITIZE_STRING );
		$post_id = is_numeric( $post_id ) ? absint( $post_id ) : false;

		if ( ! wp_verify_nonce( $nonce, 'wechat_broadcast_message_content_nonce' ) ) {
			$error = new WP_Error( __METHOD__, __( 'Invalid parameters ', 'wp-weixin' ) . $nonce );

			wp_send_json_error( $error );
		}

		if ( ! $post_id || ! get_post( $post_id ) ) {
			$error = new WP_Error( __METHOD__, __( 'Invalid parameters: invalid post_id ', 'wp-weixin-broadcast' ) . $post_id );

			wp_send_json_error( $error );
		}

		wp_weixin_ajax_safe();

		$msg_id = get_post_meta( $post_id, 'wp_weixin_broadcast_msg_id', true );
		$status = '';

		if ( ! empty( $msg_id ) ) {
			$response = $this->wechat->mass_check_status( $msg_id );
			$status   = isset( $response['msg_status'] ) ? $response['msg_status'] : '';
		}

		update_post_meta( $post_id, 'wp_weixin_broadcast_status', $status );

		$info = $this->get_latest_broadcast_info( $post_id );

		wp_send_json_success( $info );
	}

	public function delete_remote() {
		$nonce   = filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_STRING );
		$post_id = filter_input( INPUT_POST, 'postId', FILTER_SANITIZE_STRING );
		$item_id = filter_input( INPUT_POST, 'itemId', FILTER_SANITIZE_STRING );
		$index   = filter_input( INPUT_POST, 'index', FILTER_SANITIZE_STRING );

		if ( ! wp_verify_nonce( $nonce, 'wechat_broadcast_message_content_nonce' ) ) {
			$error = new WP_Error( __METHOD__, __( 'Invalid parameters ', 'wp-weixin' ) . $nonce );

			wp_send_json_error( $error );
		}

		if ( ( ! $post_id || ! get_post( $post_id ) ) ) {
			$error = new WP_Error( __METHOD__, __( 'Invalid parameters: invalid post_id ', 'wp-weixin-broadcast' ) . $post_id );

			wp_send_json_error( $error );
		}

		if ( -1 !== (int) $item_id && ( ! $item_id || ! get_post( $item_id ) ) ) {
			$error = new WP_Error( __METHOD__, __( 'Invalid parameters: invalid item_id ', 'wp-weixin-broadcast' ) . $item_id );

			wp_send_json_error( $error );
		}

		if ( -1 === (int) $item_id && 0 !== absint( $index ) ) {
			$error = new WP_Error( __METHOD__, __( 'Invalid parameters: invalid index ', 'wp-weixin-broadcast' ) . $index );

			wp_send_json_error( $error );
		}

		$deleted = get_post_meta( $post_id, 'wp_weixin_broadcast_deleted_items', true );
		$msg_id  = get_post_meta( $post_id, 'wp_weixin_broadcast_msg_id', true );

		if ( -1 === (int) $item_id ) {
			$items   = get_post_meta( $post_id, 'wp_weixin_broadcast_items', true );
			$deleted = is_array( $items ) ? array_keys( get_post_meta( $post_id, 'wp_weixin_broadcast_items', true ) ) : null;
		} else {
			$deleted[] = $item_id;
		}

		wp_weixin_ajax_safe();
		do_action( 'wp_weixin_broadcast_before_delete_remote', $post_id );

		$response = $this->delete( $index, $msg_id, $post_id );

		if ( $response ) {
			update_post_meta( $post_id, 'wp_weixin_broadcast_deleted_items', $deleted );
		}

		do_action( 'wp_weixin_broadcast_after_delete_remote', $post_id, $response );

		wp_send_json_success( $response );
	}

	public function handle_wechat_response( $request_data ) {

		if ( 'MASSSENDJOBFINISH' !== $request_data['event'] ) {

			return;
		}

		do_action( 'wp_weixin_broadcast_before_handle_job_response', $request_data );

		$msg_id = $request_data['msgid'];
		$query  = new WP_Query(
			array(
				'post_type'  => self::POST_TYPE,
				'meta_query' => array(
					array(
						'key'   => 'wp_weixin_broadcast_msg_id',
						'value' => $msg_id,
					),
				),
			)
		);

		$post    = ! empty( $query->posts ) ? reset( $query->posts ) : false;
		$post_id = ( $post ) ? $post->ID : false;

		if ( ! $post_id ) {
			WP_Weixin::log( 'Something went wrong: post ID not found for msgid "' . $msg_id . '" ; the associated WeChat Broadcast post does not exist or does not have a wp_weixin_broadcast_msg_id meta.' );
			do_action( 'wp_weixin_broadcast_job_response_broadcast_message_not_found', $request_data );

			return;
		}

		$status = $this->logger->log_job_complete_response( $post_id, $request_data );

		update_post_meta(
			$post_id,
			'wp_weixin_broadcast_last_operation',
			array(
				'action' => 'remote_job_result',
				'result' => $status,
			)
		);

		update_post_meta(
			$post_id,
			'wp_weixin_broadcast_job_complete',
			true
		);

		do_action( 'wp_weixin_broadcast_after_handle_job_response', $request_data, $post_id );
	}

	/*******************************************************************
	 * Protected methods
	 *******************************************************************/

	protected function build_mpnews_message( $item_ids ) {
		do_action( 'wp_weixin_broadcast_before_build_articles_message', $this->broadcast_message_id, $item_ids );

		$items                  = array( 'articles' => array() );
		$default_cover_media_id = $this->get_default_cover_media_id();
		$message                = array();
		$error                  = false;

		foreach ( $item_ids as $item_id ) {
			$post          = get_post( $item_id );
			$image_sources = wp_get_attachment_image_src(
				get_post_thumbnail_id( $item_id ),
				array( '900', '500' ),
				false,
				''
			);

			if ( $image_sources ) {
				$cover_id = $this->get_cover_media_id( $item_id, $image_sources );
			} else {
				$cover_id = $default_cover_media_id;
			}

			$items['articles'][] = array(
				'thumb_media_id'        => $cover_id,
				'author'                => get_post_meta( $post->ID, 'wp_weixin_broadcast_article_author', true ),
				'title'                 => $post->post_title,
				'content_source_url'    => get_post_meta( $post->ID, 'wp_weixin_broadcast_article_origin_url', true ),
				'content'               => $this->replace_images( do_shortcode( $post->post_content ) ),
				'digest'                => substr( $post->post_excerpt, 0, 64 ),
				'show_cover_pic'        => (int) WP_Weixin_Article::is_show_cover_image( $post->ID ),
				'need_open_comment'     => (int) WP_Weixin_Article::is_need_open_comment( $post->ID ),
				'only_fans_can_comment' => (int) WP_Weixin_Article::is_only_fans_can_comment( $post->ID ),
			);
		}

		$items                = apply_filters( 'wp_weixin_broadcast_articles_message', $items, $item_ids );
		$result               = $this->wechat->add_rich_media_asset( $items );
		$message['structure'] = $items;
		$message['type']      = 'mpnews';
		$message['reference'] = array(
			'media_id' => isset( $result['media_id'] ) ? $result['media_id'] : false,
		);

		$media_id = get_post_meta( $this->broadcast_message_id, 'wp_weixin_broadcast_media_id', true );

		if ( $media_id ) {
			$this->wechat->delete_asset( $media_id );
		}

		if ( ! isset( $result['media_id'] ) ) {
			$error = $this->wechat->getError();

			$this->logger->log_rich_media_error( $error, $this->broadcast_message_id );
		}

		update_post_meta( $this->broadcast_message_id, 'wp_weixin_broadcast_media_id', $result['media_id'] );

		if ( ! $error ) {
			update_post_meta(
				$this->broadcast_message_id,
				'wp_weixin_broadcast_message_structure',
				$message['structure']
			);
		} else {
			update_post_meta(
				$this->broadcast_message_id,
				'wp_weixin_broadcast_message_structure',
				false
			);
		}

		do_action( 'wp_weixin_broadcast_after_build_articles_message', $this->broadcast_message_id, $item_ids, $message );

		return $message;
	}

	protected function save_broadcast_message_type( $type ) {
		update_post_meta( $this->broadcast_message_id, 'wp_weixin_broadcast_message_type', $type );
	}

	protected function save_preview_options( $follower_ids = '', $ids_type = 'wxname' ) {
		$preview_options = array(
			'preview_followers_ids'     => empty( $follower_ids ) ? array() : array_map(
				'trim',
				explode( ',', $follower_ids )
			),
			'preview_followers_id_type' => empty( $ids_type ) ? 'wxname' : $ids_type,
		);

		update_option( 'wp_weixin_broadcast_preview_options', $preview_options, false );

		return $preview_options;
	}

	protected function save_target_all( $unused ) {
		update_post_meta( $this->broadcast_message_id, 'wp_weixin_broadcast_target_type', 'all' );
	}

	protected function save_target_tag( $tag = '' ) {
		update_post_meta( $this->broadcast_message_id, 'wp_weixin_broadcast_target_type', 'tag' );
		update_post_meta( $this->broadcast_message_id, 'wp_weixin_broadcast_target_tag', $tag );

		return $tag;
	}

	protected function get_target_tag() {

		return get_post_meta( $this->broadcast_message_id, 'wp_weixin_broadcast_target_tag', true );
	}

	protected function save_target_users( $wp_users = '' ) {
		$wp_users = empty( trim( $wp_users ) ) ? array() : explode( ',', $wp_users );

		foreach ( $wp_users as $index => $maybe_id ) {
			$wp_users[ $index ] = trim( $maybe_id );
		}

		update_post_meta( $this->broadcast_message_id, 'wp_weixin_broadcast_target_type', 'users' );
		update_post_meta( $this->broadcast_message_id, 'wp_weixin_broadcast_target_users', $wp_users );
		delete_transient( 'wp_weixin_broadcast_remote_user_info' );

		return $wp_users;
	}

	protected function get_target_users() {
		$wp_users = get_post_meta( $this->broadcast_message_id, 'wp_weixin_broadcast_target_users', true );

		if ( empty( $wp_users ) ) {
			add_action( 'pre_user_query', array( $this, 'load_meta' ) );

			$user_query = new WP_User_Query(
				array(
					'meta_key' => 'wx_openid-' . get_current_blog_id(),
					'number'   => 10000,
				)
			);

			foreach ( $user_query->get_results() as $user ) {
				$wp_users[] = $user->openid;
			}

			remove_action( 'pre_user_query', array( $this, 'load_meta' ) );
		}

		return $wp_users;
	}

	protected function save_send_ignore_reprint( $send_ignore_reprint = false ) {

		if ( $send_ignore_reprint ) {
			update_post_meta(
				$this->broadcast_message_id,
				'wp_weixin_broadcast_send_ignore_reprint',
				(bool) $send_ignore_reprint
			);
		} else {
			update_post_meta(
				$this->broadcast_message_id,
				'wp_weixin_broadcast_send_ignore_reprint',
				false
			);
		}

		return (bool) $send_ignore_reprint;
	}

	protected function save_broadcast_items_metadata( $broadcast_items_ids ) {
		$item_ids       = explode( ',', $broadcast_items_ids );
		$items_metadata = array();

		if ( ! empty( $item_ids ) ) {
			$items_metadata = $this->build_broadcast_items_metadata( $item_ids );
		}

		update_post_meta(
			$this->broadcast_message_id,
			'wp_weixin_broadcast_items',
			$items_metadata
		);

		return $items_metadata;
	}

	protected function get_broadcast_items_metadata() {
		$items_metadata = get_post_meta( $this->broadcast_message_id, 'wp_weixin_broadcast_items', true );

		if ( ! empty( $old_items_metadata ) ) {

			return $this->build_broadcast_items_metadata( array_keys( $items_metadata ) );
		}

		return array();
	}

	protected function build_broadcast_items_metadata( $item_ids ) {
		$items_metadata = array();

		foreach ( $item_ids as $index => $item_id ) {
			$item                       = get_post( $item_id );
			$items_metadata[ $item_id ] = array(
				'last_recorded_post_date' => $item->post_modified,
			);
		}

		return $items_metadata;
	}

	protected function has_broadcast_items_changed( $broadcast_items_metadata ) {
		$old_items_metadata = get_post_meta( $this->broadcast_message_id, 'wp_weixin_broadcast_items', true );

		return ( $broadcast_items_metadata !== $old_items_metadata );
	}

	protected function init_clientmsgid() {
		$clientmsgid = get_post_meta( 'wp_weixin_broadcast_clientmsgid', true );

		if ( ! $clientmsgid ) {
			$clientmsgid = substr( $this->broadcast_message_id . '-' . current_time( 'timestamp' ), 0, 64 );

			update_post_meta( $this->broadcast_message_id, 'wp_weixin_broadcast_clientmsgid', $clientmsgid );
		}

		return $clientmsgid;
	}

	protected function save_broadcast_targets( $target_type, $targets = '' ) {

		if ( method_exists( $this, 'save_target_' . $target_type ) ) {
			$args = array( $targets );

			call_user_func_array( array( $this, 'save_target_' . $target_type ), $args );
		} else {
			do_action( 'wp_weixin_broadcast_save_targets', $this->broadcast_message_id, $targets, $target_type );
		}
	}

	protected function get_broadcast_targets( $target_type ) {

		if ( method_exists( $this, 'get_target_' . $target_type ) ) {
			$targets = call_user_func( array( $this, 'get_target_' . $target_type ) );
		} else {
			$targets = apply_filters( 'wp_weixin_broadcast_get_targets', array(), $this->broadcast_message_id, $target_type );
		}

		return $targets;
	}

	protected function after_preview( $responses ) {
		$action_result        = 'success';
		$failed_actions_count = 0;

		foreach ( $responses as $response ) {

			if ( true !== $response['result'] ) {
				$action_result = 'warning';

				$failed_actions_count ++;
			}
		}

		if ( count( $responses ) === $failed_actions_count ) {
			$action_result = 'failure';
		}

		update_post_meta(
			$this->broadcast_message_id,
			'wp_weixin_broadcast_last_operation',
			array(
				'action' => 'preview',
				'result' => $action_result,
			)
		);

		do_action( 'wp_weixin_broadcast_after_preview', $this->broadcast_message_id, $responses );
	}

	protected function after_broadcast( $broadcast_result ) {
		$action_result = ( false === $broadcast_result['result'] ) ? 'failure' : 'success';

		if ( isset( $broadcast_result['response']['msg_id'] ) ) {
			update_post_meta(
				$this->broadcast_message_id,
				'wp_weixin_broadcast_msg_id',
				$broadcast_result['response']['msg_id']
			);
		}

		if ( isset( $broadcast_result['response']['msg_data_id'] ) ) {
			update_post_meta(
				$this->broadcast_message_id,
				'wp_weixin_broadcast_msg_data_id',
				$broadcast_result['response']['msg_data_id']
			);
		}

		update_post_meta(
			$this->broadcast_message_id,
			'wp_weixin_broadcast_last_operation',
			array(
				'action' => 'direct_broadcast',
				'result' => $action_result,
			)
		);

		do_action( 'wp_weixin_broadcast_after_broadcast', $this->broadcast_message_id, $broadcast_result );
	}

	protected function get_latest_broadcast_info( $post_id ) {
		$meta_value = get_post_meta( $post_id, 'wp_weixin_broadcast_status', true );
		$class      = '';
		$text       = '';

		switch ( $meta_value ) {
			case 'SEND_SUCCESS':
				$class = 'success';
				$text  = __( 'SEND SUCCESS', 'wp-weixin-broadcast' );
				break;
			case 'SENDING':
				$class = 'warning';
				$text  = __( 'SENDING', 'wp-weixin-broadcast' );
				break;
			case 'SEND_FAIL':
				$class = 'failure';
				$text  = __( 'SEND FAILED', 'wp-weixin-broadcast' );
				break;
			case 'DELETE':
				$class = 'warning';
				$text  = __( 'DELETED', 'wp-weixin-broadcast' );
				break;
			default:
				$class = 'warning';
				$text  = __( 'UNKNOWN', 'wp-weixin-broadcast' );
		}

		return array(
			'class' => $class,
			'text'  => $text,
		);
	}

	protected function preview( $message, $follower_ids, $message_type = 'mpnews', $id_type = 'wxname' ) {
		$responses = array();

		foreach ( $follower_ids as $id ) {
			$response    = $this->wechat->mass_preview( $message, $message_type, $id, $id_type );
			$responses[] = array(
				'result'   => false !== $response,
				'response' => $response,
				'error'    => $this->wechat->getError(),
				'id'       => $id,
				'id_type'  => $id_type,
			);
		}

		return $responses;
	}

	protected function broadcast( $message, $params = array() ) {
		$response          = false;
		$type              = ( isset( $params['type'] ) && $params['type'] ) ? $params['type'] : 'mpnews';
		$target            = 'all';
		$wechat_sdk_method = 'mass_to_all_or_tag';

		if ( isset( $params['tag'] ) ) {
			$target = $params['tag'];
		} elseif ( isset( $params['openids'] ) ) {
			$wechat_sdk_method = 'mass_to_users';
			$target            = $params['openids'];
		}

		$response = call_user_func_array(
			array(
				$this->wechat,
				$wechat_sdk_method,
			),
			array(
				$message,
				$type,
				$target,
				$params['send_ignore_reprint'],
				$params['clientmsgid'],
			)
		);

		return array(
			'result'   => false !== $response,
			'response' => $response,
			'error'    => $this->wechat->getError(),
			'params'   => $params,
		);
	}

	protected function delete( $index, $msg_id, $post_id ) {
		$response = $this->wechat->mass_delete( $msg_id, absint( $index ) );
		$result   = array(
			'result'   => false !== $response,
			'response' => $response,
			'error'    => $this->wechat->getError(),
			'params'   => array(
				'index'  => absint( $index ),
				'msg_id' => $msg_id,
			),
		);

		if ( $error ) {
			WP_Weixin::log( $response );
			WP_Weixin::log( $result['error'] );
		}

		$this->log_delete_response( $result, $post_id );

		return $result;
	}

	protected function get_default_cover_media_id() {
		$default_cover_media_id = get_option( 'wp_weixin_broadcast_default_cover_media_id' );
		$default_cover_path     = apply_filters(
			'wp_weixin_broadcast_default_cover_path',
			WP_WEIXIN_BROADCAST_PLUGIN_PATH . 'images/wp-weixin-broadcast-cover-placeholder.png',
			$default_cover_media_id
		);

		if ( ! empty( $default_cover_media_id ) && is_string( $default_cover_media_id ) ) {
			$this->wechat->get_asset( $default_cover_media_id );

			$error = $this->wechat->getError();

			if ( $error && 40007 === absint( $error['code'] ) ) {
				$default_cover_media_id = $this->update_default_cover_media_id( $default_cover_path );
			}
		} else {
			$default_cover_media_id = $this->update_default_cover_media_id( $default_cover_path );
		}

		return $default_cover_media_id;
	}

	protected function update_default_cover_media_id( $default_cover_path ) {
		global $post;

		$result                 = $this->wechat->add_file_asset( $default_cover_path, 'image' );
		$default_cover_media_id = isset( $result['media_id'] ) ? $result['media_id'] : false;

		if ( ! $default_cover_media_id ) {
			$error = $this->wechat->getError();

			$this->logger->log_add_image_error( $error, $default_cover_path, $this->broadcast_message_id );
		} else {
			update_option( 'wp_weixin_broadcast_default_cover_media_id', $default_cover_media_id, false );
		}

		return $default_cover_media_id;
	}

	protected function get_cover_media_id( $post_id, $image_sources ) {
		$cover_media_id = get_post_meta( get_post_thumbnail_id( $post_id ), 'wp_weixin_broadcast_media_id', true );

		if ( ! empty( $cover_media_id ) && is_string( $cover_media_id ) ) {
			$this->wechat->get_asset( $cover_media_id );

			$error = $this->wechat->getError();

			if ( $error && 40007 === absint( $error['code'] ) ) {
				$cover_path     = $this->convert_image_url_to_local_path( $image_sources[0] );
				$cover_media_id = $this->update_cover_media_id( $cover_path, $post_id );
			}
		} else {
			$cover_path     = $this->convert_image_url_to_local_path( $image_sources[0] );
			$cover_media_id = $this->update_cover_media_id( $cover_path, $post_id );
		}

		return $cover_media_id;
	}

	protected function update_cover_media_id( $cover_path, $post_id ) {
		$result         = $this->wechat->add_file_asset( $cover_path, 'image' );
		$cover_media_id = isset( $result['media_id'] ) ? $result['media_id'] : false;

		if ( ! $cover_media_id ) {
			$error = $this->wechat->getError();

			$this->logger->log_add_image_error( $error, $cover_path, $this->broadcast_message_id );
		} else {
			update_post_meta( get_post_thumbnail_id( $post_id ), 'wp_weixin_broadcast_media_id', $cover_media_id );
		}

		return $cover_media_id;
	}

	protected function replace_images( $content ) {
		preg_match_all( '/<img.*? src=\"?(.*?\.(jpg|jpeg|gif|bmp|png))\"?.*?>/i', $content, $match );

		if ( ! empty( $match[1] ) ) {

			foreach ( $match[1] as $image ) {

				$image_path = $this->convert_image_url_to_local_path( $image );
				$result     = $this->wechat->add_file_asset( $image_path, 'news_image' );

				if ( is_array( $result ) && isset( $result['url'] ) ) {
					$content = str_replace( $image, $result['url'], $content );
				} else {
					$content = false;
					$error   = $this->wechat->getError();

					$this->logger->log_add_temporary_media_error( $error, $image_path, $this->broadcast_message_id );
				}
			}
		}

		return $content;
	}

	protected function convert_image_url_to_local_path( $image ) {
		$url_info  = wp_parse_url( $image );
		$image_url = isset( $url_info['host'] ) ? $image : get_home_url( $url_info['path'] );

		if ( strstr( $image_url, home_url() ) ) {
			$image_path = $_SERVER['DOCUMENT_ROOT'] . $url_info['path'];
		} else {
			$image_path = '';

			preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $image, $matches );

			if ( ! $matches ) {
				$image_path = new WP_Error( 'image_sideload_failed', __( 'Invalid image URL' ) );
			}

			$file_array             = array();
			$file_array['name']     = basename( $matches[0] );
			$file_array['tmp_name'] = download_url( $image );

			if ( is_wp_error( $file_array['tmp_name'] ) ) {
				$image_path = $file_array['tmp_name'];
			}

			if ( $image_path && ! is_wp_error( $image_path ) ) {
				$time      = current_time( 'mysql' );
				$overrides = array( 'test_form' => false );
				$file      = wp_handle_sideload( $file_array, $overrides, $time );

				if ( isset( $file['error'] ) ) {
					$image_path = new WP_Error( 'upload_error', $file['error'] );
				} else {
					$url        = $file['url'];
					$type       = $file['type'];
					$file       = $file['file'];
					$title      = preg_replace( '/\.[^.]+$/', '', basename( $file ) );
					$content    = '';
					$image_meta = wp_read_image_metadata( $file );

					if ( $image_meta ) {

						if ( trim( $image_meta['title'] ) && ! is_numeric( sanitize_title( $image_meta['title'] ) ) ) {
							$title = $image_meta['title'];
						}

						if ( trim( $image_meta['caption'] ) ) {
							$content = $image_meta['caption'];
						}
					}

					if ( isset( $desc ) ) {
						$title = $desc;
					}

					$id = wp_insert_attachment( $attachment, $file );

					if ( is_wp_error( $id ) ) {
						$image_path = $id;
					} else {
						$src        = wp_get_attachment_url( $id );
						$image_path = $this->convert_image_url_to_local_path( $src );
					}
				}
			}

			if ( is_wp_error( $image_path ) ) {
				@unlink( $file_array['tmp_name'] ); // @codingStandardsIgnoreLine

				return $image_path;
			}
		}

		return $image_path;
	}

}
