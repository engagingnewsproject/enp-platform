/**
 * External dependencies
 */
import React from 'react';
import classNames from 'classnames';

/**
 * Checkbox component.
 */
export default class Checkbox extends React.Component {
	/**
	 * Render component.
	 *
	 * @return {JSX.Element}  Select component.
	 */
	render() {
		return (
			<div className="sui-form-field">
				<label
					htmlFor={ 'wphb-' + this.props.id }
					className={ classNames( 'sui-checkbox', {
						'sui-checkbox-sm':
							'undefined' !== typeof this.props.size &&
							'sm' === this.props.size,
					} ) }
				>
					<input
						type="checkbox"
						id={ 'wphb-' + this.props.id }
						aria-labelledby={ 'wphb-' + this.props.id + '-label' }
						checked={ this.props.checked }
						disabled={ this.props.disabled }
						onChange={ this.props.onChange }
					/>
					<span aria-hidden="true"></span>
					{ this.props.label && (
						<span id={ 'wphb-' + this.props.id + '-label' }>
							{ this.props.label }
						</span>
					) }
				</label>
				{ this.props.description && (
					<span className="sui-description sui-checkbox-description">
						{ this.props.description }
					</span>
				) }
			</div>
		);
	}
}

Checkbox.defaultProps = {
	id: '',
	description: '',
	label: '',
	checked: false,
	disabled: false,
};
