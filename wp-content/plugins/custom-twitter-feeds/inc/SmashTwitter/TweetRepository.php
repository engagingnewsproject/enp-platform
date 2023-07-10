<?php
/**
 * Class TweetRepository
 *
 * Aggregates Tweets stored in cache.
 *
 * @since 2.1
 */
namespace TwitterFeed\SmashTwitter;

use TwitterFeed\CtfCache;
use TwitterFeed\CTF_Feed;

class TweetRepository
{
	protected $types_and_terms;

	protected $cache_id;

	/**
	 * @var TweetAggregator
	 */
	protected $tweet_aggregator;

	/**
	 * @var CtfCache
	 */
	protected $feed_cache;

	/**
	 * @var TweetSetModifier
	 */
	protected $tweet_set_modifier;

	protected $tweets;

	protected $header_data;

	/**
	 * @var ErrorReport
	 */
	protected $error_report;

	public function __construct( $types_and_terms, $cache_id, TweetAggregator $tweet_aggregator, CtfCache $feed_cache, TweetSetModifier $tweet_set_modifier, ErrorReport $error_report)
	{
		$this->types_and_terms = $types_and_terms;

		$this->tweet_aggregator = $tweet_aggregator;

		$this->feed_cache = $feed_cache;

		$this->tweet_set_modifier = $tweet_set_modifier;

		$this->error_report = $error_report;

		$this->cache_id = $cache_id;

	}

	public function get_errors()
	{
		return $this->statuses['errors'];
	}

	public function set_errors( $errors_array )
	{
		$this->statuses['errors'] = $errors_array;
	}

	public function add_error( $message, $instructions )
	{
		$this->statuses['errors'][] = array(
			'message' => $message,
			'directions' => $instructions
		);
	}

	public function set_tweets($tweets)
	{
		$this->tweets = $tweets;
	}

	public function set_header_data($header_data)
	{
		$this->header_data = $header_data;
	}

	public function get_tweets()
	{
		return $this->tweets;
	}

	public function get_set_cache( $doing_cron_update = false )
	{
		$this->tweets = $this->feed_cache->get_transient( $this->cache_id );

		// Cache might come as empty or as an empty array string.
		if ( ! $this->tweets || $doing_cron_update ) {
			$this->tweets = $this->update_posts_cache();
			$endpoint = ! empty( $this->types_and_terms[0] ) ? $this->types_and_terms[0][0] : '';
			if ( $endpoint === 'usertimeline' && ! empty( $this->tweets[0]['user'] ) ) {
				$this->set_header_data( $this->tweets[0]['user'] );
				$this->update_header_cache();
			}
		} else {
			if ( ! is_array( $this->tweets ) ) {
				$this->tweets = json_decode( $this->tweets, true );
			}
			$header_data = $this->feed_cache->get_transient( $this->cache_id . '_header' );
			$this->set_header_data( $header_data );
		}

		$this->set_tweets($this->tweets);
	}

	public function paged_cache( $offset, $num ) {
		$this->tweets = false;
		$maybe_tweets = $this->feed_cache->get_transient( $this->cache_id );
		if ( $maybe_tweets ) {
			$tweets_array = json_decode( $maybe_tweets, true );
		} else {
			return false;
		}

		if ( ! empty( $tweets_array ) && is_array( $tweets_array ) ) {
			if ( $offset ) {
				$num_available = count( $tweets_array );
				if ( $offset >= $num_available) {
					return false;
				} else {
					if ( $num_available < $offset + $num ) {
						$length = $num_available - $offset < 0 ? 0 : $num_available - $offset;
						$this->tweets = array_slice( $tweets_array, $offset, $length );
					} else {
						$this->tweets = array_slice( $tweets_array, $offset );
					}

				}
			}
		}

		return true;
	}

	public function update_posts_cache()
	{
		foreach ( $this->types_and_terms as $type_and_term ) {
			$endpoint = $type_and_term[0];
			$term = $type_and_term[1];

			$remote_posts = $this->get_remote_posts( $endpoint, $term );

			if ( isset( $remote_posts[0]['id_str'] ) ) {
				$remote_posts = CTF_Feed::reduceTweetSetData( $remote_posts );

				$this->cache_single_posts_from_set($remote_posts, $endpoint, $term );
			}
		}

		$posts = $this->posts_from_db();

		$this->update_cache( $posts );

		return $posts;
	}

	public function posts_from_db() {
		$aggregator = new TweetAggregator();

		$posts = $aggregator->db_post_set( $this->types_and_terms );

		return $aggregator->normalize_db_post_set( $posts );
	}

	public function update_cache( $posts ) {
		$this->feed_cache->set_transient( $this->cache_id, json_encode( $posts ) );
	}

	public function update_header_cache()
	{
		$this->feed_cache->set_transient( $this->cache_id . '_header', json_encode( $this->header_data ), false, '' );
	}

	public function get_remote_posts( $endpoint, $term )
	{
		$ctf_options = get_option( 'ctf_options', array() );

		$site_access_token = ! empty( $ctf_options[ CTF_SITE_ACCESS_TOKEN_KEY ] ) ? $ctf_options[ CTF_SITE_ACCESS_TOKEN_KEY ] : false;

		if ( empty( $site_access_token ) ) {
			$this->error_report->process_error( 'could_not_authenticate', true );
			return array();
		}

		$request = new Request( $endpoint, $term, array(), $site_access_token );

		$response = $request->fetch();

		// Prevent showing fatal error if the site access token is invalid.
		if ( is_wp_error( $response ) ) {
			$this->error_report->process_error( $response, true );
			return [];
		}
		$error = $request->get_error();
		if ( ! empty( $error ) ) {
			$this->error_report->process_error( $error, true );
		}

		if ( ! empty( $response[0]['id_str'] ) ) {
			$this->tweet_set_modifier->set_tweet_set( $response );
			$this->tweet_set_modifier->hydrate_tweet_set();
			$response = $this->tweet_set_modifier->get_hydrated_tweet_set();
		}

		return $response;
	}

	public function cache_single_posts_from_set( $posts, $endpoint, $term )
	{
		foreach ( $posts as $single_tweet ) {
			$single_post_cache = new SinglePostCache( $single_tweet, $endpoint, $term );

			if ( ! $single_post_cache->db_record_exists() ) {
				$single_post_cache->store();
			} else {
				if ( ! $single_post_cache->db_record_exists_for_endpoint_and_term() ) {
					$single_post_cache->update_single( true );
				} else {
					$single_post_cache->update_single( false );
				}
			}
		}
	}

	public function get_post_set_page($page = 1)
	{
		$posts = $this->get_posts();

		$max = $this->settings['numPostDesktop'];
		if ($this->settings['numPostTablet'] > $this->settings['numPostDesktop']) {
			$max = $this->settings['numPostTablet'];
		}
		if ($this->settings['numPostMobile'] > $this->settings['numPostTablet']) {
			$max = $this->settings['numPostMobile'];
		}

		$offset = ($page - 1) * $max;
		return is_array( $posts ) ? array_slice($posts, $offset, $max) : [];
	}

	public function is_last_page($page)
	{
		$posts = $this->get_posts();
		$posts_per_page = $this->settings['numPostDesktop'];
		if ($this->settings['numPostTablet'] > $this->settings['numPostDesktop']) {
			$posts_per_page = $this->settings['numPostTablet'];
		}
		if ($this->settings['numPostMobile'] > $this->settings['numPostTablet']) {
			$posts_per_page = $this->settings['numPostMobile'];
		}

		return count($posts) <= ($page * (int) $posts_per_page);
	}
}