<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div id="wechat_broadcast_log_container">
	<div class="log-modal-backdrop hidden"></div>
	<?php if ( ! empty( $logs ) ) : ?>
		<?php foreach ( $logs as $index => $log ) : ?>
			<div class="card" id="<?php echo esc_attr( 'wp-weixin-broadcast-log-' . $log->id ); ?>">
				<div class="status <?php echo esc_attr( $log->status ); ?>">
					<?php echo esc_html( WP_Weixin_Broadcast_Message::$log_statuses[ $log->status ] ); ?>
				</div>
				<div class="type">
					<label><?php esc_html_e( 'Type: ', 'wp-weixin-broadcast' ); ?></label><span><?php echo esc_html( WP_Weixin_Broadcast_Message::$log_types[ $log->type ] ); ?><span>
				</div>
				<div class="timestamp">
					<label><?php esc_html_e( 'Date: ', 'wp-weixin-broadcast' ); ?></label><span><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' - H:i:s', mysql2date( 'U', $log->date ) ) ); ?></span>
				</div>
				<div class="excerpt">
					<?php echo $log->remark; // WPCS: XSS OK ?>
				</div>
				<div class="log-full hidden">
					<button type="button" class="log-full-close">
						<span class="log-full-close-icon"><span class="screen-reader-text">Close media panel</span></span>
					</button>
					<div class="log-full-details-container">
						<h3><?php esc_html_e( 'Log details', 'wp-weixin-broadcast' ); ?></h3>
						<div class="status <?php echo esc_attr( $log->status ); ?>">
						<?php echo esc_html( WP_Weixin_Broadcast_Message::$log_statuses[ $log->status ] ); ?>
					</div>
					<div class="type">
						<label><?php esc_html_e( 'Type: ', 'wp-weixin-broadcast' ); ?></label><span><?php echo esc_html( WP_Weixin_Broadcast_Message::$log_types[ $log->type ] ); ?><span>
					</div>
					<div class="timestamp">
						<label><?php esc_html_e( 'Date: ', 'wp-weixin-broadcast' ); ?></label><span><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' - H:i:s', mysql2date( 'U', $log->date ) ) ); ?></span>
					</div>
						<?php echo $log->remark; // WPCS: XSS OK ?>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	<?php else : ?>
		<p><?php esc_html_e( 'No log entry yet', 'wp-weixin-broadcast' ); ?></p>
	<?php endif; ?>
</div>
