<?php
/**
 * Advanced tools: database cleanup meta box.
 *
 * @package Hummingbird
 *
 * @since 1.8
 *
 * @var array $fields  Array with data about rows.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$total = 0;
?>

<p>
	<?php esc_html_e( 'Cleanup your database of unnecessary data you probably don’t need that’s slowing down your server. You can schedule daily, weekly or monthly automatic cleanups.', 'wphb' ); ?>
</p>

<div class="wphb-border-frame">
	<div class="table-header">
		<div><?php esc_html_e( 'Data Type', 'wphb' ); ?></div>
		<div><?php esc_html_e( 'Entries', 'wphb' ); ?></div>
		<div class="">&nbsp;</div>
	</div>

	<?php foreach ( $fields as $db_type => $field ) : ?>
		<?php $total = $total + $field['value']; ?>
		<div class="table-row" data-type="<?php echo esc_attr( $db_type ); ?>">
			<div>
				<?php echo esc_html( $field['title'] ); ?>
				<span class="sui-tooltip sui-tooltip-constrained" data-tooltip="<?php echo esc_attr( $field['tooltip'] ); ?>">
					<span class="sui-icon-info" aria-hidden="true"></span>
				</span>
			</div>
			<div class="wphb-db-items"><?php echo absint( $field['value'] ); ?></div>
			<button type="button" class="sui-button-icon sui-tooltip sui-tooltip-top-right wphb-db-row-delete"
					data-tooltip="<?php esc_attr_e( 'Delete entries', 'wphb' ); ?>"
					data-type="<?php echo esc_attr( $db_type ); ?>"
					data-entries="<?php echo absint( $field['value'] ); ?>">
				<span class="sui-loading-text" aria-hidden="true"><span class="sui-icon-trash sui-md"></span></span>
				<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
				<span class="sui-screen-reader-text">
					<?php
						printf(
							/* translators: %1$d - number of entries, %2$s - entry type */
							esc_html__( '%1$d %2$s.', 'wphb' ),
							absint( $field['value'] ),
							esc_html( $field['title'] )
						)
					?>
				</span>
			</button>
		</div>
	<?php endforeach; ?>

	<div class="sui-box-footer" data-type="all">
		<div class="sui-actions-left sui-no-margin-left">
			<span class="status-text">
				<?php esc_html_e( 'Tip: Make sure you have a current backup before running a cleanup.', 'wphb' ); ?>
			</span>
		</div>
		<div class="sui-actions-right">
			<span class="sui-icon-loader sui-loading sui-fw sui-hidden" aria-hidden="true"></span>
			<button id="wphb-db-delete-all" class="sui-button wphb-db-delete-all" data-type="all" data-entries="<?php echo absint( $total ); ?>">
				<?php esc_html_e( 'Delete All', 'wphb' ); ?> (<?php echo absint( $total ); ?>)
			</button>
		</div>
	</div>
</div>

<?php $this->modal( 'database-cleanup' ); ?>
