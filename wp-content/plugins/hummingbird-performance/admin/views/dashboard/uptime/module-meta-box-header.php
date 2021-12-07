<?php
/**
 * Uptime meta box header on dashboard page.
 *
 * @package Hummingbird
 *
 * @var string $title  Meta box title.
 */

use Hummingbird\Core\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<h3  class="sui-box-title"><?php echo esc_html( $title ); ?></h3>
<?php if ( ! Utils::is_member() ) : ?>
	<span class="sui-tag sui-tag-pro" style="margin-left: 10px">
		<?php esc_html_e( 'Pro', 'wphb' ); ?>
	</span>
<?php endif; ?>
