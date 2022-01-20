/**
 * External dependencies
 */
import React from 'react';

/**
 * Build a tag object based on the number of issues.
 * If no issues are present ( 0 === value ), show the success tick icon.
 *
 * @param {number} value  Number of issues.
 * @param {string} type   Class to use when there are issues. Accepts: warning, error, success, info, etc.
 * @return {*} Tag component.
 * @class
 */
export default function Tag( { value, type } ) {
	if ( 0 === value && 'success' === type ) {
		return (
			<span
				className="sui-icon-check-tick sui-lg sui-success"
				aria-hidden="true"
			/>
		);
	}

	const classes = 'sui-tag sui-tag-' + type;

	return <span className={ classes }>{ value }</span>;
}

// Set default props
Tag.defaultProps = {
	value: 0,
	type: 'success',
};
