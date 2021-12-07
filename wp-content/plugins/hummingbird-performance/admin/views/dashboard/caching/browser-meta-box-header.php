<?php
/**
 * Caching meta box header on dashboard page.
 *
 * @package Hummingbird
 *
 * @var bool   $cf_active   Cloudflare status.
 * @var int    $cf_current  Current Cloudflare caching value.
 * @var int    $issues      Number of issues.
 * @var string $title       Module title.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<h3  class="sui-box-title"><?php echo esc_html( $title ); ?></h3>
<?php if ( $issues && ! $cf_active ) : ?>
	<div class="sui-actions-left">
		<div class="sui-tag sui-tag-warning"><?php echo (int) $issues; ?></div>
	</div>
<?php elseif ( YEAR_IN_SECONDS !== $cf_current && $cf_active ) : ?>
	<div class="sui-tag sui-tag-warning">5</div>
<?php endif; ?>
