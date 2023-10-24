<?php

/**
 * Class for converting user sub attribute from Twitter API v1.1 to v2.
 *
 * @package twitter-api-v2
 */

namespace TwitterFeed\V2;

// Don't load directly.
if (! defined('ABSPATH')) {
	die('-1');
}

/**
 * UserAdapter class.
 */
class UserAdapter extends Adapter {

	/**
	 * Get mapped fields.
	 *
	 * @return array
	 */
	public function getMappedFields()
	{
		return [
			'screen_name'             => 'username',
			'profile_image_url_https' => 'profile_image_url',
			'followers_count'         => [ 'public_metrics', 'followers_count' ],
			'statuses_count'          => [ 'public_metrics', 'tweet_count' ],
			'utc_offset'              => '',
		];
	}
}
