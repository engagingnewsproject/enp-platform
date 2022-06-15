// noinspection JSUnusedLocalSymbols
;(function ($, window, document, undefined) {
	'use strict'

	/**
	 * Initialize the changelog actions.
	 *
	 * @since 4.11.0
	 */
	function initChangelog() {
		// Disabled updates.
		const plugins = window?.wpmudevDashboard?.plugins

		// Setup changelog for upgrade page screen.
		if (typeof wpmudevDashboard !== 'undefined' && plugins.length > 0) {
			plugins.forEach(function (plugin) {
				setupChangelogLink(plugin)
			})
		}

		/**
		 * Setup changelog modal in updates page.
		 *
		 * Replace default changelog link with our custom
		 * modal actions.
		 *
		 * @param {object} plugin Plugin data
		 *
		 * @since 4.11.0
		 */
		function setupChangelogLink(plugin) {
			// Get the plugin checkbox.
			let checkbox = $('input:checkbox[value="' + plugin.file + '"]')
			// Get the changelog link.
			let link = checkbox.closest('tr').find('td p a.thickbox')
			if (link.length > 0) {
				// Set plugin title.
				link.attr('title', plugin.name)
				// Append notice.
				if (plugin.disabled && plugin.action_html.length > 0) {
					checkbox
						.closest('tr')
						.find('td')
						.last()
						.append(plugin.action_html)
				}
			}

			// Now disable the checkbox.
			if (plugin.disabled) {
				checkbox
					.prop('disabled', true)
					.prop('checked', false)
					.attr('name', '')
					.addClass('disabled')
			}
		}
	}

	// Initialize on page load.
	window.addEventListener('load', () => initChangelog())
})(jQuery, window, document)
