<?php
/**
 * Class TweetFilterer
 *
 * Filters Tweets.
 *
 * @since 2.2.2
 */
namespace TwitterFeed\SmashTwitter;

use TwitterFeed\CTF_Parse;

class TweetFilterer {

	public function maybe_filter_for_timeline( $tweets, $ctf_feed ) {

		if ( empty( $tweets ) ) {
			return $tweets;
		}
		if ( empty( $tweets[0] ) ) {
			return $tweets;
		}
		if ( ! is_array( $tweets[0] ) ) {
			return $tweets;
		}
		$types_and_terms = $ctf_feed->feed_options['feed_types_and_terms'];

		$timelines_included = array();
		foreach ( $types_and_terms as $type_and_term ) {
			if ( $type_and_term[0] !== 'usertimeline' ) {
				return $tweets;
			} else {
				$timelines_included[] = str_replace( '@', '', strtolower( $type_and_term[1] ) );
			}
		}

		$returnable = array();
		foreach ( $tweets as $tweet ) {
			$user = trim( strtolower( CTF_Parse::get_user_name( $tweet ) ) );

			if ( in_array( $user, $timelines_included, true ) ) {
				$returnable[] = $tweet;
			}
		}
		if ( empty( $returnable ) ) {
			return $tweets;
		}

		return $returnable;
	}
}