<?php
/**
 * Metabox - Schema Tab
 *
 * @package    RankMath
 * @subpackage RankMath\Schema
 */

use RankMath\Helper;

defined( 'ABSPATH' ) || exit;

$notice = '<div class="components-notice category-notice rank-math-notice is-warning">' . esc_html__( 'We handle the Schema on Archive page automatically. Please add schema from this tab only if you are sure what you are doing.', 'rank-math-pro' ) . '</div>';

$cmb->add_field(
	[
		'id'         => 'rank_math_schema_generator',
		'type'       => 'raw',
		'content'    => $notice . '<div id="rank-math-schema-generator"></div>',
		'save_field' => false,
	]
);

$cmb->add_field(
	[
		'id'         => 'rank-math-schemas',
		'type'       => 'textarea',
		'classes'    => 'hidden',
		'save_field' => false,
	]
);

$cmb->add_field(
	[
		'id'         => 'rank-math-schemas-delete',
		'type'       => 'textarea',
		'default'    => '[]',
		'classes'    => 'hidden',
		'save_field' => false,
	]
);
