<?php
/**
 * Advanced tools meta box.
 *
 * @since 1.8
 * @package Hummingbird
 *
 * @var int $count  Number of issues.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="sui-box-settings-row">
	<p>
		<?php
		esc_html_e(
			'Database Cleanups and tools to remove unnecessary functions WordPress does that
                                can slow down your server.',
			'wphb'
		);
		?>
	</p>
</div>

<ul class="sui-list">
	<li>
		<span class="sui-list-label"><?php esc_html_e( 'Database Cleanup', 'wphb' ); ?></span>
		<?php if ( $count > 0 ) : ?>
			<span class="sui-list-detail">
				<?php
				/* translators: %d: number of entries */
				printf( __( '%d dispensable entries', 'wphb' ), absint( $count ) );
				?>
		</span>
		<?php else : ?>
			<span class="sui-list-detail">
				<?php esc_html_e( 'Up to date', 'wphb' ); ?>
		</span>
		<?php endif; ?>
	</li>
</ul>
