/* global wpmudevDashboard, jQuery */
;(function ($, window, document, undefined) {
	'use strict'

	/**
	 * Initialize the changelog actions.
	 *
	 * @since 4.11.0
	 */
	function initUpsellNotice() {
		// Open changelog button click.
		$('#wpmudev-dashboard-upsell-notice-more').on('click', openUpsellModal)
		// Dismiss notice.
		$('#wpmudev-dashboard-upsell-notice-dismiss').on('click', dismissHideModal)
		// Act on notice dismiss.
		$('#wpmudev-dashboard-upsell-notice .notice-dismiss').on('click', dismissModal)
		// Dismiss notice.
		$('#wpmudev-dashboard-upsell-notice-extend').on('click', extendHideModal)

		/**
		 * Open upsell detail modal.
		 *
		 * @param event Event.
		 *
		 * @since 4.11.15
		 */
		function openUpsellModal(event) {
			event.preventDefault()

			SUI.openModal(
				'wpmudev-dashboard-upsell',
				$(this),
				'wpmudev-dashboard-upsell-close',
				true,
				true
			)
		}

		/**
		 * Dismiss upsell details modal and show again in a week.
		 *
		 * @since 4.11.15
		 */
		function extendHideModal() {
			// Send ajax request.
			$.post(window.ajaxurl, {
				hash: wpmudevDashboard.extend_nonce,
				action: 'wdp-extend-upsell',
			})

			// Hide notice.
			$('#wpmudev-dashboard-upsell-notice').fadeOut();
		}

		/**
		 * Dismiss upsell details modal.
		 *
		 * @since 4.11.15
		 */
		function dismissModal() {
			// Send ajax request.
			$.post(window.ajaxurl, {
				hash: wpmudevDashboard.dismiss_nonce,
				action: 'wdp-dismiss-upsell',
			})
		}

		/**
		 * Dismiss upsell details modal.
		 *
		 * @since 4.11.15
		 */
		function dismissHideModal() {
			// Send ajax request.
			dismissModal();

			// Hide notice.
			$('#wpmudev-dashboard-upsell-notice').fadeOut();
		}
	}

	// Initialize on page load.
	window.addEventListener('load', () => initUpsellNotice())
})(jQuery, window, document)