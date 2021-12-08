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

/**
 * Internal dependencies
 */
import HBAPIFetch from '../../api';
import { UserContext } from '../../context';
import Status from '../../views/caching/browser/status';

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
			cloudflare: {
				isAuthed: false,
				isConnected: false,
				isSetup: false,
				notice: false,
			},
			status: {
				CSS: false,
				Images: false,
				JavaScript: false,
				Media: false,
			},
			human: {},
		};

		this.updateStatus = this.updateStatus.bind( this );
	}

	/**
	 * Fetch/refresh browser caching status.
	 *
	 * @param {string} action  Accepts: 'get' and 'refresh'.
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
					status: response.status,
					human: response.human,
				} );
			} );
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
	 * Render component.
	 *
	 * @return {*} BrowserCachingPage.
	 */
	render() {
		return (
			<UserContext.Provider value={ this.state }>
				<Status
					data={ this.props.wphbData.module }
					link={ this.state.links }
					loading={ this.state.loading }
					onUpdate={ this.updateStatus }
					status={ this.state.status }
					human={ this.state.human }
					cloudflare={ this.state.cloudflare }
					onCloudflareClick={ this.handleCloudflareClick }
				/>
			</UserContext.Provider>
		);
	}
}

BrowserCachingPage.propTypes = {
	wphbData: PropTypes.object,
};

domReady( function () {
	const browserCachingPageDiv = document.getElementById(
		'wrap-wphb-browser-caching'
	);
	if ( browserCachingPageDiv ) {
		const wphbBrowserCachingReact = ReactDOM.render(
			/*** @var {object} window.wphb */
			<BrowserCachingPage wphbData={ window.wphbReact } />,
			browserCachingPageDiv
		);

		window.wphbBrowserCachingReactRefresh =
			wphbBrowserCachingReact.updateStatus;
	}
} );
