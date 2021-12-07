<?php
/**
 * Caching meta box on dashboard page.
 *
 * @package Hummingbird
 *
 * @var string $caching_url            Caching URL.
 * @var array  $human_results          Array of results. Readable format.
 * @var array  $recommended            Array of recommended values.
 * @var array  $results                Array of results. Raw.
 * @var int    $issues                 Number of issues.
 * @var bool   $show_cf_notice         Show the Cloudflare notice.
 * @var string $cf_notice              Cloudflare copy to show.
 * @var string $cf_connect_url         Connect Cloudflare URL.
 * @var array  $caching_type_tooltips  Caching types array if browser caching is enabled.
 * @var string $configure_caching_url  Caching module URL.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="<?php echo $show_cf_notice ? 'sui-box-body' : ''; ?>">
	<p><?php esc_html_e( "Store temporary data on your visitors' devices so that they don’t have to download assets twice if they don’t have to.", 'wphb' ); ?></p>
	<?php
	if ( $issues ) {
		$this->admin_notices->show_inline(
			sprintf( /* translators: %s: Number of issues */
				__( '%1$s of your cache types don’t meet the recommended expiry period of 1 year. Configure browser caching <a href="%2$s" id="configure-link">here</a>.', 'wphb' ),
				absint( $issues ),
				esc_attr( $configure_caching_url )
			),
			'warning'
		);
	} else {
		$this->admin_notices->show_inline( esc_html__( 'All of your cache types meet the recommended expiry period of 1 year. Great work!', 'wphb' ) );
	}
	?>

	<ul class="sui-list sui-no-margin-bottom">
		<li class="sui-list-header">
			<span><?php esc_html_e( 'File type', 'wphb' ); ?></span>
			<span><?php esc_html_e( 'Current expiry', 'wphb' ); ?></span>
		</li>

		<?php
		foreach ( $human_results as $type => $result ) :
			$index = strtolower( $type );
			if ( $result && $recommended[ $index ]['value'] <= $results[ $type ] ) {
				$result_status       = $result;
				$result_status_color = 'success';
				$tooltip_text        = __( 'Caching is enabled', 'wphb' );
			} elseif ( $result ) {
				$result_status       = $result;
				$result_status_color = 'warning';
				$tooltip_text        = __( "Caching is enabled but you aren't using our recommended value", 'wphb' );
			} else {
				$result_status       = __( 'Disabled', 'wphb' );
				$result_status_color = 'warning';
				$tooltip_text        = __( 'Caching is disabled', 'wphb' );
			}
			?>
			<li>
				<span class="sui-list-label">
					<span class="wphb-filename-extension wphb-filename-extension-<?php echo esc_attr( $index ); ?> sui-tooltip sui-tooltip-top-left sui-tooltip-constrained" data-tooltip="<?php echo esc_attr( $caching_type_tooltips[ $index ] ); ?>">
						<?php
						switch ( $index ) {
							case 'javascript':
							default:
								echo 'js';
								break;
							case 'images':
								echo 'img';
								break;
							case 'css':
							case 'media':
								echo esc_html( $index );
								break;
						}
						?>
					</span>
					<span class="wphb-filename-extension-label"><?php echo esc_html( $type ); ?></span>
				</span>
				<span class="sui-list-detail">
					<span class="sui-tag sui-tag-<?php echo esc_attr( $result_status_color ); ?> sui-tooltip sui-tooltip-constrained sui-tooltip-top-right-mobile" data-tooltip="<?php echo esc_attr( $tooltip_text ); ?>">
						<?php echo esc_html( $result_status ); ?>
					</span>
				</span>
			</li>

		<?php endforeach; ?>
	</ul>
</div>
<?php if ( $show_cf_notice ) : ?>
	<div class="sui-box-settings-row sui-upsell-row cf-dash-notice sui-margin-top">
		<?php if ( ! apply_filters( 'wpmudev_branding_hide_branding', false ) ) : ?>
			<img class="sui-image sui-upsell-image"
				src="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/graphic-hb-cf-sell.png' ); ?>"
				srcset="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/graphic-hb-cf-sell@2x.png' ); ?> 2x"
				alt="<?php esc_attr_e( 'Connect your account to Cloudflare', 'wphb' ); ?>">
		<?php endif; ?>
		<?php
		$this->admin_notices->show_inline(
			$cf_notice,
			apply_filters( 'wpmudev_branding_hide_branding', false ) ? 'grey' : 'sui-upsell-notice',
			sprintf( /* translators: %s: Connect Cloudflare link */
				__( ' <a href="%s">Connect your account</a> to control your settings via Hummingbird.', 'wphb' ),
				esc_url( $cf_connect_url )
			),
			sprintf( /* translators: %1$s - opening a tag, %2$s - </a> */
				esc_html__( '%1$sDismiss%2$s', 'wphb' ),
				'<a href="#" id="dismiss-cf-notice">',
				'</a>'
			)
		);
		?>
	</div>
<?php endif; ?>
