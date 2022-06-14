/* global jQuery */
(function ($) {
	'use strict'

	/**
	 * Plugin's data settings management.
	 *
	 * @since 4.11.4
	 */
	$(window).on('load', function () {
		let confirmButton = $('#wpmudev-reset-settings-confirm-button')

		// Hide/Show fields when redirect to value changes.
		confirmButton.on('click', function () {
			// sui-button-onload-text
			resetSettings()
		})

		/**
		 * Reset plugin settings using ajax.
		 *
		 * @since 4.11.4
		 */
		let resetSettings = function () {
			confirmButton.addClass('sui-button-onload-text')

			let ajaxData = {
				action: 'wdp-reset-settings',
				hash: confirmButton.data('hash'),
			}

			$.post(
				window.ajaxurl,
				ajaxData,
				function (response) {
					if (response.data && response.data.redirect) {
						// Redirect to success url.
						window.location.href = response.data.redirect
					} else {
						// Reload as a fallback.
						window.location.reload()
					}
				},
				'json'
			).fail(function () {
				window.location.reload()
			})
		}
	})
})(jQuery)
