<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

wp_nonce_field( 'wechat_broadcast_article_metadata', 'wechat_broadcast_article_metadata' );
?>
<p>
	<label for="wechat_broadcast_article_author">
		<?php esc_html_e( 'Author:', 'wp-weixin-broadcast' ); ?>
	</label><br>
	<input type="text" name="wechat_broadcast_article_author" id="wechat_broadcast_article_author" value="<?php echo esc_html( $author ); ?>"><br/>
	<span class="howto"><?php esc_html_e( 'Leave empty to use the current user\'s full name if exists, or the user display name by default.', 'wp-weixin-broadcast' ); ?></span>
</p>
<p>
	<label for="wechat_broadcast_article_origin_url">
		<?php esc_html_e( 'Read More URL:', 'wp-weixin-broadcast' ); ?>
	</label><br>
	<input type="text" name="wechat_broadcast_article_origin_url" id="wechat_broadcast_article_origin_url" value="<?php echo esc_html( $source_url ); ?>"><br/>
	<span class="howto"><?php esc_html_e( 'Leave empty to use the original source Post URL if it exists, or the homepage URL by default.', 'wp-weixin-broadcast' ); ?></span>
</p>
<p>
	<label>
		<input value="1" type="checkbox"<?php checked( 1, $need_open_comment ); ?> name="wechat_broadcast_article_need_open_comment" id="wechat_broadcast_article_need_open_comment"> <?php esc_html_e( 'Open to comments', 'wp-weixin-broadcast' ); ?>
	</label>
</p>
<p>
	<label>
		<input value="1" type="checkbox"<?php checked( 1, $only_fans_can_comment ); ?> name="wechat_broadcast_article_only_fans_can_comment" id="wechat_broadcast_article_only_fans_can_comment"> <?php esc_html_e( 'Only the Official Account followers can comment', 'wp-weixin-broadcast' ); ?>
	</label>
</p>
