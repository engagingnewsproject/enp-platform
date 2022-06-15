<?php
/**
 * Class CTF_Parse
 *
 *
 * @since 2.0
 */
namespace TwitterFeed;

use TwitterFeed\CtfFeed;
use TwitterFeed\CtfOauthConnect;
use TwitterFeed\CTF_GDPR_Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

class CTF_Parse{


    /**
     * Get Tweet ID
     *
     * @since 2.0
     */
    public static function get_tweet_id( $data ) {
        return $data['id_str'];
    }

    public static function get_post_id( $data ) {
        return $data['id_str'];
    }

    /**
     * Get Tweet Author User name
     *
     * @since 2.0
     */
    public static function get_user_name( $data ) {
        return $data['screen_name'];
    }

    /**
	 * Get Tweet Author Name
	 *
	 * @since 2.0
	 */
	public static function get_author_name( $data ) {
        return strtolower( $data['user']['name'] );
    }

	/**
	 * Get Tweet Author Name
	 *
	 * @since 2.0
	 */
	public static function get_display_author_name( $data ) {
		return $data['user']['name'];
	}

    /**
	 * Get Tweet Author Screen Name
	 *
	 * @since 2.0
	 */
    public static function get_author_screen_name( $data ) {
        return strtolower( $data['user']['screen_name'] );
    }

    public static function get_quoted_name( $data ) {
        return $data['user']['name'];
    }

    public static function get_quoted_screen_name( $data ) {
        return $data['user']['screen_name'];
    }

    /**
	 * Get Tweet Verified
	 *
	 * @since 2.0
	*/
    public static function get_quoted_verified( $data ) {
        return $data['user']['verified'];
    }


    /**
	 * Get Tweet Post
	 *
	 * @since 2.0
	*/
    public static function get_post($tweet_set) {
        if ( isset( $tweet_set['retweeted_status'] ) ) {
            return $tweet_set['retweeted_status'];
        } else {
            return $tweet_set;
        }
    }

    /**
     * Get Tweet Avatar URL
     *
     * @since 2.0
    */
    public static function get_avatar_url( $post, $feed_options ) {
        if ( CTF_GDPR_Integrations::doing_gdpr( $feed_options ) ) {
            return trailingslashit( CTF_PLUGIN_URL ) . 'img/placeholder.png';
        }

        return self::get_avatar( $post );
    }

    /**
     * Get Tweet Avatar
     *
     * @since 2.0
    */
    public static function get_avatar( $data ) {
        if ( isset( $data['retweeted_status'] ) ) {
            return $data['retweeted_status']['user']['profile_image_url_https'];
        } elseif ( isset( $data['user'] ) ) {
            return $data['user']['profile_image_url_https'];
        } elseif ( isset( $data['profile_image_url_https'] ) ) {
            return $data['profile_image_url_https'];
        }
        return '';
    }

    /**
     * Get Tweet UTS Offset
     *
     * @since 2.0
    */
    public static function get_utc_offset ( $data ) {
        return $data['user']['utc_offset'];
    }

    /**
     * Get Tweet Original TimeStamp
     *
     * @since 2.0
    */
    public static function get_original_timestamp( $data ) {
        return $data['created_at'];
    }

    /**
     * Get Tweet Author Verified
     *
     * @since 2.0
    */
    public static function get_verified ( $data ) {
        return $data['user']['verified'];
    }


    /**
     * Get Generic Header Text
     *
     * @since 2.0
    */
    public static function get_generic_header_text( $data ) {
        if ( $data['type'] === 'search' || $data['type'] === 'hashtag' ) {
            $using_custom = $data['headertext'] != '';
            $raw_header_text = $using_custom ? $data['headertext'] : $data['feed_term'];

            //List multiple terms
            $hashtags = explode(" OR ", $data['feed_term']);
            if ( ! $using_custom ) {
                $default_header_text = '';
                $h_index = 0;
                foreach ( $hashtags as $hashtag ) {
                    if( $h_index > 0 ) $default_header_text .= ', ';
                    $default_header_text .= $hashtag;
                    $h_index++;
                }
            } else {
                $default_header_text = $data['headertext'];
            }

            $default_header_text = str_replace( ' -filter:retweets', '', $default_header_text );


            return $default_header_text;

        } else {
            $default_header_text = 'Twitter';
            // $url_part = $data['screenname']; //Need to get screenname here
            return $default_header_text;
        }

        //Header for combined feed types
        if ( ! empty( $data['feed_types_and_terms'] ) ) {
            if ( $data['headertext'] != '' ) {
                $default_header_text = $data['headertext'];

                if ( $data['feed_types_and_terms'][0][0] === 'search' || $data['feed_types_and_terms'][0][0] === 'hashtag' ) {
                    $raw_header_text = $data['feed_types_and_terms'][0][1];
                }

                return $default_header_text;

            } else {
                $default_header_text = '';
                $i_term = 0;
                foreach ( $data['feed_types_and_terms'] as $feed_set ) {
                    if ( $feed_set[0] == 'lists' ) {
                        $default_header_text .= '';
                    } else {
                        if ( $i_term > 0 ) {
                            $default_header_text .= ', ';
                        }
                        if ( $feed_set[0] == 'usertimeline' ) {
                            $default_header_text .= '@';
                        }
                        $default_header_text .= $feed_set[1];
                    }
                    $i_term++;
                }
            }

            if ( empty( $default_header_text ) ) {
                return $default_header_text = 'Twitter';
            }

        }
    }


    /**
     * Get Generic Header URL
     *
     * @since 2.0
    */
    public static function get_generic_header_url ( $data ) {
        $hashtags = isset($data['feed_term']) ? explode(" OR ", $data['feed_term']) : '';
        if ( $data['type'] === 'search' || $data['type'] === 'hashtag' ) {
            if ( $data['type'] === 'hashtag' ) {
                $url_part = 'hashtag/' . str_replace("#", "", $hashtags[0]);
            } else {
                $url_part = 'search?q=' . rawurlencode( str_replace( array( ', ', "'" ), array( ' OR ', '"' ), $data['feed_term'] ) );
            }

            return $url_part;
        }

        if ( ! empty( $data['feed_types_and_terms'] ) ) {
            if ( $data['feed_types_and_terms'][0][0] === 'search' || $data['feed_types_and_terms'][0][0] === 'hashtag' ) {
                $raw_header_text = $data['feed_types_and_terms'][0][1];
                //List multiple terms
                $hashtags = explode( " OR ", $data['feed_types_and_terms'][0][1] );

                if ( $data['feed_types_and_terms'][0][0] === 'hashtag' ) {
                    $url_part = 'hashtag/' . str_replace( "#", "", $hashtags[0] );

                    return $url_part;
                } else {
                    $url_part = 'search?q=' . rawurlencode( str_replace( array( ', ', "'" ), array(
                            ' OR ',
                            '"'
                        ), $data['feed_types_and_terms'][0][1] ) );

                    return $url_part;
                }
            }
        }

    }


    /**
     * Get Header Text
     *
     * @since 2.0
    */
    public static function get_header_text( $header_info, $feed_options ) {
        if ( empty( $header_info ) || ! is_array( $header_info ) ) {
            return '';
        }

        if ( $feed_options['headertext'] !== '' ) {
            $header_text = $feed_options['headertext'];
            return $header_text;
        } else {
            $header_text = $header_info['name'];
            return $header_text;
        }
    }


    /**
     * Get Header Description
     *
     * @since 2.0
    */
    public static function get_header_description( $data ) {
        return $data['description'];
    }



    /**
     * Get User Header JSON info
     *
     * @since 2.0
    */
    public static function get_user_header_json( $data ) {
        $transient = $data['type'] === 'usertimeline' ? 'ctf_header_' . $data['screenname'] : 'ctf_hometimeline_header';

        $header_json = get_transient( $transient );
        $header_array = json_decode( $header_json, true );
        if ( ! $header_json || isset($header_array['errors'])) {
            $endpoint = 'accountlookup';
            if ( $data['type'] === 'usertimeline' ) {
                $endpoint = 'userslookup';
            }

                // Only can be set in the options page
            $request_settings = array(
                'consumer_key' => $data['consumer_key'],
                'consumer_secret' => $data['consumer_secret'],
                'access_token' => $data['access_token'],
                'access_token_secret' => $data['access_token_secret'],
            );

            $CtfFeedPros = new CtfFeed( array(), null, null );
            $data['screenname'] = str_replace('@','', $data['screenname']);
            $get_fields = $CtfFeedPros->setGetFieldsArray( $endpoint, $data['screenname'] );
            // actual connection
            $twitter_connect = new CtfOauthConnect( $request_settings, $endpoint );
            $twitter_connect->setUrlBase();
            $twitter_connect->setGetFields( $get_fields );
            $twitter_connect->setRequestMethod( $data['request_method'] );

            $request_results = $twitter_connect->performRequest();

            $header_json = isset( $request_results->json ) ? $request_results->json : false;

            if ( $endpoint === 'accountlookup' ) {
                set_transient( 'ctf_hometimeline_header', $header_json, 60*60 );
            } else {
                set_transient( 'ctf_header_' . $data['screenname'], $header_json, 60*60 );
            }

        }
        $header_info = isset( $header_json ) ? json_decode( $header_json, true ) : array();
        if ( isset( $header_info[0] ) && !isset($header_info['errors'])) {
            return $header_info = $header_info[0];
        } elseif ( ! isset( $header_info['screen_name'] ) ) {
            return [
                'name' => $data['screenname'],
                'description' => ''
            ];
        }

        return $header_info;
    }


    /**
     * Get User Header Avatar
     *
     * @since 2.0
    */
    public static function get_header_avatar( $data, $feed_options = array() ) {
        $settings = ctf_get_database_settings();
        if ( CTF_GDPR_Integrations::doing_gdpr( $settings ) ) {
            $avatar = trailingslashit( CTF_PLUGIN_URL ) . 'img/placeholder.png';
        } else {
            $avatar = $data['profile_image_url_https'];
        }

        return $avatar;
    }

    public static function get_quoted_tc( $data ) {

        $quoted = false;

        // check for quoted
        if ( isset( $data['quoted_status'] ) ) {
            $quoted = $data['quoted_status'];
            return $quoted;
        } else {
            unset( $quoted );
        }

    }

    public static function get_quoted_media( $data, $num_media ) {
        //Quoted Tweets Media
        $quoted_media = false;

        if ( isset( $data['extended_entities']['media'] ) ) {

            $num_media = count( $data['extended_entities']['media'] );
            for( $ii = 0; $ii < $num_media; $ii++ ) {
                if ( $data['extended_entities']['media'][$ii]['type'] == 'video' || $data['extended_entities']['media'][$ii]['type'] == 'animated_gif' ) {
                    $quoted_media[$ii]['url'] = $data['extended_entities']['media'][$ii]['video_info']['variants'][$ii]['url'];
                } else {
                    $quoted_media[$ii]['url'] = $data['extended_entities']['media'][$ii]['media_url_https'];
                }
                $quoted_media[$ii]['type'] = $data['extended_entities']['media'][$ii]['type'];
                if ( $quoted_media[$ii]['type'] == 'video' ) {
                    $quoted_media[$ii]['video_atts'] = 'controls';
                } elseif ( $quoted_media[$ii]['type'] == 'animated_gif' ) {
                    $quoted_media[$ii]['video_atts'] = 'controls loop autoplay muted';
                }
                $quoted_media[$ii]['poster'] = $data['extended_entities']['media'][$ii]['media_url_https'];
            }

        } elseif ( isset( $data['entities']['media'] ) ) {

            $num_media = count( $data['entities']['media'] );
            for( $ii = 0; $ii < $num_media; $ii++ ) {
                if ( $data['entities']['media'][$ii]['type'] == 'video' || $data['entities']['media'][$ii]['type'] == 'animated_gif' ) {
                    $quoted_media[$ii]['url'] = $data['entities']['media'][$ii]['video_info']['variants'][$ii]['url'];
                } else {
                    $quoted_media[$ii]['url'] = $data['entities']['media'][$ii]['media_url_https'];
                }
                $quoted_media[$ii]['type'] = $data['entities']['media'][$ii]['type'];
                if ( $quoted_media[$ii]['type'] == 'video' ) {
                    $quoted_media[$ii]['video_atts'] = 'controls';
                } elseif ( $quoted_media[$ii]['type'] == 'animated_gif' ) {
                    $quoted_media[$ii]['video_atts'] = 'controls loop autoplay muted';
                }
                $quoted_media[$ii]['poster'] = $data['entities']['media'][$ii]['media_url_https'];
            }

        }

        return $quoted_media;
    }

    /**
     * Get Feed Classes
     *
     * @since 2.0
    */
    public static function get_feed_classes( $feed_options, $check_for_duplicates, $feed_id = false) {
        if( ctf_doing_customizer( $feed_options ) ){
            return ' :class="$parent.getFeedClasses()" ';
        }else{
            $ctf_feed_classes = 'ctf ctf-type-' . CTF_Parse::get_feed_type( $feed_options );
            $ctf_feed_classes .= ($feed_id !== false ) ?  ' ctf-feed-' . $feed_id : '';
            $ctf_feed_classes .= ' ' . $feed_options['class'] . ' ctf-styles';
            $ctf_feed_classes .= ($feed_options['layout']) ?  ' ctf-' . $feed_options['layout']: '';
            $ctf_feed_classes .= ( isset( $feed_options['tweetpoststyle'] ) ) ?  ' ctf-' . $feed_options['tweetpoststyle'] . '-style' : '';
            if ( ! empty( $feed_options['height'] ) ) $ctf_feed_classes .= ' ctf-fixed-height';
            $ctf_feed_classes .= $feed_options['width_mobile_no_fixed'] ? ' ctf-width-resp' : '';
            if ( $check_for_duplicates ) { $ctf_feed_classes .= ' ctf-no-duplicates'; }
            if( isset($feed_options['colorpalette']) && $feed_options['colorpalette'] !== 'inherit' && $feed_id !== false ){
                $feed_id_class = $feed_options['colorpalette'] === 'custom' ? ('_' . $feed_id) : '';
                $ctf_feed_classes .= ' ctf_palette_' . $feed_options['colorpalette'] . $feed_id_class;
            }
            $ctf_feed_classes = apply_filters( 'ctf_feed_classes', $ctf_feed_classes );
            return 'class=" ' . $ctf_feed_classes .'" ';
        }

    }

    public static function get_tweet_count( $data ) {

        if ( isset( $data['statuses'] ) && is_array( $data['statuses'] ) ) {
            $tweet_count = count( $data['statuses'] );
        } elseif ( is_array( $data ) ) {
            $tweet_count = count( $data );
        } else {
            $tweet_count = 0;
        }

        return $tweet_count;
    }
    /**
     * Get Feed Type
     *
     * @since 2.0
    */
    public static function get_feed_type( $feed_options ) {
        $ctf_feed_type = ! empty ( $feed_options['type'] ) ? $feed_options['type'] : 'multiple';
        return $ctf_feed_type;
    }

    public static function get_retweet_count( $data ) {
        if ( isset( $data['retweeted_status']['retweet_count'] ) ) {
            return $data['retweeted_status']['retweet_count'];
        } else {
            return $data['retweet_count'];
        }
    }

    public static function get_favorite_count( $data ) {
        if ( isset( $data['retweeted_status']['favorite_count'] ) ) {
            return $data['retweeted_status']['favorite_count'];
        } else {
            return $data['favorite_count'];
        }
    }

     /**
     * Get Global Twitter Feed CSS
     *
     * @since 2.0
     * @return array
    */
    public static function parse_css_style ( $css_array ) {
        $style = '';
        $color_elements = [
            'color',
            'background',
            'background-color'
        ];

        $size_elements = [
            'border-radius',
            'height',
            'width',
            'font-size',
            'margin',
            'margin-top',
            'margin-bottom',
            'margin-left',
            'margin-right',
            'padding',
            'padding-top',
            'padding-bottom',
            'padding-left',
            'padding-right'
        ];

        $border_elements = [
            'border',
            'border-top',
            'border-bottom',
            'border-left',
            'border-right'
        ];

        foreach ($css_array as $element) {
            $items_css = '';
            if( isset( $element['properties'] ) ){
                foreach ($element['properties'] as $property => $item) {
                    if( in_array( $property, $color_elements) && !empty( $item['value'] ) && $item['value'] !== '#' ){
                        $items_css .= $property . ':' . stripcslashes($item['value']) . ( isset( $item['important'] ) ? '!important;' : ';' );
                    }
                    if( in_array( $property, $size_elements) && !empty( $item['value'] ) && $item['value'] !== '0' && $item['value'] !== 'inherit' ){
                        $items_css .= $property . ':' . stripcslashes($item['value']) . ( isset( $item['unit'] ) ?  $item['unit'] : 'px' ) . ( isset( $item['important'] ) ? '!important;' : ';' );
                    }
                    if( in_array( $property, $border_elements) && !empty( $item['size'] ) && $item['size'] !== '0' && !empty( $item['color'] ) && $item['color'] !== '#' ){
                        $items_css .= $property . ':' . stripcslashes($item['size']) .  'px ' . ( isset( $item['style'] ) ? $item['style'] : 'solid' ) . ' ' . stripcslashes($item['color']) . ( isset( $item['important'] ) ? '!important;' : ';' );
                    }
                }
                $style .= !empty($items_css) ? $element['selector'] . '{'.$items_css .'}' : '';
            }
        }

        return $style;
    }

}
