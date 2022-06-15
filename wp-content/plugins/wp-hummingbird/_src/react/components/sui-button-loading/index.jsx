/**
 * External dependencies
 */
import React from 'react';
import classNames from 'classnames';
import Icon from '../sui-icon';

/**
 * ButtonLoading functional component.
 *
 * @since 3.2.0
 *
 * @param {Object}             props             Component props.
 * @param {string}             props.text        Button text.
 * @param {string}             props.loadingText Loading text.
 * @param {Array}              props.classes     Button class.
 * @param {string|JSX.Element} props.icon        SUI icon class.
 * @param {boolean}            props.loading     Loading status.
 * @param {*}                  props.onClick     onClick callback.
 * @return {JSX.Element} ButtonLoading component.
 * @class
 */
export default function ButtonLoading( {
	text,
	classes,
	icon,
	onClick,
	loading = false,
	loadingText
} ) {
	return (
		<button
			className={ classNames( 'sui-button', classes, { 'sui-button-onload-text': loading && loadingText }, { 'sui-button-onload': loading && ! loadingText } ) }
			onClick={ onClick }
			aria-live="polite"
		>
			{ loadingText &&
				<span className="sui-button-text-default">
					{ icon && <span className={ icon } aria-hidden="true" /> }
					{ text }
				</span> }

			{ ! loadingText &&
				<span className="sui-loading-text">{ text }</span> }

			{ ! loadingText && <Icon classes="sui-icon-loader sui-loading" /> }
			{ loadingText &&
				<span className="sui-button-text-onload">
					<Icon classes="sui-icon-loader sui-loading" />
					{ loadingText }
				</span>
			}
		</button>
	);
}
