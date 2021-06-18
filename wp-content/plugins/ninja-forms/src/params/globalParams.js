const forms =
	typeof window.nf_submissions.forms !== 'undefined'
		? window.nf_submissions.forms
		: {};
const dateFormat =
	typeof window.nf_submissions.dateFormat !== 'undefined'
		? window.nf_submissions.dateFormat
		: '';
const timeFormat =
	typeof window.nf_submissions.timeFormat !== 'undefined'
		? window.nf_submissions.timeFormat
		: '';
const siteUrl =
	typeof window.nf_submissions.siteUrl !== 'undefined'
		? window.nf_submissions.siteUrl
		: '';
const adminUrl =
	typeof window.nf_submissions.adminUrl !== 'undefined'
		? window.nf_submissions.adminUrl
		: '';
const restUrl =
	typeof window.nf_submissions.restUrl !== 'undefined'
		? window.nf_submissions.restUrl
		: '';
const token =
	typeof window.nf_submissions.token !== 'undefined'
		? window.nf_submissions.token
		: '';
//Remove Empty Forms
for ( const form in forms ) {
	if ( Object.keys( forms[ form ] ).length === 0 ) {
		delete forms[ form ];
	}
}
//TriggerEmailActions Params
export const globalParams = {
	siteUrl,
	adminUrl,
	restUrl,
	token,
	forms,
	timeFormat,
	dateFormat,
};
