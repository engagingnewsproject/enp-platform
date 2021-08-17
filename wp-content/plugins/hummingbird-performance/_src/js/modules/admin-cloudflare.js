/* global wphb */
/* global WPHB_Admin */

import Fetcher from '../utils/fetcher';
import { getString } from '../utils/helpers';

( function ( $ ) {
	WPHB_Admin.cloudflare = {
		module: 'cloudflare',

		init() {
			const self = this;

			/** @member {Array} wphb */
			if ( wphb.cloudflare.is.connected ) {
				$( 'input[type="submit"].cloudflare-clear-cache' ).on(
					'click',
					function ( e ) {
						e.preventDefault();
						this.purgeCache.apply( $( e.target ), [ this ] );
					}.bind( this )
				);

				$( '#set-cf-expiry-button' ).on( 'click', ( e ) => {
					e.preventDefault();
					self.setExpiry.call( self, $( '#set-expiry-all' ) );
				} );

				// Expiry value changed.
				$( 'select[name^="set-expiry"]' ).on( 'change', function () {
					WPHB_Admin.caching.reloadSnippets(
						WPHB_Admin.caching.getExpiryTimes(
							WPHB_Admin.caching.selectedExpiryType
						)
					);
					$( '#wphb-expiry-change-notice' ).slideDown();
				} );
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
					this.toggleHelp( e );
				} );
			}
		},

		setExpiry( selector ) {
			const spinner = $( '.wphb-expiry-changes .spinner' );
			const button = $( '.wphb-expiry-changes input[type="submit"]' );

			spinner.addClass( 'visible' );
			button.addClass( 'disabled' );

			Fetcher.cloudflare
				.setExpiration( $( selector ).val() )
				.then( ( response ) => {
					$( '#wphb-expiry-change-notice' ).hide();
					spinner.removeClass( 'visible' );
					button.removeClass( 'disabled' );

					if (
						'undefined' !==
						typeof window.wphbBrowserCachingReactRefresh
					) {
						window.wphbBrowserCachingReactRefresh();
					}

					if ( 'undefined' !== typeof response && response.success ) {
						WPHB_Admin.notices.show();
					} else {
						WPHB_Admin.notices.show(
							getString( 'errorSettingsUpdate' ),
							'error'
						);
					}
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
		 * @param {string} id  Button ID for loader animation.
		 */
		connect: ( id ) => {
			const btn = document.getElementById( id );
			btn.classList.add( 'sui-button-onload-text' );

			// Remove errors.
			const apiKeyField = document.getElementById( 'api-key-form-field' );
			apiKeyField.classList.remove( 'sui-form-field-error' );
			const apiKeyError = document.getElementById( 'error-api-key' );
			apiKeyError.innerHTML = '';
			apiKeyError.style.display = 'none';

			// Get key/values.
			const email = document.getElementById( 'cloudflare-email' ).value;
			const key = document.getElementById( 'cloudflare-api-key' ).value;
			const zone = jQuery( '#cloudflare-zones' ).find( ':selected' );

			Fetcher.cloudflare
				.connect( email, key, zone.text() )
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
		 *
		 * @param {Object} e
		 */
		toggleHelp: ( e ) => {
			document
				.getElementById( 'cloudflare-how-to' )
				.classList.toggle( 'sui-hidden' );

			const icon = e.target.querySelector( 'span' );
			if ( icon.classList.contains( 'sui-icon-chevron-down' ) ) {
				icon.classList.remove( 'sui-icon-chevron-down' );
				icon.classList.add( 'sui-icon-chevron-up' );
			} else {
				icon.classList.remove( 'sui-icon-chevron-up' );
				icon.classList.add( 'sui-icon-chevron-down' );
			}
		},
	};
} )( jQuery );
