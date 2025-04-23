<?php if ( ! defined( 'ABSPATH' ) ) exit;

return [

    'toggle_description_1' => [
        'type'           => 'html',
		'group'          => 'primary',
		'label'          => esc_html__( 'This is a message', 'ninja-forms' ),
		'value'          => esc_html__( '- This action requires a pre-existing connection between your site and Google Analytics 4. If your website is already connected to GA4, it will generate an Event on each new form submission. If your website is not already connected to GA4, it will do nothing.', 'ninja-forms' ),
		'width'          => 'full',
        'className'      => 'yellow-background-description top',
    ],
    'toggle_description_2' => [
        'type'           => 'html',
		'group'          => 'primary',
		'label'          => esc_html__( 'This is a message', 'ninja-forms' ),
		'value'          => esc_html__( '- Choose GA4 if your website integrates directly to GA4 using a Measurement ID (G-XXXXXXXXX).', 'ninja-forms' ),
		'width'          => 'full',
		'use_merge_tags' => true,
        'className'      => 'yellow-background-description',
    ],
    'toggle_description_3' => [
        'type'           => 'html',
		'group'          => 'primary',
		'label'          => esc_html__( 'This is a message', 'ninja-forms' ),
		'value'          => esc_html__( '- Choose GTM if your website integrates with GA4 through Google Tag Manager using a Container ID (GTM-XXXXXXX). A custom Tag is required. Please see our documentation for details. ', 'ninja-forms' ),
		'width'          => 'full',
		'use_merge_tags' => true,
        'className'      => 'yellow-background-description bottom',
    ],
    'method_type' => [
        'name' => 'method_type',
        'type' => 'button-toggle',
        'width' => 'full',
        'options' => [
            [ 'label' => esc_html__( 'GA4', 'ninja-forms' ), 'value' => 'ga4' ],
            [ 'label' => esc_html__( 'GTM', 'ninja-forms' ), 'value' => 'gtm' ]
        ],
        'group' => 'primary',
        'displayLabel' => 'none',
        'value' => 'ga4',
        'tooltip' => esc_html__( 'Choose GA4 if your website integrates directly to GA4 using a Measurement ID (G-XXXXXXXXX). Choose GTM if your website integrates with GA4 through Google Tag Manager using a Container ID (GTM-XXXXXXX)', 'ninja-forms' )
    ],
    'event_name' => [
        'name' => 'event_name',
        'type' => 'textbox',
        'group' => 'primary',
        'label' => esc_html__( 'Event Name', 'ninja-forms' ),
        'value' => '',
        'width' => 'full',
        'use_merge_tags' => true,
        'tooltip' => esc_html__(  'This is the name of the form submission Event as it will appear in GA4.', 'ninja-forms' )
    ],

];
