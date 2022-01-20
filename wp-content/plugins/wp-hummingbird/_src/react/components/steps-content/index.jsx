/**
 * External dependencies
 */
import React from 'react';

/**
 * WordPress dependencies
 */
const { __, sprintf } = wp.i18n;

/**
 * Internal dependencies
 */
import './style.scss';
import Notice from '../sui-notice';
import Tabs from '../sui-tabs';
import Select from '../sui-select';
import Icon from '../sui-icon';

/**
 * StepsContent component.
 *
 * @since 3.2.0
 */
export default class StepsContent extends React.Component {
	/**
	 * Component constructor.
	 *
	 * @param {Object} props
	 */
	constructor( props ) {
		super( props );

		const servers = [ 'Apache', 'NGINX', 'IIS', 'Cloudflare', 'Open LiteSpeed' ];
		// Remove Cloudflare from list if not set up.
		if ( ! this.props.cloudflare.isAuthed && ! this.props.cloudflare.isAuthed.isSetup ) {
			servers.splice( 3, 1 );
		}

		this.state = { servers };
	}

	/**
	 * Get step description.
	 *
	 * @param {number} step Step.
	 * @return {Object}  Step details.
	 */
	getStep( step ) {
		const steps = {
			1: {
				title: __( 'Choose Server Type', 'wphb' ),
				description: __(
					'Choose your server type. If you don’t know this, please contact your hosting provider.',
					'wphb'
				),
			},
			2: {
				title: __( 'Set Expiry Time', 'wphb' ),
				description: __(
					'Please choose your desired expiry time. Google recommends a minimum of 1 year as a good benchmark.',
					'wphb'
				),
			},
			3: {
				title: __( 'Add Rules', 'wphb' ),
				description:
					'apache' === this.props.server
						? __(
							'Hummingbird can automatically apply browser caching rules for Apache servers by writing to your .htaccess file.',
							'wphb'
						)
						: __(
							'Please follow the steps below to apply the rules yourself:',
							'wphb'
						),
			},
		};

		return steps[ step ];
	}

	/**
	 * Get content (step 1).
	 *
	 * @return {JSX.Element}  Step 1 content.
	 */
	getStepOne() {
		const liElements = this.state.servers.map( ( el, key ) => {
			let server = el.toLowerCase();
			if ( 'open litespeed' === server ) {
				server = 'litespeed';
			}

			const serverID = 'server-' + server;
			return (
				<li key={ key }>
					<label htmlFor={ serverID } className="sui-box-selector">
						<input
							id={ serverID }
							type="radio"
							checked={ server === this.props.server }
							onChange={ () => this.props.onServerChange( server ) }
						/>
						<span>{ el }</span>
					</label>
				</li>
			);
		} );

		const notice = sprintf( /* translators: server type */
			__(
				"We've automatically detected your server type is %s. If this is incorrect, manually select your server type to generate the relevant rules and instructions.",
				'wphb'
			),
			'nginx' === this.props.data.detectedServer ? 'NGINX' : 'Apache / LiteSpeed'
		);

		return (
			<React.Fragment>
				<div className="sui-box-selectors sui-box-selectors-col-3">
					<ul>
						{ liElements }
					</ul>
				</div>
				{ ( 'apache' === this.props.server || 'litespeed' === this.props.server || 'nginx' === this.props.server ) &&
					<Notice message={ notice } />
				}
			</React.Fragment>
		);
	}

	/**
	 * Get content (step 2).
	 *
	 * @return {JSX.Element}  Step 2 content.
	 */
	getStepTwo() {
		const tabs = [
			{
				title: __( 'All file types', 'wphb' ),
				id: 'expiry-all',
				checked: true,
			},
			{
				title: __( 'Individual file types', 'wphb' ),
				id: 'expiry-single',
			},
		];

		let frequencies;
		if ( 'cloudflare' === this.props.server ) {
			frequencies = Object.entries( this.props.data.frequenciesCF );
		} else {
			frequencies = Object.entries( this.props.data.frequencies );
		}

		const singleSelect = (
			<Select
				selectId="set-expiry-all"
				label={ __( 'JavaScript, CSS, Media, Images', 'wphb' ) }
				items={ frequencies }
				selected={ 'cloudflare' === this.props.server ? this.props.status.CSS.toString() : this.props.expires.CSS }
				onChange={ this.props.onExpiryChange }
			/>
		);

		if ( 'cloudflare' === this.props.server ) {
			return (
				<div className="sui-border-frame">
					{ singleSelect }
				</div>
			);
		}

		const multiSelect = Object.entries( this.props.human ).map( ( item, index ) => {
			return (
				<Select
					selectId={ 'set-expiry-' + item[ 0 ].toLowerCase() }
					label={ item[ 0 ] }
					items={ frequencies }
					key={ index }
					selected={ this.props.status[ item[ 0 ] ].toString() }
					onChange={ this.props.onExpiryChange }
				/>
			);
		} );

		const content = [
			{
				id: 'expiry-all',
				content: singleSelect,
				active: true,
			},
			{
				id: 'expiry-single',
				content: multiSelect,
			},
		];

		return (
			<Tabs menu={ tabs } tabs={ content } sideTabs="true" />
		);
	}

	/**
	 * Render component.
	 *
	 * @return {JSX.Element}  Component.
	 */
	render() {
		const stepIndicatorText = sprintf(
			/* translators: %d - current step */
			__( 'Step %d/3', 'wphb' ),
			this.props.currentStep
		);

		return (
			<div className="wizard-steps-content-wrapper">
				<div className="wizard-steps-content">
					<span className="step-indicator">
						{ stepIndicatorText }
					</span>
					<h2>{ this.getStep( this.props.currentStep ).title }</h2>
					<p className="sui-description">
						{ this.getStep( this.props.currentStep ).description }
					</p>

					{ this.props.applyingRules &&
						<p className="sui-description wphb-loading-text">
							<Icon classes="sui-icon-loader sui-loading sui-md" />
							{ __( 'Applying rules', 'wphb' ) }
						</p>
					}

					{ 1 === this.props.currentStep && ! this.props.applyingRules && this.getStepOne() }
					{ 2 === this.props.currentStep && ! this.props.applyingRules && this.getStepTwo() }
					{ 3 === this.props.currentStep && ! this.props.applyingRules && this.props.snippet }
				</div>
			</div>
		);
	}
}

