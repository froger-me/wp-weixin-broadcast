/* global WP_Weixin_Broadcast, console, postL10n, wp */
jQuery(function($) {

	// Replace UI labels
	var replaceLabels          = function() {
			window.postL10n          = (window.postL10n) ? postL10n : {};
			postL10n.publish         = WP_Weixin_Broadcast.postL10n.publish;
			postL10n.publishOn       = WP_Weixin_Broadcast.postL10n.publishOn;
			postL10n.publishOnFuture = WP_Weixin_Broadcast.postL10n.publishOnFuture;
			postL10n.publishOnPast   = WP_Weixin_Broadcast.postL10n.publishOnPast;
			postL10n.published       = WP_Weixin_Broadcast.postL10n.published;

			if ($('.post-type-wechat_broadcast_msg .subsubsub li.publish a').length) {
				var span = $($('.subsubsub li.publish a').html());

				$('.subsubsub li.publish a').html(window.postL10n.published + ' ');
				$('.subsubsub li.publish a').append(span);
			}
		},
		// Make cards sortable
		cardsSortableContainer = $('ol.serialization').sortable({
			cardsSortableContainer: 'serialization',
			delay: 0,
			placeholder: '<li class="placeholder"><div class="card">' + WP_Weixin_Broadcast.broadcastMessageItemPlaceholder + '</div></li>',
			onDrop: function  ($item, container, _super) {
				var data        = cardsSortableContainer.sortable('serialize').get().shift(),
					ids         = [],
					$clonedItem = $('<li/>').css({height: 0});

				$.each(data, function(idx, value) {
					ids.push(value.id);
				});

				$('#wechat_broadcast_item_ids').val(ids.join(','));

				_super($item, container);
				$item.before($clonedItem);
				$clonedItem.detach();
				_super($item, container);
			}
		}),
		// Build broadcast to user result
		buildTargetUserResult  = function(data) {
			var text = data.text;

			if (data.element) {
				var option = $(data.element);

				data.city    = option.data('city') || data.city;
				data.country = option.data('country') || data.country;
				data.thumb   = option.data('thumb') || data.thumb;
			}

			if (data.city || data.country) {
				if (data.city && data.country) {
					text += ' - ' + data.city + ', ' + data.country;
				} else if(data.city) {
					text += ' - ' + data.city + ', N/A';
				} else {
					text += ' - N/A, ' + data.country;
				}
			}

			var elem = $('<span class="wp-weixin-broadcast-select-wp-users-result">' + text + '</span>');

			elem.attr('data-id', data.id);
			elem.prepend('<img src="' + data.thumb + '" class="user-thumb" />');

			return elem;
		};

	// Limit media to 8
	WP_Weixin_Broadcast.limitCardItems = function() {

		if ($('.message-item-container > li').length >= 8) {
			$('.add-item-trigger').attr('disabled', 'disabled');
		} else if (WP_Weixin_Broadcast.canBroadcast || WP_Weixin_Broadcast.debug) {
			$('.add-item-trigger').removeAttr('disabled');
		}
	};
	
	// Wether the broadcast message has inconsistent data
	WP_Weixin_Broadcast.isBroadcastDirty = function() {

		return ($('.message-item-container .dirty').length !== 0);
	};
	
	// Enable save controls
	WP_Weixin_Broadcast.enableBroadcastSave = function() {

		if (!WP_Weixin_Broadcast.isBroadcastDirty()) {
			$('#publish').removeAttr('disabled');
			$('#save-post').removeAttr('disabled');
			$('#wechat_preview').removeAttr('disabled');
		}
	};
	
	// Disable save controls
	WP_Weixin_Broadcast.disableBroadcastSave = function() {
		$('#publish').attr('disabled', 'disabled');
		$('#save-post').attr('disabled', 'disabled');
		$('#wechat_preview').attr('disabled', 'disabled');
	};

	// Initialize card builder
	WP_Weixin_Broadcast.broadcastCardBuilder = {};

	// Build broadcast cards
	WP_Weixin_Broadcast.broadcastCardBuilder['card-article'] = function(cardData) {
		var card = $('#wp_weixin_broadcast_message_item_template_container .card').clone();

		card.find('.card-background-thumb').attr('style', 'background-image: url("' + cardData.thumbCover + '");');
		card.find('.card-background-full').attr('style', 'background-image: url("' + cardData.fullCover + '");');
		card.find('.card-title').html(cardData.text);
		card.addClass('card-article');
		card.attr({
			'data-id'  : cardData.id,
			'data-type': cardData.type,
			'data-name': cardData.text
		});

		return card;
	};

	// Broadacast message interface
	if ($('body').hasClass('post-type-wechat_broadcast_msg')) {
		// Override WP interface
		if (!WP_Weixin_Broadcast.canBroadcast) {
			if (!WP_Weixin_Broadcast.debug) {
				var removables  = [
						'#publishing-action',
						'#save-action',
						'.misc-pub-section.misc-pub-post-status',
						'.edit-timestamp',
						'.card-actions',
						'#howto-drag'
					].join(','),
					disableables = [
						'.wp-weixin-broadcast-items-selector-container input',
						'.wp-weixin-broadcast-send-ignore-reprint-container input',
						'.wp-weixin-broadcast-items-selector-container button:not(.delete-item-trigger)',
						'#titlewrap input'
					].join(',');

				$(removables).remove();
				$(disableables).attr('disabled', 'disabled');
			}

			$('.serialization').removeClass('serialization');
			$('body').addClass('broadcasted');
		} else {
			$('#publishing-action, .misc-pub-section.misc-pub-post-status, .edit-timestamp').css('visibility', 'visible');
		}

		if (WP_Weixin_Broadcast.screen === 'single' && WP_Weixin_Broadcast.isBroadcastMessage) {
			$('.misc-pub-section.curtime.misc-pub-curtime').css('visibility', 'visible');
			replaceLabels();
		}

		if (WP_Weixin_Broadcast.screen === 'list') {
			$('input[name="keep_private"]').parents('div.inline-edit-group:first').css('display', 'none');
			replaceLabels();
			$('.subsubsub').css('visibility', 'visible');
		}

		// Save Broadcast Message
		$('#save-post').on('click', function() {
			$('#preview-action .spinner, .misc-pub-curtime, #publish').css('visibility', 'hidden');
			$('.add-item-trigger').attr('disabled', 'disabled');
		});

		$('.save-timestamp').one('click', function() {

			setTimeout(function() {
				replaceLabels();
				$('#timestamp').css('visibility', 'visible');
			}, 1000);
			
		});

		// Broadcast the message
		if ('publish' !== $('#original_post_status').val()) {
			$('#publish').on('click', function(e) {

				var r = window.confirm(WP_Weixin_Broadcast.broadcastMessageSubmitConfirm);

				if (r) {
					if ($('#original_publish').val() === $('#publish').val()) {
						$('#post-body-content').append($('<input type="hidden" name="wechat_message_do_broadcast" value="1">'));
					}
				} else {
					e.preventDefault();
					e.stopPropagation();
				}
			});
		}

		// Admin notice
		if (WP_Weixin_Broadcast.isBroadcastDirty()) {
			$('.dirty-notice').show();
		}

		// Broadcast item list
		if ($('.message-item-container').length && !$('.message-item-container > li').length) {
			$('.message-item-placeholder-container').removeClass('hidden');
			WP_Weixin_Broadcast.disableBroadcastSave();
		} else {
			$('.message-item-container').removeClass('hidden');
		}

		WP_Weixin_Broadcast.limitCardItems();

		if (WP_Weixin_Broadcast.isBroadcastDirty()) {
			WP_Weixin_Broadcast.disableBroadcastSave();
		}

		$('.wp-weixin-broadcast-items-container').on('click', '.message-item-placeholder-container', function(e) {
			e.preventDefault();
			$('#wp_weixin_broadcast_select_item').select2('open');
		});

		

		$('.message-item-container').on('click', '.delete-card', function(e) {
			e.preventDefault();

			var r = window.confirm(WP_Weixin_Broadcast.broadcastMessageItemRemoveConfirm);

			if (r) {
				$(this).closest('li').remove();

				if (!$('.message-item-container li').length) {
					$('.message-item-container').addClass('hidden');
					$('#howto-drag').addClass('hidden');
					$('.message-item-placeholder-container').removeClass('hidden');
					WP_Weixin_Broadcast.disableBroadcastSave();
				}

				WP_Weixin_Broadcast.limitCardItems();
				WP_Weixin_Broadcast.enableBroadcastSave();

				var data = cardsSortableContainer.sortable('serialize').get().shift(),
					ids  = [];

				$.each(data, function(idx, value) {
					ids.push(value.id);
				});

				$('#wechat_broadcast_item_ids').val(ids.join(','));
			}
		});

		// Broadcast items selection
		$('#wp_weixin_broadcast_select_item').select2({
			placeholder: WP_Weixin_Broadcast.broadcastSelectArticlePlaceholder,
			theme: 'wp-weixin-broadcast-select',
			allowClear: true,
			disabled: !WP_Weixin_Broadcast.canBroadcast && !WP_Weixin_Broadcast.debug,
			templateSelection: function(data) {
				var output    = data.output ? data.output : 'card-article',
					builder   = WP_Weixin_Broadcast.broadcastCardBuilder[output],
					elem      = builder(data),
					selection = $('<div class="wp-weixin-broadcast-selected-item"></div>');

				elem.addClass('hidden');
				selection.append(elem);
				selection.append(data.text);

				return  selection;
			},
			templateResult: function(data) {

				if (!data.id) {

					return data.text;
				}

				var builder = WP_Weixin_Broadcast.broadcastCardBuilder[data.output];

				return builder(data);
			},
			ajax: {
				url: WP_Weixin_Broadcast.ajax_url,
				dataType: 'json',
				language: WP_Weixin_Broadcast.locale,
				cache: false,
				type: 'POST',
				data: function(params) {
					var data = {
						q: params.term,
						page: params.page || 1,
						action: 'wp_weixin_broadcast_get_items',
						nonce: $('#wechat_broadcast_message_content_nonce').val()
					};

					return data;
				},
				processResults: function(response) {
					var options = [];

					if (response && response.success && response.data) {
						$.each(response.data.items, function(index, item) {
							options.push(
								{
									id: item.id,
									text: item.title,
									output: item.output,
									type: item.type,
									thumbCover: item.thumbCover,
									fullCover: item.fullCover
								}
							);
						});
					}

					return {
						'results': options,
						'pagination': {
							'more': response.data.more
						}
					};
				}
			}
		});

		$('.add-item-trigger').removeClass('select2-ui-wait');

		$('.add-item-trigger').on('click', function(e) {
			e.preventDefault();

			if ($(this).attr('disabled')) {

				return;
			}
			
			var value = $('#wp_weixin_broadcast_select_item').select2('data').shift();

			if (value && 'undefined' !== typeof value) {
				var card = $('.wp-weixin-broadcast-selected-item .card').clone(),
					elem = $('#wp_weixin_broadcast_message_item_template_container .message-item').clone();

				elem.html('');
				card.removeClass('hidden');
				card.find('.card-actions').removeClass('hidden');
				elem.attr('data-id', card.attr('data-id'));
				card.removeAttr('data-id');
				elem.attr('data-name', card.attr('data-name'));
				card.removeAttr('data-name');
				elem.append(card);
				$('.message-item-container').append(elem);


				if (!$('.message-item-placeholder-container').hasClass('hidden')) {
					$('.message-item-placeholder-container').addClass('hidden');
				}

				if ($('.message-item-container').hasClass('hidden')) {
					$('.message-item-container').removeClass('hidden');
					$('#howto-drag').removeClass('hidden');
				}

				WP_Weixin_Broadcast.enableBroadcastSave();
				WP_Weixin_Broadcast.limitCardItems();

				var data = cardsSortableContainer.sortable('serialize').get().shift(),
					ids  = [];

				$.each(data, function(idx, value) {
					ids.push(value.id);
				});

				$('#wechat_broadcast_item_ids').val(ids.join(','));
			}
		});

		// Broadcast preview
		$('#wechat_preview').on('click', function(e) {
			e.preventDefault();

			var button = $(this),
				form = $('#post'),
				data   = {
					ids: $('#wechat_broadcast_item_ids').val(),
					postId: button.data('id'),
					previewFollowerIds: $('#wechat_broadcast_preview_followers_ids').val().split(',').map(function(item) {

						return item.trim();
					}),
					previewFollowerIdType: $('input[name="wechat_broadcast_preview_followers_id_type"]:checked').val(),
					nonce: $('#wechat_broadcast_message_content_nonce').val(),
					action: 'wp_weixin_broadcast_preview'
				};

			if (button.attr('disabled')) {

				return;
			}

			button.attr('disabled', 'disabled');
			$('#preview-action .spinner').css('visibility', 'visible');

			$.ajax({
				url: WP_Weixin_Broadcast.ajax_url,
				type: 'POST',
				data: data
			}).done(function(response) {
				
				if (WP_Weixin_Broadcast.debug) {
					button.removeAttr('disabled');
					$('#preview-action .spinner').css('visibility', 'hidden');
					console.log(response);
				} else {
					form.prepend('<input type="hidden" name="wechat_broadcast_doing_preview" value="1">');
					form.submit();
				}
			}).fail(function(qXHR, textStatus) {

				if (WP_Weixin_Broadcast.debug) {
					button.removeAttr('disabled');
					$('#preview-action .spinner').css('visibility', 'hidden');
					console.log(textStatus);
				} else {
					form.prepend('<input type="hidden" name="wechat_broadcast_doing_preview" value="1">');
					form.submit();
				}
			});
		});

		$('#wechat_broadcast_preview_followers_ids').on('input', function() {
			var val    = $(this).val(),
				button = $('#wechat_preview');

			if (!val || '' === val.replace(',', '').trim()) {
				button.attr('disabled', 'disabled');
			} else {

				if ($('.message-item-container > li').length) {
					button.removeAttr('disabled', 'disabled');
				}
			}
		});

		$('#wechat_broadcast_preview_followers_ids').trigger('input');

		// Broadcast status
		$('#wechat_broadcast_status').on('click', function(e) {
			e.preventDefault();

			var button = $(this),
				data   = {
					postId: button.data('id'),
					nonce: $('#wechat_broadcast_message_content_nonce').val(),
					action: 'wp_weixin_broadcast_status'
				};

			if (button.attr('disabled')) {

				return;
			}

			button.attr('disabled', 'disabled');
			$('#broadcast-status-action .spinner').css('visibility', 'visible');

			$.ajax({
				url: WP_Weixin_Broadcast.ajax_url,
				type: 'POST',
				data: data
			}).done(function(response) {
				button.removeAttr('disabled');
				$('#broadcast-status-action .spinner').css('visibility', 'hidden');

				if (response.success) {
					var statusContainer = $('#latest-broadcast-status span');

					statusContainer.removeClass();
					statusContainer.addClass(response.data.class);
					statusContainer.html(response.data.text);
					$('#latest-broadcast-status').removeClass('hidden');
				} else {
					console.log(response);
				}
				
				if (WP_Weixin_Broadcast.debug) {
					console.log(response);
				}
			}).fail(function(qXHR, textStatus) {
				button.removeAttr('disabled');
				$('#broadcast-status-action .spinner').css('visibility', 'hidden');

				if (WP_Weixin_Broadcast.debug) {
					console.log(textStatus);
				}
			});
		});

		

		// Broadcast options
		$('select[name="wechat_broadcast_target_type"').on('change', function() {
			var elem    = $(this),
				selected = elem.val();

			if ('undefined' === typeof selected) {

				return;
			}

			$('.wp-weixin-broadcast-to-target-settings').hide();
			$('.wp-weixin-broadcast-to-' + selected).show();

			if ('tag' === selected) {

				if (!$('#wp_weixin_broadcast_select_broadcast_to_tag_id').val()) {
					$('#publish').attr('disabled', 'disabled');
				}
			} else {

				if ($('.message-item-container > li').length) {
					$('#publish').removeAttr('disabled', 'disabled');
				}
			}

			if ('users' === selected) {

				if (1 === $('#wp_weixin_broadcast_select_broadcast_to_wp_users').select2('data').length) {
					$('#publish').attr('disabled', 'disabled');
				} else {
					$('#publish').removeAttr('disabled', 'disabled');
				}
			}
		});

		$('#wp_weixin_broadcast_select_broadcast_to_tag').select2({
			placeholder: WP_Weixin_Broadcast.broadcastSelectTagPlaceholder,
			theme: 'wp-weixin-broadcast-select',
			allowClear: true,
			disabled: !WP_Weixin_Broadcast.canBroadcast && !WP_Weixin_Broadcast.debug,
			ajax: {
				url: WP_Weixin_Broadcast.ajax_url,
				dataType: 'json',
				language: WP_Weixin_Broadcast.locale,
				cache: false,
				type: 'POST',
				data: function(params) {
					var data = {
						q: params.term,
						page: params.page || 1,
						action: 'wp_weixin_broadcast_get_tags',
						nonce: $('#wechat_broadcast_message_content_nonce').val()
					};

					return data;
				},
				processResults: function(response) {
					var options = [];

					if (response && response.success && response.data) {
						$.each(response.data, function(index, tag) {
							options.push(
								{
									id: tag.id,
									text: tag.name + ' (' + tag.count + ')'
								}
							);
						});
					}

					return {
						'results': options
					};
				}
			}
		});

		var tagValueHolder = $('.wechat-broadcast-target-tag');

		if (tagValueHolder.length) {
			(function(){
				var elem   = $('#wp_weixin_broadcast_select_broadcast_to_tag'),
					data   = {
						id: tagValueHolder.data('id'),
						text: tagValueHolder.data('text') + ' (' + tagValueHolder.data('number') + ')'
					},
					option = new Option(data.text, data.id, true, true);

					elem.append(option).trigger('change');
					elem.trigger({
						type: 'select2:select',
						params: {
							data: data
						}
					});
			})();
		}

		$('#wp_weixin_broadcast_select_broadcast_to_tag').on('change', function() {
			var value = $('#wp_weixin_broadcast_select_broadcast_to_tag').select2('data').shift();

			if (value && 'undefined' !== typeof value) {
				$('#wp_weixin_broadcast_select_broadcast_to_tag_id').val(value.id);

				if ($('.message-item-container > li').length) {
					$('#publish').removeAttr('disabled', 'disabled');
				}
			} else {
				$('#wp_weixin_broadcast_select_broadcast_to_tag_id').val('');
				$('#publish').attr('disabled', 'disabled');
			}	
		});

		$('#wp_weixin_broadcast_select_broadcast_to_wp_users').select2({
			placeholder: WP_Weixin_Broadcast.broadcastSelectWPUsersPlaceholder,
			theme: 'wp-weixin-broadcast-select',
			dropdownParent: $('.wp-weixin-broadcast-to-users'),
			cache: true,
			closeOnSelect: false,
			disabled: !WP_Weixin_Broadcast.canBroadcast && !WP_Weixin_Broadcast.debug,
			templateSelection: function(data) {

				if (!data.id) {

					return data.text;
				}

				return buildTargetUserResult(data);
			},
			templateResult: function(data) {

				if (!data.id) {

					return data.text;
				}

				return buildTargetUserResult(data);
			},
			ajax: {
				url: WP_Weixin_Broadcast.ajax_url,
				dataType: 'json',
				language: WP_Weixin_Broadcast.locale,
				cache: false,
				type: 'POST',
				data: function(params) {
					var data = {
						q: params.term,
						page: params.page || 1,
						action: 'wp_weixin_broadcast_get_wp_wechat_users',
						nonce: $('#wechat_broadcast_message_content_nonce').val()
					};

					return data;
				},
				processResults: function(response) {
					var options = [];

					if (response && response.success && response.data) {
						$.each(response.data.users, function(index, user) {
							options.push(
								{
									id: user.id,
									text: user.text,
									gender: user.gender,
									city: user.city,
									country: user.country,
									thumb: user.thumb
								}
							);
						});
					}

					return {
						'results': options,
						'pagination': {
							'more': response.data.more
						}
					};
				}
			}
		});

		$('#wp_weixin_broadcast_select_broadcast_to_wp_users').on('change', function() {
			var value = $('#wp_weixin_broadcast_select_broadcast_to_wp_users').select2('data');

			if (value && 'undefined' !== typeof value) {

				if (1 !== value.length) {
					$('#publish').removeAttr('disabled', 'disabled');
				} else {
					$('#publish').attr('disabled', 'disabled');
				}

				value = $.map(value, function(val) {

					return val.id;
				}).join(',');

				$('#wp_weixin_broadcast_select_broadcast_to_wp_users_openids').val(value);
			} else {
				$('#wp_weixin_broadcast_select_broadcast_to_wp_users_openids').val('');
				$('#publish').attr('disabled', 'disabled');
			}
		});

		if ($('.wechat-broadcast-target-user').length) {
			$('.wechat-broadcast-target-user').each(function(idx, value) {
				value = $(value);

				var elem   = $('#wp_weixin_broadcast_select_broadcast_to_wp_users'),
					option = new Option(value.data('text'), value.data('id'), true, true);

				option.title = value.data('text');

				option.setAttribute('data-thumb', value.data('thumb'));
				option.setAttribute('data-gender', value.data('gender'));
				option.setAttribute('data-city', value.data('city'));
				option.setAttribute('data-country', value.data('country'));
				elem.append(option).trigger('change');
				elem.trigger({
					type: 'select2:select',
					params: {
						data: {
							id: value.data('id'),
							text: value.data('text'),
							gender: value.data('gender'),
							city: value.data('city'),
							country: value.data('country'),
							thumb: value.data('thumb')
						}
					}
				});
			});
		}

		$('.select2-ui-wait').removeClass('select2-ui-wait');
		$('select[name="wechat_broadcast_target_type"').trigger('change');

		// Log
		$('#wechat_broadcast_log_container').on('click', '.card', function(e) {
			e.preventDefault();

			var card = $(this);

			$('.log-modal-backdrop').removeClass('hidden');
			card.find('.log-full, .log-full .wechat-broadcast-message').removeClass('hidden');
		});

		$('.log-full').on('click', '.log-full-close', function(e) {
			e.preventDefault();
			e.stopPropagation();
			$('.log-modal-backdrop, .log-full, .log-full .wechat-broadcast-message').addClass('hidden');
		});

		$('.log-full').on('click', '.log-full-details-container', function(e) {
			e.stopPropagation();
		});

		// Delete items
		if ($('.message-item-container li.deleted').length === $('.message-item-container li').length) {
			$('.delete-item-trigger').attr('disabled', 'disabled');
			$('#wp_weixin_broadcast_select_delete_item').attr('disabled', 'disabled');
		} else {
			$('.wp-weixin-broadcast-delete-article-trigger').on('click', function(e) {
				e.preventDefault();

				if ($(this).attr('disabled')) {

					return;
				}
				
				var index  = $('#wp_weixin_broadcast_select_delete_item').prop('selectedIndex'),
					button = $(this),
					data   = {
						action: 'wp_weixin_broadcast_delete_remote_material',
						index: index,
						postId: button.data('id'),
						itemId: $('#wp_weixin_broadcast_select_delete_item').val(),
						nonce: $('#wechat_broadcast_message_content_nonce').val()
					};

				button.attr('disabled', 'disabled');
				button.parent().find('.spinner').css('visibility', 'visible');

				$.ajax({
					url: WP_Weixin_Broadcast.ajax_url,
					type: 'POST',
					data: data
				}).done(function(response) {
					
					if (WP_Weixin_Broadcast.debug) {
						button.removeAttr('disabled');
						button.parent().find('.spinner').css('visibility', 'hidden');
						console.log(response);
					} else {
						location.reload();
					}
				}).fail(function(qXHR, textStatus) {

					if (WP_Weixin_Broadcast.debug) {
						button.parent().find('.spinner').css('visibility', 'hidden');
						button.removeAttr('disabled');
						console.log(textStatus);
					} else {
						location.reload();
					}
				});
			});
		}
	}

	// H5 editor
	if ($('#wp_weixin_broadcast_h5_editor_wrap').length) {

		WP_Weixin_Broadcast.updateH5EditorPreview = function () {
			var frame           = $('#wp_weixin_broadcast_h5_preview_frame'),
				codeContainer   = frame.contents().find('.rich_media_content'),
				titleContainer  = frame.contents().find('.rich_media_title'),
				authorContainer = frame.contents().find('.rich_media_author'),
				authorOverride  = $('#wechat_broadcast_article_author').val(),
				author          = (authorOverride.length) ? authorOverride : WP_Weixin_Broadcast.h5_preview_default_author;

			if (codeContainer) {
				codeContainer.html(getCoverPreview() + sanitize(codeEditor.codemirror.getValue()));
				titleContainer.html($('#title').val());
				authorContainer.html(author);
			}

			frame.removeClass('busy');
		};

		var codeEditor         = wp.codeEditor.initialize($('#wp_weixin_broadcast_h5_editor_content'), WP_Weixin_Broadcast.editor_args),
			updateFrameTimeout = setTimeout(WP_Weixin_Broadcast.updateH5EditorPreview, 300),
			h5Editor           = $('#wp_weixin_broadcast_h5_editor_wrap'),
			getCoverPreview    = function() {
				var featuredImage = $('#set-post-thumbnail img'),
				    coverPreview  = '';

				if ($('#wechat_broadcast_article_show_cover_image').prop('checked')) {
					if (featuredImage.length) {
						coverPreview  = $('<div>' + WP_Weixin_Broadcast.h5_preview_cover_image + '<div>');
						coverPreview.find('img').attr('src', featuredImage.attr('src'));

						coverPreview = coverPreview.html();
					} else {
						coverPreview = WP_Weixin_Broadcast.h5_preview_cover_image;
					}
				}

				return coverPreview;
			}, 
			sanitize           = function( string ) {
				var div = document.createElement('div');

				div.innerHTML = string;

				var elements = [
						div.getElementsByTagName('script'),
						div.getElementsByTagName('style'),
						div.getElementsByTagName('input'),
						div.getElementsByTagName('canvas'),
						div.getElementsByTagName('iframe'),
						div.getElementsByTagName('select')
					];

				for (var i = elements.length - 1; i >= 0; i--) {

					while (elements[i][0]) {
				   		elements[i][0].parentNode.removeChild(elements[i][0]);
					}
				}

				return div.innerHTML;
			},
			resizeThrottled    = false,
			resizeDebounce     = false,
			h5EditorWrap       = $('#wp_weixin_broadcast_h5_editor_wrap'),
			resizeDelay        = 200,
			previewResizer     = $('#wp_weixin_broadcast_h5_preview_resizer'),
			codeEditorWrap     = $('#wp_weixin_broadcast_h5_editor_content_wrap'),
			resize             = function () {

				if (window.matchMedia('(min-width: 680px)').matches) {
					var previewWidth         = previewResizer.width(),
					wrapperWidth         = h5EditorWrap.width();

					codeEditorWrap.width(wrapperWidth - previewWidth - 25);
				} else {
					codeEditorWrap.removeAttr('style');
				}
			};

		// window.resize event listener - throttled and debounced
		window.addEventListener('resize', function() {
			clearTimeout(resizeDebounce);

			if (!resizeThrottled || resizeDebounce) {
				resizeThrottled = true;
				resizeDebounce  = setTimeout(resize, resizeDelay + 1);

				setTimeout(function() {
					resizeThrottled = false;
				}, resizeDelay);
			}  
		});

		// Switch editors
		$('.wp-weixin-broadcast-editor-switch').on('click', function(e) {
			e.preventDefault();

			var htmlEditorHandle = $('#content-html'),
				frameContent     = $('#wp_weixin_broadcast_h5_preview_frame').contents(),
				frameBody        = frameContent.find('body'),
				wpEditor         = $('#postdivrich');

			htmlEditorHandle.trigger('click');

			if (!$('#wp_weixin_broadcast_h5_editor_wrap').hasClass('active')) {
				frameContent.find('head').html(WP_Weixin_Broadcast.h5_preview_head);
				frameBody.addClass('mm_appmsg discuss_tab appmsg_skin_default appmsg_style_default not_in_mm');
				wpEditor.hide();
				h5Editor.addClass('active');
				resize();
				codeEditor.codemirror.setValue($('#content').val());
				codeEditor.codemirror.refresh();
				frameBody.html(WP_Weixin_Broadcast.h5_preview_default_content);
				frameBody.find('.rich_media_content').html(getCoverPreview() + sanitize(codeEditor.codemirror.getValue()));				
			} else {
				htmlEditorHandle.trigger('click');
				$('#content').val(sanitize(codeEditor.codemirror.getValue()));
				wpEditor.show();
				h5Editor.removeClass('active');
				$(window).scrollTop($(window).scrollTop()+1);
				$(window).scrollTop($(window).scrollTop()-1);
			}
		});

		// Code live preview
	    codeEditor.codemirror.on('change', function() {
			var frame = $('#wp_weixin_broadcast_h5_preview_frame');

			if (!frame.hasClass('busy')) {
				frame.addClass('busy');
				clearTimeout(updateFrameTimeout);
				setTimeout(WP_Weixin_Broadcast.updateH5EditorPreview, 300);
			}
	    });

	    // Title and Author live preview
		$('#title, #wechat_broadcast_article_author').on('keyup', function(){
			var frame = $('#wp_weixin_broadcast_h5_preview_frame');

			if (!frame.hasClass('busy')) {
				frame.addClass('busy');
				clearTimeout(updateFrameTimeout);
				setTimeout(WP_Weixin_Broadcast.updateH5EditorPreview, 300);
			}
		});

		// Cover image live preview
		$('#postimagediv').on('change', '#wechat_broadcast_article_show_cover_image', function() {
			var frame = $('#wp_weixin_broadcast_h5_preview_frame');

			frame.addClass('busy');
			clearTimeout(updateFrameTimeout);
			setTimeout(WP_Weixin_Broadcast.updateH5EditorPreview, 300);
		});

		// For the sake of it, because WordPress is annoying when changing featured images, update live preview on every ajax call
		$(document).ajaxComplete(function (event, xhr, settings) {

			if ( 'string' === typeof settings.data && settings.data.includes('heartbeat') ) {

				return;
			}

			var frame = $('#wp_weixin_broadcast_h5_preview_frame');

			if (!frame.hasClass('busy')) {
				frame.addClass('busy');
				clearTimeout(updateFrameTimeout);
				setTimeout(WP_Weixin_Broadcast.updateH5EditorPreview, 300);
			}
		});

		// Make sure the H5 editor changes were taken into accoount when saving
		$('#publish').on('click', function() {

			if ( h5Editor.hasClass('active') ) {
				$('.h5-active-button').trigger('click');
				$('.h5-inactive-button').trigger('click');	
			}
		});

		// Add confirmation when activating the visual editor
		$('#content-tmce').on('click', function(e) {

			if (!window.confirm(WP_Weixin_Broadcast.h5_visual_alert)) {
				e.preventDefault();
				e.stopPropagation();
			}
		});
	}

});