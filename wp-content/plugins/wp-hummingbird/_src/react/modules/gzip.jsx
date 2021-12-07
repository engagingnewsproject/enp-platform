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
import '../app.scss';
import HBAPIFetch from '../api';
import { UserContext } from '../context';
import GzipSummary from '../views/gzip/summary';
import GzipConfig from '../views/gzip/configure';

/**
 * GzipPage component.
 *
 * @since 2.1.1
 */
class GzipPage extends React.Component {
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
			status: {
				HTML: false,
				JavaScript: false,
				CSS: false,
			},
		};

		this.updateStatus = this.updateStatus.bind( this );
	}

	/**
	 * Invoked immediately after a component is mounted.
	 */
	componentDidMount() {
		this.state.api
			.post( 'gzip_status', 'get' )
			.then( ( response ) => {
				this.setState( {
					loading: false,
					status: response.status,
				} );
			} )
			.catch( ( error ) => window.console.log( error ) );
	}

	/**
	 * Update Gzip compression status.
	 */
	updateStatus() {
		this.setState( { loading: true } );

		this.state.api
			.post( 'gzip_status', 'refresh' )
			.then( ( response ) => {
				this.setState( {
					loading: false,
					status: response.status,
				} );
			} )
			.catch( ( error ) => window.console.log( error ) );
	}

	/**
	 * Enable Gzip compression via .htaccess rules.
	 *
	 * @param {string} action Available actions: add|remove.
	 */
	gzipRules( action = 'add' ) {
		this.setState( { loading: true } );

		this.state.api
			.post( 'gzip_rules', action )
			.then( ( response ) => {
				this.props.wphbData.module.htaccess_written =
					response.htaccess_written; // Overwrite the prop.

				this.setState( {
					loading: false,
					status: response.status,
				} );
			} )
			.catch( ( error ) => window.console.log( error ) );
	}

	/**
	 * Render component.
	 *
	 * @return {*} Gzip page.
	 */
	render() {
		return (
			<UserContext.Provider value={ this.state }>
				<GzipSummary
					data={ this.props.wphbData.module }
					link={ this.state.links }
					loading={ this.state.loading }
					onUpdate={ this.updateStatus }
					status={ this.state.status }
				/>
				<GzipConfig
					data={ this.props.wphbData.module }
					disableGzip={ () => this.gzipRules( 'remove' ) }
					enableGzip={ () => this.gzipRules( 'add' ) }
					loading={ this.state.loading }
					status={ this.state.status }
				/>
			</UserContext.Provider>
		);
	}
}

GzipPage.propTypes = {
	wphbData: PropTypes.object,
};

domReady( function() {
	const gzipPageDiv = document.getElementById( 'wrap-wphb-gzip' );
	if ( gzipPageDiv ) {
		ReactDOM.render(
			/*** @var {object} window.wphb */
			<GzipPage wphbData={ window.wphb } />,
			gzipPageDiv
		);
	}
} );
