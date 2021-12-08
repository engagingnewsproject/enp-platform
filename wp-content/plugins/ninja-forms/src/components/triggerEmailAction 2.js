import apiFetch from '@wordpress/api-fetch';
import PropTypes from 'prop-types';

/**
 * There is a straitforward way of sending all requests at once and have the Jobs in the loop done quicker
 * BUT I fear it could result in memory exhaustion in some browsers, this way we run request by request
 * TODO organize batches depending on the total amount of emails to be sent in order to make the process faster
 *
 */
export const sendEmail = async ( props, submissionID ) => {
	await apiFetch( {
		url:
			props.restUrl +
			'ninja-forms-submissions/email-action',
		method: 'POST',
		data: {
			submission: submissionID,
			action_settings: props.action,
			formID: props.formID,
		},
		parse: false, //Use false to manage errors, by default apiFetch is set to true on Parse and directly sends back the json response
		signal: props.signal
	} )
	.then( ( response ) => {
		//Count Processed emails (sent or not )
		props.setEmailProcessed();
		//Catch not OK fetch task or process response via json
		if ( ! response.ok ) {
			//Push Submission ID in an array to store failures
			console.log(response.json());
			props.setNotSent( response.json() );
		} else {
			return response.json();
		}
	} )
	.then( ( res ) => {
		//Count Sent emails if sent or push Submission ID to an array if email was not sent
		if ( res === true ) {
			props.setSent();
		} else {
			props.setNotSent( res );
		}
	} );
};

sendEmail.propTypes = {
	restUrl: PropTypes.string.isRequired,
	action: PropTypes.object.isRequired,
	formID: PropTypes.number.isRequired,
	setSent: PropTypes.func.isRequired,
	setNotSent: PropTypes.func.isRequired,
	setEmailProcessed: PropTypes.func.isRequired,
	signal: PropTypes.object.isRequired
}
