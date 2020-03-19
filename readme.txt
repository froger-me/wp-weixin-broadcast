=== WP Weixin Broadcast ===
Contributors: frogerme
Tags: wechat, wechat share, wechat broadcast, 微信, 微信分享, 微信公众号
Requires at least: 4.9.5
Tested up to: 5.2.2
Stable tag: trunk
Requires PHP: 7.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

WeChat Broadcast for WordPress

== Description ==

WP Weixin Broadcast is a companion plugin for WP Weixin that adds the possibility to broadcast WordPress posts to WeChat followers.
It provides an interface very similar to the WeChat Official Account platform for easy adoption, includes Preview to WeChat mobile, an HTML5 code editor for better article designs, and allows to broadcast stacks of up to 8 posts.
Recipient followers can be the complete followers' list, followers assigned a WeChat tag, a list of followers registered on the WordPress website, or any custom target by simpley implementing a few actions and filters in a custom plugin.

### Requirements

* A [China Mainland WeChat Official Account](https://mp.weixin.qq.com).
* **[WP Weixin](https://wordpress.org/plugins/wp-weixin)** installed, activated, enabled and properly configured.

### Important Notes

* Make sure to read the "TROUBLESHOOT, FEATURE REQUESTS AND 3RD PARTY INTEGRATION" section below and [the full documentation](https://github.com/froger-me/wp-weixin-broadcast/blob/master/README.md) before contacting the author.

### Overview

This plugin adds the following major features to WordPress and WP Weixin:

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

### Troubleshoot, feature requests and 3rd party integration

Unlike most WeChat integration plugins, WP Weixin Broadcast is provided for free.  

WP Weixin Broadcast is regularly updated, and bug reports are welcome, preferably on [Github](https://github.com/froger-me/wp-weixin-broadcast/issues). Each bug report will be addressed in a timely manner, but issues reported on WordPress may take significantly longer to receive a response.  

WP Weixin Broadcast has been tested with the latest version of WordPress - in case of issue, please ensure you are able to reproduce it with a default installation of WordPress, and a default theme before reporting a bug.  

Feature requests (such as "it would be nice to have XYZ") or 3rd party integration requests (such as "it is not working with XYZ plugin" or "it is not working with my theme") will be considered only after receiving a red envelope (红包) of a minimum RMB 500 on WeChat (guarantee of best effort, no guarantee of result). 

To add the author on WeChat, click [here](https://froger.me/wp-content/uploads/2018/04/wechat-qr.png), scan the WeChat QR code, and add "Woo WeChatPay" as a comment in your contact request.  

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the plugin files to the `/wp-content/plugins/wp-weixin-broadcast` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress

== Screenshots ==
 
1. The main settings to integrate WordPress with WeChat.

== Changelog ==

= 1.3.11 =
* WC tested up to: 4.0.0
* WP Weixin tested up to: 1.3.11

= 1.3.10 =
* WC tested up to: 3.9.2
* WP Weixin tested up to: 1.3.10

= 1.3.9 =
* Make sure the altered featured image metabox is only affecting WeChat Article post type.
* WC tested up to: 3.9.1
* WP Weixin tested up to: 1.3.9

= 1.3.8 =
* WP Weixin tested up to: 1.3.8
* Completely revamp the editor: add H5 advanced editor with live preview
* Fix send preview to WeChat: make sure not to delete and rebuild permanent assets twice
* Full revamp of the log mechanism wih backward compatibility - periodically flush to get only the last 10 logs per broadcast message, output with a template and save a serialize array of info instead of HTML
* Added 18 action hooks and 14 filter hooks
* Added full documentation
* Added button to check broadcast status
* Refactor to avoid code repetition
* General optimisation

= 1.3.7 =
* WP Weixin tested up to: 1.3.7

= 1.3.6 =
* WC tested up to: 3.9.0

= 1.3.5 =
* First version
* Bump version to match WP Weixin