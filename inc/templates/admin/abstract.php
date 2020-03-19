<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<label class="screen-reader-text" for="excerpt">
	<?php esc_html_e( 'Abstract', 'wp-weixin-broadcast' ); ?>
</label>
<textarea rows="1" cols="40" name="excerpt" id="excerpt" placeholder="<?php esc_html_e( 'Optional - If this field is left empty, the first 54 words of the main text will be captured by default.', 'wp-weixin-broadcast' ); ?>"><?php echo trim( $post->post_excerpt ); // textarea_escaped // WPCS: XSS ok ?></textarea>
