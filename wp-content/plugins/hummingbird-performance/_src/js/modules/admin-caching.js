/* global WPHB_Admin */

/**
 * Internal dependencies
 */
import Fetcher from '../utils/fetcher';
import { getString } from '../utils/helpers';
import CacheScanner from '../scanners/CacheScanner';

( function ( $ ) {
	'use strict';
	WPHB_Admin.caching = {
		module: 'caching',
		selectedServer: '',
		serverSelector: null,
		serverInstructions: [],
		snippets: [],
		selectedExpiryType: 'all',

		init() {
			const self = this,
				hash = window.location.hash,
				pageCachingForm = $( 'form[id="page_cache-form"]' ),
				rssForm = $( 'form[id="rss-form"]' ),
				gravatarDiv = $( 'div[id="wphb-box-caching-gravatar"]' ),
				cachingHeader = $( '.box-caching-status .sui-box-header' ),
				expiryForm = $( 'form[id="expiry-settings"]' ),
				settingsForm = $( 'form[id="settings-form"]' );

			// We assume there's at least one site, but this.scanner.init() will properly set the total sites.
			this.scanner = new CacheScanner( 1, 0 );

			// Define selected server.
			self.serverSelector = $( '#wphb-server-type' );
			self.selectedServer = self.serverSelector.val();

			if ( hash && $( hash ).length ) {
				setTimeout( function () {
					$( 'html, body' ).animate(
						{ scrollTop: $( hash ).offset().top },
						'slow'
					);
				}, 300 );
			} else if ( '#connect-cloudflare' === hash ) {
				self.setCloudflare();
			}

			/**
			 * PAGE CACHING
			 *
			 * @since 1.7.0
			 */

			// Save page caching settings.
			pageCachingForm.on( 'submit', ( e ) => {
				e.preventDefault();
				self.saveSettings( 'page_cache', pageCachingForm );
			} );

			// Clear page cache.
			pageCachingForm.on(
				'click',
				'.sui-box-header .sui-button',
				( e ) => {
					e.preventDefault();
					self.clearCache( 'page_cache', pageCachingForm );
				}
			);

			/**
			 * Toggle clear cache settings.
			 *
			 * @since 2.1.0
			 */
			const intervalToggle = document.getElementById( 'clear_interval' );
			if ( intervalToggle ) {
				intervalToggle.addEventListener( 'change', function ( e ) {
					e.preventDefault();
					$( '#page_cache_clear_interval' ).toggle();
				} );
			}

			/**
			 * Cancel cache preload.
			 *
			 * @since 2.1.0
			 */
			const cancelPreload = document.getElementById(
				'wphb-cancel-cache-preload'
			);
			if ( cancelPreload ) {
				cancelPreload.addEventListener( 'click', function ( e ) {
					e.preventDefault();
					Fetcher.common.call( 'wphb_preload_cancel' );
					window.location.reload();
				} );
			}

			/**
			 * Show/hide preload settings.
			 *
			 * @since 2.3.0
			 */
			const preloadToggle = document.getElementById( 'preload' );
			if ( preloadToggle ) {
				preloadToggle.addEventListener( 'change', function ( e ) {
					e.preventDefault();
					$( '#page_cache_preload_type' ).toggle();
				} );
			}

			/**
			 * Remove advanced-cache.php file.
			 *
			 * @since 3.1.1
			 */
			$( '#wphb-remove-advanced-cache' ).on( 'click', ( e ) => {
				e.preventDefault();
				Fetcher.common
					.call( 'wphb_remove_advanced_cache' )
					.then( () => location.reload() );
			} );

			/**
			 * BROWSER CACHING
			 */

			// Make sure we have single set to avoid modifying all cache values at once.
			if ( false === $( '#hb_all_expiry' ).prop( 'checked' ) ) {
				this.selectedExpiryType = 'single';
			}

			// Init server instructions tabs.
			$( '.wphb-server-instructions' ).each( function () {
				self.serverInstructions[ $( this ).data( 'server' ) ] = $(
					this
				);
			} );
			self.showServerInstructions( this.selectedServer );

			// Init code snippets.
			self.snippets.apache = $( '.apache-instructions' ).find(
				'pre.sui-code-snippet'
			);
			self.snippets.nginx = $( '#wphb-server-instructions-nginx' ).find(
				'pre.sui-code-snippet'
			);

			// Server type changed.
			self.serverSelector.on( 'change', function () {
				const value = $( this ).val();
				self.hideCurrentInstructions();
				self.showServerInstructions( value );
				self.setServer( value );
				self.selectedServer = value;
				$( '.hb-server-type' ).val( value );

				// Remove the expiry time settings row for IIS servers.
				const expiryRow = document.getElementById(
					'wphb-expiry-time-row'
				);
				expiryRow.style.display =
					'iis' === value || 'litespeed' === value ? 'none' : 'flex';
			} );

			// Expiry time change between all types and individual type.
			const expiryInput = $( "div[data-name='expiry-set-type']" );
			expiryInput.on( 'click', function () {
				const type = $( this ).data( 'value' );
				self.selectedExpiryType = type;
				self.reloadSnippets( self.getExpiryTimes( type ) );
			} );

			// Expiry value changed.
			expiryForm.on( 'change', 'select[name^="set-expiry"]', function () {
				self.reloadSnippets(
					self.getExpiryTimes( self.selectedExpiryType )
				);
				$( '#wphb-expiry-change-notice' ).slideDown();
			} );

			// Re-check expiry button clicked.
			cachingHeader.on( 'click', 'button.sui-button', ( e ) => {
				e.preventDefault();
				e.target.classList.add( 'sui-button-onload-text' );

				Fetcher.common
					.call( 'wphb_caching_recheck_expiry' )
					.then( ( response ) => {
						e.target.classList.remove( 'sui-button-onload-text' );
						if (
							'undefined' !== typeof response &&
							response.success
						) {
							WPHB_Admin.notices.show(
								getString( 'successRecheckStatus' )
							);
							self.reloadExpiryTags( response.expiry_values );
						} else {
							WPHB_Admin.notices.show(
								getString( 'errorRecheckStatus' ),
								'error'
							);
						}
					} );
			} );

			// Update .htaccess clicked.
			expiryForm.on( 'submit', ( e ) => {
				e.preventDefault();

				const button = $( '.update-htaccess' );
				const spinner = $( '.wphb-expiry-changes .spinner' );
				const notice = $( '#wphb-expiry-change-notice' );

				button.addClass( 'disabled' );
				spinner.addClass( 'visible' );

				Fetcher.caching.setExpiration(
					self.getExpiryTimes( self.selectedExpiryType )
				);

				// Set timeout to allow new expiry values to be saved.
				setTimeout( function () {
					Fetcher.common
						.call( 'wphb_caching_update_htaccess' )
						.then( ( response ) => {
							button.removeClass( 'disabled' );
							spinner.removeClass( 'visible' );
							notice.slideUp( 'slow' );

							window.wphbBrowserCachingReactRefresh();

							if (
								'undefined' !== typeof response &&
								response.success
							) {
								WPHB_Admin.notices.show(
									getString( 'htaccessUpdated' )
								);
							} else {
								WPHB_Admin.notices.show(
									getString( 'htaccessUpdatedFailed' ),
									'error'
								);
							}
						} );
				}, 1000 );
			} );

			// View code clicked (when rules already in .htaccess and expiry values are updated).
			$( '#view-snippet-code' ).on( 'click', function ( e ) {
				e.preventDefault();
				const serverInstructions = $(
					'#wphb-server-instructions-' +
						self.selectedServer.toLowerCase()
				);
				const selectedServer = self.selectedServer.toLowerCase();

				$( '#auto-' + selectedServer ).removeClass( 'active' );
				$( '#manual-' + selectedServer )
					.trigger( 'click' )
					.addClass( 'active' );

				$( 'html, body' ).animate(
					{ scrollTop: serverInstructions.offset().top - 50 },
					'slow'
				);
			} );

			// Activate button clicked.
			$( '.activate-button' ).on( 'click', function ( e ) {
				e.preventDefault();
				$( this ).addClass( 'sui-button-onload' );
				// Update expiration times.
				Fetcher.caching.setExpiration(
					self.getExpiryTimes( self.selectedExpiryType )
				);
				const redirect = $( this ).attr( 'href' );
				// Set timeout to allow new expiry values to be saved.
				setTimeout( function () {
					window.location = redirect;
				}, 1000 );
			} );

			/**
			 * CLOUDFLARE
			 */
			// "# of your cache types don’t meet the recommended expiry period" notice clicked.
			$( '#configure-link' ).on( 'click', function ( e ) {
				e.preventDefault();
				$( 'html, body' ).animate(
					{
						scrollTop: $( '#wphb-box-caching-settings' ).offset()
							.top,
					},
					'slow'
				);
			} );

			/**
			 * GRAVATAR CACHING
			 *
			 * @since 1.9.0
			 */

			// Clear cache.
			gravatarDiv.on( 'click', '.sui-box-header .sui-button', ( e ) => {
				e.preventDefault();
				self.clearCache( 'gravatar', gravatarDiv );
			} );

			/**
			 * RSS CACHING
			 *
			 * @since 1.8.0
			 */

			// Parse rss cache settings.
			rssForm.on( 'submit', ( e ) => {
				e.preventDefault();

				// Make sure a positive value is always reflected for the rss expiry time input.
				const rssExpiryTime = rssForm.find( '#rss-expiry-time' );
				rssExpiryTime.val( Math.abs( rssExpiryTime.val() ) );

				self.saveSettings( 'rss', rssForm );
			} );

			/**
			 * INTEGRATIONS
			 *
			 * @since 2.5.0
			 */
			const redisForm = document.getElementById( 'redis-settings-form' );
			if ( redisForm ) {
				redisForm.addEventListener( 'submit', ( e ) => {
					e.preventDefault();

					const btn = document.getElementById( 'redis-connect-save' );
					btn.classList.add( 'sui-button-onload-text' );

					const host = document.getElementById( 'redis-host' ).value;
					let port = document.getElementById( 'redis-port' ).value;
					const pass = document.getElementById( 'redis-password' )
						.value;
					const db = document.getElementById( 'redis-db' ).value;
					const connected = document.getElementById(
						'redis-connected'
					).value;

					if ( ! port ) {
						port = 6379;
					}

					// Submit via Fetcher. then close modal.
					Fetcher.caching
						.redisSaveSettings( host, port, pass, db )
						.then( ( response ) => {
							if (
								'undefined' !== typeof response &&
								response.success
							) {
								window.location.search +=
									connected === '1'
										? '&updated=redis-auth-2'
										: '&updated=redis-auth';
							} else {
								const notice = document.getElementById(
									'redis-connect-notice-on-modal'
								);
								notice.innerHTML = response.message;
								notice.parentNode.parentNode.parentNode.classList.remove(
									'sui-hidden'
								);
								notice.parentNode.parentNode.classList.add(
									'sui-spacing-top--10'
								);

								btn.classList.remove(
									'sui-button-onload-text'
								);
							}
						} );
				} );
			}

			const objectCache = document.getElementById( 'object-cache' );
			if ( objectCache ) {
				objectCache.addEventListener( 'change', ( e ) => {
					// Track feature enable.
					if ( e.target.checked ) {
						WPHB_Admin.Tracking.enableFeature( 'Redis Cache' );
					} else {
						WPHB_Admin.Tracking.disableFeature( 'Redis Cache' );
					}

					Fetcher.caching
						.redisObjectCache( e.target.checked )
						.then( ( response ) => {
							if (
								'undefined' !== typeof response &&
								response.success
							) {
								window.location.search +=
									'&updated=redis-object-cache';
							} else {
								WPHB_Admin.notices.show(
									getString( 'errorSettingsUpdate' ),
									'error'
								);
							}
						} );
				} );
			}

			const objectCachePurge = document.getElementById(
				'clear-redis-cache'
			);
			if ( objectCachePurge ) {
				objectCachePurge.addEventListener( 'click', () => {
					objectCachePurge.classList.add( 'sui-button-onload-text' );
					Fetcher.common
						.call( 'wphb_redis_cache_purge' )
						.then( () => {
							objectCachePurge.classList.remove(
								'sui-button-onload-text'
							);
							WPHB_Admin.notices.show(
								getString( 'successRedisPurge' )
							);
						} );
				} );
			}

			const redisCacheDisable = document.getElementById(
				'redis-disconnect'
			);
			if ( redisCacheDisable ) {
				redisCacheDisable.addEventListener( 'click', ( e ) => {
					e.preventDefault();
					this.redisDisable();
				} );
			}

			/**
			 * SETTINGS
			 *
			 * @since 1.8.1
			 */

			// Parse page cache settings.
			settingsForm.on( 'submit', ( e ) => {
				e.preventDefault();

				// Hide the notice if it is showing.
				const detection = $(
					'input[name="detection"]:checked',
					settingsForm
				).val();
				if ( 'auto' === detection || 'none' === detection ) {
					$( '.wphb-notice.notice-info' ).slideUp();
				}

				self.saveSettings( 'other_cache', settingsForm );
			} );

			return this;
		},

		/**
		 * Disable Redis cache.
		 *
		 * @since 2.5.0
		 */
		redisDisable: () => {
			Fetcher.common.call( 'wphb_redis_disconnect' ).then( () => {
				window.location.search += '&updated=redis-disconnect';
			} );
		},

		/**
		 * Process form submit from page caching, rss and settings forms.
		 *
		 * @since 1.9.0
		 *
		 * @param {string} module  Module name.
		 * @param {Object} form    Form.
		 */
		saveSettings: ( module, form ) => {
			const button = form.find( 'button.sui-button' );
			button.addClass( 'sui-button-onload-text' );

			Fetcher.caching
				.saveSettings( module, form.serialize() )
				.then( ( response ) => {
					button.removeClass( 'sui-button-onload-text' );

					if ( 'undefined' !== typeof response && response.success ) {
						if ( 'page_cache' === module ) {
							window.location.search += '&updated=true';
						} else {
							WPHB_Admin.notices.show();
						}
					} else {
						WPHB_Admin.notices.show(
							getString( 'errorSettingsUpdate' ),
							'error'
						);
					}
				} );
		},

		/**
		 * Unified clear cache method that clears: page cache, gravatar cache and browser cache.
		 *
		 * @since 1.9.0
		 *
		 * @param {string} module  Module for which to clear the cache.
		 * @param {Object} form    Form from which the call was made.
		 */
		clearCache: ( module, form ) => {
			const button = form.find( '.sui-box-header .sui-button' );
			button.addClass( 'sui-button-onload-text' );

			Fetcher.caching.clearCache( module ).then( ( response ) => {
				if ( 'undefined' !== typeof response && response.success ) {
					if ( 'page_cache' === module ) {
						$( '.box-caching-summary span.sui-summary-large' ).html(
							'0'
						);
						WPHB_Admin.notices.show(
							getString( 'successPageCachePurge' )
						);
					} else if ( 'gravatar' === module ) {
						WPHB_Admin.notices.show(
							getString( 'successGravatarPurge' )
						);
					}
				} else {
					WPHB_Admin.notices.show(
						getString( 'errorCachePurge' ),
						'error'
					);
				}

				button.removeClass( 'sui-button-onload-text' );
			} );
		},

		/**
		 * Set server type.
		 *
		 * @param {string} value
		 */
		setServer( value ) {
			Fetcher.caching.setServer( value );
		},

		/**
		 * Set Cloudflare.
		 */
		setCloudflare() {
			$( '#wphb-server-type' )
				.val( 'cloudflare' )
				.trigger( 'sui:change' );
			this.hideCurrentInstructions();
			this.setServer( 'cloudflare' );
			this.showServerInstructions( 'cloudflare' );
			this.selectedServer = 'cloudflare';

			setTimeout( function () {
				$( 'html, body' ).animate(
					{ scrollTop: $( '#cloudflare-steps' ).offset().top },
					'slow'
				);
			}, 300 );
		},

		reloadExpiryTags( expiryValues ) {
			for ( const k in expiryValues ) {
				if ( expiryValues.hasOwnProperty( k ) ) {
					$( '#wphb-caching-expiry-' + k ).text( expiryValues[ k ] );
				}
			}
		},

		hideCurrentInstructions() {
			if ( this.serverInstructions[ this.selectedServer ] ) {
				this.serverInstructions[ this.selectedServer ].addClass(
					'sui-hidden'
				);

				document.getElementById(
					'wphb-expiry-change-notice'
				).style.display = 'none';
			}
		},

		showServerInstructions( server ) {
			if ( typeof this.serverInstructions[ server ] !== 'undefined' ) {
				const serverTab = this.serverInstructions[ server ];
				serverTab.removeClass( 'sui-hidden' );
			}

			if ( 'apache' === server ) {
				$( '.enable-cache-wrap-' + server ).removeClass( 'sui-hidden' );
			} else {
				$( '#enable-cache-wrap' ).addClass( 'sui-hidden' );
			}
		},

		reloadSnippets( expiryTimes ) {
			const self = this;
			const stop = false;

			for ( const i in self.snippets ) {
				if ( self.snippets.hasOwnProperty( i ) ) {
					Fetcher.caching.setExpiration( expiryTimes );
					Fetcher.caching
						.reloadSnippets( i, expiryTimes )
						.then( ( response ) => {
							if ( stop ) {
								return;
							}

							self.snippets[ response.type ].text(
								response.code
							);
						} );
				}
			}
		},

		getExpiryTimes( type ) {
			let expiryTimes = [];
			if ( 'all' === type ) {
				const all = $( '#set-expiry-all' ).val();
				expiryTimes = {
					expiry_javascript: all,
					expiry_css: all,
					expiry_media: all,
					expiry_images: all,
				};
			} else {
				expiryTimes = {
					expiry_javascript: $( '#set-expiry-javascript' ).val(),
					expiry_css: $( '#set-expiry-css' ).val(),
					expiry_media: $( '#set-expiry-media' ).val(),
					expiry_images: $( '#set-expiry-images' ).val(),
				};
			}
			return expiryTimes;
		},

		/**
		 * Clear network wide page cache.
		 *
		 * @since 2.7.0
		 */
		clearNetworkCache() {
			window.SUI.slideModal( 'ccnw-slide-two', 'slide-next', 'next' );
			this.scanner.start();
		},
	};
} )( jQuery );
