/* global wphb */
/* global wphbMixPanel */

/**
 * Internal dependencies
 */
import Fetcher from '../utils/fetcher';
import { getString } from '../utils/helpers';

/**
 * External dependencies
 */
const MixPanel = require( 'mixpanel-browser' );

( function( $ ) {
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
				function( e ) {
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
				function( e ) {
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
			 * Track performance report scan init.
			 *
			 * @since 2.5.0
			 */
			$( '#performance-run-test, #performance-scan-website' ).on(
				'click',
				() => {
					wphbMixPanel.track( 'plugin_scan_started', {
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
		 * @param {string}  message Message to display.
		 * @param {string}  type    Error or success.
		 * @param {boolean} dismiss Auto dismiss message.
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

	window.WPHB_Admin = WPHB_Admin;
} )( jQuery );
