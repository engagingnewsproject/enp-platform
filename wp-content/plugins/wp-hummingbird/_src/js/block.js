import Fetcher from './utils/fetcher';
import { getString } from './utils/helpers';

const { PluginPostStatusInfo } = wp.editPost;
const { registerPlugin } = wp.plugins;
const { select, dispatch } = wp.data;

/**
 * Handle clear cache action.
 */
const handleClearCache = () => {
	const postId = select( 'core/editor' ).getCurrentPostId();
	Fetcher.caching.clearCacheForPost( postId ).then( showNotice );
};

/**
 * Show notice.
 */
const showNotice = () => {
	const notices = select( 'core/notices' ).getNotices();
	if ( ! notices.find( ( notice ) => notice.id === 'wphb-gb-notice' ) ) {
		const text = getString( 'notice' );
		dispatch( 'core/notices' ).createNotice( 'success', text, {
			id: 'wphb-gb-notice',
		} );
	}
};

/**
 * Add clear cache button.
 *
 * @return {*} Element
 * @class
 */
const MyPluginPostStatusInfo = () => (
	<PluginPostStatusInfo className="wphb-clear-cache">
		<input
			type="submit"
			value={ getString( 'button' ) }
			onClick={ handleClearCache }
			className="components-button is-button is-default is-secondary is-large editor-post-trash"
		/>
	</PluginPostStatusInfo>
);

registerPlugin( 'wphb', { render: MyPluginPostStatusInfo } );
