<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WP_Weixin_Broadcast_Logger {

	protected $wechat;
	protected $post_type;

	public function __construct( $wechat, $post_type, $init_hooks = false ) {
		$this->wechat    = $wechat;
		$this->post_type = $post_type;

		if ( $init_hooks ) {
			// Add metaboxes
			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 10, 0 );
		}
	}

	/*******************************************************************
	 * Public methods
	 *******************************************************************/

	public function add_meta_boxes() {
		$metaboxes = array(
			'log' => array(
				'title'    => __( 'Log - latest operations', 'wp-weixin-broadcast' ),
				'context'  => 'side',
				'callback' => array( $this, 'log_meta_box' ),
				'priority' => 'core',
			),
		);

		foreach ( $metaboxes as $id => $metabox ) {
			add_meta_box(
				$id,
				$metabox['title'],
				$metabox['callback'],
				$this->post_type,
				$metabox['context'],
				$metabox['priority']
			);
		}
	}

	public function log_meta_box( $post ) {
		$logs = $this->get_logs( $post->ID );

		foreach ( $logs as $log ) {
			$log->remark = $this->format_log_remark( $log->status, $log->type, $log->code, $log->remark );
		}

		include WP_WEIXIN_BROADCAST_PLUGIN_PATH . 'inc/templates/admin/broadcast-message-log.php';
	}

	public function log_preview_responses( $post_id, $message, $responses ) {
		$type   = 'preview';
		$return = array();

		foreach ( $responses as $response ) {
			$status      = 'success';
			$code        = 'ok';
			$err_message = '';

			if ( true !== $response['result'] ) {
				$status      = 'failure';
				$code        = $response['error']['code'];
				$err_message = $response['error']['message'];
			}

			$remark_info = array(
				'excerpt_values'   => array(
					'id'      => $response['id'],
					'id_type' => $response['id_type'],
				),
				'err_message'      => $err_message,
				'details_sections' => array(
					array(
						'title'   => 'msg_to_api',
						'content' => $this->log_content_print_r( $message ),
					),
				),
			);

			$return[] = $this->add_log( $status, $type, $code, $remark_info, $post_id );
		}

		return $return;
	}

	public function log_broadcast_response( $post_id, $message, $response, $scheduled = false ) {
		$type        = $scheduled ? 'scheduled_broadcast' : 'direct_broadcast';
		$status      = 'success';
		$code        = 'ok';
		$err_message = '';
		$target      = 'all';

		if ( isset( $response['params']['openids'] ) ) {
			$target        = 'openids';
			$openids_count = count( $response['params']['openids'] );
		} elseif ( isset( $response['params']['tag'] ) ) {
			$target     = 'tag';
			$tags       = json_decode( get_transient( 'wp_weixin_broadcast_user_tags' ), true );
			$target_tag = array();

			if ( ! $tags || empty( $tags ) ) {
				$tags = $this->wechat->tags();

				if ( $tags && ! empty( $tags ) ) {
					$tags[0]['name'] = __( 'Starred', 'wp-weixin-broadcast' );
				}

				set_transient( 'wp_weixin_broadcast_user_tags', wp_json_encode( $tags, true ), 3600 );
			}

			foreach ( $tags as $index => $tag ) {

				if ( absint( $response['params']['tag'] ) === absint( $tag['id'] ) ) {
					$target_tag = $tag;

					break;
				}
			}

			$tag_name = $target_tag['name'];
			$tag_id   = $target_tag['id'];
		}

		if ( true !== $response['result'] ) {
			$status      = 'failure';
			$code        = $response['error']['code'];
			$err_message = $response['error']['message'];
		}

		$remark_info = array(
			'excerpt_values'   => array(
				'openids_count' => isset( $openids_count ) ? $openids_count : false,
				'id_type'       => $response['id_type'],
				'tag_name'      => isset( $tag_name ) ? $tag_name : false,
				'tag_id'        => isset( $tag_id ) ? $tag_id : false,
			),
			'target'           => $target,
			'err_message'      => $err_message,
			'details_sections' => array(
				array(
					'title'   => 'params_to_api',
					'content' => $this->log_content_print_r( $response['params'] ),
				),
				array(
					'title'   => 'msg_to_api',
					'content' => $this->log_content_print_r( $message ),
				),
				array(
					'title'   => 'msg_from_api',
					'content' => $this->log_content_print_r( $response['response'] ),
				),
			),
		);

		$this->add_log( $status, $type, $code, $remark_info, $post_id );
	}

	public function log_delete_response( $response, $post_id ) {
		$type        = 'delete_item';
		$status      = 'success';
		$code        = 'ok';
		$err_message = '';
		$index       = $response['params']['index'];

		if ( true !== $response['result'] ) {
			$status      = 'failure';
			$code        = $response['error']['code'];
			$err_message = $response['error']['message'];
		}

		$remark_info = array(
			'excerpt_values'   => array(
				'deleted_index' => $index,
			),
			'err_message'      => $err_message,
			'details_sections' => array(
				array(
					'title'   => 'params_to_api',
					'content' => $this->log_content_print_r( $response['params'] ),
				),
				array(
					'title'   => 'msg_from_api',
					'content' => $this->log_content_print_r( $response['response'] ),
				),
			),
		);

		$this->add_log( $status, $type, $code, $remark_info, $post_id );
	}

	public function log_job_complete_response( $post_id, $message ) {
		$type                    = 'remote_job_result';
		$status                  = 'warning';
		$code                    = 'ok';
		$filtered_ratio          = ( $message['filtercount'] + $message['errorcount'] ) . '/' . $message['totalcount'];
		$error_ratio             = $message['errorcount'] . '/' . ( $message['filtercount'] + $message['errorcount'] );
		$success_ratio           = $message['sentcount'] . '/' . ( $message['filtercount'] + $message['errorcount'] );
		$copyright_issue_remarks = array();
		$err_message             = '';
		$show_job_result         = true;

		if (
			isset(
				$message['copyrightcheckresult'],
				$message['copyrightcheckresult']->ResultList,
				$message['copyrightcheckresult']->ResultList->item
			)
		) {
			foreach ( $message['copyrightcheckresult']->ResultList->item as $item ) {
				$copyright_issue_remarks[] = sprintf(
					// translators: %1$s is the article's index, %2$s is the URL of the original
					__(
						'Item in position %1$s (<a href="%2$s" target="_blank">View similar existing article</a>).',
						'wp-weixin-broadcast'
					),
					$item->ArticleIdx, // @codingStandardsIgnoreLine
					$item->OriginalArticleUrl // @codingStandardsIgnoreLine
				);
			}
		}

		switch ( $message['status'] ) {
			case 'send success':
				$status = 'success';
				break;
			case 'send fail':
				$code   = 'fail';
				$status = 'failure';
				break;
		}

		if ( false !== strpos( $message['status'], 'err(' ) ) {
			$code = str_replace( ')', '', str_replace( 'err(', '', $message['status'] ) );
		}

		$remark_info = array(
			'excerpt_values'   => array(
				'deleted_index' => $index,
			),
			'err_message'      => $err_message,
			'filtered_ratio'   => $filtered_ratio,
			'success_ratio'    => $success_ratio,
			'error_ratio'      => $error_ratio,
			'copyright_issues' => $copyright_issue_remarks,
			'details_sections' => array(
				array(
					'title'   => 'push_event_from_api',
					'content' => $this->log_content_print_r( $message ),
				),
			),
		);

		$this->add_log( $status, $type, $code, $remark_info, $post_id );

		return $status;
	}

	public function log_add_image_error( $error, $image_path, $post_id ) {
		$status          = 'failure';
		$excerpt_message = __( 'Failed to upload default cover image to WeChat.', 'wp-weixin-broadcast' );
		$code            = $error['code'];
		$err_message     = $error['message'];
		$remark_info     = array(
			'err_message'      => $err_message,
			'details_sections' => array(
				array(
					'title'   => 'image_path',
					'content' => $this->log_content_print_r( $image_path ),
				),
			),
		);

		$this->add_log( $status, 'add_image', $code, $remark_info, $post_id );
	}

	public function log_rich_media_error( $error, $post_id ) {
		$status      = 'failure';
		$code        = $error['code'];
		$err_message = $error['message'];
		$remark_info = array(
			'err_message' => $err_message,
		);

		$this->add_log( $status, 'add_rich_media', $code, $remark_info, $post_id );
	}

	public function log_add_temporary_media_error( $error, $image_path, $post_id ) {
		$status          = 'failure';
		$excerpt_message = __( 'Failed to replace image in content and upload to WeChat.', 'wp-weixin-broadcast' );
		$code            = $error['code'];
		$err_message     = $error['message'];
		$remark_info     = array(
			'err_message'      => $err_message,
			'details_sections' => array(
				array(
					'title'   => 'image_path',
					'content' => $this->log_content_print_r( $image_path ),
				),
			),
		);

		$this->add_log( $status, 'add_temporary_media', $code, $remark_info, $post_id );
	}

	public function delete_log_by_post_id( $post_id ) {
		$this->delete_log( 'post_id', $post_id );
	}

	/*******************************************************************
	 * Protected methods
	 *******************************************************************/

	protected function add_log( $status, $type, $code, $remark, $post_id ) {
		global $wpdb;

		$return = array();
		$log    = array(
			'id'      => null,
			'post_id' => $post_id,
			'status'  => $status,
			'type'    => $type,
			'code'    => $code,
			'remark'  => maybe_serialize( $remark ),
			'date'    => current_time( 'mysql' ),
		);

		$result = $wpdb->insert(
			$wpdb->prefix . 'wp_weixin_broadcast_message_logs',
			$log
		);

		if ( false !== $result ) {
			$log['id'] = $wpdb->insert_id;
			$return    = $log;
		} else {
			WP_Weixin::log( 'Log add failed - database insertion error.' );
		}

		return $return;
	}

	protected function get_logs( $post_id ) {
		global $wpdb;

		$sql  = 'SELECT * FROM ' . $wpdb->prefix . 'wp_weixin_broadcast_message_logs WHERE post_id = %s ORDER BY date DESC;';
		$logs = $wpdb->get_results( $wpdb->prepare( $sql, $post_id ) ); // @codingStandardsIgnoreLine

		if ( 10 < count( $logs ) ) {
			$ids_to_keep = array();
			$counter     = 1;

			foreach ( $logs as $log ) {

				if ( 10 < $counter ) {

					break;
				}
				$ids_to_keep[] = $log->id;

				$counter++;
			}
			$ids_to_keep = array_map(
				'esc_sql',
				$ids_to_keep
			);
			$ids_to_keep = implode( ',', $ids_to_keep );

			$query = 'DELETE FROM ' . $wpdb->prefix . "wp_weixin_broadcast_message_logs WHERE id NOT IN ({$ids_to_keep})";

			$wpdb->query( $query ); // @codingStandardsIgnoreLine
		}

		if ( ! empty( $logs ) ) {

			foreach ( $logs as $index => $log ) {
				$logs[ $index ]->remark = maybe_unserialize( $log->remark );
			}
		}

		return $logs;
	}

	protected function delete_log( $field, $value, $format = '%d' ) {
		global $wpdb;

		return $wpdb->delete(
			$wpdb->prefix . 'wp_weixin_broadcast_message_logs',
			array( $field => $value ),
			array( $format )
		);
	}

	protected function log_content_print_r( $log_content ) {

		return print_r( $log_content, true ); // @codingStandardsIgnoreLine
	}

	protected function format_log_remark( $status, $type, $code, $remark_info ) {

		if ( ! is_array( $remark_info ) ) {

			return $remark_info;
		}

		$show_unknown_warning    = false;
		$err_message             = isset( $remark_info['err_message'] ) ? $remark_info['err_message'] : '';
		$show_job_result         = ( 'remote_job_result' === $type ) ? true : false;
		$filtered_ratio          = ( 'remote_job_result' === $type ) ? $remark_info['filtered_ratio'] : '';
		$success_ratio           = ( 'remote_job_result' === $type ) ? $remark_info['success_ratio'] : '';
		$error_ratio             = ( 'remote_job_result' === $type ) ? $remark_info['error_ratio'] : '';
		$copyright_issue_remarks = isset( $remark_info['copyright_issues'] ) ? $remark_info['copyright_issues'] : array();
		$details_sections        = isset( $remark_info['details_sections'] ) ? $remark_info['details_sections'] : array();
		$excerpt_message         = '';

		if ( 'preview' === $type ) {

			if ( 'failure' === $status ) {
				$excerpt_message = sprintf(
					// translators: %1$s is the WeChat ID, %2$s is the type of ID
					__(
						'Preview message to %1$s (%2$s) has failed.',
						'wp-weixin-broadcast'
					),
					$remark_info['excerpt_values']['id'],
					$remark_info['excerpt_values']['id_type']
				);
			} else {
				$excerpt_message = sprintf(
					// translators: %1$s is the WeChat ID, %2$s is the type of ID
					__(
						'Preview message to %1$s (%2$s) succeeded.',
						'wp-weixin-broadcast'
					),
					$remark_info['excerpt_values']['id'],
					$remark_info['excerpt_values']['id_type']
				);
			}
		} elseif ( 'scheduled_broadcast' === $type || 'direct_broadcast' === $type ) {

			if ( 'openids' === $remark_info['target'] ) {
				$remark_excerpt_target = sprintf(
					// translators: %s is the number of WordPress WeChat users
					__(
						'WordPress WeChat users (%s)',
						'wp-weixin-broadcast'
					),
					$remark_info['excerpt_values']['openids_count']
				);
			} elseif ( 'tag' === $remark_info['target'] ) {
				$remark_excerpt_target = sprintf(
					// translators: %1$s is the tag name, %2$s is the ID
					__(
						'users tagged with %1$s (remote ID %2$s)',
						'wp-weixin-broadcast'
					),
					$remark_info['excerpt_values']['tag_name'],
					$remark_info['excerpt_values']['tag_id']
				);
			} else {
				$remark_excerpt_target = __( 'all followers', 'wp-weixin-broadcast' );
			}

			if ( 'failure' === $status ) {
				$excerpt_message = sprintf(
					// translators: %s is the target of the broadcast
					__(
						'Broadcast message to %s failed.',
						'wp-weixin-broadcast'
					),
					$remark_excerpt_target
				);
			} else {
				$excerpt_message = sprintf(
					// translators: %s is the target of the broadcast
					__(
						'Broadcast message to %s succeeded.',
						'wp-weixin-broadcast'
					),
					$remark_excerpt_target
				);
			}
		} elseif ( 'delete_item' === $type ) {

			if ( 'failure' === $status ) {

				if ( $remark_info['deleted_index'] ) {
					$excerpt_message = sprintf(
						// translators: %s is the index of the item to be deleted
						__(
							'Deleting item at index %s failed.',
							'wp-weixin-broadcast'
						),
						$remark_info['deleted_index']
					);
				} else {
					$excerpt_message = __( 'Deleting all the items failed.', 'wp-weixin-broadcast' );
				}
			} else {

				if ( $remark_info['deleted_index'] ) {
					$excerpt_message = sprintf(
						// translators: %s is the index of the deleted item
						__(
							'Deleting item at index %s succeeded.',
							'wp-weixin-broadcast'
						),
						$remark_info['deleted_index']
					);
				} else {
					$excerpt_message = __( 'Deleting all the items succeeded.', 'wp-weixin-broadcast' );
				}
			}
		} elseif ( 'remote_job_result' === $type ) {

			switch ( $code ) {
				case 'success':
					$excerpt_message = __( 'The broadcast job was successful.', 'wp-weixin-broadcast' );
					break;
				case 'fail':
					$excerpt_message = __( 'The broadcast job failed.', 'wp-weixin-broadcast' );
					break;
				case '10001':
					$excerpt_message = __( 'Suspicious broadcast: suspected advertisement.', 'wp-weixin-broadcast' );
					break;
				case '20001':
					$excerpt_message = __( 'Suspicious broadcast: suspected political content.', 'wp-weixin-broadcast' );
					break;
				case '20004':
					$excerpt_message = __( 'Suspicious broadcast: suspected socially sensitive topic.', 'wp-weixin-broadcast' );
					break;
				case '20002':
					$excerpt_message = __( 'Suspicious broadcast: suspected erotic/pornographic content.', 'wp-weixin-broadcast' );
					break;
				case '20006':
					$excerpt_message = __( 'Suspicious broadcast: suspected illegal content.', 'wp-weixin-broadcast' );
					break;
				case '20008':
					$excerpt_message = __( 'Suspicious broadcast: suspected fraud.', 'wp-weixin-broadcast' );
					break;
				case '20013':
					$excerpt_message = __( 'Suspicious broadcast: suspected copyright infringement.', 'wp-weixin-broadcast' );
					break;
				case '22000':
					$excerpt_message = __( 'Suspicious broadcast: suspected promotion collusion.', 'wp-weixin-broadcast' );
					break;
				case '21000':
					$excerpt_message = __( 'Suspicious broadcast (no more information).', 'wp-weixin-broadcast' );
					break;
				case '30001':
					$excerpt_message = __( 'There was a system error during the originality check and the original author chose not allow the batch send of reprints.', 'wp-weixin-broadcast' );
					break;
				case '30002':
					$excerpt_message = __( 'The originality check determined the broadcast is a reprint and "Ignore Reprint Warning" was not checked.', 'wp-weixin-broadcast' );
					break;
				case '30003':
					$excerpt_message = __( 'The originality check determined the broadcast is a reprint and the original author chose not to allow the batch send of reprints.', 'wp-weixin-broadcast' );
					break;
				default:
					$show_unknown_warning = true;
					$excerpt_message      = __( 'Job completion status: ', 'wp-weixin-broadcast' ) . $code;
					break;
			}

			if ( ! empty( $copyright_issue_remarks ) ) {
				$formatted_copyright_remarks = array();

				foreach ( $copyright_issue_remarks as $copyright_issue_remark ) {
					$formatted_copyright_remarks[] = sprintf(
						// translators: %1$s is the article's index, %2$s is the link to the original
						__( 'Item in position %1$s (%2$s).', 'wp-weixin-broadcast' ),
						$copyright_issue_remark['index'],
						sprintf(
							'<a href="%s" target="_blank">%s</a>',
							$copyright_issue_remark['original_url'],
							__( 'View similar existing article', 'wp-weixin-broadcast' )
						)
					);
				}
				$copyright_issue_remarks = $formatted_copyright_remarks;
			}
		} elseif ( 'add_image' === $type ) {

			if ( 'failure' === $status ) {
				$excerpt_message = __( 'Failed to upload default cover image to WeChat.', 'wp-weixin-broadcast' );
			}
		} elseif ( 'add_rich_media' === $type ) {

			if ( 'failure' === $status ) {
				$excerpt_message = __( 'Failed to upload Rich Media material to WeChat.', 'wp-weixin-broadcast' );
			}
		} elseif ( 'add_temporary_media' === $type ) {

			if ( 'failure' === $status ) {
				$excerpt_message = __( 'Failed to replace image in content and upload to WeChat.', 'wp-weixin-broadcast' );
			}
		} else {
			$excerpt_message = ( is_string( $remark_info ) ) ? $remark_info : $this->log_content_print_r( $remark_info );
		}

		if ( ! empty( $details_sections ) ) {
			foreach ( $details_sections as $section ) {
				switch ( $section['title'] ) {
					case 'msg_to_api':
						$section['title'] = __( 'Message sent to the API:', 'wp-weixin-broadcast' );
						break;
					case 'params_to_api':
						$section['title'] = __( 'Parameters sent to the API:', 'wp-weixin-broadcast' );
						break;
					case 'msg_from_api':
						$section['title'] = __( 'Message received from WeChat API:', 'wp-weixin-broadcast' );
						break;
					case 'image_path':
						$section['title'] = __( 'Path of the image to send to the API:', 'wp-weixin-broadcast' );
						break;
					case 'push_event_from_api':
						$section['title'] = __( 'Push event data received from WeChat API:', 'wp-weixin-broadcast' );
						break;
				}
			}
		}

		ob_start();

		include WP_WEIXIN_BROADCAST_PLUGIN_PATH . 'inc/templates/admin/broadcast-message-log-remark.php';

		return ob_get_clean();
	}

}
