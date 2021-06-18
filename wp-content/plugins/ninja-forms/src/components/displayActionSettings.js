import PropTypes from 'prop-types';

export const DisplayActionSettings = ( props ) => {
	const values = Object.keys( props.value ).map( ( key ) => {
		//Only Display these settings
		const inValues = [ 'label', 'to', 'email_subject' ];
		if (
			inValues.includes( key ) &&
			typeof props.value[ key ] === 'string' &&
			props.value[ key ].length > 0
		) {
			return (
				<li key={ key }>
					<b>{ key }</b>: { props.value[ key ] }
				</li>
			);
		}
	} );

	return <ul>{ values }</ul>;
};

DisplayActionSettings.propTypes = {
	value: PropTypes.object.isRequired
}
