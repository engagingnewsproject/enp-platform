<?php
/**
 * Class TweetSetModifier
 *
 *
 *
 * @since 2.1
 */
namespace TwitterFeed\SmashTwitter;

use TwitterFeed\CTF_Parse;

class TweetSetModifier
{
	private $tweet_set;


	private $hydrated_tweet_set;

	public function set_tweet_set( $tweet_set ) {
		$this->tweet_set = $tweet_set;
	}

	public function get_hydrated_tweet_set() {
		return $this->hydrated_tweet_set;
	}

	public function hydrate_tweet_set() {
		if ( empty( $this->tweet_set ) ) {
			return;
		}

		$referenceable_tweet_set = array();
		foreach ( $this->tweet_set as $tweet ) {
			$referenceable_tweet_set[ CTF_Parse::get_post_id( $tweet ) ] = $tweet;
		}

		$flagged_quoted_tweets = array();
		$flagged_retweeted_quoted_tweets = array();
		$flagged_retweets = array();

		$this->hydrated_tweet_set = array();
		foreach ( $referenceable_tweet_set as $id => $tweet ) {
			// if this is a tweet that was quoted we want to remove it from the feed (unless quoted by the same author)
			// Quoted tweets are instead added to the tweet that is doing the quoting through this hydration process
			$does_not_belong = in_array( (string)$id, $flagged_quoted_tweets, true ) && CTF_Parse::get_author_screen_name( $tweet ) !== CTF_Parse::get_author_screen_name( $referenceable_tweet_set[ $id ] );
			if ( ! $does_not_belong ) {
				$does_not_belong = in_array( (string)$id, $flagged_retweets, true );
			}
			if ( ! $does_not_belong ) {
				$does_not_belong = in_array( (string)$id, $flagged_retweeted_quoted_tweets, true );
			}
			if ( ! $does_not_belong ) {
				$hydrated_tweet = $tweet;
				if ( ! empty( $tweet['is_quote_status'] )
				     && ! empty( $tweet['quoted_status_id_str'] )
				     && ! empty( $referenceable_tweet_set[ $tweet['quoted_status_id_str'] ] ) ) {
					$flagged_quoted_tweets[] = $tweet['quoted_status_id_str'];
					$hydrated_tweet['quoted_status'] = $referenceable_tweet_set[ $tweet['quoted_status_id_str'] ];
				}
				if ( ! empty( $tweet['retweeted_status_id_str'] )
				     && ! empty( $tweet['retweeted_status_id_str'] )
				     && ! empty( $referenceable_tweet_set[ $tweet['retweeted_status_id_str'] ] ) ) {
					$flagged_retweets[] = $tweet['retweeted_status_id_str'];
					$hydrated_tweet['retweeted_status'] = $referenceable_tweet_set[ $tweet['retweeted_status_id_str'] ];
					if ( strpos( $hydrated_tweet['text'], 'RT ' ) === 0 ) {
						$hydrated_tweet['text'] = str_replace( 'RT ', '', $hydrated_tweet['text'] );
						$hydrated_tweet['full_text'] = str_replace( 'RT ', '', $hydrated_tweet['full_text'] );
					}
					if ( ! empty( $hydrated_tweet['retweeted_status']['is_quote_status'] )
					     && ! empty( $hydrated_tweet['retweeted_status']['quoted_status_id_str'] )
					     && ! empty( $referenceable_tweet_set[ $hydrated_tweet['retweeted_status']['quoted_status_id_str'] ] ) ) {
						$flagged_retweeted_quoted_tweets[] = $hydrated_tweet['retweeted_status']['quoted_status_id_str'];

						$hydrated_tweet['retweeted_status']['quoted_status'] = $referenceable_tweet_set[ $tweet['quoted_status_id_str'] ];
					}
				}
				$this->hydrated_tweet_set[] = $hydrated_tweet;
			}
		}
	}
}