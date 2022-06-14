<?php
/**
 * Class CTF_Display_Elements
 *
 *
 * @since 2.0
 */
namespace TwitterFeed;
use TwitterFeed\Builder\CTF_Feed_Builder;
if ( ! defined( 'ABSPATH' ) ) {
    die( '-1' );
}

class CTF_Display_Elements {

    /**
     * Get Feed Item CSS Classes
     *
     * @since 2.0
     */
    public static function get_item_classes( $data, $feed_options, $i ) {
        $data = $data[$i];

        $tweet_classes = 'ctf-item ctf-author-' . CTF_Parse::get_author_screen_name( $data ) .' ctf-new';
        $tweet_classes .= ( !ctf_show( 'avatar', $feed_options ) )  ? ' ctf-hide-avatar' : '';
        $tweet_classes = apply_filters( 'ctf_tweet_classes', $tweet_classes );

        $tweet_classes .= isset( $data['retweeted_status'] ) ? ' ctf-retweet' : '';
        $tweet_classes .= isset( $data['quoted_status'] ) ? ' ctf-quoted' : '';
        $tweet_classes =  ' class="' . $tweet_classes . '" ';
        if( ctf_doing_customizer($feed_options) ){
            $tweet_classes .=  ' :class="!$parent.valueIsEnabled($parent.customizerFeedData.settings.include_avatar) ? \'ctf-hide-avatar\' : \'\'" ';
        }
        return $tweet_classes;
    }


    /**
     * Get Tweet Retweet Attribute
     *
     * @since 2.0
     */
    public static function get_retweet_attr( $post, $check_duplicates ) {
        if( isset( $post['retweeted_status'] ) && $check_duplicates ) {
            return ' data-ctfretweetid="'.$post['retweeted_status']['id_str'].'"';
        }
        return '';
    }

    /**
     * Get Tweet Quoted Media Text
     *
     * @since 2.0
     */
    public static function get_quoted_media_text( $post, $feed_options ) {
        $quoted_media_text = '';
        if(!$feed_options['is_legacy'] || ($feed_options['is_legacy'] && ctf_show( 'placeholder', $feed_options ))){
            if ( isset( $post['quoted_status'] ) ) {
            $quoted = $post['quoted_status'];

            if ( ( isset( $quoted['extended_entities']['media'][0] ) || isset( $quoted['entities']['media'][0] ) ) && ctf_show( 'placeholder', $feed_options ) ) {
                $quoted_media = isset( $quoted['extended_entities']['media'] ) ? $quoted['extended_entities']['media'] : $quoted['entities']['media'];
                $quoted_media_count = count( $quoted_media );
                switch ( $quoted_media[0]['type'] ) {
                    case 'video':
                    case 'animated_gif':
                    $quoted_media_text     .= '<span class="ctf-quoted-tweet-text-media-wrap">' . ctf_get_fa_el( 'fa-file-video-o' ) . '</span>';
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
        }
        return $quoted_media_text;
        }

    }

    /**
     * Get Tweet Post media TExt
     *
     * @since 2.0
     */
    public static function get_post_media_text( $post, $feed_options,  $type = 'text' ) {
        $post_media_text = '';
        $post_media_count = 0;
        if ( ( isset( $post['extended_entities']['media'][0] ) || isset( $post['entities']['media'][0] ) ) ) {
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
            return '';
        }
        $html = ($type == 'text') ? $post_media_text : $post_media_count;
        $multi_class = $post_media_count > 1 ? ' ctf-multi-media-icon' : '';

        if ( $feed_options['linktexttotwitter'] ) {
            return $html;
        } elseif ( $feed_options['disablelinks'] ) {
            $return = '<span class="ctf-tweet-text-media-wrap'.$multi_class.'">' . $html . '</span>';
        } else {
            $return =  '</p><a href="https://twitter.com/' . $post['user']['screen_name'] . '/status/' . $post['id_str'] . '" target="_blank" rel="noopener noreferrer" class="ctf-tweet-text-media-wrap'.$multi_class.'">' . $html . '</a>';
        }

        return $return;
    }
    /**
     * Display Feed Header
     *
     * @param array $settings
     *
     * @return string
     *
     * @since 2.0
     */
    public static function display_header( $feed_options ){
        if( ctf_doing_customizer( $feed_options ) ){
            $header_template = 'header-generic';
            if ( $feed_options['type'] === 'usertimeline' || $feed_options['type'] === 'mentionstimeline' || $feed_options['type'] === 'hometimeline' ) {
                $header_template = 'header';
            }
            include ctf_get_feed_template_part( $header_template, $feed_options );
            include ctf_get_feed_template_part( 'header-text', $feed_options );
        }else{
            include ctf_get_feed_template_part( CTF_Display_Elements::header_type( $feed_options ), $feed_options );
        }
    }

    public static function header_type( $feed_options ) {
        $header_template = 'header-generic';
        if ( $feed_options['type'] === 'usertimeline' || $feed_options['type'] === 'mentionstimeline' || $feed_options['type'] === 'hometimeline' ) {
            $header_template = 'header';
        }

        if( isset( $feed_options['headerstyle'] ) && $feed_options['headerstyle'] == 'text'){
            $header_template = 'header-text';
        }

        return $header_template;
    }

    /**
     * Should Show Element
     *
     * @param array $settings
     * @param string $setting_name
     * @param bool $custom_condition
     *
     * @return string
     *
     * @since 2.0
     */
    public static function should_show_element_vue( $settings, $setting_name, $custom_condition = false ) {
        $customizer = ctf_doing_customizer( $settings );
        if ( $customizer ) {
            return ' v-if="$parent.valueIsEnabled($parent.customizerFeedData.settings.' . $setting_name . ')' . ( $custom_condition != false ? $custom_condition : '' ) . '" ';
        }
        return '';
    }

    /**
     * Should Print HTML
     *
     * @param bool $customizer
     * @param string $content
     *
     * @return string
     *
     * @since 2.0
     */
    public static function should_print_element_vue( $customizer, $content ) {
        if ( $customizer ) {
            return ' v-html="' . $content . '" ';
        }
        return '';
    }

    /**
     * Should Print HTML
     *
     * @param bool $customizer
     * @param string $condition
     *
     * @return string
     *
     * @since 2.0
     */
    public static function create_condition_vue( $customizer, $condition ) {
        if ( $customizer ) {
            return ' v-if="' . $condition . '" ';
        }
        return '';
    }

    /**
     * Should Show Print HTML
     *
     * @param bool $customizer
     * @param string $condition
     *
     * @return string
     *
     * @since 2.0
     */
    public static function create_condition_show_vue( $customizer, $condition ) {
        if ( $customizer ) {
            return ' v-show="' . $condition . '" ';
        }
        return '';
    }

    /**
     * Print Element HTML Attribute
     *
     * @param bool $customizer
     * @param array $args
     *
     * @return string
     *
     * @since 2.0
     */
    public static function print_element_attribute( $customizer, $args ) {
        if ( $customizer ) {
            return ' :' . $args['attr'] . '="' . $args['vue_content'] . '"';
        }
        if( ( isset( $args['php_condition'] ) && $args['php_condition'] ) || !isset( $args['php_condition'] ) ){
            return ' ' . $args['attr'] . '="' . $args['php_content'] . '"';
        }
    }

    /**
     * Print Element HTML Attribute
     *
     * @param bool $customizer
     * @param array $args
     *
     * @return string
     *
     * @since 2.0
     */
    public static function get_element_attribute( $element, $settings ) {
        $customizer = ctf_doing_customizer( $settings );
        if( $customizer ){
            switch ($element) {
                case 'author':
                    return CTF_Display_Elements::create_condition_show_vue( $customizer,  '$parent.valueIsEnabled($parent.customizerFeedData.settings.include_author)');
                break;
                case 'avatar':
                    return CTF_Display_Elements::create_condition_show_vue( $customizer,  '$parent.valueIsEnabled($parent.customizerFeedData.settings.include_author) && $parent.valueIsEnabled($parent.customizerFeedData.settings.include_avatar)');
                break;
                case 'author_text':
                    return CTF_Display_Elements::create_condition_show_vue( $customizer,  '$parent.valueIsEnabled($parent.customizerFeedData.settings.include_author) && $parent.valueIsEnabled($parent.customizerFeedData.settings.include_author_text)');
                break;
                case 'date':
                    return CTF_Display_Elements::create_condition_show_vue( $customizer,  '$parent.valueIsEnabled($parent.customizerFeedData.settings.include_date)');
                break;
                case 'logo':
                    return CTF_Display_Elements::create_condition_show_vue( $customizer,  '$parent.valueIsEnabled($parent.customizerFeedData.settings.include_logo)');
                break;
                case 'text_and_link':
                    return CTF_Display_Elements::create_condition_vue( $customizer,  '$parent.valueIsEnabled($parent.customizerFeedData.settings.include_text) && $parent.valueIsEnabled($parent.customizerFeedData.settings.linktexttotwitter)');
                break;
                case 'text_no_link':
                    return CTF_Display_Elements::create_condition_vue( $customizer,  '$parent.valueIsEnabled($parent.customizerFeedData.settings.include_text) && !$parent.valueIsEnabled($parent.customizerFeedData.settings.linktexttotwitter)');
                break;
                case 'linkbox':
                    return CTF_Display_Elements::create_condition_show_vue( $customizer,  '$parent.valueIsEnabled($parent.customizerFeedData.settings.include_linkbox)');
                break;
                case 'media':
                    return CTF_Display_Elements::create_condition_show_vue( $customizer,  '$parent.valueIsEnabled($parent.customizerFeedData.settings.include_media)');
                break;
                case 'repliedto':
                    return CTF_Display_Elements::create_condition_show_vue( $customizer,  '$parent.valueIsEnabled($parent.customizerFeedData.settings.include_replied_to)');
                break;
                case 'retweeter':
                    return CTF_Display_Elements::create_condition_show_vue( $customizer,  '$parent.valueIsEnabled($parent.customizerFeedData.settings.include_retweeter)');
                break;
                case 'loadmore':
                    return CTF_Display_Elements::create_condition_show_vue( $customizer,  '$parent.valueIsEnabled($parent.customizerFeedData.settings.showbutton) && $parent.customizerFeedData.settings.layout !== \'carousel\' ') . ' ' . CTF_Display_Elements::should_print_element_vue( $customizer, '$parent.customizerFeedData.settings.buttontext' );
                break;
                case 'headerbio':
                    return CTF_Display_Elements::create_condition_show_vue( $customizer,  '$parent.valueIsEnabled($parent.customizerFeedData.settings.showbio)');
                break;
                case 'header':
                    return CTF_Display_Elements::create_condition_show_vue( $customizer,  '$parent.valueIsEnabled($parent.customizerFeedData.settings.showheader) && $parent.customizerFeedData.settings.headerstyle === \'standard\' ');
                break;
                case 'header-text':
                    return  CTF_Display_Elements::create_condition_show_vue( $customizer,  '$parent.valueIsEnabled($parent.customizerFeedData.settings.showheader) && $parent.customizerFeedData.settings.headerstyle === \'text\' ') . ' ' . CTF_Display_Elements::should_print_element_vue( $customizer, '$parent.customizerFeedData.settings.customheadertext' );
                break;
                case 'actions':
                    return CTF_Display_Elements::create_condition_show_vue( $customizer,  '$parent.valueIsEnabled($parent.customizerFeedData.settings.include_actions)');
                break;
                case 'viewtwitterlink':
                    return CTF_Display_Elements::create_condition_show_vue( $customizer,  '$parent.valueIsEnabled($parent.customizerFeedData.settings.viewtwitterlink)');
                break;
                case 'viewtwitterlink_text':
                    return CTF_Display_Elements::should_print_element_vue( $customizer, '$parent.customizerFeedData.settings.twitterlinktext' );
                break;
                case 'twitter_cards':
                    return CTF_Display_Elements::create_condition_show_vue( $customizer, '$parent.customizerFeedData.settings.include_twittercards' );
                break;
                case 'linkto':
                    return CTF_Display_Elements::create_condition_show_vue( $customizer, '$parent.customizerFeedData.settings.linktexttotwitter' );
                break;

            }
        }

        return '';
    }


    /**
     * Should Show Element
     *
     * @param array $settings
     * @param string $setting_name
     * @param bool $custom_condition
     *
     * @return string
     *
     * @since 2.0
     */
    public static function get_feed_container_data_attributes( $settings, $feed_id, $twitter_feed_id ) {
        $customizer = ctf_doing_customizer( $settings );

        $atts = CTF_Display_Elements::print_element_attribute(
            $customizer,
            array(
                'attr'        => 'data-ctfdisablelinks',
                'vue_content' => '$parent.customizerFeedData.settings.disablelinks',
                'php_content' => self::cast_boolean($settings['disablelinks']) ? 'true' : 'false',
            )
        );

        $atts .= CTF_Display_Elements::print_element_attribute(
            $customizer,
            array(
                'attr'        => 'data-ctflinktextcolor',
                'vue_content' => '$parent.customizerFeedData.settings.linktextcolor',
                'php_content' => $settings['linktextcolor'],
            )
        );
        /*

        $atts .= CTF_Display_Elements::print_element_attribute(
            $customizer,
            array(
                'attr'          => 'data-ctfscrolloffset',
                'vue_content'   => '$parent.valueIsEnabled($parent.customizerFeedData.settings.autoscroll) ? $parent.customizerFeedData.settings.autoscrolldistance : false',
                'php_condition' => CTF_Feed_Builder::check_if_on( $settings['autoscroll'] ),
                'php_content'   =>  $settings['autoscrolldistance'],
            )
        );
        */
        /*
        */

        $atts .= CTF_Display_Elements::print_element_attribute(
            $customizer,
            array(
                'attr'          => 'data-boxshadow',
                'vue_content'   => ' $parent.customizerFeedData.settings.tweetpoststyle === \'boxed\' && $parent.valueIsEnabled($parent.customizerFeedData.settings.tweetboxshadow) ? \'true\' : \'false\' ',
                'php_condition' => $settings['tweetpoststyle'] === 'boxed' && CTF_Feed_Builder::check_if_on( $settings['tweetboxshadow'] ),
                'php_content'   => "true",
            )
        );
        $atts .= CTF_Display_Elements::print_element_attribute(
            $customizer,
            array(
                'attr'        => 'data-header-size',
                'vue_content' => '$parent.customizerFeedData.settings.customheadersize',
                'php_content' => $settings['customheadersize'],
            )
        );

        //Global Feed Atts
        $atts .= ' data-feedid="' . esc_attr($feed_id) . '" data-postid="' . esc_attr(get_the_ID()) . '" ';
        if ( ! empty( $settings['feed'] ) ) {
            $atts .= ' data-feed="' . esc_attr($settings['feed']) . '"';
        }
        $flags = array();
        $dbsettings = ctf_get_database_settings();

        if ( CTF_GDPR_Integrations::doing_gdpr( $dbsettings ) ) {
            $flags[] = 'gdpr';
        }
        if ( !is_admin() && CTF_Feed_Locator::should_do_ajax_locating( $twitter_feed_id , get_the_ID() ) ) {
            $flags[] = 'locator';
        }
        if (!empty($flags)) {
            $atts .= ' data-ctf-flags="' . implode(',', $flags) . '"';
        }

        return $atts;
    }


    public static function cast_boolean( $value ) {
        if ( $value === 'true' || $value === true || $value === 'on' ) {
            return true;
        }
        return false;
    }


    /**
     * Get Post Date TExt Attribure
     *
     *
     * @return string
     *
     * @since 2.0
     */
    public static function get_post_date_attr($created_at, $settings ) {
        $customizer = ctf_doing_customizer( $settings );
        return CTF_Display_Elements::should_print_element_vue( $customizer, '$parent.printDate(\''.$created_at.'\')' );
    }
    /**
     * Some characters in captions are breaking the customizer.
     *
     * @param $caption
     *
     * @return mixed
     */
    public static function sanitize_caption( $caption ) {
        $caption = str_replace( array( "'" ), '`', $caption );
        $caption = str_replace( '&amp;', '&', $caption );
        $caption = str_replace( '&lt;', '<', $caption );
        $caption = str_replace( '&gt;', '>', $caption );
        $caption = str_replace( '&quot;', '"', $caption );
        $caption = str_replace( '&#039;', '/', $caption );
        $caption = str_replace( '&#92;', '\/', $caption );

        $caption = str_replace( array( "\r", "\n" ), '<br>', $caption );
        $caption = str_replace( '&lt;br /&gt;', '<br>', nl2br( $caption ) );

        return $caption;
    }
    /**
     * Get Post Text Attributes
     *
     *
     * @return string
     *
     * @since 2.0
     */
    public static function get_post_text_attr( $post_text, $settings, $post_id ) {
        $customizer = ctf_doing_customizer( $settings );
        if( $customizer ){
            $post_text = self::sanitize_caption( $post_text );
            return ' v-html="$parent.getPostText(\'' . htmlspecialchars( $post_text ) . '\', ' . $post_id . ')"';
        }
        return '';
    }

    public static function post_text( $post, $feed_options ) {
        $text = isset($post['text']) ? $post['text'] : (isset($post['full_text']) ? $post['full_text'] : '');
        $post_text = apply_filters( 'ctf_tweet_text', $text, $feed_options, $post );

        return $post_text;
    }

    /**
     * Get Post Text
     *
     *
     * @return string
     *
     * @since 2.0
     */
    public static function get_post_text( $feed_options, $post_text, $post_id, $author_screen_name, $post_media_text ) {
        $customizer = ctf_doing_customizer( $feed_options );
        $post_text_attr = CTF_Display_Elements::get_post_text_attr( $post_text, $feed_options, $post_id );
        $text_and_link_attr = CTF_Display_Elements::get_element_attribute( 'text_and_link', $feed_options );
        $text_no_link_attr = CTF_Display_Elements::get_element_attribute( 'text_no_link', $feed_options );
        if( !$customizer ){
            if( $feed_options['linktexttotwitter'] ){ ?>
                <a class="ctf-tweet-text-link" href="<?php echo esc_url( 'https://twitter.com/' . $author_screen_name . '/status/' .$post_id ) ?>" target = "_blank" rel = "noopener noreferrer">
            <?php } ?>
                <p class="ctf-tweet-text">
                    <?php echo nl2br( $post_text ) ?>
                    <?php
                        if(!$feed_options['is_legacy'] || ($feed_options['is_legacy'] && ctf_show( 'placeholder', $feed_options ))){
                            echo $post_media_text;
                        }
                    ?>
                </p>
            <?php if( $feed_options['linktexttotwitter'] ){ ?>
                </a>
            <?php } ?>
            <?php
        }else{
            ?>
            <a class="ctf-tweet-text-link" <?php echo $text_and_link_attr; ?> href="<?php echo esc_url( 'https://twitter.com/' . $author_screen_name . '/status/' .$post_id ) ?>" target = "_blank" rel = "noopener noreferrer">
                <p class="ctf-tweet-text" <?php echo $post_text_attr; ?>></p>
            </a>
            <p class="ctf-tweet-text" <?php echo $text_no_link_attr; ?> <?php echo $post_text_attr; ?>><?php echo nl2br( $post_text ) ?></p>
            <?php
                if(!$feed_options['is_legacy'] || ($feed_options['is_legacy'] && ctf_show( 'placeholder', $feed_options ))){
                    echo $post_media_text;
                }
            ?>
            <?php
        }
    }

    public static function get_ajax_code( $feed_options ) {
        $json_array = array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'font_method' => 'svg',
            'placeholder' => trailingslashit( CTF_PLUGIN_URL ) . 'img/placeholder.png'
        );
        return '<script> var ctfOptions = ' . ctf_json_encode( $json_array ) . '</script><script type="text/javascript" src="'. CTF_JS_URL .'"></script>';
    }

    public static function display_action_links( $feed_options ) {
        return ctf_show( 'actions', $feed_options ) || (isset($feed_options['is_legacy']) && $feed_options['is_legacy'] == true);
    }

}
