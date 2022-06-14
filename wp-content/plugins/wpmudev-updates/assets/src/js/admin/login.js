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
	var pluginName = "wpmudevDashboardAdminLoginPage";

	// The actual plugin constructor
	function wpmudevDashboardAdminLoginPage(element, options) {
		this.element            = element;
		this.$el                = $(this.element);
		this.animSyncInterval   = null;
		this.numAnimateSync     = 0;
		this.currentAnimateSync = 1;
		this.emailInput         = this.$el.find('.js-wpmudev-login-form').find('#dashboard-email');
		this.passwordInput      = this.$el.find('.js-wpmudev-login-form').find('#dashboard-password');
		this.init();
	}

	// Avoid Plugin.prototype conflicts
	$.extend(wpmudevDashboardAdminLoginPage.prototype, {
		init: function () {
			this.initLoginButtonState();
			this.attachEvents();
			this.animateSync();
			this.initiateHubSync();
			this.storeSingleSignOnStatus();
		},
		initLoginButtonState: function () {
			var self = this;
			this.$el.find('.js-login-form-submit-button').removeAttr('disabled');
			if (this.$el.find('.js-login-form-submit-button').length) {
				var email    = this.emailInput.val();
				var password = this.passwordInput.val();

				email    = $.trim(email);
				password = $.trim(password);
				if (email === '' || !self.isValidEmail(email) || password === '') {
					this.$el.find('.js-login-form-submit-button').attr('disabled', 'disabled');
				}
			}
		},
		attachEvents: function () {
			var self = this;

			// Handle team selection.
			this.$el.on('change', 'input[type=radio][name="team_id"]', function (e) {
				if (this.value === '') {
					$('#dashui-team-select-submit').prop('disabled', true)
				} else {
					$('#dashui-team-select-submit').prop('disabled', false)
				}
			});

			// Handle team select form submit.
			this.$el.on('click', '#dashui-team-select-submit', function (e) {
				$(this).addClass('sui-button-onload')
			});

			this.$el.on('click', '.sui-tabs div[data-tabs=""] div', function () {
				var tabWrapper = $(this).closest('.sui-tabs');
				var index      = $(this).data('index');

				tabWrapper.find('div[data-tabs=""] div').removeClass('active');
				$(this).addClass('active');

				tabWrapper.find('div[data-panes=""] div').removeClass('active');
				tabWrapper.find('div[data-panes=""] div[data-index="' + index + '"]').addClass('active');
			});

			// on Submit Login
			this.$el.on('submit', 'form.js-wpmudev-login-form', function () {
				$(this).find('#dashboard-email').trigger('keyup');
				$(this).find('#dashboard-password').trigger('keyup');
				$(this).find('.js-login-form-submit-button').trigger('sso:save');

				$(this).find('.js-login-form-submit-button').addClass('sui-button-onload');

				var email    = self.emailInput.val();
				var password = self.passwordInput.val();

				email    = $.trim(email);
				password = $.trim(password);


				if (email === '' || !self.isValidEmail(email) || password === '') {
					$(this).find('.js-login-form-submit-button').removeClass('sui-button-onload');
					$(this).find('.js-login-form-submit-button').attr('disabled', 'disabled');
					return false;
				}

				return true;
			});

			this.$el.on('keyup', 'form.js-wpmudev-login-form #dashboard-email', function () {
				$(this).closest('.sui-form-field').removeClass('sui-form-field-error');
				$(this).closest('.sui-form-field').find('.js-required-message').addClass('sui-hidden');
				$(this).closest('.sui-form-field').find('.js-valid-email-message').addClass('sui-hidden');
				var email = $.trim($(this).val());
				if (email === '') {
					$(this).closest('.sui-form-field').addClass('sui-form-field-error');
					$(this).closest('.sui-form-field').find('.js-required-message').removeClass('sui-hidden');
				} else if (!self.isValidEmail(email)) {
					$(this).closest('.sui-form-field').addClass('sui-form-field-error');
					$(this).closest('.sui-form-field').find('.js-valid-email-message').removeClass('sui-hidden');
				}

				self.initLoginButtonState();
			});

			this.$el.on('keyup', 'form.js-wpmudev-login-form #dashboard-password', function () {
				$(this).closest('.sui-form-field').removeClass('sui-form-field-error');
				$(this).closest('.sui-form-field').find('.js-required-message').addClass('sui-hidden');
				var password = $.trim($(this).val());
				if (password === '') {
					$(this).closest('.sui-form-field').addClass('sui-form-field-error');
					$(this).closest('.sui-form-field').find('.js-required-message').removeClass('sui-hidden');
				}

				self.initLoginButtonState();
			});

		},
		isValidEmail: function (email) {
			//simple email validation contains @str
			var regex = /.+\@.+/g;
			var match = email.match(regex);
			return match && match.length;
		},
		animateSync: function () {
			var self            = this;
			var anims           = this.$el.find('.animate-sync');
			this.numAnimateSync = anims.length;

			this.animSyncInterval = setInterval(function () {
				var nextAnimateItem = self.currentAnimateSync + 1;
				if (nextAnimateItem > self.numAnimateSync) {
					nextAnimateItem = 1;
				}
				self.$el.find('.animate-sync').addClass('sui-hidden');
				self.$el.find('.animate-sync.animate-' + nextAnimateItem).removeClass('sui-hidden');
				self.currentAnimateSync = nextAnimateItem;

			}, 5000); // 5 seconds
		},
		initiateHubSync: function () {
			var self    = this;
			var wrapper = this.$el.find('.js-login-sync');

			if (wrapper.length) {
				var data     = $(wrapper).data();
				var ajaxData = {
					action: 'wdp-hub-sync',
					hash: data.hash,
					key: data.key
				};

				$.post(
					window.ajaxurl,
					ajaxData,
					function (response) {
						if (response.data && response.data.redirect) {
							window.location.href = response.data.redirect;
						} else {
							window.location.href = data.dashurl;
						}
					},
					'json'
				).always(function () {
					// clearInterval(self.animSyncInterval);
				}).fail(function () {
					window.location.href = data.dashurl;
				});
			}
		},
		storeSingleSignOnStatus: function() {
			var self    = this;
			var wrapper = this.$el.find('#enable-sso');

			this.$el.on('sso:save', '.js-login-form-submit-button', function (e) {
				e.preventDefault();
				$(this).addClass('sui-button-onload');
				var data = {};
				data.action = 'wdp-sso-status';
				data.hash = $(wrapper).data('nonce');
				data.sso = 0;
				data.ssoUserId = $(wrapper).data('userid');
				if ($(wrapper).is(':checked')) {
					data.sso = $(wrapper).val();
				}

				$.post(
					window.ajaxurl,
					data,
					null,
					'json'
				);
			});

		},

	});

	// A really lightweight plugin wrapper around the constructor,
	// preventing against multiple instantiations
	$.fn[pluginName] = function (options) {
		return this.each(function () {
			if (!$.data(this, pluginName)) {
				$.data(this, pluginName, new wpmudevDashboardAdminLoginPage(this, options));
			}
		});
	};

})(jQuery, window, document);
