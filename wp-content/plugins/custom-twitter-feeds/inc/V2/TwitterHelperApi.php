<?php

/**
 * Class for requesting oauth2 access token.
 *
 * @package twitter-api-v2
 */

namespace TwitterFeed\V2;

// Don't load directly.
if (! defined('ABSPATH')) {
	die('-1');
}

/**
 * TwitterHelperApi class.
 */
class TwitterHelperApi {

	/**
	 * Access token transient key.
	 *
	 * @const string
	 */
	const ACCESS_TOKEN_TRANSIENT_KEY = 'sb_twitter_oauth_bearer_access_token_cache';

	/**
	 * Consumer key.
	 *
	 * @var string
	 */
	private $consumer_key;

	/**
	 * Consumer secret.
	 * @var string
	 */
	private $consumer_secret;

	/**
	 * Constructor.
	 *
	 * @param string $consumer_key Consumer key.
	 * @param string $consumer_secret Consumer secret.
	 */
	public function __construct($consumer_key, $consumer_secret) {
		$this->consumer_key = $consumer_key;
		$this->consumer_secret = $consumer_secret;
	}

	/**
	 * Get access token using consumer key and consumer secret.
	 *
	 * @param string $consumer_key Consumer Key.
	 * @param string $consumer_secret Consumer Secret.
	 *
	 * @return false|string
	 */
	public function getAccessToken($consumer_key, $consumer_secret)
	{
		$url = 'https://api.twitter.com/oauth2/token';

		$args = [
			'headers' => [
				'Authorization' => 'Basic ' . base64_encode($consumer_key . ':' . $consumer_secret),
			],
			'body'    => [
				'grant_type' => 'client_credentials',
			],
		];

		$response = wp_remote_post($url, $args);

		if (is_wp_error($response)) {
			return false;
		}

		$remote_post_response_body = wp_remote_retrieve_body($response);
		$array_response            = json_decode($remote_post_response_body, ARRAY_A);

		if (empty($array_response['access_token'])) {
			return false;
		}

		return $array_response['access_token'];
	}

	/**
	 * Check if cached twitter access token is valid .
	 *
	 * @param string $twitter_oauth_access_token_cache Access token cache.
	 * @param string $current_hash Current hash.
	 *
	 * @return bool
	 */
	public function isCachedTwitterAccessTokenValid($twitter_oauth_access_token_cache, $current_hash)
	{
		// If nothing is saved into the cache, invalidate cache.
		if (false === $twitter_oauth_access_token_cache) {
			return false;
		}

		// If the reference hash is missing, invalidate cache.
		if (empty($twitter_oauth_access_token_cache['reference_hash'])) {
			return false;
		}

		// If the consumer key and consumer secret got changed from the settings, invalidate cache.
		if ($twitter_oauth_access_token_cache['reference_hash'] !== $current_hash) {
			return false;
		}

		// If the access token is empty, invalidate cache.
		if (empty($twitter_oauth_access_token_cache['access_token'])) {
			return false;
		}

		return true;
	}

	/**
	 * Get cached twitter access token.
	 *
	 * @return null|string
	 */
	public function getCachedTwitterAccessToken()
	{
		$twitter_oauth_access_token_cache = get_transient(static::ACCESS_TOKEN_TRANSIENT_KEY);
		$current_hash = md5($this->consumer_key . ':' . $this->consumer_secret);

		if (!$this->isCachedTwitterAccessTokenValid($twitter_oauth_access_token_cache, $current_hash)) {
			$twitter_oauth_access_token = $this->getAccessToken($this->consumer_key, $this->consumer_secret);

			if ($twitter_oauth_access_token) {
				set_transient(
					'sb_twitter_oauth_bearer_access_token_cache',
					[
						'access_token' => $twitter_oauth_access_token,
						'reference_hash' => $current_hash,
					],
					MONTH_IN_SECONDS
				);
			}

			return $twitter_oauth_access_token;
		}

		return $twitter_oauth_access_token_cache['access_token'];
	}
}
