<?php
/**
 * Dashboard template: Support Functions
 *
 * Manage support tickets, grant support-staff access and view System
 * configuration.
 *
 * Following variables are passed into the template:
 *
 * @var array                           $data            Projects data.
 * @var array                           $profile         User profile data.
 * @var WPMUDEV_Dashboard_Sui_Page_Urls $urls            Urls of all dashboard menu items.
 * @var array                           $staff_login     Remote access status/details.
 * @var array                           $notes           Notes for support staff.
 * @var array                           $access_logs     List of all support-staff logins.
 * @var string                          $membership_type Membership type.
 * @var array                           $membership_data Membership data.
 * @var bool                            $tickets_hidden  Is tickets hidden.
 *
 * @since   4.0.0
 * @package WPMUDEV_Dashboard
 */

global $wp_version;

// Render the page header section.
$this->render_sui_header( __( 'Support', 'wpmudev' ), 'support' );

// Is current membership type expired, free or paused?.
$free_type = in_array( $membership_type, array( 'free', 'expired', 'paused' ), true );

$is_wpmudev_host       = WPMUDEV_Dashboard::$api->is_wpmu_dev_hosting();
$is_standalone_hosting = WPMUDEV_Dashboard::$api->is_standalone_hosting_plan();
$has_hosted_access     = $is_wpmudev_host && ! $is_standalone_hosting && 'free' === $membership_type;
$has_support_access    = WPMUDEV_Dashboard::$api->is_support_allowed() || $has_hosted_access;

$url_grant       = wp_nonce_url( add_query_arg( 'action', 'remote-grant', $urls->support_url . '#access' ), 'remote-grant', 'hash' );
$url_revoke      = wp_nonce_url( add_query_arg( 'action', 'remote-revoke', $urls->support_url . '#access' ), 'remote-revoke', 'hash' );
$url_extend      = wp_nonce_url( add_query_arg( 'action', 'remote-extend', $urls->support_url . '#access' ), 'remote-extend', 'hash' );
$url_all_tickets = $urls->remote_site . 'hub/support/';
$url_search      = $urls->remote_site . 'forums/search.php';
$url_open_ticket = WPMUDEV_Dashboard::$ui->page_urls->external_support_url;
$hub_url         = $urls->hub_url;

if ( $notes && ! empty( $_COOKIE['wpmudev_is_staff'] ) || ! empty( $_GET['staff'] ) ) {// wpcs csrf ok.
	$notes_class = 'active';
} else {
	$notes_class = '';
}

$threads      = $profile['forum']['support_threads'];
$open_threads = array(
	'all'      => array(),
	'open'     => array(),
	'resolved' => array(),
	'feedback' => array(),
);
if ( ! $tickets_hidden ) {
	foreach ( $threads as $thread ) {
		if ( empty( $thread['title'] ) ) {
			continue;
		}
		if ( empty( $thread['status'] ) ) {
			continue;
		}

		if ( 'resolved' === $thread['status'] ) {
			$thread['ui_status']        = array(
				'class' => 'sui-tag',
				'text'  => __( 'Resolved', 'wpmudev' ),
			);
			$open_threads['resolved'][] = $thread;
		} else {
			if ( isset( $thread['unread'] ) && $thread['unread'] ) {
				$thread['ui_status']        = array(
					'class' => 'sui-tag sui-tag-yellow',
					'text'  => __( 'Feedback', 'wpmudev' ),
				);
				$open_threads['feedback'][] = $thread;
			} else {
				$thread['ui_status']    = array(
					'class' => 'sui-tag sui-tag-blue',
					'text'  => __( 'Open', 'wpmudev' ),
				);
				$open_threads['open'][] = $thread;
			}
		}

		$open_threads['all'][] = $thread;

	}
}

$date_format = get_option( 'date_format' );
$time_format = get_option( 'time_format' );
?>

<?php if ( isset( $_GET['success-action'] ) ) : // wpcs csrf ok. ?>
	<div class="sui-floating-notices">
		<?php
		switch ( $_GET['success-action'] ) { //phpcs:ignore

			case 'remote-grant':
				$notice_msg = '<p>' . sprintf( esc_html__( 'Support access granted. Please let support staff know you have granted access via your %s support ticket %s.', 'wpmudev' ), "<a href='" . esc_url( $urls->support_url ) . "'>", "</a>" ) . '</p>';//phpcs:ignore
				$notice_id  = 'notice-success-remote-grant';
				break;

			case 'remote-revoke':
				$notice_msg = '<p>' . esc_html__( 'Support session ended. You can grant access again at any time.', 'wpmudev' ) . '</p>';
				$notice_id  = 'notice-success-remote-revoke';
				break;

			case 'remote-extend':
				$notice_msg = '<p>' . esc_html__( 'Support session extended. You can end session at any time.', 'wpmudev' ) . '</p>';
				$notice_id  = 'notice-success-remote-extend';
				break;

			case 'staff-note':
				$notice_msg = '<p>' . sprintf( esc_html__( 'Your note has been saved. Please let support staff know you have granted access via your %1$s support ticket %2$s.', 'wpmudev' ), "<a href='" . esc_url( $urls->support_url ) . "'>", '</a>' ) . '</p>'; //phpcs:ignore
				$notice_id  = 'notice-success-staff-note';
				break;
			case 'check-updates':
				$notice_msg = '<p>' . esc_html__( 'Data successfully updated.', 'wpmudev' ) . '</p>';
				$notice_id  = 'remote-check-success';
				break;
			default:
				break;
		}
		?>
		<div
			role="alert"
			id="<?php echo esc_attr( $notice_id ); ?>"
			class="sui-support-notice-alert sui-notice"
			aria-live="assertive"
			data-show-dismiss="true"
			data-notice-type="success"
			data-notice-msg="<?php echo wp_kses_post( $notice_msg ); ?>"
		>
		</div>
	</div>

<?php endif; ?>

<?php
if ( in_array( $membership_type, array( 'expired', 'paused' ), true ) ) {
	$this->render_switch_free_notice( 'dashboard_support' );
}
?>

<?php if ( isset( $_GET['failed-action'] ) ) : //phpcs:ignore ?>
	<div class="sui-floating-notices">
		<?php
		switch ( $_GET['failed-action'] ) { //phpcs:ignore
			case 'remote-grant':
				$notice_msg = '<p>' . esc_html__( 'Failed to grant support access.', 'wpmudev' ) . '</p>';
				$notice_id  = 'notice-error-remote-grant';
				break;

			case 'remote-revoke':
				$notice_msg = '<p>' . esc_html__( 'Failed to end support session.', 'wpmudev' ) . '</p>';
				$notice_id  = 'notice-error-remote-revoke';
				break;

			case 'remote-extend':
				$notice_msg = '<p>' . esc_html__( 'Failed to extend support session.', 'wpmudev' ) . '</p>';
				$notice_id  = 'notice-error-remote-extend';
				break;
			default:
				break;
		}
		?>
		<div
			role="alert"
			id="<?php echo esc_attr( $notice_id ); ?>"
			class="sui-support-notice-alert sui-notice"
			aria-live="assertive"
			data-show-dismiss="true"
			data-notice-type="error"
			data-notice-msg="<?php echo wp_kses_post( $notice_msg ); ?>"
		>
		</div>
	</div>

<?php endif; ?>

<div class="sui-row-with-sidenav">
	<div role="navigation" class="sui-sidenav">
		<ul class="sui-vertical-tabs sui-sidenav-hide-md">
			<li class="sui-vertical-tab">
				<a href="#ticket"><?php esc_html_e( 'My Tickets', 'wpmudev' ); ?></a>
				<?php if ( ! $tickets_hidden && ! empty( $open_threads['all'] ) ) : ?>
					<span class="sui-tag sui-tag-blue"><?php echo esc_html( count( $open_threads['all'] ) ); ?></span>
				<?php endif; ?>
			</li>

			<li class="sui-vertical-tab">
				<a href="#access"><?php esc_html_e( 'Support Access', 'wpmudev' ); ?></a>
				<?php if ( $staff_login->enabled ) : ?>
					<i class="sui-icon-unlock sui-blue" aria-hidden="true"></i>
				<?php endif; ?>
			</li>

			<li class="sui-vertical-tab">
				<a href="#system"><?php esc_html_e( 'System Information', 'wpmudev' ); ?></a>
			</li>
		</ul>

		<div class="sui-sidenav-settings">
			<div class="sui-sidenav-hide-lg">
				<select class="sui-select sui-mobile-nav" style="display: none;">
					<option value="#ticket" selected="selected"><?php esc_html_e( 'My Tickets', 'wpmudev' ); ?></option>
					<option value="#access"><?php esc_html_e( 'Support Access', 'wpmudev' ); ?></option>
					<option value="#system"><?php esc_html_e( 'System Information', 'wpmudev' ); ?></option>
				</select>
			</div>
		</div>
	</div>

	<div class="sui-box js-sidenav-content" id="ticket" style="display: block;">
		<div class="sui-box-header">
			<h2 class="sui-box-title"><?php esc_html_e( 'My Tickets', 'wpmudev' ); ?></h2>
			<?php if ( $free_type && ! $has_hosted_access ) : ?>
				<div class="sui-actions-left">
					<span class="sui-tag sui-tag-pro">
						<?php esc_html_e( 'Pro', 'wpmudev' ); ?>
					</span>
				</div>
			<?php endif; ?>
			<div class="sui-actions-right">
				<?php if ( ! empty( $open_threads['all'] ) ) : ?>
					<?php if ( $free_type && ! $has_hosted_access ) : ?>
						<a
							href="https://wpmudev.com/contact/#i-have-a-presales-question"
							target="_blank"
							class="sui-button sui-button-blue"
						>
							<i class="sui-icon-help-support" aria-hidden="true"></i>
							<?php esc_html_e( 'Sales support', 'wpmudev' ); ?>
						</a>
						<a
							href="<?php echo esc_url( $hub_url ); ?>"
							target="_blank"
							class="sui-button sui-button-ghost"
						>
							<i class="sui-icon-hub" aria-hidden="true"></i>
							<?php esc_html_e( 'The Hub', 'wpmudev' ); ?>
						</a>
					<?php elseif ( ! $tickets_hidden ) : // premium member. ?>
						<?php if ( ! empty( $open_threads['all'] ) ) : ?>

							<a
								href="<?php echo esc_url( $url_open_ticket ); ?>"
								target="_blank"
								class="sui-button sui-button-blue"
								<?php echo( $has_support_access ? '' : 'disabled="disabled"' ); ?>
							>
								<i class="sui-icon-plus" aria-hidden="true"></i>
								<?php esc_html_e( 'New Ticket', 'wpmudev' ); ?>
							</a>

						<?php endif; ?>
					<?php endif; ?>
					<a
						href="<?php echo esc_url( $hub_url ); ?>"
						target="_blank"
						class="sui-button sui-button-ghost"
					>
						<i class="sui-icon-hub" aria-hidden="true"></i>
						<?php esc_html_e( 'The Hub', 'wpmudev' ); ?>
					</a>
				<?php endif; ?>

			</div>
		</div>

		<?php if ( $tickets_hidden ) : ?>

			<div class="sui-box-body">
				<div class="sui-notice sui-notice-info">
					<div class="sui-notice-content">
						<div class="sui-notice-message">
							<span class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></span>
							<p><?php printf( __( 'To create a new support ticket, or to view existing tickets, go to <a href="%s" target="_blank">The Hub</a>.', 'wpmudev' ), 'https://wpmudev.com/hub2/support' ); ?></p>
						</div>
					</div>
				</div>
			</div>

		<?php elseif ( empty( $open_threads['all'] ) ) : ?>

			<div class="sui-message sui-message-lg">

				<img
					src="<?php echo esc_url( WPMUDEV_Dashboard::$site->plugin_url . 'assets/images/module.png' ); ?>"
					srcset="<?php echo esc_url( WPMUDEV_Dashboard::$site->plugin_url . 'assets/images/module.png' ); ?> 1x, <?php echo esc_url( WPMUDEV_Dashboard::$site->plugin_url . 'assets/images/module@2x.png' ); ?> 2x"
					alt="Support"
					aria-hidden="true"
				/>

				<div class="sui-message-content">
					<?php if ( 'free' === $membership_type && ! $has_hosted_access ) : ?>
						<p><?php echo __( 'Our team is here to help you with any WordPress problem, anytime. Every WPMU DEV<br>membership comes with 24/7 expert live WordPress support, upgrade your membership to<br>gain access.', 'wpmudev' ); ?>
						<p>
							<a href="https://wpmudev.com/hub/account/?utm_source=wpmudev-dashboard&utm_medium=plugin&utm_campaign=dashboard_free_upgrade" class="sui-button sui-button-purple" style="margin-top: 10px;"><?php echo __( 'Upgrade Membership', 'wpmudev' ); ?></a>
						</p>
					<?php elseif ( in_array( $membership_type, array( 'expired', 'paused' ), true ) ) : ?>
						<p><?php echo __( 'Our team is here to help you with any WordPress problem, anytime. Every WPMU DEV<br>membership comes with 24/7 expert live WordPress support, renew your membership to<br>gain access.', 'wpmudev' ); ?>
						<p>
							<a href="https://wpmudev.com/hub/account/?utm_source=wpmudev-dashboard&utm_medium=plugin&utm_campaign=dashboard_expired_modal_reactivate" class="sui-button sui-button-purple" style="margin-top: 10px;"><?php echo __( 'Reactivate Membership', 'wpmudev' ); ?></a>
						</p>
					<?php else : ?>
						<p>
							<?php
							echo wp_kses_post(
								sprintf(
									__( 'You don’t have any active support tickets. When you create a support ticket, it will appear here. You can also access this in %1$sThe Hub%2$s.', 'wpmudev' ),
									'<a href="' . esc_url( $url_all_tickets ) . '" target="_blank">',
									'</a>'
								)
							);
							?>
						</p>

						<p>
							<a href="<?php echo esc_url( $url_open_ticket ); ?>"
							   target="_blank"
							   class="sui-button sui-button-blue"
								<?php echo( $has_support_access ? '' : 'disabled="disabled"' ); ?>>
								<i class="sui-icon-plus" aria-hidden="true"></i>
								<?php esc_html_e( 'New Ticket', 'wpmudev' ); ?>
							</a></p>
					<?php endif; ?>
				</div>

			</div>

		<?php else : ?>

			<div class="sui-box-body">
				<?php if ( 'expired' === $membership_type ) : ?>
					<p><?php esc_html_e( 'Here are your open and resolved support tickets, you can view all support tickets in the Hub', 'wpmudev' ); ?></p>
					<div class="sui-notice sui-notice-purple">
						<div class="sui-notice-content">
							<div class="sui-notice-message">
								<span class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></span>
								<p><?php esc_html_e( 'Your WPMU DEV Membership has expired, you no longer have access to live support. Reactivate your subscription to open a new ticket..', 'wpmudev' ); ?></p>
								<p>
									<a href="https://wpmudev.com/hub/account/?utm_source=wpmudev-dashboard&utm_medium=plugin&utm_campaign=dashboard_expired_modal_reactivate" class="sui-button sui-button-purple">
										<?php esc_html_e( 'Reactivate Membership', 'wpmudev' ); ?>
									</a>
								</p>
							</div>
						</div>
					</div>
				<?php endif; ?>
				<div class="sui-side-tabs">
					<div class="sui-tabs-menu js-filter-ticket">
						<div class="sui-tab-item active" data-filter="all" tabindex="1"><?php esc_html_e( 'All', 'wpmudev' ); ?></div>
						<div class="sui-tab-item" data-filter="open" tabindex="2"><?php esc_html_e( 'Open', 'wpmudev' ); ?></div>
						<div class="sui-tab-item" data-filter="resolved" tabindex="3"><?php esc_html_e( 'Resolved', 'wpmudev' ); ?></div>
						<div class="sui-tab-item" data-filter="feedback" tabindex="4"><?php esc_html_e( 'Feedback', 'wpmudev' ); ?></div>
					</div>
				</div>

				<?php foreach ( $open_threads as $key => $thread_list ) : ?>

					<div class="dashui-table-tickets js-filter-ticket-content" data-filter="<?php echo esc_attr( $key ); ?>" style="display: none;">
						<table class="sui-table sui-table-flushed">
							<thead>
							<tr>
								<th class="dashui-column-topic"><?php esc_html_e( 'Topic', 'wpmudev' ); ?></th>
								<th class="dashui-column-replies"><?php esc_html_e( 'Replies', 'wpmudev' ); ?></th>
								<th class="dashui-column-status"><?php esc_html_e( 'Status', 'wpmudev' ); ?></th>
							</tr>
							</thead>
							<tbody>

							<?php if ( empty( $thread_list ) ) { ?>

								<tr>
									<td colspan="3">
										<?php esc_html_e( 'No tickets are available.', 'wpmudev' ); ?>
									</td>
								</tr>

							<?php } else { ?>

								<?php foreach ( $thread_list as $item ) : ?>

									<tr>
										<td class="dashui-column-topic">
											<a href="<?php echo esc_url( $item['link'] ); ?>" target="_blank"><?php echo esc_html( $item['title'] ); ?></a>
										</td>
										<td class="dashui-column-replies">
											<?php echo esc_html( intval( $item['posts'] ) ); ?>
										</td>
										<td class="dashui-column-status">
											<div class="dashui-status-row">
												<span class="<?php echo esc_attr( $item['ui_status']['class'] ); ?>"><?php echo esc_html( $item['ui_status']['text'] ); ?></span>
												<?php
												$link_class   = '';
												$tooltip_attr = '';
												if ( $free_type && ! $has_hosted_access ) :
													$link_class   = ' sui-tooltip sui-tooltip-constrained sui-tooltip-top-right ';
													$tooltip_attr = __( 'You can view old support tickets, but not create new ones', 'wpmudev' );
												endif;
												?>
												<a data-tooltip="<?php echo esc_attr( $tooltip_attr ); ?>" class="sui-button-icon <?php echo esc_attr( $link_class ); ?>" href="<?php echo esc_url( $item['link'] ); ?>" target="_blank">
													<i class="sui-icon-chevron-right" aria-hidden="true"></i>
												</a>

											</div>

										</td>

									</tr>

								<?php endforeach; ?>

							<?php } ?>

							</tbody>

						</table>

						<?php if ( empty( $thread_list ) ) { ?>

							<div class="dashui-ticket">
								<span class="dashui-ticket-notice"><?php esc_html_e( 'No tickets are available.', 'wpmudev' ); ?></span>
							</div>

						<?php } else { ?>

							<?php foreach ( $thread_list as $item ) : ?>

								<div class="dashui-ticket">
									<span class="dashui-ticket-status <?php echo esc_attr( $item['ui_status']['class'] ); ?>"><?php echo esc_html( $item['ui_status']['text'] ); ?></span>
									<div class="dashui-ticket-topic">
										<span><?php echo esc_html( $item['title'] ); ?></span>
										<a
											href="<?php echo esc_url( $item['link'] ); ?>"
											target="_blank"
											class="sui-button-icon sui-button-icon-right"
										>
											<i class="sui-icon-chevron-right" aria-hidden="true"></i>
										</a>
									</div>
								</div>

							<?php endforeach; ?>

						<?php } ?>

					</div>

				<?php endforeach; ?>

				<p class="sui-block-content-center"><small>
						<?php
						echo wp_kses_post(
							sprintf(
								__( 'See all your support tickets in %1$sThe Hub%2$s.', 'wpmudev' ),
								'<a href="' . esc_url( $url_all_tickets ) . '" target="_blank">',
								'</a>'
							)
						);
						?>
					</small></p>

			</div>

		<?php endif; ?>

	</div>

	<div class="sui-box js-sidenav-content" id="access" style="display: none;">
		<div class="sui-box-header">
			<h2 class="sui-box-title"><?php esc_html_e( 'Support Access', 'wpmudev' ); ?></h2>
			<div class="sui-actions-left">
				<?php if ( $staff_login->enabled ) : ?>
					<span class="sui-tag sui-tag-blue"><?php esc_html_e( 'Active', 'wpmudev' ); ?></span>
				<?php else : ?>
					<?php if ( ! empty( $access_logs ) ) { ?>
						<span class="sui-tag"><?php esc_html_e( 'Inactive', 'wpmudev' ); ?></span>
					<?php } ?>
				<?php endif; ?>
			</div>

			<div class="sui-actions-right">
				<?php if ( ! empty( $access_logs ) && ! $staff_login->enabled ) : ?>
					<a href="<?php echo esc_url( $url_grant ); ?>"
					   class="sui-button sui-button-blue js-loading-link"
						<?php echo( $has_support_access ? '' : 'disabled="disabled"' ); ?>>
						<span class="sui-loading-text">
							<i class="sui-icon-key" aria-hidden="true"></i>
							<?php esc_html_e( 'Grant Support Access', 'wpmudev' ); ?>
						</span>
						<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
					</a>

				<?php endif; ?>

				<a id="modal-security" class="sui-button sui-button-ghost js-modal-security" href="javascript:;">
					<i class="sui-icon-question" aria-hidden="true"></i>
					<?php _e( 'SECURITY INFO', 'wpmudev' ); ?>
				</a>

			</div>


		</div>

		<div class="sui-box-body">
			<?php if ( ! $staff_login->enabled ) { ?>
				<?php if ( empty( $access_logs ) ) : ?>
					<div class="sui-message">
						<img
							src="<?php echo esc_url( WPMUDEV_Dashboard::$site->plugin_url . 'assets/images/module.png' ); ?>"
							srcset="<?php echo esc_url( WPMUDEV_Dashboard::$site->plugin_url . 'assets/images/module.png' ); ?> 1x, <?php echo esc_url( WPMUDEV_Dashboard::$site->plugin_url . 'assets/images/module@2x.png' ); ?> 2x"
							alt="dev-man"
							class="sui-image"
						/>

						<p><?php esc_html_e( 'Need help? Grant support access so our WPMU DEV Support Staff are able to log in and help troubleshoot issues with you. This is completely secure and only active for a time period of your choice.', 'wpmudev' ); ?></p>
						<?php if ( 'free' === $membership_type && ! $has_hosted_access ) : ?>
							<a href="https://wpmudev.com/hub/account/?utm_source=wpmudev-dashboard&utm_medium=plugin&utm_campaign=dashboard_free_upgrade" class="sui-button sui-button-purple" style="margin-top: 10px;"><?php esc_html_e( 'Upgrade Membership', 'wpmudev' ); ?></a>
						<?php elseif ( in_array( $membership_type, array( 'expired', 'paused' ), true ) ) : ?>
							<a href="https://wpmudev.com/hub/account/?utm_source=wpmudev-dashboard&utm_medium=plugin&utm_campaign=dashboard_expired_modal_reactivate" class="sui-button sui-button-purple" style="margin-top: 10px;"><?php esc_html_e( 'Reactivate Membership', 'wpmudev' ); ?></a>
						<?php else : ?>
							<a href="<?php echo esc_url( $url_grant ); ?>"
							   class="sui-button sui-button-blue js-loading-link"
								<?php echo( $has_support_access ? '' : 'disabled="disabled"' ); ?>>
							<span class="sui-loading-text">
								<i class="sui-icon-key" aria-hidden="true"></i>
								<?php esc_html_e( 'Grant Support Access', 'wpmudev' ); ?>
							</span>
								<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
							</a>
						<?php endif; ?>

					</div>

					<div class="sui-box-body">
						<p class="sui-block-content-center sui-p-small" style="width: 100%">
							<?php
							$learnmore_url = 'https://wpmudev.com/docs/getting-started/getting-support/#chapter-5';
							printf(
								esc_html__( 'Want to know more about the security of support access? %1$sLearn more%2$s', 'wpmudev' ),
								'<a target="_blank" class="js-modal-security" style="cursor:pointer">',
								'</a>'
							);
							?>
						</p>
					</div>

				<?php endif; ?>

			<?php } ?>

			<?php if ( $staff_login->enabled || ( ! $staff_login->enabled && ! empty( $access_logs ) ) ) : ?>

				<p><?php esc_html_e( 'Need help? Grant support access so our WPMU DEV Support Staff are able to log in and help troubleshoot issues with you. This is completely secure and only active for a time period of your choice.', 'wpmudev' ); ?></p>

			<?php endif; ?>

			<?php if ( $staff_login->enabled ) { ?>
				<div id="dashui-notice-support" class="sui-notice sui-notice-info">
					<div class="sui-notice-content">
						<div class="sui-notice-message">
							<span class="sui-notice-icon sui-icon-unlock sui-md" aria-hidden="true"></span>

							<p><?php echo esc_html( sprintf( __( "You have an active support session. If you haven't already, please let support staff know you have granted access. It will remain active for another %s.", 'wpmudev' ), human_time_diff( $staff_login->expires ) ) ); ?></p>
							<div class="sui-notice-buttons">

								<a
									href="<?php echo esc_url( $url_revoke ); ?>"
									class="sui-button js-loading-link"
								>
									<span class="sui-loading-text">
										<?php esc_html_e( 'END SESSION', 'wpmudev' ); ?>
									</span>
									<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
								</a>

								<a href="<?php echo esc_url( $url_extend ); ?>"
								   class="sui-button sui-button-ghost sui-tooltip js-loading-link"
								   data-tooltip="<?php esc_attr_e( 'Add another 3 days of support access', 'wpmudev' ); ?>"
									<?php echo( ! is_wpmudev_active_member() ? 'disabled="disabled"' : '' ); ?>>
									<span class="sui-loading-text">
										<?php esc_html_e( 'EXTEND', 'wpmudev' ); ?>
									</span>
									<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
								</a>

							</div>

						</div>
					</div>
				</div>
				<form
					method="POST"
					action="<?php echo esc_url( $urls->support_url . '#access' ); ?>"
					class="sui-form-field staff-notes <?php echo esc_attr( $notes_class ); ?>"
				>
					<input
						type="hidden"
						name="action"
						value="staff-note"
					/>

					<?php wp_nonce_field( 'staff-note', 'hash' ); ?>

					<label for="support-staff-notes-id" class="sui-description" style="margin-bottom: 10px;"><?php esc_html_e( 'If you think it would help, leave our support heroes a quick message to let them know the details of your issue.', 'wpmudev' ); ?></label>

					<textarea
						name="notes"
						rows="5"
						placeholder="<?php esc_html_e( 'E.g. The issue occurs on Chrome when on smaller screens...', 'wpmudev' ); ?>"
						id="support-staff-notes-id"
						class="sui-form-control"
					><?php echo esc_textarea( $notes ); ?></textarea>

					<div style="display: block; margin-top: 10px; text-align: right;">

						<button type="submit" class="sui-button sui-button-blue">
							<span class="sui-loading-text"><?php esc_html_e( 'Save Message', 'wpmudev' ); ?></span>
							<i class="sui-icon-loader sui-loading"></i>
						</button>

					</div>

				</form>

				<?php if ( empty( $access_logs ) ) : ?>
					<div class="sui-box-settings-row sui-flushed" style="padding-bottom: 5px;">
						<div class="sui-box-settings-col-2">
							<label class="sui-table-title"><?php esc_html_e( 'Recent Sessions', 'wpmudev' ); ?></label>
						</div>
					</div>
					<div id="dashui-notice-support-logs" class="sui-notice">
						<div class="sui-notice-content">
							<div class="sui-notice-message">
								<i class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></i>
								<p><?php if ( $staff_login->enabled ) : ?>
										<?php
										echo esc_html(
											sprintf(
												__( 'No one from Support has logged in yet. Sit tight, help is coming.', 'wpmudev' ),
												human_time_diff( $staff_login->expires )
											)
										);
										?>
									<?php else : ?>
										<?php echo esc_html( sprintf( __( 'No one from Support has logged in.', 'wpmudev' ), human_time_diff( $staff_login->expires ) ) ); ?>
									<?php endif; ?></p>
							</div>
						</div>
					</div>

				<?php endif; ?>

			<?php } ?>

		</div>

		<?php if ( ! empty( $access_logs ) ) : ?>

			<table class="sui-table sui-table-flushed dashui-table-sessions">
				<thead>
				<tr>
					<th colspan="2"><?php esc_html_e( 'Recent Sessions', 'wpmudev' ); ?></th>
				</tr>
				</thead>
				<tbody>
				<?php foreach ( $access_logs as $time => $user ) : ?>
					<?php
					$time = WPMUDEV_Dashboard::$site->to_localtime( $time );

					// backward compat
					$name = isset( $user['name'] ) ? $user['name'] : $user;
					$img  = isset( $user['image'] ) ? 'https://www.gravatar.com/avatar/' . $user['image'] : '';
					?>
					<tr>

						<td class="sui-table-item-title">
							<div class="dashui-staff-info">
								<span class="dashui-avatar" style="background-image: url( <?php echo esc_url( $img ); ?> );" aria-hidden="true"></span>
								<span class="dashui-name"><?php echo esc_html( $name ); ?></span>
								<span class="sui-tag"><?php esc_html_e( 'Staff', 'wpmudev' ); ?></span>
							</div>
						</td>
						<td>
							<?php esc_html_e( 'Last seen', 'wpmudev' ); ?>
							<?php echo esc_html( date_i18n( $date_format, $time ) ); ?>
							@ <?php echo esc_html( date_i18n( $time_format, $time ) ); ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

		<?php endif; ?>

		<?php
		if (
			$staff_login->enabled ||
			( ! $staff_login->enabled && ! empty( $access_logs ) )
		) :
			?>

			<div class="sui-box-footer">

				<p class="sui-block-content-center sui-p-small" style="width: 100%">
					<?php
					$learnmore_url = 'https://wpmudev.com/docs/getting-started/getting-support/#chapter-5';
					printf(
						esc_html__( 'Want to know more about the security of support access? %1$sLearn more%2$s', 'wpmudev' ),
						'<a href="' . esc_url( $learnmore_url ) . '" target="_blank">',
						'</a>'
					);
					?>
				</p>

			</div>

		<?php endif; ?>

	</div>

	<div class="sui-box js-sidenav-content" id="system" style="display: none;">

		<div class="sui-box-header">
			<h2 class="sui-box-title"><?php esc_html_e( 'System Information', 'wpmudev' ); ?></h2>
		</div>

		<div class="sui-box-body">
			<p><?php esc_html_e( 'Use this detailed overview of your system stack to debug issues with your WordPress installation.', 'wpmudev' ); ?></p>
			<ul class="dashui-list-sysinfo">

				<li>
					<strong><?php esc_html_e( 'WordPress', 'wpmudev' ); ?></strong>
					<span class="sui-tag"><?php echo esc_html( $wp_version ); ?></span>
				</li>

				<li>
					<strong><?php esc_html_e( 'WPMU DEV Dashboard', 'wpmudev' ); ?></strong>
					<span class="sui-tag"><?php echo esc_html( WPMUDEV_Dashboard::$version ); ?></span>
				</li>

			</ul>
		</div>

		<div class="sui-box-body">
			<?php $this->render( 'sui/part-system-info' ); ?>
		</div>

	</div>

</div>

<?php $this->render( 'sui/element-last-refresh' ); ?>

<?php $this->render( 'sui/footer' ); ?>

<div class="sui-modal sui-modal-lg">
	<div
		role="dialog"
		id="security-details"
		class="sui-modal-content"
		aria-modal="true"
		aria-labelledby="supportaccess-dialogTitle"
		aria-describedby=""
	>
		<div class="sui-box">
			<div class="sui-box-header">
				<h3 class="sui-box-title" id="supportaccess-dialogTitle"><?php esc_html_e( 'How secure is support access?', 'wpmudev' ); ?></h3>

				<button class="sui-button-icon plugin-modal-close sui-button-float--right" data-modal-close="">
					<i class="sui-icon-close sui-md" aria-hidden="true"></i>
					<span class="sui-screen-reader-text"><?php esc_html_e( 'Close this dialog.', 'wpmudev' ); ?></span>
				</button>
			</div>

			<div class="sui-box-body">
				<p class="sui-p-small"><?php esc_html_e( 'In short, our support access feature is bullet-proof secure and closed off to current WPMU DEV support staff only. We have never had any security issues with it, however you can disable it if you wish to.', 'wpmudev' ); ?></p>

				<h4 class="dashui-modal-header"><?php esc_html_e( 'How it works', 'wpmudev' ); ?></h4>
				<p id="dialogDescription"
				   class="sui-p-small"><?php esc_html_e( 'When you click the "Grant Access" button a random 64 character access token is generated that is only good for 120 hours (5 days) and saved in your Database. This token is sent to the WPMU DEV API over an SSL encrypted connection to prevent eavesdropping, and stored on our secure servers. This access token is in no way related to your password, and can only be used from our closed WPMU DEV API system for temporary access to this site.', 'wpmudev' ); ?></p>

				<h4 class="dashui-modal-header"><?php esc_html_e( 'Who has access?', 'wpmudev' ); ?></h4>
				<p class="sui-p-small"><?php echo wp_kses_post( __( 'Only current WPMU DEV support staff can use this token to login as your user account by submitting a special form that only they have access to. This will give them 1 hour of admin access to this site before their login cookie expires. Every support staff login during the 5 day period is logged locally and you can view the details on this page.', 'wpmudev' ) ); ?></p>

				<h4 class="dashui-modal-header"><?php esc_html_e( 'Revoke access', 'wpmudev' ); ?></h4>
				<p class="sui-p-small"><?php echo wp_kses_post( __( 'You may at any time revoke this access which invalidates the token and it will no longer be usable. If you have special security concerns and you would like to disable the support access tab and functionality completely and permanently for whatever reason, you may do so by adding this line to your wp-config.php file:', 'wpmudev' ) ); ?></p>

				<pre class="sui-code-snippet sui-no-copy">define('WPMUDEV_DISABLE_REMOTE_ACCESS', true);</pre>

			</div>
			<div class="sui-box-footer">

				<a id="close-sec-det" class="sui-button sui-button-ghost" data-a11y-dialog-hide="security-details"><?php esc_html_e( 'Close', 'wpmudev' ); ?></a>

				<div class="sui-actions-right">
					<a class="sui-button" href="<?php echo esc_url( 'https://wpmudev.com/docs/getting-started/getting-support/' ); ?>"><?php esc_html_e( 'Support Docs', 'wpmudev' ); ?></a>
				</div>

			</div>
		</div>
	</div>

</div>