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
require( '../../js/mixpanel' );
import '../app.scss';
import { getLink } from '../../js/utils/helpers';
import HBAPIFetch from '../api';
import Button from '../components/sui-button';
import ButtonLoading from '../components/sui-button-loading';
import Tooltip from '../components/sui-tooltip';
import Wizard from '../views/setup/wizard';

/**
 * SetupWizard component.
 *
 * @since 3.3.1
 */
class SetupWizard extends React.Component {
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
			hasUptime: this.props.wphbData.hasUptime,
			loading: false,
			/**
			 * Steps:
			 * 1. Start of setup
			 * 2. Asset optimization
			 * 3. Uptime
			 * 4. Page caching
			 * 5. Advanced tools
			 * 6. Finish
			 */
			step: 1,
			issues: {
				advCacheFile: false,
				fastCGI: false
			},
			showConflicts: false,
			settings: {
				aoEnable: true,
				aoSpeedy: true,
				aoCdn: Boolean( this.props.wphbData.isMember ),
				uptimeEnable: Boolean( this.props.wphbData.hasUptime ),
				cacheEnable: true,
				cacheOnMobile: true,
				clearOnComment: true,
				cacheHeader: true,
				clearCacheButton: true,
				queryStrings: true,
				cartFragments: Boolean( this.props.wphbData.hasWoo ),
				removeEmoji: true,
				tracking: false,
			}
		};

		this.checkRequirements = this.checkRequirements.bind( this );
		this.removeAdvancedCache = this.removeAdvancedCache.bind( this );
		this.disableFastCGI = this.disableFastCGI.bind( this );
		this.skipConflicts = this.skipConflicts.bind( this );
		this.nextStep = this.nextStep.bind( this );
		this.prevStep = this.prevStep.bind( this );
		this.finish = this.finish.bind( this );
		this.updateSettings = this.updateSettings.bind( this );
		this.toggleModule = this.toggleModule.bind( this );
		this.quitWizard = this.quitWizard.bind( this );
	}

	/**
	 * Wizard started.
	 */
	componentDidMount() {
		this.checkRequirements();
	}

	/**
	 * Skip conflict check.
	 */
	skipConflicts() {
		this.setState( {
			showConflicts: false,
			step: 2
		} );
	}

	/**
	 * Go to next step in wizard.
	 */
	nextStep() {
		if ( 1 === this.state.step && ( this.state.issues.advCacheFile || this.state.issues.fastCGI ) ) {
			this.setState( { showConflicts: true } );
			return;
		}

		let step = this.state.step + 1;

		// If Asset optimization and free user - skip Uptime step.
		if ( 2 === this.state.step && ! this.state.hasUptime ) {
			step++;
		}

		this.setState( { loading: true } );

		const data = { ...this.state.settings, module: '', enable: false };
		if ( 2 === this.state.step ) {
			data.module = 'ao';
			data.enable = this.state.settings.aoEnable;
		} else if ( 3 === this.state.step ) {
			data.module = 'uptime';
			data.enable = this.state.settings.uptimeEnable;
		} else if ( 4 === this.state.step ) {
			data.module = 'caching';
			data.enable = this.state.settings.cacheEnable;
		} else if ( 5 === this.state.step ) {
			data.module = 'advanced';
		}

		this.state.api
			.post( 'settings', data )
			.then( () => this.setState( {
				showConflicts: false,
				step,
				loading: false
			} ) )
			.catch( ( error ) => window.console.log( error ) );
	}

	/**
	 * Go to previous step in wizard.
	 */
	prevStep() {
		let step = this.state.step - 1;

		// Skip Uptime step for free users.
		if ( 4 === this.state.step && ! this.state.hasUptime ) {
			step--;
		}

		this.setState( { step } );
	}

	/**
	 * Complete wizard.
	 *
	 * @param {string} goToPage Go to page.
	 */
	finish( goToPage = 'pluginDash' ) {
		this.setState( { loading: true } );
		this.state.api
			.post( 'complete_wizard' )
			.then( () => {
				if ( 'string' !== typeof goToPage ) {
					goToPage = 'pluginDash';
				}

				if ( 'runPerf' === goToPage ) {
					window.wphbMixPanel.track( 'plugin_scan_started', {
						score_mobile_previous: '-',
						score_desktop_previous: '-',
					} );
				}

				window.location.href = getLink( goToPage );
			} )
			.catch( ( error ) => window.console.log( error ) );
	}

	/**
	 * Check setup wizard requirements.
	 *
	 * @param {boolean} setLoadingState
	 */
	checkRequirements( setLoadingState = false ) {
		if ( setLoadingState ) {
			this.setState( { loading: true } );
		}

		this.state.api
			.post( 'check_requirements' )
			.then( ( response ) => {
				this.setState( {
					loading: false,
					issues: response.status
				} );
			} )
			.catch( ( error ) => window.console.log( error ) );
	}

	/**
	 * Remove advanced-cache.php file.
	 */
	removeAdvancedCache() {
		this.setState( { loading: true } );

		this.state.api
			.post( 'remove_advanced_cache' )
			.then( ( response ) => {
				this.setState( {
					loading: false,
					issues: response.status
				} );
			} )
			.catch( ( error ) => window.console.log( error ) );
	}

	/**
	 * Disable FastCGI cache.
	 */
	disableFastCGI() {
		this.setState( { loading: true } );

		this.state.api
			.post( 'disable_fast_cgi' )
			.then( ( response ) => {
				this.setState( {
					loading: false,
					issues: response.status
				} );
			} )
			.catch( ( error ) => window.console.log( error ) );
	}

	/**
	 * Update settings on toggle status change.
	 *
	 * @param {Object} e
	 */
	updateSettings( e ) {
		const settings = { ...this.state.settings };
		settings[ e.target.id ] = e.target.checked;

		if ( 'tracking' === e.target.id ) {
			if ( e.target.checked ) {
				window.wphbMixPanel.optIn();
			} else {
				window.wphbMixPanel.optOut();
			}
		}

		this.setState( { settings } );
	}

	/**
	 * Process enable/disable button clicks.
	 *
	 * @param {string}  setting Setting ID.
	 * @param {boolean} value   Value.
	 */
	toggleModule( setting, value ) {
		const settings = { ...this.state.settings };
		settings[ setting ] = value;
		this.setState( { settings } );
	}

	/**
	 * Quit wizard.
	 * TODO: add tracking
	 */
	quitWizard() {
		this.setState( { loading: true } );

		this.state.api.post( 'cancel_wizard' )
			.then( () => window.location.href = getLink( 'pluginDash' ) )
			.catch( ( error ) => window.console.log( error ) );
	}

	/**
	 * Get wizard header.
	 *
	 * @return {JSX.Element} Wizard header
	 */
	getHeader() {
		return (
			<div className="sui-header wphb-wizard-header">
				<h2 className="sui-header-title">
					<img
						className="sui-image"
						alt={ __( 'Setup wizard', 'wphb' ) }
						src={ getLink( 'wphbDirUrl' ) + 'admin/assets/image/setup/hummingbird.png' }
						srcSet={
							getLink( 'wphbDirUrl' ) + 'admin/assets/image/setup/hummingbird.png 1x, ' +
							getLink( 'wphbDirUrl' ) + 'admin/assets/image/setup/hummingbird@2x.png 2x'
						} />
					{ __( 'Hummingbird', 'wphb' ) }
					<small>{ __( 'Wizard', 'wphb' ) }</small>
				</h2>
				<div className="sui-actions-right">
					{ ! this.state.isMember &&
						<Tooltip
							text={ __( 'Get Hummingbird Pro for our full WordPress speed optimization suite, including uptime monitoring and enhanced, hosted file minification.', 'wphb' ) }
							classes={ [ 'sui-tooltip-constrained', 'sui-tooltip-bottom' ] }
							data={
								<Button
									classes={ [ 'sui-button', 'sui-button-purple' ] }
									target="blank"
									url={ getLink( 'upsell' ) }
									text={ __( 'Try Pro for free', 'wphb' ) }
								/>
							}
						/> }

					{ 6 !== this.state.step &&
						<ButtonLoading
							onClick={ this.quitWizard }
							type="button"
							loading={ this.state.loading }
							classes={ [ 'sui-button', 'sui-button-ghost' ] }
							icon="sui-icon-logout"
							text={ __( 'Quit wizard', 'wphb' ) } /> }

					<Button
						classes={ [ 'sui-button', 'sui-button-ghost' ] }
						icon="sui-icon-academy"
						target="blank"
						url={ getLink( 'docs' ) }
						text={ __( 'Documentation', 'wphb' ) } />
				</div>
			</div>
		);
	}

	/**
	 * Render component.
	 *
	 * @return {*} Gzip page.
	 */
	render() {
		return (
			<React.Fragment>
				{ this.getHeader() }
				<Wizard
					loading={ this.state.loading }
					step={ this.state.step }
					showConflicts={ this.state.showConflicts }
					issues={ this.state.issues }
					minifySteps={ this.props.wphbData.minifySteps }
					nextStep={ this.nextStep }
					prevStep={ this.prevStep }
					finish={ this.finish }
					skipConflicts={ this.skipConflicts }
					isMember={ this.state.isMember }
					isNetworkAdmin={ this.props.wphbData.isNetworkAdmin }
					hasUptime={ this.state.hasUptime }
					settings={ this.state.settings }
					hasWoo={ this.props.wphbData.hasWoo }
					reCheckRequirements={ () => this.checkRequirements( true ) }
					updateSettings={ this.updateSettings }
					toggleModule={ this.toggleModule }
					disableFastCGI={ this.disableFastCGI }
					removeAdvancedCache={ this.removeAdvancedCache } />
			</React.Fragment>
		);
	}
}

SetupWizard.propTypes = {
	wphbData: PropTypes.object,
};

domReady( function() {
	const setupWizard = document.getElementById( 'wrap-wphb-setup' );
	if ( setupWizard ) {
		const setupReact = ReactDOM.render(
			/*** @var {object} window.wphb */
			<SetupWizard wphbData={ window.wphb } />,
			setupWizard
		);
		// Add callback for scanners.
		window.wphbSetupNextStep = setupReact.nextStep;
	}
} );
