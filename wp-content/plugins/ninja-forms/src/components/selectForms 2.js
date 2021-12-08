import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';

export const SelectForms = ( props ) => {
	const forms = typeof props.forms !== 'undefined' ? props.forms : {};
	const options = [];
	for ( const [ key, value ] of Object.entries( forms ) ) {
		options.push( {
			value: value.formID,
			label: value.formTitle,
		} );
	}

	return (
		<SelectControl
			style={ { minHeight: '10em' } }
			hideLabelFromVision="true"
			label={ __( 'Select Forms', 'ninja-forms' ) }
			multiple={ true }
			options={ options }
			onChange={ ( value ) => props.setForms( value ) }
		/>
	);
};

SelectForms.propTypes = {
	setForms: PropTypes.func.isRequired,
	forms: PropTypes.object
}