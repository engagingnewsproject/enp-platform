/* global WPHB_Admin */

/**
 * External dependencies
 */
import React from 'react';
import ReactDOM from 'react-dom';

/**
 * WordPress dependencies
 */
import domReady from '@wordpress/dom-ready';
const { __, sprintf } = wp.i18n;

/**
 * Internal dependencies
 */
import { TutorialsSlider, TutorialsList } from '@wpmudev/react-tutorials';
import { getLink } from '../../js/utils/helpers';
import HBAPIFetch from '../api';

function hideTutorials() {
	const fetch = new HBAPIFetch();
	fetch.post( 'hide_tutorials' );

	WPHB_Admin.notices.show(
		sprintf(
			/* translators: %1$s - opening a tag, %2$s - closing a tag */
			__(
				'The widget has been removed. Hummingbird tutorials can still be found in the %1$sTutorials tab%2$s any time.', 'wphb'
			),
			'<a href=' + getLink( 'tutorials' ) + '>',
			'</a>'
		),
		'success',
		false
	);
}

domReady( function () {
	// Tutorials section on Dashboard page.
	const tutorialsDiv = document.getElementById( 'wphb-dashboard-tutorials' );
	if ( tutorialsDiv ) {
		ReactDOM.render(
			<TutorialsSlider
				category="11234"
				title={ __( 'Tutorials', 'wphb' ) }
				viewAll="https://wpmudev.com/blog/tutorials/tutorial-category/hummingbird-pro/"
				onCloseClick={ hideTutorials }
			/>,
			tutorialsDiv
		);
	}

	// Tutorials page.
	const tutorialsListDiv = document.getElementById( 'wrap-wphb-tutorials' );
	if ( tutorialsListDiv ) {
		ReactDOM.render(
			<TutorialsList
				category="11234"
				title={ __( 'Hummingbird Tutorials', 'wphb' ) }
			/>,
			tutorialsListDiv
		);
	}
} );
