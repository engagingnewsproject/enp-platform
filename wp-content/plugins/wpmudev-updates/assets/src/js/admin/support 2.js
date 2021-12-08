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
	var pluginName = "wpmudevDashboardAdminSupportPage";

	// The actual plugin constructor
	function wpmudevDashboardAdminSupportPage(element, options) {
		this.element = element;
		this.$el = $(this.element);
		this.secDialog = null;
		this.init();
	}

	// Avoid Plugin.prototype conflicts
	$.extend(wpmudevDashboardAdminSupportPage.prototype, {
		init: function () {
			this.attachEvents();
			this.showAlertNotice();
			$(window).trigger('hashchange');
			this.$el.find('.sui-tabs-menu.js-filter-ticket .sui-tab-item.active').trigger('click');
		},
		attachEvents: function () {
			var self = this;
			this.$el.on('click', '.sui-tabs div[data-tabs=""] div', function () {
				var tabWrapper = $(this).closest('.sui-tabs');
				var index = $(this).data('index');

				tabWrapper.find('div[data-tabs=""] div').removeClass('active');
				$(this).addClass('active');

				tabWrapper.find('div[data-panes=""] div').removeClass('active');
				tabWrapper.find('div[data-panes=""] div[data-index="' + index + '"]').addClass('active');
			});

			this.$el.on('click', '.js-modal-security', function (e) {
				self.showSupportAccessInfoModal();
			});

			$(window).on('hashchange', function () {
				self.processHash();
			});

			this.$el.find('.sui-tabs-menu.js-filter-ticket .sui-tab-item').click(function () {
				$(this).closest('.sui-tabs-menu').find('.sui-tab-item').removeClass('active');
				$(this).addClass('active');
				self.filterTickets($(this).data('filter'));
			});

			this.$el.on('click', '.sui-notice-top .sui-notice-dismiss', function (e) {
				e.preventDefault();
				$(this).closest('.sui-notice-top').stop().slideUp('slow');
			});

			this.$el.on('submit', 'form', function (e) {
				$(this).find('button[type="submit"]').addClass('sui-button-onload');
			});

			this.$el.on('click', '.js-loading-link', function (e) {
				$(this).addClass('sui-button-onload');
			});

			this.$el.on('click', '#close-sec-det', function () {
				SUI.closeModal();
			});

			this.$el.on('change', '.sui-mobile-nav', function (e) {
				self.mobileNav( $(this).val() );
			});
		},
		processHash: function (mob = true) {
			var hash = location.hash;
			hash = hash.replace(/^#/, '');

			this.$el.find('.sui-vertical-tabs li.sui-vertical-tab').removeClass('current');
			this.$el.find('.js-sidenav-content').hide();

			switch (hash) {
				case 'access':
					this.$el.find('.sui-vertical-tabs li.sui-vertical-tab a[href="#access"]').closest('li.sui-vertical-tab').addClass('current');
					this.$el.find('.js-sidenav-content#access').show();
					if (mob) {
						this.$el.find('.sui-sidenav select.sui-mobile-nav').val('#access');
						this.$el.find('.sui-sidenav select.sui-mobile-nav').trigger('change');
					}
					break;
				case 'system':
					this.$el.find('.sui-vertical-tabs li.sui-vertical-tab a[href="#system"]').closest('li.sui-vertical-tab').addClass('current');
					this.$el.find('.js-sidenav-content#system').show();
					if (mob) {
						this.$el.find('.sui-sidenav select.sui-mobile-nav').val('#system');
						this.$el.find('.sui-sidenav select.sui-mobile-nav').trigger('change');
					}
					break;
				default:
					this.$el.find('.sui-vertical-tabs li.sui-vertical-tab a[href="#ticket"]').closest('li.sui-vertical-tab').addClass('current');
					this.$el.find('.js-sidenav-content#ticket').show();
					if (mob) {
						this.$el.find('.sui-sidenav select.sui-mobile-nav').val('#ticket');
						this.$el.find('.sui-sidenav select.sui-mobile-nav').trigger('change');
					}
					break;
			}
		},
		mobileNav: function (tab) {
			var hash = location.hash;
			if (hash !== tab) {
				location.hash = tab;
			}
		},
		filterTickets: function (filter) {
			this.$el.find('.js-filter-ticket-content').hide();
			this.$el.find('.js-filter-ticket-content[data-filter=' + filter + ']').show();
		},
		showAlertNotice: function () {
			var container = $('.sui-support-notice-alert'),
				noticeID = '',
				message = '',
				noticeOptions = {};

			if (!container.length) {
				return;
			}
			noticeOptions.dismiss = {};
			noticeOptions.autoclose = {};
			noticeOptions.type = container.data('notice-type');
			;
			noticeOptions.dismiss.show = container.data('show-dismiss');
			noticeOptions.autoclose.show = false;
			message = container.data('notice-msg');
			noticeID = container.attr('id');

			if ('success' === noticeOptions.type) {
				noticeOptions.icon = 'check-tick';
			}

			SUI.openNotice(noticeID, message, noticeOptions);

		},
		showSupportAccessInfoModal: function () {
			var self = this,
				modalID = 'security-details',
				container = 'modal-security';

			SUI.openModal(
				modalID,
				container,
				undefined,
				true
			);
		},
	});

	// A really lightweight plugin wrapper around the constructor,
	// preventing against multiple instantiations
	$.fn[pluginName] = function (options) {
		return this.each(function () {
			if (!$.data(this, pluginName)) {
				$.data(this, pluginName, new wpmudevDashboardAdminSupportPage(this, options));
			}
		});
	};

})(jQuery, window, document);
