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
 * @param {boolean} loading
 * @return {*} Loader component.
 * @class
 */
export default function Loader( { loading } ) {
	return (
		<div
			className={ classNames( 'wphb-loading-overlay', {
				'wphb-loading': loading,
			} ) }
		>
			<Icon classes="sui-icon-loader sui-loading" />
			<p>{ __( 'Fetching latest data...', 'wphb' ) }</p>
		</div>
	);
}
