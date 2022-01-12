// the semi-colon before function invocation is a safety net against concatenated
// scripts and/or other plugins which may not be closed properly.
// noinspection JSUnusedLocalSymbols
(function ($, window, document, undefined) {
	'use strict';

	// undefined is used here as the undefined global variable in ECMAScript 3 is
	// mutable (ie. it can be changed by someone else). undefined isn't really being
	// passed in so we can ensure the value of it is truly undefined. In ES5, undefined
	// can no longer be modified.

	// window and document are passed through as local variables rather than global
	// as this (slightly) quickens the resolution process and can be more efficiently
	// minified (especially when both are regularly referenced in your plugin).

	// Create the defaults once
	var pluginName = 'wpmudevDashboardAdminPluginsPage';

	// The actual plugin constructor
	function wpmudevDashboardAdminPluginsPage(element, options) {
		this.element = element;
		this.$el = $(this.element);
		this.plugins = [];
		this.limitPerPage = 10;
		this.skipPluginIds = [];
		this.page = 1;
		this.currentPluginsList = [];
		this.ftpDialog = null;
		this.pluginDialogs = {};
		this.pluginAfterInstallDialogs = {};
		this.actionEnabled = true;
		this.currentFilter = 'all';
		this.bulkPluginsList = {};
		this.bulkDialog = null;
		this.bulkActionErrors = [];
		this.bulkPluginAction = '';
		this.bulkAjaxQueueName = 'bulk-ajax-queue';
		this.bulkActionProgress = 0;
		this.isSearching = false;
		this.topPluginIds = [];
		this.newReleasePluginIds = [];
		this.init();
	}

	// Avoid Plugin.prototype conflicts
	$.extend(wpmudevDashboardAdminPluginsPage.prototype, {
		init: function () {
			this.maybeShowFtpDialog();
			var self = this;
			this.plugins = [];
			this.$el.find('.js-plugin-box').each(function () {
				var name = $(this).data('name');
				var info = $(this).data('info');
				var pluginData = $(this).data();
				pluginData['searchable'] = name + ' ' + info;
				self.plugins.push(pluginData);
			});

			this.displayPlugins(this.plugins, true);
			this.attachEvents();

			if (this.isChangelogHash() && !this.isUpdateHash()) {
				window.location.hash = '';
			}

			//check if hash is coming form dashborad
			//and if is an update hash trigger hashchage
			if (this.isUpdateHash()) {
				setTimeout(function () {
					$(window).trigger('hashchange');
				}, 100);
			} else {
				setTimeout(function () {
					$(window).trigger('hashchange');
				}, 3000);
			}

			// Show notifications if required.
			this.showSuccessNotification();
		},

		/**
		 * Show success notification if required.
		 *
		 * @since 4.11.4
		 */
		showSuccessNotification: function () {
			// Current url.
			let url = window.location.href;

			// Remove success action.
			if (url.match(/success-action=[^&]+/)) {
				// Remove it.
				url = url.replace(/success-action=[^&]+/, '');

				// Change url.
				window.history.pushState( {}, 'Plugins', url );

				// Show notifications if required.
				this.showNotification('notice-success-plugins', '', 'success');
			}
		},

		refreshPluginList: function () {
			this.plugins = [];
			var self = this;
			this.$el.find('.js-plugin-box').each(function () {
				var name = $(this).data('name');
				var info = $(this).data('info');
				var pluginData = $(this).data();
				pluginData['searchable'] = name + ' ' + info;
				self.plugins.push(pluginData);
			});

			this.filterPlugins(this.currentFilter, false);

			this.$el.find('.js-plugins-bulk-action').trigger('change');
		},

		/**
		 * Plugins list table
		 * @param plugins
		 * @param clearBulk
		 */
		displayPlugins: function (plugins, clearBulk) {
			this.currentPluginsList = plugins;
			var self = this;
			var i;

			// sort!
			// a- z
			plugins.sort(function (a, b) {
				var sortA = a.name;
				var sortB = b.name;
				if (sortA < sortB)
					return -1;
				if (sortA > sortB)
					return 1;
				return 0;
			});

			// compatible
			plugins.sort(function (a, b) {
				var sortA = +a.isCompatible;
				var sortB = +b.isCompatible;
				return sortB - sortA;
			});

			// installed
			plugins.sort(function (a, b) {
				var sortA = +a.installed;
				var sortB = +b.installed;
				return sortB - sortA;
			});

			// active
			plugins.sort(function (a, b) {
				var sortA = +a.active;
				var sortB = +b.active;
				return sortB - sortA;
			});

			// updates
			plugins.sort(function (a, b) {
				var sortA = +a.hasUpdate;
				var sortB = +b.hasUpdate;
				return sortB - sortA;
			});

			this.$el
			.find('.dashui-table-plugins tbody tr')
			.not('.bulk-action-row')
			.remove();

			var notInstalledHeader = 0;

			for (i = 0; i < plugins.length; i++) {
				var data = plugins[i];
				var row = this.$el
				.find('.js-plugin-box[data-project=' + data.project + '] .js-mode-row tr')
				.clone(true, true);
				row.hide();

				if (row.hasClass('dashui-is-notinstalled') && 0 === notInstalledHeader) {

					var avheader = this.$el
					.find('.js-available-plugin-header tr')
					.clone(true, true);

					self.$el.find('.dashui-table-plugins tbody').append(avheader);
					notInstalledHeader++;
				}

				self.$el.find('.dashui-table-plugins tbody').append(row);
			}

			this.paginate(plugins, clearBulk);

			setTimeout(function () {

				// Hide alert.
				self.$el
				.find('.dashui-plugin-loader')
				.fadeOut(1)
				.attr('tabindex', '-1')
				;

				// Show table.
				self.$el
				.find('.dashui-table-plugins')
				.fadeIn('slow')
				;
			}, 500);
		},
		attachEvents: function () {
			var self = this;

			this.$el.find('.js-header-search').on('click', function (e) {
				self.$el.find('input[name=search]').focus();
				e.preventDefault();
			});

			this.$el.find('input[name=search]').on('keydown', function (e) {
				var searchTerm = $(this).val();
				setTimeout(function () {
					self.search(searchTerm);
				}, 1500);
			});

			this.$el.find('.sui-tabs-menu .sui-tab-item').on('click', function () {
				if (!self.actionEnabled) {
					return false;
				}

				$(this)
				.closest('.sui-tabs-menu')
				.find('.sui-tab-item')
				.removeClass('active');
				$(this).addClass('active');
				self.filterPlugins($(this).data('filter'), true);
			});

			this.$el.find('#dashui-mobile-filter').on('change', function () {
				if (!self.actionEnabled) {
					return false;
				}

				self.filterPlugins($(this).val(), true);
			});

			this.$el.find('.dashui-plugin-box .sui-vertical-tab').on('click', function () {
				if (!self.actionEnabled) {
					return false;
				}

				$(this)
				.parent()
				.find('.sui-vertical-tab')
				.removeClass('current');
				$(this).addClass('current');
				self.filterPlugins($(this).children('a').data('filter'), true);
			});

			this.$el.on('click', 'a[data-action=project-changelog]', function (e) {
				e.preventDefault();
				if (!self.actionEnabled) {
					return false;
				}
				var data = $(this).data();
				self.showChangelog(data);
				return false;
			});

			this.$el.on('click', 'a[data-action=project-update]', function (e) {
				e.preventDefault();
				if (!self.actionEnabled) {
					return false;
				}
				var data = $(this).data();
				self.update(data, true);
				return false;
			});

			this.$el.on('click', 'a[data-action=project-activate]', function (e) {
				e.preventDefault();
				if (!self.actionEnabled) {
					return false;
				}
				var data = $(this).data();
				self.activate(data);
				return false;
			});

			this.$el.on('click', 'a[data-action=project-deactivate]', function (e) {
				e.preventDefault();
				if (!self.actionEnabled) {
					return false;
				}
				var data = $(this).data();
				self.deactivate(data);
				return false;
			});

			this.$el.on('click', 'a[data-action=project-install]', function (e) {
				e.preventDefault();
				if (!self.actionEnabled) {
					return false;
				}
				var data = $(this).data();
				self.install(data);
				return false;
			});

			this.$el.on('click', 'a[data-action=project-delete]', function (e) {
				e.preventDefault();
				if (!self.actionEnabled) {
					return false;
				}
				var data = $(this).data();
				self.uninstall(data);
				return false;
			});

			this.$el.on('click', '.sui-notice-top .sui-notice-dismiss', function (e) {
				e.preventDefault();
				$(this)
				.closest('.sui-notice-top')
				.stop()
				.slideUp('slow');
				return false;
			});

			this.$el.on('submit', '.sui-modal #ftp-details form', function (e) {
				e.preventDefault();
				self.saveFtp();
				return false;
			});

			this.$el.on('click', '.js-show-plugin-modal', function (e) {
				e.preventDefault();
				var project_id = $(this).data('project'),
					dialogType = $(this).data('action');
				if (project_id) {
					if ('changelog' !== dialogType) {
						self.showPluginDialog(project_id, this);
					} else {
						self.showChangelog(project_id);
					}
				}
				return false;
			});

			// modal tabs
			this.$el.on('click', '.sui-tabs div[data-tabs=""] div', function () {
				var tabWrapper = $(this).closest('.sui-tabs');
				var index = $(this).data('index');

				tabWrapper.find('div[data-tabs=""] div').removeClass('active');
				$(this).addClass('active');

				tabWrapper.find('div[data-panes=""] div').removeClass('active');
				tabWrapper
				.find('div[data-panes=""] div[data-index="' + index + '"]')
				.addClass('active');
			});

			// SLIDER: Navigation thumbnails
			this.$el.on('click', '.dashui-slider .dashui-slider-nav-items li', function () {

				var $thumb = $(this),
					$parent = $thumb.closest('.dashui-slider'),
					$thumbs = $parent.find('.dashui-slider-nav-items li'),
					$sliders = $parent.find('.dashui-slider-main'),
					$slides = $sliders.find('li'),
					$slide = $sliders.find('li.' + $thumb.data('key'))
				;

				$thumbs.removeClass('current');
				$slides.removeClass('current');

				// Select current thumbnail
				$thumb.addClass('current');

				// Get current slide
				$slide.addClass('current');

				if ($sliders.height() !== ($slide.find('img').height() - 1)) {

					$sliders.css({
						'height': ($slide.find('img').height() - 1) + 'px'
					});
				}
			});

			// SLIDER: Navigation right arrow
			this.$el.on('click', '.dashui-slider .dashui-slider-nav-right', function () {

				var $button = $(this),
					$parent = $button.closest('.dashui-slider'),
					$slider = $parent.find('.dashui-slider-main'),
					$thumbs = $parent.find('.slider-nav')
				;

				// Function to animate the images in forward direction
				var forward = function () {

					var $currThumb = $thumbs.find('li.current'),
						$nextThumb = $currThumb.next()
					;

					var $currSlide = $slider.find('li.current'),
						$nextSlide = $currSlide.next()
					;

					if (!$nextThumb.length) {

						$nextThumb = $thumbs.find('li:first');
						$nextSlide = $slider.find('li:first');

						$nextThumb.addClass('current');
						$nextSlide.addClass('current');

						$currThumb.removeClass('current');
						$currSlide.removeClass('current');

					} else {

						$nextThumb.addClass('current');
						$nextSlide.addClass('current');

						$currThumb.removeClass('current');
						$currSlide.removeClass('current');

					}

					if ($slider.height() !== ($nextSlide.find('img').height() - 1)) {

						$slider.css({
							'height': ($nextSlide.find('img').height() - 1) + 'px'
						});
					}
				};

				forward();

			});

			// SLIDER: Navigation left arrow
			this.$el.on('click', '.dashui-slider .dashui-slider-nav-left', function () {

				var $button = $(this),
					$parent = $button.closest('.dashui-slider'),
					$slider = $parent.find('.dashui-slider-main'),
					$thumbs = $parent.find('.slider-nav')
				;

				// Function to animate the images in backward direction
				var backward = function () {

					var $currThumb = $thumbs.find('li.current'),
						$prevThumb = $currThumb.prev()
					;

					var $currSlide = $slider.find('li.current'),
						$prevSlide = $currSlide.prev()

					if (!$prevThumb.length) {

						$prevThumb = $thumbs.find('li:last');
						$prevSlide = $slider.find('li:last');

						$prevThumb.addClass('current');
						$prevSlide.addClass('current');

						$currThumb.removeClass('current');
						$currSlide.removeClass('current');

					} else {

						$prevThumb.addClass('current');
						$prevSlide.addClass('current');

						$currThumb.removeClass('current');
						$currSlide.removeClass('current');

					}

					if ($slider.height() !== ($prevSlide.find('img').height() - 1)) {

						$slider.css({
							'height': ($prevSlide.find('img').height() - 1) + 'px'
						});
					}
				}

				backward();

			});

			// Checkboxes
			this.$el.on('change', 'input.js-plugin-check', function () {
				var project_id = $(this).val();
				if ($(this).is(':checked')) {
					var plugin = self.searchPluginById(project_id);
					if (plugin) {
						self.bulkPluginsList[project_id] = plugin;
					}
				} else {
					var bulkPluginsList = self.bulkPluginsList;
					delete bulkPluginsList[project_id];
					self.bulkPluginsList = bulkPluginsList;
				}

				self.$el.find('.js-plugins-bulk-action').trigger('change');
			});

			this.$el.on('change', '.js-plugins-bulk-action', function () {
				var ids = Object.keys(self.bulkPluginsList);
				var bulkModal = self.$el.find('.sui-modal #bulk-action-modal');

				if (!ids.length || self.bulkPluginAction === '') {
					$(this)
					.find('.js-plugins-bulk-action-button')
					.attr('disabled', 'disabled');
				} else {
					$(this)
					.find('.js-plugins-bulk-action-button')
					.prop('disabled', false);
				}
			});

			this.$el.on('change', 'input.js-plugin-check-all', function () {
				var checkboxes = self.$el.find('.dashui-table-plugins tbody tr td.dashui-column-title input.js-plugin-check').not(':hidden');
				if ($(this).is(':checked')) {
					checkboxes.prop('checked', true).trigger('change');
				} else {
					checkboxes.prop('checked', false).trigger('change');
				}
			});

			this.$el.on('click', '.js-plugins-bulk-action-button', function () {
				let ids = Object.keys(self.bulkPluginsList);
				let activePlugins = [];
				let notInstalledPlugins = [];
				if (!ids.length) {
					return false;
				} else {
					//filter plugin with action
					var action = self.bulkPluginAction;
					for (var project_id in self.bulkPluginsList) {
						var isProject = self.bulkPluginsList[project_id];
						switch (action) {
							case 'activate':
								if (!isProject.installed || isProject.active) {
									delete self.bulkPluginsList[project_id];
									$('#bulk-action-' + project_id).prop("checked", false);
								}
								break;
							case 'deactivate':
								if (!isProject.active) {
									delete self.bulkPluginsList[project_id];
									$('#bulk-action-' + project_id).prop("checked", false);
								}
								break;
							case 'update':
								if (!isProject.hasUpdate || !isProject.installed) {
									delete self.bulkPluginsList[project_id];
									$('#bulk-action-' + project_id).prop("checked", false);
								}
								break;
							case 'delete':
								if (isProject.active || !isProject.installed) {
									delete self.bulkPluginsList[project_id];
									$('#bulk-action-' + project_id).prop("checked", false);
									if (isProject.active) {
										activePlugins.push(isProject);
									} else if (!isProject.installed) {
										notInstalledPlugins.push(isProject);
									}
								}
								break;
							case 'install':
								if (isProject.installed) {
									delete self.bulkPluginsList[project_id];
									$('#bulk-action-' + project_id).prop("checked", false);
								}
								break;
							case 'install-activate':
								if (isProject.installed && isProject.active) {
									delete self.bulkPluginsList[project_id];
									$('#bulk-action-' + project_id).prop("checked", false);
								}
								break;
						}
					}

					var bulkPluginsCount = Object.keys(self.bulkPluginsList).length;
					if (bulkPluginsCount) {
						SUI.openModal(
							'bulk-action-modal',
							this,
							undefined,
							true
						);
						self.onBulkDialogShow();

						if (activePlugins.length || notInstalledPlugins.length) {
							let message = '<p>' + wdp_locale.plugins_cannot_delete + '</p>';
							activePlugins.forEach((plugin) => {
								message += '<p>' + plugin.name + ': ' + wdp_locale.plugins_active + '</p>';
							})

							notInstalledPlugins.forEach((plugin) => {
								message += '<p>' + plugin.name + ': ' + wdp_locale.plugins_not_installed + '</p>';
							})
							self.showBulkErrorNotice(message);
						}
					} else {
						$('.js-plugins-bulk-action')
						.find('.js-plugins-bulk-action-button')
						.attr('disabled', 'disabled');
						$('#bulk-actions-all').prop("checked", false);

						if (activePlugins.length || notInstalledPlugins.length) {
							self.showNotification(
								'js-failed-deleted-multiple',
								'',
								'warning'
							);
						}
					}
				}
				return false;
			});

			// bulk plugin selector
			this.$el.on('change', 'select[name="current-bulk-action"]', function () {
				self.bulkPluginAction = $(this).val();
				self.$el.find('.js-plugins-bulk-action').trigger('change');
			});

			// close bulk plugin
			this.$el.on('click', '.sui-modal .bulk-modal-close', function () {
				setTimeout(function () {
					self.onBulkDialogHide();
				}, 1000);
			});


			// close bulk plugin
			this.$el.on('click', '.sui-modal .plugin-modal-close', function () {
				var self = this;
				setTimeout(function () {
					if ($(self).closest('.sui-modal').hasClass('sui-active')) {
						SUI.closeModal();
					}
				}, 1000);

				if ($(this).closest('.sui-modal-content').hasClass('js-plugin-modal')) {
					window.location.hash = '_';
				}

			});

			$('.sui-modal-content').on('afterClose', function () {
				window.location.hash = '_'
			});

			$(window).on('hashchange', function () {
				self.processHash();
			});

		},

		searchPluginById: function (project_id) {
			project_id = +project_id;
			var i;
			for (i = 0; i < this.plugins.length; i++) {
				var data = this.plugins[i];
				var pid = +data.project;
				if (pid === project_id) {
					return data;
				}
			}
			return false;
		},

		filterPlugins: function (filter, resetPage) {
			var plugins = [];
			var data, i;
			this.currentFilter = filter;
			var no_result_search_lang = 'no_result_search_plugin_all';
			var no_plugins_lang = '';

			this.$el.find('.js-plugins-showcase').hide();

			switch (filter) {
				case 'all':
					plugins = this.plugins;
					if (!this.isSearching) {
						this.$el.find('.js-plugins-showcase').show();
					}
					break;
				case 'activated':
					for (i = 0; i < this.plugins.length; i++) {
						data = this.plugins[i];
						if (data.installed && data.active) {
							plugins.push(data);
						}
					}
					no_result_search_lang = 'no_result_search_plugin_activated';
					no_plugins_lang = 'no_plugin_activated';
					break;
				case 'deactivated':
					for (i = 0; i < this.plugins.length; i++) {
						data = this.plugins[i];
						if (data.installed && !data.active) {
							plugins.push(data);
						}
					}
					no_result_search_lang = 'no_result_search_plugin_deactivated';
					no_plugins_lang = 'no_plugin_deactivated';
					break;
				case 'hasupdate':
					for (i = 0; i < this.plugins.length; i++) {
						data = this.plugins[i];
						if (data.installed && data.hasUpdate) {
							plugins.push(data);
						}
					}
					no_result_search_lang = 'no_result_search_plugin_updates';
					no_plugins_lang = 'no_plugin_updates';
					break;
			}
			if (resetPage) {
				// reset page
				this.page = 1;
			}

			this.$el.find('.js-no-result-search').addClass('sui-hidden');
			this.$el.find('.dashui-table-plugins').removeClass('sui-hidden');
			if (this.isSearching) {
				plugins = this.getSearchResult(plugins, this.$el.find('input[name=search]').val().toLowerCase());

				if (plugins.length < 1) {
					var no_result_search_message = window.wdp_locale[no_result_search_lang];
					if (no_result_search_message) {
						this.showSearchNotice(no_result_search_message);
						this.$el.find('.js-no-result-search').removeClass('sui-hidden');
						this.$el.find('.dashui-table-plugins').addClass('sui-hidden');
					}
				} else {
					this.closeSearchNotice();
				}
			} else {
				if (plugins.length < 1 && no_plugins_lang) {
					var no_plugins_message = window.wdp_locale[no_plugins_lang];
					if (no_plugins_message) {
						this.showSearchNotice(no_plugins_message);
						this.$el.find('.js-no-result-search').removeClass('sui-hidden');
						this.$el.find('.dashui-table-plugins').addClass('sui-hidden');
					}
				} else {
					this.closeSearchNotice();
				}
			}

			var clearBulk = true;
			if (this.currentFilter === filter) {
				clearBulk = false;
			}
			this.displayPlugins(plugins, clearBulk);
		},
		showSearchNotice(message) {
			var container = $('.js-no-result-search-message'),
				noticeID = '',
				noticeOptions = {};
			if (!container.length) {
				return;
			}
			noticeOptions.dismiss = {};
			noticeOptions.autoclose = {};
			noticeOptions.type = container.data('notice-type');
			noticeOptions.dismiss.show = container.data('show-dismiss');
			noticeOptions.autoclose.show = false;
			noticeID = container.attr('id');
			message = '<p>' + message + '</p>';

			SUI.openNotice(noticeID, message, noticeOptions);
		},

		closeSearchNotice() {
			var container = $('.js-no-result-search-message');
			if (!container.length) {
				return;
			}
			SUI.closeNotice(container.attr('id'));
		},

		paginate: function (plugins, clearBulk) {
			var project_id = 0;
			if (clearBulk) {
				// clean checked before hide
				for (project_id in this.bulkPluginsList) {
					if (this.bulkPluginsList.hasOwnProperty(project_id)) {
						this.$el
						.find(
							'.dashui-table-plugins tbody tr td.dashui-column-title input.js-plugin-check[value=' +
							project_id +
							']'
						)
						.prop('checked', false)
						.trigger('change');
					}
				}

				this.bulkPluginsList = {};
				this.$el.find('.js-plugins-bulk-action').trigger('change');
			}

			// hide all
			this.$el
			.find('.dashui-table-plugins tbody tr')
			.not('.bulk-action-row')
			.show();

			// restore checked
			for (project_id in this.bulkPluginsList) {
				this.$el.find('.dashui-table-plugins tbody tr[data-project=' + project_id + '] .js-plugin-check').attr('checked', 'checked');
			}

		},
		search: function (value) {

			var clearBulk = false;
			if (this.$el.find('.sui-tabs-menu .sui-tab-item.active').data('filter') !== 'all') {
				clearBulk = true;
			}

			this.$el.find('.sui-tabs-menu .sui-tab-item').removeClass('active');
			this.$el.find('.sui-tabs-menu .sui-tab-item[data-filter=all]').addClass('active');
			this.currentFilter = 'all';

			var toSearch = value.toLowerCase();
			if (toSearch === '') {
				this.isSearching = false;
				this.page = 1;
				this.filterPlugins(this.currentFilter, clearBulk);
				return;
			}

			this.isSearching = true;
			this.page = 1;
			this.filterPlugins(this.currentFilter, clearBulk);
		},
		getSearchResult: function (pluginsList, toSearch) {
			var plugins = [];
			var data, i;
			var searchable = '';
			for (i = 0; i < pluginsList.length; i++) {
				data = pluginsList[i];
				searchable = data.searchable.toLowerCase();
				if (searchable.indexOf(toSearch) !== -1) {
					plugins.push(data);
				}
			}
			return plugins;
		},
		activate: function (data) {
			var self = this;

			var project_id = data.project;
			this.$el
			.find('a.sui-button[data-action=project-activate][data-project=' + project_id + ']')
			.addClass('sui-button-onload');

			this.$el
			.find('tr[data-project=' + project_id + '] .dashui-loader-wrap .dashui-loader-text')
			.addClass('sui-hidden');

			this.$el
			.find('tr[data-project=' + project_id + '] .dashui-loader-wrap .dashui-loader')
			.show();

			this.$el
			.find('tr[data-project=' + project_id + '] .dashui-loader-wrap .dashui-loader .dashui-loader-delete')
			.hide();

			this.disableActions(project_id);
			this.hideNotifications();

			var ajaxData = {
				action: 'wdp-' + data.action,
				hash: data.hash,
				pid: project_id,
				is_network: +$('body').hasClass('network-admin'),
			};

			$.post(
				window.ajaxurl,
				ajaxData,
				function (response) {
					if (response.success) {
						self.showNotification('js-activated-single', '', 'success');
						window.location.reload();
					} else {
						if (response.data && response.data.message) {
							self.showNotification(
								'js-failed-activated-single',
								response.data.message,
								'error'
							);
						} else {
							self.showNotification('js-general-fail', '', 'error');
						}
					}
				},
				'json'
			)
			.always(function () {
				self.$el
				.find(
					'a.sui-button[data-action=project-activate][data-project=' +
					project_id +
					']'
				)
				.removeClass('sui-button-onload');

				self.$el
				.find('tr[data-project=' + project_id + '] .dashui-loader-wrap .dashui-loader')
				.hide();

				self.enableActions(project_id);
			})
			.fail(function (xhr, statusText, exception) {
				self.showNotification('js-general-fail', '');
			});
		},
		deactivate: function (data) {
			var self = this;

			var project_id = data.project;
			this.$el
			.find(
				'a.sui-button[data-action=project-deactivate][data-project=' + project_id + ']'
			)
			.addClass('sui-button-onload');

			this.$el
			.find('tr[data-project=' + project_id + '] .dashui-loader-wrap .dashui-loader-text')
			.addClass('sui-hidden');

			this.$el
			.find('tr[data-project=' + project_id + '] .dashui-loader-wrap .dashui-loader')
			.show();

			this.disableActions(project_id);
			this.hideNotifications();

			var ajaxData = {
				action: 'wdp-' + data.action,
				hash: data.hash,
				pid: project_id,
				is_network: +$('body').hasClass('network-admin'),
			};

			$.post(
				window.ajaxurl,
				ajaxData,
				function (response) {
					if (response.success) {
						self.showNotification('js-deactivated-single', '', 'success');
						window.location.reload();
					} else {
						if (response.data && response.data.message) {
							self.showNotification(
								'js-failed-deactivated-single',
								response.data.message,
								'error'
							);
						} else {
							self.showNotification('js-general-fail', '', 'error');
						}
					}
				},
				'json'
			)
			.always(function () {
				self.$el
				.find(
					'a.sui-button[data-action=project-deactivate][data-project=' +
					project_id +
					']'
				)
				.removeClass('sui-button-onload');

				self.$el
				.find('tr[data-project=' + project_id + '] .dashui-loader-wrap .dashui-loader')
				.hide();

				self.enableActions(project_id);
			})
			.fail(function (xhr, statusText, exception) {
				self.showNotification('js-general-fail', '', 'error');
			});
		},
		showChangelog: function (project_id) {
			if (this.isChangelogHash()) {
				// do update
				return true;
			} else {
				// append changelog hash
				window.location.hash += 'changelog';
				this.showPluginDialog(project_id);
				this.enableActions(project_id);
				this.$el
				.find(
					'a.sui-button[data-action=project-update][data-project=' + project_id + ']'
				)
				.removeClass('sui-button-onload');
				return false;
			}
		},
		update: function (data, showChangelog) {
			if (this.maybeShowFtpDialog()) {
				return false;
			}

			var self = this;
			var doUpdate = true;

			var project_id = data.project;
			this.$el
			.find('a.sui-button[data-action=project-update][data-project=' + project_id + ']')
			.addClass('sui-button-onload');
			this.hideNotifications();

			if (showChangelog) {
				doUpdate = this.showChangelog(project_id);
			}

			if (doUpdate) {
				this.disableActions(project_id);
				// Actual update
				var ajaxData = {
					action: 'wdp-' + data.action,
					hash: data.hash,
					pid: project_id,
					is_network: +$('body').hasClass('network-admin'),
				};

				$.post(
					window.ajaxurl,
					ajaxData,
					function (response) {
						if (response.success) {
							self.showNotification('js-updated-single', '', 'success');
							// update plugin box

							if (response.data && response.data.html) {
								self.$el
								.find('.js-plugin-box[data-project=' + project_id + ']')
								.replaceWith(response.data.html);
								SUI.closeModal();
								self.refreshPluginList();
								self.hidePluginDialog(project_id);
								self.refreshUpdateCount(1);
							}
						} else {
							if (response.data && response.data.message) {
								self.showNotification(
									'js-failed-updated-single',
									response.data.message,
									'error'
								);
							} else {
								self.showNotification('js-general-fail', '', 'error');
							}
						}
					},
					'json'
				)
				.always(function () {
					self.$el
					.find(
						'a.sui-button[data-action=project-update][data-project=' +
						project_id +
						']'
					)
					.removeClass('sui-button-onload');
					self.enableActions(project_id);
				})
				.fail(function (xhr, statusText, exception) {
					self.showNotification('js-general-fail', '', 'error');
				});
			} else {
				return false;
			}
		},
		install: function (data) {
			if (this.maybeShowFtpDialog()) {
				return false;
			}

			var self = this;

			var project_id = data.project;
			this.$el
			.find('a.sui-button[data-action=project-install][data-project=' + project_id + ']')
			.addClass('sui-button-onload');

			this.$el
			.find(
				'a.sui-button-icon[data-action=project-install][data-project=' +
				project_id +
				']'
			)
			.removeClass('sui-button-onload');
			this.$el
			.find(
				'a.sui-button-icon[data-action=project-install][data-project=' +
				project_id +
				']'
			)
			.addClass('sui-button-onload');
			this.disableActions(project_id);
			this.hideNotifications();

			var ajaxData = {
				action: 'wdp-' + data.action,
				hash: data.hash,
				pid: project_id,
				is_network: +$('body').hasClass('network-admin'),
			};

			$.post(
				window.ajaxurl,
				ajaxData,
				function (response) {
					if (response.success) {
						// update plugin box

						if (response.data && response.data.html) {
							self.hidePluginDialog(project_id);

							var plugin = self.searchPluginById(project_id);
							if (plugin && !plugin.active) {
								self.showPluginDialogAfterInstall(project_id)
							}
							self.$el
							.find('.js-plugin-box[data-project=' + project_id + ']')
							.replaceWith(response.data.html);
							self.refreshPluginList();
						}
					} else {
						if (response.data && response.data.message) {
							self.showNotification(
								'js-failed-installed-single',
								response.data.message,
								'error'
							);
						} else {
							self.showNotification('js-general-fail', '', 'error');
						}
					}
				},
				'json'
			)
			.always(function () {
				self.$el
				.find(
					'a.sui-button[data-action=project-install][data-project=' +
					project_id +
					']'
				)
				.removeClass('sui-button-onload');
				self.$el
				.find(
					'a.sui-button-icon[data-action=project-install][data-project=' +
					project_id +
					']'
				)
				.find('.sui-loading-text')
				.show();
				self.$el
				.find(
					'a.sui-button-icon[data-action=project-install][data-project=' +
					project_id +
					']'
				)
				.find('.sui-loading')
				.hide();

				self.enableActions(project_id);
			})
			.fail(function (xhr, statusText, exception) {
				self.showNotification('js-general-fail', '', 'error');
			});
		},
		uninstall: function (data) {
			if (this.maybeShowFtpDialog()) {
				return false;
			}

			var self = this;

			var project_id = data.project;
			this.$el
			.find('a.sui-button[data-action=project-delete][data-project=' + project_id + ']')
			.addClass('sui-button-onload');

			this.$el
			.find('tr[data-project=' + project_id + '] .dashui-loader-wrap .dashui-loader-text')
			.addClass('sui-hidden');

			this.$el
			.find('tr[data-project=' + project_id + '] .dashui-loader-wrap .dashui-loader')
			.show();

			this.$el
			.find('tr[data-project=' + project_id + '] .dashui-loader-wrap .dashui-loader .dashui-loader-activate')
			.hide();

			this.disableActions(project_id);
			this.hideNotifications();

			var ajaxData = {
				action: 'wdp-' + data.action,
				hash: data.hash,
				pid: project_id,
				is_network: +$('body').hasClass('network-admin'),
			};

			$.post(
				window.ajaxurl,
				ajaxData,
				function (response) {
					if (response.success) {
						self.showNotification('js-deleted-single', '', 'success');
						// update plugin box

						if (response.data && response.data.html) {
							self.$el
							.find('.js-plugin-box[data-project=' + project_id + ']')
							.replaceWith(response.data.html);
							self.refreshPluginList();
							self.hidePluginDialog(project_id);
						}
					} else {
						if (response.data && response.data.message) {
							self.showNotification(
								'js-failed-deleted-single',
								response.data.message,
								'error'
							);
						} else {
							self.showNotification('js-general-fail', '', 'error');
						}
					}
				},
				'json'
			)
			.always(function () {
				self.$el
				.find(
					'a.sui-button[data-action=project-delete][data-project=' +
					project_id +
					']'
				)
				.removeClass('sui-button-onload');

				self.$el
				.find('tr[data-project=' + project_id + '] .dashui-loader-wrap .dashui-loader')
				.hide();

				self.enableActions(project_id);
			})
			.fail(function (xhr, statusText, exception) {
				self.showNotification('js-general-fail', '', 'error');
			});
		},
		applyBulkAction: function () {
			var dialog = this.$el.find('.sui-modal #bulk-action-modal');

			this.disableActions(null);

			var action = this.bulkPluginAction;

			this.bulkActionProgress = 0;
			dialog.find('.sui-progress-text>span').text('0%');
			dialog.find('.js-bulk-actions-progress').css('width', '0%');
			dialog.find('.js-bulk-actions-loader-icon').show();

			this.bulkActionErrors = [];
			var i = 1;
			var isLast = false;
			var bulkPluginsCount = Object.keys(this.bulkPluginsList).length;

			if (bulkPluginsCount > 0) {
				for (var project_id in this.bulkPluginsList) {

					if (i === bulkPluginsCount) {
						isLast = true;
					}
					if (this.bulkPluginsList.hasOwnProperty(project_id)) {
						this.addBulkQueue(action, project_id, isLast);
					}
					i++;
				}
			}
		},
		addBulkQueue: function (action, project_id, isLast) {
			var self = this;
			var dialog = this.$el.find('.sui-modal #bulk-action-modal');
			var ajax_action = 'wdp-project-' + action;
			var hash = dialog.find('.js-bulk-hash').data(action);
			var plugin = this.searchPluginById(project_id);

			var bulkPluginsCount = Object.keys(this.bulkPluginsList).length;
			var progressIncreaser = 100 / bulkPluginsCount;
			progressIncreaser = +progressIncreaser;
			progressIncreaser = Math.floor(progressIncreaser);

			var stateText = dialog.find('.js-bulk-actions-state');
			var stateBaseText = '%s';

			switch (action) {
				case 'update':
					stateBaseText = window.wdp_locale.updating_plugin;
					break;
				case 'activate':
					stateBaseText = window.wdp_locale.activating_plugin;
					break;
				case 'install':
					stateBaseText = window.wdp_locale.installing_plugin;
					break;
				case 'install-activate':
					stateBaseText = window.wdp_locale.installing_activating_plugin;
					break;
				case 'deactivate':
					stateBaseText = window.wdp_locale.deactivating_plugin;
					break;
				case 'delete':
					stateBaseText = window.wdp_locale.deleting_plugin;
					break;
			}

			// block incompatible plugin action by UI
			if (action === 'update' || action === 'activate' || action === 'install' || action === 'install-activate') {
				if (!plugin.isCompatible) {
					stateBaseText = stateBaseText.replace('%s', plugin.name);
					stateText.text(stateBaseText);

					// delay 1 sec
					setTimeout(function () {
						var message = '<p>' + plugin.name + ' : ' + plugin.incompatibleReason + '</p>';
						self.showBulkErrorNotice(message);
						self.bulkActionErrors.push(plugin.incompatibleReason);
						self.updateBulkProgressBar(dialog, action, progressIncreaser, (!!isLast));
					}, 1000);

					return;
				}
			}

			$.ajaxq(this.bulkAjaxQueueName, {
				type: 'POST',
				url: window.ajaxurl,
				data: {
					action: ajax_action,
					hash: hash,
					pid: project_id,
					is_network: +$('body').hasClass('network-admin'),
				},
				beforeSend: function () {
					stateBaseText = stateBaseText.replace('%s', plugin.name);
					stateText.text(stateBaseText);

				},
				success: function (response) {
					if (response.success) {
						if (response.data && response.data.html) {
							self.$el
							.find('.js-plugin-box[data-project=' + project_id + ']')
							.replaceWith(response.data.html);
							self.hidePluginDialog(project_id);
							if (action === 'update') {
								self.refreshUpdateCount(1);
							}
						}
					} else {
						if (response.data && response.data.message) {
							var message = '<p>' + plugin.name + ' : ' + response.data.message + '</p>';
							self.showBulkErrorNotice(message);
						}

						self.bulkActionErrors.push(response.data.message);
					}
				},
				error: function (error) {
					var message = '<p>' + plugin.name + ' : HTTP Request Error</p>';
					self.showBulkErrorNotice(message);
					self.bulkActionErrors.push('HTTP Request Error');
				},
				complete: function () {
					self.updateBulkProgressBar(dialog, action, progressIncreaser, true);
				},
			});

		},
		updateBulkProgressBar: function (dialog, action, progressIncreaser, checkAjax) {
			var currentProgress = this.bulkActionProgress;
			currentProgress = +currentProgress;
			currentProgress = Math.floor(currentProgress);

			this.bulkActionProgress = currentProgress + progressIncreaser;

			dialog.find('.js-bulk-actions-progress').css('width', (this.bulkActionProgress) + '%');
			dialog.find('.sui-progress-text>span').text((this.bulkActionProgress) + '%');

			if (checkAjax && !$.ajaxq.isRunning(this.bulkAjaxQueueName)) {
				this.onBulkActionCompleted(action);
			}
		},
		onBulkActionCompleted: function (action) {
			var dialog = this.$el.find('.sui-modal #bulk-action-modal');
			var stateText = dialog.find('.js-bulk-actions-state');
			var bulkPluginsCount = Object.keys(this.bulkPluginsList).length;

			dialog.find('.js-bulk-actions-progress').css('width', '100%');
			dialog.find('.sui-progress-text>span').text('100%');
			stateText.text('');
			dialog.find('.js-bulk-actions-loader-icon').hide();

			this.enableActions(null);

			if (action === 'activate' || action === 'deactivate' || action === 'install-activate') {
				if (!this.bulkActionErrors.length) {
					//deactivate and activate need to reloaded to view new menus if avail
					dialog.find('').show();
					var noticeID = 'js-bulk-message-need-reload-' + action,
						noticeOptions = {},
						noticeMsg = $('#' + noticeID).data('message');

					noticeOptions.autoclose = {};
					noticeOptions.autoclose.show = false;
					SUI.openNotice(noticeID, noticeMsg, noticeOptions);

				}
			} else {
				if (!this.bulkActionErrors.length) {
					// dialog.close
					this.onBulkDialogHide();

					// notif
					var notif = '';

					switch (action) {
						case 'update':
							notif = 'js-updated-single';
							if (bulkPluginsCount > 1) {
								notif = 'js-updated-bulk';
							}
							break;
						case 'install':
							notif = 'js-installed-single';
							if (bulkPluginsCount > 1) {
								notif = 'js-installed-bulk';
							}
							break;
						case 'delete':
							notif = 'js-deleted-single';
							if (bulkPluginsCount > 1) {
								notif = 'js-deleted-bulk';
							}
							break;
					}


					if (notif) {
						this.showNotification(notif, '', 'success');
					}
				}
			}
		},
		showNotification: function (noticeID, message, type = 'success') {
			var container = $('#' + noticeID),
				noticeOptions = {};

			noticeOptions.dismiss = {};
			noticeOptions.autoclose = {};
			noticeOptions.type = type;
			noticeOptions.autoclose.show = false;

			if (container.length !== 0) {
				noticeOptions.dismiss.show = container.data('show-dismiss');
				if (!message) {
					message = container.data('notice-msg');
				} else {
					message = container.data('notice-msg') + '<p class="js-custom-message">' + message + '</p>';
				}
				if ('success' === noticeOptions.type) {
					noticeOptions.icon = 'check-tick';
				}
				SUI.openNotice(noticeID, message, noticeOptions);
			}
		},
		showBulkErrorNotice: function (message) {
			var noticeID = 'js-bulk-errors',
				dialog = this.$el.find('.sui-modal #bulk-action-modal'),
				noticeOptions = {};
			noticeOptions.dismiss = {};
			noticeOptions.autoclose = {};
			noticeOptions.type = 'warning';
			noticeOptions.autoclose.show = false;
			noticeOptions.dismiss.show = false;

			if ($('#' + noticeID).hasClass('sui-active')) {
				dialog.find('.js-bulk-errors .sui-notice-content .sui-notice-message ').append(message);
			} else {
				SUI.openNotice(noticeID, message, noticeOptions);
			}
		},
		hideNotifications: function () {
			this.$el.find('.sui-notice-top').hide();
		},
		maybeShowFtpDialog: function () {
			if (this.$el.find('#ftp-details').length > 0) {
				var modalID = 'ftp-details',
					container = 'dashui-all-plugins';
				SUI.openModal(
					modalID,
					container,
					undefined,
					false
				);
				return true;
			}
			return false;
		},
		saveFtp: function () {
			var form = this.$el.find('#ftp-details form');
			var dialog = this.$el.find('#ftp-details');
			dialog.find('.ftp-submit').addClass('sui-button-onload');
			dialog.find('.sui-notice').hide();
			form.attr('disabled', 'disabled');

			var ajaxData = {
				action: 'wdp-credentials',
				hash: form.find('input[name=hash]').val(),
				ftp_pass: form.find('input#ftp_pass').val(),
				ftp_user: form.find('input#ftp_user').val(),
				ftp_host: form.find('input#ftp_host').val(),
			};

			$.post(
				window.ajaxurl,
				ajaxData,
				function (response) {
					if (response.success) {
						dialog.find('.sui-notice.sui-notice-success').show();
						window.location.reload();
					} else {
						dialog.find('.sui-notice.sui-notice-error').show();
					}
				},
				'json'
			)
			.always(function () {
				form.find('.ftp-submit').removeClass('sui-button-onload');
				form.prop('disabled', false);
			})
			.fail(function (xhr, statusText, exception) {
				dialog.find('.sui-notice.sui-notice-error').show();
			});
		},
		onBulkDialogHide: function () {
			if ($('#bulk-action-modal').parent('.sui-modal').hasClass('sui-active')) {
				SUI.closeModal();
			}
			if ($.ajaxq.isRunning(this.bulkAjaxQueueName)) {
				$.ajaxq.abort(this.bulkAjaxQueueName);
			}
			if (!this.actionEnabled) {
				this.enableActions(null);
			}
			this.refreshPluginList();
		},
		onBulkDialogShow: function () {
			var dialog = this.$el.find('.sui-modal #bulk-action-modal');

			this.bulkActionErrors = [];

			SUI.closeNotice('js-bulk-message-need-reload-activate');
			SUI.closeNotice('js-bulk-message-need-reload-deactivate');
			SUI.closeNotice('js-bulk-message-need-reload-install-activate');
			SUI.closeNotice('js-bulk-errors');

			this.applyBulkAction();

		},
		showPluginDialog: function (project_id, container = undefined) {
			var self = this;
			// check if free
			if (this.$el.find('#upgrade-membership').length) {
				return false;
			}

			if (!container) {
				container = 'show-modal-' + project_id;
			}

			var modalID = 'plugin-modal-' + project_id;
			self.loadPluginDialogContent(project_id);

			SUI.openModal(
				modalID,
				container,
				undefined,
				true
			);
		},
		hidePluginDialog: function (project_id) {
			var pluginmodal = $('#plugin-modal-' + project_id);
			if (pluginmodal.parent('.sui-modal').hasClass('sui-active')) {
				SUI.closeModal();
			}
		},
		initPluginDialog: function (project_id) {
			var self = this;
			if (typeof this.pluginDialogs[project_id] === 'object') {
				this.pluginDialogs[project_id].destroy();
			}

			var dialog = document.getElementById('plugin-modal-' + project_id);
			if (dialog.length > 0) {
				dialog.on('open', function () {
					// move to sui-wrap
					self.$el
					.find('.sui-wrap')
					.append(
						self.$el.find('.sui-dialog.js-plugin-modal#plugin-modal-' + project_id)
					);
					self.loadPluginDialogContent(project_id);
				});
				dialog.on('close', function () {
					// move back to hidden place
					self.$el
					.find(
						'.sui-hidden .js-plugin-box[data-project=' + project_id + '] .js-mode-modal'
					)
					.append(
						self.$el.find('.sui-dialog.js-plugin-modal#plugin-modal-' + project_id)
					);

					window.location.hash = '_';
				});
			}
		},
		loadPluginDialogContent: function (project_id) {
			var dialog = this.$el.find('#plugin-modal-' + project_id);
			var hash = dialog.data('hash');
			var self = this;

			// conditional load
			if (dialog.find('.sui-box-body.js-dialog-body').hasClass('js-is-loading')) {
				var ajaxData = {
					action: 'wdp-show-popup',
					pid: project_id,
					type: 'info',
					hash: hash,
				};

				$.post(
					window.ajaxurl,
					ajaxData,
					function (response) {

						if (response.success) {
							var content = response.data.html;
							self.onPluginDialogContentLoaded(dialog, content);

						} else {
						}
					},
					'json'
				)
				.always(function () {
					dialog
					.find('.sui-modal-content .js-dialog-body')
					.removeClass('js-is-loading');
					dialog
					.find('.sui-modal-content .js-dialog-body')
					.find('.js-dialog-loader')
					.remove();
				})
				.fail(function (xhr, statusText, exception) {
					// dialog.find('.sui-notice.sui-notice-error').show();
				});
			} else {
				self.onPluginDialogContentLoaded(dialog, null);
			}
			return false; // do think, probably loaded
		},
		onPluginDialogContentLoaded: function (dialog, content) {
			content = content || null;

			// write the content
			if (content) {
				dialog.find('.js-dialog-body').html(content);
			}

			// trigger first slider
			var slider = dialog.find('.dashui-slider'),
				navSlider = slider.find('ul.slider-nav'),
				mainSlider = dialog.find('.dashui-slider-main'),
				firstImage = mainSlider.find('li:first-child img')
			;

			var onSliderFirstImageLoaded = function () {
				navSlider.find('li:first-child').trigger('click');
			};

			if (firstImage.get(0).complete) {
				onSliderFirstImageLoaded();
			} else {
				firstImage.on('load', function () {
					onSliderFirstImageLoaded();
				});
			}

			// go to changelog when needed
			if (this.isChangelogHash()) {
				dialog
				.find('.js-dialog-body')
				.find('.sui-tabs div[data-tabs=""] div[data-index=changelog]')
				.trigger('click');
			} else {
				// force to overview tab
				dialog
				.find('.js-dialog-body')
				.find('.sui-tabs div[data-tabs=""] div[data-index=overview]')
				.trigger('click');
			}
		},
		showPluginDialogAfterInstall: function (project_id) {
			var modalID = 'plugin-modal-after-install-' + project_id,
				container = 'show-modal-' + project_id;
			SUI.openModal(
				modalID,
				container,
				undefined,
				false
			);
		},
		disableActions: function (project_id) {
			this.actionEnabled = false;
			this.$el.find('.dashui-table-plugins .plugin-row-actions a').attr('disabled', 'disabled');
			this.$el.find('.dashui-table-plugins .dashui-plugin-actions button').attr('disabled', 'disabled');
			this.$el.find('.dashui-top-plugin .sui-actions-right a').attr('disabled', 'disabled');
			this.$el.find('.dashui-new-plugin .sui-actions-right a').attr('disabled', 'disabled');
			this.$el
			.find('.sui-dialog.js-plugin-modal .sui-box-header .sui-actions-right a')
			.attr('disabled', 'disabled');
			this.$el
			.find('.sui-dialog.js-plugin-modal .sui-box-footer .sui-actions-right a')
			.attr('disabled', 'disabled');

			this.$el.find('.dashui-table-plugins .plugin-row-actions a').addClass('disabled');
			this.$el.find('.dashui-top-plugin .sui-actions-right a').addClass('disabled');
			this.$el.find('.dashui-new-plugin .sui-actions-right a').addClass('disabled');
			this.$el
			.find('.sui-dialog.js-plugin-modal .sui-box-header .sui-actions-right a')
			.addClass('disabled');
			this.$el
			.find('.sui-dialog.js-plugin-modal .sui-box-footer .sui-actions-right a')
			.addClass('disabled');

			var dropdownActions = this.$el.find('div.sui-dropdown a.js-dropdown-actions');
			if (null !== project_id) {
				dropdownActions = this.$el.find('div.sui-dropdown a.js-dropdown-actions[data-project=' + project_id + ']')
			}

			dropdownActions.addClass('sui-button-onload');

			this.$el.find('input[name=search]').attr('disabled', 'disabled');
			this.$el.find('.dashui-plugins-filter-tabs .sui-tabs-menu .sui-tab-item').css('cursor', 'not-allowed');

		},
		enableActions: function (project_id) {
			this.actionEnabled = true;
			this.$el.find('.dashui-table-plugins .plugin-row-actions a').prop("disabled", false);
			this.$el.find('.dashui-table-plugins .dashui-plugin-actions button').prop("disabled", false);
			this.$el.find('.dashui-top-plugin .sui-actions-right a').prop("disabled", false);
			this.$el.find('.dashui-new-plugin .sui-actions-right a').prop("disabled", false);
			this.$el
			.find('.sui-dialog.js-plugin-modal .sui-box-header .sui-actions-right a')
			.prop("disabled", false);
			this.$el
			.find('.sui-dialog.js-plugin-modal .sui-box-footer .sui-actions-right a')
			.prop("disabled", false);

			this.$el.find('.dashui-table-plugins .plugin-row-actions a').removeClass('disabled');
			this.$el.find('.dashui-top-plugin .sui-actions-right a').removeClass('disabled');
			this.$el.find('.dashui-new-plugin .sui-actions-right a').removeClass('disabled');
			this.$el
			.find('.sui-dialog.js-plugin-modal .sui-box-header .sui-actions-right a')
			.removeClass('disabled');
			this.$el
			.find('.sui-dialog.js-plugin-modal .sui-box-footer .sui-actions-right a')
			.removeClass('disabled');

			var dropdownActions = this.$el.find('div.sui-dropdown a.js-dropdown-actions');
			if (null !== project_id) {
				dropdownActions = this.$el.find('div.sui-dropdown a.js-dropdown-actions[data-project=' + project_id + ']')
			}

			dropdownActions.removeClass('sui-button-onload');
			this.$el.find('input[name=search]').prop("disabled", false);
			this.$el.find('.dashui-plugins-filter-tabs .sui-tabs-menu .sui-tab-item').css('cursor', 'pointer');
		},
		processHash: function () {
			var hash = location.hash;
			hash = hash.replace(/^#/, '');
			hash = hash.split('=');

			// modal show
			if (hash[0] && hash[0] === 'pid' && hash[1]) {
				this.showPluginDialog(hash[1]);
			} else if (hash[0] && hash[0] === 'install-pid' && hash[1]) {
				this.directInstall(hash[1]);
			}
		},
		isChangelogHash: function () {
			var hash = location.hash;
			return hash.indexOf('changelog') !== -1;
		},
		isUpdateHash: function () {
			var hash = location.hash;
			hash = hash.replace(/^#/, '');
			hash = hash.split('=');

			if (hash[0] && hash[0] === 'pid' && hash[1] && 'changelog' === hash[2]) {
				return true;
			}
			return false;
		},
		refreshUpdateCount: function (updated) {
			var countOnWPMenu = $('.wdev-update-count'),
				countOnTab = this.$el.find('.wdev-update-tab'),
				allTab = $('.wdev-all-tab');

			if (countOnTab.length) {
				var count = countOnTab.data('count');
				count = +count;
				var prevCount = count;
				if (count > 0) {
					count = count - updated;
				}

				if (count >= 0) {
					countOnTab.data('count', count);
					countOnWPMenu.data('count', count);

					if (countOnWPMenu.length) {
						countOnWPMenu.find('span.countval').text(count);
						countOnWPMenu.removeClass('count-' + prevCount);
						countOnWPMenu.addClass('count-' + count);
					}

					countOnTab.find('span.sui-tag').text(count);
					if (!count) {
						countOnTab.parent('li').remove();
						allTab.parent('li').addClass('current');
						this.filterPlugins('all', true);
					}
				}
			}
		},
		clearHash: function () {
			window.location.hash = '_';
		},

		/**
		 * Install a plugin when asked from URL.
		 *
		 * @param {int} pid Project ID.
		 *
		 * @since 4.11.3
		 */
		directInstall: function (pid) {
			// Get the row.
			let div = $("#project-row-" + pid);
			// Clean hash from URL.
			this.clearHash();
			// Only if it's valid.
			if (div.length > 0) {
				// Scroll to the row.
				$('html, body').animate({
					scrollTop: div.offset().top
				}, 'slow');
				// Process install.
				this.install({
					project: pid,
					hash: $('#project-install-hash').val(),
					action: 'project-install'
				});
			}
		}
	});

	// A really lightweight plugin wrapper around the constructor,
	// preventing against multiple instantiations
	$.fn[pluginName] = function (options) {
		return this.each(function () {
			if (!$.data(this, pluginName)) {
				$.data(this, pluginName, new wpmudevDashboardAdminPluginsPage(this, options));
			}
		});
	};
})(jQuery, window, document);
