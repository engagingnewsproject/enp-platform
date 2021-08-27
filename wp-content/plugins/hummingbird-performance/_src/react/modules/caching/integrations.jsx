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
const { __, sprintf } = wp.i18n;

/**
 * Internal dependencies
 */
import HBAPIFetch from '../../api';
import BorderFrame from '../../components/border-frame';
import Box from '../../components/sui-box';
import Button from '../../components/sui-button';
import Checkbox from '../../components/sui-checkbox';
import Icon from '../../components/sui-icon';
import Notice from '../../components/sui-notice';
import Select from '../../components/sui-select';
import SettingsRow from '../../components/sui-box-settings/row';
import Toggle from '../../components/sui-toggle';
import Tooltip from '../../components/sui-tooltip';
import Tag from '../../components/sui-tag';

/**
 * Integrations component.
 *
 * @since 3.0.0
 */
class Integrations extends React.Component {
	/**
	 * Component constructor.
	 *
	 * @param {Object} props
	 */
	constructor( props ) {
		super( props );

		this.state = {
			api: new HBAPIFetch(),
			allowModify: this.props.wphbData.modify,
			links: this.props.wphbData.links,
			loading: true,
			cf: this.props.wphbData.module.cloudflare,
			apo: this.props.wphbData.module.apo,
			zones: [],
			selectedZone: false, // Track zone select.
		};

		this.disconnectCloudflare = this.disconnectCloudflare.bind( this );
		this.reCheckStatus = this.reCheckStatus.bind( this );
		this.reCheckAPOStatus = this.reCheckAPOStatus.bind( this );
		this.clearCache = this.clearCache.bind( this );
		this.handleAPOToggleChange = this.handleAPOToggleChange.bind( this );
		this.handleDevChkbxChange = this.handleDevChkbxChange.bind( this );
		this.handleZoneChange = this.handleZoneChange.bind( this );
		this.saveSelectedZone = this.saveSelectedZone.bind( this );
	}

	/**
	 * Invoked immediately after a component is mounted.
	 */
	componentDidMount() {
		this.state.api.post( 'cloudflare_status' ).then( ( response ) => {
			this.setState( {
				loading: false,
				cf: response.cloudflare,
				apo: response.apo,
			} );
		} );
	}

	/**
	 * Open the Connect Cloudflare modal.
	 */
	openConnectModal() {
		window.SUI.openModal(
			'cloudflare-connect',
			'wphb-box-integrations',
			'cloudflare-email',
			false,
			false
		);
	}

	/**
	 * Re-check status button click.
	 */
	reCheckStatus() {
		this.setState( { loading: true } );

		this.state.api.post( 'cloudflare_zones' ).then( ( response ) => {
			// Error.
			if ( 'undefined' !== typeof response.message ) {
				window.WPHB_Admin.notices.show( response.message, 'error' );
			}

			// Got a list of zones.
			let zones = [];
			if ( 'undefined' !== typeof response.zones ) {
				zones = response.zones;
			}

			this.setState( {
				loading: false,
				zones,
			} );
		} );
	}

	/**
	 * Re-check APO status (used to verify payment).
	 */
	reCheckAPOStatus() {
		this.setState( { loading: true } );

		this.state.api.post( 'cloudflare_apo_status' ).then( ( response ) => {
			this.setState( {
				loading: false,
				cf: response.cloudflare,
				apo: response.apo,
			} );
		} );
	}

	/**
	 * Disconnect Cloudflare.
	 */
	disconnectCloudflare() {
		this.setState( { loading: true } );

		this.state.api.post( 'cloudflare_disconnect' ).then( ( response ) => {
			this.setState( {
				loading: false,
				cf: response.cloudflare,
				apo: response.apo,
			} );

			window.WPHB_Admin.notices.show(
				__( 'Cloudflare was disconnected successfully.', 'wphb' )
			);
		} );
	}

	/**
	 * Clear Cloudflare cache.
	 */
	clearCache() {
		this.setState( { loading: true } );

		this.state.api.post( 'cloudflare_clear_cache' ).then( () => {
			this.setState( {
				loading: false,
			} );

			window.WPHB_Admin.notices.show(
				__(
					'Cloudflare cache successfully purged. Please wait 30 seconds for the purge to complete.',
					'wphb'
				)
			);
		} );
	}

	/**
	 * Handle toggling APO.
	 *
	 * @param {Object} e
	 */
	handleAPOToggleChange( e ) {
		this.setState( { loading: true } );

		let msg = __( 'Automatic Platform Optimization is disabled.', 'wphb' );
		if ( e.target.checked ) {
			msg = __( 'Automatic Platform Optimization is enabled.', 'wphb' );
		}

		this.state.api
			.post( 'cloudflare_toggle_apo', e.target.checked )
			.then( ( response ) => {
				this.setState( {
					loading: false,
					apo: response,
				} );

				window.WPHB_Admin.notices.show( msg );
			} );
	}

	/**
	 * Handle toggling APO cache by device type.
	 *
	 * @param {Object} e
	 */
	handleDevChkbxChange( e ) {
		this.setState( { loading: true } );

		this.state.api
			.post( 'cloudflare_toggle_device_cache', e.target.checked )
			.then( ( response ) => {
				this.setState( {
					loading: false,
					apo: response,
				} );

				window.WPHB_Admin.notices.show(
					__( 'Settings updated.', 'wphb' )
				);
			} );
	}

	/**
	 * Handle zone select.
	 */
	handleZoneChange() {
		this.setState( {
			selectedZone: jQuery( '#cloudflare-zone-select' )
				.find( ':selected' )
				.text(),
		} );
	}

	/**
	 * Save selected zone.
	 */
	saveSelectedZone() {
		if ( false === this.state.selectedZone ) {
			return;
		}

		this.setState( { loading: true } );

		this.state.api
			.post( 'cloudflare_save_zone', this.state.selectedZone )
			.then( ( response ) => {
				this.setState( {
					loading: false,
					cf: response.cloudflare,
					apo: response.apo,
					zones: [],
					selectedZone: false,
				} );

				window.WPHB_Admin.notices.show(
					__( 'Cloudflare was connected successfully.', 'wphb' )
				);
			} );
	}

	/**
	 * Render accordion header title.
	 *
	 * @return {JSX.Element}  Title element.
	 */
	renderHeaderTitle() {
		const images =
			this.state.links.wphbDirUrl + 'admin/assets/image/integrations/';

		const tooltip = (
			<Tooltip
				text={ __(
					'Cloudflare is connected for this domain.',
					'wphb'
				) }
				data={ <Icon classes="sui-icon-check-tick sui-success" /> }
				classes={ [ 'sui-tooltip-constrained' ] }
			/>
		);

		return (
			<div className="sui-accordion-item-title">
				<img
					className="sui-image"
					alt={ __( 'Cloudflare', 'wphb' ) }
					src={ images + 'icon-cloudflare.png' }
					srcSet={
						images +
						'icon-cloudflare.png 1x, ' +
						images +
						'icon-cloudflare@2x.png 2x'
					}
				/>

				{ __( 'Cloudflare', 'wphb' ) }

				{ this.state.cf.connected && this.state.cf.zone && tooltip }
			</div>
		);
	}

	/**
	 * Render status notice.
	 *
	 * @return {JSX.Element}  Notice.
	 */
	renderStatusNotice() {
		let statusNotice;

		if ( this.state.cf.connected && this.state.cf.zone ) {
			statusNotice = (
				<Notice
					message={ __(
						'Cloudflare is connected for this domain.',
						'wphb'
					) }
					classes="sui-notice-success"
				/>
			);
		} else if ( this.state.cf.connected && ! this.state.cf.zone ) {
			statusNotice = (
				<Notice
					message={ __(
						'Cloudflare is connected, but it appears you don’t have any active zones for this domain. Double check your domain has been added to Cloudflare and tap re-check when ready.',
						'wphb'
					) }
					classes={ [ 'sui-notice-warning', 'sui-no-margin-bottom' ] }
				/>
			);
		} else if ( this.state.cf.dnsSet ) {
			statusNotice = (
				<Notice
					message={ __(
						'We’ve detected you’re using Cloudflare! Connect your account to control your settings via Hummingbird.',
						'wphb'
					) }
					classes="sui-notice-info"
				/>
			);
		} else {
			statusNotice = (
				<Notice
					message={ __( 'Cloudflare is not connected.', 'wphb' ) }
					classes="sui-notice-grey"
				/>
			);
		}

		return statusNotice;
	}

	/**
	 * Render zone dropdown.
	 *
	 * @return {JSX.Element} Zone dropdown.
	 */
	renderZoneDropdown() {
		const zones = this.state.zones.map( ( el ) => {
			return [ el.value, el.label ];
		} );

		// Fix for not working placeholder (pre-selected first value).
		zones.unshift( [ '', '' ] );

		return (
			<React.Fragment>
				<p className="sui-margin-top">
					{ __(
						'If the zone is not auto detected, try selecting one of the available zones from the list below:',
						'wphb'
					) }
				</p>
				<Select
					selectId="cloudflare-zone-select"
					label={ __( 'Cloudflare zone', 'wphb' ) }
					placeholder={ __( 'Select zone', 'wphb' ) }
					items={ zones }
					onChange={ this.handleZoneChange }
				/>
			</React.Fragment>
		);
	}

	/**
	 * Render browser caching options.
	 *
	 * @return {JSX.Element}  Browser caching options.
	 */
	renderBrowserCaching() {
		const type = 31536000 === this.state.cf.expiry ? 'green' : 'yellow';

		const details = this.state.allowModify && (
			<Button
				href={ this.state.links.caching }
				text={ __( 'Configure', 'wphb' ) }
				icon="sui-icon-wrench-tool sui-sm"
			/>
		);

		const expiry = {
			label: (
				<span className="wphb-filename-extension-label">
					{ __( 'JavaScript, CSS, Media, Images', 'wphb' ) }
				</span>
			),
			expiry: <Tag value={ this.state.cf.human } type={ type } />,
			details,
		};

		return (
			<React.Fragment>
				<p>
					{ __(
						'Store temporary data on your visitors devices so that they don’t have to download assets twice if they don’t have to. This results in a much faster second time round page load speed.',
						'wphb'
					) }
				</p>

				<BorderFrame
					header={ [
						__( 'File types', 'wphb' ),
						'',
						__( 'Expiry time', 'wphb' ),
					] }
					elements={ [ expiry ] }
				/>
			</React.Fragment>
		);
	}

	/**
	 * Render Cloudflare APO.
	 *
	 * @return {JSX.Element}  APO settings.
	 */
	renderAPO() {
		let cacheByDeviceType = false;

		if (
			'undefined' !== this.state.apo.settings &&
			'undefined' !== this.state.apo.settings.cache_by_device_type
		) {
			cacheByDeviceType = this.state.apo.settings.cache_by_device_type;
		}

		return (
			<React.Fragment>
				<p>
					{ __(
						"Cloudflare APO will cache dynamic content and third-party scripts so the entire site is served from cache. This eliminates round trips between your server and the user's browser, drastically improving TTFB and other site performance metrics.",
						'wphb'
					) }
				</p>

				<Toggle
					id="cloudflare-apo"
					checked={ this.state.apo.enabled }
					disabled={
						! this.state.apo.purchased || ! this.state.allowModify
					}
					text={ __( 'Enable APO', 'wphb' ) }
					onChange={ this.handleAPOToggleChange }
				/>

				{ this.state.apo.enabled && (
					<div className="sui-border-frame">
						<Checkbox
							id="cloudflare-apo-device"
							label={ __( 'Cache by device type', 'wphb' ) }
							checked={ cacheByDeviceType }
							disabled={
								! this.state.apo.purchased ||
								! this.state.allowModify
							}
							onChange={ this.handleDevChkbxChange }
							description={ __(
								'This enables you to target visitors with cached content appropriate to their device. Once enabled, Cloudflare sends a CF-Device-Type HTTP header to your origin page with a value of either mobile, tablet or desktop for every request to specify the visitor’s device type. If your origin page responds with the appropriate content for that device type, Cloudflare caches the resource only for that specific device type. Note: changing the Cache by device type setting will purge the entire Couldflare cache for your zone.', 'wphb'
							) }
						/>
					</div>
				) }

				{ ! this.state.apo.purchased && (
					<Notice
						message={ sprintf(
							/* translators: %1$s - opening a tag, %2$s - closing a tag */
							__(
								'Automatic Platform Optimization is a paid service and you need to purchase it to enable it. You can purchase it %1$shere%2$s.',
								'wphb'
							),
							'<a href="https://dash.cloudflare.com/' +
								this.state.cf.accountId +
								'/' +
								this.state.cf.zoneName +
								'/speed/optimization/apo/purchase" target="_blank">',
							'</a>'
						) }
					/>
				) }
			</React.Fragment>
		);
	}

	/**
	 * Render content.
	 *
	 * @return {JSX.Element} Content.
	 */
	renderContent() {
		const notice = sprintf(
			/* translators: %1$s - opening a tag, %2$s - closing a tag */
			__(
				'Cloudflare is a Content Delivery Network (CDN) that sends traffic through its global network to automatically optimize the delivery of your site so your visitors can browse your site at top speeds. With the new Automatic Platform Optimization (APO), Cloudflare can also cache dynamic content and third-party scripts so the entire site is served from cache. Learn more about the integration %1$shere%2$s.',
				'wphb'
			),
			'<a href="https://wpmudev.com/docs/wpmu-dev-plugins/hummingbird/#browser-cache" target="_blank">',
			'</a>'
		);

		return (
			<React.Fragment>
				<h4>{ __( 'Overview', 'wphb' ) }</h4>
				<p dangerouslySetInnerHTML={ { __html: notice } } />

				<h4>{ __( 'Status', 'wphb' ) }</h4>

				{ this.renderStatusNotice() }

				{ 0 < this.state.zones.length && this.renderZoneDropdown() }

				{ this.state.cf.connected && this.state.cf.zone && (
					<SettingsRow
						label={ __( 'Browser Caching', 'wphb' ) }
						content={ this.renderBrowserCaching() }
						wide="true"
					/>
				) }

				{ this.state.cf.connected && this.state.cf.zone && (
					<SettingsRow
						label={ __(
							'Automatic Platform Optimization',
							'wphb'
						) }
						content={ this.renderAPO() }
						wide="true"
					/>
				) }
			</React.Fragment>
		);
	}

	/**
	 * Render footer.
	 *
	 * @return {JSX.Element}  Footer actions.
	 */
	renderFooter() {
		if ( ! this.state.allowModify ) {
			return null;
		}

		return (
			<React.Fragment>
				{ this.state.cf.connected && (
					<Button
						classes={ [ 'sui-button', 'sui-button-ghost' ] }
						icon="sui-icon-power-on-off"
						text={ __( 'Deactivate', 'wphb' ) }
						onClick={ this.disconnectCloudflare }
					/>
				) }

				{ this.state.cf.connected && this.state.cf.zone && (
					<Button
						classes={ [ 'sui-button', 'sui-button-ghost' ] }
						icon="sui-icon-update"
						text={ __( 'Re-check status', 'wphb' ) }
						onClick={ this.reCheckAPOStatus }
					/>
				) }

				<div className="sui-actions-right">
					{ this.state.cf.connected &&
						! this.state.cf.zone &&
						! this.state.selectedZone && (
							<Button
								type="button"
								icon="sui-icon-update"
								classes="sui-button"
								text={ __( 'Re-check', 'wphb' ) }
								onClick={ this.reCheckStatus }
							/>
						) }

					{ this.state.cf.connected &&
						! this.state.cf.zone &&
						this.state.selectedZone && (
							<Button
								type="button"
								classes="sui-button"
								text={ __( 'Save zone', 'wphb' ) }
								onClick={ this.saveSelectedZone }
							/>
						) }

					{ ! this.state.cf.connected && (
						<Button
							type="button"
							classes={ [ 'sui-button', 'sui-button-blue' ] }
							text={ __( 'Connect', 'wphb' ) }
							aria-label={ __( 'Connect', 'wphb' ) }
							onClick={ this.openConnectModal }
						/>
					) }

					{ this.state.cf.connected && this.state.cf.zone && (
						<Button
							type="button"
							classes="sui-button"
							text={ __( 'Clear cache', 'wphb' ) }
							onClick={ this.clearCache }
						/>
					) }
				</div>
			</React.Fragment>
		);
	}

	/**
	 * Render component.
	 *
	 * @return {*} Integrations.
	 */
	render() {
		return (
			<React.Fragment>
				<div className="sui-accordion-item-header">
					{ this.renderHeaderTitle() }
					<div className="wphb-integration-description">
						{ __(
							'Connect your Cloudflare account to control APO and Browser Caching directly from Hummingbird.',
							'wphb'
						) }
					</div>
					<div className="sui-accordion-col-auto">
						{ ! this.state.cf.connected && (
							<Button
								type="button"
								classes={ [
									'sui-button-icon',
									'sui-button-blue',
									'sui-accordion-item-action',
									'sui-tooltip',
								] }
								icon="sui-icon-plus"
								aria-label={ __(
									'Connect Cloudflare',
									'wphb'
								) }
								data-tooltip={ __(
									'Connect Cloudflare',
									'wphb'
								) }
								onClick={ this.openConnectModal }
							/>
						) }
						<Button
							type="button"
							classes={ [
								'sui-button-icon',
								'sui-accordion-open-indicator',
							] }
							icon="sui-icon-chevron-down"
							aria-label={ __( 'Open item', 'wphb' ) }
						/>
					</div>
				</div>
				<div className="sui-accordion-item-body">
					<Box
						loading={ this.state.loading }
						hideHeader="true"
						content={ this.renderContent() }
						footerActions={ this.renderFooter() }
					/>
				</div>
			</React.Fragment>
		);
	}
}

Integrations.propTypes = {
	wphbData: PropTypes.object,
};

domReady( function () {
	const cloudflareDiv = document.getElementById( 'wphb-react-cloudflare' );
	if ( cloudflareDiv ) {
		ReactDOM.render(
			/*** @var {object} window.wphb */
			<Integrations wphbData={ window.wphbReact } />,
			cloudflareDiv
		);
	}
} );
