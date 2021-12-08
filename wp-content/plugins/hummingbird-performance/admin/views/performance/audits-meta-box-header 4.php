<?php
/**
 * Audits meta box header.
 *
 * @since 3.1.0
 * @package Hummingbird
 *
 * @var array $args
 *      string $title  Page title.
 */

?>

<h3 class="sui-box-title">
	<?php echo esc_html( $args['title'] ); ?>
</h3>

<div class="sui-actions-right">
	<a href="#" class="sui-button-icon sui-button-outlined" id="wphb-audits-filter-button">
		<span class="sui-icon-filter sui-md sui-fw" aria-hidden="true"></span>
	</a>
</div>
