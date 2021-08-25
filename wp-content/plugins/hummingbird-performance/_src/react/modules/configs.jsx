/**
 * External dependencies
 */
import React from 'react';
import ReactDOM from 'react-dom';
import PropTypes from 'prop-types';

/**
 * WordPress dependencies
 */
import domReady from '@wordpress/dom-ready';
const { __, sprintf } = wp.i18n;

/**
 * Internal dependencies.
 */
import { Presets } from '@wpmudev/shared-presets';

export const ConfigsPage = ( { isWidget, wphbData } ) => {
	const proDescription = (
		<React.Fragment>
			{ __(
				'You can easily apply configs to multiple sites at once via ',
				'wphb'
			) }
			<a
				href={ wphbData.links.hubConfigs }
				target="_blank"
				rel="noreferrer"
			>
				{ __( 'the Hub.' ) }
			</a>
		</React.Fragment>
	);

	const closeIcon = __( 'Close this dialog window', 'wphb' ),
		cancelButton = __( 'Cancel', 'wphb' );

	const lang = {
		title: __( 'Preset Configs', 'wphb' ),
		upload: __( 'Upload', 'wphb' ),
		save: __( 'Save config', 'wphb' ),
		manageConfigs: __( 'Manage configs', 'wphb' ),
		loading: __( 'Updating the config list...', 'wphb' ),
		emptyNotice: __(
			'You don’t have any available config. Save preset configurations of Hummingbird’s settings, then upload and apply them to your other sites in just a few clicks!',
			'wphb'
		),
		baseDescription: __(
			'Use configs to save preset configurations of Hummingbird’s settings, then upload and apply them to your other sites in just a few clicks!',
			'wphb'
		),
		proDescription,
		widgetDescription: __(
			'Use configs to save preset configurations of your settings.',
			'wphb'
		),
		syncWithHubText: __(
			'Created or updated the configs via the Hub?',
			'wphb'
		),
		syncWithHubButton: __(
			'Re-check to get the updated list.',
			'wphb'
		),
		apply: __( 'Apply', 'wphb' ),
		download: __( 'Download', 'wphb' ),
		edit: __( 'Name and Description', 'wphb' ),
		delete: __( 'Delete', 'wphb' ),
		notificationDismiss: __( 'Dismiss notice', 'wphb' ),
		freeButtonLabel: __( 'Try The Hub', 'wphb' ),
		defaultRequestError: sprintf(
			/* translators: %s request status */
			__(
				'Request failed. Status: %s. Please reload the page and try again.',
				'wphb'
			),
			'{status}'
		),
		uploadActionSuccessMessage: sprintf(
			/* translators: %s request status */
			__(
				'%s config has been uploaded successfully – you can now apply it to this site.',
				'wphb'
			),
			'{configName}'
		),
		uploadWrongPluginErrorMessage: sprintf(
			/* translators: %s {pluginName} */
			__(
				'The uploaded file is not a %s Config. Please make sure the uploaded file is correct.',
				'wphp'
			),
			'{pluginName}'
		),
		applyAction: {
			closeIcon,
			cancelButton,
			title: __( 'Apply Config', 'wphb' ),
			description: sprintf(
				/* translators: %s config name */
				__(
					'Are you sure you want to apply the %s config to this site? We recommend you have a backup available as your existing settings configuration will be overridden.',
					'wphb'
				),
				'{configName}'
			),
			actionButton: __( 'Apply', 'wphb' ),
			successMessage: sprintf(
				/* translators: %s. config name */
				__( '%s config has been applied successfully.', 'wphb' ),
				'{configName}'
			),
		},
		deleteAction: {
			closeIcon,
			cancelButton,
			title: __( 'Delete Configuration File', 'wphb' ),
			description: sprintf(
				/* translators: %s config name */
				__(
					'Are you sure you want to delete %s? You will no longer be able to apply it to this or other connected sites.',
					'wphb'
				),
				'{configName}'
			),
			actionButton: __( 'Delete', 'wphb' ),
		},
		editAction: {
			closeIcon,
			cancelButton,
			nameInput: __( 'Config name', 'wphb' ),
			descriptionInput: __( 'Description', 'wphb' ),
			emptyNameError: __( 'The config name is required', 'wphb' ),
			actionButton: __( 'Save', 'wphb' ),
			editTitle: __( 'Rename Config', 'wphb' ),
			editDescription: __(
				'Change your config name to something recognizable.',
				'wphb'
			),
			createTitle: __( 'Save Config', 'wphb' ),
			createDescription: __(
				'Save your current settings configuration. You’ll be able to then download and apply it to your other sites.',
				'wphb'
			),
			successMessage: sprintf(
				/* translators: %s. config name */
				__( '%s config created successfully.', 'wphb' ),
				'{configName}'
			),
		},
		settingsLabels: {
			uptime: __( 'Uptime', 'wphb' ),
			gravatar: __( 'Gravatar Caching', 'wphb' ),
			page_cache: __( 'Page Caching', 'wphb' ),
			advanced: __( 'Advanced Tools', 'wphb' ),
			rss: __( 'RSS Caching', 'wphb' ),
			settings: __( 'Settings', 'wphb' ),
			performance: __( 'Performance Test', 'wphb' ),
		},
	};

	return (
		<Presets
			isWidget={ isWidget }
			isPro={ wphbData.module.isMember }
			isWhitelabel={ wphbData.module.isWhiteLabeled }
			sourceLang={ lang }
			sourceUrls={ wphbData.links }
			requestsData={ wphbData.requestsData }
		/>
	);
};

ConfigsPage.propTypes = {
	wphbData: PropTypes.object,
};

domReady( function () {
	// Configs section on Dashboard page.
	const configsDashDiv = document.getElementById( 'wphb-dashboard-configs' );
	if ( configsDashDiv ) {
		ReactDOM.render(
			<ConfigsPage isWidget={ true } wphbData={ window.wphbReact } />,
			configsDashDiv
		);
	}

	// Configs page.
	const configsDiv = document.getElementById( 'wrap-wphb-configs' );
	if ( configsDiv ) {
		ReactDOM.render(
			<ConfigsPage isWidget={ false } wphbData={ window.wphbReact } />,
			configsDiv
		);
	}
} );
