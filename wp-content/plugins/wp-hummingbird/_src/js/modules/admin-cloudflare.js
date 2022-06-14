/* global wphb */
/* global WPHB_Admin */

import Fetcher from '../utils/fetcher';
import { getString } from '../utils/helpers';

( function( $ ) {
	WPHB_Admin.cloudflare = {
		module: 'cloudflare',

		init() {
			/** @member {Array} wphb */
			if ( wphb.cloudflare.is.connected ) {
				$( 'input[type="submit"].cloudflare-clear-cache' ).on(
					'click',
					function( e ) {
						e.preventDefault();
						this.purgeCache.apply( $( e.target ), [ this ] );
					}.bind( this )
				);
			}

			this.bindActions();

			return this;
		},

		/**
		 * Bind actions.
		 */
		bindActions() {
			// On submit from the Cloudflare connect modal.
			const cfModal = document.getElementById( 'cloudflare-credentials' );
			if ( cfModal ) {
				cfModal.addEventListener( 'submit', ( e ) => {
					e.preventDefault();
					this.connect( 'cloudflare-connect-save' );
				} );
			}

			// Re-check zones.
			const reChkBtn = document.getElementById( 'cf-recheck-zones' );
			if ( reChkBtn ) {
				reChkBtn.addEventListener( 'click', ( e ) => {
					e.preventDefault();
					this.recheck( e );
				} );
			}

			// Save zone from modal.
			const saveBtn = document.getElementById( 'cloudflare-zone-save' );
			if ( saveBtn ) {
				saveBtn.addEventListener( 'click', ( e ) => {
					e.preventDefault();
					this.connect( 'cloudflare-zone-save' );
				} );
			}

			// Show key help in modal.
			const keyHelpLnk = document.getElementById(
				'cloudflare-show-key-help'
			);
			if ( keyHelpLnk ) {
				keyHelpLnk.addEventListener( 'click', ( e ) => {
					e.preventDefault();
					this.toggleHelp();
				} );
			}
			const topHelpLnk = document.getElementById(
				'cloudflare-connect-steps'
			);
			if ( topHelpLnk ) {
				topHelpLnk.addEventListener( 'click', ( e ) => {
					e.preventDefault();
					this.toggleHelp();
				} );
			}

			$( 'input[name="cf_connection_type"]' ).on( 'change', () => {
				this.hideHelp();
				this.switchLabel();
			} );

			// Enable/disable 'Connect' button based on form input.
			$( 'form#cloudflare-credentials input' ).on( 'keyup', function() {
				let disabled = true;

				$( 'form#cloudflare-credentials input' ).each( function() {
					if ( '' !== $( this ).val() ) {
						disabled = false;
					}
				} );

				$( '#cloudflare-connect-save' ).prop( 'disabled', disabled );
			} );
		},

		/**
		 * Purge Cloudflare cache.
		 */
		purgeCache() {
			const $button = this;
			$button.attr( 'disabled', true );

			Fetcher.common
				.call( 'wphb_cloudflare_purge_cache' )
				.then( () => {
					WPHB_Admin.notices.show(
						getString( 'successCloudflarePurge' )
					);
				} )
				.catch( ( reject ) => {
					WPHB_Admin.notices.show( reject.responseText, 'error' );
				} );

			$button.removeAttr( 'disabled' );
		},

		/**
		 * Connect to Cloudflare.
		 *
		 * @since 3.0.0
		 *
		 * @param {string} id Button ID for loader animation.
		 */
		connect: ( id ) => {
			const btn = document.getElementById( id );
			btn.classList.add( 'sui-button-onload-text' );

			const type = document.getElementById( 'cf-token-tab' ).checked
				? 'token'
				: 'key';

			// Remove errors.
			const apiKeyField = document.getElementById(
				'api-' + type + '-form-field'
			);
			apiKeyField.classList.remove( 'sui-form-field-error' );
			const apiKeyError = document.getElementById( 'error-api-' + type );
			apiKeyError.innerHTML = '';
			apiKeyError.style.display = 'none';

			// Get key/values.
			const email = document.getElementById( 'cloudflare-email' ).value;
			const key = document.getElementById( 'cloudflare-api-key' ).value;
			const token = document.getElementById( 'cloudflare-api-token' )
				.value;
			const zone = jQuery( '#cloudflare-zones' ).find( ':selected' );

			Fetcher.cloudflare
				.connect( email, key, token, zone.text() )
				.then( ( response ) => {
					if (
						'undefined' !== typeof response &&
						'undefined' !== typeof response.zones
					) {
						WPHB_Admin.cloudflare.populateSelectWithZones(
							response.zones
						);

						window.SUI.slideModal(
							'slide-cloudflare-zones',
							'cloudflare-zone-recheck',
							'next'
						);
					} else {
						// All good, reload page.
						window.location.reload();
					}
				} )
				.catch( ( error ) => {
					if (
						'undefined' !== typeof error.response &&
						'undefined' &&
						typeof error.response.data &&
						'undefined' !== typeof error.response.data.code
					) {
						// There was one of known errors with wrong API keys.
						if (
							400 === error.response.data.code ||
							403 === error.response.data.code
						) {
							apiKeyField.classList.add( 'sui-form-field-error' );
							apiKeyError.innerHTML = error.message;
							apiKeyError.style.display = 'block';
						}
					} else {
						// Fallback for unknown errors.
						WPHB_Admin.notices.show( error, 'error' );
					}
				} )
				.finally( () => {
					btn.classList.remove( 'sui-button-onload-text' );
				} );
		},

		/**
		 * Pre-populate a select with zones.
		 *
		 * @since 3.0.0
		 *
		 * @param {Array} zones
		 */
		populateSelectWithZones: ( zones ) => {
			const select = jQuery( '#cloudflare-zones' );

			select.SUIselect2( 'destroy' );

			zones.forEach( ( zone ) => {
				if (
					0 ===
					select.find( "option[value='" + zone.value + "']" ).length
				) {
					// Only add a new zone if it's not already present.
					const option = new Option( zone.label, zone.value );
					select.append( option ).trigger( 'change' );
				}
			} );

			select.SUIselect2( { minimumResultsForSearch: -1 } );
		},

		/**
		 * Re-check Cloudflare zones.
		 *
		 * @since 3.0.0
		 *
		 * @param {Object} e
		 */
		recheck: ( e ) => {
			e.target.classList.add( 'sui-button-onload-text' );

			Fetcher.common
				.call( 'wphb_cloudflare_recheck_zones' )
				.then( ( response ) => {
					if (
						'undefined' !== typeof response &&
						'undefined' !== typeof response.zones
					) {
						WPHB_Admin.cloudflare.populateSelectWithZones(
							response.zones
						);
					} else {
						// All good, reload page.
						window.location.reload();
					}
				} )
				.catch( ( error ) => {
					WPHB_Admin.notices.show( error, 'error' );
				} )
				.finally( () => {
					e.target.classList.remove( 'sui-button-onload-text' );
				} );
		},

		/**
		 * Toggle key help from the modal.
		 *
		 * @since 3.0.0
		 */
		toggleHelp: () => {
			const token = document.getElementById( 'cf-token-tab' ).checked;
			const type = token ? 'token' : 'key';

			document
				.getElementById( 'cloudflare-' + type + '-how-to' )
				.classList.toggle( 'sui-hidden' );

			const icon = document
				.getElementById( 'cloudflare-show-key-help' )
				.querySelector( 'span:last-of-type' );
			if ( icon.classList.contains( 'sui-icon-chevron-down' ) ) {
				icon.classList.remove( 'sui-icon-chevron-down' );
				icon.classList.add( 'sui-icon-chevron-up' );
			} else {
				icon.classList.remove( 'sui-icon-chevron-up' );
				icon.classList.add( 'sui-icon-chevron-down' );
			}
		},

		/**
		 * Hide instructions on connect modal when switching between key/token tabs.
		 *
		 * @since 3.1.0
		 */
		hideHelp: () => {
			$( '#cloudflare-key-how-to' ).addClass( 'sui-hidden' );
			$( '#cloudflare-token-how-to' ).addClass( 'sui-hidden' );

			$( 'span.sui-icon-chevron-up' )
				.addClass( 'sui-icon-chevron-down' )
				.removeClass( 'sui-icon-chevron-up' );
		},

		/**
		 * Switch label based on section in modal.
		 *
		 * @since 3.1.0
		 */
		switchLabel: () => {
			const token = document.getElementById( 'cf-token-tab' ).checked;
			const type = token ? 'token' : 'key';

			document.getElementById( 'cloudflare-email' ).value = '';
			document.getElementById( 'cloudflare-api-key' ).value = '';
			document.getElementById( 'cloudflare-api-token' ).value = '';

			document.querySelector(
				'#cloudflare-show-key-help > span:first-of-type'
			).innerHTML = wphb.strings[ 'CloudflareHelpAPI' + type ];
		},
	};
}( jQuery ) );
