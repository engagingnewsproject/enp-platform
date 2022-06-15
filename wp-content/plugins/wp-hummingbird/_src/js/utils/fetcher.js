/* global ajaxurl */
/* global wphb */

/**
 * External dependencies
 */
import assign from 'lodash/assign';

/**
 * Fetcher.
 *
 * @member {string} wphb.nonces.HBFetchNonce
 * @class
 */
function Fetcher() {
	const fetchUrl = ajaxurl;
	const fetchNonce = wphb.nonces.HBFetchNonce;
	const actionPrefix = 'wphb_';
	const actionPrefixPro = 'wphb_pro_';

	/**
	 * Request ajax with a promise.
	 * Use FormData Object as data if you need to upload file
	 *
	 * @param {string}          action
	 * @param {Object|FormData} data
	 * @param {string}          method
	 * @return {Promise<any>} Request results.
	 */
	function request( action, data = {}, method = 'GET' ) {
		const args = {
			url: fetchUrl,
			method,
			cache: false
		};

		if ( data instanceof FormData ) {
			data.append( 'nonce', fetchNonce );
			data.append( 'action', action );
			args.contentType = false;
			args.processData = false;
		} else {
			data.nonce 	= fetchNonce;
			data.action = action;
		}
		args.data = data;
		const Promise = require( 'es6-promise' ).Promise;
		return new Promise( ( resolve, reject ) => {
			jQuery.ajax( args ).done( resolve ).fail( reject );
		} ).then( ( response ) => checkStatus( response ) );
	}

	const methods = {
		/**
		 * Caching module actions.
		 */
		caching: {
			/**
			 * Unified save settings method.
			 *
			 * @since 1.9.0
			 * @param {string} module
			 * @param {string} data   Serialized form data.
			 */
			saveSettings: ( module, data ) => {
				return request(
					actionPrefix + module + '_save_settings',
					{ data },
					'POST'
				).then( ( response ) => {
					return response;
				} );
			},

			/**
			 * Clear cache for selected module.
			 *
			 * @since 1.9.0
			 * @param {string} module
			 */
			clearCache: ( module ) => {
				return request(
					actionPrefix + 'clear_module_cache',
					{ module },
					'POST'
				).then( ( response ) => {
					return response;
				} );
			},

			/**
			 * Clear cache for post.
			 *
			 * @param {number} postId
			 */
			clearCacheForPost: ( postId ) => {
				return request(
					actionPrefix + 'gutenberg_clear_post_cache',
					{ postId },
					'POST'
				);
			},

			/**
			 * Save Redis settings.
			 *
			 * @since 2.5.0
			 *
			 * @param {string} host
			 * @param {number} port
			 * @param {string} password
			 * @param {number} db
			 */
			redisSaveSettings( host, port, password, db ) {
				return request(
					actionPrefix + 'redis_save_settings',
					{ host, port, password, db },
					'POST'
				);
			},

			/**
			 * Toggle Redis object cache setting.
			 *
			 * @since 2.5.0
			 *
			 * @param {boolean} value
			 */
			redisObjectCache( value ) {
				return request(
					actionPrefix + 'redis_toggle_object_cache',
					{ value },
					'POST'
				);
			},

			/**
			 * Clear out page cache for a batch of subsites in a network.
			 *
			 * @since 2.7.0
			 *
			 * @param {number} sites
			 * @param {number} offset
			 */
			clearCacheBatch( sites, offset ) {
				return request(
					actionPrefix + 'clear_network_cache',
					{ sites, offset },
					'POST'
				);
			},
		},

		/**
		 * Cloudflare module actions.
		 */
		cloudflare: {
			/**
			 * Connect to Cloudflare.
			 *
			 * @since 3.0.0
			 *
			 * @param {string} email
			 * @param {string} key
			 * @param {string} token
			 * @param {string} zone
			 */
			connect: ( email, key, token, zone ) => {
				return request(
					actionPrefix + 'cloudflare_connect',
					{ email, key, token, zone },
					'POST'
				).then( ( response ) => {
					return response;
				} );
			},
		},

		/**
		 * Asset Optimization module actions.
		 */
		minification: {
			/**
			 * Toggle CDN settings.
			 *
			 * @param {string} value CDN checkbox value.
			 */
			toggleCDN: ( value ) => {
				const action = actionPrefix + 'minification_toggle_cdn';
				return request( action, { value }, 'POST' );
			},

			/**
			 * Toggle logs settings.
			 *
			 * @param {string} value
			 */
			toggleLog: ( value ) => {
				const action = actionPrefix + 'minification_toggle_log';
				return request( action, { value }, 'POST' );
			},

			/**
			 * Toggle minification advanced mode.
			 *
			 * @param {string}  value
			 * @param {boolean} hide
			 */
			toggleView: ( value, hide ) => {
				const action = actionPrefix + 'minification_toggle_view';
				return request( action, { value, hide }, 'POST' );
			},

			/**
			 * Start minification check.
			 */
			startCheck: () => {
				const action = actionPrefix + 'minification_start_check';
				return request( action, {}, 'POST' );
			},

			/**
			 * Do a step in minification process.
			 *
			 * @param {number} step
			 */
			checkStep: ( step ) => {
				const action = actionPrefix + 'minification_check_step';
				return request( action, { step }, 'POST' ).then(
					( response ) => {
						return response;
					}
				);
			},

			/**
			 * Finish minification process.
			 */
			finishCheck: () => {
				const action = actionPrefix + 'minification_finish_scan';
				return request( action, {}, 'POST' ).then( ( response ) => {
					return response;
				} );
			},

			/**
			 * Cancel minification scan.
			 */
			cancelScan: function cancelScan() {
				const action = actionPrefix + 'minification_cancel_scan';
				return request( action, {}, 'POST' );
			},

			/**
			 * Process critical css form.
			 *
			 * @since 1.8
			 * @param {string} form
			 */
			saveCriticalCss: ( form ) => {
				const action = actionPrefix + 'minification_save_critical_css';
				return request( action, { form }, 'POST' ).then(
					( response ) => {
						return response;
					}
				);
			},

			/**
			 * Update custom asset path
			 *
			 * @since 1.9
			 * @param {string} value
			 */
			updateAssetPath: ( value ) => {
				const action = actionPrefix + 'minification_update_asset_path';
				return request( action, { value }, 'POST' );
			},

			/**
			 * Reset individual file.
			 *
			 * @since 1.9.2
			 * @param {string} value
			 */
			resetAsset: ( value ) => {
				const action = actionPrefix + 'minification_reset_asset';
				return request( action, { value }, 'POST' );
			},

			/**
			 * Save settings in network admin.
			 *
			 * @since 2.0.0
			 * @param {string} settings
			 */
			saveNetworkSettings: ( settings ) => {
				const action =
					actionPrefix + 'minification_update_network_settings';
				return request( action, { settings }, 'POST' );
			},

			/**
			 * Update the CDN exclude list.
			 *
			 * @since 2.4.0
			 * @param {Object} data
			 */
			updateExcludeList: ( data ) => {
				const action = actionPrefix + 'minification_save_exclude_list';
				return request( action, { data }, 'POST' );
			},
		},

		/**
		 * Performance module actions.
		 */
		performance: {
			/**
			 * Save performance test settings.
			 *
			 * @param {string} data From data.
			 */
			savePerformanceTestSettings: ( data ) => {
				const action = actionPrefix + 'performance_save_settings';
				return request( action, { data }, 'POST' );
			},
		},

		/**
		 * Advanced tools module actions.
		 */
		advanced: {
			/**
			 * Save settings from advanced tools general and db cleanup sections.
			 *
			 * @param {string} data Type.
			 * @param {string} form Serialized form.
			 */
			saveSettings: ( data, form ) => {
				const action = actionPrefix + 'advanced_save_settings';
				return request( action, { data, form }, 'POST' ).then(
					( response ) => {
						return response;
					}
				);
			},

			/**
			 * Delete selected data from database.
			 *
			 * @param {string} data
			 */
			deleteSelectedData: ( data ) => {
				const action = actionPrefix + 'advanced_db_delete_data';
				return request( action, { data }, 'POST' ).then(
					( response ) => {
						return response;
					}
				);
			},

			/**
			 * Clear out a batch of orphaned asset optimization data.
			 *
			 * @since 2.7.0
			 *
			 * @param {number} rows
			 */
			clearOrphanedBatch( rows ) {
				return request(
					actionPrefix + 'advanced_purge_orphaned',
					{ rows },
					'POST'
				);
			},
		},

		/**
		 * Settings actions.
		 */
		settings: {
			/**
			 * Save settings from HB admin settings.
			 *
			 * @param {string} form_data
			 */
			saveSettings: ( form_data ) => {
				const action = actionPrefix + 'admin_settings_save_settings';
				return request( action, { form_data }, 'POST' ).then(
					( response ) => {
						return response;
					}
				);
			},

			/**
			 * Upload settings import file from HB admin settings.
			 *
			 * @param {Object} form_data
			 */
			importSettings: ( form_data ) => {
				const action = actionPrefix + 'admin_settings_import_settings';
				return request( action, form_data, 'POST' ).then(
					( response ) => {
						return response;
					}
				);
			},

			/**
			 * Export settings from HB admin settings.
			 */
			exportSettings: () => {
				const action = actionPrefix + 'admin_settings_export_settings';
				window.location =
					fetchUrl + '?action=' + action + '&nonce=' + fetchNonce;
			},
		},

		/**
		 * Common actions that are used by several modules.
		 *
		 * @since 1.9.3
		 */
		common: {
			/**
			 * Dismiss notice.
			 *
			 * @param {string} id
			 */
			dismissNotice: ( id ) => {
				return request(
					actionPrefix + 'notice_dismiss',
					{ id },
					'POST'
				);
			},

			/**
			 * Clear logs.
			 *
			 * @since 1.9.2
			 *
			 * @param {string} module Module slug.
			 */
			clearLogs: ( module ) => {
				const action = actionPrefix + 'logger_clear';
				return request( action, { module }, 'POST' ).then(
					( response ) => {
						return response;
					}
				);
			},

			/**
			 * Do a POST request to an AJAX endpoint.
			 *
			 * @since 2.5.0
			 * @param {string} endpoint AJAX endpoint.
			 */
			call: ( endpoint ) => {
				return request( endpoint, {}, 'POST' ).then( ( response ) => {
					return response;
				} );
			},

			/**
			 * Clear selected module cache.
			 *
			 * @since 2.7.1
			 *
			 * @param {Array} modules List of modules to clear cache for.
			 */
			clearCaches: ( modules ) => {
				const action = actionPrefix + 'clear_caches';
				return request( action, { modules }, 'POST' ).then(
					( response ) => {
						return response;
					}
				);
			},
		},

		/**
		 * Notifications module actions.
		 *
		 * @since 3.1.1
		 */
		notifications: {
			/**
			 * Resend email confirmation.
			 *
			 * @since 2.3.0
			 * @since 3.1.1 Moved from uptime module.
			 *
			 * @param {string} name  JSON encoded recipient name string.
			 * @param {string} email JSON encoded recipient email string.
			 */
			resendConfirmationEmail: ( name, email ) => {
				const action = actionPrefixPro + 'resend_confirmation';
				return request( action, { name, email }, 'POST' ).then(
					( response ) => {
						return response;
					}
				);
			},

			/**
			 * Send email confirmation.
			 *
			 * @since 3.1.1
			 *
			 * @param {string} name  JSON encoded recipient name string.
			 * @param {string} email JSON encoded recipient email string.
			 */
			sendConfirmationEmail: ( name, email ) => {
				const action = actionPrefixPro + 'send_confirmation';
				return request( action, { name, email }, 'POST' ).then(
					( response ) => {
						return response;
					}
				);
			},

			/**
			 * Disable notification.
			 *
			 * @since 3.1.1
			 * @param {string} id
			 * @param {string} type
			 */
			disable: ( id, type ) => {
				const action = actionPrefixPro + 'disable_notification';
				return request( action, { id, type }, 'POST' );
			},

			/**
			 * Activate/enable notification and save settings.
			 *
			 * @since 3.1.1
			 * @param {Object}  settings Settings object.
			 * @param {boolean} update   Is this an update of current settings?
			 *
			 */
			enable: ( settings, update = false ) => {
				const action = actionPrefixPro + 'enable_notification';
				return request( action, { settings, update }, 'POST' ).then(
					( response ) => {
						return response;
					}
				);
			},

			/**
			 * Get user avatar based on email.
			 *
			 * @since 3.1.1
			 * @param {string} email
			 */
			getAvatar: ( email ) => {
				const action = actionPrefixPro + 'get_avatar';
				return request( action, { email }, 'POST' ).then(
					( response ) => {
						return response;
					}
				);
			},

			/**
			 * Get users.
			 *
			 * @since 3.1.1
			 * @param {Array} exclude
			 */
			getUsers: ( exclude ) => {
				const action = actionPrefixPro + 'search_users';
				return request( action, { exclude }, 'POST' ).then(
					( response ) => {
						return response;
					}
				);
			},
		},
	};

	assign( this, methods );
}

const HBFetcher = new Fetcher();
export default HBFetcher;

/**
 * Check status.
 *
 * @param {Object|string} response
 * @return {*} Response
 */
function checkStatus( response ) {
	if ( typeof response !== 'object' ) {
		response = JSON.parse( response );
	}
	if ( response.success ) {
		return response.data;
	}

	const data = response.data || {};
	const error = new Error(
		data.message || 'Error trying to fetch response from server'
	);
	error.response = response;
	throw error;
}
