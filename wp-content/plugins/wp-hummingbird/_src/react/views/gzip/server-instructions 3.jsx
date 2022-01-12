/* global SUI */
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
import Button from '../../components/sui-button';
import Notice from '../../components/sui-notice';
import { UserContext } from '../../context';
import SupportLink from '../../components/support-link';
import CodeSnippet from '../../components/sui-code-snippet';
import OrderedList from '../../components/ordered-list';
import Tabs from '../../components/sui-tabs';

/**
 * Server instructions component.
 */
export default class ServerInstructions extends React.Component {
	/**
	 * Share UI actions need to be performed manually for elements.
	 * They should be done in this method.
	 */
	componentDidMount() {
		ServerInstructions.initSUIcomponents();
	}

	componentDidUpdate() {
		ServerInstructions.initSUIcomponents();
	}

	static initSUIcomponents() {
		const el = document.getElementById( 'wphb-server-instructions-apache' );
		if ( el ) {
			SUI.suiTabs( el.querySelector( '.sui-tabs' ) );
		}

		const troubleshootLink = document.getElementById(
			'troubleshooting-link'
		);
		if ( troubleshootLink ) {
			troubleshootLink.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				jQuery( 'html, body' ).animate(
					{
						scrollTop: jQuery( '#troubleshooting-apache' ).offset()
							.top,
					},
					'slow'
				);
			} );
		}
	}

	/**
	 * Render cache wrapper element.
	 *
	 * @return {*}  Notice or success message.
	 */
	cacheWrap() {
		let classNames = 'sui-hidden';
		if ( 'apache' === this.props.currentServer ) {
			classNames = '';
		}

		const enableButton = this.props.htaccessWritten ? (
			<Button
				onClick={ this.props.disable }
				classes={ [
					'sui-button',
					'sui-button-ghost',
					'sui-margin-top',
				] }
				text={ __( 'Deactivate', 'wphb' ) }
			/>
		) : (
			<Button
				onClick={ this.props.enable }
				classes={ [
					'sui-button',
					'sui-button-blue',
					'sui-margin-top',
				] }
				text={ __( 'Apply Rules', 'wphb' ) }
			/>
		);

		const noticeText = (
			<p>
				{ __(
					'We tried applying the .htaccess rules automatically but we weren’t able to. Make sure your file permissions on your .htaccess file are set to 644, or',
					'wphb'
				) }
				<Button
					url="#apache-config-manual"
					classes={ [ 'switch-manual' ] }
					text={ __( 'switch to manual mode', 'wphb' ) }
				/>
				{ __( 'and apply the rules yourself.', 'wphb' ) }
			</p>
		);

		return (
			<div id="enable-cache-wrap" className={ classNames }>
				{ this.props.htaccessError && (
					<Notice
						classes={ [ 'sui-notice-warning' ] }
						message={ noticeText }
					/>
				) }
				{ enableButton }
			</div>
		);
	}

	/**
	 * Render Apache tabs.
	 *
	 * @return {*}  Tab content.
	 */
	renderApacheTabs() {
		const hideEnableButton =
			this.props.fullyEnabled &&
			( ! this.props.htaccessWritten || 'nginx' === this.props.server );

		const itemsBefore = [
			__( 'Copy & paste the generated code below into your .htaccess file', 'wphb' ),
			<React.Fragment key="2">
				{ __( 'Next', 'wphb' ) },{ ' ' }
				<Button text={ __( 're-check your GZip status', 'wphb' ) } />
				{ ' ' }{ __( 'to see if it worked', 'wphb' ) }.{ ' ' }
				<Button id="troubleshooting-link" text={ __( 'Still having issues?', 'wphb' ) } />
			</React.Fragment>
		];

		const itemsAfter = [
			__( 'Look for your site in the file and find the line that starts with <Directory> - add the code above into that section and save the file.', 'wphb' ),
			__( 'Reload Apache/LiteSpeed.', 'wphb' ),
			__( "If you don't know where those files are, or you aren't able to reload Apache/LiteSpeed, you would need to consult with your hosting provider or a system administrator who has access to change the configuration of your server", 'wphb' ),
		];

		const tabs = [
			{
				title: __( 'Automatic', 'wphb' ),
				id: 'automatic',
				checked: true,
			},
			{
				title: __( 'Manual', 'wphb' ),
				id: 'manual',
			},
		];

		const content = [
			{
				id: 'automatic',
				content: (
					<React.Fragment>
						<span className="sui-description">
							{ __(
								'Hummingbird can automatically apply GZip compression for Apache/LiteSpeed servers by writing your .htaccess file. Alternately, switch to Manual to apply these rules yourself.',
								'wphb'
							) }
						</span>
						{ this.props.htaccessWritable && ! hideEnableButton && this.cacheWrap() }
					</React.Fragment>
				),
				active: true,
			},
			{
				id: 'manual',
				content: (
					<div className="apache-instructions">
						<p className="sui-description">
							{ __(
								'If you are unable to get the automated method working you can copy the generated code below into your .htaccess file to activate GZip compression.',
								'wphb'
							) }
						</p>

						<OrderedList list={ itemsBefore } />

						<CodeSnippet code={ this.props.serverSnippets.apache } />

						<p
							className="sui-description sui-margin-top"
							id="troubleshooting-apache"
						>
							<strong>{ __( 'Troubleshooting', 'wphb' ) }</strong>
						</p>
						<p className="sui-description">
							{ __(
								'If .htaccess does not work, and you have access to vhosts.conf or httpd.conf try this:',
								'wphb'
							) }
						</p>
						<OrderedList list={ itemsAfter } />
						<SupportLink isMember={ this.context.isMember } forumLink={ this.context.links.support.forum } chatLink={ this.context.links.support.chat } />
					</div>
				),
			},
		];

		return (
			<div className="wphb-server-instructions" id="wphb-server-instructions-apache">
				<Tabs menu={ tabs } tabs={ content } />
			</div>
		);
	}

	/**
	 * Render Nginx tab.
	 *
	 * @return {*}  Tab content.
	 */
	renderNginxTabs() {
		const items = [
			__( "Edit your nginx.conf. Usually it's located at /etc/nginx/nginx.conf or /usr/local/nginx/nginx.conf", 'wphb' ),
			__( 'Copy the generated code found below and paste it inside your http or server block.', 'wphb' ),
			__( 'Reload/restart NGINX.', 'wphb' ),
		];

		return (
			<div className="wphb-server-instructions">
				<p className="sui-description">
					{ __( 'For NGINX servers:', 'wphb' ) }
				</p>

				<OrderedList list={ items } />

				<p className="sui-description">
					{ __(
						'If you do not have access to your NGINX config files you will need to contact your hosting provider to make these changes.',
						'wphb'
					) }
				</p>

				<SupportLink isMember={ this.context.isMember } forumLink={ this.context.links.support.forum } chatLink={ this.context.links.support.chat } />
				<CodeSnippet code={ this.props.serverSnippets.nginx } />
			</div>
		);
	}

	/**
	 * Render IIS tab.
	 *
	 * @return {*}  Tab content.
	 */
	renderIisTabs() {
		return (
			<div className="wphb-server-instructions">
				<p className="sui-description">
					{ __( 'For IIS 7 servers and above,', 'wphb' ) }{ ' ' }
					<Button
						url="https://technet.microsoft.com/en-us/library/cc771003(v=ws.10).aspx"
						target="blank"
						text={ __( 'visit Microsoft TechNet', 'wphb' ) }
					/>
				</p>
			</div>
		);
	}

	/**
	 * Render Cloudflare tab.
	 *
	 * @since 2.7.2
	 *
	 * @return {*}  Tab content.
	 */
	renderCloudflareTabs() {
		return (
			<div className="wphb-server-instructions">
				<p className="sui-description">
					{ __(
						'Hummingbird can control your Cloudflare GZip compression settings from here. Simply add your Cloudflare API details and configure away',
						'wphb'
					) }
				</p>
			</div>
		);
	}

	/**
	 * Render component.
	 *
	 * @return {JSX.Element}  ServerInstructions component.
	 */
	render() {
		if ( this.props.htaccessWritten && this.props.fullyEnabled ) {
			return (
				<React.Fragment>
					<Notice
						classes={ [ 'sui-notice-info' ] }
						message={ __(
							'Automatic .htaccess rules have been applied.',
							'wphb'
						) }
					/>
					<Button
						onClick={ this.props.disable }
						classes={ [ 'sui-button', 'sui-button-ghost' ] }
						text={ __( 'Deactivate', 'wphb' ) }
					/>
				</React.Fragment>
			);
		}

		return (
			<React.Fragment>
				{ 'apache' === this.props.currentServer &&
					this.renderApacheTabs() }
				{ 'nginx' === this.props.currentServer &&
					this.renderNginxTabs() }
				{ 'iis' === this.props.currentServer && this.renderIisTabs() }
				{ 'cloudflare' === this.props.currentServer &&
					this.renderCloudflareTabs() }
			</React.Fragment>
		);
	}
}

ServerInstructions.contextType = UserContext;
