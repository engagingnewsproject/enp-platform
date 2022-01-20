<?php
/**
 * Add notification modal (shown when enabling a disabled notification).
 *
 * @since 3.1.1
 * @package Hummingbird
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<script type="text/template" id="add-notifications-content">
	<div id="notifications-slide-schedule" class="sui-box sui-modal-slide sui-loaded sui-active" data-modal-size="lg">
		<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">
			<?php $this->pro_modal( 'template-header' ); ?>

			<h3 id="notification-modal-title" class="sui-box-title sui-lg">
				<?php esc_html_e( 'Schedule', 'wphb' ); ?>
			</h3>

			<p id="notification-modal-description" class="sui-description">
				<# if ( 'uptime' === data.module && 'notifications' === data.type ) { #>
				<?php esc_attr_e( 'Choose the threshold should trigger uptime notifications.', 'wphb' ); ?>
				<# } else { #>
				<?php esc_attr_e( 'Choose how often you want this notification to run.', 'wphb' ); ?>
				<# } #>
			</p>
		</div>

		<div class="sui-box-body sui-spacing-top--20 sui-spacing-bottom--20">
			<?php $this->pro_modal( 'template-schedule' ); ?>
		</div>

		<div class="sui-box-footer sui-flatten sui-content-center">
			<button role="button" class="sui-button" data-modal-slide="notifications-slide-recipients" data-modal-slide-focus="search-users" data-modal-slide-intro="next">
				<?php esc_html_e( 'Continue', 'wphb' ); ?>
			</button>
		</div>
	</div>

	<div id="notifications-slide-recipients" class="sui-box sui-modal-slide" data-modal-size="lg">
		<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">
			<?php $this->pro_modal( 'template-header', array( 'back' => 'notifications-slide-schedule' ) ); ?>

			<h3 class="sui-box-title sui-lg"><?php esc_html_e( 'Recipients', 'wphb' ); ?></h3>

			<p class="sui-description">
				<# if ( 'performance' === data.module && 'reports' === data.type ) { #>
				<?php esc_attr_e( 'Add as many recipients as you like. Each recipient will be notified of scheduled performance test results.', 'wphb' ); ?>
				<# } else if ( 'database' === data.module && 'reports' === data.type ) { #>
				<?php esc_attr_e( 'Add as many recipients as you like. Each recipient will be notified of scheduled cleanup results.', 'wphb' ); ?>
				<# } else if ( 'uptime' === data.module && 'reports' === data.type ) { #>
				<?php esc_attr_e( 'Add as many recipients as you like. Each recipient will be notified of any downtime logs for the scheduled period.', 'wphb' ); ?>
				<# } else if ( 'uptime' === data.module && 'notifications' === data.type ) { #>
				<?php esc_attr_e( 'Add as many recipients as you like. Each recipient will be notified when this website is unavailable.', 'wphb' ); ?>
				<# } #>
			</p>
		</div>

		<div class="sui-box-body sui-spacing-top--20 sui-spacing-bottom--20">
			<?php $this->pro_modal( 'template-recipients' ); ?>
		</div>

		<div class="sui-box-footer sui-flatten sui-content-center">
			<# if ( ( 'performance' === data.module || 'uptime' === data.module || 'database' === data.module ) && 'reports' === data.type ) { #>
			<button role="button" class="sui-button notification-next-buttons" data-modal-slide="notifications-slide-settings" data-modal-slide-focus="notifications-add-users" data-modal-slide-intro="next">
				<?php esc_html_e( 'Continue', 'wphb' ); ?>
			</button>
			<# } else { #>
			<button type="button" class="sui-button sui-button-blue" aria-live="polite" onclick="WPHB_Admin.notifications.activate()">
				<span class="sui-button-text-default"><?php esc_html_e( 'Activate', 'wphb' ); ?></span>
				<span class="sui-button-text-onload">
					<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
					<?php esc_html_e( 'Activating', 'wphb' ); ?>
				</span>
			</button>
			<# } #>
		</div>
	</div>

	<div id="notifications-slide-settings" class="sui-box sui-modal-slide" data-modal-size="lg">
		<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">
			<?php $this->pro_modal( 'template-header', array( 'back' => 'notifications-slide-recipients' ) ); ?>

			<h3 class="sui-box-title sui-lg"><?php esc_html_e( 'Customize', 'wphb' ); ?></h3>

			<p class="sui-description">
				<# if ( 'performance' === data.module && 'reports' === data.type ) { #>
				<?php esc_html_e( 'Choose your email preferences for the performance test report.', 'wphb' ); ?>
				<# } #>

				<# if ( 'uptime' === data.module && 'reports' === data.type ) { #>
				<?php esc_html_e( 'Configure general settings for Uptime report.', 'wphb' ); ?>
				<# } #>
			</p>
		</div>

		<div class="sui-box-body sui-spacing-top--20 sui-spacing-bottom--20">
			<?php $this->pro_modal( 'template-settings' ); ?>
		</div>

		<div class="sui-box-footer sui-flatten sui-content-center">
			<button type="button" class="sui-button sui-button-blue" aria-live="polite" onclick="WPHB_Admin.notifications.activate( true )">
				<span class="sui-button-text-default"><?php esc_html_e( 'Activate', 'wphb' ); ?></span>
				<span class="sui-button-text-onload">
					<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
					<?php esc_html_e( 'Activating', 'wphb' ); ?>
				</span>
			</button>
		</div>
	</div>
</script>

<script type="text/template" id="edit-notifications-content">
	<div class="sui-box">
		<div class="sui-box-header">
			<h3 id="notification-modal-title" class="sui-box-title"><?php esc_html_e( 'Configure', 'wphb' ); ?></h3>

			<button class="sui-button-icon sui-button-float--right" onclick="location.href = wphb.links.notifications;">
				<span class="sui-icon-close sui-md" aria-hidden="true"></span>
				<span class="sui-screen-reader-text"><?php esc_attr_e( 'Close this modal', 'wphb' ); ?></span>
			</button>
		</div>

		<div class="sui-box-body">
			<div class="sui-tabs sui-tabs-flushed">
				<div role="tablist" class="sui-tabs-menu">
					<button type="button" role="tab" id="enm-schedule" class="sui-tab-item <# if( 'schedule' === data.view ) { #>active<# } #>" aria-controls="enm-schedule-content" aria-selected="true">
						<?php esc_html_e( 'Schedule', 'wphb' ); ?>
					</button>

					<button type="button" role="tab" id="enm-recipients" class="sui-tab-item <# if( 'recipients' === data.view ) { #>active<# } #>" aria-controls="enm-recipients-content" aria-selected="false" tabindex="-1">
						<?php esc_html_e( 'Recipients', 'wphb' ); ?>
					</button>

					<# if ( ( 'performance' === data.module || 'uptime' === data.module || 'database' === data.module ) && 'reports' === data.type ) { #>
					<button type="button" role="tab" id="enm-settings" class="sui-tab-item" aria-controls="enm-settings-content" aria-selected="false" tabindex="-1">
						<?php esc_html_e( 'Settings', 'wphb' ); ?>
					</button>
					<# } #>
				</div>

				<div class="sui-tabs-content">
					<div role="tabpanel" tabindex="0" id="enm-schedule-content" class="sui-tab-content <# if( 'schedule' === data.view ) { #>active<# } #>" aria-labelledby="enm-schedule">
						<p class="sui-description">
							<# if ( 'uptime' === data.module && 'notifications' === data.type ) { #>
							<?php esc_attr_e( 'Choose the threshold should trigger uptime notifications.', 'wphb' ); ?>
							<# } else { #>
							<?php esc_attr_e( 'Choose how often you want this notification to run.', 'wphb' ); ?>
							<# } #>
						</p>
						<?php $this->pro_modal( 'template-schedule' ); ?>
					</div>

					<div role="tabpanel" tabindex="0" id="enm-recipients-content" class="sui-tab-content <# if( 'recipients' === data.view ) { #>active<# } #>" aria-labelledby="enm-recipients" hidden>
						<p class="sui-description">
							<# if ( 'performance' === data.module && 'reports' === data.type ) { #>
							<?php esc_attr_e( 'Add as many recipients as you like. Each recipient will be notified of scheduled performance test results.', 'wphb' ); ?>
							<# } else if ( 'database' === data.module && 'reports' === data.type ) { #>
							<?php esc_attr_e( 'Add as many recipients as you like. Each recipient will be notified of scheduled cleanup results.', 'wphb' ); ?>
							<# } else if ( 'uptime' === data.module && 'reports' === data.type ) { #>
							<?php esc_attr_e( 'Add as many recipients as you like. Each recipient will be notified of any downtime logs for the scheduled period.', 'wphb' ); ?>
							<# } else if ( 'uptime' === data.module && 'notifications' === data.type ) { #>
							<?php esc_attr_e( 'Add as many recipients as you like. Each recipient will be notified when this website is unavailable.', 'wphb' ); ?>
							<# } #>
						</p>
						<?php $this->pro_modal( 'template-recipients' ); ?>
					</div>

					<# if ( ( 'performance' === data.module || 'uptime' === data.module || 'database' === data.module ) && 'reports' === data.type ) { #>
					<div role="tabpanel" tabindex="0" id="enm-settings-content" class="sui-tab-content" aria-labelledby="enm-settings" hidden>
						<?php $this->pro_modal( 'template-settings' ); ?>
					</div>
					<# } #>
				</div>
			</div>
		</div>

		<div class="sui-box-footer sui-content-separated">
			<button class="sui-button sui-button-ghost" data-id="{{{ data.module }}}" data-type="{{{ data.type }}}" onclick="WPHB_Admin.notifications.disable()">
				<span class="sui-icon-power-on-off" aria-hidden="true"></span>
				<?php esc_html_e( 'Deactivate', 'wphb' ); ?>
			</button>

			<# if ( ( 'performance' === data.module || 'uptime' === data.module || 'database' === data.module ) && 'reports' === data.type ) { #>
			<button type="button" class="sui-button sui-button-blue" aria-live="polite" onclick="WPHB_Admin.notifications.update( true )">
			<# } else { #>
			<button type="button" class="sui-button sui-button-blue" aria-live="polite" onclick="WPHB_Admin.notifications.update()">
			<# } #>
				<span class="sui-button-text-default">
					<span class="sui-icon-save" aria-hidden="true"></span>
					<?php esc_html_e( 'Save Changes', 'wphb' ); ?>
				</span>
				<span class="sui-button-text-onload">
					<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
					<?php esc_html_e( 'Saving Changes', 'wphb' ); ?>
				</span>
			</button>
		</div>
	</div>
</script>

<div class="sui-modal sui-modal-lg">
	<div
		role="dialog"
		id="notification-modal"
		class="sui-modal-content"
		aria-modal="true"
		aria-labelledby="notification-modal-title"
		aria-describedby="notification-modal-description"
	>
	</div>
</div>