<?php
/**
 * Notifications configure meta box.
 *
 * @since 3.1.1
 * @package Hummingbird
 *
 * @var array $notifications  Notification settings.
 * @var array $users          Up to 10 WordPress users that will be offered to add in the modals.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$disabled_modules = false;
?>

<div class="sui-box-body" style="padding-bottom: 20px">
	<p><?php esc_html_e( 'Activate and schedule notifications and reports in one place. Automate your workflow with daily, weekly or monthly reports sent directly to your inbox.', 'wphb' ); ?></p>
</div>

<div class="sui-box-settings-row sui-padding-bottom">
	<table class="sui-table sui-table-flushed">
		<thead>
		<tr>
			<th><?php esc_html_e( 'Notifications', 'wphb' ); ?></th>
			<th class="sui-hidden-xs"><?php esc_html_e( 'Type', 'wphb' ); ?></th>
			<th class="sui-hidden-xs"><?php esc_html_e( 'Status', 'wphb' ); ?></th>
			<th class="sui-hidden-xs"><?php esc_html_e( 'Recipients', 'wphb' ); ?></th>
			<th><?php esc_html_e( 'Schedule', 'wphb' ); ?></th>
			<th>&nbsp;</th>
		</tr>
		</thead>

		<tbody>
		<?php
		foreach ( $notifications as $notification_type => $reports ) {
			foreach ( $reports as $module => $data ) {
				if ( isset( $data['module_disabled'] ) && true === $data['module_disabled'] ) {
					$disabled_modules = true;
					continue;
				}
				?>
				<tr>
					<td class="sui-table-item-title">
						<?php if ( 'reports' === $notification_type ) : ?>
							<span class="sui-icon-calendar sui-hidden-xs" aria-hidden="true"></span>
						<?php else : ?>
							<span class="sui-icon-mail sui-hidden-xs" aria-hidden="true"></span>
						<?php endif; ?>
						<span class="sui-tooltip sui-tooltip-constrained sui-tooltip-top-left" data-tooltip="<?php echo esc_attr( $data['desc'] ); ?>">
							<?php echo esc_attr( $data['label'] ); ?>
						</span>
					</td>
					<td class="sui-hidden-xs">
						<?php
						if ( 'reports' === $notification_type ) {
							esc_html_e( 'Reporting', 'wphb' );
						} else {
							esc_html_e( 'Notification', 'wphb' );
						}
						?>
					</td>
					<td class="sui-hidden-xs">
						<?php if ( $data['enabled'] ) : ?>
							<span class="sui-tag sui-tag-blue sui-tag-sm"><?php esc_html_e( 'Enabled', 'wphb' ); ?></span>
						<?php else : ?>
							<span class="sui-tag sui-tag-sm"><?php esc_html_e( 'Disabled', 'wphb' ); ?></span>
						<?php endif; ?>
					</td>
					<td class="notification-recipients sui-hidden-xs">
						<?php
						if ( ! $data['enabled'] || ! isset( $data['recipients'] ) || empty( $data['recipients'] ) ) {
							echo '&mdash;';
						} else {
							echo '<span>';
							$i = 0;
							foreach ( $data['recipients'] as $recipient ) {
								if ( $i++ > 2 ) {
									break;
								}

								$avatar = get_avatar_data( $recipient['email'] );

								$class   = '';
								$tooltip = $recipient['email'];
								if ( isset( $recipient['is_pending'] ) ) {
									if ( ! $recipient['is_pending'] && isset( $recipient['is_subscribed'] ) && ! $recipient['is_subscribed'] ) {
										$class = 'unsubscribed';
										$text  = esc_html__( 'Unsubscribed', 'wphb' );
									} else {
										$class = $recipient['is_pending'] ? 'pending' : 'confirmed';
										$text  = $recipient['is_pending'] ? esc_html__( 'Pending', 'wphb' ) : esc_html__( 'Subscribed', 'wphb' );
									}

									$tooltip = sprintf( /* translators: %1$s - email, %2$s - status */
										esc_html__( '%1$s (%2$s)', 'wphb' ),
										esc_html( $recipient['email'] ),
										esc_html( $text )
									);
								}
								?>
								<div class="sui-tooltip" data-tooltip="<?php echo esc_attr( $tooltip ); ?>">
									<span class="subscriber <?php echo esc_attr( $class ); ?>">
										<a href="#" class="wphb-configure-notification" data-id="<?php echo esc_attr( $module ); ?>" data-type="<?php echo esc_attr( $notification_type ); ?>" data-view="recipients">
											<img src="<?php echo esc_url( $avatar['url'] ); ?>" alt="<?php echo esc_attr( $recipient['email'] ); ?>">
										</a>
									</span>
								</div>
								<?php
							}

							if ( $i > 3 ) {
								echo '<a class="wphb-configure-notification" href="#" data-id="' . esc_attr( $module ) . '" data-type="' . esc_attr( $notification_type ) . '" data-view="recipients">';
								printf( /* translators: %d - number of recipients */
									esc_html__( '+%d more', 'wphb' ),
									count( $data['recipients'] ) - 3
								);
								echo '</a>';
							}
							echo '</span>';
						}
						?>
					</td>
					<td>
						<?php if ( ! $data['enabled'] || empty( $data['frequency'] ) ) : ?>
							&mdash;
						<?php else : ?>
							<span class="sui-tooltip sui-tooltip-constrained" data-tooltip="<?php echo esc_attr( $data['next'] ); ?>">
							<?php echo esc_html( $data['frequency'] ); ?>
						</span>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( $data['enabled'] ) : ?>
							<div class="sui-dropdown">
								<button class="sui-button-icon sui-dropdown-anchor" aria-label="<?php esc_attr_e( 'Configure options', 'wphb' ); ?>">
									<span class="sui-icon-widget-settings-config" aria-hidden="true"></span>
								</button>
								<ul>
									<li>
										<button class="wphb-configure-notification" data-id="<?php echo esc_attr( $module ); ?>" data-type="<?php echo esc_attr( $notification_type ); ?>">
											<span class="sui-icon-wrench-tool" aria-hidden="true"></span>
											<?php esc_html_e( 'Configure', 'wphb' ); ?>
										</button>
									</li>
									<li>
										<button class="wphb-disable-notification" data-id="<?php echo esc_attr( $module ); ?>" data-type="<?php echo esc_attr( $notification_type ); ?>">
											<span class="sui-icon-power-on-off" aria-hidden="true"></span>
											<?php esc_html_e( 'Disable', 'wphb' ); ?>
										</button>
									</li>
								</ul>
							</div>
						<?php else : ?>
							<button role="button" class="wphb-enable-notification sui-button-blue sui-button-icon sui-tooltip" data-tooltip="<?php esc_attr_e( 'Enable', 'wphb' ); ?>" data-id="<?php echo esc_attr( $module ); ?>" data-type="<?php echo esc_attr( $notification_type ); ?>">
								<span class="sui-icon-plus" aria-hidden="true"></span>
								<span class="sui-screen-reader-text"><?php esc_html_e( 'Enable', 'wphb' ); ?></span>
							</button>
						<?php endif; ?>
					</td>
				</tr>
				<?php
			}
		}
		?>
		</tbody>
	</table>
</div>

<?php if ( $disabled_modules ) : ?>
	<div class="sui-box-settings-row sui-padding-bottom">
		<table class="sui-table sui-table-flushed">
			<thead>
			<tr>
				<th colspan="5"><?php esc_html_e( 'Inactive features', 'wphb' ); ?></th>
				<th>&nbsp;</th>
			</tr>
			</thead>

			<tbody>
			<?php
			foreach ( $notifications as $notification_type => $reports ) {
				foreach ( $reports as $module => $data ) {
					if ( ! isset( $data['module_disabled'] ) || false === $data['module_disabled'] ) {
						continue;
					}
					?>
					<tr>
						<td class="sui-table-item-title">
							<?php if ( 'reports' === $notification_type ) : ?>
								<span class="sui-icon-calendar" aria-hidden="true"></span>
							<?php else : ?>
								<span class="sui-icon-mail" aria-hidden="true"></span>
							<?php endif; ?>
							<span class="sui-tooltip sui-tooltip-constrained sui-tooltip-top-left" data-tooltip="<?php echo esc_attr( $data['desc'] ); ?>">
							<?php echo esc_attr( $data['label'] ); ?>
						</span>
						</td>
						<td>
							<?php
							if ( 'reports' === $notification_type ) {
								esc_html_e( 'Reporting', 'wphb' );
							} else {
								esc_html_e( 'Notification', 'wphb' );
							}
							?>
						</td>
						<td>
							<span class="sui-tag sui-tag-disabled sui-tag-sm"><?php esc_html_e( 'Inactive feature', 'wphb' ); ?></span>
						</td>
						<td class="notification-recipients">&mdash;</td>
						<td>&mdash;</td>
						<td>
							<a href="<?php echo esc_html( $data['activate_url'] ); ?>" role="button" class="sui-button-blue sui-button-icon sui-tooltip" data-tooltip="<?php esc_attr_e( 'Activate feature', 'wphb' ); ?>">
								<span class="sui-icon-plus" aria-hidden="true"></span>
								<span class="sui-screen-reader-text"><?php esc_html_e( 'Activate feature', 'wphb' ); ?></span>
							</a>
						</td>
					</tr>
					<?php
				}
			}
			?>
			</tbody>
		</table>
	</div>
<?php endif; ?>

<script>
	jQuery(document).ready( function() {
		window.WPHB_Admin.notifications.init( <?php echo wp_json_encode( $notifications ); ?> );
	});
</script>