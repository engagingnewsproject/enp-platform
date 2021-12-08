import { Notice } from '@wordpress/components';
import PropTypes from 'prop-types';

export const DisplayNotice = ( props ) => {
	//TODO: Use state management
	const removeNotice = () => {
		jQuery( '#nf-trigger-emails-container .components-notice' ).remove();
		jQuery( '#nf-bulk-export-container .components-notice' ).remove();
	};

	return (
		<>
			<Notice
				status={ props.status }
				isDismissible="true"
				children={ props.text }
				politeness="polite"
				onRemove={ removeNotice }
			/>
		</>
	);
};

DisplayNotice.propTypes = {
	isDismissible: PropTypes.string,
	status: PropTypes.string,
	text: PropTypes.string
}
