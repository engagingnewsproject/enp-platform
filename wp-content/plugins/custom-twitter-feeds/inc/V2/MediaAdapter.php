<?php

/**
 * Class for converting media attribute from Twitter API v1.1 to v2.
 *
 * @package twitter-api-v2
 */

namespace TwitterFeed\V2;

// Don't load directly.
if (! defined('ABSPATH')) {
	die('-1');
}

class MediaAdapter {

	/**
	 * Tweet data.
	 *
	 * @var array
	 */
	protected $tweet;

	/**
	 * Included media.
	 *
	 * @var array
	 */
	protected $included_media;

	/**
	 * Constructor.
	 *
	 * @param array $tweet Tweet data.
	 * @param array $included_media Included media data.
	 */
	public function __construct($tweet, $included_media)
	{
		$this->tweet          = $tweet;
		$this->included_media = $included_media;
	}

	/**
	 * Get media keys.
	 *
	 * @return array
	 */
	public function get_media_keys()
	{
		return ! empty($this->tweet['attachments']['media_keys']) ? $this->tweet['attachments']['media_keys'] : [];
	}

	/**
	 * Convert.
	 *
	 * @return array
	 */
	public function convert()
	{
		$media_array = [];

		foreach ($this->get_media_keys() as $media_key_index => $media_key_value) {
			$media_index = array_search(
				$this->tweet['attachments']['media_keys'][ $media_key_index ],
				array_column($this->included_media, 'media_key')
			);

			if ($media_index !== false) {
				$media = [
					'media_url_https' => $this->included_media[ $media_index ]['url'] ?? $this->included_media[ $media_index ]['preview_image_url'],
					'type'            => $this->included_media[ $media_index ]['type'],
				];

				if (isset($new_media['type']) && $new_media['type'] === 'video') {
					$media['video_info'] = [
						'variants' => $this->included_media[ $media_index ]['variants'],
					];
				}

				$media_array[] = $media;
			}
		}

		return $media_array;
	}
}
