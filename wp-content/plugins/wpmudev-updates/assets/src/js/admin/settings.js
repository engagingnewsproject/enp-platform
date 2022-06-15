// the semi-colon before function invocation is a safety net against concatenated
// scripts and/or other plugins which may not be closed properly.
;// noinspection JSUnusedLocalSymbols
(function ($, window, document, undefined) {

	"use strict";

	// undefined is used here as the undefined global variable in ECMAScript 3 is
	// mutable (ie. it can be changed by someone else). undefined isn't really being
	// passed in so we can ensure the value of it is truly undefined. In ES5, undefined
	// can no longer be modified.

	// window and document are passed through as local variables rather than global
	// as this (slightly) quickens the resolution process and can be more efficiently
	// minified (especially when both are regularly referenced in your plugin).

	// Create the defaults once
	var pluginName = "wpmudevDashboardAdminSettingsPage";

	// The actual plugin constructor
	function wpmudevDashboardAdminSettingsPage(element, options) {
		this.element = element;
		this.$el = $(this.element);
		this.adminAddDialog = null;
		this.translationDialog = null;
		this.bulkAjaxQueueName = 'bulk-ajax-queue-translation';
		this.init();
	}

	// Avoid Plugin.prototype conflicts
	$.extend(wpmudevDashboardAdminSettingsPage.prototype, {
		init: function () {
			var self = this;
			this.prepareTranslationUpdateDialog();
			this.attachEvents();
			this.showAlertNotice();
			this.initUsersSelect();

			$(window).trigger('hashchange');
		},
		attachEvents: function () {
			var self = this;

			$(window).on('hashchange', function () {
				self.processHash();
			});

			this.$el.on('change', 'input.js-plugin-check', function () {
				var project_slug = $(this).val();
				if (!self.bulkPluginsList) {
					self.bulkPluginsList = [];
				}
				if ($(this).is(':checked')) {
					self.bulkPluginsList.push(project_slug);
				} else {
					var bulkPluginsList = self.bulkPluginsList;
					bulkPluginsList = bulkPluginsList.filter(function (item) {
						return item !== project_slug
					});
					self.bulkPluginsList = bulkPluginsList;
				}
				self.$el.trigger('wpmu:translation-projects');
			});

			this.$el.on('wpmu:translation-projects', function () {
				var slugs = self.bulkPluginsList;

				if (!slugs.length || self.bulkPluginAction === '') {
					self.$el
					.find('#update-selected-translations')
					.attr('disabled', 'disabled');
				} else {
					self.$el
					.find('#update-selected-translations')
					.removeAttr('disabled');
				}
			});

			this.$el.on('submit', 'form#form-admin-add', function (e) {
				$(this).find('button[type="submit"]').addClass('sui-button-onload');
				return true;
			});

			this.$el.on('submit', 'form', function (e) {
				if ($(this).attr('id') !== 'form-admin-add') {
					$(this).find('button[type="submit"]').addClass('sui-button-onload');
				}

				return true;
			});

			this.$el.on('click', '.modal-open', function (e) {
				e.preventDefault();
				self.showModals(this);
			});

			this.$el.on('click', '.js-remove-user-permisssions', function () {
				$(this).addClass('sui-button-onload');
			});

			if (this.isUpdateNotificationHash()) {
				// var message = window.wdp_locale.translation_installed;
				var message = window.wdp_locale.translation_updated;
				message = message.replace('%s', this.getTranslationUpdateCount());
				this.showNotification('js-translation-updated', message);

				//remove all the hash
				var url = window.location.href.split("#")[0] + '#translation';
				//add translation tab hash again
				history.replaceState(null, null, url);
			}
			$(window).on('wpmudev:startTranslation', function () {
				self.applyBulkAction();
			});

			this.$el.on('change', '.sui-mobile-nav', function (e) {
				self.mobileNav($(this).val());
			});
		},
		processHash: function () {
			var hash = location.hash;
			hash = hash.replace(/^#/, '');

			this.$el.find('.sui-vertical-tabs li.sui-vertical-tab').removeClass('current');
			this.$el.find('.js-sidenav-content').hide();

			//for update translation
			if (hash.includes('translation#')) {
				hash = 'translation';
			}

			switch (hash) {
				case 'permissions':
					this.$el.find('.sui-vertical-tabs li.sui-vertical-tab a[href$="#permissions"]').closest('li.sui-vertical-tab').addClass('current');
					this.$el.find('.js-sidenav-content#permissions').show();
					this.$el.find('.sui-sidenav select.sui-mobile-nav').val('#permissions');
					this.$el.find('.sui-sidenav select.sui-mobile-nav').trigger('change');
					break;
				case 'translation':
					this.$el.find('.sui-vertical-tabs li.sui-vertical-tab a[href$="#translation"]').closest('li.sui-vertical-tab').addClass('current');
					this.$el.find('.js-sidenav-content#translation').show();
					this.$el.find('.sui-sidenav select.sui-mobile-nav').val('#translation');
					this.$el.find('.sui-sidenav select.sui-mobile-nav').trigger('change');
					break;
				case 'membership':
					this.$el.find('.sui-vertical-tabs li.sui-vertical-tab a[href$="#membership"]').closest('li.sui-vertical-tab').addClass('current');
					this.$el.find('.js-sidenav-content#membership').show();
					this.$el.find('.sui-sidenav select.sui-mobile-nav').val('#membership');
					this.$el.find('.sui-sidenav select.sui-mobile-nav').trigger('change');
					break;
				case 'apikey':
					this.$el.find('.sui-vertical-tabs li.sui-vertical-tab a[href$="#apikey"]').closest('li.sui-vertical-tab').addClass('current');
					this.$el.find('.js-sidenav-content#apikey').show();
					this.$el.find('.sui-sidenav select.sui-mobile-nav').val('#apikey');
					this.$el.find('.sui-sidenav select.sui-mobile-nav').trigger('change');
					break;
				case 'data':
					this.$el.find('.sui-vertical-tabs li.sui-vertical-tab a[href$="#data"]').closest('li.sui-vertical-tab').addClass('current');
					this.$el.find('.js-sidenav-content#data').show();
					this.$el.find('.sui-sidenav select.sui-mobile-nav').val('#data');
					this.$el.find('.sui-sidenav select.sui-mobile-nav').trigger('change');
					break;
				default:
					this.$el.find('.sui-vertical-tabs li.sui-vertical-tab a[href$="#general"]').closest('li.sui-vertical-tab').addClass('current');
					this.$el.find('.js-sidenav-content#general').show();
					this.$el.find('.sui-sidenav select.sui-mobile-nav').val('#general');
					this.$el.find('.sui-sidenav select.sui-mobile-nav').trigger('change');
					break;
			}
		},
		mobileNav: function (tab) {
			var hash = location.hash;
			if (hash !== tab) {
				location.hash = tab;
			}
		},

		/**
		 * Initialize dash icons selector.
		 *
		 * @since 4.11.1
		 */
		initUsersSelect: function () {
			var self = this,
				changed = false,
				// Main wrappers.
				$form = this.$el.find('#form-admin-add'),
				$added = $('#wpmudev-permissions-users-added'),
				$available = $('#wpmudev-permissions-users-all'),
				$addedLabel = $('#wpmudev-permissions-users-added-label'),
				$availableLabel = $('#wpmudev-permissions-users-all-label'),
				$noResultsNotice = $('#permissions-users-empty-results-notice'),
				$allAddedNotice = $('#permissions-users-all-added-notice'),
				$submit = $('#permissions-users-save');

			// Make sure it's disabled.
			$submit.prop('disabled', true);

			// On search input change.
			$('#search-user').on('input', function () {
				var keyword = $(this).val().toLowerCase(),
					// Searchable by name, email, first name and last name.
					$searchable = '[data-name*="' + keyword + '"],' +
						'[data-email*="' + keyword + '"],' +
						'[data-username*="' + keyword + '"]',
					$matchesAll = $($searchable, $available),
					$matchesAdded = $($searchable, $added),
					$elements = $('.permissions-user-available, .permissions-user-added', $form)

				// Hide notice.
				$noResultsNotice.addClass('sui-hidden')
				$allAddedNotice.addClass('sui-hidden');

				// If no search input, show all.
				if ('' === keyword) {
					$elements.show()
					$addedLabel.show()
					$availableLabel.show()
					toggleNotice()
					return
				}

				// Hide all.
				$elements.hide()
				$addedLabel.hide()
				$availableLabel.hide()
				// Go through each items.
				if ($matchesAdded.length > 0) {
					$matchesAdded.each(function () {
						// Show icon.
						$(this).show()
					})
					$addedLabel.show()
				}
				if ($matchesAll.length > 0) {
					$matchesAll.each(function () {
						// Show icon.
						$(this).show()
					})
					$availableLabel.show()
				}
				if (!$matchesAll.length && !$matchesAdded.length) {
					$noResultsNotice.removeClass('sui-hidden')
					$allAddedNotice.addClass('sui-hidden');
				}
			})

			// On user add.
			$('.permissions-user-add').on('click', function () {
				// User list changed.
				changed = true;
				var $user = $(this).data('user'),
					$el = $(this).closest('.permissions-user-item');
				// Hide add button.
				$(this).addClass('sui-hidden');
				// Show remove button.
				$el.find('button.permissions-user-remove').removeClass('sui-hidden');
				// Add user id to input.
				$(this).closest('.dashui-item').append('<input type="hidden" name="users[]" class="user-id-hidden" value="' + $user + '"/>');
				// Move to added list.
				$added.append($el);

				// Show/hide notice.
				toggleNotice();

				// Enable save button.
				$submit.prop('disabled', false);
			})

			// On user remove.
			$('.permissions-user-remove').on('click', function () {
				// User list changed.
				changed = true;
				var $user = $(this).data('user'),
					$el = $(this).closest('.permissions-user-item');
				// Hide remove button.
				$(this).addClass('sui-hidden');
				// Show add button.
				$el.find('button.permissions-user-add').removeClass('sui-hidden');
				// Remove input.
				$(this).closest('.dashui-item').find('.user-id-hidden').remove();
				// Move to added list.
				$available.append($el);

				// Show/hide notice.
				toggleNotice();

				// Enable save button.
				$submit.prop('disabled', false);
			})

			/**
			 * Show or hide empty notice.
			 *
			 * @since 4.11.2
			 */
			function toggleNotice() {
				if ( $('#wpmudev-permissions-users-all').find('.permissions-user-item').length > 0 ) {
					$('#permissions-users-all-added-notice').addClass('sui-hidden');
				} else {
					$('#permissions-users-all-added-notice').removeClass('sui-hidden');
				}
			}
		},

		prepareTranslationUpdateDialog: function () {
			var self = this;
			var dialog = document.getElementById('bulk-action-translation-modal');

			if (dialog && dialog.length > 0) {
				dialog.on('open', function () {
					self.onTranslationUpdateShow();
				});
				dialog.on('close', function () {
					self.onTranslationUpdateHide();
				});

				return true;
			}

			return false;
		},
		onTranslationUpdateHide: function () {
			if ($.ajaxq.isRunning(this.bulkAjaxQueueName)) {
				$.ajaxq.abort(this.bulkAjaxQueueName);
			}
		},
		onTranslationUpdateShow: function () {
			this.applyBulkAction();
		},
		applyBulkAction: function () {
			var dialog = this.$el.find('#bulk-action-translation-modal');

			this.bulkActionProgress = 0;
			dialog.find('.sui-progress-text>span').text('0%');
			dialog.find('.js-bulk-actions-progress').css('width', '0%');
			dialog.find('.js-bulk-actions-loader-icon').show();

			this.bulkActionErrors = [];
			var i = 1;
			var isLast = false;
			var bulkPluginsCount = this.bulkPluginsList.length;
			if (bulkPluginsCount > 0) {
				for (var index in this.bulkPluginsList) {

					if (i === bulkPluginsCount) {
						isLast = true;
					}
					if (this.bulkPluginsList) {
						this.addBulkQueue(this.bulkPluginsList[index], isLast);
					}
					i++;
				}
			}
		},
		addBulkQueue: function (project_slug, isLast) {
			var self = this;
			var dialog = this.$el.find('#bulk-action-translation-modal');
			var hashes = dialog.find('.js-bulk-hash').data();
			var ajax_action = 'wdp-translation-update';
			var hash = hashes['translationUpdate'];
			var name = self.$el.find('#translation-bulk-action-' + project_slug).data('plugin-name');
			var bulkPluginsCount = this.bulkPluginsList.length;
			var progressIncreaser = 100 / bulkPluginsCount;
			progressIncreaser = +progressIncreaser;
			progressIncreaser = Math.floor(progressIncreaser);

			var stateText = dialog.find('.js-bulk-actions-state');
			var stateBaseText = window.wdp_locale.installing_translation;


			$.ajaxq(this.bulkAjaxQueueName, {
				type: 'POST',
				url: window.ajaxurl,
				data: {
					action: ajax_action,
					hash: hash,
					slug: project_slug,
					is_network: +$('body').hasClass('network-admin'),
				},
				beforeSend: function () {
					stateBaseText = stateBaseText.replace('%s', name);
					stateText.text(stateBaseText);

				},
				success: function (response) {
					if (!response.success) {
						if (response.data && response.data.message) {
							dialog.find('.js-bulk-errors').append('<p>' + name + ' : ' + response.data.message + '</p>');
							dialog.find('.js-bulk-errors').show();
						}

						self.bulkActionErrors.push(response.data.message);
					}
				},
				error: function (error) {
					dialog.find('.js-bulk-errors').show();
					dialog.find('.js-bulk-errors').append('<p>' + name + ' : HTTP Request Error</p>');
					self.bulkActionErrors.push('HTTP Request Error');
				},
				complete: function () {
					self.updateBulkProgressBar(dialog, progressIncreaser, true);
				},
			});
		},
		updateBulkProgressBar: function (dialog, progressIncreaser, checkAjax) {
			var currentProgress = this.bulkActionProgress;
			currentProgress = +currentProgress;
			currentProgress = Math.floor(currentProgress);

			this.bulkActionProgress = currentProgress + progressIncreaser;

			dialog.find('.js-bulk-actions-progress').css('width', (this.bulkActionProgress) + '%');
			dialog.find('.sui-progress-text>span').text((this.bulkActionProgress) + '%');

			if (checkAjax && !$.ajaxq.isRunning(this.bulkAjaxQueueName)) {
				this.onBulkActionCompleted();
			}
		},
		onBulkActionCompleted: function () {
			var dialog = this.$el.find('#bulk-action-translation-modal');
			var stateText = dialog.find('.js-bulk-actions-state');
			var bulkPluginsCount = this.bulkPluginsList.length;

			dialog.find('.js-bulk-actions-progress').css('width', '100%');
			dialog.find('.sui-progress-text>span').text('100%');
			stateText.text('');
			dialog.find('.js-bulk-actions-loader-icon').hide();
			if (!this.bulkActionErrors.length) {
				window.location.href += "#translationUpdate=" + bulkPluginsCount;
				location.reload();
			}

		},
		isUpdateNotificationHash: function () {
			var hash = location.hash;
			return hash.indexOf('translationUpdate') !== -1;
		},
		getTranslationUpdateCount: function () {
			var hash = location.hash,
				count = 0,
				index = hash.indexOf('translationUpdate');
			if (index !== -1) {
				count = window.location.hash.slice(index);
				count = count.split('=')[1];
			}
			return count;
		},
		showNotification: function (noticeID, message) {
			var container = $('#' + noticeID),
				noticeOptions = {};

			noticeOptions.dismiss = {};
			noticeOptions.autoclose = {};
			noticeOptions.type = container.data('notice-type');
			noticeOptions.dismiss.show = container.data('show-dismiss');
			noticeOptions.autoclose.show = false;

			if (container.length !== 0) {
				message = '<p>' + message + '</p>';
				if ('success' === noticeOptions.type) {
					noticeOptions.icon = 'check-tick';
				}
				SUI.openNotice(noticeID, message, noticeOptions);
			}
		},
		showAlertNotice: function (container = '') {
			var container = $('.sui-settings-notice-alert'),
				noticeID = '',
				message = '',
				noticeOptions = {};

			if (!container.length) {
				return;
			}
			noticeOptions.dismiss = {};
			noticeOptions.autoclose = {};
			noticeOptions.type = container.data('notice-type');
			noticeOptions.dismiss.show = container.data('show-dismiss');
			noticeOptions.autoclose.show = false;
			message = container.data('notice-msg');
			noticeID = container.attr('id');

			if ('success' === noticeOptions.type) {
				noticeOptions.icon = 'check-tick';
			}

			SUI.openNotice(noticeID, message, noticeOptions);
		},
		showModals: function (container) {
			let modalId = $(container).data('modal-open'),
				focusAfterClosed = container,
				focusWhenOpen = undefined,
				hasOverlayMask = $(container).data('modal-mask'),
				hasTrigger = $(container).data('trigger');

			SUI.openModal(
				modalId,
				focusAfterClosed,
				focusWhenOpen,
				hasOverlayMask
			);

			if (hasTrigger) {
				$(window).trigger(hasTrigger);
			}
		},
		showTranslationUpdateModal: function (container) {

		}
	});

	// A really lightweight plugin wrapper around the constructor,
	// preventing against multiple instantiations
	$.fn[pluginName] = function (options) {
		return this.each(function () {
			if (!$.data(this, pluginName)) {
				$.data(this, pluginName, new wpmudevDashboardAdminSettingsPage(this, options));
			}
		});
	};

})(jQuery, window, document);
