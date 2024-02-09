<?php
/**
 * Overrides the default CtfOauthConnect class to add support for Twitter V2 endpoints.
 */
namespace TwitterFeed\V2;

// Don't load directly.
if (! defined('ABSPATH')) {
	die('-1');
}

/**
 * CtfOAuthConnect class.
 */
class CtfOauthConnect extends \TwitterFeed\CtfOauthConnect {
	/**
	 * Username.
	 *
	 * @var string
	 */
	protected $username;

	/**
	 * Get parameters.
	 *
	 * @var string
	 */
	protected $get_params;

	/**
	 * Set base URL.
	 *
	 * @return void
	 */
	public function setUrlBase()
	{
		switch ($this->feed_type) {
			case 'userslookup':
				$this->base_url = 'https://api.twitter.com/2/users/by/username/:username';
				break;
			case 'tweets':
				$this->base_url = 'https://api.twitter.com/2/tweets';
				break;
			case 'accountlookup':
				$this->base_url = 'https://api.twitter.com/1.1/account/verify_credentials.json';
				break;
			case 'hometimeline':
			default:
				$this->base_url = 'https://api.twitter.com/2/users/:id/tweets';
				break;
		}
	}

	/**
	 * Since the OAuth data is passed in an url, special characters need to be encoded
	 */
	protected function encodeHeader()
	{
		if ( ! CTF_DOING_SMASH_TWITTER && strpos($this->base_url, '/2/') !== false ) {
			$this->header = 'Authorization: Bearer ' . (new TwitterHelperApi($this->consumer_key, $this->consumer_secret))->getCachedTwitterAccessToken();;
			return;
		}

		$header = 'Authorization: OAuth ';
		$values = [];

		// Each element of the header needs to have its special characters encoded for
		// passing through a URL.
		foreach ($this->oauth as $key => $value) {
			if (
				in_array(
					$key,
					array(
					'oauth_consumer_key',
					'oauth_nonce',
					'oauth_signature',
					'oauth_signature_method',
					'oauth_timestamp',
					'oauth_token',
					'oauth_version',
					)
				)
			) {
				$values[] = "$key=\"" . rawurlencode($value) . "\"";
			}
		}

		$this->header = $header . implode(', ', $values);
	}

	/**
	 * Retrieve username id.
	 *
	 * @param string $username Username.
	 *
	 * @return string
	 */
	public function retrieveUsernameId($username)
	{
		$cached_usernames_key = 'sb_ctf_cached_usernames';
		$cached_usernames     = get_option($cached_usernames_key, []);

		if (! empty($cached_usernames[$username])) {
			return $cached_usernames[$username];
		}

		$connect = new CtfOauthConnect([
			'consumer_key' => $this->consumer_key,
			'consumer_secret' => $this->consumer_secret,
			'access_token' => $this->access_token,
			'access_token_secret' => $this->access_token_secret,
		], 'userslookup');
		$connect->setUrlBase();
		$connect->encodeHeader();
		$connect->setGetFields(['username' => $username]);
		$connect->performRequest();

		$user_data = json_decode($connect->json, ARRAY_A);

		if (isset($user_data['data']['id'])) {
			$username_id                   = $user_data['data']['id'];
			$cached_usernames[$username] = $username_id;
			update_option($cached_usernames_key, $cached_usernames);
			return $username_id;
		}

		return null;
	}

	/**
	 * Perform request.
	 *
	 * @return $this|mixed|string
	 */
	public function performRequest()
	{
		$url = $this->base_url . $this->get_fields;
		if ($this->feed_type === 'hometimeline') {
			$account_handle = get_option('ctf_options')['account_handle'];
			$url            = str_replace(':id', $this->retrieveUsernameId($account_handle), $url);
		}

		if ($this->feed_type === 'usertimeline') {
			if (strpos($url, ':id') !== false) {
				$account_handle = $this->username;
				$url            = str_replace(':id', $this->retrieveUsernameId($account_handle), $url);
			}
		}

		if ($this->feed_type === 'userslookup') {
			if (strpos($url, ':username') !== false) {
				$url = str_replace(':username', $this->username, $url);
			}
		}

		$this->buildOauth();
		$this->encodeHeader();

		$this->json = $this->wpHttpRequest($url);

		if (in_array($this->feed_type, [ 'usertimeline', 'hometimeline', 'search', 'hashtag' ], true)) {
			$this->json = $this->convertResponseToLegacy($this->json);
		}

		return $this;
	}

	/**
	 * Check if error.
	 *
	 * @param string $json Json.
	 *
	 * @return bool
	 */
	public function checkIfError($json)
	{
		$json_array = json_decode($json, JSON_OBJECT_AS_ARRAY);

		if (isset($json_array['response']['code']) && ( $json_array['response']['code'] !== 200 )) {
			return true;
		}

		if (isset($json_array['errors'][0]['code']) && ( $json_array['errors'][0]['code'] !== 200 )) {
			return true;
		}

		return false;
	}

	/**
	 * Encodes an array of GET field data into html characters for including in a URL
	 *
	 * @param array $get_fields array of GET fields that are compatible with the Twitter API
	 */
	public function setGetFields(array $get_fields)
	{
		$joined_parameters = [];

		if ($this->feed_type !== 'userslookup') {
			$parameters = [
				'tweet.fields' => [
					'attachments',
					'author_id',
					'context_annotations',
					'conversation_id',
					'created_at',
					'entities',
					'geo',
					'id',
					'in_reply_to_user_id',
					'lang',
					'referenced_tweets',
					'reply_settings',
					'source',
					'text',
					'withheld',
					'public_metrics',
				],
				'expansions'   => [
					'author_id',
					'attachments.media_keys',
					'entities.mentions.username',
					'in_reply_to_user_id',
					'referenced_tweets.id',
					'referenced_tweets.id.author_id',
				],
				'user.fields'  => [
					'description',
					'entities',
					'public_metrics',
					'profile_image_url',
					'verified',
				],
				'media.fields' => [
					'height',
					'media_key',
					'preview_image_url',
					'type',
					'url',
					'variants',
					'width',
				],
			];

			foreach ($parameters as $parameter_index => $parameter_values) {
				$joined_parameters[] = $parameter_index . '=' . implode(',', $parameter_values);
			}
		}

		if (! empty($get_fields['screen_name'])) {
			$this->username = str_replace('@', '', $get_fields['screen_name']);
		}

		if (! empty($get_fields['username'])) {
			$this->username = str_replace('@', '', $get_fields['username']);
		}

		if (isset($get_fields['ids'])) {
			$joined_parameters[] = 'ids=' . rawurlencode(implode(',', $get_fields['ids']));
		}

		if (! empty($get_fields['q'])) {
			$query = $get_fields['q'];

			if (strpos($query, '-filter') !== false) {
				$query = substr($query, 0, strpos($query, '-filter'));
			}
			$joined_parameters[] = 'query=' . rawurlencode($query);
		}

		if (! empty($get_fields['max_id'])) {
			$joined_parameters[] = 'until_id=' . rawurlencode($get_fields['max_id']);
		}

		if (! empty($get_fields['count'])) {
			$joined_parameters[] = 'max_results=' . rawurlencode(min($get_fields['count'], 100));
		}

		$this->get_fields = '?' . implode('&', $joined_parameters);
	}

	/**
	 * Convert response to legacy.
	 *
	 * @param string $json Json.
	 *
	 * @return false|string
	 */
	public function convertResponseToLegacy($json)
	{
		$json_array = json_decode($json, ARRAY_A);

		// Stop converting if it's an error.
		if (! isset($json_array['data'])) {
			return $json;
		}

		$referenced_tweets_ids = [];
		$data_array            = $json_array['data'];

		foreach ($json_array['data'] as $tweet) {
			if (empty($tweet['referenced_tweets'])) {
				continue;
			}

			$referenced_tweets_ids = array_merge(
				$referenced_tweets_ids,
				array_column($tweet['referenced_tweets'], 'id')
			);
		}

		$unique_referenced_tweets_ids = array_unique($referenced_tweets_ids);
		$indexed_referenced_tweet_ids = [];
		$referenced_tweets_data       = [];

		if (! empty($unique_referenced_tweets_ids)) {
			$connect = new CtfOauthConnect([
				'consumer_key' => $this->consumer_key,
				'consumer_secret' => $this->consumer_secret,
				'access_token' => $this->access_token,
				'access_token_secret' => $this->access_token_secret,
			], 'tweets');
			$connect->setUrlBase();
			$connect->setGetFields([ 'ids' => $unique_referenced_tweets_ids ]);
			$connect->performRequest();
			$referenced_tweets_data          = json_decode($connect->json, ARRAY_A);

			if (
				!empty($referenced_tweets_data['includes']['media'])
				&& is_array($referenced_tweets_data['includes']['media'])
			) {
				$json_array['includes']['media'] = array_merge(
					!empty($json_array['includes']['media'])
					&& is_array($json_array['includes']['media'])
						? $json_array['includes']['media']
						: [],
					$referenced_tweets_data['includes']['media']
				);
			}

			if ( is_array($referenced_tweets_data['data']) ) {
				$indexed_referenced_tweet_ids = array_column($referenced_tweets_data['data'], 'id');
			}
		}

		$included_users     = $json_array['includes']['users'];
		$included_users_ids = array_column($included_users, 'id');

		foreach ($data_array as $index => $tweet) {
			$mentioned_user_id = isset($tweet['entities']['mentions'][0]['id']) ? $tweet['entities']['mentions'][0]['id'] : null;

			$included_user_index = $mentioned_user_id ? array_search($mentioned_user_id, $included_users_ids) : null;

			$referenced_tweet_type = isset($tweet['referenced_tweets'][0]['type']) ? $tweet['referenced_tweets'][0]['type'] : null;
			$referenced_tweet_id   = isset($tweet['referenced_tweets'][0]['id']) ? $tweet['referenced_tweets'][0]['id'] : null;

			$media_items = isset($json_array['includes']['media']) ? (new MediaAdapter($tweet, $json_array['includes']['media']))->convert() : [];

			if (in_array($referenced_tweet_type, [ 'retweeted', 'replied_to' ], true)) {
				$referenced_tweet_index = array_search($referenced_tweet_id, $indexed_referenced_tweet_ids);
				$retweet                = $referenced_tweets_data['data'][ $referenced_tweet_index ];

				// The retweeted tweet media will override the retweet media. Users cannot attach custom media to retweet.
				$included_media = !empty($json_array['includes']['media']) && is_array($json_array['includes']['media'])
					? $json_array['includes']['media']
					: [];
				$media_items = (new MediaAdapter($retweet, $included_media))->convert();

				$data_array[ $index ]['retweeted_status']['id_str']                     = $referenced_tweet_id;
				$data_array[ $index ]['retweeted_status']['text']                       = $data_array[ $index ]['text'];
				$data_array[ $index ]['retweeted_status']['created_at']                 = $data_array[ $index ]['created_at'];
				$data_array[ $index ]['retweeted_status']['extended_entities']['media'] = $media_items;
				$data_array[ $index ]['retweeted_status']['retweet_count']              = $retweet['public_metrics']['retweet_count'];
				$data_array[ $index ]['retweeted_status']['favorite_count']             = $retweet['public_metrics']['like_count'];

				if (is_int($included_user_index)) {
					$data_array[ $index ]['retweeted_status']['user'] = (new UserAdapter())->convert($json_array['includes']['users'][ $included_user_index ]);
				}
			} elseif ($referenced_tweet_type === 'quoted') {
				$referenced_tweet_index = array_search($referenced_tweet_id, $indexed_referenced_tweet_ids);
				$retweet                = $referenced_tweets_data['data'][ $referenced_tweet_index ];

				$included_media = !empty($json_array['includes']['media']) && is_array($json_array['includes']['media'])
					? $json_array['includes']['media']
					: [];
				$media_items = ! empty($media_items)
					? $media_items
					: ( new MediaAdapter($retweet, $included_media) )->convert();

				$retweet_author_index = array_search($retweet['author_id'], array_column($referenced_tweets_data['includes']['users'], 'id'));
				$retweet_author_user  = $referenced_tweets_data['includes']['users'][ $retweet_author_index ];

				$author_index = array_search($retweet['author_id'], array_column($json_array['includes']['users'], 'id'));
				$author_user  = $json_array['includes']['users'][ $author_index ];

				$data_array[ $index ]['quoted_status'] = [
					'id_str'            => $referenced_tweet_id,
					'created_at'        => $tweet['created_at'],
					'text'              => $retweet['text'],
					'extended_entities' => [
						'media' => $media_items,
					],
				];

				if ($author_index !== false && $retweet_author_index !== false) {
					$data_array[ $index ]['quoted_status']['user'] = (new UserAdapter())->convert(
						array_merge(
							$author_user,
							$retweet_author_user
						)
					);
				}

				$data_array[ $index ]['retweet_count']              = $retweet['public_metrics']['retweet_count'];
				$data_array[ $index ]['favorite_count']             = $retweet['public_metrics']['like_count'];
			} else {
				$data_array[ $index ]['extended_entities']['media'] = $media_items;
			}

			$data_array[ $index ]['retweet_count']  = $data_array[ $index ]['public_metrics']['retweet_count'];
			$data_array[ $index ]['favorite_count'] = $data_array[ $index ]['public_metrics']['like_count'];

			$author_id  = $tweet['author_id'];
			$user_index = array_search($author_id, $included_users_ids);
			if (is_int($user_index)) {
				$data_array[ $index ]['user'] = ( new UserAdapter() )->convert( $included_users[ $user_index ] );
			}
			$data_array[ $index ]         = (new TweetAdapter())->convert($data_array[$index]);
		}

		$json_array['data'] = $data_array;
		return wp_json_encode($json_array);
	}
}
