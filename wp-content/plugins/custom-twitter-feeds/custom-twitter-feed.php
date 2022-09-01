<?php
/*
Plugin Name: Custom Twitter Feeds
Plugin URI: http://smashballoon.com/custom-twitter-feeds
Description: Customizable Twitter feeds for your website
Version: 2.0.3
Author: Smash Balloon
Author URI: http://smashballoon.com/
Text Domain: custom-twitter-feeds
*/
/*
Copyright 2022 Smash Balloon LLC (email : hey@smashballoon.com)
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
define( 'CTF_URL', plugin_dir_path( __FILE__ )  );
define( 'CTF_VERSION', '2.0.3' );
define( 'CTF_TITLE', 'Custom Twitter Feeds' );
define( 'CTF_JS_URL', plugins_url( '/js/ctf-scripts.min.js?ver=' . CTF_VERSION , __FILE__ ) );
define( 'OAUTH_PROCESSOR_URL', 'https://api.smashballoon.com/twitter-login.php?return_uri=' );
define( 'CTF_PRODUCT_NAME', 'Custom Twitter Feeds' );
if ( ! defined( 'CTF_PLUGIN_NAME' ) ) {
	define( 'CTF_PLUGIN_NAME', 'Custom Twitter Feeds' );
}

// Plugin Folder Path.
if ( ! defined( 'CTF_PLUGIN_DIR' ) ) {
	define( 'CTF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
// Plugin Folder URL.
if ( ! defined( 'CTF_PLUGIN_URL' ) ) {
	define( 'CTF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
// Db version.
if ( ! defined( 'CTF_DBVERSION' ) ) {
	define( 'CTF_DBVERSION', '1.4' );
}
if ( ! defined( 'CTF_FEED_LOCATOR' ) ) {
	define( 'CTF_FEED_LOCATOR', 'ctf_feed_locator' );
}

if ( ! defined( 'CTF_BUILDER_DIR' ) ) {
	define( 'CTF_BUILDER_DIR', CTF_PLUGIN_DIR . 'admin/builder/' );
}
if ( ! defined( 'CTF_BUILDER_URL' ) ) {
	define( 'CTF_BUILDER_URL', CTF_PLUGIN_URL . 'admin/builder/' );
}
if ( ! defined( 'CTF_UPLOADS_NAME' ) ) {
	define( 'CTF_UPLOADS_NAME', 'sb-twitter-feed-images' );
}

if ( ! defined( 'CTF_PLUGIN_NAME' ) ) {
	define( 'CTF_PLUGIN_NAME', 'Custom Twitter Feed Free' );
}
if ( ! defined( 'CTF_FEEDS_POSTS_TABLE' ) ) {
	define( 'CTF_FEEDS_POSTS_TABLE', 'ctf_feeds_posts' );
}
if ( ! defined( 'CTF_POSTS_TABLE' ) ) {
	define( 'CTF_POSTS_TABLE', 'ctf_posts' );
}
if ( ! defined( 'CTF_LICENSE_URL' ) ) {
	define( 'CTF_LICENSE_URL', 'https://smashballoon.com/' );
}

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( version_compare( phpversion(), '5.6', '<' ) ) {
	if( !function_exists( 'ctf_check_php_notice' ) ){
		function ctf_check_php_notice(){ ?>
			<div class="notice notice-error">
				<div>
					<p><strong><?php echo esc_html__('Important:','custom-twitter-feeds') ?> </strong><?php echo esc_html__('Your website is using an outdated version of PHP. The Custom Twitter Feeds plugin requires PHP version 5.6 or higher and so has been temporarily deactivated.','custom-twitter-feeds') ?></p>

					<p>
						<?php
						echo esc_html__('To continue using the plugin','custom-twitter-feeds') . ', ';

						echo __('you can either manually reinstall the previous version of the plugin ','custom-twitter-feeds' );

						echo esc_html__('or contact your host to request that they upgrade your PHP version to 5.6 or higher.','custom-twitter-feeds');
						?>
					</p>
				</div>
			</div>
			<?php
		}
	}
	add_action( 'admin_notices', 'ctf_check_php_notice' );
	return; //Stop until PHP version is fixed
}

require_once( CTF_URL . '/inc/widget.php' );

require_once( CTF_URL . '/inc/admin-hooks.php' );

function ctf_plugin_init() {
	require_once trailingslashit( CTF_PLUGIN_DIR ) . 'inc/blocks/class-ctf-blocks.php';

	$ctf_blocks = new CTF_Blocks();

	if ( $ctf_blocks->allow_load() ) {
		$ctf_blocks->load();
	}

	require 			trailingslashit( CTF_PLUGIN_DIR ) . 'vendor/autoload.php';
	include_once 		trailingslashit( CTF_PLUGIN_DIR ) . '/inc/ctf-functions.php';

	if ( is_admin() ) {
		if ( version_compare( PHP_VERSION,  '5.3.0' ) >= 0
		     && version_compare( get_bloginfo('version'), '4.6' , '>' ) ) {
			$ctf_notifications = new TwitterFeed\Admin\CTF_Notifications();
			$ctf_notifications->init();

			$ctf_new_user = new TwitterFeed\Admin\CTF_New_User();
			$ctf_new_user->init();

			$ctf_global_settings	= new TwitterFeed\Admin\CTF_Global_Settings();
			$ctf_admin_notices		= new TwitterFeed\Admin\CTF_Admin_Notices();
			$ctf_tooltip_wizard		= new TwitterFeed\Builder\CTF_Tooltip_Wizard();
			$ctf_support			= new TwitterFeed\Admin\CTF_Support();
  			$ctf_admin_notices		= new TwitterFeed\Admin\CTF_Admin_Notices();
			$ctf_about_us			= new TwitterFeed\Admin\CTF_About_Us();


			require_once trailingslashit( CTF_PLUGIN_DIR ) . 'inc/Admin/addon-functions.php';
		}
	}
	require_once trailingslashit( CTF_PLUGIN_DIR ) . 'inc/Admin/CTF_Upgrader.php';
	$ctf_upgrader = new TwitterFeed\Admin\CTF_Upgrader();
	$ctf_upgrader->hooks();
}
add_action( 'plugins_loaded', 'ctf_plugin_init' );

include_once trailingslashit( CTF_PLUGIN_DIR ) . '/inc/Builder/CTF_Feed_Builder.php';


function ctf_update_settings() {
    $existing_deprecated_options = get_option( 'ctf_configure' );
    $existing_options = get_option( 'ctf_options' );

    update_option( 'ctf_version', CTF_VERSION );

    if ( ! empty( $existing_deprecated_options ) && empty( $existing_options ) ) {
        $merged_options = $existing_deprecated_options;
        $merged_options = array_merge( $merged_options, get_option( 'ctf_customize', array() ) );
        $merged_options = array_merge( $merged_options, get_option( 'ctf_style', array() ) );

        update_option( 'ctf_options', $merged_options );
    }
}

function ctf_check_for_db_updates() {

	$db_ver = get_option( 'ctf_db_version', 0 );

	if ( (float) $db_ver < 1.0 ) {
		global $wp_roles;
		$wp_roles->add_cap( 'administrator', 'manage_twitter_feed_options' );

		$ctf_statuses_option = get_option( 'ctf_statuses', array() );

		if ( ! isset( $ctf_statuses_option['first_install'] ) ) {

			$options_set = get_option( 'ctf_options', false );

			if ( $options_set ) {
				$ctf_statuses_option['first_install'] = 'from_update';
			} else {
				$ctf_statuses_option['first_install'] = time();
			}

			$ctf_rating_notice_option = get_option( 'ctf_rating_notice', false );

			if ( $ctf_rating_notice_option === 'dismissed' ) {
				$ctf_statuses_option['rating_notice_dismissed'] = time();
			}

			$ctf_rating_notice_waiting = get_transient( 'custom_twitter_feeds_rating_notice_waiting' );

			if ( $ctf_rating_notice_waiting === false
			     && $ctf_rating_notice_option === false ) {
				$time = 2 * WEEK_IN_SECONDS;
				set_transient( 'custom_twitter_feeds_rating_notice_waiting', 'waiting', $time );
				update_option( 'ctf_rating_notice', 'pending', false );
			}

			update_option( 'ctf_statuses', $ctf_statuses_option, false );

		}

		update_option( 'ctf_db_version', CTF_DBVERSION );
	}

	if ( version_compare( $db_ver, '1.0.1', '<' ) ) {
		TwitterFeed\CTF_Feed_Locator::create_table();
		update_option( 'ctf_db_version', CTF_DBVERSION );
	}

	// For v2.0
	if ( version_compare( $db_ver, '1.4', '<' ) ) {
		\TwitterFeed\Builder\CTF_Db::create_tables();
		$ctf_statuses_option = get_option( 'ctf_statuses', array() );
		$ctf_options         = get_option( 'ctf_options', array() );

		if ( ! isset( $ctf_statuses_option['first_install'] ) ) {

			$options_set = get_option( 'ctf_options', false );

			if ( $options_set ) {
				$ctf_statuses_option['first_install'] = 'from_update';
			} else {
				$ctf_statuses_option['first_install'] = time();
			}

			$ctf_rating_notice_option = get_option( 'ctf_rating_notice', false );

			if ( $ctf_rating_notice_option === 'dismissed' ) {
				$ctf_statuses_option['rating_notice_dismissed'] = time();
			}

			$ctf_rating_notice_waiting = get_transient( 'custom_twitter_feeds_rating_notice_waiting' );

			if ( $ctf_rating_notice_waiting === false
				&& $ctf_rating_notice_option === false ) {
				$time = 2 * WEEK_IN_SECONDS;
				set_transient( 'custom_twitter_feeds_rating_notice_waiting', 'waiting', $time );
				update_option( 'ctf_rating_notice', 'pending', false );
			}
		}

		//Legacy feeds
		$options_support_legacy = false;

		if ( isset( $ctf_options['access_token'] ) && ! empty( $ctf_options['access_token'] ) ) {
			$options_support_legacy = true;
			$base_settings = \TwitterFeed\CTF_Settings::legacy_shortcode_atts( array(), $ctf_options );
			update_option( 'ctf_legacy_feed_settings', ctf_json_encode( $base_settings ), false );
		}
		// how many legacy feeds?
		$args = array(
			'html_location' => array( 'header', 'footer', 'sidebar', 'content', 'unknown' ),
			'group_by'      => 'shortcode_atts',
			'page'          => 1,
		);

		$feeds_data                                      = \TwitterFeed\CTF_Feed_Locator::legacy_twitter_feed_locator_query( $args );
		$num_legacy                                      = count( $feeds_data );
		$ctf_statuses_option['support_legacy_shortcode'] = $options_support_legacy;
		if ( $num_legacy > 0 ) {
			if ( $num_legacy > 1 ) {
				$ctf_statuses_option['legacy_onboarding']        = array(
					'active' => true,
					'type'   => 'multiple',
				);
				$ctf_statuses_option['support_legacy_shortcode'] = true;
			} else {
				$ctf_statuses_option['legacy_onboarding'] = array(
					'active' => true,
					'type'   => 'single',
				);

				$shortcode_atts = ! empty( $feeds_data[0] ) && $feeds_data[0]['shortcode_atts'] != '[""]' ? json_decode( $feeds_data[0]['shortcode_atts'], true ) : array();
				$shortcode_atts = is_array( $shortcode_atts ) ? $shortcode_atts : array();

				$ctf_statuses_option['support_legacy_shortcode'] = $shortcode_atts;

				$shortcode_atts['from_update'] = true;

				$db = ctf_get_database_settings();
				$base_settings = \TwitterFeed\CTF_Settings::legacy_shortcode_atts( $shortcode_atts, $db );

				$feed_saver = new \TwitterFeed\Builder\CTF_Feed_Saver( false );
				$feed_saver->set_data( $base_settings );

				$feed_name = 'My Feed';
				$feed_saver->set_feed_name( $feed_name );

				$new_feed_id = $feed_saver->update_or_insert();

				$args = array(
					'new_feed_id'    => $new_feed_id,
					'legacy_feed_id' => $feeds_data[0]['feed_id'],
				);

				\TwitterFeed\CTF_Feed_Locator::update_legacy_to_builder( $args );
			}
		} elseif ( $num_legacy === 0 && $options_support_legacy ) {
			$ctf_statuses_option['support_legacy_shortcode'] = true;
		}

		update_option( 'ctf_statuses', $ctf_statuses_option, false );
		update_option( 'ctf_db_version', CTF_DBVERSION );

		if ( ! wp_next_scheduled( 'ctf_feed_update' ) ) {
			wp_schedule_event( time() + 60, 'twicedaily', 'ctf_feed_update' );
		}

		global $wp_roles;
		$wp_roles->add_cap( 'administrator', 'manage_twitter_feed_options' );

	}

}
add_action( 'wp_loaded', 'ctf_check_for_db_updates' );


/**
 * include the admin files only if in the admin area
 */
if ( is_admin() ) {

    $ctf_version = get_option( 'ctf_version', false );

    if ( ! $ctf_version ) {
        ctf_update_settings();
    }
    require_once( CTF_URL . '/inc/CtfAdmin.php' );
    require_once( CTF_URL . '/inc/notices.php' );

    $admin = new CtfAdmin;
}

/**
 * Generates the Twitter feed wherever the shortcode is placed
 *
 * @param $atts array shortcode arguments
 *
 * @return string
 */
function ctf_init( $atts, $preview_settings = false  ) {
	wp_enqueue_script( 'ctf_scripts' );
	$twitter_feed = TwitterFeed\CtfFeed::init( $atts, null, 0, array(), 1, $preview_settings);
	if ( isset( $twitter_feed->feed_options['feederror'] ) && ! empty( $twitter_feed->feed_options['feederror'] ) ) {
			return "<span id='ctf-no-id'>" . sprintf( __( 'No feed found with the ID %1$s. Go to the %2$sAll Feeds page%3$s and select an ID from an existing feed.', 'custom-twitter-feeds' ), esc_html( $twitter_feed->feed_options['feed'] ), '<a href="' . esc_url( admin_url( 'admin.php?page=ctf-feed-builder' ) ) . '">', '</a>' ) . '</span><br /><br />';
	} else {
		$options = ctf_get_database_settings();
		$feed_html = '';
	    // if there is an error, display the error html, otherwise the feed
	    if ( ! $twitter_feed->tweet_set || $twitter_feed->missing_credentials ) {
	        return $twitter_feed->getErrorHtml();
	    } else {
	        $twitter_feed->maybeCacheTweets();
	        $feed_html .= $twitter_feed->getTweetSetHtml();
	        return $feed_html;
	    }
	}
}
add_shortcode( 'custom-twitter-feed', 'ctf_init' );
add_shortcode( 'custom-twitter-feeds', 'ctf_init' );

/**
 * Called via ajax to get more posts after the "load more" button is clicked
 */
function ctf_get_more_posts() {
    $shortcode_data = json_decode( str_replace( '\"', '"', sanitize_text_field( $_POST['shortcode_data'] ) ), true ); // necessary to unescape quotes
    $last_id_data = isset( $_POST['last_id_data'] ) ? sanitize_text_field( $_POST['last_id_data'] ) : '';
    $num_needed = isset( $_POST['num_needed'] ) ? (int)$_POST['num_needed'] : 0;
    $ids_to_remove = isset( $_POST['ids_to_remove'] ) ? $_POST['ids_to_remove'] : array();
    $feed           = isset( $_POST['v2feed'] ) ? sanitize_key( $_POST['v2feed'] ) : '';
	if ( empty( $shortcode_data ) ) {
		$shortcode_data = array();
	}
	$shortcode_data['feed'] = $feed;

    $is_pagination = empty( $last_id_data ) ? 0 : 1;
    $persistent_index = isset( $_POST['persistent_index'] ) ? sanitize_text_field( $_POST['persistent_index'] ) : '';

    $twitter_feed = TwitterFeed\CtfFeed::init( $shortcode_data, $last_id_data, $num_needed, $ids_to_remove, $persistent_index );

    if ( ! $twitter_feed->feed_options['persistentcache'] ) {
        $twitter_feed->maybeCacheTweets();
    }

	$atts = $shortcode_data;
	$feed_id = isset( $_POST['feed_id'] ) ? sanitize_text_field( $_POST['feed_id'] ) : 'unknown';
	$location = isset( $_POST['location'] ) && in_array( $_POST['location'], array( 'header', 'footer', 'sidebar', 'content' ), true ) ? sanitize_text_field( $_POST['location'] ) : 'unknown';
	$post_id = isset( $_POST['post_id'] ) && $_POST['post_id'] !== 'unknown' ? (int)$_POST['post_id'] : 'unknown';
	$feed_details = array(
		'feed_id' => $feed_id,
		'atts' => $atts,
		'location' => array(
			'post_id' => $post_id,
			'html' => $location
		)
	);

	ctf_do_background_tasks( $feed_details );

    echo $twitter_feed->getItemSetHtml( $is_pagination );

    die();
}
add_action( 'wp_ajax_nopriv_ctf_get_more_posts', 'ctf_get_more_posts' );
add_action( 'wp_ajax_ctf_get_more_posts', 'ctf_get_more_posts' );

function ctf_do_locator() {
	if ( ! isset( $_POST['feed_id'] ) || strpos( $_POST['feed_id'], 'ctf' ) === false ) {
		die( 'invalid feed ID');
	}

	$feed_id = sanitize_text_field( $_POST['feed_id'] );

	$atts_raw = isset( $_POST['atts'] ) ? json_decode( stripslashes( $_POST['atts'] ), true ) : array();
	if ( is_array( $atts_raw ) ) {
		array_map( 'sanitize_text_field', $atts_raw );
	} else {
		$atts_raw = array();
	}
	$atts = $atts_raw; // now sanitized

	$location = isset( $_POST['location'] ) && in_array( $_POST['location'], array( 'header', 'footer', 'sidebar', 'content' ), true ) ? sanitize_text_field( $_POST['location'] ) : 'unknown';
	$post_id = isset( $_POST['post_id'] ) && $_POST['post_id'] !== 'unknown' ? (int)$_POST['post_id'] : 'unknown';
	$feed_details = array(
		'feed_id' => $feed_id,
		'atts' => $atts,
		'location' => array(
			'post_id' => $post_id,
			'html' => $location
		)
	);

	ctf_do_background_tasks( $feed_details );

	wp_die( 'locating success' );
}
add_action( 'wp_ajax_ctf_do_locator', 'ctf_do_locator' );
add_action( 'wp_ajax_nopriv_ctf_do_locator', 'ctf_do_locator' );

function ctf_do_background_tasks( $feed_details ) {
	$locator = new TwitterFeed\CTF_Feed_Locator( $feed_details );
	$locator->add_or_update_entry();
	if ( $locator->should_clear_old_locations() ) {
		$locator->delete_old_locations();
	}
}

function ctf_plugin_action_links( $links ) {
	$links[] = '<a href="'. esc_url( get_admin_url( null, 'admin.php?page=custom-twitter-feeds' ) ) .'">' . __( 'Settings' ) . '</a>';
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'ctf_plugin_action_links' );

function ctf_json_encode( $thing ) {
	if ( function_exists( 'wp_json_encode' ) ) {
		return wp_json_encode( $thing );
	} else {
		return json_encode( $thing );
	}
}

/**
 * the html output is controlled by the user selecting which portions of tweets to show
 *
 * @param $part string          part of the feed in the html
 * @param $feed_options array   options that contain what parts of the tweet to show
 * @return bool                 whether or not to show the tweet
 */
function ctf_show( $part, $feed_options ) {
	if ( ctf_doing_customizer( $feed_options ) ) {
		return true;
	}

    $tweet_excludes = isset( $feed_options['tweet_excludes'] ) ? $feed_options['tweet_excludes'] : '';
    $tweet_includes = isset( $feed_options['tweet_includes'] ) ? $feed_options['tweet_includes'] : '';

    // if part is in the array of excluded parts or not in the array of included parts, don't show
    if ( ! empty( $tweet_excludes ) ) {
        return ( in_array( $part, (array) $tweet_excludes ) === false );
    } else {
        return ( in_array( $part, (array) $tweet_includes ) === true );
    }
}

/**
 * this function returns the properly formatted date string based on user input
 *
 * @param $raw_date string      the date from the Twitter api
 * @param $feed_options array   options for the feed that contain date formatting settings
 * @param $utc_offset int       offset in seconds for the time display based on timezone
 * @return string               formatted date
 */
function ctf_get_formatted_date( $raw_date, $feed_options, $utc_offset ) {

	$options  = get_option( 'ctf_options' );
	$timezone = isset( $options['timezone'] ) ? $options['timezone'] : 'default';
	// use php \DateTimeZone class to handle the date formatting and offsets
	$date_obj = new TwitterFeed\CtfDateTime( $raw_date, new \DateTimeZone( 'UTC' ) );

	if ( $timezone != 'default' ) {
		$date_obj->setTimeZone( new \DateTimeZone( $timezone ) );
		$utc_offset = $date_obj->getOffset();
	}
	$tz_offset_timestamp = $date_obj->getTimestamp() + $utc_offset;

	// use the custom date format if set, otherwise use from the selected defaults
	if ( ! empty( $feed_options['datecustom'] ) ) {
		$date_str = date_i18n( $feed_options['datecustom'], $tz_offset_timestamp );
	} else {

		switch ( $feed_options['dateformat'] ) {
			case '2':
				$date_str = date_i18n( 'F j', $tz_offset_timestamp );
				break;
			case '3':
				$date_str = date_i18n( 'F j, Y', $tz_offset_timestamp );
				break;
			case '4':
				$date_str = date_i18n( 'm.d', $tz_offset_timestamp );
				break;
			case '5':
				$date_str = date_i18n( 'm.d.y', $tz_offset_timestamp );
				break;
			case '6':
				$date_str = date_i18n( 'D M jS, Y', $tz_offset_timestamp );
				break;
			case '7':
				$date_str = date_i18n( 'l F jS, Y', $tz_offset_timestamp );
				break;
			case '8':
				$date_str = date_i18n( 'l F jS, Y - g:i a', $tz_offset_timestamp );
				break;
			case '9':
				$date_str = date_i18n( "l M jS, 'y", $tz_offset_timestamp );
				break;
			case '10':
				$date_str = date_i18n( 'm.d.y', $tz_offset_timestamp );
				break;
			case '18':
				$date_str = date_i18n( 'm.d.y - G:i', $tz_offset_timestamp );
				break;
			case '11':
				$date_str = date_i18n( 'm/d/y', $tz_offset_timestamp );
				break;
			case '12':
				$date_str = date_i18n( 'd.m.y', $tz_offset_timestamp );
				break;
			case '19':
				date_i18n( 'd.m.y - G:i', $tz_offset_timestamp );
				break;
			case '13':
				$date_str = date_i18n( 'd/m/y', $tz_offset_timestamp );
				break;
			case '14':
				$date_str = date_i18n( 'd-m-Y, G:i', $tz_offset_timestamp );
				break;
			case '15':
				$date_str = date_i18n( 'jS F Y, G:i', $tz_offset_timestamp );
				break;
			case '16':
				$date_str = date_i18n( 'd M Y, G:i', $tz_offset_timestamp );
				break;
			case '17':
				$date_str = date_i18n( 'l jS F Y, G:i', $tz_offset_timestamp );
				break;
			case '18':
				$date_str = date_i18n( 'Y-m-d', $tz_offset_timestamp );
				break;
			default:
			// default format is similar to Twitter
                $ctf_minute = ! empty( $options['mtime'] ) ? $options['mtime'] : 'm';
                $ctf_hour = ! empty( $options['htime'] ) ? $options['htime'] : 'h';
                $ctf_now_str = ! empty( $options['nowtime'] ) ? $options['nowtime'] : 'now';

                $now = time() + $utc_offset;

                $difference = $now - $tz_offset_timestamp;
                if ( $difference < 60 ) {
                    $date_str = $ctf_now_str;
                } elseif ( $difference < 60*60 ) {
                    $date_str = round( $difference/60 ) . $ctf_minute;
                } elseif ( $difference < 60*60*24 ) {
                    $date_str = round( $difference/3600 ) . $ctf_hour;
                } else  {
                    $one_year_from_date = new TwitterFeed\CtfDateTime( $raw_date, new \DateTimeZone( "UTC" ) );
                    $one_year_from_date->modify('+1 year');
                    $one_year_from_date_timestamp = $one_year_from_date->getTimestamp();
                    if ( $now > $one_year_from_date_timestamp ) {
                        $date_str = date_i18n( 'j M Y', $tz_offset_timestamp );
                    } else {
                        $date_str = date_i18n( 'j M', $tz_offset_timestamp );
                    }
                }

			break;
		}
	}

	return $date_str;
}

function ctf_maybe_shorten_text( $string, $feed_settings ) {
	#if( !ctf_doing_customizer($feed_settings) ){
	$limit = is_array( $feed_settings ) ? $feed_settings['textlength'] : $feed_settings;

	if ( strlen( $string ) <= $limit
		|| $limit >= 280 ) {
		return $string;
	}

	$parts       = preg_split( '/([\s\n\r]+)/', $string, null, PREG_SPLIT_DELIM_CAPTURE );
	$parts_count = count( $parts );

	$length      = 0;
	$last_part   = 0;
	$first_parts = array();
	$end_parts   = array();
	for ( ; $last_part < $parts_count; $last_part++ ) {
		$length += strlen( $parts[ $last_part ] );
		if ( $length < $limit ) {
			$first_parts[] = $parts[ $last_part ];
		} else {
			$end_parts[] = $parts[ $last_part ];
		}
	}
	$return = implode( ' ', $first_parts ) . '<a href="#" class="ctf_more">...</a><span class="ctf_remaining">';

	$return .= implode( ' ', $end_parts ) . '</span>';

	return $return;
	#}
	#return $string;
}
add_filter( 'ctf_tweet_text', 'ctf_maybe_shorten_text', 10, 2 );

function ctf_replace_urls( $string, $feed_settings, $post ) {

	if ( $feed_settings['shorturls'] ) {
		return $string;
	}

	if ( isset( $post['entities']['urls'][0] ) ) {
		foreach ( $post['entities']['urls'] as $url ) {
		    if ( isset( $url['url'] ) ) {
			    $string = str_replace( $url['url'], $url['expanded_url'], $string );
		    }
		}
	}

	return $string;
}
add_filter( 'ctf_tweet_text', 'ctf_replace_urls', 9, 3 );
add_filter( 'ctf_quoted_tweet_text', 'ctf_replace_urls', 9, 3 );

function ctf_get_fa_el( $icon ) {
	$options = get_option( 'ctf_options' );
	$font_method = 'svg';

	$elems = array(
		'fa-arrows-alt' => array(
			'icon' => '<span class="fa fa-arrows-alt"></span>',
			'svg' => '<svg class="svg-inline--fa fa-arrows-alt fa-w-16" aria-hidden="true" aria-label="expand" data-fa-processed="" data-prefix="fa" data-icon="arrows-alt" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M352.201 425.775l-79.196 79.196c-9.373 9.373-24.568 9.373-33.941 0l-79.196-79.196c-15.119-15.119-4.411-40.971 16.971-40.97h51.162L228 284H127.196v51.162c0 21.382-25.851 32.09-40.971 16.971L7.029 272.937c-9.373-9.373-9.373-24.569 0-33.941L86.225 159.8c15.119-15.119 40.971-4.411 40.971 16.971V228H228V127.196h-51.23c-21.382 0-32.09-25.851-16.971-40.971l79.196-79.196c9.373-9.373 24.568-9.373 33.941 0l79.196 79.196c15.119 15.119 4.411 40.971-16.971 40.971h-51.162V228h100.804v-51.162c0-21.382 25.851-32.09 40.97-16.971l79.196 79.196c9.373 9.373 9.373 24.569 0 33.941L425.773 352.2c-15.119 15.119-40.971 4.411-40.97-16.971V284H284v100.804h51.23c21.382 0 32.09 25.851 16.971 40.971z"></path></svg>'
		),
		'fa-check-circle' => array(
			'icon' => '<span class="fa fa-check-circle"></span>',
			'svg' => '<svg class="svg-inline--fa fa-check-circle fa-w-16" aria-hidden="true" aria-label="verified" data-fa-processed="" data-prefix="fa" data-icon="check-circle" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M504 256c0 136.967-111.033 248-248 248S8 392.967 8 256 119.033 8 256 8s248 111.033 248 248zM227.314 387.314l184-184c6.248-6.248 6.248-16.379 0-22.627l-22.627-22.627c-6.248-6.249-16.379-6.249-22.628 0L216 308.118l-70.059-70.059c-6.248-6.248-16.379-6.248-22.628 0l-22.627 22.627c-6.248 6.248-6.248 16.379 0 22.627l104 104c6.249 6.249 16.379 6.249 22.628.001z"></path></svg>'
		),
		'fa-reply' => array(
			'icon' => '<span class="fa fa-reply"></span>',
			'svg' => '<svg class="svg-inline--fa fa-w-16" viewBox="0 0 24 24" aria-label="reply" role="img" xmlns="http://www.w3.org/2000/svg"><g><path fill="currentColor" d="M14.046 2.242l-4.148-.01h-.002c-4.374 0-7.8 3.427-7.8 7.802 0 4.098 3.186 7.206 7.465 7.37v3.828c0 .108.044.286.12.403.142.225.384.347.632.347.138 0 .277-.038.402-.118.264-.168 6.473-4.14 8.088-5.506 1.902-1.61 3.04-3.97 3.043-6.312v-.017c-.006-4.367-3.43-7.787-7.8-7.788zm3.787 12.972c-1.134.96-4.862 3.405-6.772 4.643V16.67c0-.414-.335-.75-.75-.75h-.396c-3.66 0-6.318-2.476-6.318-5.886 0-3.534 2.768-6.302 6.3-6.302l4.147.01h.002c3.532 0 6.3 2.766 6.302 6.296-.003 1.91-.942 3.844-2.514 5.176z"></path></g></svg>'
		),
		'fa-retweet' => array(
			'icon' => '<span class="fa fa-retweet"></span>',
			'svg' => '<svg class="svg-inline--fa fa-w-16" viewBox="0 0 24 24" aria-hidden="true" aria-label="retweet" role="img"><path fill="currentColor" d="M23.77 15.67c-.292-.293-.767-.293-1.06 0l-2.22 2.22V7.65c0-2.068-1.683-3.75-3.75-3.75h-5.85c-.414 0-.75.336-.75.75s.336.75.75.75h5.85c1.24 0 2.25 1.01 2.25 2.25v10.24l-2.22-2.22c-.293-.293-.768-.293-1.06 0s-.294.768 0 1.06l3.5 3.5c.145.147.337.22.53.22s.383-.072.53-.22l3.5-3.5c.294-.292.294-.767 0-1.06zm-10.66 3.28H7.26c-1.24 0-2.25-1.01-2.25-2.25V6.46l2.22 2.22c.148.147.34.22.532.22s.384-.073.53-.22c.293-.293.293-.768 0-1.06l-3.5-3.5c-.293-.294-.768-.294-1.06 0l-3.5 3.5c-.294.292-.294.767 0 1.06s.767.293 1.06 0l2.22-2.22V16.7c0 2.068 1.683 3.75 3.75 3.75h5.85c.414 0 .75-.336.75-.75s-.337-.75-.75-.75z"></path></svg>'
		),
		'fa-heart' => array(
			'icon' => '<span class="fa fa-heart"></span>',
			'svg' => '<svg class="svg-inline--fa fa-w-16" viewBox="0 0 24 24" aria-hidden="true" aria-label="like" role="img" xmlns="http://www.w3.org/2000/svg"><g><path fill="currentColor" d="M12 21.638h-.014C9.403 21.59 1.95 14.856 1.95 8.478c0-3.064 2.525-5.754 5.403-5.754 2.29 0 3.83 1.58 4.646 2.73.814-1.148 2.354-2.73 4.645-2.73 2.88 0 5.404 2.69 5.404 5.755 0 6.376-7.454 13.11-10.037 13.157H12zM7.354 4.225c-2.08 0-3.903 1.988-3.903 4.255 0 5.74 7.034 11.596 8.55 11.658 1.518-.062 8.55-5.917 8.55-11.658 0-2.267-1.823-4.255-3.903-4.255-2.528 0-3.94 2.936-3.952 2.965-.23.562-1.156.562-1.387 0-.014-.03-1.425-2.965-3.954-2.965z"></path></g></svg>'
		),
		'fa-twitter' => array(
			'icon' => '<span class="fa fab fa-twitter"></span>',
			'svg' => '<svg class="svg-inline--fa fa-twitter fa-w-16" aria-hidden="true" aria-label="twitter logo" data-fa-processed="" data-prefix="fab" data-icon="twitter" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M459.37 151.716c.325 4.548.325 9.097.325 13.645 0 138.72-105.583 298.558-298.558 298.558-59.452 0-114.68-17.219-161.137-47.106 8.447.974 16.568 1.299 25.34 1.299 49.055 0 94.213-16.568 130.274-44.832-46.132-.975-84.792-31.188-98.112-72.772 6.498.974 12.995 1.624 19.818 1.624 9.421 0 18.843-1.3 27.614-3.573-48.081-9.747-84.143-51.98-84.143-102.985v-1.299c13.969 7.797 30.214 12.67 47.431 13.319-28.264-18.843-46.781-51.005-46.781-87.391 0-19.492 5.197-37.36 14.294-52.954 51.655 63.675 129.3 105.258 216.365 109.807-1.624-7.797-2.599-15.918-2.599-24.04 0-57.828 46.782-104.934 104.934-104.934 30.213 0 57.502 12.67 76.67 33.137 23.715-4.548 46.456-13.32 66.599-25.34-7.798 24.366-24.366 44.833-46.132 57.827 21.117-2.273 41.584-8.122 60.426-16.243-14.292 20.791-32.161 39.308-52.628 54.253z"></path></svg>'
		),
		'fa-user' => array(
			'icon' => '<span class="fa fa-user"></span>',
			'svg' => '<svg class="svg-inline--fa fa-user fa-w-16" aria-hidden="true" aria-label="followers" data-fa-processed="" data-prefix="fa" data-icon="user" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M96 160C96 71.634 167.635 0 256 0s160 71.634 160 160-71.635 160-160 160S96 248.366 96 160zm304 192h-28.556c-71.006 42.713-159.912 42.695-230.888 0H112C50.144 352 0 402.144 0 464v24c0 13.255 10.745 24 24 24h464c13.255 0 24-10.745 24-24v-24c0-61.856-50.144-112-112-112z"></path></svg>'
		),
		'ctf_playbtn' => array(
			'icon' => '',
			'svg' => '<svg aria-label="play button" style="color: rgba(255,255,255,1)" class="svg-inline--fa fa-play fa-w-14 ctf_playbtn" aria-hidden="true" data-fa-processed="" data-prefix="fa" data-icon="play" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M424.4 214.7L72.4 6.6C43.8-10.3 0 6.1 0 47.9V464c0 37.5 40.7 60.1 72.4 41.3l352-208c31.4-18.5 31.5-64.1 0-82.6z"></path></svg>'
		),
		'fa-file-video-o'  => array(
			'icon' => '<span class="fa fa-file-video-o ctf-tweet-text-media" aria-hidden="true"></span>',
			'svg' => '<svg aria-hidden="true" aria-label="video in tweet" focusable="false" data-prefix="far" data-icon="file-video" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" class="svg-inline--fa fa-file-video fa-w-12 fa-9x ctf-tweet-text-media"><path fill="currentColor" d="M369.941 97.941l-83.882-83.882A48 48 0 0 0 252.118 0H48C21.49 0 0 21.49 0 48v416c0 26.51 21.49 48 48 48h288c26.51 0 48-21.49 48-48V131.882a48 48 0 0 0-14.059-33.941zM332.118 128H256V51.882L332.118 128zM48 464V48h160v104c0 13.255 10.745 24 24 24h104v288H48zm228.687-211.303L224 305.374V268c0-11.046-8.954-20-20-20H100c-11.046 0-20 8.954-20 20v104c0 11.046 8.954 20 20 20h104c11.046 0 20-8.954 20-20v-37.374l52.687 52.674C286.704 397.318 304 390.28 304 375.986V264.011c0-14.311-17.309-21.319-27.313-11.314z" class=""></path></svg>'
		),
		'fa-picture-o'  => array(
			'icon' => '<span class="fa fa-picture-o ctf-tweet-text-media" aria-hidden="true"></span>',
			'svg' => '<svg aria-hidden="true" aria-label="images in tweet" focusable="false" data-prefix="far" data-icon="image" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="svg-inline--fa fa-image fa-w-16 fa-9x ctf-tweet-text-media"><path fill="currentColor" d="M464 64H48C21.49 64 0 85.49 0 112v288c0 26.51 21.49 48 48 48h416c26.51 0 48-21.49 48-48V112c0-26.51-21.49-48-48-48zm-6 336H54a6 6 0 0 1-6-6V118a6 6 0 0 1 6-6h404a6 6 0 0 1 6 6v276a6 6 0 0 1-6 6zM128 152c-22.091 0-40 17.909-40 40s17.909 40 40 40 40-17.909 40-40-17.909-40-40-40zM96 352h320v-80l-87.515-87.515c-4.686-4.686-12.284-4.686-16.971 0L192 304l-39.515-39.515c-4.686-4.686-12.284-4.686-16.971 0L96 304v48z" class=""></path></svg>'
		),
	);

	if ( $font_method !== 'fontfile' ){
		return $elems[ $icon ]['svg'];
	}

	return $elems[ $icon ]['icon'];
}

/**
 * Called via ajax to automatically save access token and access token secret
 * retrieved with the big blue button
 */
function ctf_auto_save_tokens() {
	if ( ! ctf_current_user_can( 'manage_twitter_feed_options' ) ) {
       wp_send_json_error();
    }
	wp_cache_delete ( 'alloptions', 'options' );

	$options = get_option( 'ctf_options', array() );

	$options['access_token'] = sanitize_text_field( $_POST['access_token'] );
	$options['access_token_secret'] = sanitize_text_field( $_POST['access_token_secret'] );

	update_option( 'ctf_options', $options );
	delete_transient( 'ctf_reauthenticate' );
    die();
}
add_action( 'wp_ajax_ctf_auto_save_tokens', 'ctf_auto_save_tokens' );

/**
 * manually clears the cached tweets in case of error or user preference
 *
 * @return mixed bool whether or not it was successful
 */
function ctf_clear_cache() {

    //Delete all transients
    global $wpdb;
    $table_name = $wpdb->prefix . "options";
    $result = $wpdb->query("
    DELETE
    FROM $table_name
    WHERE `option_name` LIKE ('%\_transient\_ctf\_%')
    ");
    $wpdb->query("
    DELETE
    FROM $table_name
    WHERE `option_name` LIKE ('%\_transient\_timeout\_ctf\_%')
    ");

	ctf_clear_page_caches();

}
add_action( 'ctf_cron_job', 'ctf_clear_cache' );

function ctf_clear_cache_admin() {
	if ( ! ctf_current_user_can( 'manage_twitter_feed_options' ) ) {
		wp_send_json_error();
	}

    //Delete all transients
    global $wpdb;
    $table_name = $wpdb->prefix . "options";
    $result = $wpdb->query("
    DELETE
    FROM $table_name
    WHERE `option_name` LIKE ('%\_transient\_ctf\_%')
    ");
    $wpdb->query("
    DELETE
    FROM $table_name
    WHERE `option_name` LIKE ('%\_transient\_timeout\_ctf\_%')
    ");

	ctf_clear_page_caches();

	wp_send_json_success();
}
add_action( 'wp_ajax_ctf_clear_cache_admin', 'ctf_clear_cache_admin' );

/**
 * manually clears the persistent cached tweets
 *
 * @return mixed bool whether or not it was successful
 */

function ctf_clear_persistent_cache() {
	if ( ! ctf_current_user_can( 'manage_twitter_feed_options' ) ) {
		wp_send_json_error();
	}
	//Delete all persistent caches (start with ctf_!)
	global $wpdb;
	$table_name = $wpdb->prefix . "options";
	$result = $wpdb->query("
	DELETE
	FROM $table_name
	WHERE `option_name` LIKE ('%ctf\_\!%')
	");
	delete_option( 'ctf_cache_list' );

	ctf_clear_page_caches();

    wp_send_json_success();
}
add_action( 'wp_ajax_ctf_clear_persistent_cache', 'ctf_clear_persistent_cache' );

function ctf_activate() {
	// set usage tracking to false if fresh install.
	$usage_tracking = get_option( 'ctf_usage_tracking', false );

    global $wp_roles;
	$wp_roles->add_cap( 'administrator', 'manage_twitter_feed_options' );

	if ( ! is_array( $usage_tracking ) ) {
		$usage_tracking = array(
			'enabled' => false,
			'last_send' => 0
		);

		update_option( 'ctf_usage_tracking', $usage_tracking, false );
	}
	global $wp_roles;
	$wp_roles->add_cap( 'administrator', 'manage_twitter_feed_options' );
}
register_activation_hook( __FILE__, 'ctf_activate' );

/**
 * clear the cache and unschedule an cron jobs when deactivated
 */
function ctf_deactivate() {
    ctf_clear_cache();

    wp_clear_scheduled_hook( 'ctf_cron_job' );
}
register_deactivation_hook( __FILE__, 'ctf_deactivate' );

/**
 * Loads the javascript for the plugin front-end. Also localizes the admin-ajax file location for use in ajax calls
 */
function ctf_scripts_and_styles( $enqueue = false ) {
	$options = get_option( 'ctf_options' );
	$not_ajax_theme = (! isset( $options['ajax_theme'] ) || ! $options['ajax_theme']);
	$font_method = 'svg';

	$loacalize_args = array(
		'ajax_url' => admin_url( 'admin-ajax.php' )
	);

	wp_enqueue_style( 'ctf_styles', plugins_url( '/css/ctf-styles.min.css', __FILE__ ), array(), CTF_VERSION );


    if ( $not_ajax_theme ) {
	    wp_register_script( 'ctf_scripts', plugins_url( '/js/ctf-scripts.min.js', __FILE__ ), array( 'jquery' ), CTF_VERSION, true );
	    wp_localize_script( 'ctf_scripts', 'ctf', $loacalize_args );
    } else {
	    wp_localize_script( 'jquery', 'ctf', $loacalize_args );
    }

	if ( $enqueue ) {
		wp_enqueue_style( 'ctf_styles' );
		wp_enqueue_script( 'ctf_scripts' );
	}
}
add_action( 'wp_enqueue_scripts', 'ctf_scripts_and_styles' );

/**
 * outputs the custom js from the "Customize" tab on the Settings page
 */
function ctf_custom_js() {
    $options = get_option( 'ctf_options' );
    $ctf_custom_js = isset( $options[ 'custom_js' ] ) ? $options[ 'custom_js' ] : '';

    if ( ! empty( $ctf_custom_js ) ) {
        ?>
        <!-- Custom Twitter Feeds JS -->
        <script type="text/javascript">
            <?php echo "window.ctf_custom_js = function($){" . stripslashes( $ctf_custom_js ) . "}\r\n"; ?>
        </script>
        <?php
    }
}
add_action( 'wp_footer', 'ctf_custom_js' );

/**
 * outputs the custom css from the "Customize" tab on the Settings page
 */
function ctf_custom_css() {
    $options = get_option( 'ctf_options' );
    $ctf_custom_css = isset( $options[ 'custom_css' ] ) ? $options[ 'custom_css' ] : '';

    if ( ! empty( $ctf_custom_css ) ) {
        ?>
        <!-- Custom Twitter Feeds CSS -->
        <style type="text/css">
            <?php echo stripslashes( $ctf_custom_css ) . "\r\n"; ?>
        </style>
        <?php
    }
}
add_action( 'wp_head', 'ctf_custom_css' );

/**
 * Some CSS and JS needed in the admin area as well
 */
function ctf_admin_scripts_and_styles() {
	wp_enqueue_style(
		'feed-global-style',
		CTF_PLUGIN_URL . 'admin/builder/assets/css/global.css',
		false,
		CTF_VERSION
	);
    wp_enqueue_style( 'ctf_admin_styles', plugins_url( '/css/ctf-admin-styles.css', __FILE__ ), array(), CTF_VERSION );
	wp_enqueue_style( 'sb-font-awesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css' );
	wp_enqueue_script( 'ctf_admin_scripts', plugins_url( '/js/ctf-admin-scripts.js', __FILE__ ) , array( 'jquery' ), CTF_VERSION, false );
    wp_localize_script( 'ctf_admin_scripts', 'ctf', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
			'sb_nonce' => wp_create_nonce( 'ctf-smash-balloon' ),
			'nonce' => wp_create_nonce( 'ctf-admin' )
        )
    );
	$strings = array(
		'addon_activate'                  => esc_html__( 'Activate', 'custom-twitter-feeds' ),
		'addon_activated'                 => esc_html__( 'Activated', 'custom-twitter-feeds' ),
		'addon_active'                    => esc_html__( 'Active', 'custom-twitter-feeds' ),
		'addon_deactivate'                => esc_html__( 'Deactivate', 'custom-twitter-feeds' ),
		'addon_inactive'                  => esc_html__( 'Inactive', 'custom-twitter-feeds' ),
		'addon_install'                   => esc_html__( 'Install Addon', 'custom-twitter-feeds' ),
		'addon_error'                     => esc_html__( 'Could not install addon. Please download from wpforms.com and install manually.', 'custom-twitter-feeds' ),
		'plugin_error'                    => esc_html__( 'Could not install a plugin. Please download from WordPress.org and install manually.', 'custom-twitter-feeds' ),
		'addon_search'                    => esc_html__( 'Searching Addons', 'custom-twitter-feeds' ),
		'ajax_url'                        => admin_url( 'admin-ajax.php' ),
		'cancel'                          => esc_html__( 'Cancel', 'custom-twitter-feeds' ),
		'close'                           => esc_html__( 'Close', 'custom-twitter-feeds' ),
		'nonce'                           => wp_create_nonce( 'ctf-admin' ),
		'almost_done'                     => esc_html__( 'Almost Done', 'custom-twitter-feeds' ),
		'oops'                            => esc_html__( 'Oops!', 'custom-twitter-feeds' ),
		'ok'                              => esc_html__( 'OK', 'custom-twitter-feeds' ),
		'plugin_install_activate_btn'     => esc_html__( 'Install and Activate', 'custom-twitter-feeds' ),
		'plugin_install_activate_confirm' => esc_html__( 'needs to be installed and activated to import its forms. Would you like us to install and activate it for you?', 'custom-twitter-feeds' ),
		'plugin_activate_btn'             => esc_html__( 'Activate', 'custom-twitter-feeds' ),
	);
	$strings = apply_filters( 'ctf_admin_strings', $strings );

	wp_localize_script(
		'ctf_admin_scripts',
		'ctf_admin_strings',
		$strings
	);
    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script( 'wp-color-picker' );
	wp_enqueue_script(
		'jquery-matchheight',
		CTF_PLUGIN_URL . 'js/jquery.matchHeight-min.js',
		array( 'jquery' ),
		'0.7.0',
		false
	);
}
add_action( 'admin_enqueue_scripts', 'ctf_admin_scripts_and_styles' );


function ctf_is_pro_version() {
	return defined( 'CTF_STORE_URL' );
}


function ctf_get_database_settings() {
	$options = get_option( 'ctf_options', array() );
	$options = set_global_default_settings( $options );
	return $options;
}

function set_global_default_settings( $options ) {
	$defaults = array(
		'disableintents' => false,
		'creditctf'      => false,
		'ajax_theme'     => false,
	);
	foreach ( $defaults as $key => $value ) {
		if ( ! isset( $options[ $key ] ) ) {
			$options[ $key ] = $value;
		}
	}
	return $options;
}

function ctf_clear_page_caches() {
	//Clear cache of major caching plugins
	if(isset($GLOBALS['wp_fastest_cache']) && method_exists($GLOBALS['wp_fastest_cache'], 'deleteCache')){
		$GLOBALS['wp_fastest_cache']->deleteCache();
	}
	//WP Super Cache
	if (function_exists('wp_cache_clear_cache')) {
		wp_cache_clear_cache();
	}
	//W3 Total Cache
	if (function_exists('w3tc_flush_all')) {
		w3tc_flush_all();
	}
	if (function_exists('sg_cachepress_purge_cache')) {
		sg_cachepress_purge_cache();
	}

	// Litespeed Cache (older method)
	if ( method_exists( 'LiteSpeed_Cache_API', 'purge' ) ) {
		LiteSpeed_Cache_API::purge( 'esi.custom-twitter-feeds' );
	}

	// Litespeed Cache (new method)
	if(has_action('litespeed_purge')) {
		do_action( 'litespeed_purge', 'esi.custom-twitter-feeds' );
	}
}

/**
 * Add the custom interval of 30 minutes for cron caching
 *
 * @param  array $schedules current list of cron intervals
 *
 * @return array
 *
 * @since  2.0
 */
function ctf_cron_custom_interval( $schedules ) {
	$schedules['ctf30mins'] = array(
		'interval' => 30 * 60,
		'display'  => __( 'Every 30 minutes' )
	);
	$schedules['ctfweekly'] = array(
		'interval' => 3600 * 24 * 7,
		'display'  => __( 'Weekly' )
	);

	return $schedules;
}

add_filter( 'cron_schedules', 'ctf_cron_custom_interval' );

//BUILDER CODE
function ctf_builder() {
	return \TwitterFeed\Builder\CTF_Feed_Builder::instance();
}
ctf_builder();
