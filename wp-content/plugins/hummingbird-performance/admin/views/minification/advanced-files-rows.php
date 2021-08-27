<?php
/**
 * Asset optimization row (advanced view).
 *
 * @package Hummingbird
 *
 * @var string      $base_name          Base name.
 * @var bool|string $compressed_size    False if no compressed size. Or size.
 * @var string      $component          Theme or Plugin - Component which the file belongs to.
 * @var bool        $disabled           Enabled or disabled state.
 * @var array       $disable_switchers  Array of disabled fields.
 * @var string      $ext                File extension. Possible values: CSS, OTHER, JS.
 * @var string      $filter             Filter string for filtering.
 * @var string      $full_src           File URL.
 * @var array       $item               File info.
 * @var bool        $is_local           Asset is local or external.
 * @var bool|string $original_size      False if no original size. Or size.
 * @var bool        $minified_file      True if site is file is already minified (extension *.min.*).
 * @var array       $options            Asset optimization settings.
 * @var bool        $processed          True file has been processed (compressed).
 * @var bool        $compressed         True if processed file is smaller than original file.
 * @var string      $position           File position. Possible values: '' or 'footer'.
 * @var string      $rel_src            Relative path to file.
 * @var bool|array  $row_error          False if no error, or array with error.
 * @var string      $type               Possible values: styles, scripts or other.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<input type="hidden" name="<?php echo esc_attr( $base_name ); ?>[handle]" value="<?php echo esc_attr( $item['handle'] ); ?>">
<div class="wphb-border-row<?php echo ( $disabled ) ? ' disabled' : ''; ?>"
	id="wphb-file-<?php echo esc_attr( $ext . '-' . $item['handle'] ); ?>"
	data-filter="<?php echo esc_attr( $item['handle'] . ' ' . $ext ); ?>"
	data-filter-secondary="<?php echo esc_attr( $filter ); echo 'OTHER' === $ext ? 'other' : ''; ?>"
	data-filter-type="<?php echo $is_local ? 'local' : 'external'; ?>">
	<?php if ( $processed && ! $compressed && ! preg_match( '/\.min\.(css|js)/', $full_src ) && ! in_array( $item['handle'], $options['dont_minify'][ $type ], true ) ) : ?>
		<span class="wphb-row-status wphb-row-status-already-compressed sui-tooltip sui-tooltip-top-left sui-tooltip-constrained"
			data-tooltip="<?php esc_attr_e( 'This file has already been compressed – we recommend you turn off compression for this file to avoid issues', 'wphb' ); ?>"><span
			class="sui-icon-warning-alert" aria-hidden="true"></span></span>
	<?php elseif ( 'OTHER' === $ext ) : ?>
		<span class="wphb-row-status wphb-row-status-other sui-tooltip sui-tooltip-top-left sui-tooltip-constrained"
			data-tooltip="<?php esc_attr_e( 'This file has no linked URL, it will not be combined/minified', 'wphb' ); ?>"><span
			class="sui-icon-info" aria-hidden="true"></span></span>
	<?php endif; ?>
	<span class="wphb-row-status wphb-row-status-changed sui-tooltip sui-tooltip-top-left sui-hidden"
		data-tooltip="<?php esc_attr_e( 'You need to publish your changes for your new settings to take effect', 'wphb' ); ?>"><span
		class="sui-icon-update" aria-hidden="true"></span></span>

	<div class="fileinfo-group  <?php echo $compressed ? 'wphb-compressed' : ''; ?>">
		<div class="wphb-minification-file-select">
			<label for="minification-file-<?php echo esc_attr( $ext . '-' . $item['handle'] ); ?>" class="screen-reader-text"><?php esc_html_e( 'Select file', 'wphb' ); ?></label>
			<label class="sui-checkbox">
				<input type="checkbox" data-type="<?php echo esc_attr( $ext ); ?>" data-handle="<?php echo esc_attr( $item['handle'] ); ?>" id="minification-file-<?php echo esc_attr( $ext . '-' . $item['handle'] ); ?>" name="minification-file[]" class="wphb-minification-file-selector">
				<span aria-hidden="true"></span>
			</label>
		</div>

		<span class="sui-tooltip sui-tooltip-constrained"
			data-tooltip="<?php esc_attr_e( 'If you’ve made changes to this file, you can recompress it without resetting your file structure.', 'wphb' ); ?>">
			<span class="wphb-filename-extension wphb-filename-extension-<?php echo esc_attr( strtolower( $ext ) ); ?>">
				<?php echo esc_html( substr( $ext, 0, 3 ) ); ?>
			</span>
		</span>

		<div class="wphb-minification-file-info">
			<span><?php echo esc_html( $item['handle'] ); ?></span>

			<?php
			$delimiter = ! empty( $component ) ? ', ' : ' &mdash; ';
			if ( ( in_array( 'minify', $disable_switchers, true ) && ! $disabled ) || ! $original_size ) :
				?>
				<span><?php esc_html_e( 'Filesize Unknown', 'wphb' ) . esc_attr( $delimiter ); ?> </span>
			<?php elseif ( $minified_file || $original_size === $compressed_size || $disabled ) : ?>
				<span class="original-size"><?php echo esc_html( $original_size ) . 'KB' . esc_attr( $delimiter ); ?></span>
			<?php elseif ( $processed && $compressed ) : ?>
				<?php $size_diff = (float) $original_size - (float) $compressed_size; ?>
				<span class="sui-tooltip sui-tooltip-constrained" data-tooltip="<?php echo esc_html( 'This assets file size has been reduced by ' ) . esc_attr( $size_diff ); ?>KB">
					<span class="original-size crossed-out"><?php echo esc_html( $original_size ); ?>KB</span>
					<span class="sui-icon-chevron-down" aria-hidden="true"></span>
					<span class="compressed-size"><?php echo esc_html( $compressed_size ); ?>KB</span>
				</span>
				<span> <?php echo esc_attr( $delimiter ); ?> </span>
			<?php elseif ( $processed && ! $compressed ) : ?>
				<span class="original-size"><?php echo esc_html( $original_size ) . 'KB' . esc_attr( $delimiter ); ?></span>
			<?php elseif ( in_array( $item['handle'], $options['dont_minify'][ $type ], true ) ) : ?>
				<span class="original-size"><?php echo esc_html( $original_size ) . 'KB' . esc_attr( $delimiter ); ?></span>
			<?php else : ?>
				<span class="wphb-row-status wphb-row-status-queued sui-tooltip sui-tooltip-top-left sui-tooltip-constrained"
					data-tooltip="<?php esc_attr_e( 'This file is queued for compression. It will get optimized when someone visits a page that requires it.', 'wphb' ); ?>">
					<span class="sui-icon-loader sui-loading" aria-hidden="true"></span></span>
				<span class="original-size"><?php echo esc_html( $original_size ) . 'KB' . esc_attr( $delimiter ); ?></span>
				<?php
			endif;
			if ( ! empty( $component ) ) :
				?>
				<span class="component"><?php echo esc_attr( $component ) . ' - ' . esc_attr( $filter ) . ', '; ?></span>
			<?php endif; ?>

			<a href="<?php echo esc_url( $full_src ); ?>" target="_blank">
				<?php echo esc_html( urldecode( basename( $rel_src ) ) ); ?>
			</a>
		</div>
	</div><!-- end fileinfo-group -->

	<div class="wphb-minification-row-details">
		<div class="checkbox-group wphb-minification-advanced-group">
			<?php
			if ( in_array( 'minify', $disable_switchers, true ) && ! $disabled ) {
				$tooltip = __( 'This file type cannot be compressed and will be left alone', 'wphb' );
			} elseif ( $minified_file ) {
				$tooltip = __( 'This file is already compressed', 'wphb' );
			} else {
				$tooltip = __( 'Compression is off for this file. Turn it on to reduce its size', 'wphb' );
				if ( ! in_array( $item['handle'], $options['dont_minify'][ $type ], true ) ) {
					$tooltip = __( 'Compression is on for this file, which aims to reduce its size', 'wphb' );
				}
			}
			?>
			<input
				type="checkbox"
				class="toggle-checkbox toggle-minify"
				name="<?php echo esc_attr( $base_name ); ?>[minify]"
				id="wphb-minification-minify-<?php echo esc_attr( $ext . '-' . $item['handle'] ); ?>"
				aria-label="<?php echo esc_attr( $tooltip ); ?>"
				<?php checked( in_array( $item['handle'], $options['dont_minify'][ $type ], true ), false ); ?>
				<?php disabled( in_array( 'minify', $disable_switchers, true ) || $minified_file ); ?>
			/>
			<label for="wphb-minification-minify-<?php echo esc_attr( $ext . '-' . $item['handle'] ); ?>" class="toggle-label sui-tooltip sui-tooltip-constrained sui-tooltip-mobile" data-tooltip="<?php echo esc_attr( $tooltip ); ?>" aria-hidden="true">
				<span class="sui-icon-arrows-in" aria-hidden="true"></span>
			</label>
			<?php
			$tooltip = __( 'Combine is off for this file. Turn it on to combine smaller files together.', 'wphb' );
			if ( in_array( 'combine', $disable_switchers, true ) && ! $disabled ) {
				$tooltip      = __( 'This file can’t be combined', 'wphb' );
				$dont_combine = true;
			} elseif ( ! in_array( $item['handle'], $options['dont_combine'][ $type ], true ) ) {
				$tooltip = __( 'Combine is on for this file, which aims to reduce server requests.', 'wphb' );
			}
			?>
			<input
				type="checkbox"
				class="toggle-checkbox toggle-combine"
				name="<?php echo esc_attr( $base_name ); ?>[combine]"
				id="wphb-minification-combine-<?php echo esc_attr( $ext . '-' . $item['handle'] ); ?>"
				aria-label="<?php echo esc_attr( $tooltip ); ?>"
				<?php checked( in_array( $item['handle'], $options['dont_combine'][ $type ], true ), false ); ?>
				<?php disabled( in_array( 'combine', $disable_switchers, true ) ); ?>
			/>
			<label for="wphb-minification-combine-<?php echo esc_attr( $ext . '-' . $item['handle'] ); ?>" class="toggle-label sui-tooltip sui-tooltip-constrained" data-tooltip="<?php echo esc_attr( $tooltip ); ?>" aria-hidden="true">
				<span class="sui-icon-combine" aria-hidden="true"></span>
			</label>
			<?php
			$tooltip = __( 'Move to footer is off for this file. Turn it on to load it from the footer.', 'wphb' );
			if ( 'footer' === $position ) {
				$tooltip = __( 'Move to footer is on for this file, which aims to speed up page load.', 'wphb' );
			}
			?>
			<input
				type="checkbox"
				class="toggle-checkbox toggle-position-footer"
				name="<?php echo esc_attr( $base_name ); ?>[position]"
				value="footer"
				id="wphb-minification-position-footer-<?php echo esc_attr( $ext . '-' . $item['handle'] ); ?>"
				aria-label="<?php echo esc_attr( $tooltip ); ?>"
				<?php checked( $position, 'footer' ); ?>
				<?php disabled( in_array( 'position', $disable_switchers, true ) ); ?>
			/>
			<label for="wphb-minification-position-footer-<?php echo esc_attr( $ext . '-' . $item['handle'] ); ?>" class="toggle-label sui-tooltip sui-tooltip-constrained" data-tooltip="<?php echo esc_attr( $tooltip ); ?>" aria-hidden="true">
				<span class="sui-icon-movefooter" aria-hidden="true"></span>
			</label>
			<?php if ( 'scripts' === $type && $is_local ) : ?>
				<?php
				$tooltip = __( 'Click to turn on the force-loading of this file after the page has rendered.', 'wphb' );
				if ( in_array( $item['handle'], $options['defer'][ $type ], true ) ) {
					$tooltip = __( 'This file will be loaded only after the page has rendered.', 'wphb' );
				}
				?>
				<input
					type="checkbox"
					class="toggle-checkbox toggle-defer" name="<?php echo esc_attr( $base_name ); ?>[defer]"
					id="wphb-minification-defer-<?php echo esc_attr( $ext . '-' . $item['handle'] ); ?>"
					value="1"
					aria-label="<?php echo esc_attr( $tooltip ); ?>"
					<?php checked( in_array( $item['handle'], $options['defer'][ $type ], true ) ); ?>
					<?php disabled( in_array( 'defer', $disable_switchers, true ) ); ?>
				/>
				<label for="wphb-minification-defer-<?php echo esc_attr( $ext . '-' . $item['handle'] ); ?>" class="toggle-label sui-tooltip sui-tooltip-constrained" data-tooltip="<?php echo esc_attr( $tooltip ); ?>" aria-hidden="true">
					<span class="sui-icon-defer" aria-hidden="true"></span>
				</label>
			<?php elseif ( 'scripts' === $type && ! $is_local ) : ?>
				<?php
				$tooltip = __( 'Async is off for this file. Turn it on to download the file asynchronously and execute it as soon as it’s ready. HTML parsing will be paused while the file is executed.', 'wphb' );
				if ( in_array( $item['handle'], $options['async'][ $type ], true ) ) {
					$tooltip = __( 'Async is enabled for this file, which will download the file asynchronously and execute it as soon as it’s ready. HTML parsing will be paused while the file is executed.', 'wphb' );
				}
				?>
				<input
					type="checkbox"
					class="toggle-checkbox toggle-async" name="<?php echo esc_attr( $base_name ); ?>[async]"
					id="wphb-minification-async-<?php echo esc_attr( $ext . '-' . $item['handle'] ); ?>"
					value="1"
					aria-label="<?php echo esc_attr( $tooltip ); ?>"
					<?php checked( in_array( $item['handle'], $options['async'][ $type ], true ) ); ?>
					<?php disabled( in_array( 'async', $disable_switchers, true ) ); ?>
				/>
				<label for="wphb-minification-async-<?php echo esc_attr( $ext . '-' . $item['handle'] ); ?>" class="toggle-label sui-tooltip sui-tooltip-constrained" data-tooltip="<?php echo esc_attr( $tooltip ); ?>" aria-hidden="true">
					<span class="sui-icon-async" aria-hidden="true"></span>
				</label>
			<?php elseif ( 'styles' === $type ) : ?>
				<?php
				$tooltip = __( 'Inline CSS is off for this file. Turn it on to  add the style attributes to an HTML tag.', 'wphb' );
				if ( in_array( $item['handle'], $options['inline'][ $type ], true ) ) {
					$tooltip = __( 'Inline CSS is on for this file, which will add the style attributes to an HTML tag.', 'wphb' );
				}
				?>
				<input
					type="checkbox"
					class="toggle-checkbox toggle-inline"
					name="<?php echo esc_attr( $base_name ); ?>[inline]"
					id="wphb-minification-inline-<?php echo esc_attr( $ext . '-' . $item['handle'] ); ?>"
					value="1"
					aria-label="<?php echo esc_attr( $tooltip ); ?>"
					<?php checked( in_array( $item['handle'], $options['inline'][ $type ], true ) ); ?>
					<?php disabled( in_array( 'inline', $disable_switchers, true ) ); ?>
				/>
				<label for="wphb-minification-inline-<?php echo esc_attr( $ext . '-' . $item['handle'] ); ?>" class="toggle-label sui-tooltip sui-tooltip-constrained" data-tooltip="<?php echo esc_attr( $tooltip ); ?>" aria-hidden="true">
					<span class="sui-icon-inlinecss" aria-hidden="true"></span>
				</label>
			<?php endif; ?>
			<?php
			$tooltip = __( 'Preload is off for this file. Turn it on to download and cache the file so it is immediately available when the site is loaded.', 'wphb' );
			if ( in_array( $item['handle'], $options['preload'][ $type ], true ) ) {
				$tooltip = __( 'Preload is on for this file, which will download and cache the file so it is immediately available when the site is loaded.', 'wphb' );
			}
			?>
			<input
				type="checkbox"
				class="toggle-checkbox toggle-preload"
				name="<?php echo esc_attr( $base_name ); ?>[preload]"
				id="wphb-minification-preload-<?php echo esc_attr( $ext . '-' . $item['handle'] ); ?>"
				value="1"
				aria-label="<?php echo esc_attr( $tooltip ); ?>"
				<?php checked( in_array( $item['handle'], $options['preload'][ $type ], true ) ); ?>
				<?php disabled( in_array( 'preload', $disable_switchers, true ) ); ?>
			/>
			<label for="wphb-minification-preload-<?php echo esc_attr( $ext . '-' . $item['handle'] ); ?>" class="toggle-label sui-tooltip sui-tooltip-constrained" data-tooltip="<?php echo esc_attr( $tooltip ); ?>" aria-hidden="true">
				<span class="sui-icon-update" aria-hidden="true"></span>
			</label>
		</div><!-- end checkbox-group -->

		<div class="wphb-minification-exclude">
			<input type="checkbox" <?php disabled( in_array( 'include', $disable_switchers, true ) ); ?>
				class="toggle-checkbox toggle-include" name="<?php echo esc_attr( $base_name ); ?>[include]"
				id="wphb-minification-include-<?php echo esc_attr( $ext . '-' . $item['handle'] ); ?>"
				<?php checked( $disabled, false ); ?>
				value="1">
			<?php
			$tooltip       = __( "Don't load this file", 'wphb' );
			$exclude_class = 'fileIncluded';
			if ( $disabled ) {
				$tooltip       = __( 'Click to re-include', 'wphb' );
				$exclude_class = '';
			}
			?>
			<label for="wphb-minification-include-<?php echo esc_attr( $ext . '-' . $item['handle'] ); ?>" class="toggle-label sui-tooltip <?php echo esc_attr( $exclude_class ); ?>" data-tooltip="<?php echo esc_attr( $tooltip ); ?>" aria-hidden="true">
				<span class="<?php echo $disabled ? 'sui-icon-eye' : 'sui-icon-eye-hide'; ?>" aria-hidden="true"></span>
			</label>
		</div><!-- end wphb-minification-exclude -->
	</div><!-- end wphb-minification-row-details -->

</div><!-- end wphb-border-row -->
