<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WP_Weixin_Article {

	protected $wechat;

	const POST_TYPE = 'wechat_article';
	const CAPS      = array(
		'create_wechat_articles',
		'edit_wechat_article',
		'read_wechat_article',
		'delete_wechat_article',
		'edit_wechat_articles',
		'edit_others_wechat_articles',
		'publish_wechat_articles',
		'read_private_wechat_articles',
	);

	public function __construct( $wechat, $init_hooks = false ) {
		$this->wechat = $wechat;

		if ( $init_hooks ) {
			// Register WeChat Article Post Type
			add_action( 'init', array( $this, 'register_wechat_article' ), 0, 0 );
			// Save cover image status
			add_action( 'save_post', array( $this, 'show_cover_image_save' ), 10, 1 );
			// Save WeChat metadata
			add_action( 'save_post', array( $this, 'metadata_save' ), 10, 1 );
			// Add metaboxes
			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 10, 0 );
			// Add capabilities
			add_action( 'admin_init', array( $this, 'add_caps' ), 10, 0 );
			// Handle post cloning
			add_action( 'admin_action_wechat_broadcast_article_clone_post', array( $this, 'clone_post' ), 10, 0 );
			// Add admin notices
			add_action( 'admin_notices', array( $this, 'notices' ), 10, 0 );
			// Add h5 editor
			add_action( 'edit_form_after_editor', array( $this, 'after_editor' ), 10, 1 );
			// Alter H5 editor button
			add_action( 'media_buttons', array( $this, 'h5_editor_button' ), 10, 1 );

			// Set default editor
			add_filter( 'wp_default_editor', array( $this, 'set_default_editor' ), 10, 1 );
			// Edit featured image metabox
			add_filter( 'admin_post_thumbnail_html', array( $this, 'cover_image_html' ), 10, 3 );
			// Add post cloning action
			add_filter( 'post_row_actions', array( $this, 'clone_post_link' ), 10, 2 );
			// Force classic editor
			add_filter( 'use_block_editor_for_post_type', array( $this, 'force_classic_editor' ), 10, 2 );
		}
	}

	/*******************************************************************
	 * Public methods
	 *******************************************************************/

	public static function is_show_cover_image( $post_id ) {

		if ( ! $post_id ) {
			global $post;

			$post_id = $post->ID;
		}

		$field = get_post_meta( $post_id, 'wp_weixin_broadcast_article_show_cover_image', true );

		return (bool) $field;
	}

	public static function is_need_open_comment( $post_id ) {

		if ( ! $post_id ) {
			global $post;

			$post_id = $post->ID;
		}

		$field = get_post_meta( $post_id, 'wp_weixin_broadcast_article_need_open_comment', true );

		return (bool) $field;
	}

	public static function is_only_fans_can_comment( $post_id ) {

		if ( ! $post_id ) {
			global $post;

			$post_id = $post->ID;
		}

		$field = get_post_meta( $post_id, 'wp_weixin_broadcast_article_only_fans_can_comment', true );

		return (bool) $field;
	}

	public static function get_main_script_params( $params ) {
		global $post, $typenow;

		return array(
			'h5_preview_default_content' => self::get_preview_default_content( $post ),
			'h5_preview_head'            => self::get_preview_head(),
			'h5_preview_cover_image'     => self::get_cover_image_preview_default_content(),
			'h5_preview_default_author'  => self::get_preview_default_author( $post ),
			'h5_visual_alert'            => __( "Are you sure you want to do this?\n\nIf you proceed, the WordPress Visual Editor will sanitize the markup and it may drastically alter the structure of the content.\nProceed at your own risk!", 'wp-weixin-broadcast' ),
		);
	}

	public function register_wechat_article() {
		$labels       = array(
			'name'                  => _x( 'WeChat Articles', 'Post Type General Name', 'wp-weixin-broadcast' ),
			'singular_name'         => _x( 'WeChat Article', 'Post Type Singular Name', 'wp-weixin-broadcast' ),
			'menu_name'             => __( 'WeChat Articles', 'wp-weixin-broadcast' ),
			'name_admin_bar'        => __( 'WeChat Articles', 'wp-weixin-broadcast' ),
			'archives'              => __( 'WeChat Article Archives', 'wp-weixin-broadcast' ),
			'attributes'            => __( 'WeChat Article Attributes', 'wp-weixin-broadcast' ),
			'parent_item_colon'     => __( 'Parent WeChat Article:', 'wp-weixin-broadcast' ),
			'all_items'             => __( 'All WeChat Articles', 'wp-weixin-broadcast' ),
			'add_new_item'          => __( 'Add New WeChat Article', 'wp-weixin-broadcast' ),
			'new_item'              => __( 'New WeChat Article', 'wp-weixin-broadcast' ),
			'edit_item'             => __( 'Edit WeChat Article', 'wp-weixin-broadcast' ),
			'update_item'           => __( 'Update WeChat Article', 'wp-weixin-broadcast' ),
			'view_item'             => __( 'View WeChat Articles', 'wp-weixin-broadcast' ),
			'view_items'            => __( 'View WeChat Articles', 'wp-weixin-broadcast' ),
			'search_items'          => __( 'Search WeChat Article', 'wp-weixin-broadcast' ),
			'featured_image'        => __( 'Cover Image', 'wp-weixin-broadcast' ),
			'set_featured_image'    => __( 'Set cover image', 'wp-weixin-broadcast' ),
			'remove_featured_image' => __( 'Remove cover image', 'wp-weixin-broadcast' ),
			'use_featured_image'    => __( 'Use as cover image', 'wp-weixin-broadcast' ),
			'insert_into_item'      => __( 'Insert into WeChat Article', 'wp-weixin-broadcast' ),
			'uploaded_to_this_item' => __( 'Uploaded to this WeChat Article', 'wp-weixin-broadcast' ),
			'items_list'            => __( 'WeChat Articles list', 'wp-weixin-broadcast' ),
			'items_list_navigation' => __( 'WeChat Articles list navigation', 'wp-weixin-broadcast' ),
			'filter_items_list'     => __( 'Filter WeChat Articles list', 'wp-weixin-broadcast' ),
		);
		$capabilities = array(
			'create_posts'       => 'create_wechat_articles',
			'edit_post'          => 'edit_wechat_article',
			'read_post'          => 'read_wechat_article',
			'delete_post'        => 'delete_wechat_article',
			'edit_posts'         => 'edit_wechat_articles',
			'edit_others_posts'  => 'edit_others_wechat_articles',
			'publish_posts'      => 'publish_wechat_articles',
			'read_private_posts' => 'read_private_wechat_articles',
		);
		$args         = array(
			'label'               => __( 'WeChat Article', 'wp-weixin-broadcast' ),
			'description'         => __( 'A WeChat Article Material to upload', 'wp-weixin-broadcast' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'thumbnail', 'revisions' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => 'wechat-broadcast-menu',
			'menu_position'       => 100,
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
			'wp_weixin_broadcast_wechat_article_roles',
			array(
				'administrator',
				'editor',
			)
		);

		foreach ( $roles as $role ) {
			$caps = apply_filters( 'wp_weixin_broadcast_wechat_article_caps', self::CAPS, $role );
			$role = get_role( $role );

			foreach ( $caps as $cap ) {
				$role->add_cap( $cap );
			}
		}
	}

	public function set_default_editor( $type ) {
		global $post_type;

		if ( self::POST_TYPE === $post_type ) {
				return 'html';
		}

		return $type;
	}

	public function add_meta_boxes() {
		$metaboxes = array(
			'abstract' => array(
				'title'    => __( 'Abstract', 'wp-weixin-broadcast' ),
				'context'  => 'side',
				'callback' => array( $this, 'abstract_meta_box' ),
				'priority' => 'low',
			),
			'metadata' => array(
				'title'    => __( 'WeChat Metadata', 'wp-weixin-broadcast' ),
				'context'  => 'side',
				'callback' => array( $this, 'metadata_meta_box' ),
				'priority' => 'default',
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

	public function show_cover_image_save( $post_id ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {

			return;
		}

		if (
			! isset( $_POST['wechat_broadcast_article_show_cover_image_nonce'] ) ||
			! wp_verify_nonce(
				$_POST['wechat_broadcast_article_show_cover_image_nonce'],
				'wechat_broadcast_article_show_cover_image_nonce'
			)
		) {

			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {

			return;
		}

		if ( isset( $_POST['wechat_broadcast_article_show_cover_image'] ) ) {
			update_post_meta(
				$post_id,
				'wp_weixin_broadcast_article_show_cover_image',
				(bool) filter_input( INPUT_POST, 'wechat_broadcast_article_show_cover_image', FILTER_SANITIZE_STRING )
			);
		} else {
			update_post_meta(
				$post_id,
				'wp_weixin_broadcast_article_show_cover_image',
				false
			);
		}
	}

	public function metadata_save( $post_id ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {

			return;
		}

		if (
			! isset( $_POST['wechat_broadcast_article_metadata'] ) ||
			! wp_verify_nonce(
				$_POST['wechat_broadcast_article_metadata'],
				'wechat_broadcast_article_metadata'
			)
		) {

			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {

			return;
		}

		if (
			isset( $_POST['wechat_broadcast_article_author'] ) &&
			! empty( $_POST['wechat_broadcast_article_author'] )
		) {
			$author = filter_input( INPUT_POST, 'wechat_broadcast_article_author', FILTER_SANITIZE_STRING );
		} else {
			$user_id    = get_post_field( 'post_author', $post_id );
			$first_name = get_the_author_meta( 'first_name', $user_id );
			$last_name  = get_the_author_meta( 'last_name', $user_id );
			// translators: %1$s is the author's first name, %2$s is the author's last name
			$author = trim( sprintf( __( '%1$s %2$s' ), $first_name, $last_name ) );
			$author = empty( $author ) ? get_the_author_meta( 'display_name', $user_id ) : $author;
		}

		update_post_meta( $post_id, 'wp_weixin_broadcast_article_author', $author );

		if (
			isset( $_POST['wechat_broadcast_article_origin_url'] ) &&
			! empty( $_POST['wechat_broadcast_article_origin_url'] ) &&
			filter_input( INPUT_POST, 'wechat_broadcast_article_origin_url', FILTER_VALIDATE_URL )
		) {
			$source_url = filter_input( INPUT_POST, 'wechat_broadcast_article_origin_url', FILTER_VALIDATE_URL );
		} else {
			$source_post_id = get_post_meta( $post_id, 'wp_weixin_broadcast_article_source_post_id', true );
			$source_url     = false;

			if ( ! empty( $source_post_id ) && is_numeric( $source_post_id ) ) {
				$source_url = get_permalink( $source_post_id );
			}

			if ( ! $source_url ) {
				$source_url = get_home_url();
			}
		}

		update_post_meta( $post_id, 'wp_weixin_broadcast_article_origin_url', $source_url );

		if ( isset( $_POST['wechat_broadcast_article_need_open_comment'] ) ) {
			update_post_meta(
				$post_id,
				'wp_weixin_broadcast_article_need_open_comment',
				(bool) filter_input( INPUT_POST, 'wechat_broadcast_article_need_open_comment', FILTER_SANITIZE_STRING )
			);
		} else {
			update_post_meta(
				$post_id,
				'wp_weixin_broadcast_article_need_open_comment',
				false
			);
		}

		if ( isset( $_POST['wechat_broadcast_article_only_fans_can_comment'] ) ) {
			update_post_meta(
				$post_id,
				'wp_weixin_broadcast_article_only_fans_can_comment',
				(bool) filter_input( INPUT_POST, 'wechat_broadcast_article_only_fans_can_comment', FILTER_SANITIZE_STRING )
			);
		} else {
			update_post_meta(
				$post_id,
				'wp_weixin_broadcast_article_only_fans_can_comment',
				false
			);
		}
	}

	public function cover_image_html( $content, $post_id, $thumbnail_id ) {

		if ( self::POST_TYPE !== get_post_type( $post_id ) ) {
				return $content;
		}

		$show_cover = self::is_show_cover_image( $post_id );

		ob_start();

		include WP_WEIXIN_BROADCAST_PLUGIN_PATH . 'inc/templates/admin/cover-image.php';

		$checkbox = ob_get_clean();

		return $content . $checkbox;
	}

	public function abstract_meta_box( $post ) {
		include WP_WEIXIN_BROADCAST_PLUGIN_PATH . 'inc/templates/admin/abstract.php';
	}

	public function metadata_meta_box( $post ) {
		$source_url            = get_post_meta( $post->ID, 'wp_weixin_broadcast_article_origin_url', true );
		$author                = get_post_meta( $post->ID, 'wp_weixin_broadcast_article_author', true );
		$need_open_comment     = self::is_need_open_comment( $post->ID );
		$only_fans_can_comment = self::is_only_fans_can_comment( $post->ID );

		include WP_WEIXIN_BROADCAST_PLUGIN_PATH . 'inc/templates/admin/metadata.php';
	}

	public function clone_post() {
		global $wpdb;

		if (
			! isset( $_GET['wechat_broadcast_article_duplicate_nonce'] ) ||
			! wp_verify_nonce( $_GET['wechat_broadcast_article_duplicate_nonce'], 'wechat_broadcast_article_duplicate_nonce' )
		) {
			return;
		}

		if (
			! ( isset( $_GET['post'] ) ||
				isset( $_POST['post'] ) ||
				(
					isset( $_REQUEST['action'] ) && 'wechat_broadcast_article_clone_post' === $_REQUEST['action']
				)
			)
		) {
			wp_die( __( 'No post to duplicate has been supplied!', 'wp-weixin-broadcast' ) ); // WPCS:XSS OK
		}

		$post_id         = ( isset( $_GET['post'] ) ? absint( $_GET['post'] ) : absint( $_POST['post'] ) );
		$post            = get_post( $post_id );
		$new_post_author = $post->post_author;

		do_action( 'wp_weixin_broadcast_before_post_clone_to_wechat_article', $post_id );

		if ( isset( $post ) && null !== $post ) {
			$args = array(
				'post_author'  => $new_post_author,
				'post_content' => $post->post_content,
				'post_excerpt' => $post->post_excerpt,
				'post_name'    => $post->post_name,
				'post_status'  => 'draft',
				'post_title'   => $post->post_title,
				'post_type'    => self::POST_TYPE,
			);

			$new_post_id = wp_insert_post( $args );
			$user_id     = get_post_field( 'post_author', $new_post_id );
			$first_name  = get_the_author_meta( 'first_name', $user_id );
			$last_name   = get_the_author_meta( 'last_name', $user_id );
			$author      = trim( $first_name . ' ' . $last_name );
			$author      = empty( $author ) ? get_the_author_meta( 'display_name', $user_id ) : $author;

			update_post_meta(
				$new_post_id,
				'wp_weixin_broadcast_article_author',
				$author
			);

			update_post_meta(
				$new_post_id,
				'wp_weixin_broadcast_article_origin_url',
				get_permalink( $post )
			);

			update_post_meta(
				$new_post_id,
				'wp_weixin_broadcast_article_source_post_id',
				$post->ID
			);

			do_action( 'wp_weixin_broadcast_after_post_clone_to_wechat_article', $post_id, $new_post_id );
			wp_redirect( admin_url( 'post.php?action=edit&post=' . $new_post_id ) );

			exit();
		} else {
			wp_die( 'Post creation failed, could not find original post with ID: ' . $post_id ); // WPCS: XSS OK
		}
	}

	public function clone_post_link( $actions, $post ) {
		$no_duplicate = apply_filters( 'wp_weixin_broadcast_no_duplicate_post_types', array( self::POST_TYPE ) );

		if (
				current_user_can( 'edit_posts' ) &&
				! in_array( $post->post_type, $no_duplicate, true )
			) {
			$actions['duplicate']  = '<a href="';
			$actions['duplicate'] .= wp_nonce_url(
				'admin.php?action=wechat_broadcast_article_clone_post&post=' . $post->ID,
				'wechat_broadcast_article_duplicate_nonce',
				'wechat_broadcast_article_duplicate_nonce'
			);
			$actions['duplicate'] .= '" title="' . __( 'Duplicate as WeChat Article', 'wp-weixin-broadcast' );
			$actions['duplicate'] .= '" rel="permalink">' . __( 'Duplicate as WeChat Article', 'wp-weixin-broadcast' ) . '</a>';
		}

		return $actions;
	}

	public function notices() {
		global $pagenow, $post, $shortcode_tags;

		if ( 'post.php' === $pagenow && self::POST_TYPE === $post->post_type ) {
			$source_post_id = get_post_meta( $post->ID, 'wp_weixin_broadcast_article_source_post_id', true );

			if ( $source_post_id ) {
				$source_post = get_post( $source_post_id );

				if ( $source_post ) {
					$class = 'notice notice-info';
					$link  = get_edit_post_link( $source_post );

					if ( $link ) {
						$link  = '<a href="' . $link . '">' . __( 'Edit Source', 'wp-weixin-broadcast' ) . '</a>';
						$link .= ' - <a href="' . get_permalink( $source_post ) . '">' . __( 'View Source', 'wp-weixin-broadcast' ) . '</a>';
					} else {
						$link = '<a href="' . get_permalink( $source_post ) . '">' . __( 'View Source', 'wp-weixin-broadcast' ) . '</a>';
					}

					$message = sprintf(
						/* translators: %s: the post type of the source post */
						__( 'This content has been duplicated from a WordPress Post of type "%s". ' ),
						$source_post->post_type,
						'wp-weixin-broadcast'
					);

					echo sprintf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message . $link ); // WPCS: XSS ok
				}
			}

			if ( false !== strpos( $post->post_content, '[' ) && false !== strpos( $post->post_content, ']' ) ) {
				$class    = 'notice notice-warning is-dismissible';
				$message  = __( 'Opening squared bracket "[" and closing squared bracket "]" detected in content: the article may contain shortcodes. Although some may work out fine, shortcodes relying on javascript code or external CSS stylesheets may break the layout of your content.', 'wp-weixin-broadcast' );
				$message .= '<br/>';
				$message .= __( 'To avoid compatibility issues, make sure to thoroughly review broadcast messages including this article with the "Send Preview to WeChat" button before broadcasting the message to followers.', 'wp-weixin-broadcast' );

				echo sprintf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message ); // WPCS: XSS ok
			}
		}
	}

	public function force_classic_editor( $can_edit, $post_type ) {

		if ( self::POST_TYPE === $post_type ) {
			$can_edit = false;
		}

		return $can_edit;
	}

	public function after_editor( $post ) {

		if ( self::POST_TYPE !== $post->post_type ) {

			return;
		}

		$button = $this->h5_editor_button( false, true );

		include WP_WEIXIN_BROADCAST_PLUGIN_PATH . 'inc/templates/admin/h5-editor.php';
	}

	public function h5_editor_button( $output, $h5_active = false ) {
		global $post;

		if ( $post && self::POST_TYPE !== $post->post_type ) {

			return;
		}

		$text  = ( $h5_active ) ? __( 'Switch to WordPress Editor', 'wp-weixin-broadcast' ) : __( 'Switch to H5 Editor', 'wp-weixin-broadcast' );
		$class = ( $h5_active ) ? ' h5-active-button' : ' h5-inactive-button';

		ob_start();

		include WP_WEIXIN_BROADCAST_PLUGIN_PATH . 'inc/templates/admin/h5-editor-button.php';

		$markup = ob_get_clean();

		if ( false === $output ) {

			return $markup;
		} else {

			echo $markup; // WPCS: XSS OK
		}
	}

	/*******************************************************************
	 * Protected methods
	 *******************************************************************/

	protected static function get_preview_default_content( $post ) {
		$author  = self::get_preview_default_author( $post );
		$oa_name = wp_weixin_get_option( 'name' );
		$oa_name = empty( $oa_name ) ? __( '[WeChat OA name]', 'wp-weixin-broadcast' ) : $oa_name;

		ob_start();

		include WP_WEIXIN_BROADCAST_PLUGIN_PATH . 'inc/templates/admin/h5-preview-default-content.php';

		return ob_get_clean();
	}

	protected static function get_preview_default_author( $post ) {
		$author = get_post_meta( $post->ID, 'wp_weixin_broadcast_article_author', true );

		if ( ! $author || empty( $author ) ) {
			$user_id    = get_post_field( 'post_author', $post->ID );
			$first_name = get_the_author_meta( 'first_name', $user_id );
			$last_name  = get_the_author_meta( 'last_name', $user_id );
			$author     = trim( $first_name . ' ' . $last_name );
			$author     = empty( $author ) ? get_the_author_meta( 'display_name', $user_id ) : $author;
		}

		return $author;
	}

	protected static function get_preview_head() {
		ob_start();

		include WP_WEIXIN_BROADCAST_PLUGIN_PATH . 'inc/templates/admin/h5-preview-head.php';

		return ob_get_clean();
	}

	protected static function get_cover_image_preview_default_content() {
		$default_path = apply_filters(
			'wp_weixin_broadcast_default_cover_path',
			WP_WEIXIN_BROADCAST_PLUGIN_URL . 'images/wp-weixin-broadcast-cover-placeholder.png',
			false
		);

		ob_start();

		include WP_WEIXIN_BROADCAST_PLUGIN_PATH . 'inc/templates/admin/h5-cover-image-preview-default-content.php';

		return ob_get_clean();
	}
}
