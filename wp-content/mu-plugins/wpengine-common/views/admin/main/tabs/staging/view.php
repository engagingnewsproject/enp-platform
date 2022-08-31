<?php
/**
 * Admin UI - Staging Tab
 * Adds the WP Engine Admin "Staging" tab.
 *
 * @package wpengine/common-mu-plugin
 */

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

// Check user capabilities.
if ( ! current_user_can( 'manage_options' ) ) {
	return;
}

$wpe_common     = \WpeCommon::instance();
$snapshot_state = $wpe_common->get_staging_status();

?>

<!-- WordPress settings tab -->
<div class="notice wpe-warning inline">
		<p>Legacy staging will reach End-of-Life and no longer be available on WP Engine by March 2022. You will now be able to test any new site changes with the <a href="https://wpengine.com/support/copy-site/" target="_blank" rel="noopener noreferrer">Copy Environment</a> feature in the WP Engine User Portal.</p>
</div>
<div class="wpe-common-plugin-container">
	<h2>What is a Staging Area?</h2>
	<p>This takes a snapshot of your site and copies it to a "<strong>staging area</strong>" where you can test changes without it affecting your <strong>live site</strong>. There's only one staging area, so every time you click this button the old staging area is lost forever, replaced with a snapshot of your live site. Both your live and staging areas are backed up daily. You can use backup points to create and restore your <a href="https://my.wpengine.com/installs/<?php echo esc_attr( esc_attr( $site_info->name ) ); ?>/backup_points" target="_blank" rel="noopener noreferrer">live site</a> and <a href="https://my.wpengine.com/installs/<?php echo esc_attr( $site_info->name ); ?>/backup_points#staging" target="_blank" rel="noopener noreferrer">staging area</a> anytime via the <a href="https://my.wpengine.com/" target="_blank" rel="noopener noreferrer">WP Engine User Portal</a>.</p>

	<p>Please note: if you want to access your staging site via SFTP, there is a different username required.  You can manage your SFTP users in your <a href="https://my.wpengine.com/" target="_blank" rel="noopener noreferrer">User Portal</a>.</p>
	<div class="wpe-admin-button-controls">
		<form id="staging" method="post" name="options" action="">
			<?php wp_nonce_field( PWP_NAME . '-config' ); ?>
			<button
				type="submit"
				name="snapshot"
				value="Create staging area"
				class="wpe-admin-button-primary"
				onclick="return confirm('This will overwrite your STAGING site with your LIVE site. Are you sure you want to do this?');"
			>Copy site from <span class="wpe-caps">Live</span> to <span class="wpe-caps">Staging</span></button>
		</form>
	</div>
</div>
<?php
// If there's a staging site that exists, and the current user has permission to deploy to live from staging, show the form for deploying.
if ( $snapshot_state['is_ready'] && current_user_can( 'administrator' ) ) {
	?>
	<div id="wpe-common-deploy-staging-to-live-section" class="wpe-common-plugin-container">
		<h2>Deploy STAGING to LIVE</h2>
			<form class="form" action="" name="deploy_staging_to_live" method="post">
				<p>By default only your files will be copied back to <b>LIVE</b>. You can choose to move content by checking the tables you would like to move below. Keep in mind these tables will replace the <b>LIVE</b> version with the <b>STAGING</b> version. So for instance if you choose to move <b>wp_posts</b> all posts added to the <b>LIVE</b> site since the staging site was created will be removed. However, a checkpoint of your site will be created so you can 'undo' the changes if necessary.</p>
				<?php
					// Tables.
					global $wpdb;

					// Allow a direct database call here to make sure it's fresh and non cached.
					$tables = $wpdb->get_col( 'SHOW TABLES;' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->flush();
				?>
				<div class="wpe-common-select-panel">
					<label for="wpe-common-staging-db-mode-select">Database Mode</label>
					<select id="wpe-common-staging-db-mode-select" name="db_mode">
						<option value="none">Move No Tables</option>
						<option value="default">Move All Tables</option>
						<option value="tables">Select Tables to Move</option>
					</select>
				</div>

				<div id="wpe-common-staging-table-select" class="wpe-common-select-panel wpe-panel-align-top" style="display:none;">
					<label for="select_tables">Select Tables</label>
					<div class="wpe-common-select-tables">
						<div class="wpe-select-table-links">
							<a id="wpe-add-all-tables">Add all tables</a> | <a id="wpe-remove-all-tables">Remove all tables</a>
						</div>
						<select name="tables[]" multiple size="12">
							<?php foreach ( $tables as $table ) { ?>
								<option value="<?php echo esc_attr( $table ); ?>" <?php echo 'wp_options' === $table ? 'selected' : ''; ?>><?php echo esc_attr( $table ); ?></option>
							<?php } ?>
						</select>
					</div>
				</div>

				<div class="wpe-common-select-panel">
					<label for="staging-email">Email to Notify</label>
					<input id="staging-email" type="email" name="email" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" value="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>"/>
				</div>

				<?php
				$production_version = get_bloginfo( 'version' );
				$staging_version    = $snapshot_state['version'];
				$can_push_staging   = is_staging_gte( $production_version, $snapshot_state['version'] );
				?>

				<?php if ( $can_push_staging ) { ?>
					<div class="form-actions">
						<?php wp_nonce_field( PWP_NAME . '-config' ); ?>
						<div class="wpe-admin-button-controls">
							<button
								id="submit-deploy"
								name="wpe-common-deploy-staging-to-live"
								value="Submit"
								class="wpe-admin-button-primary"
								onclick="return confirm('This will overwrite your LIVE site with your STAGING site. Are you sure you want to do this?');"
							>Deploy STAGING to LIVE</button>
				</div>
					</div>
				<?php } else { ?>
					<div class="wpe-error">
						<h3>Your Staging Site is Running an Old Version of WordPress (<?php echo esc_attr( $snapshot_state['version'] ); ?>)</h3>
						<p>Your staging site is running an old version of WordPress, WordPress <?php echo esc_attr( $snapshot_state['version'] ); ?>.
						Before you can deploy your staging site to your live site, you need to
						<a target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( $snapshot_state['staging_url'] ); ?>/wp-admin/update-core.php">update WordPress</a>
						to match your live version, <?php echo esc_attr( $production_version ); ?>. Please follow the steps to update WordPress.
						We recommend you also create a <a target="_blank" rel="noopener noreferrer" href="https://my.wpengine.com/installs/<?php echo esc_attr( PWP_NAME ); ?>/backup_points">backup point</a> before updating.</p>
					</div>
				<?php } ?>
			</form>
	</div>

	<script type="text/javascript">
		jQuery(function($) {
			// Show/hide the select field that allows the user to choose the tables they want to deploy.
			$('#wpe-common-staging-db-mode-select').change(function() {
				if( $(this).attr('name') == 'db_mode' && $(this).find('option:selected').val() == 'tables') {
					$('#wpe-common-staging-table-select').slideDown();
				} else {
					$('#wpe-common-staging-table-select').slideUp();
				}
			});

			// Select all tables when the user clicks "Add all tables".
			$('#wpe-add-all-tables').on('click', function() {
				$("[name='tables[]'] option").prop('selected', true);
			});
			// Clear all tables selected when the user clicks "Remove all tables".
			$('#wpe-remove-all-tables').on('click', function() {
				$("[name='tables[]'] option").prop('selected', false);
			});
		});
	</script>
	<?php
}
