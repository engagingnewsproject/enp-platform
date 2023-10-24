<?php

/**
 * Class for converting tweet sub attribute from Twitter API v1.1 to v2.
 *
 * @package twitter-api-v2
 */

namespace TwitterFeed\V2;

// Don't load directly.
if (! defined('ABSPATH')) {
	die('-1');
}

/**
 * TweetAdapter class.
 */
class TweetAdapter extends Adapter {
	/**
	 * Get mapped fields.
	 *
	 * @return string[]
	 */
	public function getMappedFields()
	{
		return [
			'id_str'          => 'id',
			'conversation_id' => 'conversation_id_str',
		];
	}

	/**
	 * Remove retweet prefix.
	 *
	 * @param string $text Tweet text.
	 *
	 * @return string
	 */
	public function removeRetweetPrefix($text)
	{
		if (strpos($text, 'RT') !== 0) {
			return $text;
		}

		preg_match('/^RT\s*@[^:]*:(.*)/s', $text, $matches);

		if (! isset($matches[1])) {
			return $text;
		}

		return $matches[1];
	}

	/**
	 * Convert entity from v1.1 to v2 format.
	 *
	 * @param array $entity Entity.
	 *
	 * @return array
	 */
	public function convert($entity)
	{
		$entity['text'] = $this->removeRetweetPrefix($entity['text']);

		if (isset($entity['retweeted_status']['text'])) {
			$entity['retweeted_status']['text'] = $this->removeRetweetPrefix($entity['retweeted_status']['text']);
		}

		return parent::convert($entity);
	}
}
