import { Button, Modal } from '@wordpress/components';
import PropTypes from 'prop-types';

export const DisplayModal = ( props ) => {
	return (
		<>
			<Modal
				title={ props.title }
				onRequestClose={ () => props.cancel( false ) }
			>
				<Button
					isPrimary
					onClick={ props.action }
					style={ { marginRight: '1rem' } }
				>
					{ props.actionText }
				</Button>
				{ props.cancel && (
					<Button isSecondary onClick={ () => props.cancel( false ) }>
						{ props.cancelText }
					</Button>
				) }
			</Modal>
		</>
	);
};

DisplayModal.propTypes = {
	title: PropTypes.string,
	actionText: PropTypes.string,
	cancelText: PropTypes.string,
	action: PropTypes.func.isRequired,
	cancel: PropTypes.func.isRequired
}
