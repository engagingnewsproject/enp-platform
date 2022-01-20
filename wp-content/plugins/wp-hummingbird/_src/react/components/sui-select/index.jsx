/**
 * External dependencies
 */
import React from 'react';
import classNames from 'classnames';

/**
 * Select component.
 */
export default class Select extends React.Component {
	/**
	 * Share UI actions need to be performed manually for elements.
	 * They should be done in this method.
	 */
	componentDidMount() {
		this.$el = jQuery( this.el );
		this.$el.SUIselect2( { minimumResultsForSearch: -1 } );
		this.$el.on( 'change', this.props.onChange );
	}

	/**
	 * Render component.
	 *
	 * @return {JSX.Element}  Select component.
	 */
	render() {
		const selectOptions = this.props.items.map( ( item, id ) => {
			return (
				<option
					value={ item[ 0 ] }
					selected={ item[ 0 ] === this.props.selected }
					key={ id }
				>
					{ item[ 1 ] }
				</option>
			);
		} );

		const width = 'undefined' === typeof this.props.classes ? '250' : null;

		return (
			<div
				className={ classNames( 'sui-form-field', {
					'sui-input-md': '250' === width,
				} ) }
			>
				<label
					htmlFor={ this.props.selectId }
					id={ this.props.selectId + '-label' }
					className="sui-label"
				>
					{ this.props.label }
				</label>
				<select
					className={ classNames( 'sui-select', this.props.classes ) }
					data-width={ width }
					name={ this.props.selectId }
					id={ this.props.selectId }
					multiple={ this.props.multiple }
					data-placeholder={ this.props.placeholder ?? '' }
					aria-labelledby={ this.props.selectId + '-label' }
					aria-describedby={
						this.props.description
							? this.props.selectId + '-description'
							: ''
					}
					ref={ ( el ) => ( this.el = el ) }
				>
					{ selectOptions }
				</select>

				{ this.props.description && (
					<span
						id={ this.props.selectId + '-description' }
						className="sui-description"
					>
						{ this.props.description }
					</span>
				) }
			</div>
		);
	}
}

/**
 * Default props.
 *
 * @param {string} selectId Select ID. Will be also used as class and htmlFor in the label.
 * @param {string} label    Label text.
 * @param {Array}  items    List of items for the select.
 * @param {string} selected Selected item.
 *
 * @type {{selectId: string, multiple: boolean, label: string, items: *[], selected: string}}
 */
Select.defaultProps = {
	selectId: '',
	label: '',
	items: [],
	selected: '',
	multiple: false,
};
