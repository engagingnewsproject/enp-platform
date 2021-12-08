/**
 * External dependencies
 */
import React from 'react';
import classNames from 'classnames';

/**
 * Functional SettingsRow (sui-box-settings-row) component.
 *
 * @param {string} label
 * @param {string} description
 * @param {Object} content
 * @param {string} classes
 * @param {boolean} wide
 * @return {*} SettingsRow component.
 * @class
 */
export default function SettingsRow( {
	label,
	description,
	content,
	classes,
	wide = false,
} ) {
	// Wide layout.
	if ( wide ) {
		return (
			<div className="sui-box-settings-row">
				<div className="sui-box-settings-col-2">
					<span className="sui-settings-label">{ label }</span>
					{ content }
				</div>
			</div>
		);
	}

	if ( description ) {
		return (
			<div className="sui-box-settings-row">
				<div className="sui-box-settings-col-1">
					<span className="sui-settings-label">{ label }</span>
					<span className="sui-description">{ description }</span>
				</div>
				<div className="sui-box-settings-col-2">{ content }</div>
			</div>
		);
	}

	return (
		<div className={ classNames( 'sui-box-settings-row', classes ) }>
			{ content }
		</div>
	);
}
