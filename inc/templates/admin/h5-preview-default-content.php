<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<div class="rich_media_inner">
	<div id="page-content" class="rich_media_area_primary">
		<div class="rich_media_area_primary_inner">
			<div id="img-content" class="rich_media_wrp">
				<h2 class="rich_media_title" id="activity-name"><?php echo esc_html( $post->post_title ); ?></h2>
				<div id="meta_content" class="rich_media_meta_list">
					<span class="rich_media_meta rich_media_meta_text rich_media_author"><?php echo esc_html( $author ); ?></span>                     
					<span class="rich_media_meta rich_media_meta_nickname" id="profileBt">
						<a href="javascript:void(0);" id="js_name"><?php echo esc_html( $oa_name ); ?></a>
					</span>
					<em id="publish_time" class="rich_media_meta rich_media_meta_text"><?php esc_html_e( 'Today', 'wp-weixin-broadcast' ); ?></em>
				</div>
				<div class="rich_media_content " id="js_content" style="visibility: visible;">
				</div>
			</div>
		</div>
	</div>
</div>
