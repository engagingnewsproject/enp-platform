/**
 * External dependencies
 */
import React from 'react';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import Icon from '../sui-icon';

/**
 * Notice functional component.
 *
 * @param {string} message  Notice message.
 * @param {Array}  classes  Array of extra classes to use.
 * @param {Object} content  CTA content.
 * @return {*} Notice component.
 * @class
 */
export default function Notice( { message, classes, content } ) {
	const combinedClasses = classNames( 'sui-notice', classes );

	return (
		<div className={ combinedClasses }>
			<div className="sui-notice-content">
				<div className="sui-notice-message">
					<Icon classes="sui-notice-icon sui-icon-info sui-md" />
					{ message && (
						<p dangerouslySetInnerHTML={ { __html: message } } />
					) }
					{ content && <p>{ content }</p> }
				</div>
			</div>
		</div>
	);
}
