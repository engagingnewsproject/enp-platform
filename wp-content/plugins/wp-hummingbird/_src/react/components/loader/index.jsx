/**
 * External dependencies
 */
import React from 'react';
import classNames from 'classnames';

/**
 * WordPress dependencies
 */
const { __ } = wp.i18n;

/**
 * Internal dependencies
 */
import './style.scss';
import Icon from '../sui-icon';

/**
 * Loader component.
 *
 * @param {Object}  props         Component props.
 * @param {boolean} props.loading
 * @param {string}  props.text
 * @return {*} Loader component.
 * @class
 */
export default function Loader( { loading, text } ) {
	let loadingText = __( 'Fetching latest data...', 'wphb' );

	if ( text ) {
		loadingText = text;
	}

	return (
		<div
			className={ classNames( 'wphb-loading-overlay', {
				'wphb-loading': loading,
			} ) }
		>
			<Icon classes="sui-icon-loader sui-loading" />
			<p>
				{ loadingText }
			</p>
		</div>
	);
}
