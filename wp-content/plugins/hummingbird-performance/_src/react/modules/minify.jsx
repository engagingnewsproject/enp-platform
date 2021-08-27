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
import HBAPIFetch from '../api';
import { UserContext } from '../context';
import Assets from '../views/minify/assets';
import Configurations from '../views/minify/configurations';

/**
 * AutoMinifyPage component.
 *
 * @since 2.7.2
 */
class AutoMinifyPage extends React.Component {
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
			view: 'speedy',
			assets: {
				styles: {},
				scripts: {},
			},
			enabled: {
				styles: true,
				scripts: true,
			},
			exclusions: {
				styles: {},
				scripts: {},
			},
		};

		this.clearCache = this.clearCache.bind( this );
		this.reCheckFiles = this.reCheckFiles.bind( this );
		this.resetSettings = this.resetSettings.bind( this );
		this.updateCheckBox = this.updateCheckBox.bind( this );
		this.saveSettings = this.saveSettings.bind( this );
		this.handleToggleChange = this.handleToggleChange.bind( this );
	}

	/**
	 * Invoked immediately after a component is mounted.
	 */
	componentDidMount() {
		this.state.api.post( 'minify_status' ).then( ( response ) => {
			this.setState( {
				assets: response.assets,
				enabled: response.enabled,
				exclusions: response.exclusions,
				loading: false,
				view: response.view,
			} );
		} );
	}

	/**
	 * Clear asset optimization cache.
	 */
	clearCache() {
		this.setState( { loading: true } );

		this.state.api.post( 'minify_clear_cache' ).then( () => {
			WPHB_Admin.notices.show(
				__(
					'Your cache has been successfully cleared. Your assets will regenerate the next time someone visits your website.', 'wphb'
				)
			);

			this.setState( {
				loading: false,
			} );
		} );
	}

	/**
	 * Re-check files.
	 */
	reCheckFiles() {
		this.setState( { loading: true } );

		this.state.api.post( 'minify_recheck_files' ).then( () => {
			location.reload();
		} );
	}

	/**
	 * Reset asset optimization settings.
	 */
	resetSettings() {
		this.setState( { loading: true } );

		this.state.api.post( 'minify_reset_settings' ).then( () => {
			WPHB_Admin.notices.show(
				__( 'Settings restored to defaults', 'wphb' )
			);
			this.setState( {
				enabled: {
					styles: true,
					scripts: true,
				},
				exclusions: {
					styles: {},
					scripts: {},
				},
				loading: false,
			} );
		} );
	}

	/**
	 * Update files checkbox states.
	 *
	 * @param {Object} e
	 */
	updateCheckBox( e ) {
		if ( 'undefined' === e.target.id ) {
			return;
		}

		const enabled = {
			styles: this.state.enabled.styles,
			scripts: this.state.enabled.scripts,
		};

		if ( 'wphb-auto-css' === e.target.id ) {
			enabled.styles = e.target.checked;
		}

		if ( 'wphb-auto-js' === e.target.id ) {
			enabled.scripts = e.target.checked;
		}

		this.setState( { enabled } );
	}

	/**
	 * Save asset optimization settings.
	 */
	saveSettings() {
		this.setState( { loading: true } );

		const data = WPHB_Admin.minification.getMultiSelectValues(
			'wphb-auto-exclude'
		);

		const settings = {
			type: this.state.view,
			styles: this.state.enabled.styles,
			scripts: this.state.enabled.scripts,
			data: JSON.stringify( data ),
		};

		this.state.api.post( 'minify_save_settings', settings ).then( ( r ) => {
			// Automatic type has not changed.
			if (
				undefined !== typeof r.typeChanged &&
				false === r.typeChanged
			) {
				WPHB_Admin.notices.show();
			} else {
				WPHB_Admin.notices.show( r.typeChanged, 'success', false );

				// Allow opening a "how-to" modal from the notice.
				const noticeLink = document.getElementById(
					'wphb-basic-hdiw-link'
				);
				if ( noticeLink ) {
					noticeLink.addEventListener( 'click', () => {
						window.SUI.closeNotice( 'wphb-ajax-update-notice' );
						window.SUI.openModal(
							'automatic-ao-hdiw-modal-content',
							'automatic-ao-hdiw-modal-expand'
						);
					} );
				}
			}

			const view =
				'undefined' === typeof r.view ? this.state.view : r.view;

			this.setState( {
				assets: r.assets,
				enabled: r.enabled,
				exclusions: r.exclusions,
				loading: false,
				view,
			} );
		} );
	}

	/**
	 * Handle toggle click (Speedy/Basic).
	 *
	 * @param {Object} e Event.
	 */
	handleToggleChange( e ) {
		if ( ! e.target.checked ) {
			return;
		}

		this.setState( {
			view: e.target.dataset.type,
		} );
	}

	/**
	 * Render component.
	 *
	 * @return {*} AutoMinifyPage.
	 */
	render() {
		return (
			<UserContext.Provider value={ this.state }>
				<Assets
					loading={ this.state.loading }
					clearCache={ this.clearCache }
					reCheckFiles={ this.reCheckFiles }
					view={ this.state.view }
					handleToggleChange={ this.handleToggleChange }
					showModal={ this.props.wphbData.module.showModal }
				/>
				<Configurations
					link={ this.state.links }
					isMember={ this.state.isMember }
					module={ this.props.wphbData.module }
					loading={ this.state.loading }
					resetSettings={ this.resetSettings }
					saveSettings={ this.saveSettings }
					onEnabledChange={ this.updateCheckBox }
					assets={ this.state.assets }
					enabled={ this.state.enabled }
					exclusions={ this.state.exclusions }
				/>
			</UserContext.Provider>
		);
	}
}

AutoMinifyPage.propTypes = {
	wphbData: PropTypes.object,
};

domReady( function () {
	const minifyPageDiv = document.getElementById( 'wrap-wphb-auto-minify' );
	if ( minifyPageDiv ) {
		ReactDOM.render(
			/*** @var {object} window.wphb */
			<AutoMinifyPage wphbData={ window.wphbReact } />,
			minifyPageDiv
		);
	}
} );
