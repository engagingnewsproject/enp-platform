<?php

use TwitterFeed\CTF_Feed;
use TwitterFeed\CtfFeed;
use TwitterFeed\CTF_Settings;
use TwitterFeed\CTF_GDPR_Integrations;
/**
 * May include support for templates in theme folders in the future
 *
 * @return string full path to template
 *
 * @since 2.0 custom templates supported
 */
function ctf_get_feed_template_part( $part, $settings = array() ) {
	$options = get_option( 'ctf_options', array() );
	$file = '';
	$settings['customtemplates'] = isset( $options['customtemplates'] ) ? $options['customtemplates'] !== 'false' : false;

	$using_custom_templates_in_theme = apply_filters( 'ctf_use_theme_templates', $settings['customtemplates'] );
	$generic_path = trailingslashit( CTF_PLUGIN_DIR ) . 'templates/';

	if ( $using_custom_templates_in_theme == true ) {
		$custom_header_template 			= locate_template( 'ctf/header.php', false, false );
		$custom_header_generic_template 	= locate_template( 'ctf/header-generic.php', false, false );
		$custom_header_text_template    	= locate_template( 'ctf/header-text.php', false, false );
		$custom_item_template 				= locate_template( 'ctf/item.php', false, false );
		$custom_footer_template 			= locate_template( 'ctf/footer.php', false, false );
		$custom_feed_template 				= locate_template( 'ctf/feed.php', false, false );
		$custom_author_template 			= locate_template( 'ctf/author.php', false, false );
		$custom_linkbox_template 			= locate_template( 'ctf/linkbox.php', false, false );
		$custom_actions_template 			= locate_template( 'ctf/actions.php', false, false );
	} else {
		$custom_header_template         = false;
		$custom_header_generic_template = false;
		$custom_item_template           = false;
		$custom_footer_template         = false;
		$custom_feed_template           = false;
		$custom_author_template         = false;
		$custom_linkbox_template        = false;
		$custom_actions_template        = false;
	}

	if ( $part === 'header' ) {
		if ( $custom_header_template ) {
			$file = $custom_header_template;
		} else {
			$file = $generic_path . 'header.php';
		}
	} elseif ( $part === 'header-generic' ) {
		if ( $custom_header_generic_template ) {
			$file = $custom_header_generic_template;
		} else {
			$file = $generic_path . 'header-generic.php';
		}
	} if ( $part === 'header-text' ) {
		if ( $custom_header_generic_template ) {
			$file = $custom_header_text_template;
		} else {
			$file = $generic_path . 'header-text.php';
		}
	} elseif ( $part === 'item' ) {
		if ( $custom_item_template ) {
			$file = $custom_item_template;
		} else {
			$file = $generic_path . 'item.php';
		}
	} elseif ( $part === 'footer' ) {
		if ( $custom_footer_template ) {
			$file = $custom_footer_template;
		} else {
			$file = $generic_path . 'footer.php';
		}
	} elseif ( $part === 'feed' ) {
		if ( $custom_feed_template ) {
			$file = $custom_feed_template;
		} else {
			$file = $generic_path . 'feed.php';
		}
	} elseif ( $part === 'author' ) {
		if ( $custom_author_template ) {
			$file = $custom_author_template;
		} else {
			$file = $generic_path . 'author.php';
		}
	} elseif ( $part === 'linkbox' ) {
		if ( $custom_linkbox_template ) {
			$file = $custom_linkbox_template;
		} else {
			$file = $generic_path . 'linkbox.php';
		}
	} elseif ( $part === 'actions' ) {
		if ( $custom_actions_template ) {
			$file = $custom_actions_template;
		} else {
			$file = $generic_path . 'actions.php';
		}
	}

	return $file;
}

/**
 * Check if it's Customizer
 *
 * @since 2.0
*/
function ctf_doing_customizer( $settings ) {
	return !empty( $settings['customizer'] ) && $settings['customizer'] == true;
}

/**
 * @return int
 */
function ctf_get_utc_offset() {
	return get_option( 'gmt_offset', 0 ) * HOUR_IN_SECONDS;
}


function ctf_current_user_can( $cap ) {
	if ( current_user_can( 'manage_twitter_feed_options' ) && 'manage_twitter_feed_options' === $cap ) {
		$cap = 'manage_twitter_feed_options';
	} elseif ( current_user_can( 'manage_custom_twitter_feeds_options' ) && 'manage_custom_twitter_feeds_options' === $cap ) {
		$cap = 'manage_custom_twitter_feeds_options';
	}

	$cap = apply_filters( 'ctf_settings_pages_capability', $cap );

	return current_user_can( $cap );
}

function ctf_get_manage_options_cap() {
	$cap = 'manage_options';
	if ( current_user_can( 'manage_twitter_feed_options' ) ) {
		$cap = 'manage_twitter_feed_options';
	} elseif ( current_user_can( 'manage_custom_twitter_feeds_options' ) ) {
		$cap = 'manage_custom_twitter_feeds_options';
	}
	$cap = apply_filters( 'ctf_settings_pages_capability', $cap );

	return $cap;
}


function ctf_clear_cache_sql() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'options';
	$result     = $wpdb->query(
		"
		DELETE
		FROM $table_name
		WHERE `option_name` LIKE ('%\_transient\_ctf\_%')
		"
	);
	$wpdb->query(
		"
		DELETE
		FROM $table_name
		WHERE `option_name` LIKE ('%\_transient\_timeout\_ctf\_%')
		"
	);
}



function ctf_background_processing() {

	if ( ! isset( $_POST['feed_id'] ) ) {
		return;
	}
	$feed_id = sanitize_text_field( $_POST['feed_id'] );

	$atts_raw = isset( $_POST['atts'] ) ? json_decode( stripslashes( $_POST['atts'] ), true ) : array();
	if ( is_array( $atts_raw ) ) {
		array_map( 'sanitize_text_field', $atts_raw );
	} else {
		$atts_raw = array();
	}
	$atts = $atts_raw; // now sanitized

	$location     = isset( $_POST['location'] ) && in_array( $_POST['location'], array( 'header', 'footer', 'sidebar', 'content' ), true ) ? sanitize_text_field( $_POST['location'] ) : 'unknown';
	$post_id      = isset( $_POST['post_id'] ) && $_POST['post_id'] !== 'unknown' ? (int) $_POST['post_id'] : 'unknown';
	$feed_details = array(
		'feed_id'  => $feed_id,
		'atts'     => $atts,
		'location' => array(
			'post_id' => $post_id,
			'html'    => $location,
		),
	);

	ctf_do_background_tasks( $feed_details );



	//$twitter_feed_settings = new CTF_Settings_Pro( $atts );
	//$twitter_feed_settings->set_feed_type_and_terms();
	//$tw_settings = $twitter_feed_settings->get_settings();
	$twitter_feed_object = CtfFeed::init( $atts, '', 0, array(), 0 );
	$tw_settings = $twitter_feed_object->feed_options;

	$twitter_feed = new CTF_Feed( $feed_id );
	$twitter_feed->set_cache( $tw_settings['cache_time'], $tw_settings );
	$posts = array();
	if ( $twitter_feed->regular_cache_exists() ) {
		$twitter_feed->set_post_data_from_cache();
		$posts = $twitter_feed->get_post_data();
	}

	$twitter_return['resizing'] = 'success';

	echo wp_json_encode( $twitter_return );

	wp_die();
}
add_action( 'wp_ajax_ctf_background_processing', 'ctf_background_processing' );
add_action( 'wp_ajax_nopriv_ctf_background_processing', 'ctf_background_processing' );



/**
 * Triggered by a cron event to update feeds
 *
 * @since 2.0
 */
function ctf_cron_updater() {
	$settings = ctf_get_database_settings();
	if ( ! empty( $settings['ctf_caching_type'] ) && $settings['ctf_caching_type'] === 'page' ) {
		return;
	}

	$cron_updater = new TwitterFeed\SB_Twitter_Cron_Updater();
	$cron_updater->do_feed_updates();
	ctf_do_background_tasks( array() );
}
add_action( 'ctf_feed_update', 'ctf_cron_updater' );


function ctf_maybe_ajax_theme_html( $twitter_feed, $feed_id ) {
	$options = ctf_get_database_settings();
	if ( $options['ajax_theme'] ) {
		echo TwitterFeed\CTF_Display_Elements::get_ajax_code( $options );
	}
}
add_action( 'ctf_before_feed_end', 'ctf_maybe_ajax_theme_html', 10, 2 );

/**
 * Debug report added at the end of the feed when sbi_debug query arg is added to a page
 * that has the feed on it.
 *
 * @param object $twitter_feed
 * @param string $feed_id
 */
function ctf_debug_report( $twitter_feed, $feed_id ) {

	if ( ! isset( $_GET['sbi_debug'] ) && ! isset( $_GET['sb_debug'] ) ) {
		return;
	}

	$settings_obj = new CTF_Settings( array(), array() );

	$settings = $twitter_feed->feed_options;

	$public_settings_keys = CTF_Settings::get_public_db_settings_keys();
	?>

	<p>Status</p>
	<ul>
		<li>Time: <?php echo esc_html( date( 'Y-m-d H:i:s', time() ) ); ?></li>

	</ul>
	<p>Settings</p>
	<ul>
		<?php
		foreach ( $public_settings_keys as $key ) :
			if ( isset( $settings[ $key ] ) ) :
				?>
				<li>
					<small><?php echo esc_html( $key ); ?>:</small>
					<?php
					if ( ! is_array( $settings[ $key ] ) ) :
						echo esc_html( $settings[ $key ] );
					else :
						?>
						<ul>
							<?php
							foreach ( $settings[ $key ] as $sub_key => $value ) {
								echo '<li><small>' . esc_html( $sub_key ) . ':</small> ' . esc_html( $value ) . '</li>';
							}
							?>
						</ul>
					<?php endif; ?>
				</li>

			<?php
			endif;
		endforeach;
		?>
	</ul>
	<p>GDPR</p>
	<ul>
		<?php
		$statuses = CTF_GDPR_Integrations::statuses();
		foreach ( $statuses as $status_key => $value ) :
			?>
			<li>
				<small><?php echo esc_html( $status_key ); ?>:</small>
				<?php
				if ( $value == 1 ) {
					echo 'success';
				} else {
					echo 'failed'; }
				?>
			</li>

		<?php endforeach; ?>
		<li>
			<small>Enabled:</small>
			<?php echo CTF_GDPR_Integrations::doing_gdpr( $settings ); ?>
		</li>
	</ul>
	<?php
}
add_action( 'ctf_before_feed_end', 'ctf_debug_report', 99, 2 );
