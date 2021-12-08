<?php
/**
 * Browser caching meta box on dashboard page when Cloudflare is active.
 *
 * @package Hummingbird
 *
 * @var string $caching_url              Caching URL.
 * @var array  $human_results            Array of results. Readable format.
 * @var array  $recommended              Array of recommended values.
 * @var array  $results                  Array of results. Raw.
 * @var int    $issues                   Number of issues.
 * @var bool   $show_cf_notice           Show the Cloudflare notice.
 * @var string $cf_notice                Cloudflare copy to show.
 * @var string $cf_connect_url           Connect Cloudflare URL.
 * @var array  $caching_type_tooltips    Caching types array if browser caching is enabled.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
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

	<li>
		<span class="sui-list-label">
			<?php
			foreach ( $human_results as $type => $result ) :
				if ( $result && $recommended[ $type ]['value'] <= $results[ $type ] ) {
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
				<span class="wphb-filename-extension wphb-filename-extension-<?php echo esc_attr( $type ); ?> sui-tooltip sui-tooltip-top-left sui-tooltip-constrained" data-tooltip="<?php echo esc_attr( $caching_type_tooltips[ $type ] ); ?>">
					<?php
					switch ( $type ) {
						case 'javascript':
						default:
							echo 'js';
							break;
						case 'images':
							echo 'img';
							break;
						case 'css':
						case 'media':
							echo esc_html( $type );
							break;
					}
					?>
				</span>
			<?php endforeach; ?>
			<span class="wphb-filename-extension wphb-filename-extension-other tooltip-left" tooltip="<?php echo esc_attr( $caching_type_tooltips['cloudflare'] ); ?>">
				oth
			</span>
		</span>
		<span class="sui-list-detail">
			<span class="sui-tag sui-tag-<?php echo esc_attr( $result_status_color ); ?> sui-tooltip sui-tooltip-top-left sui-tooltip-constrained" data-tooltip="<?php echo esc_attr( $tooltip_text ); ?>">
				<?php echo esc_html( $result_status ); ?>
			</span>
		</span>
	</li>
</ul>
