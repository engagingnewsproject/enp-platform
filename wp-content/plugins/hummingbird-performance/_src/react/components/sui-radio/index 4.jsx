/**
 * External dependencies
 */
import React from 'react';
import classNames from 'classnames';

/**
 * Radio functional component.
 *
 * @param {string} message  Notice message.
 * @return {*} Radio component.
 * @class
 */
export default function Radio( { text, id, name, value, checked = false, onChange } ) {
	return (
		<label
			id={ id + '-label' }
			htmlFor={ id }
			className={ classNames( 'sui-tab-item', { active: checked } ) }
		>
			<input
				type="radio"
				name={ name }
				value={ value }
				id={ id }
				checked={ checked }
				onChange={ onChange }
			/>
			{ text }
		</label>
	);
}
