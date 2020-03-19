<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

wp_nonce_field( 'wechat_broadcast_article_show_cover_image_nonce', 'wechat_broadcast_article_show_cover_image_nonce' );
?>
<p class="howto"><?php esc_html_e( 'The recommended image size is 900x500 pixels, and must not be more than 2MB.', 'wp-weixin-broadcast' ); ?></p>
<label>
	<input value="1" type="checkbox"<?php checked( 1, $show_cover ); ?> name="wechat_broadcast_article_show_cover_image" id="wechat_broadcast_article_show_cover_image"> <?php esc_html_e( 'Show cover image in article', 'wp-weixin-broadcast' ); ?>
</label>
