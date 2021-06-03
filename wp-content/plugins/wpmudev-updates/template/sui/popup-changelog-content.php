<?php
/**
 * The changelog modal content.
 *
 * @package WPMUDEV_Dashboard
 *
 * @var int $pid Project ID
 */

defined( 'WPINC' ) || die();

// Get project data.
$project = WPMUDEV_Dashboard::$site->get_project_info( $pid, true );

// No need to continue if empty.
if ( empty( $project->changelog ) ) {
	return;
}

// We need only 3 items.
$changelog = array_slice( $project->changelog, 0, 3 );

foreach ( $changelog as $log ) {
	// Should be a proper item.
	if ( empty( $log ) || ! is_array( $log ) ) {
		continue;
	}

	// Version compare.
	$version_check = version_compare( $log['version'], $project->version_installed );
	?>

	<div class="sui-box-settings-row sui-flushed">
		<div class="sui-box-settings-col-2">
			<div class="dashui-changelog-version">
			<span class="sui-tag sui-tag-purple">
				<?php esc_attr_e( 'Version', 'wpmudev' ); ?>
				<?php echo esc_attr( $log['version'] ); ?>
			</span>
				<?php if ( ! empty( $project->version_installed ) && 1 === $version_check ) { ?>
					<span class="sui-tag sui-tag-green"><?php esc_attr_e( 'New', 'wpmudev' ); ?></span>
				<?php } elseif ( 0 === $version_check ) { ?>
					<span class="sui-tag dashui-tag-current"><?php esc_attr_e( 'Current', 'wpmudev' ); ?></span>
				<?php } ?>
				<div class="sui-actions-right">
					<span class="sui-changelog-date">
						<?php if ( ! empty( $log['time'] ) ) { ?>
							<?php echo esc_html( date_i18n( get_option( 'date_format' ), $log['time'] ) ); ?>
						<?php } ?>
					</span>
				</div>
			</div>
			<?php $notes = explode( "\n", $log['log'] ); // Split by line break. ?>
			<?php if ( ! empty( $notes ) ) { ?>
				<ul class="dashui-changelog-list">
					<?php
					foreach ( $notes as $note ) {
						// Remove all unwanted tags.
						$note = stripslashes( $note );
						$note = preg_replace( '/(<br ?\/?>|<p>|<\/p>)/', '', $note );
						$note = trim( preg_replace( '/^\s*(\*|\-)\s*/', '', $note ) );
						$note = str_replace( array( '<', '>' ), array( '&lt;', '&gt;' ), $note );
						$note = preg_replace( '/`(.*?)`/', '<code>\1</code>', $note );
						// If empty, skip the item.
						if ( empty( $note ) ) {
							continue;
						}
						echo '<li>' . wp_kses_post( $note ) . '</li>';
					}
					?>
				</ul>
			<?php } ?>
		</div>
	</div>
	<?php
}

if ( ! empty( $changelog ) && ! empty( $project->url->website ) ) {
	?>
	<p class="dashui-changelog-view-more">
		<a href="<?php echo esc_url( $project->url->website ); ?>" target="_blank">
			<?php esc_attr_e( 'View more information on WPMU DEV', 'wpmudev' ); ?> <span class="sui-icon-arrow-right" aria-hidden="true"></span>
		</a>
	</p>
	<?php
}