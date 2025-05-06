<?php
/**
 * Bulk notices
 *
 * @package WPMUDEV DASHBOARD 4.10.0
 */

?>


<div class="js-notifications sui-floating-notices">

	<!-- Plugin activation success. -->
	<div
		role="alert"
		id="js-activated-single"
		class="sui-notice"
		aria-live="assertive"
		data-show-dismiss="false"
		data-notice-msg="<p><strong><?php esc_html_e( 'Success', 'wpmudev' ); ?>:</strong> <?php esc_html_e( 'Plugin activated successfully.', 'wpmudev' ); ?></p><p><?php esc_html_e( 'Please wait while we refresh the page...', 'wpmudev' ); ?></p>"
	>
	</div>

	<!-- Plugin activation failed. -->
	<div
		role="alert"
		id="js-failed-activated-single"
		class="sui-notice"
		aria-live="assertive"
		data-show-dismiss="true"
		data-notice-msg="<p><strong><?php esc_html_e( 'Failed', 'wpmudev' ); ?>:</strong> <?php esc_html_e( 'Plugin failed to be activated.', 'wpmudev' ); ?></p>"
	>
	</div>

	<!-- Plugin activation multi. -->
	<div
		role="alert"
		id="js-activated-multi"
		class="sui-notice"
		aria-live="assertive"
		data-show-dismiss="false"
		data-notice-msg="<p><strong><?php esc_html_e( 'Success', 'wpmudev' ); ?>:</strong> <?php esc_html_e( 'Plugins activated.', 'wpmudev' ); ?></p><p><?php esc_html_e( 'Please wait while we refresh the page...', 'wpmudev' ); ?></p>"
	>
	</div>

	<!-- Plugin deactivation. -->
	<div
		role="alert"
		id="js-deactivated-single"
		class="sui-notice"
		aria-live="assertive"
		data-show-dismiss="false"
		data-notice-msg="<p><strong><?php esc_html_e( 'Success', 'wpmudev' ); ?>:</strong> <?php esc_html_e( 'Plugin deactivated.', 'wpmudev' ); ?></p><p><?php esc_html_e( 'Please wait while we refresh the page...', 'wpmudev' ); ?></p>"
	>
	</div>

	<!-- Plugin deactivation multi. -->
	<div
		role="alert"
		id="js-deactivated-multi"
		class="sui-notice"
		aria-live="assertive"
		data-show-dismiss="false"
		data-notice-msg="<p><strong><?php esc_html_e( 'Success', 'wpmudev' ); ?>:</strong> <?php esc_html_e( 'Plugins deactivated.', 'wpmudev' ); ?></p><p><?php esc_html_e( 'Please wait while we refresh the page...', 'wpmudev' ); ?></p>"
	>
	</div>

	<!-- Plugin install success. -->
	<div
		role="alert"
		id="js-installed-single"
		class="sui-notice"
		aria-live="assertive"
		data-show-dismiss="true"
		data-notice-msg="<p><strong><?php esc_html_e( 'Success', 'wpmudev' ); ?>:</strong> <?php esc_html_e( 'Plugin successfully installed.', 'wpmudev' ); ?></p>"
	>
	</div>

	<!-- Plugin install failed. -->
	<div
		role="alert"
		id="js-failed-installed-single"
		class="sui-notice"
		aria-live="assertive"
		data-show-dismiss="true"
		data-notice-msg="<p><strong><?php esc_html_e( 'Failed', 'wpmudev' ); ?>:</strong> <?php esc_html_e( 'Plugin failed to be installed.', 'wpmudev' ); ?></p>"
	>
	</div>

	<!-- Plugin delete success. -->
	<div
		role="alert"
		id="js-deleted-single"
		class="sui-notice"
		aria-live="assertive"
		data-show-dismiss="true"
		data-notice-msg="<p><strong><?php esc_html_e( 'Success', 'wpmudev' ); ?>:</strong> <?php esc_html_e( 'Plugin successfully deleted.', 'wpmudev' ); ?></p>"
	>
	</div>

	<!-- Plugin delete failed. -->
	<div
		role="alert"
		id="js-failed-deleted-single"
		class="sui-notice"
		aria-live="assertive"
		data-show-dismiss="true"
		data-notice-msg="<p><strong><?php esc_html_e( 'Failed', 'wpmudev' ); ?>:</strong> <?php esc_html_e( 'Plugin failed to be deleted.', 'wpmudev' ); ?></p>"
	>
	</div>

	<!-- Plugin delete bulk failed. -->
	<div
		role="alert"
		id="js-failed-deleted-multiple"
		class="sui-notice"
		aria-live="assertive"
		data-show-dismiss="true"
		data-notice-msg="<p><?php esc_html_e( 'Plugins that are active or not installed cannot be deleted.', 'wpmudev' ); ?></p>"
	>
	</div>

	<!-- Plugin update success. -->
	<div
		role="alert"
		id="js-updated-single"
		class="sui-notice"
		aria-live="assertive"
		data-show-dismiss="true"
		data-notice-msg="<p><strong><?php esc_html_e( 'Success', 'wpmudev' ); ?>:</strong> <?php esc_html_e( 'Plugin successfully updated.', 'wpmudev' ); ?></p>"
	>
	</div>

	<!-- Plugin update failed. -->
	<div
		role="alert"
		id="js-failed-updated-single"
		class="sui-notice"
		aria-live="assertive"
		data-show-dismiss="true"
		data-notice-msg="<p><strong><?php esc_html_e( 'Failed', 'wpmudev' ); ?>:</strong> <?php esc_html_e( 'Plugin failed to be updated.', 'wpmudev' ); ?></p>"
	>
	</div>

	<!-- Plugin update bulk. -->
	<div
		role="alert"
		id="js-updated-bulk"
		class="sui-notice"
		aria-live="assertive"
		data-show-dismiss="true"
		data-notice-msg="<p><strong><?php esc_html_e( 'Success', 'wpmudev' ); ?>:</strong> <?php esc_html_e( 'Plugins successfully updated.', 'wpmudev' ); ?></p>"
	>
	</div>

	<!-- Plugin installed bulk. -->
	<div
		role="alert"
		id="js-installed-bulk"
		class="sui-notice"
		aria-live="assertive"
		data-show-dismiss="true"
		data-notice-msg="<p><strong><?php esc_html_e( 'Success', 'wpmudev' ); ?>:</strong> <?php esc_html_e( 'Plugins successfully installed.', 'wpmudev' ); ?></p>"
	>
	</div>

	<!-- Plugin deleted bulk. -->
	<div
		role="alert"
		id="js-deleted-bulk"
		class="sui-notice"
		aria-live="assertive"
		data-show-dismiss="true"
		data-notice-msg="<p><strong><?php esc_html_e( 'Success', 'wpmudev' ); ?>:</strong> <?php esc_html_e( 'Plugins successfully deleted.', 'wpmudev' ); ?></p>"
	>
	</div>

	<!-- Plugin general fail. -->
	<div
		role="alert"
		id="js-general-fail"
		class="sui-notice"
		aria-live="assertive"
		data-show-dismiss="true"
		data-notice-msg="<p><strong><?php esc_html_e( 'Failed', 'wpmudev' ); ?>:</strong> <?php esc_html_e( 'Whoops, we had an unexpected response from WordPress, please try again.', 'wpmudev' ); ?></p>"
	>
	</div>

</div>