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
import Icon from '../sui-icon';
import './style.scss';

/**
 * StepsBar component.
 *
 * @since 3.2.0
 *
 * @param {number} currentStep Current step.
 * @return {JSX.Element}  Component.
 */
export default function StepsBar( { currentStep } ) {
	const steps = [
		{ number: 1, title: __( 'Server Type', 'wphb' ) },
		{ number: 2, title: __( 'Set Expiry', 'wphb' ) },
		{ number: 3, title: __( 'Add Rules', 'wphb' ) },
	];

	const completeTooltip = __( 'This stage is already completed.', 'wphb' );

	const getStepClass = ( step ) => {
		if ( step > currentStep ) {
			return 'wizard-bar-step';
		}

		return step === currentStep ? 'wizard-bar-step current' : 'wizard-bar-step sui-tooltip done';
	};

	const getStepNumber = ( step ) => {
		return currentStep > step ? <Icon classes="sui-icon-check" /> : step;
	};

	return (
		<div className="sui-sidenav">
			<span className="wizard-bar-subtitle">
				{ __( 'Setup', 'wphb' ) }
			</span>
			<div className="wizard-bar-title">
				<h4>{ __( 'Browser Caching', 'wphb' ) }</h4>
			</div>

			<div className="wizard-steps-container">
				<svg className="svg-mobile" focusable="false" aria-hidden="true">
					<line x1="0" x2="50%"
						stroke={ 1 !== currentStep ? '#1ABC9C' : '#E6E6E6' }
					/>
					<line x1="50%" x2="100%"
						stroke={ 3 === currentStep ? '#1ABC9C' : '#E6E6E6' }
					/>
				</svg>
				<ul>
					{ steps.map( ( step ) => (
						<React.Fragment key={ step.number }>
							<li
								className={ getStepClass( step.number ) }
								data-tooltip={ completeTooltip }
							>
								<div className="wizard-bar-step-number">
									{ getStepNumber( step.number ) }
								</div>
								{ step.title }
							</li>
							{ 3 !== step.number && (
								<svg className="svg-desktop" focusable="false" aria-hidden="true">
									<line y1="0" y2="40px"
										stroke={ step.number < currentStep ? '#1ABC9C' : '#E6E6E6' }
									/>
								</svg>
							) }
						</React.Fragment>
					) ) }
				</ul>
			</div>
		</div>
	);
}
