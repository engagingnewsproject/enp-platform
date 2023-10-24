/* global WPHB_Admin */

/**
 * External dependencies
 */
import React from 'react';
import ReactDOM from 'react-dom';
import PropTypes from 'prop-types';

/**
 * WordPress dependencies
 */
import domReady from '@wordpress/dom-ready';
const { __ } = wp.i18n;

/**
 * Internal dependencies
 */
import '../../app.scss';
import HBAPIFetch from '../../api';
import { UserContext } from '../../context';
import Status from '../../views/caching/browser/status';
import Wizard from '../../views/caching/browser/wizard';
import { getString } from '../../../js/utils/helpers';

/**
 * BrowserCachingPage component.
 *
 * @since 2.7.2
 */
class BrowserCachingPage extends React.Component {
	/**
	 * Component constructor.
	 *
	 * @param {Object} props
	 */
	constructor( props ) {
		super( props );

		this.state = {
			api: new HBAPIFetch(),
			isMember: this.props.wphbData.isMember,
			links: this.props.wphbData.links,
			loading: true,
			detectedServer: this.props.wphbData.module.detectedServer,
			cloudflare: {
				isAuthed: false,
				isConnected: false,
				isSetup: false,
				notice: false,
			},
			status: { // Expires - server settings (in seconds).
				CSS: false,
				Images: false,
				JavaScript: false,
				Media: false,
			},
			expires: { // Expires - User selected settings (format).
				CSS: false,
				Images: false,
				JavaScript: false,
				Media: false,
			},
			snippets: this.props.wphbData.module.snippets,
			human: {}, // Expires - human readable format.
			showWizard: false,
		};

		this.updateStatus = this.updateStatus.bind( this );
		this.showWizard = this.showWizard.bind( this );
		this.hideWizard = this.hideWizard.bind( this );
		this.setExpiry = this.setExpiry.bind( this );
		this.saveExpiryRules = this.saveExpiryRules.bind( this );
		this.clearCache = this.clearCache.bind( this );
		this.disconnectCloudflare = this.disconnectCloudflare.bind( this );
	}

	/**
	 * Fetch/refresh browser caching status.
	 *
	 * @param {string} action Accepts: 'get' and 'refresh'.
	 */
	browserCachingStatus( action = 'get' ) {
		this.state.api
			.post( 'browser_caching_status', action )
			.then( ( response ) => {
				this.setState( {
					loading: false,
					cloudflare: {
						isAuthed: response.cloudflareAuthed,
						isConnected: response.usingCloudflare,
						isSetup: response.cloudflareSetUp,
						notice: response.cloudflareNotice,
					},
					detectedServer: response.detectedServer,
					status: response.status,
					expires: response.expires,
					human: response.human,
				} );
			} )
			.catch( ( error ) => window.console.log( error ) );
	}

	/**
	 * Invoked immediately after a component is mounted.
	 */
	componentDidMount() {
		this.browserCachingStatus();
	}

	/**
	 * Update browser caching status.
	 */
	updateStatus() {
		this.setState( { loading: true } );
		this.browserCachingStatus( 'refresh' );
	}

	/**
	 * Show setup wizard.
	 *
	 * @since 3.2.0
	 */
	showWizard() {
		this.setState( { showWizard: true } );
	}

	/**
	 * Hide setup wizard.
	 *
	 * @since 3.2.0
	 */
	hideWizard() {
		this.setState( { showWizard: false } );
	}

	/**
	 * Update expiry values.
	 *
	 * @since 3.2.0
	 *
	 * @param {Object} e Select that triggered the change.
	 */
	setExpiry( e ) {
		let CSS;
		let Images;
		let JavaScript;
		let Media;

		if ( 'set-expiry-all' === e.target.id ) {
			CSS = e.target.value;
			Images = e.target.value;
			JavaScript = e.target.value;
			Media = e.target.value;

			// Fix for select2 not picking up updates.
			if ( ! this.state.cloudflare.isAuthed && ! this.state.cloudflare.isSetup ) {
				const event = new Event( 'change' );
				document.getElementById( 'set-expiry-css' ).value = e.target.value;
				document.getElementById( 'set-expiry-css' ).dispatchEvent( event );
				document.getElementById( 'set-expiry-images' ).value = e.target.value;
				document.getElementById( 'set-expiry-images' ).dispatchEvent( event );
				document.getElementById( 'set-expiry-javascript' ).value = e.target.value;
				document.getElementById( 'set-expiry-javascript' ).dispatchEvent( event );
				document.getElementById( 'set-expiry-media' ).value = e.target.value;
				document.getElementById( 'set-expiry-media' ).dispatchEvent( event );
			}
		} else {
			CSS = document.getElementById( 'set-expiry-css' ).value;
			Images = document.getElementById( 'set-expiry-images' ).value;
			JavaScript = document.getElementById( 'set-expiry-javascript' ).value;
			Media = document.getElementById( 'set-expiry-media' ).value;
		}

		this.setState( { expires: { CSS, Images, JavaScript, Media } } );
	}

	/**
	 * Update expiry rules in the database.
	 *
	 * @since 3.2.0
	 *
	 * @param {string} server Selected server.
	 */
	saveExpiryRules( server ) {
		const data = {
			server,
			expires: this.state.expires,
		};

		return this.state.api
			.post( 'update_expiry', data )
			.then( ( response ) => {
				this.setState( { snippets: response.snippets } );

				// Update status if we were able to apply Apache rules.
				if ( ( 'apache' === server || 'litespeed' === server ) && 'undefined' !== typeof response.status ) {
					this.setState( {
						status: response.status,
						human: response.human,
					} );
				}

				return response;
			} )
			.catch( ( error ) => window.console.log( error ) );
	}

	/**
	 * Scroll to Cloudflare settings, when clicking on the upsell "Connect" button.
	 */
	handleCloudflareClick() {
		window.SUI.openModal(
			'cloudflare-connect',
			'wrap-wphb-browser-caching',
			'cloudflare-email',
			false,
			false
		);
	}

	/**
	 * Clear Cloudflare cache.
	 *
	 * @since 3.2.0
	 */
	clearCache() {
		this.setState( { loading: true } );

		return this.state.api
			.post( 'clear_cache' )
			.then( () => {
				this.setState( { loading: false } );
				WPHB_Admin.notices.show(
					getString( 'successCloudflarePurge' )
				);
			} )
			.catch( ( error ) => window.console.log( error ) );
	}

	/**
	 * Disconnect Cloudflare.
	 *
	 * @since 3.2.0
	 */
	disconnectCloudflare() {
		this.setState( { loading: true } );

		this.state.api
			.post( 'cloudflare_disconnect' )
			.then( () => {
				this.browserCachingStatus( 'refresh' );
				WPHB_Admin.notices.show(
					__( 'Cloudflare was disconnected successfully.', 'wphb' )
				);
			} )
			.catch( ( error ) => window.console.log( error ) );
	}

	/**
	 * Render component.
	 *
	 * @return {*} BrowserCachingPage.
	 */
	render() {
		return (
			<UserContext.Provider value={ this.state }>
				{ this.state.showWizard &&
					<Wizard
						loading={ this.state.loading }
						detectedServer={ this.state.detectedServer }
						data={ this.props.wphbData.module }
						status={ this.state.status }
						human={ this.state.human }
						expires={ this.state.expires }
						onHideWizard={ this.hideWizard }
						onRecheckStatus={ this.updateStatus }
						onExpiryChange={ this.setExpiry }
						saveExpiryRules={ this.saveExpiryRules }
						snippets={ this.state.snippets }
						cloudflare={ this.state.cloudflare }
					/>
				}

				{ ! this.state.showWizard &&
					<Status
						data={ this.props.wphbData.module }
						link={ this.state.links }
						loading={ this.state.loading }
						onUpdate={ this.updateStatus }
						onShowWizard={ this.showWizard }
						status={ this.state.status }
						human={ this.state.human }
						cloudflare={ this.state.cloudflare }
						onCloudflareClick={ this.handleCloudflareClick }
						clearCache={ this.clearCache }
						disconnectCloudflare={ this.disconnectCloudflare }
					/>
				}
			</UserContext.Provider>
		);
	}
}

BrowserCachingPage.propTypes = {
	wphbData: PropTypes.object,
};

domReady( function() {
	const browserCachingPageDiv = document.getElementById(
		'wrap-wphb-browser-caching'
	);
	if ( browserCachingPageDiv ) {
		ReactDOM.render(
			/*** @var {object} window.wphb */
			<BrowserCachingPage wphbData={ window.wphbReact } />,
			browserCachingPageDiv
		);
	}
} );
