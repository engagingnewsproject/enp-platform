/**
 * External dependencies
 */
import React from 'react';
import classNames from 'classnames';

/**
 * Button functional component.
 *
 * @param {Object}             props          Component props.
 * @param {string}             props.text     Button text.
 * @param {string}             props.url      URL link.
 * @param {Array}              props.classes  Button class.
 * @param {string}             props.id       Button ID.
 * @param {string|JSX.Element} props.icon     SUI icon class.
 * @param {string}             props.target   Target __blank?
 * @param {boolean}            props.disabled Disabled or not.
 * @param {*}                  props.onClick  onClick callback.
 * @param {string}             props.type     Link or button.
 * @return {JSX.Element} Button component.
 * @class
 */
export default function Button( {
	text,
	url = '#',
	classes,
	id,
	icon,
	target,
	disabled = false,
	onClick,
	type = 'link',
	...props
} ) {
	if ( icon ) {
		icon = <span className={ icon } aria-hidden="true" />;
	}

	if ( 'button' === type ) {
		return (
			<button
				className={ classNames( classes ) }
				id={ id }
				disabled={ disabled }
				onClick={ onClick }
				{ ...props }
			>
				{ ! window.lodash.includes( classes, 'sui-button-icon-right' ) && icon }
				{ text }
				{ window.lodash.includes( classes, 'sui-button-icon-right' ) && icon }
			</button>
		);
	}

	let rel;
	if ( 'blank' === target ) {
		rel = 'noopener noreferrer';
	}

	return (
		<a
			className={ classNames( classes ) }
			href={ url }
			id={ id }
			target={ target }
			rel={ rel }
			disabled={ disabled }
			onClick={ onClick }
			{ ...props }
		>
			{ ! window.lodash.includes( classes, 'sui-button-icon-right' ) && icon }
			{ text }
			{ window.lodash.includes( classes, 'sui-button-icon-right' ) && icon }
		</a>
	);
}
