<?php
$all_tabs = apply_filters( 'tribe_settings_all_tabs', [] );

$network_tab = [
	'priority'      => 10,
	'network_admin' => true,
	'fields'        => apply_filters(
		'tribe_network_settings_tab_fields', [
			'info-start'           => [
				'type' => 'html',
				'html' => '<div id="modern-tribe-info">',
			],
			'info-box-title'       => [
				'type' => 'html',
				'html' => '<h1>' . esc_html__( 'Network Settings', 'the-events-calendar' ) . '</h1>',
			],
			'info-box-description' => [
				'type' => 'html',
				'html' => '<p>' . esc_html__( 'This is where all of the global network settings for The Events Calendar can be modified.', 'the-events-calendar' ) . '</p>',
			],
			'info-end'             => [
				'type' => 'html',
				'html' => '</div>',
			],
			'hideSettingsTabs'     => [
				'type'         => 'checkbox_list',
				'label'        => esc_html__( 'Hide the following settings tabs on every site:', 'the-events-calendar' ),
				'default'      => false,
				'options'      => $all_tabs,
				'can_be_empty' => true,
			],
		]
	),
];
