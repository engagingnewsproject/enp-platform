<?php
/**
 * Class CtfFeed
 *
 * Creates the settings for the feed and outputs the html
 */

// Don't load directly
if ( ! defined( 'ABSPATH' ) ) {
    die( '-1' );
}

class CtfFeed
{
    /**
     * @var array
     */
    public $errors = array();

    /**
     * @var array
     */
    protected $atts;

    /**
     * @var string
     */
    protected $last_id_data;

    private $num_needed_input;

    /**
     * @var mixed|void
     */
    protected $db_options;

    /**
     * @var array
     */
    public $feed_options = array();

    /**
     * @var mixed|void
     */
    public $missing_credentials;

    /**
     * @var string
     */
    public $transient_name;

    /**
     * @var bool
     */
    protected $transient_data = false;

    /**
     * @var int
     */
    private $num_tweets_needed;

    private $check_for_duplicates = false;

    /**
     * @var array
     */
    public $tweet_set;

    /**
     * @var object
     */
    public $api_obj;

    /**
     * @var string
     */
    public $feed_html;

	private $persistent_index;


	/**
     * retrieves and sets options that apply to the feed
     *
     * @param array $atts           data from the shortcode
     * @param string $last_id_data  the last visible tweet on the feed, empty string if first set
     * @param int $num_needed_input this number represents the number left to retrieve after the first set
     */
    public function __construct( $atts, $last_id_data, $num_needed_input )
    {
        $this->atts = $atts;
        $this->last_id_data = $last_id_data;
        $this->num_needed_input = $num_needed_input;
        $this->db_options = get_option( 'ctf_options', array() );
    }

    /**
     * creates and returns all of the data needed to generate the output for the feed
     *
     * @param array $atts           data from the shortcode
     * @param string $last_id_data  the last visible tweet on the feed, empty string if first set
     * @param int $num_needed_input this number represents the number left to retrieve after the first set
     * @return CtfFeed                 the complete object for the feed
     */
    public static function init( $atts, $last_id_data = '', $num_needed_input = 0, $ids_to_remove = array(), $persistent_index = 1  )
    {
        $feed = new CtfFeed( $atts, $last_id_data, $num_needed_input );
        $feed->setFeedOptions();

	    $feed->setCacheTypeOption();
	    if ( $feed->feed_options['persistentcache'] ) {
		    $feed->persistent_index = $persistent_index;
	    }

        $feed->setTweetSet();
        return $feed;
    }

    /**
     * creates all of the feed options with shortcode settings having the highest priority
     */
    protected function setFeedOptions()
    {
        $this->setFeedTypeAndTermOptions();

        $bool_false = array (
            'have_own_tokens',
            'includereplies',
            'ajax_theme',
            'width_mobile_no_fixed',
            'disablelinks',
            'linktexttotwitter',
            'creditctf',
	        'selfreplies',
	        'disableintents',
	        'shorturls'
        );
        $this->setStandardBoolOptions( $bool_false, false );

        $this->setAccessTokenAndSecretOptions();
        $this->setConsumerKeyAndSecretOptions();

        $db_only =  array(
            'request_method'
        );
        $this->setDatabaseOnlyOptions( $db_only );

        $this->setStandardTextOptions( 'num', 5 );

        $standard_text = array(
            'class',
            'headertext',
            'dateformat',
            'datecustom',
            'mtime',
            'htime',
            'nowtime'
        );
        $this->setStandardTextOptions( $standard_text, '' );

        $this->setStandardTextOptions( 'retweetedtext', __( 'Retweeted', 'custom-twitter-feeds' ) );
	    $this->setStandardTextOptions( 'font_method', 'svg' );
        $this->setStandardTextOptions( 'multiplier', 1.25 );
        $this->setStandardTextOptions( 'twitterlinktext', 'Twitter' );
	    $this->setStandardTextOptions( 'gdpr', 'auto' );

        $this->setStandardTextOptions( 'buttontext', __( 'Load More...', 'custom-twitter-feeds' ) );
	    $this->setStandardTextOptions( 'textlength', 280 );
        $text_size = array(
            'authortextsize',
            'tweettextsize',
            'datetextsize',
            'quotedauthorsize',
            'iconsize',
	        'logosize'
        );
        $this->setTextSizeOptions( $text_size );

        $text_weight = array(
            'authortextweight',
            'tweettextweight',
            'datetextweight',
            'quotedauthorweight'
        );
        $this->setStandardStyleProperty( $text_weight, 'font-weight' );

        $text_color = array(
            'headertextcolor',
            'textcolor',
            'linktextcolor',
            'iconcolor',
	        'logocolor',
            'buttontextcolor'
        );
        $this->setStandardStyleProperty( $text_color, 'color' );

        $bg_color = array(
            'bgcolor',
            'tweetbgcolor',
            'headerbgcolor',
            'buttoncolor'
        );
        $this->setStandardStyleProperty( $bg_color, 'background-color' );

        $bool_true = array(
	        'persistentcache',
	        'showbutton',
            'showbio',
	        'showheader'
        );
        $this->setStandardBoolOptions( $bool_true, true );

        $this->setDimensionOptions();
        $this->setCacheTimeOptions();
        $this->setIncludeExcludeOptions();

	    if ( CTF_GDPR_Integrations::doing_gdpr( $this->feed_options ) ) {
		    CTF_GDPR_Integrations::init();
	    }
    }

    /**
     * uses the feed options to set the the tweets in the feed by using
     * an existing set in a cache or by retrieving them from Twitter
     */
    protected function setTweetSet()
    {
        $this->setTransientName();
        $success = $this->maybeSetTweetsFromCache();

        if ( ! $success ) {
            $this->maybeSetTweetsFromTwitter();
        }

        $this->num_tweets_needed = $this->numTweetsNeeded();
    }

    /**
     * the access token and secret must be set in order for the feed to work
     * this function processes the user input and sets a flag if none are entered
     */
    private function setAccessTokenAndSecretOptions()
    {
        $this->feed_options['access_token'] = isset( $this->db_options['access_token'] ) && strlen( $this->db_options['access_token'] ) > 30 ? $this->db_options['access_token'] : 'missing';
        $this->feed_options['access_token_secret'] = isset( $this->db_options['access_token_secret'] ) && strlen( $this->db_options['access_token_secret'] ) > 30 ? $this->db_options['access_token_secret'] : 'missing';

        // verify that access token and secret have been entered
        $this->setMissingCredentials();
    }

    /**
     * generates the flag if there are missing access tokens
     */
    private function setMissingCredentials() {
        if ( $this->feed_options['access_token'] == 'missing' || $this->feed_options['access_token_secret'] == 'missing' ) {
            $this->missing_credentials = true;
        } else {
            $this->missing_credentials = false;
        }
    }

    /**
     * processes the consumer key and secret options
     */
    protected function setConsumerKeyAndSecretOptions()
    {
        if ( $this->feed_options['have_own_tokens'] ) {
            $this->feed_options['consumer_key'] = isset( $this->db_options['consumer_key'] ) && strlen( $this->db_options['consumer_key'] ) > 15 ? $this->db_options['consumer_key'] : 'FPYSYWIdyUIQ76Yz5hdYo5r7y';
            $this->feed_options['consumer_secret'] = isset( $this->db_options['consumer_secret'] ) && strlen( $this->db_options['consumer_secret'] ) > 30 ? $this->db_options['consumer_secret'] : 'GqPj9BPgJXjRKIGXCULJljocGPC62wN2eeMSnmZpVelWreFk9z';
        } else {
            $this->feed_options['consumer_key'] ='FPYSYWIdyUIQ76Yz5hdYo5r7y';
            $this->feed_options['consumer_secret'] = 'GqPj9BPgJXjRKIGXCULJljocGPC62wN2eeMSnmZpVelWreFk9z';
        }
    }

    /**
     * determines what value to use and saves it for the appropriate key in the feed_options array
     *
     * @param $options mixed        the key or array of keys to be set
     * @param $options_page string  options page this setting is set on
     * @param string $default       default value to use if there is no user input
     */
    public function setDatabaseOnlyOptions( $options, $default = '' )
    {
        if ( is_array( $options ) ) {
            foreach ( $options as $option ) {
                $this->feed_options[$option] = isset( $this->db_options[$option] ) && ! empty( $this->db_options[$option] ) ? $this->db_options[$option] : $default;
            }
        } else {
            $this->feed_options[$options] = isset( $this->db_options[$options] ) && ! empty( $this->db_options[$options] ) ? $this->db_options[$options] : $default;
        }
    }

    /**
     * determines what value to use and saves it for the appropriate key in the feed_options array
     *
     * @param $options mixed        the key or array of keys to be set
     * @param $options_page string  options page this setting is set on
     * @param string $default       default value to use if there is no user input
     */
    public function setStandardTextOptions( $options, $default = '' )
    {
        if ( is_array( $options ) ) {
            foreach ( $options as $option ) {
                $this->feed_options[$option] = isset( $this->atts[$option] ) ? esc_attr( __( $this->atts[$option], 'custom-twitter-feeds' ) ) : ( isset( $this->db_options[$option] ) ?  esc_attr( $this->db_options[$option] )  : $default );
            }
        } else {
            $this->feed_options[$options] = isset( $this->atts[$options] ) ? esc_attr( __( $this->atts[$options], 'custom-twitter-feeds' ) ) : ( isset( $this->db_options[$options] ) ?  esc_attr( $this->db_options[$options] )  : $default );
        }
    }

    /**
     * creates the appropriate style attribute string for the text size setting
     *
     * @param $value mixed  pixel size or other that the user has selected
     * @return string       string for the style attribute
     */
    public static function processTextSizeStyle( $value )
    {
        if ( $value == '' ) {
            return '';
        }
        $processed_value = $value == 'inherit' ? '' : 'font-size: ' . $value . 'px;';

        return $processed_value;
    }

    /**
     * determines what value to use and saves it for the appropriate key in the feed_options array
     *
     * @param $options mixed        the key or array of keys to be set
     * @param string $default       default value to use if there is no user input
     */
    public function setTextSizeOptions( $options, $default = '' )
    {
        if ( is_array( $options ) ) {
            foreach ( $options as $option ) {
                $this->feed_options[$option] = isset( $this->atts[$option] ) ? $this->processTextSizeStyle( esc_attr( $this->atts[$option] ) ) : ( isset( $this->db_options[$option] ) ? $this->processTextSizeStyle( esc_attr( $this->db_options[$option] ) ) : $default );
            }
        } else {
            $this->feed_options[$options] = isset( $this->atts[$options] ) ? $this->processTextSizeStyle( esc_attr( $this->atts[$options] ) ) : ( isset( $this->db_options[$options] ) ? $this->processTextSizeStyle( esc_attr( $this->db_options[$options] ) ) : $default );
        }
    }

    /**
     * determines what value to use and saves it for the appropriate key in the feed_options array
     *
     * @param $options mixed    the key or array of keys to be set
     * @param $property string  name of the property to be set
     * @param string $default   default value to use if there is no user input
     */
    public function setStandardStyleProperty( $options, $property, $default = '' )
    {
        if ( is_array( $options ) ) {
            foreach ( $options as $option ) {
                $this->feed_options[$option] = isset( $this->atts[$option] ) && $this->atts[$option] != 'inherit' ? $property . ': ' . esc_attr( $this->atts[$option] ) . ';'  : ( isset( $this->db_options[$option] ) && $this->db_options[$option] != '#' && $this->db_options[$option] != '' && $this->db_options[$option] != 'inherit' ? $property . ': ' . esc_attr( $this->db_options[$option] ) . ';' : $default );
            }
        } else {
            $this->feed_options[$options] = isset( $this->atts[$options] ) && $this->atts[$options] != 'inherit' ? $property . ': ' . esc_attr( $this->atts[$options] ) . ';'  : ( isset( $this->db_options[$options] ) && $this->db_options[$options] != '#' && $this->db_options[$options] != '' && $this->db_options[$options] != 'inherit' ? $property . ': ' . esc_attr( $this->db_options[$options] ) . ';' : $default );
        }
    }

    /**
     * determines what value to use and saves it for the appropriate key in the feed_options array
     *
     * @param $options mixed        the key or array of keys to be set
     * @param bool|true $default    default value to use if there is no user input
     */
    public function setStandardBoolOptions( $options, $default = true )
    {
        if ( is_array( $options ) ) {
            foreach ( $options as $option ) {
                $this->feed_options[$option] = isset( $this->atts[$option] ) ? ( $this->atts[$option] === 'true'  ) : ( isset( $this->db_options[$option] ) ? (bool) $this->db_options[$option] : (bool) $default );
            }
        } else {
            $this->feed_options[$options] = isset( $this->atts[$options] ) ? esc_attr( $this->atts[$options] ) : ( isset( $this->db_options[$options] ) ?  esc_attr( $this->db_options[$options] )  : $default );
        }
    }

    /**
     * sets the width and height of the feed based on user input
     */
    public function setDimensionOptions()
    {
        $this->feed_options['width'] = isset( $this->atts['width'] ) ? 'width: '. esc_attr( $this->atts['width'] ) .';' : ( ( isset( $this->db_options['width'] ) && $this->db_options['width'] != '' ) ? 'width: '. esc_attr( $this->db_options['width'] ) . ( isset( $this->db_options['width_unit'] ) ? esc_attr( $this->db_options['width_unit'] ) : '%' ) . ';' : '' );
        $this->feed_options['height'] = isset( $this->atts['height'] ) ? 'height: '. esc_attr( $this->atts['height'] ) .';' : ( ( isset( $this->db_options['height'] ) && $this->db_options['height'] != '' ) ? 'height: '. esc_attr( $this->db_options['height'] ) . ( isset( $this->db_options['height_unit'] ) ? esc_attr( $this->db_options['height_unit'] ) : 'px' ) . ';' : '' );
    }

    /**
     * sets the cache time based on user input
     */
    public function setCacheTimeOptions()
    {
        $user_cache = isset( $this->db_options['cache_time'] ) ? ( $this->db_options['cache_time'] * $this->db_options['cache_time_unit'] ) : HOUR_IN_SECONDS;

        if ( $this->feed_options['have_own_tokens'] ) {
	        $this->feed_options['cache_time'] = max( $user_cache, 60 );
        } else {
	        $this->feed_options['cache_time'] = max( $user_cache, 3600 );
        }
    }


    /**
     * sets the number of tweets to retrieve
     */
    public function setTweetsToRetrieve()
    {
        $min_tweets_to_retrieve = 10;

        if ( $this->num_needed_input < 1 ) {
            if ( $this->feed_options['includereplies'] ) {
                $this->feed_options['count'] = $this->feed_options['num'];
            } else {
                if ( $this->feed_options['num'] < 10 ) {
                    $this->feed_options['count'] = max( round( $this->feed_options['num'] * (float)$this->feed_options['multiplier'] * 1.6 ), $min_tweets_to_retrieve );
                } elseif ( $this->feed_options['num'] < 30 ) {
                    $this->feed_options['count'] = round( $this->feed_options['num'] * (float)$this->feed_options['multiplier'] * 1.2 );
                } else {
                    $this->feed_options['count'] = round( $this->feed_options['num'] * (float)$this->feed_options['multiplier'] );
                }
            }
        } else {
            $this->feed_options['count'] = max( $this->num_needed_input, 50 );
            $this->feed_options['num'] = $this->num_needed_input;
        }

    }

    /**
     * sets the feed type and associated parameter
     */
    public function setFeedTypeAndTermOptions()
    {
        $this->feed_options['type'] = '';
        $this->feed_options['feed_term'] = '';
        $this->feed_options['screenname'] = isset( $this->db_options['usertimeline_text'] ) ? $this->db_options['usertimeline_text'] : '';

        if ( isset( $this->atts['home'] ) && $this->atts['home'] == 'true' ) {
            $this->feed_options['type'] = 'hometimeline';
        }
        if ( isset( $this->atts['screenname'] ) ) {
            $this->feed_options['type'] = 'usertimeline';
            $this->feed_options['feed_term'] = isset( $this->atts['screenname'] ) ? ctf_validate_usertimeline_text( $this->atts['screenname'] ) : ( ( isset( $this->db_options['usertimeline_text'] ) ) ? $this->db_options['usertimeline_text'] : '' );
            $this->feed_options['screenname'] = $this->feed_options['feed_term'];
        }
        if ( isset( $this->atts['search'] ) || isset( $this->atts['hashtag'] ) ) {
            $this->feed_options['type'] = 'search';
            $this->working_term = isset( $this->atts['hashtag'] ) ? $this->atts['hashtag'] : ( isset( $this->atts['search'] ) ? $this->atts['search'] : '' );
            $this->feed_options['feed_term'] = isset( $this->working_term ) ? ctf_validate_search_text( $this->working_term ) . ' -filter:retweets' : ( ( isset( $this->db_options['search_text'] ) ) ? $this->db_options['search_text'] . ' -filter:retweets' : '' );
            $this->check_for_duplicates = true;
        }

        if ( $this->feed_options['type'] == '' ) {
            $this->feed_options['type'] = isset( $this->db_options['type'] ) ? $this->db_options['type'] : 'usertimeline';
            switch ( $this->feed_options['type'] ) {
                case 'usertimeline':
                    $this->feed_options['feed_term'] = isset( $this->db_options['usertimeline_text'] ) ? $this->db_options['usertimeline_text'] : '';
                    break;
                case 'hometimeline':
                    $this->feed_options['type'] = 'hometimeline';
                    break;
                case 'search':
                    $this->feed_options['feed_term'] = isset( $this->db_options['search_text'] ) ? $this->db_options['search_text'] . ' -filter:retweets' : '';
                    $this->check_for_duplicates = true;
                    break;
            }
        }
    }

    /**
     * sets the visible parts of each tweet for the feed
     */
    public function setIncludeExcludeOptions()
    {
        $this->feed_options['tweet_excludes'] = array();
        $this->feed_options['tweet_includes'] = isset( $this->atts['include'] ) ? explode( ',', str_replace( ', ', ',', esc_attr( $this->atts['include'] ) ) ) : array();

	    if ( empty( $this->feed_options['tweet_includes'][0] ) ) {
            $this->feed_options['tweet_excludes'] = isset( $this->atts['exclude'] ) ? explode( ',', str_replace( ', ', ',', esc_attr( $this->atts['exclude'] ) ) ) : array();
        }
        if ( empty( $this->feed_options['tweet_excludes'][0] ) && empty( $this->feed_options['tweet_includes'][0] ) ) {
            $this->feed_options['tweet_includes'][] = isset( $this->db_options['include_retweeter'] ) && $this->db_options['include_retweeter'] == false ? null : 'retweeter';
            $this->feed_options['tweet_includes'][] = isset( $this->db_options['include_avatar'] ) && $this->db_options['include_avatar'] == false ? null : 'avatar';
            $this->feed_options['tweet_includes'][] = isset( $this->db_options['include_author'] ) && $this->db_options['include_author'] == false ? null : 'author';
            $this->feed_options['tweet_includes'][] = isset( $this->db_options['include_text'] ) && $this->db_options['include_text'] == false ? null : 'text';
	        $this->feed_options['tweet_includes'][] = isset( $this->db_options['include_media_placeholder'] ) && $this->db_options['include_media_placeholder'] == false ? null : 'placeholder';
	        $this->feed_options['tweet_includes'][] = isset( $this->db_options['include_date'] ) && $this->db_options['include_date'] == false ? null : 'date';
            $this->feed_options['tweet_includes'][] = isset( $this->db_options['include_actions'] ) && $this->db_options['include_actions'] == false ? null : 'actions';
            $this->feed_options['tweet_includes'][] = isset( $this->db_options['include_twitterlink'] ) && $this->db_options['include_twitterlink'] == false ? null : 'twitterlink';
            $this->feed_options['tweet_includes'][] = isset( $this->db_options['include_linkbox'] ) && $this->db_options['include_linkbox'] == false ? null : 'linkbox';
	        $this->feed_options['tweet_includes'][] = isset( $this->db_options['include_logo'] ) && $this->db_options['include_logo'] == false ? null : 'logo';
        }

    }

    /**
     * sets the transient name for the caching system
     */
    public function setTransientName()
    {
        $last_id_data = $this->last_id_data;
        $num = isset( $this->feed_options['num'] ) ? $this->feed_options['num'] : '';

        switch ( $this->feed_options['type'] ) {
            case 'hometimeline' :
                $this->transient_name = 'ctf_' . $last_id_data . 'hometimeline'. $num;
                break;
            case 'usertimeline' :
                $screenname = isset( $this->feed_options['feed_term'] ) ? $this->feed_options['feed_term'] : '';
                $this->transient_name = substr( 'ctf__' . $last_id_data . $screenname . $num, 0, 45 );
                break;
            case 'search' :
                $hashtag = isset( $this->feed_options['feed_term'] ) ? $this->feed_options['feed_term'] : '';
	            $hashtag = str_replace( ' -filter:retweets', '', $hashtag );
                $this->transient_name = substr( 'ctf_' . $last_id_data . $hashtag . $num, 0, 45 );
                break;
        }
    }

	public function setCacheTypeOption() {
		if ( $this->feed_options['persistentcache'] && ( $this->feed_options['type'] == 'search' || $this->feed_options['type'] == 'hashtag' ) ) {
			$this->feed_options['persistentcache'] = true;
		} else {
			$this->feed_options['persistentcache'] = false;
		}
	}

    /**
     * checks the data available in the cache to make sure it seems to be valid
     *
     * @return bool|string  false if the cache is valid, error otherwise
     */
    private function validateCache()
    {
        if ( isset( $this->transient_data[0] ) ) {
            return false;
        } else {
            return 'invalid cache';
        }
    }

    /**
     * will use the cached data in the feed if data seems to be valid and user
     * wants to use caching
     *
     * @return bool|mixed   false if none is set, tweet set otherwise
     */
    public function maybeSetTweetsFromCache()
    {
	    if ( $this->feed_options['persistentcache'] && ( $this->feed_options['type'] == 'search' || $this->feed_options['type'] == 'hashtag' ) ) {
		    $persistent_cache_tweets = $this->persistentCacheTweets();
		    if ( is_array( $persistent_cache_tweets ) ) {
			    $this->transient_data = array_slice( $persistent_cache_tweets, ( $this->persistent_index - $this->feed_options['num'] - 1 ) , $this->persistent_index );
		    } else {
			    $this->transient_data = $persistent_cache_tweets;
		    }
	    } else {
		    $this->transient_data = get_transient( $this->transient_name );
		    if ( ! is_array( $this->transient_data ) ) {
			    $this->transient_data = json_decode( $this->transient_data, $assoc = true );
		    }

		    if ( $this->feed_options['cache_time'] <= 0 ) {
			    return $this->tweet_set = false;
		    }
	    }
        // validate the transient data
        if ( $this->transient_data ) {
            $this->errors['cache_status'] = $this->validateCache();
            if ( $this->errors['cache_status'] === false ) {
                return $this->tweet_set = $this->transient_data;
            } else {
                return $this->tweet_set = false;
            }
        } else {
            $this->errors['cache_status'] = 'none found';
            return $this->tweet_set = false;
        }
    }

	private function persistentCacheTweets()
	{
		// if cache exists get cached data
		$includewords = ! empty( $this->feed_options['includewords'] ) ? substr( str_replace( array( ',', ' ' ), '', $this->feed_options['includewords'] ), 0, 10 ) : '';
		$excludewords = ! empty( $this->feed_options['excludewords'] ) ? substr( str_replace( array( ',', ' ' ), '', $this->feed_options['excludewords'] ), 0, 5 ) : '';
		$cache_name = substr( 'ctf_!_' . $this->feed_options['feed_term'] . $includewords . $excludewords, 0, 45 );

		if ( $this->feed_options['type'] === 'search' ) {
			$cache_name = str_replace( ' -filter:retweets', '', $cache_name );
		}

		$cache_time_limit_reached = get_transient( $cache_name ) ? false : true;

		$existing_cache = get_option( $cache_name, false );
		if ( $existing_cache && ! is_array( $existing_cache ) ) {
			$existing_cache = json_decode( $existing_cache, $assoc = true );
		}

		$this->persistent_index = $this->persistent_index + $this->feed_options['num'];

		$this->feed_options['count'] = 200;

		if ( ! empty( $this->last_id_data ) || ( ! $cache_time_limit_reached && $existing_cache ) ) {
			return $existing_cache;
		} elseif ( $existing_cache ) {
			// use "since-id" to look for more in an api request
			$since_id = $existing_cache[0]['id_str'];
			$api_obj = $this->getTweetsSinceID( $since_id, 'search', $this->feed_options['feed_term'], $this->feed_options['count'] );
			// add any new tweets to the cache
			$this->tweet_set = json_decode( $api_obj->json , $assoc = true );

			$tweets = isset( $this->tweet_set['statuses'] ) ? $this->tweet_set['statuses'] : array();

			// add a transient to delay another api retrieval
			set_transient( $cache_name, true, $this->feed_options['cache_time'] );

			if ( empty( $tweets ) ) {
				if ( ! is_array( $existing_cache ) ) {
					return false;
				} else {
					return $existing_cache;
				}
			} else {
				$tweet_set = $this->reduceTweetSetData( $tweets, false );
			}
			$tweet_set = $this->appendPersistentCacheTweets( $existing_cache, $tweet_set );
			$cache_set = json_encode( $tweet_set );

			update_option( $cache_name, $cache_set );

			return $tweet_set;
			// else if cached data doesn't exist
		} else {
			// make a request for last 200 tweets
			$api_obj = $this->apiConnectionResponse( 'search', $this->feed_options['feed_term'] );
			// cache them in a regular option
			$this->tweet_set = json_decode( $api_obj->json , $assoc = true );

			// check for errors/tweets present
			if ( isset( $this->tweet_set['errors'][0] ) ) {
				if ( empty( $this->api_obj ) ) {
					$this->api_obj = new stdClass();
				}
				$this->api_obj->api_error_no = $this->tweet_set['errors'][0]['code'];
				$this->api_obj->api_error_message = $this->tweet_set['errors'][0]['message'];

				$this->tweet_set = false;
			}

			$tweets = isset( $this->tweet_set['statuses'] ) ? $this->tweet_set['statuses'] : $this->tweet_set;

			if ( empty( $tweets ) ) {
				$this->errors['error_message'] = 'No Tweets returned';
				$this->tweet_set = false;
			} else {
				$this->tweet_set = $this->reduceTweetSetData( $tweets, false );
			}

			// create a new persistent cache
			if ( $this->tweet_set && isset( $this->tweet_set[0] ) ) {
				$tweet_set = json_encode( $this->tweet_set );

				update_option( $cache_name, $tweet_set );

				// update list of persistent cache
				$cache_list = get_option( 'ctf_cache_list', array() );

				$cache_list[] = $cache_name;

				update_option( 'ctf_cache_list', $cache_list );
			}

			return $this->tweet_set;
		}

		// add the search parameter to another option that contains a list of all persistent caches available
	}

	private function reduceTweetSetData( $tweet_set, $limit = true ) {
		if ( $this->check_for_duplicates ) {
			$this->tweet_set = $this->removeDuplicates( $tweet_set, $limit );
		}

		if ( $this->feed_options['selfreplies'] ) {
			$this->tweet_set = $this->filterTweetSet( $tweet_set, $limit );
		}

		$this->tweet_set = $tweet_set;
		$this->trimTweetData( false );
		return $this->tweet_set;
	}

	/**
	* this takes the current set of tweets and processes them until there are
	* enough filtered tweets to create the feed from
	*/
	private function filterTweetSet( $tweet_set, $limit = true )
	{
		$working_tweet_set = isset( $tweet_set['statuses'] ) ? $tweet_set['statuses'] : $tweet_set;
		$usable_tweets = 0;
		if ( $limit ) {
			$tweets_needed = $this->feed_options['count'] + 1; // magic number here should be ADT
		} else {
			$tweets_needed = 200;
		}
		$i = 0; // index of working_tweet_set
		$still_setting_filtered_tweets = true;

		while ( $still_setting_filtered_tweets ) { // stays true until the number to display is reached or out of tweets
			if ( isset ( $working_tweet_set[$i] ) ) { // if there is another tweet available
				if ( !$this->feed_options['selfreplies'] && isset( $working_tweet_set[$i]['in_reply_to_screen_name'] ) ) {
					unset( $working_tweet_set[$i] );
				} elseif ( $this->feed_options['selfreplies']
				           && isset( $working_tweet_set[$i]['in_reply_to_screen_name'] )
				           && $working_tweet_set[$i]['in_reply_to_screen_name'] !== $working_tweet_set[$i]['user']['screen_name']) {
					unset( $working_tweet_set[$i] );
				} else {
					$usable_tweets++;
				}
			} else {
				$still_setting_filtered_tweets = false;
			}

			// if there are no more tweets needed
			if ( $usable_tweets >= $tweets_needed ) {
				$still_setting_filtered_tweets = false;
			} else {
				$i++;
			}

		}

		if ( is_array( $working_tweet_set ) ) {
			return array_values( $working_tweet_set );
		} else {
			return false;
		}
	}

	private function appendPersistentCacheTweets( $existing_cache )
	{
		if ( is_array( $this->tweet_set ) ) {
			$tweet_set = array_merge( $this->tweet_set, $existing_cache );
		} else {
			$tweet_set = $existing_cache;
		}

		$tweet_set = array_slice( $tweet_set, 0, 150 );

		return $tweet_set;
	}


	private function removeDuplicates( $tweet_set, $limit = true )
	{
		$tweet_set = isset( $tweet_set['statuses'] ) ? $tweet_set['statuses'] : $tweet_set;
		$usable_tweets = 0;
		if ( $limit ) {
			$tweets_needed = $this->feed_options['count'] + 1; // magic number here should be ADT
		} else {
			$tweets_needed = 200;
		}
		$ids_of_tweets_to_remove = array();

		$i = 0; // index of tweet_set
		$still_setting_filtered_tweets = true;
		while ( $still_setting_filtered_tweets ) { // stays true until the number to display is reached or out of tweets
			if ( isset( $tweet_set[$i]['retweeted_status']['id_str'] ) ) {
				unset( $tweet_set[$i] );
			} elseif ( isset( $tweet_set[$i] ) ) {
				$id = isset( $tweet_set[$i]['retweeted_status']['id_str'] ) ? $tweet_set[$i]['retweeted_status']['id_str'] : $tweet_set[$i]['id_str'];
				if ( in_array( $id, $ids_of_tweets_to_remove ) ) {
					unset( $tweet_set[$i] );
				} else {
					$usable_tweets++;
					$ids_of_tweets_to_remove[] = $id;
				}
			} else {
				$still_setting_filtered_tweets = false;
			}

			// if there are no more tweets needed
			if ( $usable_tweets >= $tweets_needed ) {
				$still_setting_filtered_tweets = false;
			} else {
				$i++;
			}

		}

		if ( is_array( $tweet_set ) ) {
			return array_values( $tweet_set );
		} else {
			return false;
		}
	}

    /**
     *  will attempt to connect to the api to retrieve current tweets
     */
    public function maybeSetTweetsFromTwitter()
    {
        $this->setTweetsToRetrieve();
        $this->api_obj = $this->apiConnectionResponse( $this->feed_options['type'], $this->feed_options['feed_term'] );
        $this->tweet_set = json_decode( $this->api_obj->json , $assoc = true );

	    $working_tweet_set = $this->tweet_set;
	    if ( ! isset( $working_tweet_set['errors'][0] ) ) {
		    if ( isset( $working_tweet_set[0] ) ) {
			    $value = array_values( array_slice( $working_tweet_set, -1 ) );
			    $this->last_id_data = $value[0]['id_str'];
		    }

		    $working_tweet_set = $this->reduceTweetSetData( $working_tweet_set );
		    if ( $working_tweet_set === false ) {
			    $working_tweet_set = array();
		    }
	    }

	    $num_tweets = is_array( $working_tweet_set ) ? count( $working_tweet_set ) : 500;

	    if ( ! isset( $working_tweet_set['errors'][0] )
	         && $num_tweets < $this->feed_options['count'] ) {
		    // remove the last tweet as it is returned in the next request
		    array_pop( $working_tweet_set );
		    $original_count = $this->feed_options['count'];
		    $this->feed_options['count'] = 200;
		    $api_obj = $this->apiConnectionResponse( $this->feed_options['type'], $this->feed_options['feed_term'] );
		    $tweet_set_to_merge = json_decode( $api_obj->json , $assoc = true );

		    if ( isset( $tweet_set_to_merge['statuses'] ) ) {
			    $working_tweet_set = array_merge( $working_tweet_set, $tweet_set_to_merge['statuses'] );
		    } elseif ( isset( $tweet_set_to_merge[0]['created_at'] ) ) {
			    $working_tweet_set = array_merge( $working_tweet_set, $tweet_set_to_merge );
		    }

		    $this->feed_options['count'] = $original_count;
	    }

	    $this->tweet_set = $working_tweet_set;

        // check for errors/tweets present
        if ( isset( $this->tweet_set['errors'][0] ) ) {
            $this->api_obj->api_error_no = $this->tweet_set['errors'][0]['code'];
            $this->api_obj->api_error_message = $this->tweet_set['errors'][0]['message'];
            $this->tweet_set = false;
        }

        $tweets = isset( $this->tweet_set['statuses'] ) ? $this->tweet_set['statuses'] : $this->tweet_set;

        if ( empty( $tweets ) ) {
            $this->errors['error_message'] = 'No Tweets returned';
            $this->tweet_set = false;
        }

        if ( $this->check_for_duplicates ) {
	        $this->tweet_set = $this->removeDuplicates( $this->tweet_set );
        }
    }


    /**
     * calculates how many tweets short the feed is so more can be retrieved via ajax
     *
     * @return int number of tweets needed
     */
    protected function numTweetsNeeded() {
	    $tweet_count = 0;
	    if ( isset( $this->tweet_set['statuses'] ) && is_array( $this->tweet_set['statuses'] ) ) {
		    $tweet_count = count( $this->tweet_set['statuses'] );
	    } elseif ( isset( $this->tweet_set ) && is_array( $this->tweet_set ) ) {
		    $tweet_count = count( $this->tweet_set );
	    }

        return $this->feed_options['num'] - $tweet_count;
    }

    /**
     * trims the unused data retrieved for more efficient caching
     */
    protected function trimTweetData( $limit = true )
    {
        $is_pagination = !empty( $this->last_id_data ) ? 1 : 0;
        $tweets = isset( $this->tweet_set['statuses'] ) ? $this->tweet_set['statuses'] : $this->tweet_set;
	    if ( $limit ) {
		    $len = min( $this->feed_options['num'] + $is_pagination, count( $tweets ) );
	    } else {
		    $len = count( $tweets );
	    }
	    $trimmed_tweets = array();

        // for header
        if ( $this->last_id_data == '' && isset( $tweets[0] ) ) { // if this is the first set of tweets
            $trimmed_tweets[0]['user']['name']= $tweets[0]['user']['name'];
            $trimmed_tweets[0]['user']['description']= $tweets[0]['user']['description'];
            $trimmed_tweets[0]['user']['statuses_count']= $tweets[0]['user']['statuses_count'];
            $trimmed_tweets[0]['user']['followers_count']= $tweets[0]['user']['followers_count'];
        }

        for ( $i = 0; $i < $len; $i++ ) {
            $trimmed_tweets[$i]['user']['name'] = $tweets[$i]['user']['name'];
            $trimmed_tweets[$i]['user']['screen_name'] = $tweets[$i]['user']['screen_name'];
            $trimmed_tweets[$i]['user']['verified'] = $tweets[$i]['user']['verified'];
            $trimmed_tweets[$i]['user']['profile_image_url_https'] = $tweets[$i]['user']['profile_image_url_https'];
            $trimmed_tweets[$i]['user']['utc_offset']= $tweets[$i]['user']['utc_offset'];
	        $trimmed_tweets[$i]['text'] = isset( $tweets[$i]['text'] ) ? $tweets[$i]['text'] : $tweets[$i]['full_text'];
            $trimmed_tweets[$i]['id_str']= $tweets[$i]['id_str'];
            $trimmed_tweets[$i]['created_at']= $tweets[$i]['created_at'];
            $trimmed_tweets[$i]['retweet_count']= $tweets[$i]['retweet_count'];
            $trimmed_tweets[$i]['favorite_count']= $tweets[$i]['favorite_count'];

	        if ( isset( $tweets[$i]['entities']['urls'][0] ) ) {
		        foreach ( $tweets[$i]['entities']['urls'] as $url ) {
			        $trimmed_tweets[$i]['entities']['urls'][] = array(
				        'url' => $url['url'],
				        'expanded_url' => $url['expanded_url'],
				        'display_url' => $url['display_url'],

			        );
		        }
	        }

            if ( isset( $tweets[$i]['retweeted_status'] ) ) {
                $trimmed_tweets[$i]['retweeted_status']['user']['name'] = $tweets[$i]['retweeted_status']['user']['name'];
                $trimmed_tweets[$i]['retweeted_status']['user']['screen_name'] = $tweets[$i]['retweeted_status']['user']['screen_name'];
                $trimmed_tweets[$i]['retweeted_status']['user']['verified'] = $tweets[$i]['retweeted_status']['user']['verified'];
                $trimmed_tweets[$i]['retweeted_status']['user']['profile_image_url_https'] = $tweets[$i]['retweeted_status']['user']['profile_image_url_https'];
                $trimmed_tweets[$i]['retweeted_status']['user']['utc_offset']= $tweets[$i]['retweeted_status']['user']['utc_offset'];
	            $trimmed_tweets[$i]['retweeted_status']['text'] = isset( $tweets[$i]['retweeted_status']['text'] ) ? $tweets[$i]['retweeted_status']['text'] : $tweets[$i]['retweeted_status']['full_text'];
                $trimmed_tweets[$i]['retweeted_status']['id_str'] = $tweets[$i]['retweeted_status']['id_str'];
                $trimmed_tweets[$i]['retweeted_status']['created_at']= $tweets[$i]['retweeted_status']['created_at'];
                $trimmed_tweets[$i]['retweeted_status']['retweet_count']= $tweets[$i]['retweeted_status']['retweet_count'];
                $trimmed_tweets[$i]['retweeted_status']['favorite_count']= $tweets[$i]['retweeted_status']['favorite_count'];
	            if ( isset( $tweets[$i]['retweeted_status']['entities']['urls'][0] ) ) {
		            foreach ( $tweets[$i]['retweeted_status']['entities']['urls'] as $url ) {
			            $trimmed_tweets[$i]['retweeted_status']['entities']['urls'][] = array(
				            'url' => $url['url'],
				            'expanded_url' => $url['expanded_url'],
				            'display_url' => $url['display_url'],

			            );
		            }
	            }
            }

	        if ( isset( $tweets[$i]['retweeted_status']['quoted_status'] ) ) {
		        $trimmed_tweets[$i]['retweeted_status']['quoted_status']['user']['name'] = $tweets[$i]['retweeted_status']['quoted_status']['user']['name'];
		        $trimmed_tweets[$i]['retweeted_status']['quoted_status']['user']['screen_name'] = $tweets[$i]['retweeted_status']['quoted_status']['user']['screen_name'];
		        $trimmed_tweets[$i]['retweeted_status']['quoted_status']['user']['verified'] = $tweets[$i]['retweeted_status']['quoted_status']['user']['verified'];
		        $trimmed_tweets[$i]['retweeted_status']['quoted_status']['text'] = isset( $tweets[$i]['retweeted_status']['quoted_status']['text'] ) ? $tweets[$i]['retweeted_status']['quoted_status']['text'] : $tweets[$i]['retweeted_status']['quoted_status']['full_text'];
		        $trimmed_tweets[$i]['retweeted_status']['quoted_status']['id_str'] = $tweets[$i]['retweeted_status']['quoted_status']['id_str'];
		        if ( isset( $tweets[$i]['retweeted_status']['quoted_status']['entities']['urls'][0] ) ) {
			        foreach ( $tweets[$i]['retweeted_status']['quoted_status']['entities']['urls'] as $url ) {
				        $trimmed_tweets[$i]['retweeted_status']['quoted_status']['entities']['urls'][] = array(
					        'url' => $url['url'],
					        'expanded_url' => $url['expanded_url'],
					        'display_url' => $url['display_url'],
				        );
			        }
		        }
	        }

            if ( isset( $tweets[$i]['quoted_status'] ) ) {
                $trimmed_tweets[$i]['quoted_status']['user']['name'] = $tweets[$i]['quoted_status']['user']['name'];
                $trimmed_tweets[$i]['quoted_status']['user']['screen_name'] = $tweets[$i]['quoted_status']['user']['screen_name'];
                $trimmed_tweets[$i]['quoted_status']['user']['verified'] = $tweets[$i]['quoted_status']['user']['verified'];
	            $trimmed_tweets[$i]['quoted_status']['text'] = isset( $tweets[$i]['quoted_status']['text'] ) ? $tweets[$i]['quoted_status']['text'] : $tweets[$i]['quoted_status']['full_text'];
                $trimmed_tweets[$i]['quoted_status']['id_str'] = $tweets[$i]['quoted_status']['id_str'];
	            if ( isset( $tweets[$i]['quoted_status']['entities']['urls'][0] ) ) {
		            foreach ( $tweets[$i]['quoted_status']['entities']['urls'] as $url ) {
			            $trimmed_tweets[$i]['quoted_status']['entities']['urls'][] = array(
				            'url' => $url['url'],
				            'expanded_url' => $url['expanded_url'],
				            'display_url' => $url['display_url'],
			            );
		            }
	            }
            }

            $trimmed_tweets[$i] = $this->filterTrimmedTweets( $trimmed_tweets[$i], $tweets[$i] );
        }

        $this->tweet_set = $trimmed_tweets;
    }

	protected function removeStringFromText( $string, $text) {
		return str_replace( $string, '', $text );
	}

	/**
	 * captures additional data for "Pro" features
	 *
	 * @param $trimmed array    current set of trimmed tweets
	 * @param $tweet array      raw tweet data from api
	 * @return array
	 */
	protected function filterTrimmedTweets( $trimmed, $tweet )
	{
		if ( isset( $tweet['in_reply_to_screen_name'] ) ) {
			$trimmed['in_reply_to_screen_name'] = $tweet['in_reply_to_screen_name'];
			$trimmed['entities']['user_mentions'][0]['name'] = isset( $tweet['entities']['user_mentions'][0]['name'] ) ? $tweet['entities']['user_mentions'][0]['name'] : '';
			$trimmed['in_reply_to_status_id_str'] = $tweet['in_reply_to_status_id_str'];
		}

		if ( isset( $tweet['extended_entities']['media'] ) ) {
			// if there is media, we need to remove the media url from the tweet text
			$text = isset( $tweet['full_text'] ) ? $tweet['full_text'] : $tweet['text'];
			if ( isset( $tweet['extended_entities']['media'][0]['url'] ) ) {
				$trimmed['text'] = $this->removeStringFromText( $tweet['extended_entities']['media'][0]['url'], $text );
			}
			$num_media = count( $tweet['extended_entities']['media'] );
			for ( $i = 0; $i < $num_media; $i++ ) {
				$trimmed['extended_entities']['media'][$i]['media_url_https'] = $tweet['extended_entities']['media'][$i]['media_url_https'];
				$trimmed['extended_entities']['media'][$i]['type'] = $tweet['extended_entities']['media'][$i]['type'];
				if ( isset( $tweet['extended_entities']['media'][$i]['sizes'] ) ) {
					$trimmed['extended_entities']['media'][$i]['sizes'] = $tweet['extended_entities']['media'][$i]['sizes'];
				}
				if ( $tweet['extended_entities']['media'][$i]['type'] == 'video' || $tweet['extended_entities']['media'][$i]['type'] == 'animated_gif' ) {
					foreach ( $tweet['extended_entities']['media'][$i]['video_info']['variants'] as $variant ) {
						if ( isset( $variant['content_type'] ) && $variant['content_type'] == 'video/mp4' ) {
							$trimmed['extended_entities']['media'][$i]['video_info']['variants'][$i]['url'] = $variant['url'];
						}
					}
					if ( ! isset( $trimmed['extended_entities']['media'][$i]['video_info']['variants'][$i]['url'] ) ) {
						$trimmed['extended_entities']['media'][$i]['video_info']['variants'][$i]['url'] = $tweet['extended_entities']['media'][$i]['video_info']['variants'][0]['url'];
					}
				}
			}

		} elseif ( isset( $tweet['entities']['media'] ) ) {
			// if there is media, we need to remove the media url from the tweet text
			$text = isset( $tweet['full_text'] ) ? $tweet['full_text'] : $tweet['text'];
			if ( isset( $tweet['entities']['media'][0]['url'] ) ) {
				$trimmed['text'] = $this->removeStringFromText( $tweet['entities']['media'][0]['url'], $text );
			}

			$num_media = count( $tweet['entities']['media'] );
			for ( $i = 0; $i < $num_media; $i++ ) {
				$trimmed['entities']['media'][$i]['media_url_https'] = $tweet['entities']['media'][$i]['media_url_https'];
				$trimmed['entities']['media'][$i]['type'] = $tweet['entities']['media'][$i]['type'];
				if ( isset( $tweet['entities']['media'][$i]['sizes'] ) ) {
					$trimmed['entities']['media'][$i]['sizes'] = $tweet['entities']['media'][$i]['sizes'];
				}
				if ( $tweet['entities']['media'][$i]['type'] == 'video' || $tweet['entities']['media'][$i]['type'] == 'animated_gif' ) {
					foreach ( $tweet['entities']['media'][$i]['video_info']['variants'] as $variant ) {
						if ( isset( $variant['content_type'] ) && $variant['content_type'] == 'video/mp4' ) {
							$trimmed['entities']['media'][$i]['video_info']['variants'][$i]['url'] = $variant['url'];
						}
					}
					if ( ! isset( $trimmed['entities']['media'][$i]['video_info']['variants'][$i]['url'] ) ) {
						$trimmed['entities']['media'][$i]['video_info']['variants'][$i]['url'] = $tweet['entities']['media'][$i]['video_info']['variants'][0]['url'];
					}
				}
			}

		}

		if ( isset( $tweet['retweeted_status']['extended_entities']['media'] ) ) {
			// if there is media, we need to remove the media url from the tweet text
			$retweeted_text = isset( $tweet['retweeted_status']['full_text'] ) ? $tweet['retweeted_status']['full_text'] : $tweet['retweeted_status']['text'];
			if ( isset( $tweet['retweeted_status']['extended_entities']['media'][0]['url'] ) ) {
				$trimmed['retweeted_status']['text'] = $this->removeStringFromText( $tweet['retweeted_status']['extended_entities']['media'][0]['url'], $retweeted_text );
			}

			$num_media = count( $tweet['retweeted_status']['extended_entities']['media'] );
			for ( $i = 0; $i < $num_media; $i++ ) {
				$trimmed['retweeted_status']['extended_entities']['media'][$i]['media_url_https'] = $tweet['retweeted_status']['extended_entities']['media'][$i]['media_url_https'];
				$trimmed['retweeted_status']['extended_entities']['media'][$i]['type'] = $tweet['retweeted_status']['extended_entities']['media'][$i]['type'];
				if ( isset( $tweet['retweeted_status']['extended_entities']['media'][$i]['sizes'] ) ) {
					$trimmed['retweeted_status']['extended_entities']['media'][$i]['sizes'] = $tweet['retweeted_status']['extended_entities']['media'][$i]['sizes'];
				}
				if ( $tweet['retweeted_status']['extended_entities']['media'][$i]['type'] == 'video' || $tweet['retweeted_status']['extended_entities']['media'][$i]['type'] == 'animated_gif' ) {
					foreach ( $tweet['retweeted_status']['extended_entities']['media'][$i]['video_info']['variants'] as $variant ) {
						if ( isset( $variant['content_type'] ) && $variant['content_type'] == 'video/mp4' ) {
							$trimmed['retweeted_status']['extended_entities']['media'][$i]['video_info']['variants'][$i]['url'] = $variant['url'];
						}
					}
					if ( ! isset( $trimmed['retweeted_status']['extended_entities']['media'][$i]['video_info']['variants'][$i]['url'] ) ) {
						$trimmed['retweeted_status']['extended_entities']['media'][$i]['video_info']['variants'][$i]['url'] = $tweet['retweeted_status']['extended_entities']['media'][$i]['video_info']['variants'][0]['url'];
					}
				}
			}

		} elseif ( isset( $tweet['retweeted_status']['entities']['media'] ) ) {
			// if there is media, we need to remove the media url from the tweet text
			$retweeted_text = isset( $tweet['retweeted_status']['full_text'] ) ? $tweet['retweeted_status']['full_text'] : $tweet['retweeted_status']['text'];
			if ( isset( $tweet['retweeted_status']['entities']['media'][0]['url'] ) ) {
				$trimmed['retweeted_status']['text'] = $this->removeStringFromText( $tweet['retweeted_status']['entities']['media'][0]['url'], $retweeted_text );
			}

			$num_media = count( $tweet['retweeted_status']['entities']['media'] );
			for( $i = 0; $i < $num_media; $i++ ) {
				$trimmed['retweeted_status']['entities']['media'][$i]['media_url_https'] = $tweet['retweeted_status']['entities']['media'][$i]['media_url_https'];
				$trimmed['retweeted_status']['entities']['media'][$i]['type'] = $tweet['retweeted_status']['entities']['media'][$i]['type'];
				if ( isset( $tweet['retweeted_status']['entities']['media'][$i]['sizes'] ) ) {
					$trimmed['retweeted_status']['entities']['media'][$i]['sizes'] = $tweet['retweeted_status']['entities']['media'][$i]['sizes'];
				}
				if ( $tweet['retweeted_status']['entities']['media'][$i]['type'] == 'video' || $tweet['retweeted_status']['entities']['media'][$i]['type'] == 'animated_gif' ) {
					foreach ( $tweet['retweeted_status']['entities']['media'][$i]['video_info']['variants'] as $variant ) {
						if ( isset( $variant['content_type'] ) && $variant['content_type'] == 'video/mp4' ) {
							$trimmed['retweeted_status']['entities']['media'][$i]['video_info']['variants'][$i]['url'] = $variant['url'];
						}
					}
					if ( ! isset( $trimmed['retweeted_status']['entities']['media'][$i]['video_info']['variants'][$i]['url'] ) ) {
						$trimmed['retweeted_status']['entities']['media'][$i]['video_info']['variants'][$i]['url'] = $tweet['retweeted_status']['entities']['media'][$i]['video_info']['variants'][0]['url'];
					}
				}
			}

		} elseif ( isset( $tweet['quoted_status']['extended_entities']['media'] ) ) {
			// if there is media, we need to remove the media url from the tweet text
			$quoted_text = isset( $tweet['quoted_status']['full_text'] ) ? $tweet['quoted_status']['full_text'] : $tweet['quoted_status']['text'];
			if ( isset( $tweet['quoted_status']['extended_entities']['media'][0]['url'] ) ) {
				$trimmed['quoted_status']['text'] = $this->removeStringFromText( $tweet['quoted_status']['extended_entities']['media'][0]['url'], $quoted_text );
			}

			$num_media = count( $tweet['quoted_status']['extended_entities']['media'] );
			for( $i = 0; $i < $num_media; $i++ ) {
				$trimmed['quoted_status']['extended_entities']['media'][$i]['media_url_https'] = $tweet['quoted_status']['extended_entities']['media'][$i]['media_url_https'];
				$trimmed['quoted_status']['extended_entities']['media'][$i]['type'] = $tweet['quoted_status']['extended_entities']['media'][$i]['type'];
				if ( $tweet['quoted_status']['extended_entities']['media'][$i]['type'] == 'video' || $tweet['quoted_status']['extended_entities']['media'][$i]['type'] == 'animated_gif' ) {
					foreach ( $tweet['quoted_status']['extended_entities']['media'][$i]['video_info']['variants'] as $variant ) {
						if ( isset( $variant['content_type'] ) && $variant['content_type'] == 'video/mp4' ) {
							$trimmed['quoted_status']['extended_entities']['media'][$i]['video_info']['variants'][$i]['url'] = $variant['url'];
						}
					}
					if ( ! isset( $trimmed['quoted_status']['extended_entities']['media'][$i]['video_info']['variants'][$i]['url'] ) ) {
						$trimmed['quoted_status']['extended_entities']['media'][$i]['video_info']['variants'][$i]['url'] = $tweet['quoted_status']['extended_entities']['media'][$i]['video_info']['variants'][0]['url'];
					}
				}
			}

		} elseif ( isset( $tweet['quoted_status']['entities']['media'] ) ) {
			// if there is media, we need to remove the media url from the tweet text
			$quoted_text = isset( $tweet['quoted_status']['full_text'] ) ? $tweet['quoted_status']['full_text'] : $tweet['quoted_status']['text'];
			if ( isset( $tweet['quoted_status']['entities']['media'][0]['url'] ) ) {
				$trimmed['quoted_status']['text'] = $this->removeStringFromText( $tweet['quoted_status']['entities']['media'][0]['url'], $quoted_text );
			}

			$num_media = count( $tweet['quoted_status']['entities']['media'] );
			for( $i = 0; $i < $num_media; $i++ ) {
				$trimmed['quoted_status']['entities']['media'][$i]['media_url_https'] = $tweet['quoted_status']['entities']['media'][$i]['media_url_https'];
				$trimmed['quoted_status']['entities']['media'][$i]['type'] = $tweet['quoted_status']['entities']['media'][$i]['type'];
				if ( $tweet['quoted_status']['entities']['media'][$i]['type'] == 'video' || $tweet['quoted_status']['entities']['media'][$i]['type'] == 'animated_gif' ) {
					foreach ( $tweet['quoted_status']['entities']['media'][$i]['video_info']['variants'] as $variant ) {
						if ( isset( $variant['content_type'] ) && $variant['content_type'] == 'video/mp4' ) {
							$trimmed['quoted_status']['entities']['media'][$i]['video_info']['variants'][$i]['url'] = $variant['url'];
						}
					}
					if ( ! isset( $trimmed['quoted_status']['entities']['media'][$i]['video_info']['variants'][$i]['url'] ) ) {
						$trimmed['quoted_status']['entities']['media'][$i]['video_info']['variants'][$i]['url'] = $tweet['quoted_status']['entities']['media'][$i]['video_info']['variants'][0]['url'];
					}
				}
			}

		}

		if ( isset( $tweet['retweeted_status']['quoted_status']['extended_entities']['media'] ) ) {
			// if there is media, we need to remove the media url from the tweet text
			$retweeted_text = isset( $tweet['retweeted_status']['quoted_status']['full_text'] ) ? $tweet['retweeted_status']['quoted_status']['full_text'] : $tweet['retweeted_status']['quoted_status']['text'];
			if ( isset( $tweet['retweeted_status']['quoted_status']['extended_entities']['media'][0]['url'] ) ) {
				$trimmed['retweeted_status']['quoted_status']['text'] = $this->removeStringFromText( $tweet['retweeted_status']['quoted_status']['extended_entities']['media'][0]['url'], $retweeted_text );
			}
			$num_media = count( $tweet['retweeted_status']['quoted_status']['extended_entities']['media'] );
			for ( $i = 0; $i < $num_media; $i++ ) {
				$trimmed['retweeted_status']['quoted_status']['extended_entities']['media'][$i]['media_url_https'] = $tweet['retweeted_status']['quoted_status']['extended_entities']['media'][$i]['media_url_https'];
				$trimmed['retweeted_status']['quoted_status']['extended_entities']['media'][$i]['type'] = $tweet['retweeted_status']['quoted_status']['extended_entities']['media'][$i]['type'];
				if ( isset( $tweet['retweeted_status']['quoted_status']['extended_entities']['media'][$i]['sizes'] ) ) {
					$trimmed['retweeted_status']['quoted_status']['extended_entities']['media'][$i]['sizes'] = $tweet['retweeted_status']['quoted_status']['extended_entities']['media'][$i]['sizes'];
				}
				if ( $tweet['retweeted_status']['quoted_status']['extended_entities']['media'][$i]['type'] == 'video' || $tweet['retweeted_status']['quoted_status']['extended_entities']['media'][$i]['type'] == 'animated_gif' ) {
					foreach ( $tweet['retweeted_status']['quoted_status']['extended_entities']['media'][$i]['video_info']['variants'] as $variant ) {
						if ( isset( $variant['content_type'] ) && $variant['content_type'] == 'video/mp4' ) {
							$trimmed['retweeted_status']['quoted_status']['extended_entities']['media'][$i]['video_info']['variants'][$i]['url'] = $variant['url'];
						}
					}
					if ( ! isset( $trimmed['retweeted_status']['quoted_status']['extended_entities']['media'][$i]['video_info']['variants'][$i]['url'] ) ) {
						$trimmed['retweeted_status']['quoted_status']['extended_entities']['media'][$i]['video_info']['variants'][$i]['url'] = $tweet['retweeted_status']['quoted_status']['extended_entities']['media'][$i]['video_info']['variants'][0]['url'];
					}
				}
			}
		} elseif ( isset( $tweet['retweeted_status']['quoted_status']['entities']['media'] ) ) {
			// if there is media, we need to remove the media url from the tweet text
			$retweeted_text = isset( $tweet['retweeted_status']['quoted_status']['full_text'] ) ? $tweet['retweeted_status']['quoted_status']['full_text'] : $tweet['retweeted_status']['quoted_status']['text'];
			if ( isset( $tweet['retweeted_status']['quoted_status']['entities']['media'][0]['url'] ) ) {
				$trimmed['retweeted_status']['quoted_status']['text'] = $this->removeStringFromText( $tweet['retweeted_status']['quoted_status']['entities']['media'][0]['url'], $retweeted_text );
			}
			$num_media = count( $tweet['retweeted_status']['quoted_status']['entities']['media'] );
			for( $i = 0; $i < $num_media; $i++ ) {
				$trimmed['retweeted_status']['quoted_status']['entities']['media'][$i]['media_url_https'] = $tweet['retweeted_status']['quoted_status']['entities']['media'][$i]['media_url_https'];
				$trimmed['retweeted_status']['quoted_status']['entities']['media'][$i]['type'] = $tweet['retweeted_status']['quoted_status']['entities']['media'][$i]['type'];
				if ( isset( $tweet['retweeted_status']['quoted_status']['entities']['media'][$i]['sizes'] ) ) {
					$trimmed['retweeted_status']['quoted_status']['entities']['media'][$i]['sizes'] = $tweet['retweeted_status']['quoted_status']['entities']['media'][$i]['sizes'];
				}
				if ( $tweet['retweeted_status']['quoted_status']['entities']['media'][$i]['type'] == 'video' || $tweet['retweeted_status']['quoted_status']['entities']['media'][$i]['type'] == 'animated_gif' ) {
					foreach ( $tweet['retweeted_status']['quoted_status']['entities']['media'][$i]['video_info']['variants'] as $variant ) {
						if ( isset( $variant['content_type'] ) && $variant['content_type'] == 'video/mp4' ) {
							$trimmed['retweeted_status']['quoted_status']['entities']['media'][$i]['video_info']['variants'][$i]['url'] = $variant['url'];
						}
					}
					if ( ! isset( $trimmed['retweeted_status']['quoted_status']['entities']['media'][$i]['video_info']['variants'][$i]['url'] ) ) {
						$trimmed['retweeted_status']['quoted_status']['entities']['media'][$i]['video_info']['variants'][$i]['url'] = $tweet['retweeted_status']['quoted_status']['entities']['media'][$i]['video_info']['variants'][0]['url'];
					}
				}
			}
		}

		//remove the url from the text if it links to a quoted tweet that is already linked to
		if ( isset( $tweet['quoted_status'] ) ) {
			$maybe_remove_index = count( $tweet['entities']['urls'] ) - 1;
			if ( isset( $tweet['entities']['urls'][$maybe_remove_index]['url'] ) ) {
				$text = isset( $trimmed['full_text'] ) ? $trimmed['full_text'] : $trimmed['text'];
				$trimmed['text'] = $this->removeStringFromText( $tweet['entities']['urls'][$maybe_remove_index]['url'], $text );
			}
		}


		// used to generate twitter cards
		if ( isset( $tweet['entities']['urls'][0]['expanded_url'] ) ) {
			$trimmed['entities']['urls'][0]['expanded_url'] = $tweet['entities']['urls'][0]['expanded_url'];
		}

		if ( isset( $tweet['retweeted_status']['entities']['urls'][0]['expanded_url'] ) ) {
			$trimmed['retweeted_status']['entities']['urls'][0]['expanded_url'] = $tweet['retweeted_status']['entities']['urls'][0]['expanded_url'];
		}

		return $trimmed;
	}

    /**
     * will create a transient with the tweet cache if one doesn't exist, the data seems valid, and caching is active
     */
    public function maybeCacheTweets()
    {
        if ( ( ! $this->transient_data || $this->errors['cache_status'] ) && $this->feed_options['cache_time'] > 0 ) {
            $this->trimTweetData();
	        $cache = json_encode( $this->tweet_set );
            set_transient( $this->transient_name, $cache, $this->feed_options['cache_time'] );
        }
    }

    /**
     * returns a JSON string to be used in the data attribute that contains the shortcode data
     */
    public function getShortCodeJSON()
    {
        $json_data = '{';
        $i = 0;
	    $len = is_array( $this->atts ) ? count( $this->atts ) : 0;

        if ( ! empty( $this->atts ) ) {
            foreach ( $this->atts as $key => $value) {
                if ( $i == $len - 1 ) {
                    $json_data .= '&quot;' . $key . '&quot;: &quot;' . $value . '&quot;';
                } else {
                    $json_data .= '&quot;' . $key . '&quot;: &quot;' . $value . '&quot;, ';
                }
                $i++;
            }
        }

        $json_data .= '}';

        return $json_data;
    }

    /**
     * uses the endpoint to determing what get fields need to be set
     *
     * @param $end_point api endpoint needed
     * @param $feed_term term associated with the endpoint, user name or search term
     * @return array the get fields for the request
     */
    protected function setGetFieldsArray( $end_point, $feed_term )
    {
        $get_fields = array();

	    $get_fields['tweet_mode'] = 'extended';

	    if ( $end_point === 'usertimeline' ) {
            if ( ! empty ( $feed_term ) ) {
                $get_fields['screen_name'] = $feed_term;
            }
            if ( !$this->feed_options['selfreplies'] ) {
	            $get_fields['exclude_replies'] = 'true';
            }
        }
        if ( $end_point === 'hometimeline' ) {
            $get_fields['exclude_replies'] = 'true';
	        if ( !$this->feed_options['selfreplies'] ) {
		        $get_fields['exclude_replies'] = 'true';
	        }
        }
        if ( $end_point === 'search' ) {
            $get_fields['q'] = $feed_term;
        }

        return $get_fields;
    }

	/**
	 * attempts to connect and retrieve tweets from the Twitter api
	 *
	 * @return mixed|string object containing the response
	 */
	public function apiConnectionResponse( $end_point, $feed_term )
	{
		// Only can be set in the options page
		$request_settings = array(
			'consumer_key' => $this->feed_options['consumer_key'],
			'consumer_secret' => $this->feed_options['consumer_secret'],
			'access_token' => $this->feed_options['access_token'],
			'access_token_secret' => $this->feed_options['access_token_secret'],
		);

		// For pagination, an extra post needs to be retrieved since the last post is
		// included in the next set
		$count = $this->feed_options['count'];

		$get_fields = $this->setGetFieldsArray( $end_point, $feed_term );

		if ( ! empty( $this->last_id_data ) ) {
			$count++;
			$max_id = $this->last_id_data;
		}
		$get_fields['count'] = $count;

		// max_id parameter should only be included for the second set of posts
		if ( isset( $max_id ) ) {
			$get_fields['max_id'] = $max_id;
		}

		include_once( CTF_URL . '/inc/CtfOauthConnect.php' );

		// actual connection
		$twitter_connect = new CtfOauthConnect( $request_settings, $end_point );
		$twitter_connect->setUrlBase();
		$twitter_connect->setGetFields( $get_fields );
		$twitter_connect->setRequestMethod( $this->feed_options['request_method'] );

		return $twitter_connect->performRequest();
	}

	private function getTweetsSinceID( $since_id, $end_point = 'search', $feed_term, $count )
	{
		// Only can be set in the options page
		$request_settings = array(
			'consumer_key' => $this->feed_options['consumer_key'],
			'consumer_secret' => $this->feed_options['consumer_secret'],
			'access_token' => $this->feed_options['access_token'],
			'access_token_secret' => $this->feed_options['access_token_secret'],
		);

		$get_fields = $this->setGetFieldsArray( $end_point, $feed_term );

		$get_fields['since_id'] = $since_id;

		$get_fields['count'] = $count;

		include_once( CTF_URL . '/inc/CtfOauthConnect.php' );

		// actual connection
		$twitter_connect = new CtfOauthConnect( $request_settings, $end_point );
		$twitter_connect->setUrlBase();
		$twitter_connect->setGetFields( $get_fields );
		$twitter_connect->setRequestMethod( $this->feed_options['request_method'] );

		return $twitter_connect->performRequest();
	}

	public function feedID() {
		if ( $this->feed_options['persistentcache'] ) {
			$feed_id = substr( 'ctf_!_' . $this->feed_options['feed_term'], 0, 45 );
			$feed_id = str_replace( ' -filter:retweets', '', $feed_id );
		} else {
			$feed_id = $this->transient_name;
		}

		return $feed_id;
	}

    /**
     * If the feed runs out of tweets to display for some reason,
     * this function creates a graceful failure message
     *
     * @param $feed_options
     * @return string html for "out of tweets" message
     */
    protected function getOutOfTweetsHtml( $feed_options )
    {
        $html = '';

        $html .= '<div class="ctf-out-of-tweets">';
        $html .= '<p>' . __( "That's all! No more Tweets to load", 'custom-twitter-feeds' ) . '</p>';
        $html .= '<p>';
        $html .= '<a class="twitter-share-button" href="https://twitter.com/share" target="_blank" rel="noopener noreferrer" data-size="large" data-url="'.get_home_url().'">Share</a>';
        if ( !empty( $feed_options['screenname'] ) ) {
            $html .= '<a class="twitter-follow-button" href="https://twitter.com/' . $feed_options['screenname'] . '" target="_blank" rel="noopener noreferrer" data-show-count="false" data-size="large" data-dnt="true">Follow</a>';
        }
        $html .= '</p>';
        $html .= '</div>';

        return $html;
    }

    /**
     * creates opening html for the feed
     *
     * @return string opening html that creates the feed
     */
    public function getFeedOpeningHtml()
    {
        $feed_options = $this->feed_options;
        $ctf_data_disablelinks = ($feed_options['disablelinks'] == 'true') ? ' data-ctfdisablelinks="true"' : '';
        $ctf_data_linktextcolor = $feed_options['linktextcolor'] != '' ? ' data-ctflinktextcolor="'.$feed_options['linktextcolor'].'"' : '';
	    $ctf_enable_intents = $feed_options['disableintents'] === false && ctf_show( 'actions', $feed_options ) ? ' data-ctfintents="1"' : '';
	    $ctf_data_needed = $this->num_tweets_needed;
        $ctf_feed_type = ! empty ( $feed_options['type'] ) ? esc_attr( $feed_options['type'] ) : 'multiple';
        $ctf_feed_classes = 'ctf ctf-type-' . $ctf_feed_type;
        $ctf_feed_classes .= ' ' . $feed_options['class'] . ' ctf-styles';
        $ctf_feed_classes .= $feed_options['width_mobile_no_fixed'] ? ' ctf-width-resp' : '';
        if ( $this->check_for_duplicates ) { $ctf_feed_classes .= ' ctf-no-duplicates'; }
        $ctf_feed_classes = apply_filters( 'ctf_feed_classes', $ctf_feed_classes ); //add_filter( 'ctf_feed_classes', function( $ctf_feed_classes ) { return $ctf_feed_classes . ' new-class'; }, 10, 1 );
        $ctf_feed_html = '';

	    $flags_att = '';
	    $flags = array();
	    if ( CTF_GDPR_Integrations::doing_gdpr( $feed_options ) ) {
		    $flags[] = 'gdpr';
	    }
	    if ( ! is_admin()
	         && CTF_Feed_Locator::should_do_ajax_locating( $this->feedID(), get_the_ID() ) ) {
		    $flags[] = 'locator';
	    }
	    if ( ! empty( $flags ) ) {
		    $flags_att = ' data-ctf-flags="' . implode( ',', $flags ) . '"';
	    }
	    $post_id_att = ' data-postid="' . esc_attr( get_the_ID() ) . '"';
	    $feed_id_att = ' data-feed-id="' . $this->feedID() . '"';

        $ctf_feed_html .= '<!-- Custom Twitter Feeds by Smash Balloon -->';
        $ctf_feed_html .= '<div id="ctf" class="' . $ctf_feed_classes . '" style="' . $feed_options['width'] . $feed_options['height'] . $feed_options['bgcolor'] . '" data-ctfshortcode="' . $this->getShortCodeJSON() . '"' .$ctf_data_disablelinks . $ctf_data_linktextcolor . $ctf_enable_intents . $flags_att . $post_id_att . $feed_id_att .' data-ctfneeded="'. $ctf_data_needed .'">';
        $tweet_set = $this->tweet_set;

        // dynamically include header
        if ( $feed_options['showheader'] ) {
            $ctf_feed_html .= $this->getFeedHeaderHtml( $tweet_set, $this->feed_options );
        }

        $ctf_feed_html .= '<div class="ctf-tweets">';

        return $ctf_feed_html;
    }

    /**
     * creates opening html for the feed
     *
     * @return string opening html that creates the feed
     */
    public function getFeedClosingHtml()
    {
        $feed_options = $this->feed_options;
        $ctf_feed_html = '';

        $ctf_feed_html .= '</div>'; // closing div for ctf-tweets

        if ( $feed_options['showbutton'] ) {
            $ctf_feed_html .= '<a href="javascript:void(0);" id="ctf-more" class="ctf-more" style="' . $feed_options['buttoncolor'] . $feed_options['buttontextcolor'] . '"><span>' . $feed_options['buttontext'] . '</span></a>';
        }

        if ( $feed_options['creditctf'] ) {
            $ctf_feed_html .= '<div class="ctf-credit-link"><a href="https://smashballoon.com/custom-twitter-feeds" target="_blank" rel="noopener noreferrer">' . ctf_get_fa_el( 'fa-twitter' ) . 'Custom Twitter Feeds Plugin</a></div>';
        }

        $ctf_feed_html .= '</div>'; // closing div tag for #ctf

        if ( $feed_options['ajax_theme'] ) {
            $ctf_feed_html .= '<script type="text/javascript" src="' . CTF_JS_URL . '"></script>';
        }

        return $ctf_feed_html;
    }

    /**
     * creates html for header of the feed
     *
     * @param $tweet_set string     trimmed tweets to be added to the feed
     * @param $feed_options         options for the feed
     * @return string html that creates the header of the feed
     */
    protected function getFeedHeaderHtml( $tweet_set, $feed_options )
    {
        $ctf_header_html = '';
        $ctf_no_bio = ( $feed_options['showbio'] && !empty($tweet_set[0]['user']['description']) ) ? '' : ' ctf-no-bio';

        // temporary workaround for cached http images
        $tweet_set[0]['user']['profile_image_url_https'] = isset( $tweet_set[0]['user']['profile_image_url_https'] ) ? $tweet_set[0]['user']['profile_image_url_https'] : $tweet_set[0]['user']['profile_image_url'];


        if ( $feed_options['type'] === 'usertimeline' ) {
            $ctf_header_html .= '<div class="ctf-header' . $ctf_no_bio . '" style="' . $feed_options['headerbgcolor'] . '">';
            $ctf_header_html .= '<a href="https://twitter.com/' . $tweet_set[0]['user']['screen_name'] . '" target="_blank" rel="noopener noreferrer" title="@' . $tweet_set[0]['user']['screen_name'] . '" class="ctf-header-link">';
            $ctf_header_html .= '<div class="ctf-header-text">';
            $ctf_header_html .= '<p class="ctf-header-user" style="' . $feed_options['headertextcolor'] . '">';
            $ctf_header_html .= '<span class="ctf-header-name">';

            if ( $feed_options['headertext'] != '' ) {
                $ctf_header_html .= esc_html( $feed_options['headertext'] );
            } else {
                $ctf_header_html .= esc_html( $tweet_set[0]['user']['name'] );
            }

            $ctf_header_html .= '</span>';

            if ( $tweet_set[0]['user']['verified'] == 1 ) {
	            $ctf_header_html .= '<span class="ctf-verified">' . ctf_get_fa_el( 'fa-check-circle' ) . '</span>';
            }

	        $ctf_header_html .= '<span class="ctf-header-follow">' . ctf_get_fa_el( 'fa-twitter' ) . __( 'Follow', 'custom-twitter-feeds' ) . '</span>';
            $ctf_header_html .= '</p>';

            if ( $feed_options['showbio'] && !empty($tweet_set[0]['user']['description']) ) {
                $ctf_header_html .= '<p class="ctf-header-bio" style="' . $feed_options['headertextcolor'] . '">' . $tweet_set[0]['user']['description'] . '</p>';
            }

            $ctf_header_html .= '</div>';
            $ctf_header_html .= '<div class="ctf-header-img">';
	        $ctf_header_html .= '<div class="ctf-header-img-hover">' . ctf_get_fa_el( 'fa-twitter' ) . '</div>';
	        if ( CTF_GDPR_Integrations::doing_gdpr( $feed_options ) ) {
		        $ctf_header_html .= '<span data-avatar="' . esc_url( $tweet_set[0]['user']['profile_image_url_https'] ) . '" data-alt="' . $tweet_set[0]['user']['name'] . '" style="display: none;">Avatar</span>';
	        } else {
		        $ctf_header_html .= '<img src="' . $tweet_set[0]['user']['profile_image_url_https'] . '" alt="' . $tweet_set[0]['user']['name'] . '" width="48" height="48">';
	        }
            $ctf_header_html .= '</div>';
            $ctf_header_html .= '</a>';
            $ctf_header_html .= '</div>';
        } else {

            if ( $feed_options['type'] === 'search' ) {
                $default_header_text = $feed_options['headertext'] != '' ? esc_html($feed_options['headertext']) : $feed_options['feed_term'];
                $url_part = 'hashtag/' . str_replace("#", "", $feed_options['feed_term']);
            } else {
                $default_header_text = 'Twitter';
                $url_part = $feed_options['screenname']; //Need to get screenname here
            }

	        $default_header_text = str_replace( ' -filter:retweets', '', $default_header_text );

	        $ctf_header_html .= '<div class="ctf-header ctf-header-type-generic" style="' . $feed_options['headerbgcolor'] . '">';
            $ctf_header_html .= '<a href="https://twitter.com/' . $url_part . '" target="_blank" rel="noopener noreferrer" class="ctf-header-link">';
            $ctf_header_html .= '<div class="ctf-header-text">';
            $ctf_header_html .= '<p class="ctf-header-no-bio" style="' . $feed_options['headertextcolor'] . '">' . $default_header_text . '</p>';
            $ctf_header_html .= '</div>';
            $ctf_header_html .= '<div class="ctf-header-img">';
            $ctf_header_html .= '<div class="ctf-header-generic-icon">';
	        $ctf_header_html .= ctf_get_fa_el( 'fa-twitter' );
            $ctf_header_html .= '</div>';
            $ctf_header_html .= '</div>';
            $ctf_header_html .= '</a>';
            $ctf_header_html .= '</div>';
        }

        return $ctf_header_html;
    }

    /**
     * outputs the html for a set of tweets to be used in the feed
     *
     * @param int $is_pagination    1 or 0, used to differentiate between the first set and subsequent tweet sets
     *
     * @return string $tweet_html
     */
    public function getTweetSetHtml( $is_pagination = 0 )
    {
        $tweet_set = isset( $this->tweet_set['statuses'] ) ? $this->tweet_set['statuses'] : $this->tweet_set;
        $len = min( $this->feed_options['num'] + $is_pagination, count( $tweet_set ) );
        $i = $is_pagination; // starts at index "1" to offset duplicate tweet
        $feed_options = $this->feed_options;
        $tweet_html = $this->feed_html;

        if ( $is_pagination && ( ! isset ( $tweet_set[1]['id_str'] ) ) ) {
            $tweet_html .= $this->getOutOfTweetsHtml( $this->feed_options );
        } else {
            while ( $i < $len ) {

                // run a check to accommodate the "search" endpoint as well
                $post = $tweet_set[$i];

                // temporary workaround for cached http images
                $post['user']['profile_image_url_https'] = isset( $post['user']['profile_image_url_https'] ) ? $post['user']['profile_image_url_https'] : $post['user']['profile_image_url'];

                // save the original tweet data in case it's a retweet
                $post_id = $post['id_str'];
                $author = strtolower( $post['user']['screen_name'] );

                // creates a string of classes applied to each tweet
                $tweet_classes = 'ctf-item ctf-author-' . $author .' ctf-new';
                if ( !ctf_show( 'avatar', $feed_options ) ) $tweet_classes .= ' ctf-hide-avatar';
                $tweet_classes = apply_filters( 'ctf_tweet_classes', $tweet_classes ); // add_filter( 'ctf_tweet_classes', function( $tweet_classes ) { return $ctf_feed_classes . ' new-class'; }, 10, 1 );

                // check for retweet
                $retweet_data_att = '';
                if ( isset( $post['retweeted_status'] ) ) {
                    $retweeter = array(
                        'name' => $post['user']['name'],
                        'screen_name' => $post['user']['screen_name']
                    );
                    $retweet_data_att = ( $this->check_for_duplicates ) ? ' data-ctfretweetid="'.$post['retweeted_status']['id_str'].'"' : '';
	                if ( isset( $post['retweeted_status'] ))
                    $post = $post['retweeted_status'];

                    // temporary workaround for cached http images
                    $post['user']['profile_image_url_https'] = isset( $post['user']['profile_image_url_https'] ) ? $post['user']['profile_image_url_https'] : $post['user']['profile_image_url'];
                    $tweet_classes .= ' ctf-retweet';
                } else {
                    unset( $retweeter );
                }

                // check for quoted
                if ( isset( $post['quoted_status'] ) ) {
                    $tweet_classes .= ' ctf-quoted';
                    $quoted = $post['quoted_status'];
	                $quoted_media_text = '';
	                if ( ( isset( $quoted['extended_entities']['media'][0] ) || isset( $quoted['entities']['media'][0] ) ) && ctf_show( 'placeholder', $feed_options ) ) {
		                $quoted_media = isset( $quoted['extended_entities']['media'] ) ? $quoted['extended_entities']['media'] : $quoted['entities']['media'];
		                $quoted_media_count = count( $quoted_media );
		                switch ( $quoted_media[0]['type'] ) {
			                case 'video':
			                case 'animated_gif':
				                $quoted_media_text     .= ctf_get_fa_el( 'fa-file-video-o' );
				                break;
			                default:
				                if ( $quoted_media_count > 1 ) {
					                $quoted_media_text     .= '<span class="ctf-quoted-tweet-text-media-wrap ctf-multi-media-icon">' . $quoted_media_count . ctf_get_fa_el( 'fa-picture-o' ) . '</span>';
				                } else {
					                $quoted_media_text     .= '<span class="ctf-quoted-tweet-text-media-wrap">' . ctf_get_fa_el( 'fa-picture-o' ) . '</span>';
				                }
				                break;
		                }
	                } else {
		                unset( $quoted_media );
	                }
                } else {
                    unset( $quoted );
	                unset( $quoted_media_text );
                }

                // check for media [0]['type']
	            $post_media_text = '';
                $post_media_count = 0;
	            if ( ( isset( $post['extended_entities']['media'][0] ) || isset( $post['entities']['media'][0] ) ) && ctf_show( 'placeholder', $feed_options ) ) {
		            $post_media = isset( $post['extended_entities']['media'] ) ? $post['extended_entities']['media'] : $post['entities']['media'];
		            $post_media_count = count( $post_media );
		            switch ( $post_media[0]['type'] ) {
			            case 'video':
			            case 'animated_gif':
				            $post_media_text     .= ctf_get_fa_el( 'fa-file-video-o' );
				            break;
			            default:
			            	if ( $post_media_count > 1 ) {
					            $post_media_text     .= $post_media_count . ctf_get_fa_el( 'fa-picture-o' );
				            } else {
					            $post_media_text     .= ctf_get_fa_el( 'fa-picture-o' );
				            }
				            break;
		            }
	            } else {
		            unset( $post_media );
	            }

                // include tweet view
                $tweet_html .= '<div class="'. $tweet_classes . '" id="' . $post_id . '" style="' . $feed_options['tweetbgcolor'] . '"' . $retweet_data_att . '>';

                if ( isset( $retweeter ) && ctf_show( 'retweeter', $feed_options ) ) {
                    $tweet_html .= '<div class="ctf-context">';
                    $tweet_html .= '<a href="https://twitter.com/intent/user?screen_name=' . $retweeter['screen_name'] . '" target="_blank" rel="noopener noreferrer" class="ctf-retweet-icon">' . ctf_get_fa_el( 'fa-retweet' ) . '<span class="ctf-screenreader">'.__( 'Retweet on Twitter', 'custom-twitter-feeds' ).'</span></a>';
                    $tweet_html .= '<a href="https://twitter.com/' . $retweeter['screen_name'] . '" target="_blank" rel="noopener noreferrer" class="ctf-retweet-text" style="' . $feed_options['authortextsize'] . $feed_options['authortextweight'] . $feed_options['textcolor'] . '">' . $retweeter['name'] . ' ' . __( $feed_options['retweetedtext'], 'custom-twitter-feeds' ) . '</a>';
                    $tweet_html .= '</div>';
                }

	            if ( ctf_show( 'avatar', $feed_options ) || ctf_show( 'logo', $feed_options ) || ctf_show( 'author', $feed_options ) || ctf_show( 'date', $feed_options ) ) {

		            $tweet_html .= '<div class="ctf-author-box">';
		            $tweet_html .= '<div class="ctf-author-box-link" style="' . $feed_options['authortextsize'] . $feed_options['authortextweight'] . $feed_options['textcolor'] . '">';
		            if ( ctf_show( 'avatar', $feed_options ) ) {
			            $tweet_html .= '<a href="https://twitter.com/' . $post['user']['screen_name'] . '" class="ctf-author-avatar" target="_blank" rel="noopener noreferrer" style="' . $feed_options['authortextsize'] . $feed_options['authortextweight'] . $feed_options['textcolor'] . '">';
			            if ( CTF_GDPR_Integrations::doing_gdpr( $feed_options ) ) {
				            $tweet_html .= '<span data-avatar="' . esc_url( $post['user']['profile_image_url_https'] ) . '" data-alt="' . $post['user']['screen_name'] . '">Avatar</span>';
			            } else {
				            $tweet_html .= '<img src="' . esc_url( $post['user']['profile_image_url_https'] ) . '" alt="' . $post['user']['screen_name'] . '" width="48" height="48">';
			            }
			            $tweet_html .= '</a>';
		            }

		            if ( ctf_show( 'author', $feed_options ) ) {
			            $tweet_html .= '<a href="https://twitter.com/' . $post['user']['screen_name'] . '" target="_blank" rel="noopener noreferrer" class="ctf-author-name" style="' . $feed_options['authortextsize'] . $feed_options['authortextweight'] . $feed_options['textcolor'] . '">' . $post['user']['name'] . '</a>';
			            if ( $post['user']['verified'] == 1 ) {
				            $tweet_html .= '<span class="ctf-verified" >' . ctf_get_fa_el( 'fa-check-circle' ) . '</span>';
			            }
			            $tweet_html .= '<a href="https://twitter.com/' . $post['user']['screen_name'] . '" class="ctf-author-screenname" target="_blank" rel="noopener noreferrer" style="' . $feed_options['authortextsize'] . $feed_options['authortextweight'] . $feed_options['textcolor'] . '">@' . $post['user']['screen_name'] . '</a>';
			            $sep_style_att = ! empty( $feed_options['authortextsize'] ) ? ' style="' . $feed_options['authortextsize'] . '"' : '';
			            $tweet_html .= '<span class="ctf-screename-sep"' . $sep_style_att . '>&middot;</span>';
		            }

		            if ( ctf_show( 'date', $feed_options ) ) {
			            $tweet_html .= '<div class="ctf-tweet-meta">';
			            //https://twitter.com/EnterLaw/status/869452491041243137
			            $tweet_html .= '<a href="https://twitter.com/' . $post['user']['screen_name'] . '/status/' . $post['id_str'] . '" class="ctf-tweet-date" target="_blank" rel="noopener noreferrer" style="' . $feed_options['datetextsize'] . $feed_options['datetextweight'] . $feed_options['textcolor'] . '">' . ctf_get_formatted_date( $post['created_at'], $feed_options, $post['user']['utc_offset'] ) . '</a>';
			            $tweet_html .= '</div>';
		            } // show date
		            $tweet_html .= '</div>';
		            if ( ctf_show( 'logo', $feed_options ) ) {
			            $tweet_html .= '<div class="ctf-corner-logo" style="' . $feed_options['logosize'] . $feed_options['logocolor'] . '">';
			            $tweet_html .= ctf_get_fa_el( 'fa-twitter' );
			            $tweet_html .= '</div>';
		            }
		            $tweet_html .= '</div>';
	            }

                if ( ctf_show( 'text', $feed_options ) ) {
	                $post_text = apply_filters( 'ctf_tweet_text', $post['text'], $feed_options, $post );

                    $tweet_html .= '<div class="ctf-tweet-content">';

                    if ( $feed_options['linktexttotwitter'] ) {
                        $tweet_html .= '<a class="ctf-tweet-text-link" href="https://twitter.com/' .$post['user']['screen_name'] . '/status/' . $post['id_str'] . '" target="_blank" rel="noopener noreferrer">';
                        $tweet_html .= '<p class="ctf-tweet-text" style="' . $feed_options['tweettextsize'] . $feed_options['tweettextweight'] . $feed_options['textcolor'] . '">' . nl2br( $post_text ) . $post_media_text .'</p>';
                        $tweet_html .= '</a>';
                    } else {
                        $tweet_html .= '<p class="ctf-tweet-text" style="' . $feed_options['tweettextsize'] . $feed_options['tweettextweight'] . $feed_options['textcolor'] . '">' . nl2br( $post_text );

	                    if( $post_media_count > 0 ){
		                    $multi_class = '';
		                    if ( $post_media_count > 1 ) {
			                    $multi_class     = ' ctf-multi-media-icon';
		                    }
		                    if ( $feed_options['disablelinks'] ) {
			                    $tweet_html .= '<span class="ctf-tweet-text-media-wrap' . $multi_class . '">' . $post_media_text . '</span>' . '</p>';
		                    } else {
			                    $tweet_html .= '</p><a href="https://twitter.com/' .$post['user']['screen_name'] . '/status/' . $post['id_str'] . '" target="_blank" rel="noopener noreferrer" class="ctf-tweet-text-media-wrap' . $multi_class . '">' . $post_media_text . '</a>';
		                    }
	                    }
                    } // link text to twitter option is selected

                    $tweet_html .= '</div>';
                } // show tweet text

                if ( ctf_show( 'linkbox', $feed_options ) && isset( $quoted ) ) {
                    $tweet_html .= '<a href="https://twitter.com/' . $quoted['user']['screen_name'] . '/status/' . $quoted['id_str'] . '" class="ctf-quoted-tweet" style="' . $feed_options['quotedauthorsize'] . $feed_options['quotedauthorweight'] . $feed_options['textcolor'] . '" target="_blank" rel="noopener noreferrer">';
                    $tweet_html .= '<span class="ctf-quoted-author-name">' . $quoted['user']['name'] . '</span>';

                    if ($quoted['user']['verified'] == 1) {
                        $tweet_html .= '<span class="ctf-quoted-verified">' . ctf_get_fa_el( 'fa-check-circle' ) . '</span>';
                    } // user is verified
	                $quoted_text = apply_filters( 'ctf_quoted_tweet_text', $quoted['text'], $feed_options, $quoted );

                    $tweet_html .= '<span class="ctf-quoted-author-screenname">@' . $quoted['user']['screen_name'] . '</span>';
                    $tweet_html .= '<p class="ctf-quoted-tweet-text" style="' . $feed_options['tweettextsize'] . $feed_options['tweettextweight'] . $feed_options['textcolor'] . '">' . nl2br( $quoted_text ) . $quoted_media_text . '</p>';
		                //$tweet_html .= ;
                    $tweet_html .= '</a>';
                }// show link box

                $tweet_html .= '<div class="ctf-tweet-actions">';
                if ( ctf_show( 'actions', $feed_options ) ) {
	                $tweet_html .= '<a href="https://twitter.com/intent/tweet?in_reply_to=' . $post['id_str'] . '&related=' . $post['user']['screen_name'] . '" class="ctf-reply" target="_blank" rel="noopener noreferrer" style="' . $feed_options['iconsize'] . $feed_options['iconcolor'] . '">' . ctf_get_fa_el( 'fa-reply' ) . '<span class="ctf-screenreader">Reply on Twitter ' . $post['id_str'] . '</span></a>';
	                $tweet_html .= '<a href="https://twitter.com/intent/retweet?tweet_id=' . $post['id_str'] . '&related=' . $post['user']['screen_name'] . '" class="ctf-retweet" target="_blank" rel="noopener noreferrer" style="' . $feed_options['iconsize'] . $feed_options['iconcolor'] . '">' . ctf_get_fa_el( 'fa-retweet' ) . '<span class="ctf-screenreader">Retweet on Twitter ' . $post['id_str'] . '</span><span class="ctf-action-count ctf-retweet-count">';
                    if ( $post['retweet_count'] > 0 ) {
                        $tweet_html .= $post['retweet_count'];
                    }
                    $tweet_html .= '</span></a>';
	                $tweet_html .= '<a href="https://twitter.com/intent/like?tweet_id=' . $post['id_str'] . '&related=' . $post['user']['screen_name'] . '" class="ctf-like" target="_blank" rel="noopener noreferrer" style="' . $feed_options['iconsize'] . $feed_options['iconcolor'] . '">' . ctf_get_fa_el( 'fa-heart' ) . '<span class="ctf-screenreader">Like on Twitter ' . $post['id_str'] . '</span><span class="ctf-action-count ctf-favorite-count">';
                    if ( $post['favorite_count'] > 0 ) {
                        $tweet_html .= $post['favorite_count'];
                    }
                    $tweet_html .= '</span></a>';
                }
                if ( ctf_show( 'twitterlink', $feed_options ) ) {
	                $tweet_html .= '<a href="https://twitter.com/' . $post['user']['screen_name'] . '/status/' . $post['id_str'] . '" class="ctf-twitterlink" style="' . $feed_options['textcolor'] . '" target="_blank" rel="noopener noreferrer">' . esc_html( $feed_options['twitterlinktext'] ) . ' <span class="ctf-screenreader">' . $post['id_str'] . '</span></a>';
                } // show twitter link or actions
                $tweet_html .= '</div>';
                $tweet_html .= '</div>';

                $i++;
            }
        }
        return $tweet_html;
    }

    /**
     * displays a message if there is an error in the feed
     *
     * @return string error html
     */
    public function getErrorHtml()
    {
        $error_html = '';
        $error_html .= '<div id="ctf" class="ctf" data-ctfshortcode="' . $this->getShortCodeJSON() . '">';
        $error_html .= '<div class="ctf-error">';
        $error_html .= '<div class="ctf-error-user">';

        $error_html .= '</div>';

        if ( current_user_can( 'manage_options' ) ) {
            $error_html .= '<div class="ctf-error-admin">';

            if ( ! empty( $this->api_obj->api_error_no ) ) {

	            $error_html .= '<p>Unable to load Tweets</p>';
	            $error_html .= '<a class="twitter-share-button"';
	            $error_html .= 'href="https://twitter.com/share"';
	            $error_html .= 'data-size="large"';
	            $error_html .= 'data-url="' . get_the_permalink() . '"';
	            $error_html .= 'data-text="Check out this website">';
	            $error_html .= '</a>';

	            if ( !empty( $this->feed_options['screenname'] ) ) {
		            $error_html .= '<a class="twitter-follow-button"';
		            $error_html .= 'href="https://twitter.com/' . $this->feed_options['screenname'] . '"';
		            $error_html .= 'data-show-count="false"';
		            $error_html .= 'data-size="large"';
		            $error_html .= 'data-dnt="true">Follow</a>';
	            }

	            $error_html .= '<p><b>This message is only visible to admins:</b><br />';
	            $error_html .= 'An error has occurred with your feed.<br />';
	            if ( $this->missing_credentials ) {
		            $error_html .= 'There is a problem with your access token, access token secret, consumer token, or consumer secret<br />';
	            }
	            if ( isset( $this->errors['error_message'] ) ) {
		            $error_html .= $this->errors['error_message'] . '<br />';
	            }
	            $error_html .= 'The error response from the Twitter API is the following:<br />';
	            $error_html .= '<code>Error number: ' . $this->api_obj->api_error_no . '<br />';
	            $error_html .= 'Message: ' . $this->api_obj->api_error_message . '</code>';
	            $error_html .= '<a href="https://smashballoon.com/custom-twitter-feeds/docs/errors/?utm_campaign=twitter-free&utm_source=frontend&utm_medium=errormessage" target="_blank" rel="noopener noreferrer">Click here to troubleshoot</a></p>';


            }

            $error_html .= '</div>';
        }
        $error_html .= '</div>'; // end .ctf-error
        $error_html .= '</div>'; // end #ctf

        return $error_html;
    }
}