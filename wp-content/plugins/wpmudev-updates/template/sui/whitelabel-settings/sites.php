<?php
/**
 * The whitelabel sites configuration content.
 *
 * @var array                           $settings Settings.
 * @var WPMUDEV_Dashboard_Sui_Page_Urls $urls     URL class.
 * @package WPMUDEV_Dashboard
 * @since   4.11.1
 *
 */

defined( 'WPINC' ) || die();

// Enabled sites.
$sites = empty( $settings['labels_subsites'] ) ? array() : (array) $settings['labels_subsites'];

?>
<div class="sui-form-field">
	<label class="sui-label" for="wpmudev-labels-subsites-select">
		<?php echo esc_html__( 'Sites', 'wpmudev' ); ?>
	</label>
	<select
		class="sui-select search-no-arrow wpmudev-search"
		id="wpmudev-labels-subsites-select"
		data-placeholder="<?php esc_html_e( 'Start typing the name of website...', 'wpmudev' ); ?>"
		data-minimum-input-length="3"
		data-search-action="wdp-sitesearch"
		data-search-parent="wpmudev-labels-subsites-content"
		data-hash="<?php echo esc_attr( wp_create_nonce( 'sitesearch' ) ); ?>"
		data-language-searching="<?php esc_attr_e( 'Searching...', 'wpmudev' ); ?>"
		data-language-noresults="<?php esc_attr_e( 'No results found', 'wpmudev' ); ?>"
		data-language-error-load="<?php esc_attr_e( 'Searching...', 'wpmudev' ); ?>"
		data-language-input-tooshort="<?php esc_attr_e( 'Type minimum 3 characters to start searching', 'wpmudev' ); ?>"
	>
	</select>
</div>
<?php if ( ! empty( $sites ) ) : ?>
	<div class="sui-form-field">
		<label class="sui-label" for="wpmudev-labels-subsites-list">
			<?php echo esc_html__( 'Added Sites', 'wpmudev' ); ?>
		</label>
		<div class="dashui-list-items">
			<?php foreach ( $sites as $site_id ) : ?>
				<?php $home_url = get_home_url( $site_id ); ?>
				<?php
				if ( empty( $home_url ) ) :
					continue;
				endif;
				?>
				<div class="dashui-item" id="wpmudev-labels-subsites-list">
					<input type="hidden" name="labels_subsites[]" value="<?php echo (int) $site_id; ?>">
					<div class="dashui-item-name">
						<span><?php echo esc_html( str_replace( array( 'https://', 'http://' ), '', $home_url ) ); ?></span>
					</div>
					<div class="dashui-item-action">
						<?php
						$remove_url = wp_nonce_url(
							add_query_arg(
								array(
									'site'   => $site_id,
									'action' => 'whitelabel-setup',
									'status' => 'site-remove',
								),
								$urls->whitelabel_url
							),
							'whitelabel-setup',
							'hash'
						);
						?>
						<a
							href="<?php echo esc_url( $remove_url ); ?>"
							class="sui-button-icon sui-button-red js-remove-whitelabel-site"
						>
							<span class="sui-loading-text">
								<i class="sui-icon-trash" aria-hidden="true"></i>
							</span>
							<i class="sui-icon-loader sui-loading"></i>
						</a>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
<?php endif; ?>
