/* global wphb */

const MixPanel = require( 'mixpanel-browser' );

( function() {
	'use strict';

	window.wphbMixPanel = {
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
		 * Opt in tracking.
		 */
		optIn() {
			wphb.mixpanel.enabled = true;
			this.init();
			MixPanel.opt_in_tracking();
		},

		/**
		 * Opt out tracking.
		 */
		optOut() {
			MixPanel.opt_out_tracking();
		},

		/**
		 * Deactivate feedback.
		 *
		 * @param {string} reason   Deactivation reason.
		 * @param {string} feedback Deactivation feedback.
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
		 * @param {string} feature Feature name.
		 */
		enableFeature( feature ) {
			this.track( 'plugin_feature_activate', { feature } );
		},

		/**
		 * Track feature disable.
		 *
		 * @param {string} feature Feature name.
		 */
		disableFeature( feature ) {
			this.track( 'plugin_feature_deactivate', { feature } );
		},

		/**
		 * Track an event.
		 *
		 * @param {string} event Event ID.
		 * @param {Object} data  Event data.
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
		}
	};
}() );
