/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies.
 */
import Radio from '../sui-radio';

/**
 * SideTabs component.
 *
 * @param {Object} tabs  Tabs.
 * @return {JSX.Element}  SuiTabs component.
 * @class
 */
export default function SideTabs( { tabs } ) {
	const items = Object.values( tabs ).map( ( el, id ) => {
		return (
			<Radio
				text={ el.title }
				id={ el.id }
				name={ el.name }
				value={ el.value }
				checked={ 'undefined' !== typeof el.checked && el.checked }
				key={ id }
				onChange={ el.onChange }
			/>
		);
	} );

	return (
		<div className="sui-side-tabs">
			<div className="sui-tabs-menu">{ items }</div>
		</div>
	);
}
