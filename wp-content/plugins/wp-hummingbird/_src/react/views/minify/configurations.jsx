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
import './configurations.scss';
import Action from '../../components/sui-box/action';
import Box from '../../components/sui-box';
import Button from '../../components/sui-button';
import Checkbox from '../../components/sui-checkbox';
import Tabs from '../../components/sui-tabs';
import Select from '../../components/sui-select';

/**
 * Configurations component.
 *
 * @since 2.7.2
 */
export default class Configurations extends React.Component {
	/**
	 * Component header.
	 *
	 * @return {JSX.Element}  Header action buttons.
	 */
	getHeaderActions() {
		const buttons = (
			<Button
				text={ __( 'Reset settings', 'wphb' ) }
				classes={ [ 'sui-button', 'sui-button-ghost' ] }
				icon="sui-icon-undo"
				onClick={ this.props.resetSettings }
			/>
		);

		return <Action type="right" content={ buttons } />;
	}

	/**
	 * Component footer.
	 *
	 * @return {JSX.Element}  Footer action buttons.
	 */
	getFooterActions() {
		const buttons = (
			<Button
				text={ __( 'Publish changes', 'wphb' ) }
				classes={ [ 'sui-button', 'sui-button-blue' ] }
				onClick={ this.props.saveSettings }
			/>
		);

		return <Action type="right" content={ buttons } />;
	}

	/**
	 * Files tab content.
	 *
	 * @return {JSX.Element}  Content
	 */
	tabFiles() {
		return (
			<React.Fragment>
				<Checkbox
					id="auto-css"
					label={ __( 'CSS files', 'wphb' ) }
					description={ __( 'Hummingbird will minify your CSS files, generating a version that loads faster. It will remove unnecessary characters or lines of code from your file to make it more compact.', 'wphb' ) }
					checked={ this.props.enabled.styles }
					onChange={ this.props.onEnabledChange }
				/>

				<Checkbox
					id="auto-js"
					label={ __( 'JavaScript files', 'wphb' ) }
					description={ __( 'JavaScript minification is the process of removing whitespace and any code that is not necessary to create a smaller but valid code.', 'wphb' ) }
					checked={ this.props.enabled.scripts }
					onChange={ this.props.onEnabledChange }
				/>

				{ 'speedy' === this.props.view &&
					<Checkbox
						id="auto-fonts"
						label={ __( 'Fonts', 'wphb' ) }
						description={ __( 'Enable this option to optimize the delivery of your fonts so they don\'t trigger the "Eliminate render-blocking resources" recommendation in your performance tests.', 'wphb' ) }
						checked={ this.props.enabled.fonts }
						onChange={ this.props.onEnabledChange }
					/> }
			</React.Fragment>
		);
	}

	/**
	 * Exclusions tab content.
	 *
	 * @return {JSX.Element}  Content
	 */
	tabExclusions() {
		const types = [ 'styles', 'scripts' ];

		const select = jQuery( '#wphb-auto-exclude' );
		select.empty();

		types.forEach( ( type ) => {
			if ( 'undefined' === this.props.assets[ type ] ) {
				return;
			}

			Object.values( this.props.assets[ type ] ).forEach( ( el ) => {
				const text =
					el.handle + ' (' + __( 'file: ', 'wphb' ) + el.src + ')';
				const excluded = window.lodash.includes(
					this.props.exclusions[ type ],
					el.handle
				);

				const item = select.find( "option[value='" + el.handle + "']" );

				// Only add a new zone if it's not already present.
				if ( 0 === item.length ) {
					const option = new Option(
						text,
						el.handle,
						false,
						excluded
					);
					option.dataset.type = type;
					select.append( option ).trigger( 'change' );
				}
			} );
		} );

		return (
			<Select
				selectId="wphb-auto-exclude"
				classes="sui-select-lg"
				label={ __( 'File exclusions', 'wphb' ) }
				description={ __(
					'Type the filename and click on the filename to add it to the list.',
					'wphb'
				) }
				placeholder={ __(
					'Start typing the files to exclude...',
					'wphb'
				) }
				multiple="true"
			/>
		);
	}

	/**
	 * Tabs content.
	 *
	 * @return {Object}  Tab content elements.
	 */
	getTabs() {
		return [
			{
				id: 'auto-files',
				description: __(
					'Choose which files you want to automatically optimize.',
					'wphb'
				),
				content: this.tabFiles(),
				active: true,
			},
			{
				id: 'auto-exclusions',
				description: __(
					"By default, we'll optimize all the CSS and JS files we can find. If you have specific files you want to leave as-is, list them here, and we'll exclude them.",
					'wphb'
				),
				content: this.tabExclusions(),
			},
		];
	}

	/**
	 * Component body.
	 *
	 * @return {JSX.Element}  Content.
	 */
	getContent() {
		const tabsMenu = [
			{
				title: __( 'Files', 'wphb' ),
				id: 'auto-files',
				checked: true,
			},
			{
				title: __( 'Exclusions', 'wphb' ),
				id: 'auto-exclusions',
			},
		];

		return (
			<React.Fragment>
				<p>
					{ __(
						'The configurations will be applied to the enabled automatic optimization option.',
						'wphb'
					) }
				</p>
				<Tabs
					menu={ tabsMenu }
					tabs={ this.getTabs() }
					flushed="true"
				/>
			</React.Fragment>
		);
	}

	/**
	 * Render component.
	 *
	 * @return {JSX.Element}  Configurations component.
	 */
	render() {
		return (
			<Box
				boxClass="box-minification-assets-auto-config"
				loading={ this.props.loading }
				title={ __( 'Configurations', 'wphb' ) }
				headerActions={ this.getHeaderActions() }
				content={ this.getContent() }
				footerActions={ this.getFooterActions() }
			/>
		);
	}
}
