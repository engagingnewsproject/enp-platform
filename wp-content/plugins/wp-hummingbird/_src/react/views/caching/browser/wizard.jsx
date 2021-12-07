/**
 * External dependencies
 */
import React from 'react';

/**
 * WordPress dependencies
 */
const { __ } = wp.i18n;

/**
 * Internal dependencies
 */
import Box from '../../../components/sui-box';
import StepsBar from '../../../components/steps-bar';
import StepsContent from '../../../components/steps-content';
import Button from '../../../components/sui-button';
import ServerInstructions from './server-instructions';
import ButtonLoading from '../../../components/sui-button-loading';

/**
 * Wizard component.
 *
 * @since 3.2.0
 */
class Wizard extends React.Component {
	/**
	 * Component constructor.
	 *
	 * @param {Object} props
	 */
	constructor( props ) {
		super( props );

		const step = 'cloudflare' === this.props.detectedServer ? 2 : 1;

		this.state = {
			loading: false,
			step,
			server: this.props.detectedServer,
		};

		this.onServerChange = this.onServerChange.bind( this );
	}

	/**
	 * Catch component updates.
	 *
	 * @param {Object} prevProps Props from previous update.
	 * @param {Object} prevState State from previous update.
	 */
	componentDidUpdate( prevProps, prevState ) {
		// Avoid duplicate calls.
		if ( 3 === prevState.step ) {
			return;
		}

		if ( 3 !== this.state.step || false === this.state.loading ) {
			return;
		}

		this.props.saveExpiryRules( this.state.server ).then( ( response ) => {
			// If rules applied - exit wizard.
			if (
				( 'apache' === this.state.server || 'litespeed' === this.state.server || 'cloudflare' === this.state.server ) &&
				'undefined' !== typeof response.htaccessUpdated &&
				true === response.htaccessUpdated
			) {
				/**
				 * We can revert location.reload() back to onHideWizard(), but
				 * we need to update status in summary meta box and left menu.
				 */
				//this.props.onHideWizard();
				location.reload();
				return;
			}

			// Else - step 3.
			this.setState( { loading: false, step: 3 } );
		} );
	}

	/**
	 * Update step.
	 *
	 * @param {string} type Go to next/previous step. Accepts: next, prev.
	 */
	setStep( type ) {
		let step = 1;

		if ( 'next' === type ) {
			step = this.state.step + 1;

			// Skip second step for IIS servers.
			if ( 2 === step && 'iis' === this.state.server ) {
				step = 3;
			}
		}

		if ( 'prev' === type && this.state.step > 1 ) {
			step = this.state.step - 1;

			// Skip second step for IIS servers.
			if ( 2 === step && 'iis' === this.state.server ) {
				step = 1;
			}
		}

		this.setState( { loading: 3 === step, step } );
	}

	/**
	 * Set selected server.
	 *
	 * @param {string} server Server ID.
	 */
	onServerChange( server ) {
		this.setState( { server } );
	}

	/**
	 * Get content.
	 *
	 * @return {JSX.Element}  Content.
	 */
	getContent() {
		return (
			<div className="sui-row-with-sidenav">
				<StepsBar currentStep={ this.state.step } />
				<StepsContent
					applyingRules={ this.state.loading }
					currentStep={ this.state.step }
					data={ this.props.data }
					status={ this.props.status }
					expires={ this.props.expires }
					human={ this.props.human }
					onServerChange={ this.onServerChange }
					onExpiryChange={ this.props.onExpiryChange }
					server={ this.state.server }
					cloudflare={ this.props.cloudflare }
					snippet={ <ServerInstructions currentServer={ this.state.server } snippets={ this.props.snippets } /> }
				/>
			</div>
		);
	}

	/**
	 * Get footer actions.
	 *
	 * @return {JSX.Element}  Footer.
	 */
	getFooter() {
		return (
			<React.Fragment>
				<Button
					type="button"
					classes={ [ 'sui-button', 'sui-button-ghost' ] }
					icon="sui-icon-logout"
					text={ __( 'Quit Setup', 'wphb' ) }
					onClick={ this.props.onHideWizard }
					disabled={ this.state.loading }
				/>

				{ this.state.step > 1 &&
					<Button
						type="button"
						classes={ [ 'sui-button', 'sui-button-ghost' ] }
						icon="sui-icon-arrow-left"
						text={ __( 'Previous', 'wphb' ) }
						onClick={ () => this.setStep( 'prev' ) }
						disabled={ this.state.loading }
					/>
				}

				<div className="sui-actions-right">
					{ this.state.step < 3 &&
						<Button
							type="button"
							classes={ [ 'sui-button', 'sui-button-blue', 'sui-button-icon-right' ] }
							icon="sui-icon-arrow-right"
							text={ __( 'Next', 'wphb' ) }
							onClick={ () => this.setStep( 'next' ) }
							disabled={ this.state.loading }
						/>
					}
					{ this.state.step === 3 &&
						<ButtonLoading
							classes={ [ 'sui-button', 'sui-button-blue' ] }
							text={ __( 'Check Status', 'wphb' ) }
							onClick={ this.props.onRecheckStatus }
							loading={ this.state.loading }
							loadingText={ __( 'Applying...', 'wphb' ) }
						/>
					}
				</div>
			</React.Fragment>
		);
	}

	/**
	 * Render component.
	 *
	 * @return {*} Status component.
	 */
	render() {
		return (
			<Box
				loading={ this.props.loading }
				content={ this.getContent() }
				hideHeader="true"
				boxBodyClass="sui-no-padding"
				footerActions={ this.getFooter() }
			/>
		);
	}
}

export default Wizard;
