<?php
/**
 * Reduce server response times (TTFB) audit.
 *
 * @since 2.0.0
 * @package Hummingbird
 *
 * @var stdClass $audit  Audit object.
 */

use Hummingbird\Core\Modules\Performance;
use Hummingbird\Core\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<h4><?php esc_html_e( 'Overview', 'wphb' ); ?></h4>
<p>
	<?php esc_html_e( "Time To First Byte identifies the time it takes for a visitor's browser to receive the first byte of page content from the server. Ideally, TTFB for your server should be under 600 ms. ", 'wphb' ); ?>
</p>

<h4><?php esc_html_e( 'Status', 'wphb' ); ?></h4>
<?php if ( isset( $audit->errorMessage ) && ! isset( $audit->score ) ) {
	$this->admin_notices->show_inline( /* translators: %s - error message */
		sprintf( esc_html__( 'Error: %s', 'wphb' ), esc_html( $audit->errorMessage ) ),
		'error'
	);
	return;
}
?>
<?php if ( isset( $audit->score ) && 1 === $audit->score ) : ?>
	<?php
	$this->admin_notices->show_inline(
		sprintf( /* translators: %s - number of ms */
			esc_html__( 'Nice! TTFB for your server was %s.', 'wphb' ),
			esc_html( str_replace( 'Root document took ', '', $audit->displayValue ) )
		)
	);
	?>
<?php else : ?>
	<?php
	$this->admin_notices->show_inline(
		sprintf( /* translators: %s - number of ms */
			esc_html__( 'It took %s to receive the first byte of page content.', 'wphb' ),
			esc_html( str_replace( 'Root document took ', '', $audit->displayValue ) )
		),
		Performance::get_impact_class( $audit->score )
	);
	?>

	<h4><?php esc_html_e( 'How to fix', 'wphb' ); ?></h4>
	<ol>
		<li>
		<?php
		if ( apply_filters( 'wpmudev_branding_hide_branding', false ) ) {
			esc_html_e( 'If yours is a high traffic site, upgrade your server resources to improve your server response time. Not happy with your server response time? Talk with your current host about server resource upgrades.', 'wphb' );
		} else {
			if ( ! isset( $_SERVER['WPMUDEV_HOSTED'] ) ) {
				printf( /* translators: %1$s - link to Hosting project page, %2$s - closing a tag */
					esc_html__( 'TTFB largely depends on your server’s performance capacity. Host your website on %1$sWPMU DEV Hosting%2$s which comes with features such as dedicated resources, object caching, support for the latest PHP versions, and a blazing-fast CDN.', 'wphb' ),
					'<a href="' . esc_html( Utils::get_link( 'hosting', 'hummingbird_test_response_time_hosting_upgrade_plan_link' ) ) . '" target="_blank">',
					'</a>'
				);
			} else {
				esc_html_e( 'If yours is a high traffic site, upgrade your server resources to improve your server response time.', 'wphb' );
			}
			?>

			<div class="sui-settings-box">
				<img class="sui-image sui-margin-right"
					src="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hosting-image.png' ); ?>"
					srcset="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hosting-image@2x.png' ); ?> 2x"
					alt="<?php esc_attr_e( 'WPMU DEV Hosting', 'wphb' ); ?>">

				<?php if ( ! isset( $_SERVER['WPMUDEV_HOSTED'] ) ) : ?>
					<p><?php esc_html_e( 'Not happy with your server response time? Talk with your current host about server resource upgrades, or check out WPMU DEV’s flexible hosting plans to explore other options.', 'wphb' ); ?></p>
					<div style="clear: both"></div>
					<p>
						<?php
						printf( /* translators: %1$s - opening a tag, %2$s - closing a tag */
							esc_html__( 'Here is the average TTFB behaviour for WPMU DEV hosting that we tested and compared with other hosting providers. Check it out %1$shere%2$s.', 'wphb' ),
							'<a href="https://wpmudev.com/wpmu-dev-hosting-vs/?utm_source=hummingbird&utm_medium=plugin&utm_campaign=hummingbird_test_response_time_hosting_ttfb_comparison_link" target="_blank">',
							'</a>'
						)
						?>
					</p>

					<div class="wphb-ttfb-measurements">
						<div class="sui-form-field sui-margin-right">
							<label for="button_margin_l" id="button_margin_l_label" class="sui-label">
								<strong><?php esc_html_e( 'TTFB Average', 'wphb' ); ?></strong> &nbsp;
								(<?php esc_html_e( 'All Locations', 'wphb' ); ?>)
							</label>
							<input id="button_margin_l" class="sui-form-control sui-input-sm"" aria-labelledby="button_margin_l_label" value="476 ms" disabled>
						</div>
						<div class="sui-form-field">
							<label for="button_margin_r" id="button_margin_r_label" class="sui-label">
								<strong><?php esc_html_e( 'TTFB Average', 'wphb' ); ?></strong> &nbsp;
								(<?php esc_html_e( 'Geo-Optimized', 'wphb' ); ?>)
							</label>
							<input id="button_margin_r" class="sui-form-control sui-input-sm"" aria-labelledby="button_margin_r_label" value="81.14 ms" disabled>
						</div>
					</div>

					<p><?php esc_html_e( 'For more details, check out our hosting plans below.', 'wphb' ); ?></p>
					<a href="<?php echo esc_url( Utils::get_link( 'hosting', 'hummingbird_test_response_time_hosting_upgrade_plan_link' ) ); ?>" target="_blank" class="sui-button sui-button-purple" role="button">
						<?php esc_html_e( 'View Plans', 'wphb' ); ?>
					</a>
				<?php else : ?>
					<p><?php esc_html_e( 'Getting more traffic than initially expected? Check out our other hosting plans for additional SSD storage and RAM options.', 'wphb' ); ?></p>
					<a href="<?php echo esc_url( Utils::get_link( 'hosting', 'hummingbird_test_response_time_hosting_upgrade_plan_link' ) ); ?>" target="_blank" class="sui-button sui-button-blue" role="button">
						<?php esc_html_e( 'View Plans', 'wphb' ); ?>
					</a>
				<?php endif; ?>
			</div>
		<?php } ?>
		</li>

		<?php if ( ! Utils::get_module( 'page_cache' )->is_active() ) : ?>
			<li>
				<?php
				esc_html_e( "Enable Hummingbird's page caching. This can substantially improve your server response time for logged out visitors and search engine bots.", 'wphb' );
				$url = Utils::get_admin_menu_url( 'caching' );
				?>
				<?php if ( $url ) : ?>
					<br><a href="<?php echo esc_url( $url ); ?>" class="sui-button">
						<?php esc_html_e( 'Configure Page Caching', 'wphb' ); ?>
					</a>
				<?php endif; ?>
			</li>
		<?php endif; ?>

		<li>
			<?php
			printf( /* translators: %1$s - link to Query Monitor wp.org page, %2$s - closing a tag */
				esc_html__( 'Usually, your installed WordPress plugins have a huge impact on your page generation time. Some are horribly inefficient, and some are just resource intensive. Test the performance impact of your plugins by using a plugin like %1$sQuery Monitor%2$s, then remove the worst offenders, or replace them with a suitable alternative.', 'wphb' ),
				'<a href="https://wordpress.org/plugins/query-monitor/" target="_blank">',
				'</a>'
			);
			?>
		</li>
	</ol>
<?php endif; ?>
