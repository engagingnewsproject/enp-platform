/* global WPHB_Admin */
/* global SUI */

import Fetcher from '../utils/fetcher';
import { getString } from '../utils/helpers';

( function ( $ ) {
	WPHB_Admin.dashboard = {
		module: 'dashboard',

		init() {
			$( '.wphb-performance-report-item' ).on( 'click', function () {
				const url = $( this ).data( 'performance-url' );
				if ( url ) {
					location.href = url;
				}
			} );

			const clearCacheModalButton = document.getElementById(
				'clear-cache-modal-button'
			);
			if ( clearCacheModalButton ) {
				clearCacheModalButton.addEventListener(
					'click',
					this.clearCache
				);
			}

			return this;
		},

		/**
		 * Clear selected cache.
		 *
		 * @since 2.7.1
		 */
		clearCache() {
			this.classList.toggle( 'sui-button-onload-text' );

			const checkboxes = document.querySelectorAll(
				'input[type="checkbox"]'
			);

			const modules = [];
			for ( let i = 0; i < checkboxes.length; i++ ) {
				if ( false === checkboxes[ i ].checked ) {
					continue;
				}

				modules.push( checkboxes[ i ].dataset.module );
			}

			Fetcher.common.clearCaches( modules ).then( ( response ) => {
				this.classList.toggle( 'sui-button-onload-text' );
				SUI.closeModal();
				WPHB_Admin.notices.show( response.message );
			} );
		},

		/**
		 * Skip quick setup.
		 *
		 * @param {boolean} reload  Reload the page after skipping setup.
		 */
		skipSetup( reload = true ) {
			Fetcher.common.call( 'wphb_dash_skip_setup' ).then( () => {
				if ( reload ) {
					window.location.reload();
				}
			} );
		},

		/**
		 * Run performance test after quick setup.
		 */
		runPerformanceTest() {
			window.SUI.closeModal(); // Hide tracking-modal.
			// Show performance test modal
			window.SUI.openModal(
				'run-performance-onboard-modal',
				'wpbody-content',
				undefined,
				false
			);

			window.WPHB_Admin.Tracking.track( 'plugin_scan_started', {
				score_mobile_previous: getString( 'previousScoreMobile' ),
				score_desktop_previous: getString( 'previousScoreDesktop' ),
			} );

			this.skipSetup( false );

			// Run performance test
			window.WPHB_Admin.getModule( 'performance' ).scanner.start();
		},

		hideUpgradeSummary: () => {
			window.SUI.closeModal();
			Fetcher.common.call( 'wphb_hide_upgrade_summary' );
		},
	};
} )( jQuery );
