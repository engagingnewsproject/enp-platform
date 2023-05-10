<?php
/**
 * Class CtfFeed
 *
 * Creates the settings for the feed and outputs the html
 */
namespace TwitterFeed;

use TwitterFeed\CTF_Parse;
use TwitterFeed\CtfCache;
use TwitterFeed\CTF_Feed;
use TwitterFeed\CTF_Settings;
use TwitterFeed\CtfOauthConnect;
use TwitterFeed\CTF_Feed_Locator;
use TwitterFeed\CTF_GDPR_Integrations;
use TwitterFeed\Builder\CTF_Feed_Builder;

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
     * @var string
     */
    public $feed_id;

    /**
     * @var CtfCache
     */
    public $cache;

    /**
     * @var boolean
     */
    public $is_legacy;

    /**
     * @var boolean
     */
    public $unique_legacy_id;

    /**
     * retrieves and sets options that apply to the feed
     *
     * @param array $atts           data from the shortcode
     * @param string $last_id_data  the last visible tweet on the feed, empty string if first set
     * @param int $num_needed_input this number represents the number left to retrieve after the first set
     */
    public function __construct($atts, $last_id_data, $num_needed_input, $preview_settings = false) {
        //$this->atts = $atts;
        $this->atts = CTF_Settings::filter_atts_for_legacy($atts);
        $this->raw_shortcode_atts = CTF_Settings::filter_atts_for_legacy($atts);
        if( isset($this->atts['feed']) ){
            $atts['feed'] = $this->atts['feed'];
        }

        $this->last_id_data = $last_id_data;
        $this->num_needed_input = $num_needed_input;
        $this->db_options = get_option( 'ctf_options', array() );
        $this->is_legacy = false;
        if ( ! empty( $atts['feed'] ) && $atts['feed'] !== 'legacy' ) {
            $this->feed_id = $this->atts['feed'];
            $this->feed_options = CTF_Settings::get_settings_by_feed_id( $atts['feed'], $preview_settings );
            if($this->feed_options == false){
                $this->feed_options['feederror'] = true;
            }else{
                $this->feed_options['feed'] = $this->feed_id;
                $this->atts = $this->feed_options;
                add_action( 'wp_footer', [ $this, 'get_feed_style' ] );
            }

            /*
            if(self::get_legacy_feed_settings()){
                $this->is_legacy = true;
            }
            */
        }else{
            $ctf_statuses = get_option( 'ctf_statuses', array() );
            if ( ! empty( $ctf_statuses['support_legacy_shortcode'] ) ) {
                if($preview_settings !== false && is_array($preview_settings)){
                    $this->feed_options = wp_parse_args( $this->atts, $preview_settings );
                }else{
                    $legacy_settings_option = self::get_legacy_feed_settings();
                    if ( empty( $legacy_settings_option ) ) {
                        $this->feed_options = CTF_Settings::legacy_shortcode_atts( $this->atts, $this->db_options );
                    } else {
                        $this->feed_options = wp_parse_args( $this->atts, $legacy_settings_option );
                    }
                }
                $this->atts = wp_parse_args( $this->atts, \TwitterFeed\Builder\CTF_Feed_Saver::settings_defaults() );
                $this->feed_options = wp_parse_args( $this->feed_options, $this->atts );
                $this->feed_options['persistentcache'] = $this->atts['persistentcache'];

                add_action( 'wp_footer', [ $this, 'get_feed_style' ] );
            }
            $this->unique_legacy_id = rand( 1, time() );
            $this->is_legacy = true;

        }
        if ( ! empty( $this->feed_options ) ) {
            $this->feed_options['customizer'] = isset($atts['customizer']) && $atts['customizer'] == true ? true : false;
        }
        if ( ! empty( $atts['feed'] ) ) {
            $this->feed_options['feed'] = $atts['feed'];
        }
        $this->feed_options['selfreplies'] = false;
        $this->feed_options['includereplies'] = false;
        $this->feed_options['is_legacy'] = $this->is_legacy;
    }

    /**
     * creates and returns all of the data needed to generate the output for the feed
     *
     * @param array $atts           data from the shortcode
     * @param string $last_id_data  the last visible tweet on the feed, empty string if first set
     * @param int $num_needed_input this number represents the number left to retrieve after the first set
     * @return CtfFeed                 the complete object for the feed
     */
    public static function init( $atts, $last_id_data = '', $num_needed_input = 0, $ids_to_remove = array(), $persistent_index = 1, $preview_settings = false   ){
        if ( empty( $atts['feed'] ) ) {
            $ctf_statuses = get_option( 'ctf_statuses', array() );
            if ( empty( $ctf_statuses['support_legacy_shortcode'] ) ) {
                if ( empty( $atts ) ) {
                    $atts = array();
                }
                $atts['feed'] = 1;
            }
        }

        $feed = new CtfFeed( $atts, $last_id_data, $num_needed_input, $preview_settings );
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
    public function setFeedOptions(){
        $this->feed_options['num'] = isset($this->feed_options['num']) && !empty($this->feed_options['num']) ? $this->feed_options['num'] : 1;
        $this->setFeedTypeAndTermOptions();
        $this->setAccessTokenAndSecretOptions();
        $this->setConsumerKeyAndSecretOptions();
        $db_only =  array(
            'request_method'
        );
        $this->setDatabaseOnlyOptions( $db_only );
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
    protected function setTweetSet(){
        $this->setTransientName();
        if ( ! empty( $this->feed_options['feed'] ) ) {
           $feed_id = $this->feed_options['feed'];
        } else {
            $feed_id = 'legacy';
        }
        if ( ! empty( $this->last_id_data ) ) {
            $page = $this->last_id_data;
        } else {
            $page = '';
        }
        $this->cache = new CtfCache( $feed_id, $this->feed_options['cache_time'], $page );
        $success = $this->maybeSetTweetsFromCache();
        $is_persistent = $this->feed_options['persistentcache'] && ($this->feed_options['type'] == 'search' || $this->feed_options['type'] == 'hashtag');
        if ( ! $success && ! $is_persistent) {
            $this->maybeSetTweetsFromTwitter();
        } elseif ( ! $success && $is_persistent ) {
            $this->errors['error_message'] = 'No Tweets returned';
            if (empty($this->api_obj)) {
                $this->api_obj = new \stdClass();
            }
            $this
                ->api_obj->api_error_no = '';
            $this
                ->api_obj->api_error_message = 'No Tweets returned';
            $this->tweet_set = false;
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
	protected function setConsumerKeyAndSecretOptions(){
		if (! empty( $this->db_options['consumer_key'] ) && ! empty($this->db_options['consumer_secret'] )) {
			$this->feed_options['consumer_key']    = isset($this->db_options['consumer_key']) && strlen($this->db_options['consumer_key']) > 15 ? $this->db_options['consumer_key'] : 'FPYSYWIdyUIQ76Yz5hdYo5r7y';
			$this->feed_options['consumer_secret'] = isset($this->db_options['consumer_secret']) && strlen($this->db_options['consumer_secret']) > 30 ? $this->db_options['consumer_secret'] : 'GqPj9BPgJXjRKIGXCULJljocGPC62wN2eeMSnmZpVelWreFk9z';
		} else {
			$this->feed_options['consumer_key']    = 'FPYSYWIdyUIQ76Yz5hdYo5r7y';
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
    public function setDimensionOptions(){
        $this->feed_options['width'] = isset( $this->atts['width'] ) ? 'width: '. esc_attr( $this->atts['width'] ) .';' : ( ( isset( $this->db_options['width'] ) && $this->db_options['width'] != '' ) ? 'width: '. esc_attr( $this->db_options['width'] ) . ( isset( $this->db_options['width_unit'] ) ? esc_attr( $this->db_options['width_unit'] ) : '%' ) . ';' : '' );
        $this->feed_options['height'] = isset( $this->atts['height'] ) ? 'height: '. esc_attr( $this->atts['height'] ) .';' : ( ( isset( $this->db_options['height'] ) && $this->db_options['height'] != '' ) ? 'height: '. esc_attr( $this->db_options['height'] ) . ( isset( $this->db_options['height_unit'] ) ? esc_attr( $this->db_options['height_unit'] ) : 'px' ) . ';' : '' );
    }

    /**
     * sets the cache time based on user input
     */
    public function setCacheTimeOptions(){
        if ( ! empty( $this->raw_shortcode_atts ) && ! empty( $this->raw_shortcode_atts['doingcronupdate'] ) ) {
            $this->feed_options['cache_time'] = 60;
            return;
        }
        $user_cache = isset($this->db_options['cache_time']) ? ((int)$this->db_options['cache_time'] * (int)$this->db_options['cache_time_unit']) : HOUR_IN_SECONDS;
        $caching_type = ! empty( $this->db_options['ctf_caching_type'] ) ? $this->db_options['ctf_caching_type'] : 'page';
        if ( empty( $this->raw_shortcode_atts['feed'] ) || $caching_type === 'page' ) {
            $this->feed_options['cache_time'] = max($user_cache, 60);
        } else {
            $this->feed_options['cache_time'] = DAY_IN_SECONDS + HOUR_IN_SECONDS;
        }
    }


    /**
     * sets the number of tweets to retrieve
     */
    public function setTweetsToRetrieve(){
        $min_tweets_to_retrieve = 10;

        if ($this->num_needed_input < 1) {

            if ($this->feed_options['num'] < 10) {
                $count = max(round((int)$this->feed_options['num'] * (float)$this->feed_options['multiplier'] * 1.6) , $min_tweets_to_retrieve);
            }
            elseif ($this->feed_options['num'] < 30) {
                $count = round((int)$this->feed_options['num'] * (float)$this->feed_options['multiplier'] * 1.2);
            }
            else {
                $count = round((int)$this->feed_options['num'] * (float)$this->feed_options['multiplier']);
            }
        }
        else {
            $count = max($this->num_needed_input, 50);
            $this->feed_options['num'] = $this->num_needed_input;
        }

        $this->feed_options['count'] = min($count, 200);

    }

    /**
     * sets the feed type and associated parameter
     */
    public function setFeedTypeAndTermOptions(){
        $this->feed_options['screenname'] = isset( $this->atts['screenname'] ) ? $this->atts['screenname'] : ( isset( $this->feed_options['usertimeline_text'] ) ? $this->feed_options['usertimeline_text'] : '' );

        if ( isset( $this->atts['home'] ) && $this->atts['home'] == 'true' ) {
            $this->feed_options['type'] = 'hometimeline';
        }
        if ( isset( $this->atts['screenname'] ) && !empty($this->atts['screenname'] )) {
            $this->feed_options['type'] = 'usertimeline';
            $this->feed_options['feed_term'] = isset( $this->atts['screenname'] ) ? ctf_validate_usertimeline_text( $this->atts['screenname'] ) : ( ( isset( $this->feed_options['usertimeline_text'] ) ) ? $this->feed_options['usertimeline_text'] : '' );
            $this->feed_options['screenname'] = $this->feed_options['feed_term'];
        }
        if (  isset( $this->atts['hashtag'] )  && !empty($this->atts['hashtag'] )) {
            $this->feed_options['type'] = 'hashtag';
            $this->working_term = isset( $this->atts['hashtag'] ) ? $this->atts['hashtag'] : ( isset( $this->feed_options['hashtag'] ) ? $this->feed_options['hashtag'] : '' );
            $this->feed_options['hashtag_text'] = $this->working_term;
            $this->feed_options['feed_term'] = isset( $this->working_term ) ? ctf_validate_search_text( $this->working_term ) . ' -filter:retweets' : ( ( isset( $this->db_options['search_text'] ) ) ? $this->db_options['search_text'] . ' -filter:retweets' : '' );
            $this->check_for_duplicates = true;
        }

        if ( empty($this->feed_options['type'])) {
            $this->feed_options['type'] = isset( $this->atts['type'] ) ? $this->atts['type'] : 'usertimeline';
            switch ( $this->feed_options['type'] ) {
                case 'usertimeline':
                $this->feed_options['feed_term'] = isset( $this->atts['usertimeline_text'] ) ? $this->atts['usertimeline_text'] : '';
                break;
                case 'hometimeline':
                $this->feed_options['type'] = 'hometimeline';
                break;
                case 'hashtag':
                    $this->feed_options['feed_term'] = isset( $this->atts['search_text'] ) ? $this->atts['search_text'] . ' -filter:retweets' : '';
                    $this->check_for_duplicates = true;
                break;
            }
        }
        if($this->feed_options['type'] == 'usertimeline'){
            $this->feed_options['feed_term'] = isset( $this->atts['screenname'] ) ? ctf_validate_usertimeline_text( $this->atts['screenname'] ) : ( ( isset( $this->feed_options['usertimeline_text'] ) ) ? $this->feed_options['usertimeline_text'] : '' );
            $this->feed_options['usertimeline_text'] = $this->feed_options['feed_term'];
            $this->feed_options['screenname'] = $this->feed_options['feed_term'];
        }
        $this->feed_options['includeretweets'] = true;

        if($this->is_legacy && !empty($this->feed_options['type'])){
            $this->feed_options['type'] = $this->feed_options['type'] == 'search' ? 'hashtag' : $this->feed_options['type'];
            $this->set_legacy_terms_options();
        }
    }

    public function set_legacy_terms_options(){
        switch ($this->feed_options['type']) {
                case 'usertimeline':
                $this->feed_options['type'] = 'usertimeline';
                $this->feed_options['feed_term'] = isset( $this->atts['screenname'] ) ? ctf_validate_usertimeline_text( $this->atts['screenname'] ) : ( ( isset( $this->feed_options['usertimeline_text'] ) ) ? $this->feed_options['usertimeline_text'] : '' );
                $this->feed_options['screenname'] = $this->feed_options['feed_term'];
                $this->feed_options['usertimeline_text'] = $this->feed_options['screenname'];
            break;
            case 'hometimeline':
                $this->feed_options['type'] = 'hometimeline';
            break;
            case 'hashtag':
                $this->feed_options['type'] = 'hashtag';
                $this->working_term = isset( $this->atts['hashtag'] ) ? $this->atts['hashtag'] : ( isset( $this->feed_options['hashtag'] ) ? $this->feed_options['hashtag'] : '' );
                $this->feed_options['hashtag_text'] = $this->working_term;
                $this->feed_options['feed_term'] = isset( $this->working_term ) ? ctf_validate_search_text( $this->working_term ) . ' -filter:retweets' : ( ( isset( $this->db_options['search_text'] ) ) ? $this->db_options['search_text'] . ' -filter:retweets' : '' );
                $this->check_for_duplicates = true;
                $this->feed_options['persistentcache'] = true;
            break;
        }
    }

    /**
     * sets the visible parts of each tweet for the feed
     */
    public function setIncludeExcludeOptions(){
        $this->feed_options['tweet_includes'] = array();
        $this->feed_options['tweet_excludes'] = array();
        $this->feed_options['tweet_includes'] = isset($this->atts['include']) ? explode(',', str_replace(', ', ',', $this->atts['include'])) : array();
        $legacy_atts_include = isset($this->raw_shortcode_atts['include']) ? explode(',', str_replace(', ', ',', $this->raw_shortcode_atts['include'])) : array();
        $legacy_atts_exclude = isset($this->raw_shortcode_atts['exclude']) ? explode(',', str_replace(', ', ',', $this->raw_shortcode_atts['exclude'])) : array();
        if ( ! empty( $legacy_atts_include ) ) {
            if ( in_array( 'author', $legacy_atts_include, true ) ) {
                $this->feed_options['tweet_includes'][] = 'author_text';
            }
        }
        if ( ! empty( $legacy_atts_exclude ) ) {
            if ( in_array( 'author', $legacy_atts_exclude, true ) ) {
                $this->atts['exclude'] .= ',author_text';
            }
        }
        if (empty($this->feed_options['tweet_includes'][0])) {
            $this->feed_options['tweet_excludes'] = isset($this->atts['exclude']) ? explode(',', str_replace(', ', ',', $this->atts['exclude'])) : array();
        }
        if (empty($this->feed_options['tweet_excludes'][0]) && empty($this->feed_options['tweet_includes'][0])) {
            $this->feed_options['tweet_includes'][] = isset($this->feed_options['include_retweeter']) && $this->feed_options['include_retweeter'] == false ? null : 'retweeter';
            $this->feed_options['tweet_includes'][] = isset($this->feed_options['include_avatar']) && $this->feed_options['include_avatar'] == false ? null : 'avatar';
            $this->feed_options['tweet_includes'][] = isset($this->feed_options['include_author']) && $this->feed_options['include_author'] == false ? null : 'author';
            $this->feed_options['tweet_includes'][] = isset($this->feed_options['include_author_text']) && $this->feed_options['include_author_text'] == false ? null : 'author_text';

            $this->feed_options['tweet_includes'][] = isset($this->feed_options['include_logo']) && $this->feed_options['include_logo'] == false ? null : 'logo';
            $this->feed_options['tweet_includes'][] = isset($this->feed_options['include_text']) && $this->feed_options['include_text'] == false ? null : 'text';
            $this->feed_options['tweet_includes'][] = isset($this->feed_options['include_date']) && $this->feed_options['include_date'] == false ? null : 'date';
            $this->feed_options['tweet_includes'][] = isset($this->feed_options['include_actions']) && $this->feed_options['include_actions'] == false ? null : 'actions';
            $this->feed_options['tweet_includes'][] = isset($this->feed_options['include_twitterlink']) && $this->feed_options['include_twitterlink'] == false ? null : 'twitterlink';
            $this->feed_options['tweet_includes'][] = isset($this->feed_options['include_linkbox']) && $this->feed_options['include_linkbox'] == false ? null : 'linkbox';
            $this->feed_options['tweet_includes'][] = isset($this->feed_options['include_replied_to']) && $this->feed_options['include_replied_to'] == false ? null : 'repliedto';
            $this->feed_options['tweet_includes'][] = isset($this->feed_options['include_media']) && $this->feed_options['include_media'] == false ? null : 'media';
            $this->feed_options['tweet_includes'][] = isset($this->feed_options['include_twittercards']) && $this->feed_options['include_twittercards'] == false ? null : 'twittercards';
        }

    }

    /**
     * sets the transient name for the caching system
     */
    public function setTransientName()
    {

        $this->transient_name = 'ctf____' . $this->last_id_data;

        $last_id_data = substr($this->last_id_data, -5, 5);
        $num = isset($this->feed_options['num']) && !empty($this->feed_options['num']) ? $this->feed_options['num'] : 1;
        $reply = $this->feed_options['includereplies'] === true ? 'r' : '';
        $includewords = !empty($this->feed_options['includewords']) ? substr(str_replace(array(
            ',',
            ' '
        ) , '', $this->feed_options['includewords']) , 0, 10) : '';
        $excludewords = !empty($this->feed_options['excludewords']) ? substr(str_replace(array(
            ',',
            ' '
        ) , '', $this->feed_options['excludewords']) , 0, 5) : '';
        $noretweets = !$this->feed_options['includeretweets'] ? 'n' : '';
        $remove_by_id_array = explode(',', str_replace(' ', '', $this->feed_options['remove_by_id']));
        $remove_by_id_str = '';
        $feedID = (!empty($this->atts['feedid'])) ? $this->atts['feedid'] . '_' : '';

        if (!empty($remove_by_id_array)) {
            foreach ($remove_by_id_array as $id) {
                $remove_by_id_str .= substr($id, -3, 3);
            }
        }

        switch ($this->feed_options['type']) {
            case 'hometimeline':
                $this->transient_name = 'ctf_' . $feedID . $last_id_data . 'home' . $num . $reply . $includewords . $remove_by_id_str . $excludewords . $noretweets;
            break;
            case 'usertimeline':
                $screenname = isset($this->feed_options['screenname']) ? $this->feed_options['screenname'] : '';
                $this->transient_name = substr('ctf__' . $feedID . $last_id_data . $screenname . $num . $reply . $includewords . $remove_by_id_str . $excludewords . $noretweets, 0, 45);
            break;
            case 'search':
                $hashtag = isset($this->feed_options['feed_term']) ? $this->feed_options['feed_term'] : '';
                $this->transient_name = substr('ctf_' . $feedID . $last_id_data . substr($hashtag, 0, 20) . $includewords . $num . $reply . $remove_by_id_str . $excludewords . $noretweets, 0, 45);
            break;
            case 'hashtag':
                $hashtag = isset($this->feed_options['feed_term']) ? str_replace(' -filter:retweets', '', $this->feed_options['feed_term']) : '';
                $this->transient_name = substr('ctf_' . $feedID . $last_id_data . substr($hashtag, 0, 20) . $includewords . $num . $reply . $remove_by_id_str . $excludewords . $noretweets, 0, 45);
            break;
            case 'mentionstimeline':
                $this->transient_name = 'ctf_' . $feedID . $last_id_data . 'mentions' . $num . $includewords . $remove_by_id_str . $excludewords . $noretweets;
            break;
            case 'lists':
                $list = isset($this->feed_options['feed_term']) ? $this->feed_options['feed_term'] : '';
                $this->transient_name = substr('ctf_' . $feedID . $last_id_data . $list . $num . $reply . $includewords . $remove_by_id_str . $excludewords, 0, 45);
            break;
            default:
                if (!empty($this->feed_options['feed_types_and_terms'])) {
                    $names = $this->feed_options['feed_types_and_terms'];
                    $working_name = '';
                    foreach ($names as $name) {
                        $working_name .= substr($name[1], 0, 3);
                    }
                    $this->transient_name = substr('ctf__' . $feedID . $last_id_data . $working_name . $num . $reply . $includewords . $remove_by_id_str . $excludewords . $noretweets, 0, 45);
                    break;
                }

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
   public function maybeSetTweetsFromCache() {
        if ($this->feed_options['persistentcache'] && ($this->feed_options['type'] == 'search' || $this->feed_options['type'] == 'hashtag')) {
            $persistent_cache_tweets = $this->persistentCacheTweets();
            if (is_array($persistent_cache_tweets)) {
                $this->transient_data = array_slice($persistent_cache_tweets, ($this->persistent_index - $this->feed_options['num'] - 1) , $this->persistent_index);
            }
            else {
                $this->transient_data = $persistent_cache_tweets;
            }
        }
        else {
            $this->transient_data = $this->cache->get_transient($this->transient_name);
            if (!is_array($this->transient_data)) {
                $this->transient_data = json_decode($this->transient_data, $assoc = true);
            }

            if ($this->feed_options['cache_time'] <= 0) {
                return $this->tweet_set = false;
            }
        }
        // validate the transient data
	   if ($this->transient_data) {

		   if ( is_array( $this->transient_data ) && ! empty( $this->transient_data[0] ) && $this->transient_data[0] === 'error' ) {
			   $this->tweet_set = array();
			   return true;
		   }

            $this->errors['cache_status'] = $this->validateCache();
            if ($this->errors['cache_status'] === false) {
                return $this->tweet_set = $this->transient_data;
            }
            else {
                return $this->tweet_set = false;
            }
        }
        else {
            $this->errors['cache_status'] = 'none found';
            return $this->tweet_set = false;
        }
    }

 private function persistentCacheTweets() {
        // if cache exists get cached data
        $includewords = !empty($this->feed_options['includewords']) ? substr(str_replace(array(
            ',',
            ' '
        ) , '', $this->feed_options['includewords']) , 0, 10) : '';
        $excludewords = !empty($this->feed_options['excludewords']) ? substr(str_replace(array(
            ',',
            ' '
        ) , '', $this->feed_options['excludewords']) , 0, 5) : '';
        $feedID = (!empty($this->atts['feedid'])) ? $this->atts['feedid'] . '_' : '';
        $cache_name = substr('ctf_!_' . $feedID . $this->feed_options['feed_term'] . $includewords . $excludewords, 0, 45);
        if ($this->feed_options['type'] === 'hashtag') {
            $cache_name = str_replace(' -filter:retweets', '', $cache_name);
        }
        $cache_time_limit_reached = $this->cache->get_transient($cache_name) ? false : true;

        $existing_cache = $this->cache->get_persistent( $cache_name );

        if ($existing_cache && !is_array($existing_cache)) {
            $existing_cache = json_decode($existing_cache, $assoc = true);
        }

        $this->persistent_index = (int)$this->persistent_index + (int)$this->feed_options['num'];

        $this->feed_options['count'] = 200;

        if (!empty($this->last_id_data) || (!$cache_time_limit_reached && $existing_cache !== false)) {
            return $existing_cache;
        }
        elseif ($existing_cache) {
            // use "since-id" to look for more in an api request
            $since_id = $existing_cache[0]['id_str'];
            $api_obj = $this->getTweetsSinceID($since_id, 'search', $this->feed_options['feed_term'], $this->feed_options['count']);
            // add any new tweets to the cache
            $tweet_set = json_decode($api_obj->json, $assoc = true);

            $tweet_set = isset($tweet_set['statuses']) ? $tweet_set['statuses'] : array();

            // add a transient to delay another api retrieval
            $this->cache->set_transient($cache_name, true, $this->feed_options['cache_time']);

            if (empty($tweet_set)) {
                $existing_cache = $this->filterTweetSet($existing_cache, false);
                $cache_set = json_encode($existing_cache);

                $this->cache->set_persistent($cache_name, $cache_set );
                return $existing_cache;
            }
            else {
                $tweet_set = $this->reduceTweetSetData($tweet_set, false);
            }

            $tweet_set = $this->appendPersistentCacheTweets($existing_cache, $tweet_set);
            $tweet_set = $this->filterTweetSet($tweet_set, false);

            $cache_set = json_encode($tweet_set);

            $this->cache->set_persistent($cache_name, $cache_set );

            return $tweet_set;
            // else if cached data doesn't exist

        }
        else {
            // make a request for last 200 tweets
            $api_obj = $this->apiConnectionResponse('search', $this->feed_options['feed_term']);
            // cache them in a regular option
            $this->tweet_set = json_decode($api_obj->json, $assoc = true);

	        // check for errors/tweets present
            if (isset($this->tweet_set['errors'][0])) {
                if (empty($this->api_obj)) {
                    $this->api_obj = new \stdClass();
                }
                $this
                    ->api_obj->api_error_no = $this->tweet_set['errors'][0]['code'];
                $this
                    ->api_obj->api_error_message = $this->tweet_set['errors'][0]['message'];

                $this->tweet_set = false;
            }

            $tweets = isset($this->tweet_set['statuses']) ? $this->tweet_set['statuses'] : $this->tweet_set;

            if (empty($tweets)) {
                $this->errors['error_message'] = 'No Tweets returned';
                $this->tweet_set = false;
            }
            else {
                $this->tweet_set = $this->reduceTweetSetData($tweets, false);
            }

            if ( empty( $this->tweet_set ) ) {
                $this->tweet_set = array();
            }

            $tweet_set = json_encode($this->tweet_set);
            // create a new persistent cache
            $this->cache->set_persistent( $cache_name, $tweet_set );

            // update list of persistent cache
            $cache_list = get_option('ctf_cache_list', array());

            $cache_list[] = $cache_name;

            update_option('ctf_cache_list', $cache_list, false);

            return $this->tweet_set;
        }

        // add the search parameter to another option that contains a list of all persistent caches available

    }

    /**
     * a check to see if any of the filtering options for the feed are set
     *
     * @return bool whether or not a filter is used for this feed
     */
    private function hasTweetTextFilter() {
        if (!empty($this->feed_options['includewords']) || !empty($this->feed_options['excludewords'])) {
            return true;
        }
        elseif (!empty($this->feed_options['remove_by_id']) || !$this->feed_options['includeretweets']) {
            return true;
        }
        else {
            return false;
        }
    }

private function reduceTweetSetData( $tweet_set, $limit = true ) {
    if ($this->hasTweetTextFilter() || !$this->feed_options['includereplies']) {
        $tweet_set = $this->filterTweetSet($tweet_set, $limit);
    }
    if ( $this->check_for_duplicates ) {
        $this->tweet_set = $this->removeDuplicates( $tweet_set, $limit );
    }

    if ( $this->feed_options['selfreplies'] ) {
        $this->tweet_set = $this->filterTweetSet( $tweet_set, $limit );
    }

    $this->tweet_set = $tweet_set;
    if (isset($tweet_set[0]['created_at'])) {
        $this->tweet_set = CTF_Feed::reduceTweetSetData($tweet_set);
        return $this->tweet_set;
    }
    else {
        return false;
    }
    //$this->trimTweetData( false );
    //return $this->tweet_set;
}

    /**
    * this takes the current set of tweets and processes them until there are
    * enough filtered tweets to create the feed from
    */
    private function filterTweetSet( $tweet_set, $limit = true ){
        $working_tweet_set = isset( $tweet_set['statuses'] ) ? $tweet_set['statuses'] : $tweet_set;
        $usable_tweets = 0;
        if ( $limit ) {
            $tweets_needed = $this->feed_options['count'] + 1; // magic number here should be ADT
        } else {
            $tweets_needed = 200;
        }
        $this->feed_options['selfreplies'] = false;
        $i = 0; // index of working_tweet_set
        $still_setting_filtered_tweets = true;
        while ( $still_setting_filtered_tweets ) { // stays true until the number to display is reached or out of tweets
            if ( isset ( $working_tweet_set[$i] ) ) { // if there is another tweet available
                $retweet_id = isset($working_tweet_set[$i]['retweeted_status']['id_str']) ? $working_tweet_set[$i]['retweeted_status']['id_str'] : '';
                if (!empty($retweet_id) && !$this->feed_options['includeretweets']) {
                    unset($working_tweet_set[$i]);
                }

                if ( !$this->feed_options['selfreplies'] && isset( $working_tweet_set[$i]['in_reply_to_screen_name'] ) ) {
                    unset( $working_tweet_set[$i] );
                }
                if ( $this->feed_options['selfreplies'] && isset( $working_tweet_set[$i]['in_reply_to_screen_name'] ) && $working_tweet_set[$i]['in_reply_to_screen_name'] !== $working_tweet_set[$i]['user']['screen_name']) {
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
    public function maybeSetTweetsFromTwitter() {
        $this->setTweetsToRetrieve();
        if (!isset($this->feed_options['feed_types_and_terms']) || empty($this->feed_options['feed_types_and_terms'])) {
            $feed_term = isset($this->feed_options['feed_term']) ? $this->feed_options['feed_term'] : '';
            if (empty($feed_term) && $this->feed_options['type'] !== 'hometimeline' && $this->feed_options['type'] !== 'mentionstimeline') {
                $this->tweet_set = false;
                return;
            }
            $api_obj = $this->apiConnectionResponse($this->feed_options['type'], $feed_term);

            $this->tweet_set = json_decode($api_obj->json, $assoc = true);

            $working_tweet_set = $this->tweet_set;
            if (!isset($working_tweet_set['errors'][0])) {
                if (isset($working_tweet_set[0])) {
                    $value = array_values(array_slice($working_tweet_set, -1));
                    $this->last_id_data = $value[0]['id_str'];
                }

                $working_tweet_set = $this->reduceTweetSetData($working_tweet_set);
                if ($working_tweet_set === false) {
                    $working_tweet_set = array();
                }
            }

            $num_tweets = is_array($working_tweet_set) ? count($working_tweet_set) : 500;
            // remove the last tweet as it is returned in the next request
            if (!isset($working_tweet_set['errors'][0]) && isset($working_tweet_set[0]) && $num_tweets < $this->feed_options['count']) {
                // remove the last tweet as it is returned in the next request
                $value = array_values(array_slice($working_tweet_set, -1));
                $last_tweet_id = $value[0]['id_str'];
                if ($last_tweet_id === $this->last_id_data) {
                    array_pop($working_tweet_set);
                }

                $original_count = $this->feed_options['count'];
                $this->feed_options['count'] = 200;
                $api_obj = $this->apiConnectionResponse($this->feed_options['type'], $feed_term);
                $tweet_set_to_merge = json_decode($api_obj->json, $assoc = true);

                if (isset($tweet_set_to_merge['statuses'])) {
                    $working_tweet_set = array_merge($working_tweet_set, $tweet_set_to_merge['statuses']);
                }
                elseif (isset($tweet_set_to_merge[0]['created_at'])) {
                    $working_tweet_set = array_merge($working_tweet_set, $tweet_set_to_merge);
                }

                $this->feed_options['count'] = $original_count;
            }

            $this->tweet_set = $working_tweet_set;
        }
        else {
            $working_tweet_set = array();
            foreach ($this->feed_options['feed_types_and_terms'] as $feed_type_and_term) {
                $api_obj = $this->apiConnectionResponse($feed_type_and_term[0], $feed_type_and_term[1]);
                $tweet_set_to_merge = json_decode($api_obj->json, $assoc = true);

                if (isset($tweet_set_to_merge['statuses'])) {
                    $working_tweet_set = array_merge($working_tweet_set, $tweet_set_to_merge['statuses']);
                }
                elseif (isset($tweet_set_to_merge[0]['created_at'])) {
                    $working_tweet_set = array_merge($working_tweet_set, $tweet_set_to_merge);
                }
            }
            if (!isset($working_tweet_set['errors'][0])) {
                if (isset($working_tweet_set[0])) {
                    $value = array_values(array_slice($working_tweet_set, -1));
                    $this->last_id_data = $value[0]['id_str'];
                }
                $working_tweet_set = $this->reduceTweetSetData($working_tweet_set);
                if ($working_tweet_set === false) {
                    $working_tweet_set = array();
                }
            }
            $num_tweets = is_array($working_tweet_set) ? count($working_tweet_set) : 500;

            if (!isset($working_tweet_set['errors'][0]) && $num_tweets < $this->feed_options['count']) {

                $value = array_values(array_slice($working_tweet_set, -1));
                $last_tweet_id = $value[0]['id_str'];
                if ($last_tweet_id === $this->last_id_data) {
                    array_pop($working_tweet_set);
                }
                $original_count = $this->feed_options['count'];
                $this->feed_options['count'] = 200;
                //last_id_data
                foreach ($this->feed_options['feed_types_and_terms'] as $feed_type_and_term) {
                    $api_obj = $this->apiConnectionResponse($feed_type_and_term[0], $feed_type_and_term[1]);
                    $tweet_set_to_merge = json_decode($api_obj->json, $assoc = true);


                    if (isset($tweet_set_to_merge['statuses'])) {
                        $working_tweet_set = array_merge($working_tweet_set, $tweet_set_to_merge['statuses']);
                    }
                    elseif (isset($tweet_set_to_merge[0]['created_at'])) {
                        $working_tweet_set = array_merge($working_tweet_set, $tweet_set_to_merge);
                    }
                }

                $this->feed_options['count'] = $original_count;
            }
        }

        // check for errors/tweets present
        if (isset($this->tweet_set['errors'][0])) {

            if (empty($this->api_obj)) {
                $this->api_obj = new \stdClass();
            }
            $this
                ->api_obj->api_error_no = $this->tweet_set['errors'][0]['code'];
            $this
                ->api_obj->api_error_message = $this->tweet_set['errors'][0]['message'];
        }

        $tweets = isset($this->tweet_set['statuses']) ? $this->tweet_set['statuses'] : $this->tweet_set;
        if (empty($tweets)) {
            if ( empty( $this->tweet_set['errors'][0]['message'] ) ) {
                $this->errors['error_message'] = 'No Tweets returned';
	            $this->tweet_set = array();
            }

        } elseif ( !empty( $this->tweet_set['errors'][0]['message'] ) ) {

        } else {
            $this->tweet_set = $this->reduceTweetSetData($tweets);
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
        if ( isset( $tweets[0] ) ) { // if this is the first set of tweets
            $trimmed_tweets[0]['user']['name']= $tweets[0]['user']['name'];
            //$trimmed_tweets[0]['user']['description']= $tweets[0]['user']['description'];
            //$trimmed_tweets[0]['user']['statuses_count']= $tweets[0]['user']['statuses_count'];
            //$trimmed_tweets[0]['user']['followers_count']= $tweets[0]['user']['followers_count'];
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
     public function maybeCacheTweets( $error = false ) {
		 if ( $error ) {
			 $cache = json_encode(array('error'));
		 } else {
			 $cache = json_encode($this->tweet_set);
		 }
        $this->cache->set_transient($this->transient_name, $cache, $this->feed_options['cache_time']);
	}

    /**
     * returns a JSON string to be used in the data attribute that contains the shortcode data
     */
    public function getShortCodeJSON() {
        return htmlentities(json_encode($this->raw_shortcode_atts));
    }

    /**
     * uses the endpoint to determing what get fields need to be set
     *
     * @param $end_point api endpoint needed
     * @param $feed_term term associated with the endpoint, user name or search term
     * @return array the get fields for the request
     */
        public function setGetFieldsArray( $end_point, $feed_term ){
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
            if ($end_point === 'userslookup') {
                if (!empty($feed_term)) {
                    $get_fields['screen_name'] = $feed_term;
                }
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
        // actual connection
        $twitter_connect = new CtfOauthConnect( $request_settings, $end_point );
        $twitter_connect->setUrlBase();
        $twitter_connect->setGetFields( $get_fields );
        $twitter_connect->setRequestMethod( $this->feed_options['request_method'] );

        return $twitter_connect->performRequest();
    }

    private function getTweetsSinceID( $since_id, $end_point = 'search', $feed_term = '', $count = 0)
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

        // actual connection
        $twitter_connect = new CtfOauthConnect( $request_settings, $end_point );
        $twitter_connect->setUrlBase();
        $twitter_connect->setGetFields( $get_fields );
        $twitter_connect->setRequestMethod( $this->feed_options['request_method'] );

        return $twitter_connect->performRequest();
    }

    public function feedID() {
        if ($this->feed_options['persistentcache']) {
            $includewords = !empty($this->feed_options['includewords']) ? substr(str_replace(array(
                ',',
                ' '
            ) , '', $this->feed_options['includewords']) , 0, 10) : '';
            $excludewords = !empty($this->feed_options['excludewords']) ? substr(str_replace(array(
                ',',
                ' '
            ) , '', $this->feed_options['excludewords']) , 0, 5) : '';
            $feed_id = (!empty($this->atts['feedid'])) ? substr('ctf_!_' . $this->atts['feedid'] . $includewords . $excludewords, 0, 45) : substr('ctf_!_' . $this->feed_options['feed_term'] . $includewords . $excludewords, 0, 45);
            if ($this->feed_options['type'] === 'hashtag') {
                $feed_id = str_replace(' -filter:retweets', '', $feed_id);
            }
        }
        else {
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
    public function getFeedOpeningHtml(){
        $feed_options = $this->feed_options;
        $options                = ctf_get_database_settings();

        $ctf_data_disablelinks = ($feed_options['disablelinks'] == 'true') ? ' data-ctfdisablelinks="true"' : '';
        $ctf_data_linktextcolor = $feed_options['linktextcolor'] != '' ? ' data-ctflinktextcolor="'.$feed_options['linktextcolor'].'"' : '';
        $ctf_enable_intents = ($options['disableintents'] === false || $options['disableintents'] === 0) && ctf_show('actions', $feed_options) ? ' data-ctfintents="1"' : '';

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
        if ( ! is_admin() && CTF_Feed_Locator::should_do_ajax_locating( $this->feedID(), get_the_ID() ) ) {
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
            ### TEMPLATE HEADER
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
            ### TEMPLATE HEADER GENERIC
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
    public function getTweetSetHtml( $is_pagination = 0 ){
        $tweet_set          = isset( $this->tweet_set['statuses'] ) ? $this->tweet_set['statuses'] : $this->tweet_set;
        $options            = ctf_get_database_settings();
        $len                = min( $this->feed_options['num'] + $is_pagination, count( $tweet_set ) );
        $i                  = $is_pagination; // starts at index "1" to offset duplicate tweet
        $feed_options       = $this->feed_options;
        $tweet_html         = $this->feed_html;
        $feed_id            = $this->is_legacy ? $this->unique_legacy_id : $this->feed_id;
        $ctf_data_needed    = $this->num_tweets_needed;
        $ctf_feed_classes   = CTF_Parse::get_feed_classes($feed_options, $this->check_for_duplicates, $feed_id);
        $ctf_enable_intents = ((bool)$options['disableintents'] === false) && ctf_show('actions', $feed_options) ? ' data-ctfintents="1" ' : '';
        $ctf_main_atts      = CTF_Display_Elements::get_feed_container_data_attributes( $feed_options, $feed_id, $this->feedID() ) . $ctf_enable_intents;



        if (!$is_pagination) {
            ob_start();
            include ctf_get_feed_template_part('feed', $feed_options);
            $tweet_html .= ob_get_contents();
            ob_get_clean();
        }

        return $tweet_html;
    }

    public function getItemSetHtml($is_pagination = 0) {
        $options = get_option('ctf_options');
        $tweet_set = isset($this->tweet_set['statuses']) ? $this->tweet_set['statuses'] : $this->tweet_set;
        $tweet_count = CTF_Parse::get_tweet_count($tweet_set);
        $len = min($this->feed_options['num'] + $is_pagination, $tweet_count);
        $i = $is_pagination; // starts at index "1" to offset duplicate tweet
        $tweet_html = '';
        if ($is_pagination && (!isset($tweet_set[1]['id_str']))) {
            $tweet_html = $this->getOutOfTweetsHtml($this->feed_options);
        }
        ob_start();

        $this->tweet_loop($tweet_set, $this->feed_options, $is_pagination);
        $tweet_html .= ob_get_contents();
        ob_get_clean();

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
          $error_html .= '<p><b>This message is only visible to admins:</b><br />';
          $error_html .= 'Due to changes with Twitter\'s API the plugin will not update feeds. Smash Balloon is working on a solution for our free users to see updated tweets in feeds again.<br>Follow the link below for more information and updates.<br />';

          $error_html .= '<a href="https://smashballoon.com/doc/smash-balloon-twitter-changes-free-version/?utm_source=twitter-free&utm_medium=error-notice&utm_campaign=smash-twitter-update&utm_content=CustomTwitterFeedChanges" target="_blank" rel="noopener noreferrer">Custom Twitter Feed Changes</a></p>';

		      $error_html .= '</div>';
		  } else {
	        $error_html .= '<p>' . __( 'Twitter feed is not available at the moment.', 'custom-twitter-feeds' ) . '</p>';
        }
        $error_html .= '</div>'; // end .ctf-error
        $error_html .= '</div>'; // end #ctf

        return $error_html;
    }


    public function tweet_loop( $tweet_set, $feed_options, $is_pagination ) {
        #$tweet_set = isset( $this->tweet_set['statuses'] ) ? $this->tweet_set['statuses'] : $this->tweet_set;
        $len = min( $this->feed_options['num'] + $is_pagination, count( (array)$tweet_set ) );
        $i = $is_pagination; // starts at index "1" to offset duplicate tweet
        $tweet_html = '';
        if ( $is_pagination && ( ! isset ( $tweet_set[1]['id_str'] ) ) ) {
            $tweet_html .= $this->getOutOfTweetsHtml( $this->feed_options );
        } else {
            while ( $i < $len ) {
                $post = $tweet_set[$i];
                if (isset($post['retweeted_status'])) {
                    $retweeter = array(
                        'name' => $post['user']['name'],
                        'screen_name' => $post['user']['screen_name']
                    );
                    $post = $post['retweeted_status'];
                    // temporary workaround for cached http images
                    $post['user']['profile_image_url_https'] = isset($post['user']['profile_image_url_https']) ? $post['user']['profile_image_url_https'] : $post['user']['profile_image_url'];
                }
                else {
                    unset($retweeter);
                }
                // check for quoted
                if (isset($post['quoted_status'])) {
                    $quoted = $post['quoted_status'];
                }
                else {
                    unset($quoted);
                }
                include ctf_get_feed_template_part('item', $feed_options);
                $i++;
            }
        }
    }

    /**
     * Get Global Twitter Feed CSS
     *
     * @since 2.0
     * @return array
    */
    public function get_feed_style(){
        $feed_style = '';
        $feed_html_id = ($this->is_legacy ? $this->unique_legacy_id : $this->feed_id);

        $feed_ctn = '.ctf-feed-' . $feed_html_id;

         $css_array = [
            //Load More Button Style
            [
                'selector' => $feed_ctn . ' .ctf-more',
                'properties' => [
                    'background-color' => [
                        'value' => $this->feed_options['buttoncolor'],
                        'important' => true
                    ],
                    'color' => [
                        'value' => $this->feed_options['buttontextcolor'],
                        'important' => true
                    ]
                ]
            ],
            [
                'selector' => $feed_ctn . ' .ctf-more:hover',
                'properties' => [
                    'background-color' => [
                        'value' => $this->feed_options['buttonhovercolor'],
                        'important' => true
                    ]
                ]
            ],
            //Author Text Style
            [
                'selector' => $feed_ctn . ' .ctf-author-name',
                'properties' => [
                    'color' => [
                        'value' => $this->feed_options['authortextcolor'],
                        'important' => true
                    ],
                    'font-size' => [
                        'value' => $this->feed_options['authortextsize'],
                        'important' => true
                    ]
                ]
            ],
            //Tweet Text Style
            [
                'selector' => $feed_ctn . ' .ctf-tweet-text, ' .$feed_ctn . ' .ctf-quoted-tweet-text' ,
                'properties' => [
                    'color' => [
                        'value' => $this->feed_options['textcolor'],
                        'important' => true
                    ],
                    'font-size' => [
                        'value' => $this->feed_options['tweettextsize'],
                        'important' => true
                    ],
                    'font-weight' => [
                        'value' => $this->feed_options['tweettextweight'],
                        'important' => true
                    ]
                ]
            ],
            //Date Style
            [
                'selector' => $feed_ctn . ' .ctf-tweet-meta a',
                'properties' => [
                    'color' => [
                        'value' => $this->feed_options['datetextcolor'],
                        'important' => true
                    ],
                    'font-size' => [
                        'value' => $this->feed_options['datetextsize'],
                        'important' => true
                    ],
                    'font-weight' => [
                        'value' => $this->feed_options['datetextweight'],
                        'important' => true
                    ]
                ]
            ],
            //Icon Style
            [
                'selector' => $feed_ctn . ' .ctf-tweet-actions a',
                'properties' => [
                    'color' => [
                        'value' => $this->feed_options['iconcolor'],
                        'important' => true
                    ],
                    'font-size' => [
                        'value' => $this->feed_options['iconsize'],
                        'important' => true
                    ]
                ]
            ],
            //Quoted Tweet
            [
                'selector' => $feed_ctn . ' .ctf-quoted-tweet',
                'properties' => [
                    'font-size' => [
                        'value' => $this->feed_options['quotedauthorsize'],
                        'important' => true
                    ],
                    'font-weight' => [
                        'value' => $this->feed_options['quotedauthorweight'],
                        'important' => true
                    ],
                    'color' => [
                        'value' => $this->feed_options['textcolor'],
                        'important' => true
                    ]
                ]
            ],
            //Twitter Link Style
            [
                'selector' => $feed_ctn . ' .ctf-twitterlink',
                'properties' => [
                    'font-size' => [
                        'value' => floor( .8 * (int)$this->feed_options['iconsize'] ),
                        'important' => true
                    ],
                    'color' => [
                        'value' => $this->feed_options['textcolor'],
                        'important' => true
                    ]
                ]
            ],
            //Twitter Cards
            [
                'selector' => $feed_ctn . ' .ctf-tc-summary-info *',
                'properties' => [
                    'font-size' => [
                        'value' => floor( .8 * (int)$this->feed_options['cardstextsize'] ),
                        'important' => true
                    ],
                    'color' => [
                        'value' => $this->feed_options['cardstextcolor'],
                        'important' => true
                    ]
                ]
            ],
            //Text Header
            [
                'selector' => $feed_ctn . ' .ctf-header-type-text',
                'properties' => [
                    'color' => [
                        'value' => $this->feed_options['customheadertextcolor'],
                        'important' => true
                    ]
                ]
            ]
        ];

        //Feed Container
        if( isset($this->feed_options['height'])  && $this->feed_options['height'] != 0){
            array_push($css_array,  [
                'selector' => $feed_ctn . '.ctf-fixed-height',
                'properties' => [
                    'height' => [
                        'value' => $this->feed_options['height'],
                        'important' => true
                    ],
                ]
            ]);
        }
        //Link Text Color
        if( !CTF_Feed_Builder::check_if_on( $this->feed_options['disablelinks'] ) ){
            array_push($css_array,[
                'selector' => $feed_ctn . ' .ctf-tweet-text a',
                'properties' => [
                    'color' => [
                        'value' => $this->feed_options['linktextcolor'],
                        'important' => true
                    ]
                ]
            ]
        );
        }
        //Feed Post item style / Boxed & Regular
        $post_item_css = [];
        if($this->feed_options['tweetpoststyle'] === 'boxed'){
            $post_item_css =  [
                'selector' => $feed_ctn . '.ctf-boxed-style .ctf-item',
                'properties' => [
                    'background-color' => [
                        'value' => $this->feed_options['tweetbgcolor'],
                        'important' => true
                    ],
                    'border-radius' => [
                        'value' => $this->feed_options['tweetcorners']
                    ]
                ]
            ];
        }else if($this->feed_options['tweetpoststyle'] === 'regular' && CTF_Feed_Builder::check_if_on( $this->feed_options['tweetsepline'] ) ){
            $post_item_css =  [
                'selector' => $feed_ctn . '.ctf-regular-style .ctf-item, '. $feed_ctn . ' .ctf-header',
                'properties' => [
                    'border-bottom' => [
                        'size' => $this->feed_options['tweetsepsize'],
                        'color' => $this->feed_options['tweetsepcolor'],
                        'important' => true
                    ]
                ]
            ];
        }
        array_push($css_array, $post_item_css);

        //Color Pallete
        if( isset($this->feed_options['colorpalette'])  && $this->feed_options['colorpalette'] == 'custom'){

            //Custom Background Color
            array_push($css_array,  [
                'selector' => $feed_ctn . '.ctf_palette_custom_' . $feed_html_id . ' .ctf-item, '.  $feed_ctn . '.ctf_palette_custom_' . $feed_html_id . ' .ctf-header',
                'properties' => [
                    'background' => [
                        'value' => $this->feed_options['custombgcolor'],
                        'important' => true
                    ],
                ]
            ]);
            //Custom Accent Color
            array_push($css_array,  [
                'selector' => $feed_ctn . '.ctf_palette_custom_' . $feed_html_id . ' .ctf-corner-logo',
                'properties' => [
                    'color' => [
                        'value' => $this->feed_options['customaccentcolor'],
                        'important' => true
                    ],
                ]
            ]);
            //Custom Text 1 Color
            array_push($css_array,  [
                'selector' => $feed_ctn . '.ctf_palette_custom_' . $feed_html_id . ' .ctf-author-name, .ctf_palette_custom_' . $feed_html_id . ' .ctf-tweet-text',
                'properties' => [
                    'color' => [
                        'value' => $this->feed_options['customtextcolor1'],
                        'important' => true
                    ],
                ]
            ]);

            //Custom Text 2 Color
            array_push($css_array,  [
                'selector' => $feed_ctn . '.ctf_palette_custom_' . $feed_html_id . ' .ctf-author-screenname',
                'properties' => [
                    'color' => [
                        'value' => $this->feed_options['customtextcolor2'],
                        'important' => true
                    ],
                ]
            ]);

            //Custom Link Color
            array_push($css_array,  [
                'selector' => $feed_ctn . '.ctf_palette_custom_' . $feed_html_id . ' .ctf-tweet-text a',
                'properties' => [
                    'color' => [
                        'value' => $this->feed_options['customlinkcolor'],
                        'important' => true
                    ],
                ]
            ]);
        }

        //Legacy Feeds
        if( $this->is_legacy == true ){
            array_push($css_array,
                [
                    'selector' => $feed_ctn . ' .ctf-item',
                    'properties' => [
                        'background-color' => [
                            'value' => $this->feed_options['tweetbgcolor'],
                            'important' => true
                        ]
                    ]
                ],
                [
                    'selector' => $feed_ctn . ' .ctf-corner-logo ',
                    'properties' => [
                        'font-size' => [
                            'value' => $this->feed_options['logosize'],
                            'important' => true
                        ],
                        'color' => [
                            'value' => $this->feed_options['logocolor'],
                            'important' => true
                        ]
                    ]
                ],
                [
                    'selector' => $feed_ctn . ' .ctf-retweet-text, '. $feed_ctn . ' .ctf-author-box-link, '. $feed_ctn . ' .ctf-author-avatar, '. $feed_ctn . ' .ctf-author-name, '.$feed_ctn . ' .ctf-author-screenname',
                    'properties' => [
                        'font-size' => [
                            'value' => $this->feed_options['authortextsize'],
                            'important' => true
                        ],
                        'font-weight' => [
                            'value' => $this->feed_options['authortextweight']
                        ],
                        'color' => [
                            'value' => $this->feed_options['textcolor'],
                        ]
                    ]
                ],
                [
                    'selector' => $feed_ctn . ' .ctf-header-user, ' . $feed_ctn . ' .ctf-header-bio, ' . $feed_ctn . ' .ctf-header-no-bio' ,
                    'properties' => [
                        'color' => [
                            'value' => $this->feed_options['headertextcolor'],
                            'important' => true
                        ]
                    ]
                ],
                [
                    'selector' => $feed_ctn . ' .ctf-header' ,
                    'properties' => [
                        'background-color' => [
                            'value' => $this->feed_options['headerbgcolor'],
                            'important' => true
                        ]
                    ]
                ],
                [
                    'selector' => $feed_ctn,
                    'properties' => [
                        'background-color' => [
                            'value' => $this->feed_options['bgcolor'],
                            'important' => true
                        ],
                        'height' => [
                            'value' => $this->feed_options['height'],
                            'unit' => $this->feed_options['height_unit'],
                            'important' => true
                        ],
                        'width' => [
                            'value' => $this->feed_options['width'],
                            'unit' => $this->feed_options['width_unit'],
                            'important' => true
                        ]
                    ]
                ]


            );

        }

        $feed_style .= '<style type="text/css" data-ctf-style="' . $feed_html_id . '">';
        $feed_style .= CTF_Parse::parse_css_style( $css_array );
        $feed_style .= '</style>';
        echo $feed_style;
    }

     /**
     * @return mixed
     * @since 2.0
     */
    public static function get_legacy_feed_settings() {
        return json_decode( get_option( 'ctf_legacy_feed_settings', '{}' ), true );
    }

    public function get_legacy_feed_unique_id() {
        return $this->unique_legacy_id;
    }

}
