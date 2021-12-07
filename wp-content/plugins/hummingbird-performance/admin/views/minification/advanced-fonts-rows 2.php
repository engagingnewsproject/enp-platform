<?php
/**
 * Asset optimization Google fonts row (advanced view).
 *
 * @since 3.0.0
 *
 * @package Hummingbird
 *
 * @var string $base_name          Base name.
 * @var bool   $disabled           Enabled or disabled state.
 * @var array  $disable_switchers  Array of disabled fields.
 * @var string $ext                File extension. Possible values: CSS, OTHER, JS, FONT.
 * @var string $filter             Filter string for filtering.
 * @var string $full_src           File URL.
 * @var array  $item               File info.
 * @var bool   $optimized          Font optimization status.
 * @var array  $options            Asset optimization settings.
 * @var string $position           File position. Possible values: '' or 'footer'.
 * @var string $type               Possible values: styles, scripts or other.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<input type="hidden" name="<?php echo esc_attr( $base_name ); ?>[handle]" value="<?php echo esc_attr( $item['handle'] ); ?>">
<div class="wphb-border-row<?php echo ( $disabled ) ? ' disabled' : ''; ?>" id="wphb-file-<?php echo esc_attr( $ext . '-' . $item['handle'] ); ?>" data-filter="<?php echo esc_attr( $item['handle'] . ' ' . $ext ); ?>" data-filter-secondary="<?php echo esc_attr( $filter ); ?> font" data-filter-type="external">
	<span class="wphb-row-status wphb-row-status-changed sui-tooltip sui-tooltip-top-left sui-hidden" data-tooltip="<?php esc_attr_e( 'You need to publish your changes for your new settings to take effect', 'wphb' ); ?>">
		<span class="sui-icon-update" aria-hidden="true"></span>
	</span>

	<div class="fileinfo-group">
		<div class="wphb-minification-file-select">
			<label for="minification-file-<?php echo esc_attr( $ext . '-' . $item['handle'] ); ?>" class="screen-reader-text">
				<?php esc_html_e( 'Select file', 'wphb' ); ?>
			</label>
			<label class="sui-checkbox">
				<input type="checkbox" data-type="<?php echo esc_attr( $ext ); ?>" data-handle="<?php echo esc_attr( $item['handle'] ); ?>" id="minification-file-<?php echo esc_attr( $ext . '-' . $item['handle'] ); ?>" name="minification-file[]" class="wphb-minification-file-selector">
				<span aria-hidden="true"></span>
			</label>
		</div>

		<span class="wphb-filename-extension wphb-filename-extension-<?php echo esc_attr( strtolower( $ext ) ); ?>">
			<?php echo esc_html( $ext ); ?>
		</span>

		<div class="wphb-minification-file-info wphb-minification-font-info">
			<span><?php echo esc_html( $item['handle'] ); ?></span>
			<a href="<?php echo esc_url( $full_src ); ?>" class="wphb-minification-font-url" target="_blank">
				<?php echo esc_url( $full_src ); ?>
			</a>
		</div>
	</div>

	<div class="wphb-minification-row-details">
		<div class="checkbox-group wphb-minification-advanced-group">
			<?php
			$tooltip = esc_html__( 'Font optimization is off for this file. Turn it on to optimize it.', 'wphb' );
			if ( $optimized ) {
				$tooltip = esc_html__( 'Font is optimized.', 'wphb' );
			}
			?>
			<input type="hidden" name="<?php echo esc_attr( $base_name ); ?>[font-optimize]" value="0">
			<input
				type="checkbox"
				class="toggle-checkbox toggle-font-optimize"
				name="<?php echo esc_attr( $base_name ); ?>[font-optimize]"
				id="wphb-minification-font-optimize-<?php echo esc_attr( $ext . '-' . $item['handle'] ); ?>"
				value="1"
				aria-label="<?php echo esc_attr( $tooltip ); ?>"
				<?php checked( $optimized ); ?>
			/>
			<label for="wphb-minification-font-optimize-<?php echo esc_attr( $ext . '-' . $item['handle'] ); ?>" class="toggle-label sui-tooltip sui-tooltip-mobile <?php echo $optimized ? '' : 'sui-tooltip-constrained'; ?>" data-tooltip="<?php echo esc_attr( $tooltip ); ?>" aria-hidden="true">
				<span class="sui-icon-arrows-compress" aria-hidden="true"></span>
			</label>

			<?php
			$tooltip = esc_html__( 'Move to footer is off for this file. Turn it on to load it from the footer.', 'wphb' );
			if ( 'footer' === $position ) {
				$tooltip = esc_html__( 'Move to footer is on for this file, which aims to speed up page load.', 'wphb' );
			}
			?>
			<input
				type="checkbox"
				class="toggle-checkbox toggle-position-footer"
				name="<?php echo esc_attr( $base_name ); ?>[position]"
				id="wphb-minification-position-footer-<?php echo esc_attr( $ext . '-' . $item['handle'] ); ?>"
				value="footer"
				aria-label="<?php echo esc_attr( $tooltip ); ?>"
				<?php checked( $position, 'footer' ); ?>
				<?php disabled( in_array( 'position', $disable_switchers, true ) ); ?>
			/>
			<label for="wphb-minification-position-footer-<?php echo esc_attr( $ext . '-' . $item['handle'] ); ?>" class="toggle-label sui-tooltip sui-tooltip-constrained" data-tooltip="<?php echo esc_attr( $tooltip ); ?>" aria-hidden="true">
				<span class="sui-icon-movefooter" aria-hidden="true"></span>
			</label>

			<?php
			$tooltip = esc_html__( 'Inline CSS is off for this file. Turn it on to add the style attributes to an HTML tag.', 'wphb' );
			if ( in_array( $item['handle'], $options['inline'][ $type ], true ) ) {
				$tooltip = esc_html__( 'Inline CSS is on for this file, which will add the style attributes to an HTML tag.', 'wphb' );
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
		</div>

		<div class="wphb-minification-exclude">
			<?php
			$tooltip = esc_html__( 'Don’t load this file', 'wphb' );
			if ( $disabled ) {
				$tooltip = esc_html__( 'Click to re-include', 'wphb' );
			}
			?>
			<input
				type="checkbox"
				class="toggle-checkbox toggle-include"
				name="<?php echo esc_attr( $base_name ); ?>[include]"
				id="wphb-minification-include-<?php echo esc_attr( $ext . '-' . $item['handle'] ); ?>"
				value="1"
				aria-label="<?php echo esc_attr( $tooltip ); ?>"
				<?php checked( $disabled, false ); ?>
			/>
			<label for="wphb-minification-include-<?php echo esc_attr( $ext . '-' . $item['handle'] ); ?>" class="toggle-label sui-tooltip <?php echo $disabled ? '' : 'fileIncluded'; ?>" data-tooltip="<?php echo esc_attr( $tooltip ); ?>" aria-hidden="true">
				<span class="<?php echo $disabled ? 'sui-icon-eye' : 'sui-icon-eye-hide'; ?>" aria-hidden="true"></span>
			</label>
		</div>
	</div>
</div>
