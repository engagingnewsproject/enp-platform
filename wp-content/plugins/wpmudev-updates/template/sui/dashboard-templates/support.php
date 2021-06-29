<?php
/**
 * Template file for support section in main Dashboard page.
 *
 * @package WPMUDEV_Dashboard
 */

$threads     = $member['forum']['support_threads'];
$all_threads = array();
$url_revoke  = wp_nonce_url( add_query_arg( 'action', 'remote-revoke', $urls->support_url . '#access' ), 'remote-revoke', 'hash' );
$url_extend  = wp_nonce_url( add_query_arg( 'action', 'remote-extend', $urls->support_url . '#access' ), 'remote-extend', 'hash' );

foreach ( $threads as $thread ) {
	$total_thread = array();

	if ( empty( $thread['title'] ) ) {
		continue;
	}

	if ( empty( $thread['status'] ) ) {
		continue;
	}

	if ( 'resolved' === $thread['status'] ) {
		continue;
	}

	if ( isset( $thread['unread'] ) && $thread['unread'] ) {
		$all_threads[] = array(
			'class' => 'sui-tag sui-tag-yellow sui-tag-sm',
			'text'  => __( 'Feedback', 'wpmudev' ),
			'title' => $thread['title'],
			'url'   => $thread['link'],
		);
	} else {
		$all_threads[] = array(
			'class' => 'sui-tag sui-tag-blue sui-tag-sm',
			'text'  => __( 'Open', 'wpmudev' ),
			'title' => $thread['title'],
			'url'   => $thread['link'],
		);
	}
}
?>
<div class="sui-box wpmud-main-page-support-box">
	<div class="sui-box-header">
		<h3 class="sui-box-title">
			<i class="sui-icon-help-support" aria-hidden="true"></i>
			<?php esc_html_e( 'Support', 'wpmudev' ); ?>
		</h3>
		<?php if ( 'free' === $membership_data['membership'] ) : ?>
			<div class="sui-actions-left">
				<span class="sui-tag sui-tag-purple sui-dashboard-expired-pro-tag">
				<?php echo esc_html__( 'Pro', 'wpmudev' ); ?>
				</span>
			</div>
		<?php else : // premium user. ?>
			<?php if ( ! $staff_login->enabled ) { ?>
				<div class="sui-actions-right">
					<a href="<?php echo esc_url( $urls->support_url . '#access' ); ?>" style="font-size:13px">
						<?php esc_html_e( 'Grant support access', 'wpmudev' ); ?>
					</a>
				</div>
			<?php } ?>
		<?php endif; ?>
	</div><?php // end header box. ?>




	<div class="sui-box-body">
		<p><?php esc_html_e( 'Get 24/7 support for any issue you’re having. When you have active tickets they’ll be displayed here.', 'wpmudev' ); ?></p>
		<?php
				if ( ! $staff_login->enabled && empty( $all_threads ) && 'free' !== $membership_data['membership'] ) :
					?>
					<a href="<?php echo esc_url( 'https://wpmudev.com/hub/support/#wpmud-chat-pre-survey-modal' ); ?>" target="_blank" class="sui-button sui-button-blue"><i class="sui-icon-plus" aria-hidden="true"></i><?php echo esc_html__( 'Get Support' ); ?></a>
					<?php
			endif;
				?>

</div>
		<?php if ( $staff_login->enabled ) {
				?>
		<div class="sui-notice sui-notice-info" style="margin: 0px 30px 30px;">
					<div class="sui-notice-content">
						<div class="sui-notice-message">
							<span class="sui-notice-icon sui-icon-lock sui-md" aria-hidden="true"></span>
								<?php // translators: %s - human readable time period. ?>
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
				<?php
		}
		if ( ! empty( $all_threads ) ) {
			?>
	<table class="sui-table sui-table-flushed wpmud-tickets" style="border-top:1px solid #e6e6e6; ">
		<tbody>
				<?php foreach ( $all_threads as $thread ) { ?>
			<tr>
				<td class="dashui-item-content">
					<p>
						<?php echo esc_html( wp_trim_words( $thread['title'], 6, '...' ) ); ?>
					</p>
				</td>
				<td class="wpmud-ticket-status">
					<span class="<?php echo esc_attr( $thread['class'] ); ?>"> <?php echo esc_html( $thread['text'] ); ?></span>
				</td>
				<td class="wpmud-ticket-action">
					<a class="sui-button-icon" href="<?php echo esc_url( $thread['url'] ); ?>">
						<?php if ( 'free' === $membership_data['membership'] ) : ?>
						<i class="sui-icon-lock" aria-hidden="true"></i>
						<?php else : ?>
						<i class="sui-icon-eye" aria-hidden="true"></i>
						<?php endif; ?>
					</a>
				</td>
			</tr>
					<?php
				}
				?>
		</tbody>
	</table>
				<?php
			}
			?>
	<?php // box footer for premium users. ?>
	<?php if ( ( ! empty( $all_threads ) || $staff_login->enabled ) && 'free' !== $membership_data['membership'] ) { ?>
	<div class="sui-box-footer">
		<?php if ( ! empty( $all_threads ) ) { ?>
		<a href="<?php echo esc_url( $urls->support_url ); ?>" class="sui-button sui-button-ghost">
			<i class="sui-icon-eye" aria-hidden="true"></i>
			<?php esc_html_e( 'VIEW ALL', 'wpmudev' ); ?>
		</a>
		<?php } ?>
		<div class="sui-actions-right">
			<a href="<?php echo esc_url( 'https://wpmudev.com/hub/support/#wpmud-chat-pre-survey-modal' ); ?>" target="_blank" class="sui-button sui-button-blue"><i class="sui-icon-plus" aria-hidden="true"></i><?php echo esc_html__( 'Get Support' ); ?></a>
		</div>
	</div>
	<?php } ?>
	<?php
	if ( 'free' === $membership_data['membership'] ) :
		if ( empty( $all_threads ) ) :
			?>
		<div class="sui-box-footer wpmud-main-page-support-no-top">
			<a
				href="https://wpmudev.com/contact/#i-have-a-presales-question"
				target="_blank"
				class="sui-button sui-button-blue"
			>
				<i class="sui-icon-help-support" aria-hidden="true"></i>
				<?php esc_html_e( 'Sales support', 'wpmudev' ); ?>
			</a>
		</div>
				<?php
			else : // There are open support tickets.
				?>
		<div class="sui-box-footer wpmud-main-page-support">
			<div class="sui-actions-left">
				<a href="<?php echo esc_url( $urls->support_url ); ?>" class="sui-button sui-button-ghost">
					<i class="sui-icon-eye" aria-hidden="true"></i>
					<?php esc_html_e( 'VIEW ALL', 'wpmudev' ); ?>
				</a>
			</div>
			<div class="sui-actions-right">
				<a
					href="https://wpmudev.com/contact/#i-have-a-presales-question"
					target="_blank"
					class="sui-button sui-button-blue"
				>
					<i class="sui-icon-help-support" aria-hidden="true"></i>
					<?php esc_html_e( 'Sales support', 'wpmudev' ); ?>
				</a>
			</div>
		</div><!-- box footer -->
				<?php
			endif;
		endif;
	?>
</div>