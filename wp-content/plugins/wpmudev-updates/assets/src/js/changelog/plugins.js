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
		const plugins = window.wpmudevDashboard.plugins || []

		// Dynamic elements.
		let contentEl = $('#wpmudev-dashboard-changelog-content')
		let loaderEl = $('#wpmudev-dashboard-changelog-loader')

		// Setup changelog for upgrade page screen.
		if (plugins.length > 0) {
			plugins.forEach(function (plugin) {
				setupChangelogLink(plugin)
			})
		}

		// Open changelog button click.
		$('.wpmudev-dashboard-changelog-btn').on('click', openChangelog)

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
				// Remove default link.
				link.attr('href', '#')
				// Remove default classes and add our custom class.
				link.removeClass().addClass('wpmudev-dashboard-changelog-btn')
				// Set plugin ID.
				link.attr('data-pid', plugin.pid)
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

		/**
		 * Open changelog modal and set content.
		 *
		 * @param event Event.
		 *
		 * @since 4.11
		 */
		function openChangelog(event) {
			event.preventDefault()

			let button = $(this)
			let pluginId = button.attr('data-pid')
			let pluginName = button.attr('title')

			// Set title.
			$('#wpmudev-dashboard-changelog-title').html(pluginName)

			// Open modal.
			openChangeLogModal(button)

			// Hide existing content.
			contentEl.hide()
			// Show loader.
			loaderEl.show()

			// Get changelog.
			getChangelog(pluginId)
		}

		/**
		 * Get changelog content using ajax.
		 *
		 * @param {int} pid Project ID.
		 *
		 * @since 4.11
		 */
		function getChangelog(pid) {
			// Prepare form data.
			let data = {
				hash: wpmudevDashboard.nonce,
				pid: pid,
				type: 'updates_changelog',
				action: 'wdp-show-popup',
			}

			// Send ajax request.
			$.get(window.ajaxurl, data).done(function (response) {
				// Update the stats.
				if (true === response.success && response.data) {
					// Update modal content.
					showChangelog(response.data)
				}
			})
		}

		/**
		 * Set changelog modal content.
		 *
		 * @param {int} data Project ID.
		 *
		 * @since 4.11
		 */
		function showChangelog(data) {
			if (data.html) {
				// Hide loader.
				loaderEl.hide()
				// Show content.
				contentEl.html(data.html)
				contentEl.show()
			}
		}

		/**
		 * Open the changelog modal.
		 *
		 * @since 4.11
		 */
		function openChangeLogModal(button) {
			SUI.openModal(
				'wpmudev-dashboard-changelog',
				button,
				'wpmudev-dashboard-changelog-close',
				true,
				true
			)
		}
	}

	// Initialize on page load.
	window.addEventListener('load', () => initChangelog())
})(jQuery, window, document)
