/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import './style.scss';
import Icon from '../sui-icon';
import Loader from '../loader';

/**
 * Box component.
 */
export default class Box extends React.Component {
	/**
	 * Generate header.
	 *
	 * @param {string} title          Box title.
	 * @param {string} icon           Icon name to use, false for no icon.
	 * @param {Action} headerActions  Action component.
	 * @return {*} Box header.
	 */
	static boxHeader( title = '', icon = '', headerActions = null ) {
		return (
			<React.Fragment>
				{ ( title || icon ) && (
					<h3 className="sui-box-title">
						{ icon && <Icon classes={ 'sui-icon-' + icon } /> }
						{ ' ' + title }
					</h3>
				) }

				{ headerActions }
			</React.Fragment>
		);
	}

	/**
	 * Render component.
	 *
	 * @return {*} Box component.
	 */
	render() {
		const boxHeader = Box.boxHeader(
			this.props.title,
			this.props.icon,
			this.props.headerActions
		);

		return (
			<div className={ classNames( 'sui-box', this.props.boxClass ) }>
				<Loader loading={ this.props.loading } />

				{ ! this.props.hideHeader && (
					<div className="sui-box-header">{ boxHeader }</div>
				) }

				{ this.props.content && (
					<div
						className={ classNames(
							'sui-box-body',
							this.props.boxBodyClass
						) }
					>
						{ this.props.content }
					</div>
				) }

				{ this.props.footerActions && (
					<div className="sui-box-footer">
						{ this.props.footerActions }
					</div>
				) }
			</div>
		);
	}
}

Box.propTypes = {
	boxClass: PropTypes.string,
	boxBodyClass: PropTypes.string,
	title: PropTypes.string,
	icon: PropTypes.string,
	hideHeader: PropTypes.bool,
	headerActions: PropTypes.element,
	content: PropTypes.element,
	footerActions: PropTypes.element,
};
