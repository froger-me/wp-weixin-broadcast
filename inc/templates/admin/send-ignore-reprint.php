<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<div class="wp-weixin-broadcast-send-ignore-reprint-container">
	<p class="howto"><?php esc_html_e( 'Check if you still want to attempt to proceed with the broadcast if WeChat has determined the content is not "Original Content".', 'wp-weixin-broadcast' ); ?></p>
	<label>
		<input value="1" type="checkbox"<?php checked( 1, $send_ignore_reprint ); ?> name="wechat_broadcast_send_ignore_reprint" id="wechat_broadcast_send_ignore_reprint"> <?php esc_html_e( 'Ignore Reprint Warning', 'wp-weixin-broadcast' ); ?>
	</label>
</div>
