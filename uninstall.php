<?php

if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit; // Exit if accessed directly}
}

global $wpdb;

$transient_prefix = $wpdb->esc_like( '_transient_wp_weixin_broadcast_' ) . '%';
$option_prefix    = $wpdb->esc_like( 'wp_weixin_broadcast_' ) . '%';
$sql              = "DELETE FROM $wpdb->options WHERE `option_name` LIKE '%s' OR `option_name` LIKE '%s'";

$wpdb->query( $wpdb->prepare( $sql, $option_prefix . '%', $transient_prefix . '%' ) ); // @codingStandardsIgnoreLine

$sql          = "SELECT `ID` FROM $wpdb->posts WHERE `post_type` = %s OR `post_type` = %s";
$ids          = $wpdb->get_col( $wpdb->prepare( $sql, 'wechat_article', 'wechat_roadcast_msg' ) ); // @codingStandardsIgnoreLine
$count_ids    = count( $ids );
$placeholders = array_fill( 0, count( $ids ), '%d' );
$format       = implode( ', ', $placeholders );
$sql          = "DELETE FROM $wpdb->posts WHERE `post_type` = %s OR `post_type` = %s";

$wpdb->query( $wpdb->prepare( $sql, 'wechat_article', 'wechat_broadcast_msg' ) ); // @codingStandardsIgnoreLine

$sql = "DELETE FROM $wpdb->postmeta WHERE post_id IN ($format)";

$wpdb->query( $wpdb->prepare( $sql, $ids ) ); // @codingStandardsIgnoreLine

$meta_key_prefix = $wpdb->esc_like( 'wp_weixin_broadcast_' ) . '%';
$sql             = "DELETE FROM $wpdb->usermeta WHERE meta_key LIKE '%s'";

$wpdb->query( $wpdb->prepare( $sql, $meta_key_prefix ) ); // @codingStandardsIgnoreLine

$sql = 'DROP TABLE ' . $wpdb->prefix . 'wp_weixin_broadcast_message_logs';

$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'wp_weixin_broadcast_message_logs' );
