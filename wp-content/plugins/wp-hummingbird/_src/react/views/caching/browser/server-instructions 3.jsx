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
import Button from '../../../components/sui-button';
import { UserContext } from '../../../context';
import SupportLink from '../../../components/support-link';
import CodeSnippet from '../../../components/sui-code-snippet';
import OrderedList from '../../../components/ordered-list';

/**
 * Server instructions component.
 *
 * @since 3.2.0
 */
export default class ServerInstructions extends React.Component {
	/**
	 * Render Apache tabs.
	 *
	 * @param {boolean} litespeed Is this a litespeed server.
	 *
	 * @return {JSX.Element}  Tab content.
	 */
	renderApacheTab( litespeed = false ) {
		const liteSpeedList = [
			<React.Fragment key="1">
				{ __( 'Manually configure the Cache-Control header for browser caching in your WebAdmin Console following the Open LiteSpeed guide', 'wphb' ) }&nbsp;
				<Button url="https://openlitespeed.org/kb/how-to-set-up-custom-headers/" target="_blank" text={ __( 'here', 'wphb' ) } />.
			</React.Fragment>,
			__( 'Set Expires by Type to 31536000 (1 year) to meet Google’s recommended benchmark.', 'wphb' )
		];

		const listItems = [
			<React.Fragment key="1">
				{ __( 'Copy the generated code into your .htaccess file & save your changes.', 'wphb' ) }
				<CodeSnippet code={ this.props.snippets.apache } />
			</React.Fragment>,
			litespeed ? __( 'Restart/reload LiteSpeed', 'wphb' ) : __( 'Restart/reload Apache', 'wphb' ),
		];

		return (
			<React.Fragment>
				{ litespeed &&
					<React.Fragment>
						<OrderedList list={ liteSpeedList } />
						<p className="sui-description sui-margin-top">
							{ __( 'Alternatively, browser cache can be configured via an .htaccess file. Follow the steps below to add browser caching to your LiteSpeed server:', 'wphb' ) }
						</p>
					</React.Fragment>
				}

				<OrderedList list={ listItems } />

				<p className="sui-description sui-margin-top">
					<strong>{ __( 'Troubleshooting', 'wphb' ) }</strong>
				</p>

				<p className="sui-description">
					{ __( "If adding the rules to your .htaccess doesn't work and you have access to vhosts.conf or httpd.conf try to find the line that starts with <Directory> - add the code above into that section and save the file.", 'wphb' ) }
				</p>

				<p className="sui-description">
					{ __( "If you don't know where those files are, or you aren't able to reload the web server, you would need to consult with your hosting provider or a system administrator who has access to change the configuration of your server", 'wphb' ) }
				</p>

				<SupportLink isMember={ this.context.isMember } forumLink={ this.context.links.support.forum } chatLink={ this.context.links.support.chat } />
			</React.Fragment>
		);
	}

	/**
	 * Render Nginx tab.
	 *
	 * @return {JSX.Element}  Tab content.
	 */
	renderNginxTab() {
		const items = [
			<React.Fragment key="1">
				{ __( 'Copy the generated code into your nginx.conf usually located at /etc/nginx/nginx.conf or /usr/local/nginx/conf/nginx.conf', 'wphb' ) }
				<CodeSnippet code={ this.props.snippets.nginx } />
			</React.Fragment>,
			__( 'Add the code above to the http or inside server section in the file.', 'wphb' ),
			__( 'Reload/restart NGINX.', 'wphb' ),
		];

		return (
			<React.Fragment>
				<OrderedList list={ items } />

				<p className="sui-description sui-margin-top">
					<strong>{ __( 'Troubleshooting', 'wphb' ) }</strong>
				</p>

				<p className="sui-description">
					{ __( 'If you do not have access to your NGINX config files you will need to contact your hosting provider to make these changes.', 'wphb' ) }
				</p>

				<SupportLink isMember={ this.context.isMember } forumLink={ this.context.links.support.forum } chatLink={ this.context.links.support.chat } />
			</React.Fragment>
		);
	}

	/**
	 * Render IIS tab.
	 *
	 * @return {JSX.Element}  Tab content.
	 */
	renderIisTab() {
		return (
			<React.Fragment>
				<p className="sui-description">
					{ __( 'For IIS 7 servers and above,', 'wphb' ) }{ ' ' }
					<Button
						url="https://technet.microsoft.com/en-us/library/cc732475(v=ws.10).aspx"
						target="blank"
						text={ __( 'visit Microsoft TechNet', 'wphb' ) }
					/>
				</p>
			</React.Fragment>
		);
	}

	/**
	 * Render component.
	 *
	 * @return {JSX.Element}  ServerInstructions component.
	 */
	render() {
		// TODO: handle cloudflare.
		return (
			<div className="wphb-server-instructions">
				{ 'apache' === this.props.currentServer && this.renderApacheTab() }
				{ 'nginx' === this.props.currentServer && this.renderNginxTab() }
				{ 'iis' === this.props.currentServer && this.renderIisTab() }
				{ 'litespeed' === this.props.currentServer && this.renderApacheTab( true ) }
			</div>
		);
	}
}

ServerInstructions.contextType = UserContext;
