/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import './style.scss';
import Icon from '../sui-icon';

export default function ProgressBar( { progress = 0, status = '' } ) {
	return (
		<React.Fragment>
			<div className="sui-progress-block">
				<div className="sui-progress">
					<span className="sui-progress-icon" aria-hidden="true">
						<Icon classes="sui-icon-loader sui-loading" />
					</span>
					<span className="sui-progress-text">{ progress + '%' }</span>
					<div className="sui-progress-bar" aria-hidden="true">
						<span style={ { width: progress + '%' } }></span>
					</div>
				</div>
			</div>
			{ status &&
				<div className="sui-progress-state">
					<span>{ status }</span>
				</div> }
		</React.Fragment>
	);
}
