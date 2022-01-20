/**
 * External dependencies
 */
import React from 'react';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import './style.scss';

/**
 * Functional BorderFrame component.
 *
 * @param {Object} elements
 * @param {Object} header
 * @param {Array} extraClasses Extra classes.
 *
 * @return {*} List component.
 *
 * @class
 */
export default function BorderFrame( { elements, header, extraClasses } ) {
	const items = Object.values( elements ).map( ( element, id ) => {
		return (
			<div className="table-row" key={ id }>
				<div className="wphb-caching-summary-item-type">
					{ element.label }
				</div>
				{ ! window.lodash.includes( extraClasses, 'two-columns' ) && (
					<div className="wphb-caching-summary-item-expiry">
						{ element.expiry }
					</div>
				) }
				<div>{ element.details }</div>
			</div>
		);
	} );

	return (
		<div className={ classNames( 'wphb-border-frame', extraClasses ) }>
			{ header && (
				<div className="table-header">
					<div className="wphb-caching-summary-heading-type">
						{ header[ 0 ] }
					</div>
					{ ! window.lodash.includes( extraClasses, 'two-columns' ) && (
						<div className="wphb-caching-summary-heading-expiry">
							{ header[ 2 ] }
						</div>
					) }
					<div className="wphb-caching-summary-heading-status">
						{ header[ 1 ] }
					</div>
				</div>
			) }
			{ items }
		</div>
	);
}
