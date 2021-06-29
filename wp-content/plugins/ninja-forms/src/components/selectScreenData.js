import PropTypes from 'prop-types';

//Select Data from the WP Form ( Form ID and SUbmission IDs)
export const selectScreenData = ( setStateNewValue, props ) => {
	screenData( setStateNewValue, props );

	const postsFilter = document.getElementById( 'posts-filter' );
	postsFilter.addEventListener( 'change', ( event ) => {
		event.stopPropagation();
		screenData( setStateNewValue, props );
	} );
};

const screenData = ( setStateNewValue, props ) => {
	const postsFilter = document.getElementById( 'posts-filter' );
	//Get IDs of submissions selected and Form ID
	const allPosts = [];
	jQuery( postsFilter )
		.serializeArray()
		.forEach( ( item ) => {
			if ( item.name === 'post[]' ) {
				allPosts.push( item.value );
			} else if ( item.name === 'form_id' ) {
				setStateNewValue( "form", props.globalParams.forms[ item.value ] );
			}
		} );

		setStateNewValue( "submissionIds", allPosts );
};

selectScreenData.propTypes = {
	setStateNewValue: PropTypes.func.isRequired,
	props: PropTypes.object.isRequired
}