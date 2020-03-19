<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<div id="wp_weixin_broadcast_h5_editor_wrap">
	<?php echo $button; // WPCS: XSS OK ?>
	<div id="wp_weixin_broadcast_h5_preview_resizer">
		<div id="wp_weixin_broadcast_h5_preview_wrap">
			<div id="wp_weixin_broadcast_h5_preview">
				<iframe id="wp_weixin_broadcast_h5_preview_frame"></iframe>
			</div>
		</div>
	</div>
	<div id="wp_weixin_broadcast_h5_editor_content_wrap">
		<p id="wp_weixin_broadcast_h5_editor_description">
			<?php esc_html_e( 'Write or Paste HTML5 code in the editor below.', 'wp-weixin-broadcast' ); ?><br>
			<strong><?php esc_html_e( 'Warning: Activating the WordPress Visual Editor may alter the markup!', 'wp-weixin-broadcast' ); ?></strong><br>
			<?php esc_html_e( 'The following tags will be automatically stripped:', 'wp-weixin-broadcast' ); ?><br>
			<code>script style input canvas iframe select</code>
		</p>
		<textarea id ="wp_weixin_broadcast_h5_editor_content" name="wp_weixin_broadcast_h5_editor_content"><?php echo htmlspecialchars( $post->post_content ); // WPCS: XSS OK ?></textarea>
	</div>
</div>
