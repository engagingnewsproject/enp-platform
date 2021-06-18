import { CustomSelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';

export const SelectAction = ( props ) => {
	const actions =
		typeof props.form !== 'undefined' ? props.form.emailActions : {};
	const options = [];
	for ( const [ key, value ] of Object.entries( actions ) ) {
		options.push( {
			value: key,
			name: value.label,
			key,
		} );
	}

	return (
		<CustomSelectControl
			label={ __( 'Select Email Action:', 'ninja-forms' ) }
			options={ options }
			onChange={ ( { selectedItem } ) => {
				actions[ selectedItem.value ].value = selectedItem.value;
				props.setAction( "action", actions[ selectedItem.value ] );
			} }
		/>
	);
};

SelectAction.propTypes = {
	form:  PropTypes.object,
	setAction: PropTypes.func.isRequired
}