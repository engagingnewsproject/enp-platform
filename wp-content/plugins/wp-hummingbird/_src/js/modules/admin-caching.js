/* global WPHB_Admin */
/* global wphbMixPanel */

/**
 * Internal dependencies
 */
import Fetcher from '../utils/fetcher';
import { getString } from '../utils/helpers';
import CacheScanner from '../scanners/CacheScanner';

( function( $ ) {
	'use strict';
	WPHB_Admin.caching = {
		module: 'caching',

		init() {
			const self = this,
				hash = window.location.hash,
				pageCachingForm = $( 'form[id="page_cache-form"]' ),
				rssForm = $( 'form[id="rss-form"]' ),
				gravatarDiv = $( 'div[id="wphb-box-caching-gravatar"]' ),
				settingsForm = $( 'form[id="settings-form"]' );

			// We assume there's at least one site, but this.scanner.init() will properly set the total sites.
			this.scanner = new CacheScanner( 1, 0 );

			if ( hash && $( hash ).length ) {
				setTimeout( function() {
					$( 'html, body' ).animate(
						{ scrollTop: $( hash ).offset().top },
						'slow'
					);
				}, 300 );
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
				intervalToggle.addEventListener( 'change', function( e ) {
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
				cancelPreload.addEventListener( 'click', function( e ) {
					e.preventDefault();
					Fetcher.common.call( 'wphb_preload_cancel' ).then( () => {
						window.location.reload();
					} );
				} );
			}

			/**
			 * Show/hide preload settings.
			 *
			 * @since 2.3.0
			 */
			const preloadToggle = document.getElementById( 'preload' );
			if ( preloadToggle ) {
				preloadToggle.addEventListener( 'change', function( e ) {
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
			 * CLOUDFLARE
			 */
			// "# of your cache types don’t meet the recommended expiry period" notice clicked.
			$( '#configure-link' ).on( 'click', function( e ) {
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
						wphbMixPanel.enableFeature( 'Redis Cache' );
					} else {
						wphbMixPanel.disableFeature( 'Redis Cache' );
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
		 * @param {string} module Module name.
		 * @param {Object} form   Form.
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
		 * @param {string} module Module for which to clear the cache.
		 * @param {Object} form   Form from which the call was made.
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
