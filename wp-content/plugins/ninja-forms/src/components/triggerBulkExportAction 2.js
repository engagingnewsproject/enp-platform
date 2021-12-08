import apiFetch from '@wordpress/api-fetch';
import PropTypes from 'prop-types';

export const triggerBulkExportAction = async ( props ) => {
	props.setProcessing( true );
	///Set Nonce in API requests headers
	apiFetch.use( apiFetch.createNonceMiddleware( props.globalParams.token ) );

	const forms = props.forms ? props.forms.join( ',' ) : '',
		start = props.startDateTimestamp
			? JSON.stringify( props.startDateTimestamp )
			: '',
		end = props.endDateTimeStamp
			? JSON.stringify( props.endDateTimeStamp )
			: '';

	//TODO apiFetch returns a parsed response we need to check if it already checks and throws errors for http errors
	//Still need to determine how it ends here
	await apiFetch({
		url: props.globalParams.restUrl + 'ninja-forms-submissions/export',
		method: 'POST',
		data: {
			form_ids: forms,
			start_date: start,
			end_date: end,
		},
		signal: props.signal
	})
	.then( ( res ) => {
		for ( const [ key, value ] of Object.entries( res ) ) {
			downloadCsv( key, value );
		}
		props.setExportSuccess();
		props.setProcessing( false );
	} )
	.catch( (e) => {
		console.log( 'Export cancelled: ' + e.message );
	});
};

const downloadCsv = ( formID, data ) => {
	const blob = new Blob( [ data ] );
	let a = window.document.createElement( 'a' );
	a.href = window.URL.createObjectURL( blob, {
		encoding: 'UTF-8',
		type: 'text/csv;charset=UTF-8',
	} );
	a.download = 'nf-subs-' + formID + '.csv';
	document.body.appendChild( a );
	a.click();
	document.body.removeChild( a );
};

triggerBulkExportAction.propTypes = {
	globalParams: PropTypes.object.isRequired,
	forms: PropTypes.array.isRequired,
	startDateTimestamp: PropTypes.object.isRequired,
	endDateTimeStamp: PropTypes.object.isRequired,
	setProcessing: PropTypes.func.isRequired,
	setExportSuccess: PropTypes.func.isRequired,
	setExportError: PropTypes.func.isRequired,
	signal: PropTypes.object.isRequired
}