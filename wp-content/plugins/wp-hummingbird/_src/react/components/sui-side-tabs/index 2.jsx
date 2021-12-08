/**
 * External dependencies
 */
import React from 'react';
import classNames from 'classnames';

/**
 * Internal dependencies.
 */
import Button from '../sui-button';

/**
 * SideTabs component.
 *
 * @deprecated 3.2.0 In favour of sui-tabs component, which covers the same functionality.
 *
 * @param {Object} props      Component props.
 * @param {Object} props.tabs Tabs.
 * @return {JSX.Element}  SuiTabs component.
 */
export default function SideTabs( { tabs } ) {
	const tabItems = Object.values( tabs ).map( ( el, id ) => {
		return (
			<Button
				type="button"
				role="tab"
				key={ id }
				id={ el.id }
				class={ classNames( 'sui-tab-item', {
					active: 'undefined' !== typeof el.active && el.active,
				} ) }
				aria-controls={ 'tab-content-' + el.id }
				aria-selected={ 'undefined' !== typeof el.active && el.active }
				text={ el.title }
				onClick={ el.onClick }
			/>
		);
	} );

	return (
		<div className="sui-side-tabs">
			<div role="tablist" className="sui-tabs-menu">{ tabItems }</div>
		</div>
	);
}
