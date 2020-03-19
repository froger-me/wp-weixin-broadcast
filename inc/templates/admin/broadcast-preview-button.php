<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

wp_nonce_field( 'wechat_broadcast_message_preview', 'wechat_broadcast_message_preview' );
?>
<?php do_action( 'wp_weixin_broadcast_before_preview_button', $post, $broadcasted, $latest_info ); ?>
<?php if ( ! in_array( $post->post_status, array( 'publish', 'future' ), true ) || apply_filters( 'wp_weixin_debug', (bool) ( constant( 'WP_DEBUG' ) ) ) ) : ?>
	<div id="preview-action">
		<a class="button" href="#" data-id="<?php echo esc_attr( $post->ID ); ?>" id="wechat_preview"><?php esc_html_e( 'Send Preview to WeChat', 'wp-weixin-broadcast' ); ?></a>
		<span class="spinner"></span>
	</div>
	<?php if ( in_array( $post->post_status, array( 'publish', 'future' ), true ) ) : ?>
		<h4 class="wp-weixin-broadcast-debug-warning"><?php esc_html_e( 'WARNING!', 'wp-weixin-broadcast' ); ?></h4>
		<p class="wp-weixin-broadcast-debug-warning"><?php esc_html_e( 'WordPress debug mode is activated. In normal circumstances, this WeChat Broadcast post should not be editable or available for preview.', 'wp-weixin-broadcast' ); ?></p>
		<p class="wp-weixin-broadcast-debug-warning"><?php esc_html_e( 'It is generally safe to revert the post to another post status (such as "Draft" or "Pending Reviews") and change the schedule time, but other operations may have unintended consequences.', 'wp-weixin-broadcast' ); ?></p>
		<p class="wp-weixin-broadcast-debug-warning"><?php esc_html_e( 'Proceed at our own risks.', 'wp-weixin-broadcast' ); ?></p>
	<?php endif; ?>
<?php else : ?>
<div id="preview-action">
	<strong><?php esc_html_e( 'This message has already been broadcasted and cannot be modified.', 'wp-weixin-broadcast' ); ?></strong>
</div>
<?php endif; ?>
<?php if ( $broadcasted ) : ?>
	<div id="broadcast-status-action">
		<a class="button" href="#" data-id="<?php echo esc_attr( $post->ID ); ?>" id="wechat_broadcast_status"><?php esc_html_e( 'Check broadcast status', 'wp-weixin-broadcast' ); ?></a>
		<span class="spinner"></span>
		<div id="latest-broadcast-status" class="<?php echo ( ( $latest_info['text'] ) ? esc_attr( 'show' ) : esc_attr( 'hidden' ) ); ?>">
			<label><?php esc_html_e( 'Latest status recorded:', 'wp-weixin-broadcast' ); ?></label> <span class="<?php echo esc_attr( $latest_info['class'] ); ?>"><?php echo esc_html( $latest_info['text'] ); ?></span>
		</div>
	</div>
<?php endif; ?>
<?php do_action( 'wp_weixin_broadcast_after_preview_button', $post, $broadcasted, $latest_info ); ?>
