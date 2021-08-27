<?php
/**
 * Asset optimization table (basic view).
 *
 * @package Hummingbird
 *
 * @since 1.7.1
 *
 * @var \Hummingbird\Admin\Page $this
 *
 * @var string $fonts_rows       Table rows for Google fonts.
 * @var int    $error_time_left  Time left before next scan is possible.
 * @var bool   $is_server_error  Server error status.
 * @var string $scripts_rows     Table rows for minified scripts.
 * @var array  $selector_filter  List of items to filter by.
 * @var array  $server_errors    List of server errors.
 * @var string $styles_rows      Table rows for minified styles.
 * @var string $others_rows      Table rows for files not hosted locally.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="wphb-minification-files">
	<div class="wphb-minification-files-header">

		<p class="sui-margin-bottom">
			<?php esc_html_e( 'Optimizing your assets will compress and organize them in a way that improves page load times. You can choose to use our automated options, or manually configure each file yourself.', 'wphb' ); ?>
		</p>

		<div class="sui-actions" style="float: right">
			<small>
				<a href="#" id="wphb-advanced-hdiw-link" data-modal-open="manual-ao-hdiw-modal-content" data-modal-mask="true">
					<?php esc_html_e( 'How Does it Work?', 'wphb' ); ?>
				</a>
			</small>
		</div>

		<div class="sui-side-tabs">
			<div class="sui-tabs-menu">
				<label id="wphb-ao-auto-label" for="wphb-ao-auto" class="sui-tab-item">
					<input type="radio" name="asset_optimization_mode" value="auto" id="wphb-ao-auto">
					<?php esc_html_e( 'Automatic', 'wphb' ); ?>
				</label>
				<label id="wphb-ao-manual-label" for="wphb-ao-manual" class="sui-tab-item active">
					<input type="radio" name="asset_optimization_mode" value="manual" id="wphb-ao-manual" checked="checked">
					<?php esc_html_e( 'Manual', 'wphb' ); ?>
				</label>
			</div>
		</div>

		<p class="sui-description">
			<?php esc_html_e( 'Manually configure your optimization settings (compress, combine, move, inline, defer, async, and preload) and then publish your changes.', 'wphb' ); ?>
		</p>
	</div>

	<?php
	if ( get_transient( 'wphb_infinite_loop_warning' ) ) {
		ob_start();
		esc_html_e( 'Issues processing queue. Hummingbird performance can be degraded.', 'wphb' );
		echo esc_html( '&nbsp;' );
		\Hummingbird\Core\Utils::still_having_trouble_link();
		$text = ob_get_clean();
		$this->admin_notices->show_inline( $text, 'error' );
	}

	if ( $is_server_error ) {
		$message = sprintf( /* translators: %d: Time left before another retry. */
			__( 'It seems that we are having problems in our servers. Asset optimization will be turned off for %d minutes', 'wphb' ),
			$error_time_left
		) . '<br>' . $server_errors[0]->get_error_message();
		$this->admin_notices->show_floating( $message, 'error' );
	}

	do_action( 'wphb_asset_optimization_notice' );
	?>

	<div class="sui-box sui-box-sticky">
		<div class="sui-actions-left">
			<a class="sui-button button-notice disabled" id="bulk-update" >
				<?php esc_html_e( 'Bulk Update', 'wphb' ); ?>
			</a>
			<input type="submit" id="wphb-publish-changes" class="sui-button sui-button-blue disabled" name="submit" value="<?php esc_attr_e( 'Publish Changes', 'wphb' ); ?>"/>
		</div>

		<div class="sui-actions-right">
			<a href="#wphb-box-minification-enqueued-files" class="sui-button-icon sui-button-outlined" id="wphb-minification-filter-button">
				<span class="sui-icon-filter sui-md sui-fw" aria-hidden="true"></span>
			</a>
		</div>
	</div>

	<div class="sui-box wphb-minification-filter sui-hidden">
		<div class="sui-box-body">
			<label for="wphb-secondary-filter" class="sui-label">
				<?php esc_html_e( 'Display Files', 'wphb' ); ?>
			</label>

			<div class="sui-side-tabs sui-tabs">
				<div class="sui-tabs-menu">
					<label id="wphb-filter-all-label" for="wphb-filter-all" class="sui-tab-item active" checked="checked">
						<input type="radio" name="asset_optimization_filter" value="all" id="wphb-filter-all">
						<?php esc_html_e( 'All', 'wphb' ); ?>
					</label>
					<label id="wphb-filter-local-label" for="wphb-filter-local" class="sui-tab-item">
						<input type="radio" name="asset_optimization_filter" value="local" id="wphb-filter-local">
						<?php esc_html_e( 'Hosted', 'wphb' ); ?>
					</label>
					<label id="wphb-filter-external-label" for="wphb-filter-external" class="sui-tab-item">
						<input type="radio" name="asset_optimization_filter" value="external" id="wphb-filter-external">
						<?php esc_html_e( 'External', 'wphb' ); ?>
					</label>
				</div>
			</div>

			<div class="sui-row">
				<div class="sui-col">
					<div class="sui-form-field">
						<label for="wphb-secondary-filter" class="sui-label" aria-label="<?php esc_attr_e( 'Filter plugin or theme', 'wphb' ); ?>">
							<?php esc_html_e( 'Sort By', 'wphb' ); ?>
						</label>
						<select class="sui-select" name="wphb-secondary-filter" id="wphb-secondary-filter">
							<option value=""><?php esc_html_e( 'Choose Plugin or Theme', 'wphb' ); ?></option>
							<option value="other"><?php esc_html_e( 'Others', 'wphb' ); ?></option>
							<?php foreach ( $selector_filter as $secondary_filter ) : ?>
								<option value="<?php echo esc_attr( $secondary_filter ); ?>"><?php echo esc_html( $secondary_filter ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>

				<div class="sui-col">
					<div class="sui-form-field">
						<label for="wphb-s" class="sui-label" aria-label="<?php esc_attr_e( 'Search by name or extension', 'wphb' ); ?>">&nbsp;</label>
						<input type="text" id="wphb-s" class="sui-form-control" name="s" placeholder="<?php esc_attr_e( 'Search by name or extension', 'wphb' ); ?>" autocomplete="off">
					</div>
				</div>
			</div>
		</div>
		<div class="sui-box-footer">
			<button type="button" class="sui-button" id="wphb-clear-filters">
				<?php esc_html_e( 'Clear filters', 'wphb' ); ?>
			</button>
		</div>
	</div>

	<div class="wphb-minification-files-select">
		<label for="minification-bulk-file-css" class="screen-reader-text"><?php esc_html_e( 'Select all CSS files', 'wphb' ); ?></label>
		<label class="sui-checkbox">
			<input type="checkbox" id="minification-bulk-file-css" name="minification-bulk-files" class="wphb-minification-bulk-file-selector" data-type="CSS">
			<span aria-hidden="true"></span>
		</label>
		<h3><?php esc_html_e( 'CSS', 'wphb' ); ?></h3>
	</div>

	<div class="wphb-minification-files-table wphb-minification-files-advanced">
		<?php echo $styles_rows; ?>
	</div>

	<div class="wphb-minification-files-select">
		<label for="minification-bulk-file-js" class="screen-reader-text"><?php esc_html_e( 'Select all JavaScript files', 'wphb' ); ?></label>
		<label class="sui-checkbox">
			<input type="checkbox" id="minification-bulk-file-js" name="minification-bulk-files" class="wphb-minification-bulk-file-selector" data-type="JS">
			<span aria-hidden="true"></span>
		</label>
		<h3><?php esc_html_e( 'JavaScript', 'wphb' ); ?></h3>
	</div>

	<div class="wphb-minification-files-table wphb-minification-files-advanced">
		<?php echo $scripts_rows; ?>
	</div>

	<?php if ( '' !== $others_rows ) : ?>
		<div class="wphb-minification-files-select">
			<label for="minification-bulk-file-other" class="screen-reader-text"><?php esc_html_e( 'Select all Other files', 'wphb' ); ?></label>
			<label class="sui-checkbox">
				<input type="checkbox" id="minification-bulk-file-other" name="minification-bulk-files" class="wphb-minification-bulk-file-selector" data-type="OTHER">
				<span aria-hidden="true"></span>
			</label>
			<h3><?php esc_html_e( 'Other', 'wphb' ); ?></h3>
		</div>

		<div class="wphb-minification-files-table wphb-minification-files-advanced">
			<?php echo $others_rows; ?>
		</div>
	<?php endif; ?>

	<?php if ( '' !== $fonts_rows ) : ?>
		<div class="wphb-minification-files-select">
			<label for="minification-bulk-file-font" class="screen-reader-text"><?php esc_html_e( 'Select all Google fonts', 'wphb' ); ?></label>
			<label class="sui-checkbox">
				<input type="checkbox" id="minification-bulk-file-font" name="minification-bulk-files" class="wphb-minification-bulk-file-selector" data-type="FONT">
				<span aria-hidden="true"></span>
			</label>
			<h3><?php esc_html_e( 'Google Fonts', 'wphb' ); ?></h3>
		</div>

		<div class="wphb-minification-files-table wphb-minification-files-advanced">
			<?php echo $fonts_rows; ?>
		</div>
	<?php endif; ?>
</div><!-- end wphb-minification-files -->

<?php wp_nonce_field( 'wphb-enqueued-files' ); ?>
<?php $this->modal( 'bulk-update' ); ?>
