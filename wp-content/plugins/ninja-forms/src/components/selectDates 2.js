import Flatpickr from 'react-flatpickr';
import PropTypes from 'prop-types';

export const SelectDates = ( props ) => {
	return (
		<Flatpickr
			data-mode="range"
			options={ {
				dateFormat: props.dateFormat,
			} }
			onChange={ ( date ) => {
				props.setDates( { date } );
			} }
		/>
	);
};

SelectDates.propTypes = {
	dateFormat: PropTypes.string.isRequired,
	setDates: PropTypes.func.isRequired
}
