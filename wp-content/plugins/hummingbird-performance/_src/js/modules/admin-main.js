/* global wphb */

/**
 * Internal dependencies
 */
import Fetcher from '../utils/fetcher';
import { getString } from '../utils/helpers';

/**
 * External dependencies
 */
const MixPanel = require( 'mixpanel-browser' );

( function ( $ ) {
	'use strict';

	const WPHB_Admin = {
		modules: [],
		// Common functionality to all screens
		init() {
			/**
			 * Handles the tab navigation on mobile.
			 *
			 * @since 2.7.2
			 */
			$( '.sui-mobile-nav' ).on( 'change', ( e ) => {
				window.location.href = e.target.value;
			} );

			/**
			 * Refresh page, when selecting a report type.
			 *
			 * @since 2.0.0
			 */
			$( 'select#wphb-performance-report-type' ).on(
					'change',
					function ( e ) {
						const url = new URL( window.location );
						url.searchParams.set( 'type', e.target.value );
						window.location = url;
					}
			);

			/**
			 * Clear log button clicked.
			 *
			 * @since 1.9.2
			 */
			$( '.wphb-logging-buttons' ).on(
				'click',
				'.wphb-logs-clear',
				function ( e ) {
					e.preventDefault();

					Fetcher.common
						.clearLogs( e.target.dataset.module )
						.then( ( response ) => {
							if ( 'undefined' === typeof response.success ) {
								return;
							}

							if ( response.success ) {
								WPHB_Admin.notices.show( response.message );
							} else {
								WPHB_Admin.notices.show(
									response.message,
									'error'
								);
							}
						} );
				}
			);

			/**
			 * Add recipient button clicked.
			 *
			 * On Performance and Uptime recipient modals.
			 *
			 * @since 1.9.3  Unified two handle both modules.
			 */
			$( '#add-recipient' ).on( 'click', function () {
				const self = $( this );
				self.attr( 'disabled', 'disabled' );

				let module = '';
				let setting = 'reports';

				// Get the module name from URL.
				if ( window.location.search.includes( 'wphb-performance' ) ) {
					module = 'performance';
				} else if ( window.location.search.includes( 'wphb-uptime' ) ) {
					module = 'uptime';
					if ( window.location.search.includes( 'notifications' ) ) {
						setting = 'notifications';
					}
				}

				const reportingEmail = $( '#reporting-email' );
				const emailField = reportingEmail.closest( '.sui-form-field' );
				const email = reportingEmail.val();
				const name = $( '#reporting-first-name' ).val();

				// Remove errors.
				emailField.removeClass( 'sui-form-field-error' );
				emailField.find( '.sui-error-message' ).remove();

				Fetcher.common
					.addRecipient( module, setting, email, name )
					.then( ( response ) => {
						const userRow = $( '<div class="sui-recipient"/>' );

						if ( 'notifications' === setting ) {
							userRow.append(
								'<span class="sui-recipient-status sui-tooltip" data-tooltip="' +
									getString( 'awaitingConfirmation' ) +
									'"><span class="sui-icon-clock" aria-hidden="true"></span></span>'
							);
						}

						userRow.append( '<span class="sui-recipient-name"/>' );
						userRow
							.find( '.sui-recipient-name' )
							.append( response.name );

						userRow.append(
							$( '<span class="sui-recipient-email"/>' ).html(
								email
							)
						);

						if ( 'notifications' === setting ) {
							userRow.append(
								$( '<button/>' )
									.attr( {
										class:
											'sui-button-icon wphb-resend-recipient sui-tooltip',
										type: 'button',
										'data-tooltip': getString(
											'resendEmail'
										),
									} )
									.html(
										'<span class="sui-icon-send" aria-hidden="true"></span>'
									)
							);
						}

						userRow.append(
							$( '<button/>' )
								.attr( {
									class:
										'sui-button-icon wphb-remove-recipient',
									type: 'button',
								} )
								.html(
									'<span class="sui-icon-trash" aria-hidden="true"></span>'
								)
						);

						$( '<input>' )
							.attr( {
								type: 'hidden',
								id: 'report-recipient',
								name: 'report-recipients[]',
								value: JSON.stringify( {
									email: response.email,
									name: response.name,
								} ),
							} )
							.appendTo( userRow );

						$( '.sui-recipients' ).append( userRow );
						$( '#reporting-email' ).val( '' );
						$( '#reporting-first-name' ).val( '' );

						// Hide no recipients notification.
						$( '.wphb-no-recipients' ).slideUp();
						window.SUI.closeModal();

						// Hide top notice.
						$( '.sui-notice-top.sui-notice-success' ).hide();

						// Hide the last notice.
						$( '.wphb-pending-sub-notice' ).hide();
						// Show confirm recipients notice.
						$( '.wphb-confirm-sub-notice' ).show();

						// Show notice to save settings.
						WPHB_Admin.notices.show(
							name + getString( 'successRecipientAdded' ),
							'info'
						);
						self.removeAttr( 'disabled' );
					} )
					.catch( ( error ) => {
						emailField.addClass( 'sui-form-field-error' );
						emailField.append(
							'<span class="sui-error-message"/>'
						);
						emailField
							.find( '.sui-error-message' )
							.append( error.message );
						self.removeAttr( 'disabled' );
					} );
			} );

			const body = $( 'body' );

			/**
			 * Save report settings clicked (performance reports, uptime
			 * reports and uptime notifications).
			 */
			body.on( 'submit', '.wphb-report-settings', function ( e ) {
				e.preventDefault();

				$( '.wphb-confirm-sub-notice' ).slideUp();

				$( this ).find( '.button' ).attr( 'disabled', 'disabled' );

				Fetcher.common
					.saveReportsSettings(
						this.dataset.module,
						$( this ).serialize()
					)
					.then( ( response ) => {
						if (
							'undefined' !== typeof response &&
							response.success
						) {
							if ( response.moduleStatus ) {
								WPHB_Admin.Tracking.enableFeature(
									this.dataset.name
								);
							} else {
								WPHB_Admin.Tracking.disableFeature(
									this.dataset.name
								);
							}

							if ( response.enabled || '' !== response.notice ) {
								$( '.sui-notice-top' ).hide();

								if ( response.moduleStatus ) {
									$(
										'.sui-box-body > .sui-notice-default:first-of-type'
									)
										.addClass( 'sui-notice-success' )
										.removeClass( 'sui-notice-default' );
									$(
										'.sui-box-body > .sui-notice-success:first-of-type > p'
									).text( response.recipientNotice );
									$(
										'.sui-vertical-tab.current span[class^="sui-icon"]'
									).removeClass( 'sui-hidden' );
								} else {
									$(
										'.sui-box-body > .sui-notice-success:first-of-type'
									)
										.addClass( 'sui-notice-default' )
										.removeClass( 'sui-notice-success' );
									$(
										'.sui-box-body > .sui-notice-default:first-of-type > p'
									).text( response.recipientNotice );
									$(
										'.sui-vertical-tab.current span[class^="sui-icon"]'
									).addClass( 'sui-hidden' );
								}

								WPHB_Admin.notices.show(
									response.enabled
										? getString( 'confirmRecipient' )
										: response.notice
								);
							} else {
								window.location.search += '&updated=true';
							}

							$( '.wphb-pending-sub-notice' ).toggle(
								response.recipientPending
							);
						} else {
							WPHB_Admin.notices.show(
								getString( 'errorSettingsUpdate' ),
								'error'
							);
						}
					} );
			} );

			/**
			 * Remove recipient button clicked.
			 */
			body.on( 'click', '.wphb-remove-recipient', function () {
				$( this ).closest( '.sui-recipient' ).remove();

				const id = $( this ).attr( 'data-id' );
				const row = 'input[id="report-recipient"][value=' + id + ']';

				$( '.wphb-report-settings' ).find( row ).remove();

				if ( 0 === $( '.sui-recipient' ).length ) {
					$( '.wphb-pending-sub-notice' ).slideUp();
					$( '.wphb-no-recipients' ).slideDown();
				}
			} );

			/**
			 * Handle the show/hiding of the report schedule.
			 */
			$( '#chk1' ).on( 'click', function () {
				$( '.schedule-box' ).toggleClass( 'sui-hidden' );

				$(
					'#wphb-performance-reporting .sui-box-settings-row:first-child'
				).toggleClass( 'wphb-first-of-type' );
				$( '#performance-customizations' ).toggleClass( 'sui-hidden' );
			} );

			/**
			 * Schedule show/hide day of week.
			 */
			$( 'select[name="report-frequency"]' )
				.on( 'change', function () {
					const freq = $( this ).val();

					if ( '1' === freq ) {
						$( this )
							.closest( '.schedule-box' )
							.find( 'div.days-container' )
							.hide();
					} else {
						$( this )
							.closest( '.schedule-box' )
							.find( 'div.days-container' )
							.show();

						if ( '7' === freq ) {
							$( this )
								.closest( '.schedule-box' )
								.find( '[data-type="week"]' )
								.show();
							$( this )
								.closest( '.schedule-box' )
								.find( '[data-type="month"]' )
								.hide();
						} else {
							$( this )
								.closest( '.schedule-box' )
								.find( '[data-type="week"]' )
								.hide();
							$( this )
								.closest( '.schedule-box' )
								.find( '[data-type="month"]' )
								.show();
						}
					}
				} )
				.trigger( 'change' );

			/**
			 * Track performance report scan init.
			 *
			 * @since 2.5.0
			 */
			$( '#performance-run-test, #performance-scan-website' ).on(
				'click',
				() => {
					WPHB_Admin.Tracking.track( 'plugin_scan_started', {
						score_mobile_previous: getString(
							'previousScoreMobile'
						),
						score_desktop_previous: getString(
							'previousScoreDesktop'
						),
					} );
				}
			);
		},

		initModule( module ) {
			if ( this.hasOwnProperty( module ) ) {
				this.modules[ module ] = this[ module ].init();
				return this.modules[ module ];
			}

			return {};
		},

		getModule( module ) {
			if ( typeof this.modules[ module ] !== 'undefined' ) {
				return this.modules[ module ];
			}
			return this.initModule( module );
		},
	};

	/**
	 * Admin notices.
	 */
	WPHB_Admin.notices = {
		init() {
			const cfNotice = document.getElementById( 'dismiss-cf-notice' );
			if ( cfNotice ) {
				cfNotice.onclick = ( e ) => this.dismissCloudflareNotice( e );
			}

			const http2Notice = document.getElementById(
				'wphb-floating-http2-info'
			);
			if ( http2Notice ) {
				http2Notice.addEventListener( 'click', ( e ) => {
					e.preventDefault();
					Fetcher.common.dismissNotice( 'http2-info' );
					$( '.wphb-box-notice' ).slideUp();
				} );
			}
		},

		/**
		 * Show notice.
		 *
		 * @since 1.8
		 *
		 * @param {string}  message  Message to display.
		 * @param {string}  type     Error or success.
		 * @param {boolean} dismiss  Auto dismiss message.
		 */
		show( message = '', type = 'success', dismiss = true ) {
			if ( '' === message ) {
				message = getString( 'successUpdate' );
			}

			const options = {
				type,
				dismiss: {
					show: false,
					label: getString( 'dismissLabel' ),
					tooltip: getString( 'dismissLabel' ),
				},
				icon: 'info',
			};

			if ( ! dismiss ) {
				options.dismiss.show = true;
			}

			window.SUI.openNotice(
				'wphb-ajax-update-notice',
				'<p>' + message + '</p>',
				options
			);
		},

		/**
		 * Dismiss notice.
		 *
		 * @since 2.6.0  Refactored and moved from WPHB_Admin.init()
		 *
		 * @param {Object} el
		 */
		dismiss( el ) {
			const noticeId = el.closest( '.sui-notice' ).getAttribute( 'id' );
			Fetcher.common.dismissNotice( noticeId );
			window.SUI.closeNotice( noticeId );
		},

		/**
		 * Dismiss Cloudflare notice from Dashboard or Caching pages.
		 *
		 * @since 2.6.0  Refactored and moved from WPHB_Admin.dashboard.init() && WPHB_ADMIN.caching.init()
		 *
		 * @param {Object} e
		 */
		dismissCloudflareNotice( e ) {
			e.preventDefault();
			Fetcher.common.call( 'wphb_cf_notice_dismiss' );
			const cloudFlareDashNotice = $( '.cf-dash-notice' );
			cloudFlareDashNotice.slideUp();
			cloudFlareDashNotice.parent().addClass( 'no-background-image' );
		},
	};

	/**
	 * Mixpanel tracking.
	 *
	 * @since 2.5.0
	 */
	WPHB_Admin.Tracking = {
		/**
		 * Init super properties (common with every request).
		 */
		init() {
			if (
				'undefined' === typeof wphb.mixpanel ||
				! wphb.mixpanel.enabled
			) {
				return;
			}

			MixPanel.init( '5d545622e3a040aca63f2089b0e6cae7', {
				opt_out_tracking_by_default: true,
				ip: false,
			} );

			MixPanel.register( {
				plugin: wphb.mixpanel.plugin,
				plugin_type: wphb.mixpanel.plugin_type,
				plugin_version: wphb.mixpanel.plugin_version,
				wp_version: wphb.mixpanel.wp_version,
				wp_type: wphb.mixpanel.wp_type,
				locale: wphb.mixpanel.locale,
				active_theme: wphb.mixpanel.active_theme,
				php_version: wphb.mixpanel.php_version,
				mysql_version: wphb.mixpanel.mysql_version,
				server_type: wphb.mixpanel.server_type,
			} );
		},

		/**
		 * Toggle the tracking option from the modal.
		 */
		toggle() {
			const checkbox = document.getElementById( 'tracking-toggle-modal' );
			if ( checkbox ) {
				/**
				 * This is our way of tracking the opt-in event on the modal.
				 *
				 * @type {boolean}
				 */
				if ( true === checkbox.checked ) {
					this.optIn();
				} else {
					MixPanel.opt_out_tracking();
				}

				Fetcher.common.toggleTracking( checkbox.checked );
			}
		},

		/**
		 * Opt in tracking.
		 */
		optIn() {
			wphb.mixpanel.enabled = true;
			this.init();
			MixPanel.opt_in_tracking();
		},

		/**
		 * Deactivate feedback.
		 *
		 * @param {string} reason    Deactivation reason.
		 * @param {string} feedback  Deactivation feedback.
		 */
		deactivate( reason, feedback = '' ) {
			this.track( 'plugin_deactivate', {
				reason,
				feedback,
			} );
		},

		/**
		 * Track feature enable.
		 *
		 * @param {string} feature  Feature name.
		 */
		enableFeature( feature ) {
			this.track( 'plugin_feature_activate', { feature } );
		},

		/**
		 * Track feature disable.
		 *
		 * @param {string} feature  Feature name.
		 */
		disableFeature( feature ) {
			this.track( 'plugin_feature_deactivate', { feature } );
		},

		/**
		 * Track an event.
		 *
		 * @param {string} event  Event ID.
		 * @param {Object} data   Event data.
		 */
		track( event, data = {} ) {
			if (
				'undefined' === typeof wphb.mixpanel ||
				! wphb.mixpanel.enabled
			) {
				return;
			}

			if ( ! MixPanel.has_opted_out_tracking() ) {
				MixPanel.track( event, data );
			}
		},
	};

	window.WPHB_Admin = WPHB_Admin;
} )( jQuery );
