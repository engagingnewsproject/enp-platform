<?php
/**
 * Notifications template: recipients.
 *
 * @since 3.1.1
 * @package Hummingbird
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div id="notifications-resend-notice" class="sui-notice sui-notice-success notifications-resend-notice sui-hidden">
	<div class="sui-notice-content">
		<div class="sui-notice-message">
			<span class="sui-notice-icon sui-icon-check-tick" aria-hidden="true"></span>
			<p></p>
		</div>
		<div class="sui-notice-actions">
			<button class="sui-button-icon sui-tooltip" data-notice-close="notifications-resend-notice" data-tooltip="<?php esc_attr_e( 'Dismiss', 'wphb' ); ?>">
				<span class="sui-icon-check" aria-hidden="true"></span>
				<span class="sui-screen-reader-text"><?php esc_html_e( 'Close this notice', 'wphb' ); ?></span>
			</button>
		</div>
	</div>
</div>

<div class="sui-tabs sui-tabs-overflow">
	<div role="tablist" class="sui-tabs-menu">
		<button type="button" role="tab" id="notifications-add-users" class="sui-tab-item active" aria-controls="notifications-add-users-content" aria-selected="true">
			<?php esc_html_e( 'Add users', 'wphb' ); ?>
		</button>

		<button type="button" role="tab" id="notifications-invite-users" class="sui-tab-item" aria-controls="notifications-invite-users-content" aria-selected="false" tabindex="-1">
			<?php esc_html_e( 'Add by email', 'wphb' ); ?>
		</button>
	</div>

	<div class="sui-tabs-content">
		<div role="tabpanel" tabindex="0" id="notifications-add-users-content" class="sui-tab-content active" aria-labelledby="notifications-add-users">
			<div class="sui-form-field sui-no-margin-bottom">
				<label for="search-users" class="sui-label"><?php esc_html_e( 'Search users', 'wphb' ); ?></label>
				<select id="search-users" class="sui-select" data-theme="search" data-placeholder="<?php esc_attr_e( 'Type username', 'wphb' ); ?>" multiple></select>
			</div>

			<# const hasUserRecipients = data.recipients.find( r => undefined !== r.role && '' !== r.role ); #>
			<div class="<# if ( undefined === hasUserRecipients ) { #>sui-hidden<# } else { #>sui-margin-top<# } #>">
				<strong><?php esc_html_e( 'Added users', 'wphb' ); ?></strong>
				<div class="sui-recipients" id="modal-user-recipients-list">
					<# for ( const recipient of data.recipients ) { #>
						<# if ( '' !== recipient.role ) { #>
						<div class="sui-recipient" data-id="{{{ recipient.id }}}" data-email="{{{ recipient.email }}}">
							<# let subClass = ''; #>
							<# if ( 'undefined' !== typeof recipient.is_pending ) { #>
								<# if ( ! recipient.is_pending && 'undefined' !== typeof recipient.is_subscribed && ! recipient.is_subscribed ) { subClass = 'unsubscribed'; } else { #>
								<# subClass = recipient.is_pending ? 'pending' : 'confirmed'; #>
								<# } #>
							<# } #>
							<span class="sui-recipient-name">
								<span class="subscriber {{{ subClass }}}">
									<# if ( 'pending' === subClass ) { #>
									<span class="sui-tooltip" data-tooltip="<?php esc_attr_e( 'Awaiting confirmation', 'wphb' ); ?>">
									<# } #>
										<img src="{{{ recipient.avatar }}}" alt="{{{ recipient.email }}}">
									<# if ( 'pending' === subClass ) { #></span><# } #>
								</span>
								<span class="wphb-recipient-name">{{{ recipient.name }}}</span>
							</span>
							<span class="sui-recipient-email">{{{ recipient.role }}}</span>
							<# if ( 'pending' === subClass || 'unsubscribed' === subClass ) { #>
							<button
								type="button"
								class="resend-invite sui-button-icon sui-tooltip"
								data-tooltip="<?php esc_attr_e( 'Resend invite email', 'wphb' ); ?>"
								onclick="WPHB_Admin.notifications.resendInvite( '{{{ recipient.name }}}', '{{{ recipient.email }}}' )">
								<span class="sui-icon-send" aria-hidden="true"></span>
							</button>
							<# } #>
							<button
								type="button"
								class="sui-button-icon sui-tooltip"
								data-tooltip="<?php esc_attr_e( 'Remove recipient', 'wphb' ); ?>"
								onclick="WPHB_Admin.notifications.removeUser( {{{ recipient.id }}}, '{{{ recipient.email }}}' )">
								<span class="sui-icon-trash" aria-hidden="true"></span>
							</button>
						</div>
						<# } #>
					<# } #>
				</div>
			</div>

			<div class="sui-margin-top sui-hidden">
				<strong><?php esc_html_e( 'Users', 'wphb' ); ?></strong>
				<div class="sui-recipients" id="modal-wp-user-list"></div>
			</div>

			<div class="sui-notice sui-notice-warning sui-margin-top notifications-recipients-notice sui-hidden">
				<div class="sui-notice-content">
					<div class="sui-notice-message">
						<span class="sui-notice-icon sui-icon-info" aria-hidden="true"></span>
						<p></p>
					</div>
				</div>
			</div>
		</div>

		<div role="tabpanel" tabindex="0" id="notifications-invite-users-content" class="sui-tab-content" aria-labelledby="notifications-invite-users" hidden>
			<p class="sui-description">
				<# if ( 'uptime' === data.module && 'notifications' === data.type ) { #>
				<?php esc_html_e( 'Add recipient credentials below to invite for notification subscription.', 'wphb' ); ?>
				<# } else { #>
				<?php esc_html_e( 'Add credentials below for each recipient.', 'wphb' ); ?>
				<# } #>
			</p>

			<strong><?php esc_html_e( 'Invite Recipients', 'wphb' ); ?></strong>

			<div class="sui-form-field">
				<label for="recipient-name" id="label-recipient-name" class="sui-label">
					<?php esc_html_e( 'First name', 'wphb' ); ?>
				</label>

				<input
					placeholder="<?php esc_attr_e( 'E.g. John', 'wphb' ); ?>"
					id="recipient-name"
					class="sui-form-control"
					aria-labelledby="label-recipient-name"
				/>
			</div>

			<div class="sui-form-field">
				<label for="recipient-email" id="label-recipient-email" class="sui-label">
					<?php esc_html_e( 'Email address', 'wphb' ); ?>
				</label>

				<input
					placeholder="<?php esc_attr_e( 'E.g John@doe.com', 'wphb' ); ?>"
					id="recipient-email"
					class="sui-form-control"
					aria-labelledby="label-recipient-email"
				/>
				<span id="error-recipient-email" class="sui-error-message" role="alert"></span>
			</div>

			<div class="sui-form-field sui-no-margin-bottom">
				<button type="button" id="add-recipient-button" class="sui-button" aria-live="polite" disabled="disabled">
					<span class="sui-button-text-default"><?php esc_html_e( 'Add recipient', 'wphb' ); ?></span>
					<span class="sui-button-text-onload">
						<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
						<?php esc_html_e( 'Adding recipient', 'wphb' ); ?>
					</span>
				</button>
			</div>

			<# const hasInvitedRecipients = data.recipients.find( r => undefined === r.role || '' === r.role ); #>
			<div class="<# if ( undefined === hasInvitedRecipients ) { #>sui-hidden<# } else { #>sui-margin-top<# } #>">
				<strong><?php esc_html_e( 'Added users', 'wphb' ); ?></strong>
				<div class="sui-recipients" id="modal-email-recipients-list">
					<# for ( const recipient of data.recipients ) { #>
						<# if ( undefined === recipient.role || '' === recipient.role ) { #>
						<div class="sui-recipient" data-id="{{{ recipient.id }}}" data-email="{{{ recipient.email }}}">
							<# let subClass = ''; #>
							<# if ( 'undefined' !== typeof recipient.is_pending ) { #>
								<# if ( ! recipient.is_pending && 'undefined' !== typeof recipient.is_subscribed && ! recipient.is_subscribed ) { subClass = 'unsubscribed'; } else { #>
								<# subClass = recipient.is_pending ? 'pending' : 'confirmed'; #>
								<# } #>
							<# } #>
							<span class="sui-recipient-name">
								<span class="subscriber {{{ subClass }}}">
									<# if ( 'pending' === subClass ) { #>
									<span class="sui-tooltip" data-tooltip="<?php esc_attr_e( 'Awaiting confirmation', 'wphb' ); ?>">
									<# } #>
									<img src="{{{ recipient.avatar }}}" alt="{{{ recipient.email }}}">
									<# if ( 'pending' === subClass ) { #></span><# } #>
								</span>
								<span>{{{ recipient.name }}}</span>
							</span>
							<span class="sui-recipient-email">{{{ recipient.email }}}</span>
							<# if ( 'pending' === subClass || 'unsubscribed' === subClass ) { #>
							<button
								type="button"
								class="resend-invite sui-button-icon sui-tooltip"
								data-tooltip="<?php esc_attr_e( 'Resend invite email', 'wphb' ); ?>"
								onclick="WPHB_Admin.notifications.resendInvite( '{{{ recipient.name }}}', '{{{ recipient.email }}}' )">
								<span class="sui-icon-send" aria-hidden="true"></span>
							</button>
							<# } #>
							<button
								type="button"
								class="sui-button-icon sui-tooltip"
								data-tooltip="<?php esc_attr_e( 'Remove recipient', 'wphb' ); ?>"
								onclick="WPHB_Admin.notifications.removeUser( {{{ recipient.id }}}, '{{{ recipient.email }}}', 'email' )">
								<span class="sui-icon-trash" aria-hidden="true"></span>
							</button>
						</div>
						<# } #>
					<# } #>
				</div>
			</div>

			<div class="sui-notice sui-notice-warning sui-margin-top notifications-recipients-notice sui-hidden">
				<div class="sui-notice-content">
					<div class="sui-notice-message">
						<span class="sui-notice-icon sui-icon-info" aria-hidden="true"></span>
						<p></p>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<# if ( 'uptime' === data.module && 'notifications' === data.type ) { #>
<p class="sui-description sui-margin-top" style="text-align: center">
	<?php esc_html_e( 'Note: Added recipients must confirm their subscription to begin receiving emails.', 'wphb' ); ?>
</p>
<# } #>