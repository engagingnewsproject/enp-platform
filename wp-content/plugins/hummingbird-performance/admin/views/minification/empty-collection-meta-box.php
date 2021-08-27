<?php
/**
 * Asset optimization empty collection meta box.
 * Will be used when the scan completed but wphb_styles_collection and wphb_scripts_collection are empty.
 *
 * @since 2.5.0
 * @package Hummingbird
 *
 * @var boolean $is_scanning
 */

use Hummingbird\Core\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="wphb-minification-files">
	<div class="wphb-minification-files-header">
		<p>
			<?php if ( $is_scanning ) : ?>
				<?php esc_html_e( 'File check is in progress...', 'wphb' ); ?>
			<?php else : ?>
				<?php esc_html_e( 'Choose which files you wish to compress and then publish your changes.', 'wphb' ); ?>
			<?php endif; ?>
		</p>
	</div>

	<?php if ( ! $is_scanning ) : ?>
		<div class="wphb-minification-files-table wphb-minification-files-basic">
			<?php
			$this->admin_notices->show_inline(
				sprintf( /* translators: %1$s - <a>, %2$s - </a> */
					esc_html__( "We've completed the file check but haven't been able to load the files. Please try clearing your object cache, refresh the page and wait a few seconds to load the files, or visit your homepage to trigger the file list to show. If you continue to have problems, please %1\$sopen a ticket%2\$s with our support team.", 'wphb' ),
					'<a href="' . esc_url( Utils::get_link( 'support' ) ) . '" target="_blank">',
					'</a>'
				),
				'info',
				sprintf( /* translators: %1$s - opening a tag, %2$s - </a> */
					esc_html__( '%1$sVisit homepage%2$s', 'wphb' ),
					'<a href="' . esc_url( site_url() ) . '" target="_blank" class="sui-button sui-button-blue">',
					'</a>'
				)
			);
			?>
		</div>
	<?php endif; ?>
</div>

<?php if ( $is_scanning ) : ?>
	<script>
		window.addEventListener("load", function(){
			jQuery(document).trigger('check-files');
		});
	</script>
<?php endif; ?>
