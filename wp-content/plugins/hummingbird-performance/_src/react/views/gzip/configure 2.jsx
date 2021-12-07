/**
 * External dependencies
 */
import React from 'react';

/**
 * WordPress dependencies
 */
const { __ } = wp.i18n;

/**
 * Internal dependencies
 */
import Box from '../../components/sui-box';
import Select from '../../components/sui-select';
import ServerInstructions from './server-instructions';
import SettingsRow from '../../components/sui-box-settings/row';

/**
 * GzipConfig component.
 *
 * @since 2.1.1
 */
export default class GzipConfig extends React.Component {
	/**
	 * Component constructor.
	 *
	 * @param {Object} props
	 */
	constructor( props ) {
		super( props );

		this.state = {
			currentServer: this.props.data.server_name,
		};

		this.handleServerChange = this.handleServerChange.bind( this );
	}

	/**
	 * Handle server select update.
	 *
	 * @param {Object} e
	 */
	handleServerChange( e ) {
		this.setState( {
			currentServer: e.target.value,
		} );
	}

	/**
	 * Render component.
	 *
	 * @return {*} GzipConfig component.
	 */
	render() {
		const fullyEnabled =
			Object.entries( this.props.status ).filter( ( item ) => item[ 1 ] )
				.length === 3;

		if ( true === fullyEnabled ) {
			return null;
		}

		// Remove Cloudflare from the server list.
		const serverList = Object.entries(
			this.props.data.servers_array
		).filter( ( value ) => {
			return 'cloudflare' !== value[ 0 ];
		} );

		const serverSelect = (
			<Select
				selectId="wphb-server-type"
				label={ __( 'Server type', 'wphb' ) }
				items={ serverList }
				selected={ this.state.currentServer }
				onChange={ this.handleServerChange }
			/>
		);

		const serverInstructions = (
			<ServerInstructions
				currentServer={ this.state.currentServer }
				fullyEnabled={ fullyEnabled }
				gzipStatus={ this.props.status }
				htaccessError={ this.props.data.htaccess_error }
				htaccessWritable={ this.props.data.htaccess_writable }
				htaccessWritten={ this.props.data.htaccess_written }
				serverSnippets={ this.props.data.snippets }
				enable={ this.props.enableGzip }
				disable={ this.props.disableGzip }
				server={ this.props.data.server_name }
			/>
		);

		return (
			<Box
				loading={ this.props.loading }
				title={ __( 'Configure', 'wphb' ) }
				boxClass={ [ 'box-gzip-settings' ] }
				content={
					<React.Fragment>
						<SettingsRow
							label={ __( 'Server type', 'wphb' ) }
							description={ __(
								'Choose your server type. If you don’t know this, please contact your hosting provider.',
								'wphb'
							) }
							content={ serverSelect }
						/>
						<SettingsRow
							label={ __( 'Enable compression' ) }
							description={ __(
								'Follow the instructions to activate GZip compression for this website.',
								'wphb'
							) }
							content={ serverInstructions }
						/>
					</React.Fragment>
				}
			/>
		);
	}
}
