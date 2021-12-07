<?php
/**
 * Asset optimization summary meta box.
 *
 * @package Hummingbird
 *
 * @var string $compressed_size  Compressed size string.
 * @var int    $enqueued_files   Number of enqueued files.
 * @var bool   $is_member        Is WPMU DEV member.
 * @var string $percentage       Percentage string.
 * @var bool   $use_cdn          CDN status.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$branded_image = apply_filters( 'wpmudev_branding_hero_image', '' );
?>

<?php if ( $branded_image ) : ?>
	<div class="sui-summary-image-space" aria-hidden="true" style="background-image: url('<?php echo esc_url( $branded_image ); ?>')"></div>
<?php else : ?>
	<div class="sui-summary-image-space" aria-hidden="true"></div>
<?php endif; ?>
<div class="sui-summary-segment">
	<div class="sui-summary-details">
		<?php if ( ! $percentage || '0.0' === $percentage ) : ?>
			<?php if ( 'basic' === $this->mode ) : ?>
				<span class="sui-tooltip" data-tooltip="<?php esc_attr_e( 'All assets are auto-compressed', 'wphb' ); ?>">
					<span class="sui-icon-check-tick sui-lg" aria-hidden="true"></span>
				</span>
			<?php else : ?>
				&mdash;
			<?php endif; ?>
		<?php else : ?>
			<span class="sui-summary-large">
				<?php echo esc_html( $percentage ); ?>%
			</span>
		<?php endif; ?>
		<span class="sui-summary-sub"><?php esc_html_e( 'Compression savings', 'wphb' ); ?></span>
	</div>
</div>
<div class="sui-summary-segment">
	<ul class="sui-list">
		<li>
			<span class="sui-list-label"><?php esc_html_e( 'Total files', 'wphb' ); ?></span>
			<span class="sui-list-detail"><?php echo (int) $enqueued_files; ?></span>
		</li>
		<li>
			<span class="sui-list-label"><?php esc_html_e( 'Filesize reductions', 'wphb' ); ?></span>
			<span class="sui-list-detail">
				<?php if ( 'basic' === $this->mode && 0 === (int) $compressed_size ) : ?>
					<?php esc_html_e( 'Files are compressed', 'wphb' ); ?> <span class="sui-icon-check-tick sui-md" aria-hidden="true"></span>
				<?php else : ?>
					<?php echo $compressed_size; ?>kb
				<?php endif; ?>
			</span>
		</li>
		<li>
			<span class="sui-list-label">
				<?php esc_html_e( 'WPMU DEV CDN', 'wphb' ); ?>
				<?php if ( ! $is_member ) : ?>
					<span class="sui-tag sui-tag-pro"><?php esc_html_e( 'Pro', 'wphb' ); ?></span>
				<?php endif; ?>
			</span>
			<span class="sui-list-detail">
				<?php if ( ! is_multisite() && $is_member ) : ?>
					<label class="sui-toggle sui-tooltip sui-tooltip-top-right" data-tooltip="<?php esc_html_e( 'Enable WPMU DEV CDN', 'wphb' ); ?>">
						<input type="checkbox" name="use_cdn" id="use_cdn" <?php checked( $use_cdn && $is_member ); ?> <?php disabled( ! $is_member ); ?>>
						<span class="sui-toggle-slider"></span>
					</label>
				<?php elseif ( ! is_multisite() && ! $is_member ) : ?>
					<a href="<?php echo esc_url( \Hummingbird\Core\Utils::get_link( 'plugin', 'hummingbird_topsummary_cdnbutton' ) ); ?>" target="_blank" class="sui-button sui-button-purple sui-tooltip sui-tooltip-top-right" data-tooltip="<?php esc_html_e( 'Host your files on WPMU DEV’s blazing-fast CDN', 'wphb' ); ?>">
						<?php esc_html_e( 'Try CDN Free', 'wphb' ); ?>
					</a>
				<?php elseif ( $use_cdn && $is_member ) : ?>
					<span class="sui-tooltip sui-tooltip-top-right" data-tooltip="<?php esc_html_e( 'The Network Admin has the WPMU DEV CDN turned on', 'wphb' ); ?>">
						<span class="sui-icon-check-tick sui-md sui-info" aria-hidden="true"></span>
					</span>
				<?php else : ?>
					<span class="sui-tag sui-tag-disabled sui-tooltip sui-tooltip-top-right" data-tooltip="<?php esc_html_e( 'The Network Admin has the WPMU DEV CDN turned off', 'wphb' ); ?>">
						<?php esc_html_e( 'Disabled', 'wphb' ); ?>
					</span>
				<?php endif; ?>
			</span>
		</li>
	</ul>
</div>
