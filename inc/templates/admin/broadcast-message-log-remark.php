<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<div class="excerpt-content">
	<?php if ( 'warning' === $status ) : ?>
		<?php esc_html_e( 'The broadcast job was completed with warnings.', 'wp-weixin-broadcast' ); ?>
		<br/>
	<?php endif; ?>
	<?php if ( isset( $show_unknown_warning ) && $show_unknown_warning ) : ?>
		<?php esc_html_e( 'The broadcast job was completed with an unknown status.', 'wp-weixin-broadcast' ); ?>
		<br/>
	<?php endif; ?>
	<?php echo esc_html( $excerpt_message ); ?>
	<?php if ( 'failure' === $status ) : ?>
		<br/>
		err('<?php echo esc_html( $code ); ?>') <?php echo esc_html( $err_message ); ?>
	<?php endif; ?>
</div>
<div class="wechat-broadcast-message hidden">
	<?php if ( isset( $show_job_result ) && $show_job_result ) : ?>
		<h4><?php esc_html_e( 'Broadcast result statistics:', 'wp-weixin-broadcast' ); ?></h4>
		<div class="howto">
			<?php esc_html_e( 'WeChat does not guarantee the accuracy of the result and it is given below for reference only.', 'wp-weixin-broadcast' ); ?>
		</div>
		<ul>
			<li>
				<label><?php esc_html_e( 'Filtered total: ', 'wp-weixin-broadcast' ); ?></label><span><?php echo esc_html( $filtered_ratio ); ?></span> <span class="howto"><?php esc_html_e( 'Total number of followers eligible to receive the broadcast after filters have been applied (removing followers who have opted out, have been blacklisted, or already received the daily/monthly quota of messages) over the total number of followers the message was sent to.', 'wp-weixin-broadcast' ); ?></span>
			</li>
			<li>
				<label><?php esc_html_e( 'Success ratio: ', 'wp-weixin-broadcast' ); ?></label><span><?php echo esc_html( $success_ratio ); ?></span> <span class="howto"><?php esc_html_e( 'Number of followers who actually received the broadcast over the total number of followers eligible.', 'wp-weixin-broadcast' ); ?></span>
			</li>
			<li>
				<label><?php esc_html_e( 'Error ratio: ', 'wp-weixin-broadcast' ); ?></label><span><?php echo esc_html( $error_ratio ); ?></span> <span class="howto"><?php esc_html_e( 'Number of followers who did <strong>not</strong> receive the broadcast over the total number of followers eligible.', 'wp-weixin-broadcast' ); ?></span>
			</li>
		</ul>
		<?php if ( ! empty( $copyright_issue_remarks ) ) : ?>
			<h4><?php esc_html_e( 'Issues found during originality check:', 'wp-weixin-broadcast' ); ?></h4>
			<ul>
			<?php foreach ( $copyright_issue_remarks as $index => $copyright_issue_remark ) : ?>
				<li><?php echo esc_html( $copyright_issue_remark ); ?></li>
			<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	<?php endif; ?>
	<?php if ( ! empty( $details_sections ) ) : ?>
		<?php foreach ( $details_sections as $section ) : ?>
			<h4><?php echo esc_html( $section['title'] ); ?></h4>
			<div class="wechat-broadcast-message-scroll">
			<pre>
				<code><?php echo esc_html( $section['content'] ); ?></code>
			</pre>
		</div>
		<?php endforeach; ?>
	<?php endif ?>
</div>
