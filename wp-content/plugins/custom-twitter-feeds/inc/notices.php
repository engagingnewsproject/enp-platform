<?php
function ctf_get_current_time() {
	$current_time = time();

	// where to do tests
	//$current_time = strtotime( 'November 25, 2020' ) + 1;

	return $current_time;
}

// generates the html for the admin notices
function ctf_notices_html() {
		//delete_option( 'ctf_rating_notice');
		//delete_transient( 'instagram_feed_rating_notice_waiting' );
}
//add_action( 'admin_notices', 'ctf_notices_html', 12 ); // priority 8 for Instagram, priority 10 for Facebook

function ctf_get_future_date( $month, $year, $week, $day, $direction ) {
	if ( $direction > 0 ) {
		$startday = 1;
	} else {
		$startday = date( 't', mktime(0, 0, 0, $month, 1, $year ) );
	}

	$start = mktime( 0, 0, 0, $month, $startday, $year );
	$weekday = date( 'N', $start );

	$offset = 0;
	if ( $direction * $day >= $direction * $weekday ) {
		$offset = -$direction * 7;
	}

	$offset += $direction * ($week * 7) + ($day - $weekday);
	return mktime( 0, 0, 0, $month, $startday + $offset, $year );
}


function ctf_admin_database_warning() {
	if ( isset( $_GET['page'] ) && in_array( $_GET['page'], array( 'custom-twitter-feeds', '' ) ) ) {


		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . "options";
		$result = $wpdb->get_var("
        SELECT COUNT(*)
        FROM $table_name
        WHERE option_name LIKE '%ctf_!%'
        ");

		if ( (int) $result < 500 ) {
			return;
		}
		?>
		<div class="notice notice-warning is-dismissible ctf-admin-notice">
			<p>
				<?php echo esc_html__( 'Heads up! It looks like you have over 500 Twitter feeds stored in your WordPress database. This is typically caused by a large number of hashtag feeds on your site, as the plugin permanently stores older Tweets to work around Twitter\'s 7 day hashtag feed limit. This many caches may lead to performance issues.', 'custom-twitter-feeds' ); ?>
			</p>
			<p>
				<?php echo sprintf( __( 'For a solution, please follow the directions %shere%s.', 'custom-twitter-feeds' ), '<a href="https://smashballoon.com/why-does-my-database-have-a-lot-of-twitter-feed-caches/" target="_blank" rel="noopener noreferrer">', '</a>' ); ?>
			</p>
		</div>
		<?php
	}
}
add_action( 'admin_notices', 'ctf_admin_database_warning' );

/* Usage */
add_action( 'admin_notices', 'ctf_usage_opt_in' );
function ctf_usage_opt_in() {

	if ( isset( $_GET['trackingdismiss'] ) ) {
		$usage_tracking = get_option( 'ctf_usage_tracking', array( 'last_send' => 0, 'enabled' => false ) );

		$usage_tracking['enabled'] = false;

		update_option( 'ctf_usage_tracking', $usage_tracking, false );

		return;
	}

	$cap = 'manage_options';

	$cap = apply_filters( 'ctf_settings_pages_capability', $cap );
	if ( ! current_user_can( $cap ) ) {
		return;
	}
	$usage_tracking = get_option( 'ctf_usage_tracking', false );
	if ( $usage_tracking ) {
		return;
	}
	?>
    <div class="notice notice-warning is-dismissible ctf-admin-notice">

        <p>
            <strong><?php echo __( 'Help us improve the Custom Twitter Feed plugin', 'custom-twitter-feeds' ); ?></strong><br>
			<?php echo __( 'Understanding how you are using the plugin allows us to further improve it. Opt-in below to agree to send a weekly report of plugin usage data.', 'custom-twitter-feeds' ); ?>
            <a target="_blank" rel="noopener noreferrer" href="https://smashballoon.com/custom-twitter-feeds/docs/usage-tracking/"><?php echo __( 'More information', 'custom-twitter-feeds' ); ?></a>
        </p>
        <p>
            <a href="<?php echo admin_url('admin.php?page=custom-twitter-feeds&trackingdismiss=1') ?>" type="submit" class="button button-primary ctf-opt-in"><?php echo __( 'Yes, I\'d like to help', 'custom-twitter-feeds' ); ?></a>
            <a href="<?php echo admin_url('admin.php?page=custom-twitter-feeds&trackingdismiss=1') ?>" type="submit" class="ctf-no-usage-opt-out ctf-space-left button button-secondary"><?php echo __( 'No, thanks', 'custom-twitter-feeds' ); ?></a>
        </p>

    </div>

	<?php
}

function ctf_usage_opt_in_or_out() {
	if ( ! current_user_can( 'manage_custom_twitter_feeds_options' ) ) {
		wp_send_json_error();
	}
	if ( ! isset( $_POST['opted_in'] ) ) {
		die ( 'You did not do this the right way!' );
	}

	$usage_tracking = get_option( 'ctf_usage_tracking', array( 'last_send' => 0, 'enabled' => false ) );

	$usage_tracking['enabled'] = isset( $_POST['opted_in'] ) ? $_POST['opted_in'] === 'true' : false;

	update_option( 'ctf_usage_tracking', $usage_tracking, false );

	die();
}
add_action( 'wp_ajax_ctf_usage_opt_in_or_out', 'ctf_usage_opt_in_or_out' );
