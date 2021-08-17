<?php
/**
 * Asset optimization network meta box on dashboard page.
 *
 * @package Hummingbird
 *
 * @var bool   $enabled           Asset optimization status.
 * @var bool   $use_cdn           CDN status.
 * @var bool   $log               Debug log status.
 * @var bool   $use_cdn_disabled  Can use CDN?
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<p>
	<?php esc_html_e( 'Compress, combine and position your assets to dramatically improve your pageload speed. Choose which user roles can configure Asset Optimization.', 'wphb' ); ?>
</p>

<?php if ( $enabled ) : ?>
	<?php $this->admin_notices->show_inline( esc_html__( 'Asset Optimization is enabled for subsites.', 'wphb' ) ); ?>

	<ul class="sui-list sui-no-margin-bottom">
		<li>
			<span class="sui-list-label">
				<span><?php esc_html_e( 'Minimum user role', 'wphb' ); ?></span>
			</span>

			<span class="sui-list-detail">
				<?php $ao_text = 'super-admins' === $enabled ? __( 'Super Admins', 'wphb' ) : __( 'Blog Admins', 'wphb' ); ?>
				<span><?php echo esc_html( $ao_text ); ?></span>
			</span>
		</li>
		<li <?php echo $use_cdn_disabled ? 'class="sui-disabled"' : ''; ?>>
			<span class="sui-list-label">
				<span><?php esc_html_e( 'WPMU DEV CDN', 'wphb' ); ?></span>
			</span>

			<span class="sui-list-detail">
				<?php if ( $use_cdn_disabled ) : ?>
					<a class="sui-button sui-button-ghost sui-button-purple" href="<?php echo \Hummingbird\Core\Utils::get_link( 'plugin', 'hummingbird_dash_summary_pro_tag' ); ?>" target="_blank">
						<?php esc_html_e( 'Pro Feature', 'wphb' ); ?>
					</a>
				<?php else : ?>
					<?php $cdn_text = $use_cdn ? __( 'Active', 'wphb' ) : __( 'Disabled', 'wphb' ); ?>
					<span><?php echo esc_html( $cdn_text ); ?></span>
				<?php endif; ?>
			</span>
		</li>
		<li>
			<span class="sui-list-label">
				<span><?php esc_html_e( 'Debug logs', 'wphb' ); ?></span>
			</span>

			<span class="sui-list-detail">
				<?php $log_text = $log ? __( 'Enabled', 'wphb' ) : __( 'Disabled', 'wphb' ); ?>
				<span><?php echo esc_html( $log_text ); ?></span>
			</span>
		</li>
	</ul>
<?php else : ?>
	<?php $this->admin_notices->show_inline( esc_html__( 'Asset Optimization is disabled for subsites.', 'wphb' ), 'grey' ); ?>
<?php endif; ?>

<?php if ( isset( $_GET['minify-instructions'] ) ) : ?>
	<?php $this->admin_notices->show_inline( esc_html__( 'Please, activate minification first. A new menu will appear in every site on your Network.', 'wphb' ), 'warning' ); ?>
<?php endif; ?>
