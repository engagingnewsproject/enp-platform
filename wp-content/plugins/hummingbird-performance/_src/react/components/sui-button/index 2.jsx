/**
 * External dependencies
 */
import React from 'react';
import classNames from 'classnames';

/**
 * Button functional component.
 *
 * @param {string}             text      Button text.
 * @param {string}             url       URL link.
 * @param {Array}              classes   Button class.
 * @param {string}             id        Button ID.
 * @param {string|JSX.Element} icon      SUI icon class.
 * @param {string}             target    Target __blank?
 * @param {boolean}            disabled  Disabled or not.
 * @param {*}                  onClick   onClick callback.
 * @param {string}             type      Link or button.
 * @return {*} Button component.
 * @class
 */
export default function Button( {
	text,
	url,
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
				{ icon }
				{ text }
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
			{ icon }
			{ text }
		</a>
	);
}
