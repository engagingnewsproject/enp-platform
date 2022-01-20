/**
 * External dependencies
 */
import React from 'react';
import classNames from 'classnames';

/**
 * Tooltip component.
 *
 * @param {string}          text     Tooltip text.
 * @param {React.Component} data     Should the tooltip wrap around a component.
 * @param {Array}           classes  Tooltip classes.
 * @return {*} Tooltip component.
 * @class
 */
export default function Tooltip( { text, data, classes } ) {
	const combinedClasses = classNames( 'sui-tooltip', classes );

	return (
		<span className={ combinedClasses } data-tooltip={ text }>
			{ data }
		</span>
	);
}
