;// noinspection JSUnusedLocalSymbols
(function ($, window, document, undefined) {

	"use strict";

	// Create the defaults once
	var pluginName = "wpmudevDashboardAdminCommon";

	// The actual plugin constructor
	function wpmudevDashboardAdminCommon(element, options) {
		this.element      = element;
		this.$el          = $(this.element);
		this.init();
	}

	// Avoid Plugin.prototype conflicts
	$.extend(wpmudevDashboardAdminCommon.prototype, {
		init: function () {
			this.showAlertNotice();
			this.showUpgradeHighlightsModal();
			this.initSelectSearch();
		},
		showAlertNotice() {
			var container = $( '.sui-common-notice-alert' ),
			noticeID      = '',
			message       = '',
			noticeOptions = {};
			if ( ! container.length ) {
				return;
			}
			noticeOptions.dismiss        = {};
			noticeOptions.autoclose      = {};
			noticeOptions.type           = container.data( 'notice-type' );
			noticeOptions.dismiss.show   = container.data( 'show-dismiss' );
			noticeOptions.autoclose.show = false;
			message                      = container.data( 'notice-msg' );
			noticeID                     = container.attr( 'id' );

			if ( 'success' === noticeOptions.type ) {
				noticeOptions.icon = 'check-tick';
			}

			SUI.openNotice( noticeID, message, noticeOptions );
		},

		/**
		 * Show upgrade highlights modal.
		 *
		 * If modal is available, open it.
		 *
		 * @since 4.11
		 */
		showUpgradeHighlightsModal() {
			// Get the modal element.
			var modal = $('#upgrade-highlights');
			if (modal.length > 0) {
				// Open the modal.
				SUI.openModal('upgrade-highlights', 'wpmudev-dashboard-header', null, true, false);

				// On close button click.
				modal.find('.modal-close-button').on('click', function () {
					var data = {
						action: 'wdp-dismiss-highlights',
						hash: $('#highlight_modal_hash').val(),
					};

					// Send request.
					$.post(window.ajaxurl, data);
				});
			}
		},

		/**
		 * Initialize search input.
		 *
		 * @since 4.0
		 * @since 4.11 Enabled for multiple inputs.
		 */
		initSelectSearch: function () {
			var self = this;

			this.$el.find('.wpmudev-search').each(function(){
				var search = $(this);
				var hash = search.data('hash');

				var searchAction = search.data('search-action');
				var searchParent = search.data('search-parent');
				var languageSearching = search.data('language-searching');
				var languageNoresults = search.data('language-noresults');
				var languageErrorLoading = search.data('language-error-load');
				var languageInputTooShort = search.data('language-input-tooshort');
				var languageInputTooShort = search.data('language-input-tooshort');

				search.SUIselect2({
					dropdownCssClass: 'sui-select-dropdown',
					dropdownParent: searchParent ? self.$el.find('#' + searchParent) : null,
					ajax: {
						url: window.ajaxurl,
						type: "POST",
						data: function (params) {
							return {
								action: searchAction,
								hash: hash,
								q: params.term,
							};
						},
						processResults: function (data) {
							return {
								results: data.data
							};
						},
					},
					templateResult: function (result) {
						if (typeof result.id !== 'undefined' && typeof result.label !== 'undefined') {
							return $(result.label);
						}
						return result.text;
					},
					templateSelection: function (result) {
						return result.display || result.text;
					},
					language: {
						searching: function () {
							return languageSearching;
						},
						noResults: function () {
							return languageNoresults;
						},
						errorLoading: function () {
							return languageErrorLoading;
						},
						inputTooShort: function () {
							return languageInputTooShort;
						},
					}
				});
			});
		},
	});

	// A really lightweight plugin wrapper around the constructor,
	// preventing against multiple instantiations
	$.fn[pluginName] = function (options) {
		return this.each(function () {
			if (!$.data(this, pluginName)) {
				$.data(this, pluginName, new wpmudevDashboardAdminCommon(this, options));
			}
		});
	};

})(jQuery, window, document);
