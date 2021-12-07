<?php
/**
 * Reports no membership meta box on dashboard page.
 *
 * @package Hummingbird
 *
 * @var string $title  Reports module title.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<p><?php esc_html_e( 'Automate your workflow with daily, weekly or monthly reports sent directly to your inbox.', 'wphb' ); ?></p>

<table class="sui-table sui-flushed sui-margin-top">
	<tbody>
	<tr>
		<td>
			<span class="sui-icon-hummingbird" aria-hidden="true"></span>
			<strong><?php esc_html_e( 'Performance Test', 'wphb' ); ?></strong>
		</td>
		<td>
			<span class="sui-tag sui-tag-inactive"><?php esc_html_e( 'Inactive', 'wphb' ); ?></span>
		</td>
	</tr>
	<tr>
		<td>
			<span class="sui-icon-user-reputation-points" aria-hidden="true"></span>
			<strong><?php esc_html_e( 'Database Cleanup', 'wphb' ); ?></strong>
		</td>
		<td>
			<span class="sui-tag sui-tag-inactive"><?php esc_html_e( 'Inactive', 'wphb' ); ?></span>
		</td>
	</tr>
	<tr>
		<td>
			<span class="sui-icon-uptime" aria-hidden="true"></span>
			<strong><?php esc_html_e( 'Uptime', 'wphb' ); ?></strong>
		</td>
		<td>
			<span class="sui-tag sui-tag-inactive"><?php esc_html_e( 'Inactive', 'wphb' ); ?></span>
		</td>
	</tr>
	</tbody>
</table>
