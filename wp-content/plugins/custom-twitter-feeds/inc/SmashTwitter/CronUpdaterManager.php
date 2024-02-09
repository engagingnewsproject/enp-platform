<?php
/**
* Class Request
*
* Performs a request to the Smash Balloon Twitter API.
*
* @since 2.1
*/
namespace TwitterFeed\SmashTwitter;

use TwitterFeed\Builder\CTF_Feed_Builder;
use TwitterFeed\SB_Twitter_Cron_Updater;

class CronUpdaterManager
{
	private $max_batch;
	private $max_requests;

	private $api_call_log;
	private $options;
	private $request_counter;

	public function __construct() {
		$this->max_batch = 1;
		$this->max_requests = 1;

		$this->api_call_log = get_option( 'ctf_api_call_log', array() );

		$this->options = ctf_get_database_settings();

		$ctf_statuses_option = get_option( 'ctf_statuses', array() );
		$this->request_counter = 0;
		if ( ! empty( $ctf_statuses_option['smash_twitter_cron']['request_counter'] ) ) {
			$this->request_counter = $ctf_statuses_option['smash_twitter_cron']['request_counter'];
		}

	}

	public function hooks() {
		add_action('ctf_smash_twitter_feed_update', [$this, 'maybe_do_updates']);
		add_action('ctf_smash_twitter_additional_batch', [$this, 'init_additional_batch']);
	}

	public function calculate_frequency() {
		return DAY_IN_SECONDS * 3;
	}

	public function can_update() {
		return true;
	}

	public function update_api_call_log( $type, $term ) {
		if ( ! is_array( $this->api_call_log ) ) {
			$this->api_call_log = array();
		}

		if ( is_array( $this->api_call_log ) && count( $this->api_call_log ) > 50 ) {
			reset( $this->api_call_log );
			unset( $this->api_call_log[ key($this->api_call_log ) ] );
		}

		$this->api_call_log[] = array(
			'time' => time(),
			'type' => $type,
			'term' => $term
		);

		update_option( 'ctf_api_call_log', $this->api_call_log, false );
	}

	public function get_api_call_log() {
		return $this->api_call_log;
	}

	public function init_additional_batch() {
		$this->do_updates( true );
	}

	public function should_do_updates() {
		if ( ! $this->is_past_first_allowed_update() ) {
			return false;
		}

		$time_with_minute_buffer = time() + 60;

		if ( $this->get_last_update_process_time() < ($time_with_minute_buffer - $this->calculate_frequency()) ) {
			if ( $this->request_counter < $this->max_requests ) {
				return true;
			}
		}

		return false;
	}

	public function get_last_update_process_time() {
		$ctf_statuses_option = get_option( 'ctf_statuses', array() );
		if ( empty( $ctf_statuses_option['smash_twitter_cron']['last_update_process_time'] ) ) {
			return 0;
		}
		return $ctf_statuses_option['smash_twitter_cron']['last_update_process_time'];
	}

	public function update_last_update_process( $time ) {
		$ctf_statuses_option = get_option( 'ctf_statuses', array() );
		$ctf_statuses_option['smash_twitter_cron']['last_update_process_time'] = $time;

		update_option( 'ctf_statuses', $ctf_statuses_option );
	}

	public function reset_request_counter() {
		$ctf_statuses_option = get_option( 'ctf_statuses', array() );
		$ctf_statuses_option['smash_twitter_cron']['request_counter'] = 0;
		$ctf_statuses_option['smash_twitter_cron']['request_counter_reset_time'] = time();

		$this->request_counter = 0;
		update_option( 'ctf_statuses', $ctf_statuses_option );
	}

	public function maybe_reset_request_counter() {
		$ctf_statuses_option = get_option( 'ctf_statuses', array() );
		$reset_time = ! empty( $ctf_statuses_option['smash_twitter_cron']['request_counter_reset_time'] ) ? $ctf_statuses_option['smash_twitter_cron']['request_counter_reset_time'] : 0;
		if ( $reset_time < time() - DAY_IN_SECONDS * 3 ) {
			$this->reset_request_counter();
			return true;
		}

		return false;
	}

	public function get_request_counter() {
		return $this->request_counter;
	}

	public function add_to_request_counter( $to_add ) {
		$ctf_statuses_option = get_option( 'ctf_statuses', array() );
		$request_counter = $this->request_counter + $to_add;

		$ctf_statuses_option['smash_twitter_cron']['request_counter'] = $request_counter;

		update_option( 'ctf_statuses', $ctf_statuses_option );
	}

	private function is_past_first_allowed_update() {
		return true;
		$ctf_statuses_option = get_option( 'ctf_statuses', array() );
		if ( empty( $ctf_statuses_option['first_cron_update'] ) ) {
			return true;
		}
		return time() > $ctf_statuses_option['first_cron_update'];
	}

	public function do_updates( $is_additional_batch = false ) {
		$can_auth = $this->maybe_setup_auth();
		if ( ! $can_auth ) {
			return;
		}


		$batch_feeds = $this->get_next_batch_of_updatable_feeds( $is_additional_batch );

		$num_found = count($batch_feeds);

		if (! $num_found) {
			return;
		}

		$return = array();
		$requests_made = $this->request_counter;
		foreach ( $batch_feeds as $batch_feed ) {
			if ( $this->max_requests <= $requests_made ) {
				continue;
			}
			$updatable_feed_id = ! empty( $batch_feed['feed_id'] ) ? $batch_feed['feed_id'] : 'legacy';

			$sources_to_update = 1;
			if ( ! empty( $batch_feed['settings'] ) ) {
				$settings = json_decode( $batch_feed['settings'], true );

				$sources_to_update = $this->calc_sources_to_update( $settings );
			}

			$this->add_to_request_counter( $sources_to_update );

			$this->update_cache_last_updated( $updatable_feed_id );

			$return = SB_Twitter_Cron_Updater::do_single_feed_cron_update( $updatable_feed_id );
		}

		if ( ! empty( $return['data'] ) ) {
			foreach ( $return['data'] as $item ) {
				if ( ! empty( $item ) ) {
					foreach ( $item as $feed_type_and_terms ) {
						$this->update_api_call_log( $feed_type_and_terms[0], $feed_type_and_terms[1]);
					}
				}
			}
		}
	}

	public function calc_sources_to_update( $settings ) {
		$sources_to_update = 1;
		if ( ! empty( $settings['type'] ) ) {
			if ( $settings['type'] === 'usertimeline' ) {
				if ( ! empty( $settings['usertimeline_text'] ) ) {
					if ( is_array( $settings['usertimeline_text'] ) ) {
						$sources_to_update = count( $settings['usertimeline_text'] );
					} elseif ( is_string( $settings['usertimeline_text'] ) ) {
						$sources_array = explode( ',',  $settings['usertimeline_text'] );
						$sources_to_update = count( $sources_array );
					}
				}
			} elseif ( $settings['type'] === 'search' ) {
				if ( ! empty( $settings['search_text'] ) ) {
					if ( is_array( $settings['search_text'] ) ) {
						$sources_to_update = count( $settings['search_text'] );
					} elseif ( is_string( $settings['search_text'] ) ) {
						$sources_array = explode( ',',  $settings['search_text'] );
						$sources_to_update = count( $sources_array );
					}
				}
			} elseif ( $settings['type'] === 'hashtag' ) {
				if ( ! empty( $settings['hashtag_text'] ) ) {
					if ( is_array( $settings['hashtag_text'] ) ) {
						$sources_to_update = count( $settings['hashtag_text'] );
					} elseif ( is_string( $settings['hashtag_text'] ) ) {
						$sources_array = explode( ',',  $settings['hashtag_text'] );
						$sources_to_update = count( $sources_array );
					}
				}
			}
		}
		return $sources_to_update;
	}

	public function maybe_setup_auth() {
		$ctf_options = get_option( 'ctf_options', array() );

		if ( empty( $ctf_options[ CTF_SITE_ACCESS_TOKEN_KEY ] ) ) {
			return false;
		}

		return true;
	}

	public function fetch_all_feeds() {
		$feeds = CTF_Feed_Builder::get_feed_list();

		$builder = new CTF_Feed_Builder();
		$legacy_feeds = $builder->get_legacy_feed_list();

		return array_merge($feeds, $legacy_feeds);
	}

	public function get_next_batch_of_updatable_feeds( $is_additional_batch )
	{
		return $this->get_updatable_feeds( $is_additional_batch );
	}

	public function get_updatable_feeds( $is_additional_batch ) {
		global $wpdb;
		$feeds_table_name = $wpdb->prefix . 'ctf_feeds';
		$cache_table_name = $wpdb->prefix . 'ctf_feed_caches';


		$sql = $wpdb->prepare( "
		SELECT * FROM $cache_table_name as c
		LEFT JOIN $feeds_table_name as f ON c.feed_id = f.id
		WHERE c.cron_update = 'yes'
		GROUP BY c.feed_id
		ORDER BY c.feed_id ASC
		LIMIT %d;
	 ", 1 );

		return $wpdb->get_results( $sql, ARRAY_A );
	}

	public function update_cache_last_updated( $feed_id ) {
		global $wpdb;
		$cache_table_name = $wpdb->prefix . 'ctf_feed_caches';

		return $wpdb->query( $wpdb->prepare(
			"
			UPDATE $cache_table_name
			SET last_updated = %s
			WHERE feed_id = %s",
			date( 'Y-m-d H:i:s'),
			$feed_id
		) );
	}

	public static function schedule_cron_job() {
		if ( ! wp_next_scheduled( 'ctf_smash_twitter_feed_update' ) ) {
			wp_schedule_event( time(), 'hourly', 'ctf_smash_twitter_feed_update' );
		}
	}

	/**
	 * Maybe do updates if enough time has passed since the plugin was updated.
	 *
	 * @return boolean
	 */
	public function maybe_do_updates() {
		$this->maybe_reset_request_counter(); 

		if ( $this->should_do_updates() ) {
			$this->update_last_update_process( time() );

			$this->do_updates();
			return true;
		}
		return false;
	}
}
