<?php
/**
 * Advanced tools: plugin health meta box.
 *
 * @since 2.7.0
 * @package Hummingbird
 *
 * @var array  $minify_groups   Array of wphb_minify_group posts.
 * @var int    $orphaned_metas  Orphaned rows in wp_postmeta.
 * @var bool   $preloading      Is preloading active.
 * @var int    $queue_size      Number of items in preloader queue.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<p>
	<?php esc_html_e( 'Plugin Health provides plugin information and advanced database usage information to fix critical site issues, right inside the plugin.', 'wphb' ); ?>
</p>

<?php
ob_start();
esc_html_e( 'When to action? This feature is implemented to fix critical issues. You can action only if you need to resolve a critical database issue related to one of the features below.', 'wphb' );
echo esc_html( '&nbsp;' );
\Hummingbird\Core\Utils::still_having_trouble_link();
$text = ob_get_clean();
$this->admin_notices->show_inline( $text, 'warning' );
?>

<?php if ( ! is_multisite() || is_network_admin() ) : ?>
	<div class="sui-box-settings-row sui-flushed">
		<div class="sui-box-settings-col-2">
			<div class="sui-row" style="margin-bottom: 10px">
				<div class="sui-col-sm-9">
					<h4><?php esc_html_e( 'Preload Caching - Database Data', 'wphb' ); ?></h4>
					<p class="sui-description">
						<?php esc_html_e( 'The Preload Cache feature improves page load speeds by pre-generating cached versions of your data. But on some sites, the preloader can get stuck and result in the non-stop generation of queue fields. This often can be fixed by deleting the database data and resetting the feature.', 'wphb' ); ?>
					</p>
				</div>
				<div class="sui-col-sm-3">
					<button role="button" class="sui-button sui-button-ghost sui-button-red" <?php disabled( ! $preloading || empty( $queue_size ) ); ?> id="btn-cache-purge">
						<span class="sui-button-text-default">
							<span class="sui-icon-trash sui-sm"></span>
							<?php esc_html_e( 'Delete', 'wphb' ); ?>
						</span>
						<span class="sui-button-text-onload">
							<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
							<?php esc_html_e( 'Deleting...', 'wphb' ); ?>
						</span>
					</button>
				</div>
			</div>

			<table class="sui-table wphb-sys-info-table">
				<tr>
					<td><?php esc_html_e( 'Field Name', 'wphb' ); ?> &mdash; <strong>wphb_cache_preload_batch</strong></td>
					<td>
						<?php esc_html_e( 'Rows', 'wphb' ); ?> &mdash; <span id="count-cache"><?php echo (int) $queue_size; ?></span>
					</td>
				</tr>
			</table>
		</div>
	</div>
<?php endif; ?>

<?php if ( ! is_multisite() || ! is_network_admin() ) : ?>
	<div class="sui-box-settings-row sui-flushed">
		<div class="sui-box-settings-col-2">
			<div class="sui-row" style="margin-bottom: 10px">
				<div class="sui-col-sm-9">
					<h4><?php esc_html_e( 'Orphaned Asset Optimization Metadata', 'wphb' ); ?></h4>
					<p class="sui-description">
						<?php esc_html_e( 'When an asset is optimized, it is mapped to a hidden custom post type, which has multiple entries of metadata in the wp_postmeta table. Clearing Asset Optimization cache should remove both the custom post type and its multiple meta fields. But in rare cases, when a custom post type is removed, the data in the wp_postmeta table remains and can bloat the database. This can be fixed by deleting the data stored in the wp_postmeta table.', 'wphb' ); ?>
						<br>
						<strong><?php esc_html_e( 'Note: The data we are referring to do not relate to the posts in the wp_posts table.', 'wphb' ); ?></strong>
					</p>
				</div>
				<div class="sui-col-sm-3">
					<button role="button" class="sui-button sui-button-ghost sui-button-red" <?php disabled( empty( $orphaned_metas ) ); ?> data-modal-open="site-health-orphaned-modal" data-modal-open-focus="site-health-orphaned-clear">
						<span class="sui-icon-trash sui-sm" aria-hidden="true"></span>
						<?php esc_html_e( 'Delete', 'wphb' ); ?>
					</button>
				</div>
			</div>

			<table class="sui-table wphb-sys-info-table">
				<tr>
					<td><?php esc_html_e( 'Field Name', 'wphb' ); ?> &mdash; <strong>'_handles'</strong> <span class="sui-tooltip sui-tooltip-constrained" data-tooltip="'_handle_urls', '_handle_versions', '_extra', '_args', '_type', '_dont_minify', '_dont_combine', '_dont_enqueue', '_defer', '_inline', '_handle_dependencies, '_handle_original_sizes’, _handle_compressed_sizes', '_hash', '_file_id', '_url', '_expires'">+ 17</span></td>
					<td>
						<?php esc_html_e( 'Rows', 'wphb' ); ?> &mdash; <span id="count-ao-orphaned"><?php echo (int) $orphaned_metas; ?></span>
						<?php if ( $orphaned_metas >= 100 ) : ?>
							<span class="sui-tooltip sui-tooltip-constrained wphb-site-health-ai-icon" data-tooltip="<?php esc_attr_e( 'The orphaned asset optimization metadata rows exceed the acceptable limit. We recommend you delete this data to avoid unnecessarily bloating the database.', 'wphb' ); ?>">
								<span class="sui-icon-info sui-sm sui-warning" aria-hidden="true"></span>
							</span>
						<?php endif; ?>
					</td>
				</tr>
			</table>
		</div>
	</div>

	<div class="sui-box-settings-row sui-flushed">
		<div class="sui-box-settings-col-2">
			<div class="sui-row" style="margin-bottom: 10px">
				<div class="sui-col-sm-9">
					<h4><?php esc_html_e( 'Asset Optimization - Database Data', 'wphb' ); ?></h4>
					<p class="sui-description">
						<?php esc_html_e( 'The Asset Optimization feature optimizes your files and in doing so, it creates database entries. Problems related to Asset Optimization can be settled by deleting the database data, which will substantially reset the feature.', 'wphb' ); ?>
					</p>
				</div>
				<div class="sui-col-sm-3">
					<button role="button" class="sui-button sui-button-ghost sui-button-red" <?php disabled( ! count( $minify_groups ) ); ?> id="btn-minify-purge" aria-live="polite">
						<span class="sui-button-text-default">
							<span class="sui-icon-trash sui-sm" aria-hidden="true"></span>
							<?php esc_html_e( 'Delete', 'wphb' ); ?>
						</span>
						<span class="sui-button-text-onload">
							<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
							<?php esc_html_e( 'Deleting...', 'wphb' ); ?>
						</span>
					</button>
				</div>
			</div>

			<table class="sui-table wphb-sys-info-table">
				<tr>
					<td><?php esc_html_e( 'Field Name', 'wphb' ); ?> &mdash; <strong>wphb_minify_group</strong></td>
					<td>
						<?php esc_html_e( 'Rows', 'wphb' ); ?> &mdash; <span id="count-minify"><?php echo count( $minify_groups ); ?></span>
					</td>
				</tr>
			</table>
		</div>
	</div>
<?php endif; ?>
