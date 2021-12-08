/**
 * Internal dependencies
 */
import Fetcher from '../js/utils/fetcher';
import { NoticeBlack } from '@wpmudev/shared-notifications-black-friday';

/**
 * External dependencies
 */
import React from 'react';
import ReactDOM from 'react-dom';

/**
 * WordPress dependencies
 */
import domReady from '@wordpress/dom-ready';

/**
 * Hide notice.
 *
 * @since 3.1.3
 */
function hideNotice() {
	Fetcher.common.call( 'wphb_hide_black_friday' );
}

/**
 * Render the "Black Friday" component.
 *
 * @since 3.1.3
 */
domReady( function() {
	const blackFridayDiv = document.getElementById( 'wphb-black-friday' );
	if ( blackFridayDiv ) {
		ReactDOM.render(
			<NoticeBlack
				link={ window.wphbBF.link }
				onCloseClick={ hideNotice }
			>
				<p>
					<strong>{ window.wphbBF.header }</strong>{ ' ' }
					{ window.wphbBF.message }
				</p>
				<p>
					<small>{ window.wphbBF.notice }</small>
				</p>
			</NoticeBlack>,
			blackFridayDiv
		);
	}
} );
