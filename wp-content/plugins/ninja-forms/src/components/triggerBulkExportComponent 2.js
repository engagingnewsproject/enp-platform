import { Component } from '@wordpress/element';
import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { DisplayBulkExport } from './';

export class TriggerBulkExportComponent extends Component {
	constructor( props ) {
		super( props );
		this.state = { compOpen: false };
		this.setOpen = this.setOpen.bind( this );
		this.setClose = this.setClose.bind( this );
	}

	setOpen() {
		this.setState( { compOpen: true } );
	}
	setClose() {
		this.setState( { compOpen: false } );
	}

	render() {
		const { state, props, setClose } = this;
		const { compOpen } = state;

		const data = {
			fetchController: new AbortController(),
			props,
			setClose,
		};

		return (
			<>
				<Button
					style={ { height: '30px', borderRadius: '3px' } }
					isSecondary
					onClick={ this.setOpen }
				>
					{ __( 'Bulk Form Exports', 'ninja-forms' ) }
				</Button>
				{ compOpen && <DisplayBulkExport data={ data } /> }
			</>
		);
	}
}

TriggerBulkExportComponent.propTypes = {
	globalParams: PropTypes.object.isRequired,
	bulkExportParams: PropTypes.object.isRequired
}
