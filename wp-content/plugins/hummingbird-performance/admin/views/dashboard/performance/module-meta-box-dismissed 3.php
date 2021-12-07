<?php
/**
 * Performance report dismissed meta box on dashboard page.
 *
 * @package Hummingbird
 *
 * @var bool   $notifications     Performance cron reports status.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<p>
	<?php esc_html_e( 'Run a Google PageSpeed test and get itemized insight (with fixes) on where you can improve your website’s performance.', 'wphb' ); ?>
</p>

<?php
$this->admin_notices->show_inline(
	esc_html__( 'You chose to ignore your last performance test. Run a new test to see new recommendations.', 'wphb' ),
	'grey'
);
