<?php
/**
 * Admin UI - Logic for actions which take place on the Staging Tab.
 *
 * @package wpengine/common-mu-plugin
 */

declare(strict_types=1);

namespace wpengine\admin_options;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * If this is a staging site, don't show any staging controls.
 */
function hide_controls_for_non_live() {
	if ( \is_wpe_snapshot() ) {
		?>
		<div class="notice wpe-error is-dismissible inline">
			<p><?php echo esc_html( __( 'Cannot use the standard WPEngine controls from a staging server!. This is valid only from your live site.', 'wpe-common' ) ); ?></p>
		</div>
		<?php
		die();
	}
}
add_action( 'wpe_common_admin_notices', __NAMESPACE__ . '\hide_controls_for_non_live', 1 );

/**
 * Deploy From Staging
 * Sends the api request to deploy from staging.
 * This function was formerly located in ajax.php, but has been changed to be non-ajax with the new redesign of 2021.
 * That function is moved here with almost no changes, aside from security improvements and WPCS applied.
 */
function deploy_staging_to_live() {
	// Don't do anything here unless the user has submitted a deploy_staging_to_live request.
	if ( ! wpe_param( 'wpe-common-deploy-staging-to-live' ) ) {
		return;
	}

	if ( ! is_user_logged_in() ) {
		wp_die( esc_attr( __( 'Must be an autheticated user', 'wpe-common' ) ) );
	}

	if ( ! current_user_can( 'administrator' ) ) {
		wp_die( esc_attr( __( 'Must be an administrator', 'wpe-common' ) ) );
	}

	if ( ! defined( 'PWP_NAME' ) || ! defined( 'WPE_APIKEY' ) ) {
		wp_die( esc_attr( __( 'This process could not be started.', 'wpe-common' ) ) );
	}

	check_admin_referer( PWP_NAME . '-config' );

	require_once WPE_PLUGIN_DIR . '/class-wpeapi.php';

	$db_mode = isset( $_REQUEST['db_mode'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['db_mode'] ) ) : 'default';
	$email   = isset( $_REQUEST['email'] ) ? sanitize_email( wp_unslash( $_REQUEST['email'] ) ) : get_option( 'admin_email' );

	if ( isset( $_REQUEST['tables'] ) ) {
		$tables = array_map( 'sanitize_text_field', wp_unslash( $_REQUEST['tables'] ) );
	} else {
		$tables = false;
	}

	$api = new \WPE_API();
	$api->set_arg( 'method', 'deploy-from-staging' );
	$api->set_arg( 'db_mode', esc_attr( $db_mode ) );
	$api->set_arg( 'email', esc_attr( $email ) );

	if ( $tables ) {
		$api->set_arg( 'tables', implode( '&', $tables ) );
	}
	$api_domain = wpe_el( $GLOBALS, 'api-domain', 'https://api.wpengine.com' );
	$api_domain = str_replace( 'https://', '', $api_domain );
	$api->set_arg( 'headers', "Host:{$api_domain}" );
	$api->post();

	if ( ! $api->is_error() ) {
		// Translators: The email address where the deploy notification will be sent.
		render_admin_notice( 'info', sprintf( __( 'Your staging site is being deployed. You will receive an email at %s once it has been completed.', 'wpe-common' ), $email ) );
	} else {
		render_admin_notice( 'error', $api->is_error() );
	}
}
add_action( 'wpe_common_admin_notices', __NAMESPACE__ . '\deploy_staging_to_live' );

/**
 * Check if a deployment from staging to live has happened, and show a notice to the user if so.
 */
function maybe_notify_user_about_staging_to_live_deployment() {
	// If a deployment is underway.
	$status_file = ABSPATH . '/wpe-deploy-status-' . PWP_NAME;

	// Stop here if there's no status file.
	if ( ! file_exists( $status_file ) ) {
		return;
	}

	// Check status and either delete the status file if it is more than five minutes old, else post a nag message.
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$status = file_get_contents( $status_file );
	$time   = filemtime( $status_file );

	if ( ! strstr( $status, 'Deploy Completed' ) ) {
		return;
	}

	if ( function_exists( 'wp_timezone' ) ) {
		$dt = new \DateTime();
		$dt->setTimezone( wp_timezone() );
		$dt->setTimestamp( $time );
		$rendered_date = $dt->format( 'Y-m-d g:ia (T)' );
	} else {
		$rendered_date = gmdate( 'Y-m-d g:ia (T)', $time );
	}

	$message = sprintf(
		// Translators: 1. The date when a deployment last took place. 2. The HTML for an opening anchor tag. 3. The closing HTML for the anchor tag.
		__( 'A deployment from STAGING to LIVE was completed for this site on %1$s. If you need to revert to previous state you can do so via the WP Engine %2$sUser Portal%3$s.', 'wpe-common' ),
		$rendered_date,
		'<a href="https://my.wpengine.com" target="_blank" rel="noopener noreferrer">',
		'</a>'
	);

	?>
	<div class="notice wpe-default inline">
		<p><b>
			<?php
				echo esc_html( __( 'Successful Deployment', 'wpe-common' ) );
			?>
		</b></p>
		<p><?php echo wp_kses_post( $message ); ?></p>
	</div>
	<?php
}
add_action( 'wpe_common_admin_notices', __NAMESPACE__ . '\maybe_notify_user_about_staging_to_live_deployment' );

/**
 * This function handles legacy staging requests and displaying the corresponding notices to the user.
 *
 * This function does way too much. It should be broken down, but requires better responses from WpeCommon.
 * Specifically, the get_staging_status needs to provide a proper response about the status of a staging site,
 * especially in regards to whether a staging site is being built right now.
 *
 * The logic in this function was mostly taken unmodified from the old admin-ui.php file during the redesign process.
 * Changes made are only to accomodate the new admin notice functionality in the new design.
 */
function handle_staging_requests_and_notices() {
	$wpe_common     = \WpeCommon::instance();
	$snapshot_state = $wpe_common->get_staging_status();

	// Process snapshot -> staging.
	$just_started_snapshot = false;

	if ( wpe_param( 'snapshot' ) ) {
		check_admin_referer( PWP_NAME . '-config' );

		// Can't run one if one is already running.
		$snapshot_state = $wpe_common->get_staging_status();
		if ( $snapshot_state['have_snapshot'] && ! $snapshot_state['is_ready'] ) {
			add_action( 'wpe_common_admin_notices', __NAMESPACE__ . '\staging_snapshot_error_notice' );
		} else {
			$snapshot_result = $wpe_common->snapshot_to_staging();

			// This is definitely not an ideal check, but it's working with the way the "snapshot_to_staging" function works.
			if ( 'Recreating the staging area failed!  Please contact support for assistance.' !== $snapshot_result ) {
				$wpe_common->snapshot_to_staging();
				$just_started_snapshot = true;
				add_action( 'wpe_common_admin_notices', __NAMESPACE__ . '\notice_snapshot_in_progress' );

				// Because WpeCommon::get_staging_status doesn't return accurate results for in-progress staging calls, we have to hide the deploy section this way.
				// This makes sure it stays hidden directly after a new deploy is initiated, since $just_started_snapshot only exists here, and not in the WpeCommon::get_staging_status method.
				?>
				<style type="text/css">
					#wpe-common-deploy-staging-to-live-section {
						display:none;
					}
				</style>
				<?php

			} else {
				?>
				<div class="notice wpe-error is-dismissible inline">
					<p>
					<?php
						echo esc_attr( __( 'Recreating the staging area failed!  Please contact support for assistance.', 'wpe-common' ) );
					?>
					</p>
				</div>
				<?php
			}
		}
	}

	// If a snapshot was just initiated, override the status and the is_ready values. This is where better logic needs to come from WpeCommon::get_staging_status.
	if ( $just_started_snapshot && $snapshot_state['have_snapshot'] ) {
		$snapshot_state['status']   = 'Starting the staging snapshot process...';
		$snapshot_state['is_ready'] = false;
	}

	if ( isset( $snapshot_state['is_ready'] ) && $snapshot_state['is_ready'] ) {
		$message_type = 'success';
	} else {
		$message_type = 'info';
	}

	// If a staging snapshot exists.
	if ( $snapshot_state['have_snapshot'] ) {
		?>
		<div class="notice wpe-<?php echo esc_attr( $message_type ); ?> inline">
			<p><b>
			<?php
				echo esc_html( __( 'Staging Status:', 'wpe-common' ) . ' ' . $snapshot_state['status'] );
			?>
			</b></p>
			<?php
			if ( $snapshot_state['is_ready'] ) {
				?>
				<p>
				<?php
				if ( function_exists( 'wp_timezone' ) ) {
					$dt = new \DateTime();
					$dt->setTimezone( wp_timezone() );
					$dt->setTimestamp( $snapshot_state['last_update'] );
					$rendered_date = $dt->format( 'Y-m-d g:ia (T)' );
				} else {
					$rendered_date = gmdate( 'Y-m-d g:ia (T)', $snapshot_state['last_update'] );
				}

				echo wp_kses_post(
					sprintf(
						// Translators: the opening and closing for a bold tag.
						__( 'Last staging snapshot was taken on %1$s. Access it here:', 'wpe-common' ),
						'<span class="wpe_last_updated_date">' . $rendered_date . '</span>'
					)
				);

				?>
				<a target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( $snapshot_state['staging_url'] ); ?>"><b><?php echo esc_url( $snapshot_state['staging_url'] ); ?></b></a>
			<?php } else { ?>
				<p>
				<?php
					echo esc_html( __( 'Please wait while the staging area continues to be deployed.  It can take a while!  You can refresh this page to check on its progress.', 'wpe-common' ) );
			}
			?>
			</p>
		</div>
		<?php
	}
}

/**
 * Add an admin notice to the WP Engine settings page in wp-admin.
 *
 * @param string $type The type of error. One of the following: 'default', 'error', 'info', 'success', 'warning'.
 * @param string $message The message to be shown to the user.
 */
function render_admin_notice( string $type, string $message ) {

	// Make sure the supplied message type exists.
	if ( ! in_array( $type, array( 'default', 'error', 'info', 'success', 'warning' ), true ) ) {
		return;
	}

	$type = 'wpe-' . $type;

	// Translators: 1. The CSS class name representing the type of notification this is. 2. The message shown to the user.
	echo \sprintf(
		'<div class="notice %1$s is-dismissible inline"><p>%2$s</p></div>',
		esc_attr( $type ),
		wp_kses_post( $message )
	);
}

/**
 * Render an admin notice that tells the user a staging snapshot is already in progress.
 */
function staging_snapshot_error_notice() {
	?>
	<div class="notice wpe-error is-dismissible inline">
		<p><?php echo esc_html( __( 'A staging snapshot is already in progress. Please wait for the current staging process to complete, then you can either use the staging area or you can then request another snapshot.', 'wpe-common' ) ); ?></p>
	</div>
	<?php
}

/**
 * Render an admin notice that tells the user their staging site is being built.
 */
function notice_snapshot_in_progress() {
	?>
	<div class="notice wpe-info is-dismissible inline">
		<p>
		<?php
		echo wp_kses_post(
			sprintf(
				// Translators: the opening and closing for a bold tag.
				__( 'Your staging site is being built in the background.  %1$sIt can take a long time%2$s, especially for large sites.', 'wpe-common' ),
				'<b>',
				'</b>'
			)
		);
		?>
		</p>
	</div>
	<?php
}
add_action( 'wpe_common_admin_notices', __NAMESPACE__ . '\handle_staging_requests_and_notices' );
