<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<div class="wp-weixin-broadcast-to-all wp-weixin-broadcast-to-target-settings select2-ui-wait">
	<div class="howto">
		<?php esc_html_e( 'The broadcasted message will be sent to all the followers of the Official Account. The broadcast allowance (1 daily for Subscription accounts, 4 monthly for Service Accounts) will be decreased for all the followers.', 'wp-weixin-broadcast' ); ?>
	</div>
</div>
<div class="wp-weixin-broadcast-to-tag wp-weixin-broadcast-to-target-settings select2-ui-wait">
	<select id="wp_weixin_broadcast_select_broadcast_to_tag"></select>
	<input type="hidden" name="wechat_broadcast_target_tag" id="wp_weixin_broadcast_select_broadcast_to_tag_id" value="<?php echo empty( $broadcast_to_tag ) ? '' : esc_attr( $broadcast_to_tag ); ?>">
	<?php if ( isset( $tag_value ) ) : ?>
		<input type="hidden" class="wechat-broadcast-target-tag" data-id="<?php echo esc_attr( $tag_value['id'] ); ?>" data-text="<?php echo esc_attr( $tag_value['name'] ); ?>" data-number="<?php echo esc_attr( $tag_value['count'] ); ?>">
	<?php endif; ?>
	<div class="howto">
		<?php esc_html_e( 'The broadcasted message will be sent only to the followers tagged with the selected WeChat Official Account tag item. The broadcast allowance (1 daily for Subscription accounts, 4 monthly for Service Accounts) will be decreased only for the followers tagged with the selected item.', 'wp-weixin-broadcast' ); ?>
	</div>
</div>
<div class="wp-weixin-broadcast-to-users wp-weixin-broadcast-to-target-settings select2-ui-wait">
	<select id="wp_weixin_broadcast_select_broadcast_to_wp_users" multiple="multiple"></select>
	<input type="hidden" name="wechat_broadcast_target_users" id="wp_weixin_broadcast_select_broadcast_to_wp_users_openids" value="">
	<?php if ( isset( $wp_user_values ) ) : ?>
		<?php foreach ( $wp_user_values as $index => $wp_user_item ) : ?>
			<input type="hidden" class="wechat-broadcast-target-user" data-id="<?php echo esc_attr( $wp_user_item['id'] ); ?>" data-text="<?php echo esc_attr( $wp_user_item['text'] ); ?>" data-gender="<?php echo esc_attr( $wp_user_item['gender'] ); ?>" data-city="<?php echo esc_attr( $wp_user_item['city'] ); ?>" data-country="<?php echo esc_attr( $wp_user_item['country'] ); ?>" data-thumb="<?php echo esc_attr( $wp_user_item['thumb'] ); ?>" >
		<?php endforeach; ?>
	<?php endif; ?>

	<div class="howto">
		<strong><?php esc_html_e( 'If not left empty, this type of broadcast requires a minimum of 2 followers selected as target recipients.', 'wp-weixin-broadcast' ); ?></strong><br/>
		<?php esc_html_e( 'If left empty, the message will be broadcasted to all the WordPress WeChat users registered (with a limit of 10,000).' ); ?><br/>
		<?php esc_html_e( 'Only the WordPress users currently following the Official Account can receive the broadcasted message and will appear in the search results. The broadcast allowance (1 daily for Subscription accounts, 4 monthly for Service Accounts) will be decreased only for the selected followers.', 'wp-weixin-broadcast' ); ?>
	</div>
</div>
