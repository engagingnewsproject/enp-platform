;// noinspection JSUnusedLocalSymbols
(function($, window, document, undefined) {

	'use strict'

	// Create the defaults once
	var pluginName = 'wpmudevDashboardAdminToolsPage'

	// The actual plugin constructor
	function wpmudevDashboardAdminToolsPage(element, options) {
		this.element = element
		this.$el = $(this.element)
		this.wpMediaFrames = {}
		this.initDashIconPicker()
		this.init()
	}

	// Avoid Plugin.prototype conflicts
	$.extend(wpmudevDashboardAdminToolsPage.prototype, {
		init: function() {
			this.attachEvents()
			$(window).trigger('hashchange')
			this.initBrandingMediaUploader()
			this.initBrandingLinkLogo()
			this.showAlertNotice()
			this.initSitesActions()
			this.overflowTabsShowArrowRight()
		},
		attachEvents: function() {
			var self = this
			this.$el.on('click', '.sui-tabs div[data-tabs=""] div', function() {
				var tabWrapper = $(this).closest('.sui-tabs')
				var index = $(this).data('index')

				tabWrapper.find('div[data-tabs=""] div').removeClass('active')
				$(this).addClass('active')

				tabWrapper.find('div[data-panes=""] div').removeClass('active')
				tabWrapper.find('div[data-panes=""] div[data-index="' + index + '"]').addClass('active')
			})

			$(window).on('hashchange', function() {
				self.processHash()
			})

			this.$el.on('click', '.sui-notice-top .sui-notice-dismiss', function(e) {
				e.preventDefault()
				$(this).closest('.sui-notice-top').stop().slideUp('slow')
				return false
			})

			this.$el.on('submit', 'form', function(e) {
				$(this).find('button[type="submit"]').addClass('sui-button-onload')

				return true
			})

			// On plugin tab change.
			this.$el.find('.tab-content-wpmudev-plugin-item').on('click', function() {
				$('#wpmudev-labels-config-selected').val($(this).data('pid'));
			})
		},

		/**
		 * Setup media uploader for custom image.
		 *
		 * @since 4.0
		 */
		initBrandingMediaUploader: function() {
			var self = this

			this.$el.find('.wp-browse-media').each(function() {
				var mediaButton = $(this)
				mediaButton.on('click', function(event) {
					event.preventDefault()

					var wrapperId = mediaButton.data('upload-wrapper-id')
					var wrapper = self.$el.find('#' + wrapperId)

					// If the media frame already exists, reopen it.
					if (self.wpMediaFrames.hasOwnProperty(wrapperId)) {
						self.wpMediaFrames[wrapperId].open()
						return false
					}

					// Create a new media frame
					self.wpMediaFrames[wrapperId] = wp.media({
						title: mediaButton.data('frame-title'),
						button: {
							text: mediaButton.data('button-text')
						},
						multiple: false
					})

					// When an image is selected in the media frame...
					self.wpMediaFrames[wrapperId].on('select', function() {

						// Get media attachment details from the frame state
						var attachment = self.wpMediaFrames[wrapperId].state().get('selection').first().toJSON(),
							input = self.$el.find('#' + mediaButton.data('input-id')),
							inputId = self.$el.find('#' + mediaButton.data('input-id-container')),
							preview = self.$el.find('#' + mediaButton.data('preview-id')),
							text = self.$el.find('#' + mediaButton.data('text-id'))


						// Send the attachment URL to our custom image input field.
						preview.css('background-image', 'url(' + attachment.url + ')')
						// Send the attachment url to our input
						input.val(attachment.url)
						inputId.val(attachment.id)
						wrapper.addClass('sui-has_file')
						text.html(attachment.url)
					})

					self.wpMediaFrames[wrapperId].on('open', function() {
						if (self.$el.hasClass('wpmud')) {
							self.$el.removeClass('wpmud')
						}
					})

					self.wpMediaFrames[wrapperId].on('close', function() {
						if (!self.$el.hasClass('wpmud')) {
							self.$el.addClass('wpmud')
						}
					})

					// Finally, open the modal on click
					self.wpMediaFrames[wrapperId].open()
					return false
				})
			})

			this.$el.find('.js-clear-image').each(function() {
				var mediaButton = self.$el.find('#' + $(this).data('media-button-id'))
				$(this).on('click', function(event) {
					event.preventDefault()
					var input = self.$el.find('#' + mediaButton.data('input-id')),
						inputId = self.$el.find('#' + mediaButton.data('input-id-container')),
						preview = self.$el.find('#' + mediaButton.data('preview-id')),
						wrapper = self.$el.find('#' + mediaButton.data('upload-wrapper-id')),
						text = self.$el.find('#' + mediaButton.data('text-id'))

					// Send the attachment URL to our custom image input field.
					preview.css('background-image', 'url()')
					// Send the attachment url to our input
					input.val('')
					inputId.val('')
					wrapper.removeClass('sui-has_file')
					text.html('')
					return false
				})
			})
		},

		/**
		 * Handle custom logo link input.
		 *
		 * Custom link can be anything but in a proper url format.
		 *
		 * @since 4.11.1
		 */
		initBrandingLinkLogo: function() {
			let self = this,
				// Save button.
				$submitbtn = $('#save_changes')

			this.$el.find('.wp-link-media').each(function() {
				var $input = $(this),
					// Clear buttons.
					$clearbtn = self.$el.find('#' + $input.data('clear-btn-id')),
					// Preview wrapper.
					$preview = self.$el.find('#' + $input.data('preview-id')),
					// Field wrapper.
					$formfield = self.$el.find('#' + $input.data('form-field-id')),
					// Field wrapper.
					$type = self.$el.find('[name="' + $input.data('tab-type-name') + '"]')

				$input.on('input linkUpdate', function() {
					let url = $input.val()

					// Remove form errors.
					$formfield.removeClass('sui-form-field-error')
					// Enable submit.
					$submitbtn.prop('disabled', false)

					// If some value is found, show clear button.
					url.length > 0 ? $clearbtn.removeClass('hidden-clear-link') : $clearbtn.addClass('hidden-clear-link')

					// Check if it is a valid link.
					if (self.isValidLink(url)) {
						// Set background image.
						$preview.css('background-image', 'url(' + url + ')').addClass('has-logo-image')
					} else {
						// Remove background image.
						$preview.css('background-image', 'url()').removeClass('has-logo-image')
						// Set error.
						if (url.length > 0) {
							// Show errors.
							$formfield.addClass('sui-form-field-error')
							// Disable submit.
							$submitbtn.prop('disabled', true)
						}
					}

					return false
				})

				// On clear button click.
				$clearbtn.on('click', function() {
					// Link input.
					$input.val('').trigger('linkUpdate')
				})

				// If tab changed.
				if ($type.length > 0) {
					$type.on('change', function() {
						// Link input.
						if ('link' === $(this).val()) {
							$input.trigger('linkUpdate')
						} else {
							if (!self.isValidLink($input.val())) {
								// Clear input and error.
								$input.val('').trigger('linkUpdate')
							}
						}
					})
				}
			})
		},

		/**
		 * Initialize dash icons selector.
		 *
		 * @since 4.11.1
		 */
		initDashIconPicker: function() {
			var self = this,
				// Main wrappers.
				$wrapper = this.$el.find('.wpmudev-dashicon-picker')

			// On each dash icons set.
			$wrapper.each(function() {
				// Main wrapper.
				var $iconWrapper = $(this),
					// Get search input and icon input.
					$search = $('#' + $(this).data('search-id')),
					$input = $('#' + $(this).data('input-id'))

				// On icon click.
				$(this).find('.wpmudev-dashicons').on('click', function() {
					// Make all icons not selected.
					$iconWrapper.find('.wpmudev-dashicons.active').removeClass('active')
					// Select current icon.
					$(this).addClass('active')
					// Set value to the hidden field.
					$input.val($(this).data('icon'))
				})

				// On search input change.
				$search.on('input', function() {
					var keyword = $(this).val(),
						$matches = $('[data-icon*="' + keyword + '"]', $iconWrapper),
						$elements = $('.wpmudev-dashicon-picker-group, .wpmudev-dashicons', $iconWrapper)

					// If no search input, show all.
					if ('' === keyword) {
						$elements.show()
						return
					}

					// Hide all.
					$elements.hide()
					// Go through each items.
					$matches.each(function() {
						// Show icon.
						$(this).show()
						// Show the group.
						$(this).closest('.wpmudev-dashicon-picker-group').show()
					})
				})
			})
		},

		/**
		 * Initialize sites add action.
		 *
		 * @since 4.11.1
		 */
		initSitesActions: function() {
			var self = this
			// Add new sites.
			this.$el.on('change', '#wpmudev-labels-subsites-select', function(e) {
				var site = $(this).val()
				// Add values.
				$('<input>', {
					type: 'hidden',
					name: 'labels_subsites[]',
					value: site
				}).appendTo('#wpmudev-labels-subsites-content')
				// Submit form.
				// We need to clock the button to save.
				self.$el.find('#save_changes').click()
			})

			// Add loading animation.
			this.$el.on('click', '.js-remove-whitelabel-site', function() {
				$(this).addClass('sui-button-onload')
			})
		},

		/**
		 * Check if the link is in url format.
		 *
		 * @param {string} link Link
		 *
		 * @since 4.11
		 *
		 * @return {boolean}
		 */
		isValidLink: function(link) {
			return /(http|https):\/\/(\w+:{0,1}\w*)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%!\-\/]))?/.test(link)
		},
		processHash: function() {
			var hash = location.hash
			hash = hash.replace(/^#/, '')

			this.$el.find('.sui-vertical-tabs li.sui-vertical-tab').removeClass('current')
			this.$el.find('.js-sidenav-content').hide()

			switch (hash) {
				case 'whitelabel':
					this.$el.find('.sui-vertical-tabs li.sui-vertical-tab a[href$="#whitelabel"]').closest('li.sui-vertical-tab').addClass('current')
					this.$el.find('.js-sidenav-content#whitelabel').show()
					this.$el.find('.sui-sidenav select.sui-mobile-nav').val('#whitelabel')
					this.$el.find('.sui-sidenav select.sui-mobile-nav').trigger('change')
					break
				default:
					this.$el.find('.sui-vertical-tabs li.sui-vertical-tab a[href$="#analytics"]').closest('li.sui-vertical-tab').addClass('current')
					this.$el.find('.js-sidenav-content#analytics').show()
					this.$el.find('.sui-sidenav select.sui-mobile-nav').val('#analytics')
					this.$el.find('.sui-sidenav select.sui-mobile-nav').trigger('change')
					break
			}
		},
		showAlertNotice: function() {
			var container = $('.sui-tools-notice-alert'),
				noticeID = '',
				message = '',
				noticeOptions = {}

			if (!container.length) {
				return
			}
			noticeOptions.dismiss = {}
			noticeOptions.autoclose = {}
			noticeOptions.type = container.data('notice-type')
			noticeOptions.dismiss.show = container.data('show-dismiss')
			noticeOptions.autoclose.show = false
			message = container.data('notice-msg')
			noticeID = container.attr('id')

			if ('success' === noticeOptions.type) {
				noticeOptions.icon = 'check-tick'
			}

			SUI.openNotice(noticeID, message, noticeOptions)
		},

		/**
		 * Shows right arrow to scroll tab panes in the overflown tab menu
		 *
		 * @since 4.11.6
		 *
		 * @return {void}
		 */
		overflowTabsShowArrowRight: function () {
			this.$el.on('click', '.sui-side-tabs .sui-tab-item', function (e) {
				let $parentTabsElement = $(this).closest('.sui-side-tabs')
				let $tabsElements = $parentTabsElement.find('.sui-tabs-overflow')

				$tabsElements.each(function (index, element) {
					SUI.tabsOverflow($(element))
				})
			})
		}
	})

	// A really lightweight plugin wrapper around the constructor,
	// preventing against multiple instantiations
	$.fn[pluginName] = function(options) {
		return this.each(function() {
			if (!$.data(this, pluginName)) {
				$.data(this, pluginName, new wpmudevDashboardAdminToolsPage(this, options))
			}
		})
	}

})(jQuery, window, document)
