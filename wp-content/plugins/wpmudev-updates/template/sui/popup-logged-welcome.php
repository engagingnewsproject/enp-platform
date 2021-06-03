<?php
$project_names = array();
$message       = sprintf( esc_html__( "You’re now connected to The Hub. Pro features of plugins have been enabled. You can manage this sites updates and services in %s The Hub %s.", 'wpmudev' ), '<a href="' . esc_url( $urls->hub_url ) . '">', '</a>' );

if ( isset( $_GET['updated-plugins'] ) && ! empty( $_GET['updated-plugins'] ) && 'full' === $type ) {
	$plugins = explode( ',', $_GET['updated-plugins'] );
	foreach ( $plugins as $plugin ) {
		$project         = WPMUDEV_Dashboard::$site->get_project_info( $plugin );
		$project_names[] = str_replace( 'Pro', '', $project->name );
	}

	$message = sprintf( esc_html__( "You’re now connected to The Hub. Pro versions of %s have been enabled. You can manage this sites updates and services in %s The Hub %s.", 'wpmudev' ), preg_replace( '/,([^,]*)$/', ' and \1', implode( ', ', $project_names ) ), '<a href="' . esc_url( $urls->hub_url ) . '">', '</a>' );
}

if ( 'single' === $type ) {
	$url_upgrade = $urls->remote_site . 'hub/account/';

	$url_upgrade = add_query_arg(
		array(
			'utm_source'   => 'wpmudev-dashboard',
			'utm_medium'   => 'plugin',
			'utm_campaign' => 'dashboard_expired_modal_reactivate',
		),
		$url_upgrade
	);

	$project_name = '';
	if ( is_array( $licensed_projects ) ) {
		$project_name = $licensed_projects[0]->name;
	} else {
		$project_name = $licensed_projects->name;
	}

	$message = sprintf( esc_html__( "You’re now connected to The Hub and have unlocked %s for this website. %s Upgrade %s at any time to install more pro plugins or connect more sites", 'wpmudev' ), $project_name, '<a href="' . esc_url( $url_upgrade ) . '" style="color:#8D00B1">', '</a>' );
}
?>
<div class="sui-modal sui-modal-sm">

	<div
		role="dialog"
		id="logged-welcome-modal"
		class="sui-modal-content"
		aria-modal="true"
		data-modal-mask="true"
		aria-labelledby="modal-title-unique-id"
		aria-describedby="modal-description-unique-id"
	>

		<div class="sui-box">
			<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">
				<figure class="sui-box-logo dash-welcome-modal-logo" aria-hidden="true">
					<i class="sui-icon-plug-connected sui-lg" aria-hidden="true"></i>
				</figure>

				<button class="sui-button-icon sui-button-float--right" data-modal-close="">
					<i class="sui-icon-close sui-md" aria-hidden="true"></i>
					<span class="sui-screen-reader-text"><?php _e( 'Close this dialog.', 'wpmudev' ); ?></span>
				</button>

				<h3 id="demo-dialog--sample-dismiss-notice-title" class="sui-box-title sui-lg"><?php esc_html_e( 'Connected!', 'wpmudev' ); ?></h3>

				<p id="demo-dialog--sample-dismiss-notice-desc" class="sui-description"><?php echo $message; ?></p>

			</div>

			<div class="sui-box-footer sui-flatten sui-content-center">
				<button class="sui-button" data-modal-close=""><?php esc_html_e( 'Okay, got it!', 'wpmudev' ); ?> </button>
			</div>

		</div>

	</div>

</div>