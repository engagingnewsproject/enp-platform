<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return apply_filters( 'ninja_forms_action_recaptcha_settings', array(
	'score' => array(
		'name'           => 'score',
		'type'           => 'number',
		'min_val'        => 0,
		'max_val'        => 1,
		'step'           => '0.1',
		'value'          => '0.5',
		'group'          => 'advanced',
		'label'          => esc_html__( 'Score Threshold', 'ninja-forms' ),
		'placeholder'    => esc_attr__( 'Score', 'ninja-forms' ),
		'width'          => 'one-half',
		'use_merge_tags' => false,
		'desc'           => esc_attr__( 'reCAPTCHA v3 returns a score (1.0 is very likely a good interaction, 0.0 is very likely a bot). Configure the score threshold for your form submission.', 'ninja-forms' ),
	),
) );