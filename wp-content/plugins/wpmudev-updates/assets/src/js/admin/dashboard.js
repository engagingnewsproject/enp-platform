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
	var pluginName = "wpmudevDashboardAdminDashboardPage";

	// The actual plugin constructor
	function wpmudevDashboardAdminDashboardPage(element, options) {
		this.element = element;
		this.$el = $(this.element);
		this.pluginList = [];
		this.upgradeNonce = false;
		this.redirectHash = false;
		this.upgradeQueName = 'DashUpgradeQue';
		this.isFailed = false;
		this.upgradedPlugins = [];
		this.init();
	}

	// Avoid Plugin.prototype conflicts
	$.extend(wpmudevDashboardAdminDashboardPage.prototype, {
		init: function () {
			this.attachEvents();
			this.initUpgrading();
		},

		attachEvents: function () {
			this.$el.on('click', '.sui-notice-top .sui-notice-dismiss', function (e) {
				e.preventDefault();
				$(this).closest('.sui-notice-top').stop().slideUp('slow');
				return false;
			});

			this.$el.on('click', '.dashui-expired-box .dashui-expired-box__refresh', function (e) {
				$(this).addClass('sui-button-onload-text');
			});
		},

		initUpgrading: function () {
			var self = this;
			// sync plugins must exist
			if (!this.$el.find('.js-sync-plugins').length) {
				return false;
			}

			var pluginListEl = this.$el.find('ul.js-sync-plugin-list');

			pluginListEl.find('li.js-upgrading').each(function () {
				var data = $(this).data();
				if (!self.upgradeNonce) {
					self.upgradeNonce = data.hash;
				}
				if (!self.redirectHash) {
					self.redirectHash = data.redirecth;
				}
				self.pluginList.push(data.project);
			});

			this.upgradePlugins();
		},

		upgradePlugins: function () {
			var self = this;
			var i;
			for (i = 0; i < this.pluginList.length; i++) {
				var project_id = self.pluginList[i];
				this.addUpgradeAjaxQue(project_id);
			}
		},

		addUpgradeAjaxQue: function (project_id) {
			var self = this;
			$.ajaxq(this.upgradeQueName, {
				type: "POST",
				url: window.ajaxurl,
				data: {
					action: 'wdp-project-upgrade-free',
					hash: self.upgradeNonce,
					pid: project_id,
				},
				success: function (response) {
					if (response.success) {
						self.$el.find('ul.js-sync-plugin-list li.js-upgrading[data-project=' + project_id + ']').addClass('sui-hidden');
						self.$el.find('ul.js-sync-plugin-list li.js-upgraded[data-project=' + project_id + ']').removeClass('sui-hidden');
						self.upgradedPlugins.push(project_id);
					} else {
						if (response.data && response.data.message) {
							console.log(response.data.message);
						}
						self.$el.find('ul.js-sync-plugin-list li.js-upgrading[data-project=' + project_id + ']').addClass('sui-hidden');
						self.$el.find('ul.js-sync-plugin-list li.js-failed-upgrading[data-project=' + project_id + ']').removeClass('sui-hidden');
						self.isFailed = true;
					}
				},
				error: function (error) {
					self.$el.find('ul.js-sync-plugin-list li.js-upgrading[data-project=' + project_id + ']').addClass('sui-hidden');
					self.$el.find('ul.js-sync-plugin-list li.js-failed-upgrading[data-project=' + project_id + ']').removeClass('sui-hidden');
					self.isFailed = true;
				},
				complete: function () {
					if (!$.ajaxq.isRunning(self.upgradeQueName)) {
						self.redirectToDash();
					}
				}
			})
		},

		redirectToDash: function () {
			var self = this;
			$.ajaxq('redirectToDash', {
				type: "POST",
				url: window.ajaxurl,
				data: {
					action: 'wdp-login-success',
					pid: self.upgradedPlugins,
					hash: self.redirectHash
				},
				success: function (response) {
					if (response.data.redirect) {
						window.location.href = response.data.redirect;
					}
				},
			});
		}
	});

	// A really lightweight plugin wrapper around the constructor,
	// preventing against multiple instantiations
	$.fn[pluginName] = function (options) {
		return this.each(function () {
			if (!$.data(this, pluginName)) {
				$.data(this, pluginName, new wpmudevDashboardAdminDashboardPage(this, options));
			}
		});
	};

})(jQuery, window, document);
