# WP Weixin Broadcast - WeChat Broadcast for WordPress

* [General Description](#user-content-general-description)
	* [Requirements](#user-content-requirements)
	* [Overview](#user-content-overview)
* [Broadcast Target Types](#user-content-broadcast-target-types)
* [Hooks - actions & filters](#user-content-hooks---actions--filters)
	* [Actions](#user-content-actions)
	* [Filters](#user-content-filters)
* [JavaScript]('#user-content-javascript')

## General Description

WP Weixin Broadcast is a companion plugin for WP Weixin that adds the possibility to broadcast WordPress posts to WeChat followers.  
It provides an interface very similar to the WeChat Official Account platform for easy adoption, includes Preview to WeChat mobile, an HTML5 code editor for better article designs, and allows to broadcast stacks of up to 8 posts.  
Recipient followers can be the complete followers' list, followers assigned a WeChat tag, a list of followers registered on the WordPress website, or any custom target by simpley implementing a few actions and filters in a custom plugin.  

### Requirements

* A [China Mainland WeChat Official Account](https://mp.weixin.qq.com)
* **[WP Weixin](https://wordpress.org/plugins/wp-weixin)** installed, activated, enabled and properly configured.

### Overview

This plugin adds the following major features to WordPress:

* **WeChat Article Custom Post Type:** a post type to contain the content to be broadcasted to WeChat followers. Provided with an H5 code editor with live preview to build stunning h5-enhanced Wechat Articles.
* **WeChat Broadcast Custom Post Type:** a post type to build the WeChat Articles stack and broadcast it to WeChat followers.
* **Broadcast Message Content Builder**: WeChat Broadcast posts are built using an interactive interface with on-screen preview and removing & reordering items just like in the WeChat Official Account platform.
* **Duplicate as WeChat Article:** WordPress posts can be duplicated and then edited before being used in a WeChat Broadcast.
* **WeChat Article settings**: settings for each WeChat Article post - displayed author, Read More destination, comments, cover image, excerpt, ...
* **Send Preview to WeChat:** WeChat Broadcast posts can be sent to one or several followers for preview, either by WeChat ID or OpenID (useful if the WeChat ID is not known and the target follower of the preview is a WordPress user).
* **Target recipients by tag**: Select a tag previously created in the WeChat Official Account platform to send the broadcast only to users assigned the selected tag. The broadcast allowance (1 daily for Subscription accounts, 4 monthly for Service Accounts) will be decreased only for the followers tagged with the selected item.  
* **Target recipients with a list of WordPress users:** for WeChat user accounts that have been bound to a WordPress account, users who are followers can be manually selected (min. 2 users, max. 10,000) as target recipient of the broadcast. The broadcast allowance (1 daily for Subscription accounts, 4 monthly for Service Accounts) will be decreased only for the selected followers.
* **WeChat Broadcast Original Statement**: by default, WeChat Articles are marked with a declaration of Original Statement by WeChat ; if the statement is refused, an "Ignore Reprint Warning" option can be used to attempt to broadcast to followers anyway.
* **WeChat Broadcast log**: all the preview and broadcast communications with the WeChat Official Account platform and Material upload errors are logged and shown on the interface.  
* **Automatic Material Upload**: all images included in WeChat Articles (wether from the WordPress Media Library or externally sourced) are automatically uploaded to the WeChat Official Account platform on broadcast or preview, without duplicates ; a default image is used in case of broken images.
* **Scheduled WeChat Broadcasts**: broadcasting a message to WeChat followers via this plugin is fully integrated with WordPress post scheduling feature.
* **Delete WeChat Broadcast items**: after a broadcast, possibility to remove remote items or the whole stack if sent by mistake. 

Compatible with [WooCommerce Multilingual](https://wordpress.org/plugins/woocommerce-multilingual/), [WPML](http://wpml.org/), [Ultimate Member](https://wordpress.org/plugins/ultimate-member/), [WordPress Multisite](https://codex.wordpress.org/Create_A_Network), and [many caching plugins](https://github.com/froger-me/wp-weixin/blob/master/README.md#user-content-object-cache-considerations).

___

## Broadcast Target Types

By default, WP Weixin Broacast can target all users, tagged users, and a custom list of users registered both in WeChat and WordPress selected manually.  
Extra target types can be added by building a plugin to customise the targets of a Broadcast Message, according to custom criteria (for example, if recorded in WordPress: gender, location, product purchased, registration date, etc).  
All extra target types must target a list of WeChat openIDs (max 10,000).  
In this simple example, we add a new target type "My Target Type" with key `my_target_type` to the Broadcast Options by implementing a few action and filter hooks.  

### Implementing the `wp_weixin_broadcast_target_types` filter.

This filter makes sure the extra target type is selectable by adding it to the list of target types.

```php
add_filter( 'wp_weixin_broadcast_target_types', 'my_target_type_wp_weixin_broadcast_target_types', 10, 1 );
function my_target_type_wp_weixin_broadcast_target_types( $target_types ) {
	$target_types[] = array(
		'label' => __( 'My Target Type', 'my-domain' ),
		'value' => 'my_target_type',
	);

	return $target_types;
}

```

### Implementing the `wp_weixin_broadcast_targets_type_settings` action.

This action outputs the target type's settings.

```php
add_action( 'wp_weixin_broadcast_targets_type_settings', 'my_target_type_wp_weixin_broadcast_targets_type_settings', 10, 2 );
function my_target_type_wp_weixin_broadcast_targets_type_settings( $wechat_broadcast_msg_post, $target_type ) {
	// In this example, targets are openids in an array in database, and a comma-separated string when posted
	$targets         = get_post_meta( $wechat_broadcast_msg_post->ID, 'wp_weixin_broadcast_' . $target_type . '_targets', true );
	$targets_ouptput = empty( $targets ) ? '' : implode( PHP_EOL, $targets );
	$targets         = empty( $targets ) ? '' : implode( ',', $targets );
	?>
	<div class="wp-weixin-broadcast-to-my_target_type wp-weixin-broadcast-to-target-settings select2-ui-wait">
		<input type="hidden" id="my_target_type_targets" name="wechat_broadcast_target_my_target_type" value="<?php echo empty( $targets ) ? '' : esc_attr( $targets ); ?>">
		<!-- Replace with your settings markup here -->
		<p><?php esc_html_e( 'OpenIDs to send the Broadcast to - one per line:', 'my-domain' ); ?></p>
		<textarea id="my_target_type_values" cols="50" rows="7"><?php echo esc_html( $targets_ouptput ); ?></textarea>
		<!-- You may include javascript logic to update the value of the field 'my_target_type_targets' (must be a string) -->
		<!-- In this example, we update the value when the text area changes -->
		<script type="text/javascript">
			jQuery(function($) {
				var targetsContainer = $('#my_target_type_targets');

				$('#my_target_type_values').on('keyup', function() {
					targetsContainer.val($(this).val().split('\n').join(','));
				});
			});
		</script>
		<!-- The content of 'my_target_type_targets' field will be sanitized with filter_input( INPUT_POST, 'wechat_broadcast_target_' . $target_type, FILTER_SANITIZE_STRING ); -->
		<!-- Alternatively, values of any named field posted on form submit can be retrieved and used in the 'wp_weixin_broadcast_save_targets' action below, and you will need to sanitize it yourself -->
	</div>
	<?php
}
```

### Implementing the `wp_weixin_broadcast_save_targets` action.

This action saves the targets when the WeChat Broadcast Message form is saved.

```php
add_action( 'wp_weixin_broadcast_save_targets', 'my_target_type_wp_weixin_broadcast_save_targets', 10, 3 );
function my_target_type_wp_weixin_broadcast_save_targets( $wechat_broadcast_msg_id, $targets, $target_type ) {
	// The $targets argument is what is posted in the 'my_target_type_targets' field

	// Make sure to apply the action to my_target_type only
	if ( 'my_target_type' !== $target_type ) {

		return;
	}

	// Explode the targets - in this example, they are held in a comma-separated string
	$targets = empty( trim( $targets ) ) ? array() : explode( ',', $targets );
	// Remove empty values and white spaces
	foreach ( $targets as $index => $maybe_id ) {

		if ( empty( $targets[ $index ] ) ) {
			unset( $targets[ $index ] );
		} else {
			$targets[ $index ] = trim( $maybe_id );
		}
	}

	// Save the broadcast target type
	update_post_meta( $wechat_broadcast_msg_id, 'wp_weixin_broadcast_target_type', $target_type );
	// Save the targets - in this example, we save the array of target values
	update_post_meta( $wechat_broadcast_msg_id, 'wp_weixin_broadcast_' . $target_type . '_targets', $targets );
}
```

### Implementing `wp_weixin_broadcast_get_targets` filter.

This filter provides the array of openIDs to target when sending the broadcast to WeChat users.

```php
add_filter( 'wp_weixin_broadcast_get_targets', 'my_target_type_wp_weixin_broadcast_get_targets', 10, 3 );
function my_target_type_wp_weixin_broadcast_get_targets( $targets, $wechat_broadcast_msg_id, $target_type ) {
	// Make sure to apply the filter to my_target_type only
	if ( 'my_target_type' !== $target_type ) {

		return $targets;
	}

	$targets = get_post_meta( $wechat_broadcast_msg_id, 'wp_weixin_broadcast_' . $target_type . '_targets', true );

	// In this example, the meta value already contains an array of openIDs.
	// It can contain whatever you want according to your targetting logic (user gender, location(s), product purchased, etc...)
	// However, at this point, you need to make sure to return an array of openIDs.
	$openids = $targets;

	return $openids;
}
```

___

## Hooks - actions & filters

WP Weixin Broadcast gives developers the possibilty to customise its behavior with a series of custom actions and filters. 

### Actions

Actions index:
* [wp_weixin_broadcast_before_post_clone_to_wechat_article](#user-content-wp_weixin_broadcast_before_post_clone_to_wechat_article)
* [wp_weixin_broadcast_after_post_clone_to_wechat_article](#user-content-wp_weixin_broadcast_after_post_clone_to_wechat_article)
* [wp_weixin_broadcast_card_item](#user-content-wp_weixin_broadcast_card_item)
* [wp_weixin_broadcast_before_save_broadcast_message](#user-content-wp_weixin_broadcast_before_save_broadcast_message)
* [wp_weixin_broadcast_after_save_broadcast_message](#user-content-wp_weixin_broadcast_after_save_broadcast_message)
* [wp_weixin_broadcast_before_broadcast](#user-content-wp_weixin_broadcast_before_broadcast)
* [wp_weixin_broadcast_before_scheduled_broadcast](#user-content-wp_weixin_broadcast_before_scheduled_broadcast)
* [wp_weixin_broadcast_before_preview](#user-content-wp_weixin_broadcast_before_preview)
* [wp_weixin_broadcast_before_delete_remote](#user-content-wp_weixin_broadcast_before_delete_remote)
* [wp_weixin_broadcast_after_delete_remote](#user-content-wp_weixin_broadcast_after_delete_remote)
* [wp_weixin_broadcast_before_handle_job_response](#user-content-wp_weixin_broadcast_before_handle_job_response)
* [wp_weixin_broadcast_job_response_broadcast_message_not_found](#user-content-wp_weixin_broadcast_job_response_broadcast_message_not_found)
* [wp_weixin_broadcast_after_handle_job_response](#user-content-wp_weixin_broadcast_after_handle_job_response)
* [wp_weixin_broadcast_before_build_articles_message](#user-content-wp_weixin_broadcast_before_build_articles_message)
* [wp_weixin_broadcast_after_build_articles_message](#user-content-wp_weixin_broadcast_after_build_articles_message)
* [wp_weixin_broadcast_save_targets](#user-content-wp_weixin_broadcast_save_targets)
* [wp_weixin_broadcast_after_preview](#user-content-wp_weixin_broadcast_after_preview)
* [wp_weixin_broadcast_after_broadcast](#user-content-wp_weixin_broadcast_after_broadcast)

___

#### wp_weixin_broadcast_before_post_clone_to_wechat_article

```php
do_action( 'wp_weixin_broadcast_before_post_clone_to_wechat_article', int $post_id );
```

**Description**  
Fired before cloning a post into a WeChat Article.  

**Parameters**  
$post_id  
> (int) The ID of the post to clone.   
___

#### wp_weixin_broadcast_after_post_clone_to_wechat_article

```php
do_action( 'wp_weixin_broadcast_after_post_clone_to_wechat_article', int $post_id, int $wechat_article_post_id );
```

**Description**  
Fired after cloning a post into a WeChat Article.  

**Parameters**  
$post_id  
> (int) The ID of the post cloned.  

$wechat_article_post_id  
> (int) The ID of the newly created WeChat Article.  
___

#### wp_weixin_broadcast_card_item

```php
do_action( 'wp_weixin_broadcast_card_item', array $card );
```

**Description**  
Fired when an item (aka "card") in the WeChat Broadcast Message stack has the `output` value set to something other than `card-article`.  
Expected to output markup for content of the item in the Broadcast Message Content.  
A corresponding `broadcastCardBuilder` JavaScript method should also be added (see [The `WP_Weixin_Broadcast.broadcastCardBuilder` object](#user-content-the-wp_weixin_broadcastcardbuilder-object) section of the documentation).  
Please note the appearance of cards is only customised for aesthetic purposes in WordPress: it will not change the appearance of the card in WeChat when broadcasted.  

**Parameters**  
$card  
> (array) The card item. Structure of a card item:  
```php
array(
	'id'               => $post_id,     // (int)    the ID of the post corresponding to the item to broadcast
	'title'            => $post_title,  // (string) the title of the post to broadcast
	'type'             => $post_type,   // (string) the type of the post
	'output'           => $ouptut,      // (string) the output format of the card item - default to `card-article`
	'background_thumb' => $thumb_cover, // (string) the thumbnail of the card (displayed when the card is not the first item in the stack)
	'background_full'  => $full_cover,  // (string) the background of the card (displayed when the card is the first item in the stack)
	'is_dirty'         => $is_dirty,    // (bool)   wether the post corresponding to the card should be trusted (`false` if the corresponding post was deleted or is in trash)
);
```
___

#### wp_weixin_broadcast_before_save_broadcast_message

```php
do_action( 'wp_weixin_broadcast_before_save_broadcast_message', int $wechat_broadcast_msg_id );
```

**Description**  
Fired before saving a WeChat Broadcast.  

**Parameters**  
$wechat_broadcast_msg_id  
> (int) The post ID of the WeChat Broadcast.  
___

#### wp_weixin_broadcast_after_save_broadcast_message

```php
do_action( 'wp_weixin_broadcast_after_save_broadcast_message', int $wechat_broadcast_msg_id );
```

**Description**  
Fired after saving a WeChat Broadcast.  

**Parameters**  
$wechat_broadcast_msg_id  
> (int) The post ID of the WeChat Broadcast.  
___

#### wp_weixin_broadcast_before_broadcast

```php
do_action( 'wp_weixin_broadcast_before_broadcast', int $wechat_broadcast_msg_id );
```

**Description**  
Fired before broadcasting to WeChat users (not scheduled).  

**Parameters**  
$wechat_broadcast_msg_id  
> (int) The post ID of the WeChat Broadcast.  
___

#### wp_weixin_broadcast_before_scheduled_broadcast

```php
do_action( 'wp_weixin_broadcast_before_scheduled_broadcast', int $wechat_broadcast_msg_id );
```

**Description**  
Fired before broadcasting to WeChat users (scheduled).  

**Parameters**  
$wechat_broadcast_msg_id  
> (int) The post ID of the WeChat Broadcast.  
___

#### wp_weixin_broadcast_before_preview

```php
do_action( 'wp_weixin_broadcast_before_preview', int $wechat_broadcast_msg_id );
```

**Description**  
Fired before sending a broadcast preview to selected WeChat users.  

**Parameters**  
$wechat_broadcast_msg_id  
> (int) The post ID of the WeChat Broadcast.  
___

#### wp_weixin_broadcast_before_delete_remote

```php
do_action( 'wp_weixin_broadcast_before_delete_remote', int $wechat_broadcast_msg_id );
```

**Description**  
Fired before deleting remote items of a broadcast.  

**Parameters**  
$wechat_broadcast_msg_id  
> (int) The post ID of the WeChat Broadcast.  
___

#### wp_weixin_broadcast_after_delete_remote

```php
do_action( 'wp_weixin_broadcast_after_delete_remote', int $wechat_broadcast_msg_id, array $outcome );
```

**Description**  
Fired after deleting remote items of a broadcast.  

**Parameters**  
$wechat_broadcast_msg_id  
> (int) The post ID of the WeChat Broadcast.  

$outcome
> (array) The outcome of the operation - Structure of the outcome:
```php
array(
	'result'   => $result,   // (bool)   wether the operation was successful
	'response' => $response, // (bool)   the response received from WeChat
	'error'    => $error,    // (mixed)  if the operation was not succcessful, an array with keys `code` and `message` ; false otherwise
	'params'   => array(     // (array)  the parameters originally sent to WeChat to perform the operation
		'index'  => $index,  // (int)    the index of the item to delete between 1 and 8, or 0 for all items
		'msg_id' => $msg_id, // (string) the rich material ID of the broadcast in WeChat
	),
);
```  
___

#### wp_weixin_broadcast_before_handle_job_response

```php
do_action( 'wp_weixin_broadcast_before_handle_job_response', array $request_data );
```

**Description**  
Fired before handling the request received from WeChat when the broadcast job has finished.  

**Parameters**  
$request_data  
> (array) The data sent in the request from WeChat.  
___

#### wp_weixin_broadcast_job_response_broadcast_message_not_found

```php
do_action( 'wp_weixin_broadcast_job_response_broadcast_message_not_found', array $request_data );
```

**Description**  
Fired after receiving a request from WeChat when the broadcast job has finished but the post ID of the WeChat Broadcast was not found.  

**Parameters**  
$request_data  
> (array) The data sent in the request from WeChat.  
___

#### wp_weixin_broadcast_after_handle_job_response

```php
do_action( 'wp_weixin_broadcast_after_handle_job_response', array $request_data, int $wechat_broadcast_msg_id );
```

**Description**  
Fired after handling the request received from WeChat when the broadcast job has finished.  

**Parameters**  
$param  
> (array) The data sent in the request from WeChat.  

$wechat_broadcast_msg_id  
> (int) The post ID of the WeChat Broadcast.  
___

#### wp_weixin_broadcast_before_build_articles_message

```php
do_action( 'wp_weixin_broadcast_before_build_articles_message', int $wechat_broadcast_msg_id, array $post_ids );
```

**Description**  
Fired before building the rich media asset sent to WeChat to perform a preview or a broadcast.  

**Parameters**  
$wechat_broadcast_msg_id  
> (int) The post ID of the WeChat Broadcast.  

$post_ids  
> (array) The IDs of the posts which content will be used to send a message to WeChat.  
___

#### wp_weixin_broadcast_after_build_articles_message

```php
do_action( 'wp_weixin_broadcast_after_build_articles_message', int $wechat_broadcast_msg_id, array $post_ids, array $message );
```

**Description**  
Fired after building the rich media asset sent to WeChat to perform a preview or a broadcast.  

**Parameters**  
$wechat_broadcast_msg_id  
> (int) The post ID of the WeChat Broadcast.  

$post_ids  
> (array) The IDs of the posts which content will be used to send a message to WeChat.  

$message  
> (array) The array representation of the message to send to WeChat.  
___

#### wp_weixin_broadcast_save_targets

```php
do_action( 'wp_weixin_broadcast_save_targets', int $wechat_broadcast_msg_id, mixed $targets, string $target_type );
```

**Description**  
Fired when the WeChat Broadcast is saved and the target type is not equal to `all`, `tag` or `users`.  

**Parameters**  
$wechat_broadcast_msg_id  
> (int) The post ID of the WeChat Broadcast.  

$targets
> (mixed) The targets to save - result of:
```php
filter_input( INPUT_POST, 'wechat_broadcast_target_' . $target_type, FILTER_SANITIZE_STRING );
```

$target_type
> (string) The target type of the targets to save.  
___

#### wp_weixin_broadcast_after_preview

```php
do_action( 'wp_weixin_broadcast_after_preview', int $wechat_broadcast_msg_id, array $outcomes );
```

**Description**  
Fired after sending a broadcast preview to selected WeChat users.  

**Parameters**  
$wechat_broadcast_msg_id  
> (int) The post ID of the WeChat Broadcast.  

$outcomes
> (array) The outcomes of the operation, one for each recipient of the preview - Structure of the outcomes:
```php
array(
	array(
		'result'   => $result,   // (bool)   wether the operation was successful
		'response' => $response, // (bool)   the response received from WeChat
		'error'    => $error,    // (mixed)  if the operation was not succcessful, an array with keys `code` and `message` ; false otherwise
		'id'       => $id,       // (mixed)  the ID of the recipient of the preview
		'id_type'  => $id_type,  // (string) the type of ID used - `wxname` or `openid`
	),
	...
);
```  
___

#### wp_weixin_broadcast_after_broadcast

```php
do_action( 'wp_weixin_broadcast_after_broadcast', int $wechat_broadcast_msg_id, array $outcome );
```

**Description**  
Fired after broadcasting to WeChat users (scheduled or not).  

**Parameters**  
$wechat_broadcast_msg_id  
> (int) The post ID of the WeChat Broadcast.  

$outcome
> (array) The outcome of the operation - Structure of the outcome:
```php
array(
	'result'   => $result,   // (bool)   wether the operation was successful
	'response' => $response, // (bool)   the response received from WeChat
	'error'    => $error,    // (mixed)  if the operation was not succcessful, an array with keys `code` and `message` ; false otherwise
	'params'   => $params,   // (array)  the parameters originally sent to WeChat to perform the operation
);
```  
___

### Filters

Filters index:

* [wp_weixin_broadcast_wechat_article_caps](#user-content-wp_weixin_broadcast_wechat_article_caps)
* [wp_weixin_broadcast_wechat_article_roles](#user-content-wp_weixin_broadcast_wechat_article_roles)
* [wp_weixin_broadcast_no_duplicate_post_types](#user-content-wp_weixin_broadcast_no_duplicate_post_types)
* [wp_weixin_broadcast_card_output](#user-content-wp_weixin_broadcast_card_output)
* [wp_weixin_broadcast_message_cards_data](#user-content-wp_weixin_broadcast_message_cards_data)
* [wp_weixin_broadcast_broadcastable_post_types](#user-content-wp_weixin_broadcast_broadcastable_post_types)
* [wp_weixin_broadcast_broadcast_message_caps](#user-content-wp_weixin_broadcast_broadcast_message_caps)
* [wp_weixin_broadcast_broadcast_message_roles](#user-content-wp_weixin_broadcast_broadcast_message_roles)
* [wp_weixin_broadcast_default_pic_urls](#user-content-wp_weixin_broadcast_default_pic_urls)
* [wp_weixin_broadcast_deleted_item_pic_urls](#user-content-wp_weixin_broadcast_deleted_item_pic_urls)
* [wp_weixin_broadcast_target_types](#user-content-wp_weixin_broadcast_target_types)
* [wp_weixin_broadcast_articles_message](#user-content-wp_weixin_broadcast_articles_message)
* [wp_weixin_broadcast_get_targets](#user-content-wp_weixin_broadcast_get_targets)
* [wp_weixin_broadcast_default_cover_path](#user-content-wp_weixin_broadcast_default_cover_path)

___
#### wp_weixin_broadcast_wechat_article_caps

```php
apply_filters( 'wp_weixin_broadcast_wechat_article_caps', array $caps, string $role );
```

**Description**  
Filter the capabilities of a specific role for WeChat Article posts.  

**Parameters**  
$caps
> (array) The WordPress capabilities. Default:
```php
array(
	'create_wechat_articles',
	'edit_wechat_article',
	'read_wechat_article',
	'delete_wechat_article',
	'edit_wechat_articles',
	'edit_others_wechat_articles',
	'publish_wechat_articles',
	'read_private_wechat_articles',
);
```  

$role
> (string) The WordPress role.  
___

#### wp_weixin_broadcast_wechat_article_roles

```php
apply_filters( 'wp_weixin_broadcast_wechat_article_roles', array $roles );
```

**Description**  
Filter the roles allowed to interact with WeChat Article posts.  

**Parameters**  
$roles
> (array) The roles allowed to interact with WeChat Article posts.  
```php
array(
	'administrator',
	'editor',
);
```
___

#### wp_weixin_broadcast_no_duplicate_post_types

```php
apply_filters( 'wp_weixin_broadcast_no_duplicate_post_types', array $post_types );
```

**Description**  
Filter the post types not available for duplication in a WeChat Article.  
By default, all post types except WeChat Broadcast can be duplicated.

**Parameters**  
$post_types
> (array) The list of post types.  
___

#### wp_weixin_broadcast_card_output

```php
apply_filters( 'wp_weixin_broadcast_card_output', string $card_output_type, int $post_id );
```

**Description**  
Filter the output type of a card item (see [wp_weixin_broadcast_card_item](#user-content-wp_weixin_broadcast_card_item) action hook).  

**Parameters**  
$card_output_type
> (string) The type of output. Default `card-article`.  

$post_id
> (int) The ID of the post corresponding to the item to broadcast.  
___

#### wp_weixin_broadcast_message_cards_data

```php
apply_filters( 'wp_weixin_broadcast_message_cards_data', array $cards_data, int $wechat_broadcast_msg_id );
```

**Description**  
Filter card items data of the Broadcast Message Content of a WeChat Broadcast post (see [wp_weixin_broadcast_card_item](#user-content-wp_weixin_broadcast_card_item) action hook).  

**Parameters**  
$cards_data
> (array) The card items data.  

$wechat_broadcast_msg_id
> (int) The post ID of the WeChat Broadcast.  
___

#### wp_weixin_broadcast_broadcastable_post_types

```php
apply_filters( 'wp_weixin_broadcast_broadcastable_post_types', $broadcastable_post_types );
```

**Description**  
Filter the post types that can be used to build the Broadcast Message Content of a WeChat Broadcast post.  

**Parameters**  
$broadcastable_post_types
> (array) the post types that can be used to build the Broadcast Message Content of a WeChat Broadcast post.  

___

#### wp_weixin_broadcast_broadcast_message_caps

```php
apply_filters( 'wp_weixin_broadcast_broadcast_message_caps', array $caps, string $role );
```

**Description**  
Filter the capabilities of a specific role for WeChat Broadcast posts.  

**Parameters**  
$caps
> (array) The WordPress capabilities. Default:
```php
array(
	'create_wechat_broadcast_message',
	'edit_wechat_broadcast_message',
	'read_wechat_broadcast_message',
	'delete_wechat_broadcast_message',
	'edit_wechat_broadcast_messages',
	'edit_others_wechat_broadcast_messages',
	'publish_wechat_broadcast_messages',
	'read_wechat_broadcast_messages',
);
```  

$role
> (string) The WordPress role.  
___

#### wp_weixin_broadcast_broadcast_message_roles

```php
apply_filters( 'wp_weixin_broadcast_broadcast_message_roles', array $roles );
```

**Description**  
Filter the roles allowed to interact with WeChat Broadcast posts.  

**Parameters**  
$roles
> (array) The roles allowed to interact with WeChat Broadcast posts.  
```php
array(
	'administrator',
	'editor',
);
```
___

#### wp_weixin_broadcast_default_pic_urls

```php
apply_filters( 'wp_weixin_broadcast_default_pic_urls', array $default_image_urls, int $post_id );
```

**Description**  
Filter the urls of the default images of the card item to add to the WeChat Broadcast.  

**Parameters**  
$default_image_urls
> (array) $default_image_urls The urls of the default images of the card item. Structure:  
```php
array(
	'full'  => $full_url // (string) the url of the thumbnail of the card (shown when the card is not the first item in the stack)
	'thumb' => $thum_url // (string) the url of the background of the card (shown when the card is the first item in the stack)   
),
```

$post_id
> (int) The ID of the post corresponding to the item to broadcast.  
___

#### wp_weixin_broadcast_deleted_item_pic_urls

```php
apply_filters( 'wp_weixin_broadcast_deleted_item_pic_urls', array $deleted_image_urls, int $post_id );
```

**Description**  
Filter the urls of the images of the card item to add to the WeChat Broadcast when the corresponding post ID does not exist.  

**Parameters**  
$deleted_image_urls
> (array) The urls of the images of the card item when the corresponding post ID does not exist. Structure:  
```php
array(
	'full'  => $full_url // (string) the url of the thumbnail of the card (shown when the card is not the first item in the stack)
	'thumb' => $thum_url // (string) the url of the background of the card (shown when the card is the first item in the stack)   
),
```

$post_id
> (int) The ID of the post corresponding to the item to broadcast.  
___

#### wp_weixin_broadcast_target_types

```php
apply_filters( 'wp_weixin_broadcast_target_types', array $target_types );
```

**Description**  
Filter the list of target types available for broadcast.  

**Parameters**  
$target_types
> (array) The list of target types available for broadcast. Default:  
```php
array(
	array(
		'label' => __( 'All users', 'wp-weixin-broadcast' ),
		'value' => 'all',
	),
	array(
		'label' => __( 'Select by tag', 'wp-weixin-broadcast' ),
		'value' => 'tag',
	),
	array(
		'label' => __( 'WordPress WeChat users', 'wp-weixin-broadcast' ),
		'value' => 'users',
	),
);
```
___

#### wp_weixin_broadcast_articles_message

```php
apply_filters( 'wp_weixin_broadcast_articles_message', array $rich_media_articles, array $post_ids );
```

**Description**  
Filter rich media articles items of the message to be sent to WeChat.  

**Parameters**  
$rich_media_articles
> (array) The rich media article items. Structure:
```php
array(
	'articles' => array(
		'thumb_media_id'        => $thumb_media_id        // (string) $cover_id,
		'author'                => $author                // (string) get_post_meta( $post->ID, 'wp_weixin_broadcast_article_author', true ),
		'title'                 => $title                 // (string) $post->post_title,
		'content_source_url'    => $content_source_url    // (string) get_post_meta( $post->ID, 'wp_weixin_broadcast_article_origin_url', true ),
		'content'               => $content               // (string) $this->replace_images( do_shortcode( $post->post_content ) ),
		'digest'                => $digest                // (string) substr( $post->post_excerpt, 0, 64 ),
		'show_cover_pic'        => $show_cover_pic        // (int)    1 if the cover picture needs to be shown in the article, 0 otherwise
		'need_open_comment'     => $need_open_comment     // (int)    1 if comments need to be open, 0 otherwise
		'only_fans_can_comment' => $only_fans_can_comment // (int)    1 if only fans can comment, 0 otherwise
	),
	...
)
```

$post_ids
> (array) The ID of the corresponding posts.  
___

#### wp_weixin_broadcast_get_targets

```php
apply_filters( 'wp_weixin_broadcast_get_targets', array $targets, int $wechat_broadcast_msg_id, string $target_type );
```

**Description**  
Filter the targets of the broadcast for a target type other than `all`, `tag` or `users` - expected to return an array of openIDs.  

**Parameters**  
$targets
> (array) The targets of the broadcast. Default is an empty array.  

$wechat_broadcast_msg_id
> (int) The post ID of the WeChat Broadcast.  

$target_type
> (string) The target type - other than `all`, `tag` or `users`.  
___

#### wp_weixin_broadcast_default_cover_path

```php
apply_filters( 'wp_weixin_broadcast_default_cover_path', string $default_cover_image_path, $default_cover_media_id );
```

**Description**  
Filter the path of the default cover image used when posts used to build item to broadcast do not have one.  
NOTE: after implementing this filter, because this file will be referenced in the broadcast by `media_id`, it is necessary to delete the `wp_weixin_broadcast_default_cover_media_id` option in WordPress options database table to refresh the image in the WeChat backend's file library.  

**Parameters**  
$default_cover_image_path
> (type) The absolute path of the default cover image.  

$default_cover_media_id
> (type) The `media_id` of the corresponding image in the WeChat backend's file library.  
___

## JavaScript

The `WP_Weixin_Broadcast` object can be accessed and augmented when needed.

To use it properly, developers must:
- include their scripts in `admin_enqueue_scripts` action hook with a priority of `10` or more,
- make sure to set `wp-weixin-broadcast-main-script` as a dependency

### The `WP_Weixin_Broadcast.broadcastCardBuilder` object

If a card output in the Broadcast Message Content is different from `card-article` (achieved by implementing the `wp_weixin_broadcast_card_output` filter and the the `wp_weixin_broadcast_card_item` action hooks), it is also necessary to let the interface know how to build cards for the custom output by providing a corresponding method to the `WP_Weixin_Broadcast.broadcastCardBuilder` object.  

```JavaScript
/* global WP_Weixin_Broadcast */
jQuery(function($) {

/* --------------------------------------------------------------------------------------------------------------
  | Properties of the `cardData` parameter:                                                                         |
  | - id         => (int)    the ID of the post corresponding to the item to broadcast                          |
  | - text       => (string) the title of the post to broadcast                                                 |
  | - output     => (string) the output format of the card item - in this example `card-custom`                 |
  | - type       => (string) the type of the post                                                               |
  | - thumbCover => (string) the thumbnail of the card (shown when the card is not the first item in the stack) |
  | - fullCover  => (string) the background of the card (shown when the card is the first item in the stack)    |
  -------------------------------------------------------------------------------------------------------------- */

	WP_Weixin_Broadcast.broadcastCardBuilder['card-custom'] = function(cardData) {
		var customCard = $('#wp_weixin_broadcast_message_item_template_container .card').clone();

		// Do something with the customCard element skeleton to build the card

		customCard.addClass('card-custom');
		customCard.attr({
			'data-id'  : cardData.id,
			'data-type': cardData.type,
			'data-name': cardData.text
		});

		return customCard;
	};

});
```