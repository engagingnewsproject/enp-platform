/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import './style.scss';

/**
 * OrderedList component.
 *
 * @since 3.2.0
 *
 * @param {Object} props      Component props.
 * @param {Array}  props.list Array of list entries.
 * @return {JSX.Element}  Component.
 */
export default function OrderedList( { list } ) {
	const items = list.map( ( element, id ) => {
		return (
			<li key={ id }>
				{ element }
			</li>
		);
	} );

	return (
		<ol className="wphb-ordered-list">
			{ items }
		</ol>
	);
}
