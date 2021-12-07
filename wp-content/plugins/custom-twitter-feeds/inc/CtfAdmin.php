<?php
/**
 * Class CtfAdmin
 *
 * Uses the Settings API to create easily customizable settings pages and tabs
 */

// Don't load directly
if ( ! defined( 'ABSPATH' ) ) {
    die( '-1' );
}

class CtfAdmin
{
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_init', array( $this, 'options_page_init' ) );
        add_action( 'admin_init', array( $this, 'ctf_current_user_can' ) );
    }

    public function ctf_current_user_can( $cap ) {
        if ( $cap === 'manage_custom_twitter_feeds_options' ) {
            $cap = current_user_can( 'manage_custom_twitter_feeds_options' ) ? 'manage_custom_twitter_feeds_options' : 'manage_options';
        }
        $cap = apply_filters( 'ctf_settings_pages_capability', $cap );

        return current_user_can( $cap );
    }

    public function add_menu()
    {
        $cap = current_user_can( 'manage_custom_twitter_feeds_options' ) ? 'manage_custom_twitter_feeds_options' : 'manage_options';

	    $cap = apply_filters( 'ctf_settings_pages_capability', $cap );
		$ctf_notifications = new CTF_Notifications();
		$notifications = $ctf_notifications->get();

		$notice_bubble = '';
		if ( empty( $notice ) && ! empty( $notifications ) && is_array( $notifications ) ) {
			$notice_bubble = ' <span class="ctf-notice-alert"><span>'.count( $notifications ).'</span></span>';
		}
        add_menu_page(
            'Twitter Feeds',
            'Twitter Feeds' . $notice_bubble,
            $cap,
            'custom-twitter-feeds',
            array( $this, 'create_options_page' ),
            '',
            99
        );

        add_submenu_page(
            'custom-twitter-feeds',
            'Customize',
            'Customize',
            $cap,
            'custom-twitter-feeds-customize',
            array( $this, 'create_submenu_page_customize' )
        );

        add_submenu_page(
            'custom-twitter-feeds',
            'Style',
            'Style',
            $cap,
            'custom-twitter-feeds-style',
            array( $this, 'create_submenu_page_style' )
        );

        //Show a Instagram plugin menu item if it isn't already installed
        if( !is_plugin_active( 'instagram-feed/instagram-feed.php' ) && !is_plugin_active( 'instagram-feed-pro/instagram-feed.php' ) ){
            add_submenu_page(
                'custom-twitter-feeds',
                __( 'Instagram Feed', 'ctf' ),
                '<span class="ctf_get_sbi"><svg style="height: 14px; margin: 0 8px 0 0; position: relative; top: 2px;" aria-hidden="true" focusable="false" data-prefix="fab" data-icon="instagram" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" class="svg-inline--fa fa-instagram fa-w-14 fa-2x"><path fill="currentColor" d="M224.1 141c-63.6 0-114.9 51.3-114.9 114.9s51.3 114.9 114.9 114.9S339 319.5 339 255.9 287.7 141 224.1 141zm0 189.6c-41.1 0-74.7-33.5-74.7-74.7s33.5-74.7 74.7-74.7 74.7 33.5 74.7 74.7-33.6 74.7-74.7 74.7zm146.4-194.3c0 14.9-12 26.8-26.8 26.8-14.9 0-26.8-12-26.8-26.8s12-26.8 26.8-26.8 26.8 12 26.8 26.8zm76.1 27.2c-1.7-35.9-9.9-67.7-36.2-93.9-26.2-26.2-58-34.4-93.9-36.2-37-2.1-147.9-2.1-184.9 0-35.8 1.7-67.6 9.9-93.9 36.1s-34.4 58-36.2 93.9c-2.1 37-2.1 147.9 0 184.9 1.7 35.9 9.9 67.7 36.2 93.9s58 34.4 93.9 36.2c37 2.1 147.9 2.1 184.9 0 35.9-1.7 67.7-9.9 93.9-36.2 26.2-26.2 34.4-58 36.2-93.9 2.1-37 2.1-147.8 0-184.8zM398.8 388c-7.8 19.6-22.9 34.7-42.6 42.6-29.5 11.7-99.5 9-132.1 9s-102.7 2.6-132.1-9c-19.6-7.8-34.7-22.9-42.6-42.6-11.7-29.5-9-99.5-9-132.1s-2.6-102.7 9-132.1c7.8-19.6 22.9-34.7 42.6-42.6 29.5-11.7 99.5-9 132.1-9s102.7-2.6 132.1 9c19.6 7.8 34.7 22.9 42.6 42.6 11.7 29.5 9 99.5 9 132.1s2.7 102.7-9 132.1z" class=""></path></svg>' . __( 'Instagram Feed', 'ctf' ) . '</span>',
                'manage_options',
                'admin.php?page=custom-twitter-feeds&tab=more',
                ''
            );
        }

        //Show a Instagram plugin menu item if it isn't already installed
        if( !is_plugin_active( 'custom-facebook-feed/custom-facebook-feed.php' ) && !is_plugin_active( 'custom-facebook-feed-pro/custom-facebook-feed.php' ) ){
            add_submenu_page(
                'custom-twitter-feeds',
                __( 'Facebook Feed', 'ctf' ),
                '<span class="ctf_get_cff"><svg style="height: 14px; margin: 0 8px 0 0; position: relative; top: 2px;" aria-hidden="true" focusable="false" data-prefix="fab" data-icon="facebook" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="svg-inline--fa fa-facebook fa-w-16 fa-2x"><path fill="currentColor" d="M504 256C504 119 393 8 256 8S8 119 8 256c0 123.78 90.69 226.38 209.25 245V327.69h-63V256h63v-54.64c0-62.15 37-96.48 93.67-96.48 27.14 0 55.52 4.84 55.52 4.84v61h-31.28c-30.8 0-40.41 19.12-40.41 38.73V256h68.78l-11 71.69h-57.78V501C413.31 482.38 504 379.78 504 256z" class=""></path></svg>' . __( 'Facebook Feed', 'ctf' ) . '</span>',
                'manage_options',
                'admin.php?page=custom-twitter-feeds&tab=more',
                ''
            );
        }

        //Show a YouTube plugin menu item if it isn't already installed
        if( !is_plugin_active( 'feeds-for-youtube/youtube-feed.php' ) && !is_plugin_active( 'youtube-feed-pro/youtube-feed.php' ) ){
            add_submenu_page(
                'custom-twitter-feeds',
                __( 'YouTube Feed', 'ctf' ),
                '<span class="ctf_get_yt"><svg style="height: 14px; margin: 0 8px 0 0; position: relative; top: 2px;" aria-hidden="true" focusable="false" data-prefix="fab" data-icon="youtube" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" class="svg-inline--fa fa-youtube fa-w-18 fa-2x"><path fill="currentColor" d="M549.655 124.083c-6.281-23.65-24.787-42.276-48.284-48.597C458.781 64 288 64 288 64S117.22 64 74.629 75.486c-23.497 6.322-42.003 24.947-48.284 48.597-11.412 42.867-11.412 132.305-11.412 132.305s0 89.438 11.412 132.305c6.281 23.65 24.787 41.5 48.284 47.821C117.22 448 288 448 288 448s170.78 0 213.371-11.486c23.497-6.321 42.003-24.171 48.284-47.821 11.412-42.867 11.412-132.305 11.412-132.305s0-89.438-11.412-132.305zm-317.51 213.508V175.185l142.739 81.205-142.739 81.201z" class=""></path></svg>' . __( 'YouTube Feed', 'ctf' ) . '</span>',
                'manage_options',
                'admin.php?page=custom-twitter-feeds&tab=more',
                ''
            );
        }

        add_submenu_page(
            'custom-twitter-feeds',
            __( 'Social Wall', 'ctf' ),
            '<span><svg style="height: 14px; margin: 0 8px 0 0; position: relative; top: 2px;" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="th" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="svg-inline--fa fa-th fa-w-16 fa-2x"><path fill="currentColor" d="M149.333 56v80c0 13.255-10.745 24-24 24H24c-13.255 0-24-10.745-24-24V56c0-13.255 10.745-24 24-24h101.333c13.255 0 24 10.745 24 24zm181.334 240v-80c0-13.255-10.745-24-24-24H205.333c-13.255 0-24 10.745-24 24v80c0 13.255 10.745 24 24 24h101.333c13.256 0 24.001-10.745 24.001-24zm32-240v80c0 13.255 10.745 24 24 24H488c13.255 0 24-10.745 24-24V56c0-13.255-10.745-24-24-24H386.667c-13.255 0-24 10.745-24 24zm-32 80V56c0-13.255-10.745-24-24-24H205.333c-13.255 0-24 10.745-24 24v80c0 13.255 10.745 24 24 24h101.333c13.256 0 24.001-10.745 24.001-24zm-205.334 56H24c-13.255 0-24 10.745-24 24v80c0 13.255 10.745 24 24 24h101.333c13.255 0 24-10.745 24-24v-80c0-13.255-10.745-24-24-24zM0 376v80c0 13.255 10.745 24 24 24h101.333c13.255 0 24-10.745 24-24v-80c0-13.255-10.745-24-24-24H24c-13.255 0-24 10.745-24 24zm386.667-56H488c13.255 0 24-10.745 24-24v-80c0-13.255-10.745-24-24-24H386.667c-13.255 0-24 10.745-24 24v80c0 13.255 10.745 24 24 24zm0 160H488c13.255 0 24-10.745 24-24v-80c0-13.255-10.745-24-24-24H386.667c-13.255 0-24 10.745-24 24v80c0 13.255 10.745 24 24 24zM181.333 376v80c0 13.255 10.745 24 24 24h101.333c13.255 0 24-10.745 24-24v-80c0-13.255-10.745-24-24-24H205.333c-13.255 0-24 10.745-24 24z" class=""></path></svg>' . __( 'Social Wall', 'ctf' ) . '</span>',
            $cap,
            'ctf-sw',
	        array( $this, 'social_wall_page' )
        );

        add_submenu_page(
            'custom-twitter-feeds',
            __( 'Try the Pro Demo', 'ctf' ),
            __( '<span class="ctf_get_pro">Try the Pro Demo</span>', 'custom-twitter-feeds' ),
            $cap,
            'https://smashballoon.com/custom-twitter-feeds/demo/?utm_campaign=twitter-free&utm_source=menu-link&utm_medium=upgrade-link',
            ''
        );

    }


    public static function get_active_tab( $tab = '' )
    {
        switch ( $tab ) {
            case 'customize':
                return 'customize';
            case 'style':
                return 'style';
            case 'display':
                return 'display';
            case 'support':
                return 'support';
	        case 'allfeeds':
		        return 'allfeeds';
	        case 'more':
		        return 'more';
            default:
                return 'configure';
        }
    }

    public function create_options_page()
    {
        require_once CTF_URL . '/views/admin/main.php';
    }

    public function create_submenu_page_customize()
    {
        $tab = 'customize';

        require_once CTF_URL . '/views/admin/main.php';
    }

    public function create_submenu_page_style()
    {
        $tab = 'style';

        require_once CTF_URL . '/views/admin/main.php';
    }


public function social_wall_page() {
		require_once CTF_URL .'views/admin/social-wall.php';
	}

    public function general_section_text()
    {
        // no explanation needed
    }

    public function access_token_button()
    {
        $this->the_admin_access_token_configure_html( $_GET );
        $options = get_option( 'ctf_options' );
        $option_checked = ( isset( $options['have_own_tokens'] ) ) ? $options['have_own_tokens'] : false;
        ?>
        <input name="<?php echo 'ctf_options'.'[have_own_tokens]'; ?>" id="ctf_have_own_tokens" type="checkbox" <?php if ( $option_checked ) echo "checked"; ?> />
        <label for="ctf_have_own_tokens" class="ctf_checkbox"><?php _e( 'Or, manually enter my own Twitter app information' ); ?></label>
        <span class="ctf-tooltip-wrap">
            <a class="ctf-tooltip-link" href="JavaScript:void(0);"><i class="fa fa-question-circle" aria-hidden="true"></i></a>
            <p class="ctf-tooltip ctf-more-info"><?php _e( 'Check this box if you would like to manually enter the information from your own <a href="https://smashballoon.com/custom-twitter-feeds/docs/create-twitter-app/?utm_campaign=twitter-free&utm_source=settings&utm_medium=createownapp" target="_blank">Twitter app</a>', 'custom-twitter-feeds' ); ?>.</p>
        </span>
        <?php
    }

    /**
     * generates the html for the access token retrieving button
     *
     * @param $access_token_data array      the $_GET data if it exists
     */
    private function the_admin_access_token_configure_html( $access_token_data ) {
        ?>

        <div id="ctf_config">

            <?php if ( isset( $access_token_data['oauth_token'] ) ) : ?>
                <a href="<?php echo OAUTH_PROCESSOR_URL . admin_url( 'admin.php?page=custom-twitter-feeds' ); ?>" id="ctf-get-token"><i class="fa fa-twitter"></i><?php _e( 'Log in to Twitter and get my Access Token and Secret' ); ?></a>
                <a class="ctf-tooltip-link" href="https://smashballoon.com/custom-twitter-feeds/token/?utm_campaign=twitter-free&utm_source=settings&utm_medium=buttonnotworking" target="_blank"><?php _e( "Button not working?", 'custom-twitter-feeds' ); ?></a>

                <input type="hidden" id="ctf-retrieved-access-token" value="<?php echo esc_html( sanitize_text_field( $access_token_data['oauth_token'] ) ); ?>">
                <input type="hidden" id="ctf-retrieved-access-token-secret" value="<?php echo esc_html( sanitize_text_field( $access_token_data['oauth_token_secret'] ) ); ?>">
                <input type="hidden" id="ctf-retrieved-default-screen-name" value="<?php echo esc_html( sanitize_text_field( $access_token_data['screen_name'] ) ); ?>">

            <?php elseif ( isset( $access_token_data['error'] ) && ! isset( $access_token_data['oauth_token'] ) ) : ?>

                <p class="ctf_error_notice"><?php _e( 'There was an error with retrieving your access tokens. Please <a href="https://smashballoon.com/custom-twitter-feeds/token/?utm_campaign=twitter-free&utm_source=settings&utm_medium=errorconnecting" target="_blank">use this tool</a> to get your access token and secret.' ); ?></p><br>
                <a href="<?php echo OAUTH_PROCESSOR_URL . admin_url( 'admin.php?page=custom-twitter-feeds' ); ?>" id="ctf-get-token"><i class="fa fa-twitter"></i><?php _e( 'Log in to Twitter and get my Access Token and Secret' ); ?></a>
                <a class="ctf-tooltip-link" href="https://smashballoon.com/custom-twitter-feeds/token/?utm_campaign=twitter-free&utm_source=settings&utm_medium=errorconnecting" target="_blank"><?php _e( "Button not working?", 'custom-twitter-feeds' ); ?></a>

            <?php else : ?>

                <a href="<?php echo OAUTH_PROCESSOR_URL . admin_url( 'admin.php?page=custom-twitter-feeds' ); ?>" id="ctf-get-token"><i class="fa fa-twitter"></i><?php _e( 'Log in to Twitter and get my Access Token and Secret' ); ?></a>
                <a class="ctf-tooltip-link" href="https://smashballoon.com/custom-twitter-feeds/token/?utm_campaign=twitter-free&utm_source=settings&utm_medium=buttonnotworking" target="_blank"><?php _e( "Button not working?", 'custom-twitter-feeds' ); ?></a>

            <?php endif; ?>

        </div>
        <?php
    }

    public function options_page_init()
    {
        /*
         * "Configure" Tab
         */

        register_setting(
            'ctf_options', // name of the option that gets called in "get_option()"
            'ctf_options', // matches the options name
            array( $this, 'validate_ctf_options' ) // callback function to validate and clean data
        );

        add_settings_section(
            'ctf_options_connect', // matches the section name
            'Configuration',
            array( $this, 'access_token_button' ), // callback function to explain the section
            'ctf_options_connect' // matches the section name
        );

        // Consumer Key
        $this->create_settings_field( array(
            'name' => 'consumer_key',
            'title' => '<label for="ctf_consumer_key">Consumer Key</label>', // label for the input field
            'callback'  => 'default_text', // name of the function that outputs the html
            'page' => 'ctf_options_connect', // matches the section name
            'section' => 'ctf_options_connect', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'ctf-toggle-consumer', // class for the wrapper and input field
            'whatis' => 'A Consumer Key and a Consumer Secret are both needed if you want to use credentials from your own Twitter App. You can create these <a href="https://smashballoon.com/custom-twitter-feeds/docs/create-twitter-app/?utm_campaign=twitter-free&utm_source=settings&utm_medium=createapp" target="_blank">here</a>', // what is this? text
            'size' => '27'
        ) );

        // Consumer Secret
        $this->create_settings_field( array(
            'name' => 'consumer_secret',
            'title' => '<label for="ctf_consumer_secret">Consumer Secret</label>', // label for the input field
            'callback'  => 'default_text', // name of the function that outputs the html
            'page' => 'ctf_options_connect', // matches the section name
            'section' => 'ctf_options_connect', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'ctf-toggle-consumer', // class for the wrapper and input field
            'whatis' => 'A Consumer Key and a Consumer Secret are both needed if you want to use credentials from your own Twitter App. You can create these <a href="https://smashballoon.com/custom-twitter-feeds/docs/create-twitter-app/?utm_campaign=twitter-free&utm_source=settings&utm_medium=createapp" target="_blank">here</a>', // what is this? text
            'size' => '57'
        ) );

        // Access Token
        $this->create_settings_field( array(
            'name' => 'access_token',
            'title' => '<label for="ctf_access_token">Access Token</label>', // label for the input field
            'callback'  => 'default_text', // name of the function that outputs the html
            'page' => 'ctf_options_connect', // matches the section name
            'section' => 'ctf_options_connect', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'ctf-toggle-access', // class for the wrapper and input field
            'whatis' => "This will allow the plugin to connect to the Twitter API", // "what is this?" text
            'size' => '57'
        ) );

        // Access Token Secret
        $this->create_settings_field( array(
            'name' => 'access_token_secret',
            'title' => '<label for="ctf_access_token_secret">Access Token Secret</label>', // label for the input field
            'callback'  => 'access_token_secret', // name of the function that outputs the html
            'page' => 'ctf_options_connect', // matches the section name
            'section' => 'ctf_options_connect', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'ctf-toggle-access', // class for the wrapper and input field
            'whatis' => "This will allow the plugin to connect to the Twitter API", // "what is this?" text
            'size' => '57'
        ));

        add_settings_section(
            'ctf_options_feed_settings', // matches the section name
            'Feed Settings',
            array( $this, 'general_section_text' ), // callback function to explain the section
            'ctf_options_feed_settings' // matches the section name
        );

	    $locator_html = '';
	    if ( CTF_Feed_Locator::count_unique() > 1 ) {
		    $locator_html .= '<div class="ctf_locations_link">';
		    $locator_html .= '<a href="?page=custom-twitter-feeds&amp;tab=allfeeds"><svg aria-hidden="true" focusable="false" data-prefix="far" data-icon="search" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="svg-inline--fa fa-search fa-w-16 fa-2x"><path fill="currentColor" d="M508.5 468.9L387.1 347.5c-2.3-2.3-5.3-3.5-8.5-3.5h-13.2c31.5-36.5 50.6-84 50.6-136C416 93.1 322.9 0 208 0S0 93.1 0 208s93.1 208 208 208c52 0 99.5-19.1 136-50.6v13.2c0 3.2 1.3 6.2 3.5 8.5l121.4 121.4c4.7 4.7 12.3 4.7 17 0l22.6-22.6c4.7-4.7 4.7-12.3 0-17zM208 368c-88.4 0-160-71.6-160-160S119.6 48 208 48s160 71.6 160 160-71.6 160-160 160z" class=""></path></svg> ' . __( 'Feed Finder', 'custom-twitter-feeds' ) . '</a>';
		    $locator_html .= '</div>';
	    }

        // User Timeline Radio
        $this->create_settings_field( array(
            'name' => 'usertimeline',
            'title' => '<label for="ctf_feed_type">Select a Feed Type</label><code class="ctf_shortcode">type
                            Eg: screenname=gopro
                            Eg: home=true
                            Eg: hashtag=#cats</code>' . $locator_html, // label for the input field
            'callback'  => 'feed_settings_radio', // name of the function that outputs the html
            'page' => 'ctf_options_feed_settings', // matches the section name
            'section' => 'ctf_options_feed_settings', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'ctf-radio', // class for the wrapper and input field
            'whatis' => "Select this option and enter any screen name to create a user timeline feed", // what is this? text
            'label' => "User Timeline:",
            'has_input' => true,
            'has_replies' => true
        ));

        // Search Radio
        $search_label = apply_filters( 'ctf_admin_search_label', '' );
        $search_whatis = apply_filters( 'ctf_admin_search_whatis', '' );
        $this->create_settings_field( array(
            'name' => 'search',
            'title' => '<label></label>', // label for the input field
            'callback'  => 'feed_settings_radio_search', // name of the function that outputs the html
            'page' => 'ctf_options_feed_settings', // matches the section name
            'section' => 'ctf_options_feed_settings', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'ctf-radio', // class for the wrapper and input field
            'whatis' => $search_whatis, // what is this? text
            'label' => $search_label,
            'has_input' => true,
            'note_after_input' => __( '(only recent tweets initially)', 'custom-twitter-feeds' ),
            'extra' => true
        ) );

        // Home Timeline Radio
        $this->create_settings_field( array(
            'name' => 'hometimeline',
            'title' => '<label></label>', // label for the input field
            'callback'  => 'feed_settings_radio', // name of the function that outputs the html
            'page' => 'ctf_options_feed_settings', // matches the section name
            'section' => 'ctf_options_feed_settings', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'ctf-radio', // class for the wrapper and input field
            'whatis' => 'Select this option to display tweets from yourself and those you follow', // what is this? text
            'label' => "Home Timeline",
            'has_input' => false,
            'has_replies' => true
        ));

        do_action( 'ctf_admin_endpoints', $this );

        // Number of Tweets
        $this->create_settings_field( array(
            'name' => 'num',
            'title' => '<label for="ctf_num">How Many Tweets to Display</label><code class="ctf_shortcode">num
            Eg: num=10</code>', // label for the input field
            'callback'  => 'default_text', // name of the function that outputs the html
            'page' => 'ctf_options_feed_settings', // matches the section name
            'section' => 'ctf_options_feed_settings', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'small-text', // class for the wrapper and input field
            'whatis' => "Enter the number of tweets you would like to display when the feed first loads", // what is this? text
            'type' => 'number', // input field "type" attribute
            'default' => 5
        ));

        // time unit for cache
        $this->create_settings_field( array(
            'name' => 'cache_time',
            'title' => '<label for="ctf_cache_time">How Many Tweets to Display</label>', // label for the input field
            'callback'  => 'default_text', // name of the function that outputs the html
            'page' => 'ctf_options_feed_settings', // matches the section name
            'section' => 'ctf_options_feed_settings', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'small-text', // class for the wrapper and input field
            'whatis' => "Enter the number of tweets you would like to display when the feed first loads", // what is this? text
            'type' => 'number' // input field "type" attribute
        ));

        // check for new tweets
        $this->create_settings_field( array(
            'name' => 'cache_time',
            'title' => '<label for="ctf_cache_time">Check for new tweets every</label>', // label for the input field
            'callback'  => 'cache_time', // name of the function that outputs the html
            'page' => 'ctf_options_feed_settings', // matches the section name
            'section' => 'ctf_options_feed_settings', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'short-text', // class for the wrapper and input field
            'whatis' => "Your Tweets are temporarily cached by the plugin in your WordPress database. You can choose how long the posts should be cached for. If you set the time to 1 hour then the plugin will clear the cache after that length of time and check Instagram for posts again" // what is this? text
        ) );

        // preserve settings
        $this->create_settings_field( array(
            'name' => 'preserve_settings',
            'title' => '<label for="ctf_preserve_settings">Preserve settings when plugin is removed</label>', // label for the input field
            'callback'  => 'default_checkbox', // name of the function that outputs the html
            'page' => 'ctf_options_feed_settings', // matches the section name
            'section' => 'ctf_options_feed_settings', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => '',
            'whatis' => "When removing the plugin your settings are automatically erased. Checking this box will prevent any settings from being deleted. This means that you can uninstall and reinstall the plugin without losing your settings"
        ));

        /*
         * "Customize" tab
         */

        add_settings_section(
            'ctf_options_general', // matches the section name
            'General',
            array( $this, 'general_section_text' ), // callback function to explain the section
            'ctf_options_general' // matches the section name
        );

        // width
        $this->create_settings_field( array(
            'name' => 'width',
            'title' => '<label for="ctf_width">Width of Feed</label><code class="ctf_shortcode">width
            Eg: width=500</code>', // label for the input field
            'callback'  => 'width_and_height_settings', // name of the function that outputs the html
            'page' => 'ctf_options_general', // matches the section name
            'section' => 'ctf_options_general', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'small-text',
            'default' => '100',
            'default_unit' => '%'
        ));

        // height
        $this->create_settings_field( array(
            'name' => 'height',
            'title' => '<label for="ctf_height">Height of Feed</label><code class="ctf_shortcode">height
            Eg: height=1000</code>', // label for the input field
            'callback'  => 'width_and_height_settings', // name of the function that outputs the html
            'page' => 'ctf_options_general', // matches the section name
            'section' => 'ctf_options_general', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'small-text',
            'default_unit' => 'px'
        ));

        // class
        $this->create_settings_field( array(
            'name' => 'class',
            'title' => '<label for="ctf_class">Add Custom CSS Class</label><code class="ctf_shortcode">class
            Eg: class="my-class"</code>', // label for the input field
            'callback'  => 'default_text', // name of the function that outputs the html
            'page' => 'ctf_options_general', // matches the section name
            'section' => 'ctf_options_general', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'default-text',
            'type' => 'text',
            'whatis' => "You can add your own CSS classes to the feed here. To add multiple classes separate each with a space, Eg. classone classtwo classthree"
        ));

	    add_settings_section(
		    'ctf_options_layout', // matches the section name
		    'Layout',
		    array( $this, 'general_section_text' ), // callback function to explain the section
		    'ctf_options_layout' // matches the section name
	    );

	    $settings = get_option( 'ctf_options', array() );
	    $layout = 'list';
	    $layout_selections = array(
		    'layout' => $layout,
		    'carouselcols' => isset( $settings['carouselcols'] ) ? $settings['carouselcols'] : 3,
		    'carouselmobilecols' => isset( $settings['carouselmobilecols'] ) ? $settings['carouselmobilecols'] : 1,
		    'carouselarrows' => isset( $settings['carouselarrows'] ) ? $settings['carouselarrows'] : 'onhover',
		    'carouselpag' => isset( $settings['carouselpag'] ) ? $settings['carouselpag'] : true,
		    'carouselheight' => isset( $settings['carouselheight'] ) ? $settings['carouselheight'] : 'tallest',
		    'carouselautoplay' => isset( $settings['carouselautoplay'] ) ? $settings['carouselautoplay'] : false,
		    'carouseltime' => isset( $settings['carouseltime'] ) ? $settings['carouseltime'] : '5000',
		    'carouselloop' => isset( $settings['carouselloop'] ) ? $settings['carouselloop'] : 'infinite',
		    'masonrycols' => isset( $settings['masonrycols'] ) ? $settings['masonrycols'] : 3,
		    'masonrymobilecols' => isset( $settings['masonrymobilecols'] ) ? $settings['masonrymobilecols'] : 1,
	    );

	    $this->create_settings_field( array(
		    'name' => 'class',
		    'title' => '<label for="ctf_layout">Layout Type</label><code class="ctf_shortcode">layout
            Eg: layout="masonry"</code>', // label for the input field
		    'callback'  => 'layout', // name of the function that outputs the html
		    'page' => 'ctf_options_layout', // matches the section name
		    'section' => 'ctf_options_layout', // matches the section name
		    'option' => 'ctf_options', // matches the options name
		    'class' => 'default-text ctf_pro',
		    'type' => 'text',
		    'layout_selections' => $layout_selections,
		    'whatis' => ""
	    ));

        add_settings_section(
            'ctf_options_showandhide', // matches the section name
            'Show/Hide',
            array( $this, 'general_section_text' ), // callback function to explain the section
            'ctf_options_showandhide' // matches the section name
        );

        // show/hide
        $show_hide_list = array(
            array( 'include_retweeter', 'Retweeted text' ),
            array( 'include_avatar', 'Avatar image' ),
            array( 'include_author', 'Author name' ),
	        array( 'include_logo', 'Twitter logo' ),
	        array( 'include_text', 'Tweet text' ),
            array( 'include_media_placeholder', 'Media placeholder' ),
            array( 'include_date', 'Date' ),
            array( 'include_actions', 'Tweet actions (reply, retweet, like)' ),
            array( 'include_twitterlink', '"Twitter" link' ),
            array( 'include_linkbox', 'Quoted tweet box' )
        );
        $show_hide_list = apply_filters( 'ctf_admin_show_hide_list', $show_hide_list );

        $this->create_settings_field( array(
            'name' => 'showandhide',
            'title' => '<label>Include the Following in Tweets <em>(when applicable)</em></label><code class="ctf_shortcode">include exclude
            Eg: include=author,date
            Eg: exclude=actions
            Options: avatar, author,
            logo, text, placeholder,
            date, actions, linkbox </code>', // label for the input field
            'callback'  => 'include_exclude_checkbox', // name of the function that outputs the html
            'page' => 'ctf_options_showandhide', // matches the section name
            'section' => 'ctf_options_showandhide', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'fields' => $show_hide_list,
            'class' => ''
        ));

        // show header
        $this->create_settings_field( array(
            'name' => 'showheader',
            'title' => '<label for="ctf_showheader">Show Header</label><code class="ctf_shortcode">showheader
            Eg: showheader=true</code>', // label for the input field
            'callback'  => 'reverse_checkbox', // name of the function that outputs the html
            'page' => 'ctf_options_showandhide', // matches the section name
            'section' => 'ctf_options_showandhide', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => '',
            'whatis' => "The header is displayed above your tweets with some basic information about the feed"
        ));

        // load more button
        $this->create_settings_field( array(
            'name' => 'showbutton',
            'title' => '<label for="ctf_showbutton">Show the "Load More" Button</label><code class="ctf_shortcode">showbutton
            Eg: showbutton=true</code>', // label for the input field
            'callback'  => 'reverse_checkbox', // name of the function that outputs the html
            'page' => 'ctf_options_showandhide', // matches the section name
            'section' => 'ctf_options_showandhide', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => '',
            'whatis' => "Show the Load More Button",
        ));


        // credit ctf
        $this->create_settings_field( array(
            'name' => 'creditctf',
            'title' => '<label for="ctf_creditctf">Add Custom Twitter Feeds Credit</label><code class="ctf_shortcode">creditctf
            Eg: creditctf=true</code>', // label for the input field
            'callback'  => 'default_checkbox', // name of the function that outputs the html
            'page' => 'ctf_options_showandhide', // matches the section name
            'section' => 'ctf_options_showandhide', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => '',
            'whatis' => "Help us keep this plugin great! Add a link below your feed to credit Custom Twitter Feeds by Smash Balloon"
        ));

        do_action( 'ctf_admin_customize_option', $this );

        add_settings_section(
            'ctf_options_misc', // matches the section name
            'Misc',
            array( $this, 'general_section_text' ), // callback function to explain the section
            'ctf_options_misc' // matches the section name
        );

        // Custom CSS
        $this->create_settings_field( array(
            'name' => 'custom_css',
            'title' => '<label for="ctf_custom_css">Custom CSS</label>', // label for the input field
            'callback'  => 'custom_code', // name of the function that outputs the html
            'page' => 'ctf_options_misc', // matches the section name
            'section' => 'ctf_options_misc', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'default-text', // class for the wrapper and input field
            'description' => 'Enter your own custom CSS in the box below'
        ));

        // Custom JS
        $this->create_settings_field( array(
            'name' => 'custom_js',
            'title' => '<label for="ctf_custom_js">Custom Javascript*</label>', // label for the input field
            'callback'  => 'custom_code', // name of the function that outputs the html
            'page' => 'ctf_options_misc', // matches the section name
            'section' => 'ctf_options_misc', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'default-text', // class for the wrapper and input field
            'description' => 'Enter your own custom Javascript/JQuery in the box below',
            'extra' => '*will be fired every time more tweets are loaded'
        ));

	    add_settings_section(
		    'ctf_options_gdpr', // matches the section name
		    'GDPR',
		    array( $this, 'general_section_text' ), // callback function to explain the section
		    'ctf_options_gdpr' // matches the section name
	    );

	    $this->create_settings_field( array(
		    'name' => 'gdpr',
		    'title' => '<label for="ctf_gdpr">Enable GDPR Settings</label>', // label for the input field
		    'callback'  => 'gdpr', // name of the function that outputs the html
		    'page' => 'ctf_options_gdpr', // matches the section name
		    'section' => 'ctf_options_gdpr', // matches the section name
		    'option' => 'ctf_options', // matches the options name
		    'class' => 'default-text', // class for the wrapper and input field
	    ));

        add_settings_section(
            'ctf_options_advanced', // matches the section name
            'Advanced',
            array( $this, 'general_section_text' ), // callback function to explain the section
            'ctf_options_advanced' // matches the section name
        );

	    // ajax theme
	    $this->create_settings_field( array(
		    'name' => 'ajax_theme',
		    'title' => '<label for="ctf_ajax_theme">Are you using an Ajax powered theme?</label>', // label for the input field
		    'callback'  => 'default_checkbox', // name of the function that outputs the html
		    'page' => 'ctf_options_advanced', // matches the section name
		    'section' => 'ctf_options_advanced', // matches the section name
		    'option' => 'ctf_options', // matches the options name
		    'class' => '',
		    'whatis' => "When navigating your site, if your theme uses Ajax to load content into your pages (meaning your page doesn't refresh) then check this setting. If you're not sure then please check with the theme author"
	    ));

        // Request Method
        $this->create_settings_field( array(
            'name' => 'request_method',
            'title' => '<label for="ctf_request_method">Request Method</label>', // label for the input field
            'callback'  => 'default_select', // name of the function that outputs the html
            'page' => 'ctf_options_advanced', // matches the section name
            'section' => 'ctf_options_advanced', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'default-text', // class for the wrapper and input field
            'fields' => array(
                1 => array( 'auto', 'Auto' ),
                2 => array( 'curl', 'cURL' ),
                3 => array( 'file_get_contents', 'file_get_contents()' ),
                4 => array( 'wp_http', 'WP_Http' )
            ),
            'whatis' => "Explicitly set the request method. You would only want to change this if you are unable to connect to the Twitter API" // what is this? text
        ) );

        // force cache to clear on interval
        $this->create_settings_field( array(
            'name' => 'cron_cache_clear',
            'title' => '<label for="ctf_cron_cache_clear">Force cache to clear on interval</label>', // label for the input field
            'callback'  => 'default_select', // name of the function that outputs the html
            'page' => 'ctf_options_advanced', // matches the section name
            'section' => 'ctf_options_advanced', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'default-text', // class for the wrapper and input field
            'fields' => array(
                1 => array( 'unset', '-' ),
                2 => array( 'yes', 'Yes' ),
                3 => array( 'no', 'No' )
            ),
            'whatis' => "If you're experiencing an issue with the plugin not auto-updating then you can set this to 'Yes' to run a scheduled event behind the scenes which forces the plugin cache to clear on a regular basis and retrieve new data from Twitter" // what is this? text
        ) );

        // tweet multiplier
        $this->create_settings_field( array(
            'name' => 'multiplier',
            'title' => '<label for="ctf_multiplier">Tweet Multiplier</label><code class="ctf_shortcode">multiplier
            Eg: multiplier=1.5</code>', // label for the input field
            'callback'  => 'default_text', // name of the function that outputs the html
            'page' => 'ctf_options_advanced', // matches the section name
            'section' => 'ctf_options_advanced', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'small-text', // class for the wrapper and input field
            'whatis' => "If your feed excludes reply tweets (this is automatic in hashtag/search feeds), the correct number of tweets may not show up. Increasing this number will increase the number of tweets retrieved but will also increase the load time for the feed as well", // what is this? text
            'type' => 'number', // input field "type" attribute
            'min' => 1,
            'max' => 3,
            'step' => 'any',
            'default' => 1.25
        ));

        $this->create_settings_field( array(
            'name' => 'persistent',
            'title' => '<label for="ctf_multiplier">Clear Persistent Cache</label>', // label for the input field
            'callback'  => 'clear_persistent_cache_button', // name of the function that outputs the html
            'page' => 'ctf_options_advanced', // matches the section name
            'section' => 'ctf_options_advanced', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'small-text' // class for the wrapper and input field
        ));

        // persistent cache
        $this->create_settings_field( array(
            'name' => 'persistentcache',
            'title' => '<label for="ctf_persistentcache">Persistent cache enabled by default</label><code class="ctf_shortcode">persistentcache
            Eg: persistentcache=false</code>', // label for the input field
            'callback'  => 'reverse_checkbox', // name of the function that outputs the html
            'page' => 'ctf_options_advanced', // matches the section name
            'section' => 'ctf_options_advanced', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => '',
            'whatis' => "Checking this box will make all Search and Hashtag feeds have a permanent cache saved in the database by default of up to 150 tweets. Tweets will be available for the feed even after the 7 day limit though numbers of retweets and likes will not update"
        ));

	    $this->create_settings_field( array(
		    'name' => 'selfreplies',
		    'title' => '<label for="ctf_selfreplies">Always include replies to self in the feed</label><code class="ctf_shortcode">autores
            Eg: selfreplies=true</code>', // label for the input field
		    'callback'  => 'default_checkbox', // name of the function that outputs the html
		    'page' => 'ctf_options_advanced', // matches the section name
		    'section' => 'ctf_options_advanced', // matches the section name
		    'option' => 'ctf_options', // matches the options name
		    'class' => '',
		    'whatis' => "Twitter considers @mentions of your own account and replies to your own account's tweets as \"reply\" tweets. Enable this setting to include these type of tweets in your feed"
	    ));

	    $this->create_settings_field( array(
		    'name' => 'disableintents',
		    'title' => '<label for="ctf_disableintents">Disable Twitter intents JS</label><code class="ctf_shortcode">disableintents
            Eg: disableintents=true</code>', // label for the input field
		    'callback'  => 'default_checkbox', // name of the function that outputs the html
		    'page' => 'ctf_options_advanced', // matches the section name
		    'section' => 'ctf_options_advanced', // matches the section name
		    'option' => 'ctf_options', // matches the options name
		    'class' => '',
		    'whatis' => "Twitter provides JavaScript that allows visitors of your site to reply to, retweet, and like tweets without leaving your site. This can be disabled using this setting"
	    ));

	    $this->create_settings_field( array(
		    'name' => 'shorturls',
		    'title' => '<label for="ctf_shorturls">Use shortened urls</label>', // label for the input field
		    'callback'  => 'default_checkbox', // name of the function that outputs the html
		    'page' => 'ctf_options_advanced', // matches the section name
		    'section' => 'ctf_options_advanced', // matches the section name
		    'option' => 'ctf_options', // matches the options name
		    'class' => '',
		    'whatis' => "Twitter provides shortened versions of links in tweets. Enable this setting to use them instead of the full URLs"
	    ));

        /**
         *  "Style" tab
         */

        add_settings_section(
            'ctf_options_general_style', // matches the section name
            'General',
            array( $this, 'general_section_text' ), // callback function to explain the section
            'ctf_options_general_style' // matches the section name
        );

        // background color
        $this->create_settings_field( array(
            'name' => 'bgcolor',
            'title' => '<label for="ctf_bgcolor">Feed Background Color</label><code class="ctf_shortcode">bgcolor
            Eg: bgcolor=#eee</code>', // label for the input field
            'callback'  => 'default_color', // name of the function that outputs the html
            'page' => 'ctf_options_general_style', // matches the section name
            'section' => 'ctf_options_general_style', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => '',
            'whatis' => "The background color of the feed"
        ));

        // tweet background color
        $this->create_settings_field( array(
            'name' => 'tweetbgcolor',
            'title' => '<label for="ctf_tweetbgcolor">Tweet Background Color</label><code class="ctf_shortcode">tweetbgcolor
            Eg: tweetbgcolor=#eee</code>', // label for the input field
            'callback'  => 'default_color', // name of the function that outputs the html
            'page' => 'ctf_options_general_style', // matches the section name
            'section' => 'ctf_options_general_style', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => '',
            'whatis' => "The background color of each tweet"
        ));

        add_settings_section(
            'ctf_options_header', // matches the section name
            'Header',
            array( $this, 'general_section_text' ), // callback function to explain the section
            'ctf_options_header' // matches the section name
        );

        // show bio
        $this->create_settings_field( array(
            'name' => 'showbio',
            'title' => '<label for="ctf_showbio">Show Bio</label><code class="ctf_shortcode">showbio
            Eg: showbio=false</code>', // label for the input field
            'callback'  => 'reverse_checkbox', // name of the function that outputs the html
            'page' => 'ctf_options_header', // matches the section name
            'section' => 'ctf_options_header', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'default-text', // class for the wrapper and input field
            'whatis' => "Show the bio text description on the header of the feed"
        ));

        // header background color
        $this->create_settings_field( array(
            'name' => 'headerbgcolor',
            'title' => '<label for="ctf_headerbgcolor">Header Background Color</label><code class="ctf_shortcode">headerbgcolor
            Eg: headerbgcolor=#ee0</code>', // label for the input field
            'callback'  => 'default_color', // name of the function that outputs the html
            'page' => 'ctf_options_header', // matches the section name
            'section' => 'ctf_options_header', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => ''
        ));

        // header text color
        $this->create_settings_field( array(
            'name' => 'headertextcolor',
            'title' => '<label for="ctf_headertextcolor">Header Text Color</label><code class="ctf_shortcode">headertextcolor
            Eg: headertextcolor=#444</code>', // label for the input field
            'callback'  => 'default_color', // name of the function that outputs the html
            'page' => 'ctf_options_header', // matches the section name
            'section' => 'ctf_options_header', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => ''
        ));


        // custom header text
        $this->create_settings_field( array(
            'name' => 'headertext',
            'title' => '<label for="ctf_headertext">Custom Header Text</label><code class="ctf_shortcode">headertext
            Eg: headertext="Tweets from @SmashBalloon"</code>', // label for the input field
            'callback'  => 'default_text', // name of the function that outputs the html
            'page' => 'ctf_options_header', // matches the section name
            'section' => 'ctf_options_header', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'default-text', // class for the wrapper and input field
            'whatis' => 'This will replace the default text displayed inside the optional header of the feed'  // "what is this?" text
        ));

        add_settings_section(
            'ctf_options_date', // matches the section name
            'Date',
            array( $this, 'general_section_text' ), // callback function to explain the section
            'ctf_options_date' // matches the section name
        );

        // Timezone
        $this->create_settings_field( array(
            'name' => 'timezone',
            'title' => '<label for="ctf_timezone">Timezone</label>', // label for the input field
            'callback'  => 'feed_settings_timezone', // name of the function that outputs the html
            'page' => 'ctf_options_date', // matches the section name
            'section' => 'ctf_options_date', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'default-text', // class for the wrapper and input field
            'whatis' => "Select a timezone for displaying date and timestamps of tweets" // what is this? text
        ));

        // Date Format
        $this->create_settings_field( array(
            'name' => 'dateformat',
            'title' => '<label for="ctf_date_format">Date Format</label><code class="ctf_shortcode">dateformat
            Eg: dateformat=3</code>', // label for the input field
            'callback'  => 'customize_date_format', // name of the function that outputs the html
            'page' => 'ctf_options_date', // matches the section name
            'section' => 'ctf_options_date', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'default-text', // class for the wrapper and input field
            'whatis' => "Select the format you would like for dates in tweets" // what is this? text
        ));

        // Custom Date Format
        $this->create_settings_field( array(
            'name' => 'datecustom',
            'title' => '<label for="ctf_custom_date_format">Custom Format</label><code class="ctf_shortcode">datecustom
            Eg: datecustom="D M jS, Y"</code>', // label for the input field
            'callback'  => 'customize_custom_date_format', // name of the function that outputs the html
            'page' => 'ctf_options_date', // matches the section name
            'section' => 'ctf_options_date', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'default-text', // class for the wrapper and input field
        ));

        // Custom Time Translations
        $this->create_settings_field( array(
            'name' => 'custom_time_translations',
            'title' => '<label>Custom Time Translations</label><code class="ctf_shortcode">mtime, htime,
            nowtime
            Eg: mtime="M"
            Eg: htime="S"
            Eg: nowtime="Jetzt"</code>', // label for the input field
            'callback'  => 'customize_custom_time_translations', // name of the function that outputs the html
            'page' => 'ctf_options_date', // matches the section name
            'section' => 'ctf_options_date', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'default-text', // class for the wrapper and input field
        ));

        // date Text Size
        $this->create_settings_field( array(
            'name' => 'datetextsize',
            'title' => '<label for="ctf_datetextsize">Date Text Size</label><code class="ctf_shortcode">datetextsize
            Eg: datetextsize=16</code>', // label for the input field
            'callback'  => 'text_size', // name of the function that outputs the html
            'page' => 'ctf_options_date', // matches the section name
            'section' => 'ctf_options_date', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'default-text', // class for the wrapper and input field
        ));

        // date text weight
        $this->create_settings_field( array(
            'name' => 'datetextweight',
            'title' => '<label for="ctf_datetextweight">Date Text Weight</label><code class="ctf_shortcode">datetextweight
            Eg: datetextweight=bold</code>', // label for the input field
            'callback'  => 'text_weight', // name of the function that outputs the html
            'page' => 'ctf_options_date', // matches the section name
            'section' => 'ctf_options_date', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => '',
        ));

        add_settings_section(
            'ctf_options_author', // matches the section name
            'Author',
            array( $this, 'general_section_text' ), // callback function to explain the section
            'ctf_options_author' // matches the section name
        );

        // Author Text Size
        $this->create_settings_field( array(
            'name' => 'authortextsize',
            'title' => '<label for="ctf_authortextsize">Author Text Size</label><code class="ctf_shortcode">authortextsize
            Eg: authortextsize=16</code>', // label for the input field
            'callback'  => 'text_size', // name of the function that outputs the html
            'page' => 'ctf_options_author', // matches the section name
            'section' => 'ctf_options_author', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'default-text', // class for the wrapper and input field
        ));

        // author text weight
        $this->create_settings_field( array(
            'name' => 'authortextweight',
            'title' => '<label for="ctf_authortextcolor">Author Text Weight</label><code class="ctf_shortcode">authortextweight
            Eg: authortextweight=bold</code>', // label for the input field
            'callback'  => 'text_weight', // name of the function that outputs the html
            'page' => 'ctf_options_author', // matches the section name
            'section' => 'ctf_options_author', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => '',
        ));

	    $this->create_settings_field( array(
		    'name' => 'logosize',
		    'title' => '<label for="ctf_logotextsize">Twitter Logo Size</label><code class="ctf_shortcode">logosize
            Eg: logosize=16</code>', // label for the input field
		    'callback'  => 'text_size', // name of the function that outputs the html
		    'page' => 'ctf_options_author', // matches the section name
		    'section' => 'ctf_options_author', // matches the section name
		    'option' => 'ctf_options', // matches the options name
		    'class' => 'default-text', // class for the wrapper and input field
	    ));

	    $this->create_settings_field( array(
		    'name' => 'logocolor',
		    'title' => '<label for="ctf_logocolor">Logo Color</label><code class="ctf_shortcode">logocolor
            Eg: logocolor=#333</code>', // label for the input field
		    'callback'  => 'default_color', // name of the function that outputs the html
		    'page' => 'ctf_options_author', // matches the section name
		    'section' => 'ctf_options_author', // matches the section name
		    'option' => 'ctf_options', // matches the options name
		    'class' => '',
	    ));

        add_settings_section(
            'ctf_options_text', // matches the section name
            'Tweet Text',
            array( $this, 'general_section_text' ), // callback function to explain the section
            'ctf_options_text' // matches the section name
        );

        // Tweet Text Size
        $this->create_settings_field( array(
            'name' => 'tweettextsize',
            'title' => '<label for="ctf_tweettextsize">Tweet Text Size</label><code class="ctf_shortcode">tweettextsize
            Eg: tweettextsize=16</code>', // label for the input field
            'callback'  => 'text_size', // name of the function that outputs the html
            'page' => 'ctf_options_text', // matches the section name
            'section' => 'ctf_options_text', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'default-text', // class for the wrapper and input field
        ));

        // tweet text weight
        $this->create_settings_field( array(
            'name' => 'tweettextweight',
            'title' => '<label for="ctf_tweettextweight">Tweet Text Weight</label><code class="ctf_shortcode">tweettextweight
            Eg: tweettextweight=bold</code>', // label for the input field
            'callback'  => 'text_weight', // name of the function that outputs the html
            'page' => 'ctf_options_text', // matches the section name
            'section' => 'ctf_options_text', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => '',
        ));

        // text color
        $this->create_settings_field( array(
            'name' => 'textcolor',
            'title' => '<label for="ctf_textcolor">Text Color</label><code class="ctf_shortcode">textcolor
            Eg: textcolor=#333</code>', // label for the input field
            'callback'  => 'default_color', // name of the function that outputs the html
            'page' => 'ctf_options_text', // matches the section name
            'section' => 'ctf_options_text', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => '',
        ));

	    $this->create_settings_field( array(
		    'name' => 'textlength',
		    'title' => '<label for="ctf_textlength">Text Length</label><code class="ctf_shortcode">textlength
            Eg: textlength=150</code>', // label for the input field
		    'callback'  => 'default_text', // name of the function that outputs the html
		    'page' => 'ctf_options_text', // matches the section name
		    'section' => 'ctf_options_text', // matches the section name
		    'option' => 'ctf_options', // matches the options name
		    'class' => '',
		    'default' => 280,
		    'min' => 20,
		    'max' => 280,
		    'step' => 1,
		    'example' => 'characters',
		    'type' => 'number',
		    'whatis' => 'The number of characters of text to display in the tweet text. An ellipsis link will be added to allow the user to reveal more text if desired',
	    ));

        // custom retweeted text
        $this->create_settings_field( array(
            'name' => 'retweetedtext',
            'title' => '<label for="ctf_retweetedtext">Translation for "Retweeted"</label><code class="ctf_shortcode">retweetedtext
            Eg: retweetedtext="retuiteó"</code>', // label for the input field
            'callback'  => 'default_text', // name of the function that outputs the html
            'page' => 'ctf_options_text', // matches the section name
            'section' => 'ctf_options_text', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'default-text', // class for the wrapper and input field
            'whatis' => 'This will replace the default text displayed for retweeted texts',
            'default' => 'Retweeted'// "what is this?" text
        ));

        add_settings_section(
            'ctf_options_links', // matches the section name
            'Links',
            array( $this, 'general_section_text' ), // callback function to explain the section
            'ctf_options_links' // matches the section name
        );

        // disable links
        $this->create_settings_field( array(
            'name' => 'disablelinks',
            'title' => '<label for="ctf_disablelinks">Disable Links in Tweet Text</label><code class="ctf_shortcode">disablelinks
            Eg: disablelinks=true</code>', // label for the input field
            'callback'  => 'default_checkbox', // name of the function that outputs the html
            'page' => 'ctf_options_links', // matches the section name
            'section' => 'ctf_options_links', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => '',
            'whatis' => "By default, links, hashtags, and mentions are turned into links inside the tweet text"
        ));

        // link text to twitter
        $this->create_settings_field( array(
            'name' => 'linktexttotwitter',
            'title' => '<label for="ctf_linktexttotwitter">Link Tweet Text to Twitter</label><code class="ctf_shortcode">linktexttotwitter
            Eg: linktexttotwitter=true</code>', // label for the input field
            'callback'  => 'default_checkbox', // name of the function that outputs the html
            'page' => 'ctf_options_links', // matches the section name
            'section' => 'ctf_options_links', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => '',
            'whatis' => "Clicking on the text of the tweet will link to the tweet on Twitter"
        ));

        // link text color
        $this->create_settings_field( array(
            'name' => 'linktextcolor',
            'title' => '<label for="ctf_linktextcolor">Links in Tweets Text Color</label><code class="ctf_shortcode">linktextcolor
            Eg: linktextcolor=#00e</code>', // label for the input field
            'callback'  => 'default_color', // name of the function that outputs the html
            'page' => 'ctf_options_links', // matches the section name
            'section' => 'ctf_options_links', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => '',
        ));

        add_settings_section(
            'ctf_options_quoted', // matches the section name
            'Retweet Boxes',
            array( $this, 'general_section_text' ), // callback function to explain the section
            'ctf_options_quoted' // matches the section name
        );

        // quoted author Size
        $this->create_settings_field( array(
            'name' => 'quotedauthorsize',
            'title' => '<label for="ctf_quotedauthorsize">Quoted Author Size</label><code class="ctf_shortcode">quotedauthorsize
            Eg: quotedauthorsize=16</code>', // label for the input field
            'callback'  => 'text_size', // name of the function that outputs the html
            'page' => 'ctf_options_quoted', // matches the section name
            'section' => 'ctf_options_quoted', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'default-text', // class for the wrapper and input field
        ));

        // quoted author weight
        $this->create_settings_field( array(
            'name' => 'quotedauthorweight',
            'title' => '<label for="ctf_quotedauthorweight">Quoted Author Weight</label><code class="ctf_shortcode">quotedauthorweight
            Eg: quotedauthorweight=bold</code>', // label for the input field
            'callback'  => 'text_weight', // name of the function that outputs the html
            'page' => 'ctf_options_quoted', // matches the section name
            'section' => 'ctf_options_quoted', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => '',
        ));

        add_settings_section(
            'ctf_options_actions', // matches the section name
            'Tweets Actions',
            array( $this, 'general_section_text' ), // callback function to explain the section
            'ctf_options_actions' // matches the section name
        );

        // icon Size
        $this->create_settings_field( array(
            'name' => 'iconsize',
            'title' => '<label for="ctf_iconsize">Icon Size</label><code class="ctf_shortcode">iconsize
            Eg: iconsize=16</code>', // label for the input field
            'callback'  => 'text_size', // name of the function that outputs the html
            'page' => 'ctf_options_actions', // matches the section name
            'section' => 'ctf_options_actions', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'default-text', // class for the wrapper and input field
        ));

        // icon color
        $this->create_settings_field( array(
            'name' => 'iconcolor',
            'title' => '<label for="ctf_iconcolor">Icon Color</label><code class="ctf_shortcode">iconcolor
            Eg: iconcolor=green</code>', // label for the input field
            'callback'  => 'default_color', // name of the function that outputs the html
            'page' => 'ctf_options_actions', // matches the section name
            'section' => 'ctf_options_actions', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => '',
        ));


        // view on twitter text
        $this->create_settings_field( array(
            'name' => 'twitterlinktext',
            'title' => '<label for="ctf_twitterlinktext">Custom Text for "Twitter" Link</label><code class="ctf_shortcode">twitterlinktext
            Eg: twitterlinktext="View this Tweet"</code>', // label for the input field
            'callback'  => 'default_text', // name of the function that outputs the html
            'page' => 'ctf_options_actions', // matches the section name
            'section' => 'ctf_options_actions', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'default-text', // class for the wrapper and input field
            'default' => 'Twitter'
        ));

        add_settings_section(
            'ctf_options_load', // matches the section name
            '"Load More" Button',
            array( $this, 'general_section_text' ), // callback function to explain the section
            'ctf_options_load' // matches the section name
        );

        // button background color
        $this->create_settings_field( array(
            'name' => 'buttoncolor',
            'title' => '<label for="ctf_buttoncolor">Button Background Color</label><code class="ctf_shortcode">buttoncolor
            Eg: buttoncolor=#f33</code>', // label for the input field
            'callback'  => 'default_color', // name of the function that outputs the html
            'page' => 'ctf_options_load', // matches the section name
            'section' => 'ctf_options_load', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => '',
            'whatis' => "The color of the background of the load more button"
        ));

        // button text color
        $this->create_settings_field( array(
            'name' => 'buttontextcolor',
            'title' => '<label for="ctf_buttontextcolor">Button Text Color</label><code class="ctf_shortcode">buttontextcolor
            Eg: buttontextcolor=#444</code>', // label for the input field
            'callback'  => 'default_color', // name of the function that outputs the html
            'page' => 'ctf_options_load', // matches the section name
            'section' => 'ctf_options_load', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => '',
            'whatis' => "The color of the text of the load more button"
        ));

        // button text
        $this->create_settings_field( array(
            'name' => 'buttontext',
            'title' => '<label for="ctf_buttontext">Button Text</label><code class="ctf_shortcode">buttontext
            Eg: buttontext="More"</code>', // label for the input field
            'callback'  => 'default_text', // name of the function that outputs the html
            'page' => 'ctf_options_load', // matches the section name
            'section' => 'ctf_options_load', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'default-text', // class for the wrapper and input field
            'default' => 'Load More...'
        ));

        do_action( 'ctf_admin_style_option', $this );
    }

    public function create_settings_field( $args=array() )
    {
        add_settings_field(
            $args['name'],
            $args['title'],
            array( $this, $args['callback'] ),
            $args['page'],
            $args['section'],
            $args
        );
    }

    public function default_text( $args )
    {
        $options = get_option( $args['option'] );
        $default = isset( $args['default'] ) ? $args['default'] : '';
        $option_string = ( isset( $options[ $args['name'] ] ) ) ? esc_attr( $options[ $args['name'] ] ) : $default;
        $type = ( isset( $args['type'] ) ) ? ' type="'. $args['type'].'"' : ' type="text"';
        $size = ( isset( $args['size'] ) ) ? ' size="'. $args['size'].'"' : '';
        $min = ( isset( $args['min'] ) ) ? ' min="'. $args['min'].'"' : '';
        $max = ( isset( $args['max'] ) ) ? ' max="'. $args['max'].'"' : '';
        $step = ( isset( $args['step'] ) ) ? ' step="'. $args['step'].'"' : '';
        ?>
        <input name="<?php echo $args['option'].'['.$args['name'].']'; ?>" id="ctf_<?php echo $args['name']; ?>" class="<?php echo $args['class']; ?>"<?php echo $type; ?><?php echo $size; ?><?php echo $min; ?><?php echo $max; ?><?php echo $step; ?> value="<?php echo $option_string; ?>" />
        <?php if ( isset( $args['example'] ) ) : ?>
        <span><?php echo $args['example']; ?></span>
    <?php endif; ?>
        <?php if ( isset( $args['whatis'] ) ) : ?>
        <a class="ctf-tooltip-link" href="JavaScript:void(0);"><i class="fa fa-question-circle" aria-hidden="true"></i></a>
        <p class="ctf-tooltip ctf-more-info"><?php _e( $args['whatis'], 'custom-twitter-feeds' ); ?>.</p>
    <?php endif; ?>
        <?php
    }

    public function default_select( $args )
    {
        $options = get_option( $args['option'] );
        $selected = ( isset( $options[ $args['name'] ] ) ) ? esc_attr( $options[ $args['name'] ] ) : '';
        ?>
        <select name="<?php echo $args['option'].'['.$args['name'].']'; ?>" id="ctf_<?php echo $args['name']; ?>" class="<?php echo $args['class']; ?>">
            <?php foreach ( $args['fields'] as $field ) : ?>
                <option value="<?php echo $field[0]; ?>" id="ctf-<?php echo $args['name']; ?>" class="<?php echo $args['class']; ?>"<?php if( $selected == $field[0] ) { echo ' selected'; } ?>><?php _e( $field[1], 'custom-twitter-feeds' ); ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ( isset( $args['whatis'] ) ) : ?>
        <a class="ctf-tooltip-link" href="JavaScript:void(0);"><i class="fa fa-question-circle" aria-hidden="true"></i></a>
        <p class="ctf-tooltip ctf-more-info"><?php _e( $args['whatis'], 'custom-twitter-feeds' ); ?>.</p>
    <?php endif; ?>
        <?php
    }

    public function default_color( $args )
    {
        $options = get_option( $args['option'] );
        $option_string = ( isset( $options[ $args['name'] ] ) ) ? esc_attr( $options[ $args['name'] ] ) : '';
        ?>
        <input name="<?php echo $args['option'].'['.$args['name'].']'; ?>" id="ctf_<?php echo $args['name']; ?>" value="#<?php esc_attr_e( str_replace('#', '', $option_string ) ); ?>" class="ctf-colorpicker" />
        <?php
    }

    public function default_checkbox( $args )
    {
        $options = get_option( $args['option'] );
        $option_checked = ( isset( $options[ $args['name'] ] ) ) ? $options[ $args['name'] ] : false;
        ?>
        <input name="<?php echo $args['option'].'['.$args['name'].']'; ?>" id="ctf_<?php echo $args['name']; ?>" type="checkbox" <?php if ( $option_checked ) echo "checked"; ?> />
        <?php if ( isset( $args['whatis'] ) ) : ?>
        <a class="ctf-tooltip-link" href="JavaScript:void(0);"><i class="fa fa-question-circle" aria-hidden="true"></i></a>
        <p class="ctf-tooltip ctf-more-info"><?php _e( $args['whatis'], 'custom-twitter-feeds' ); ?>.</p>
    <?php endif; ?>
        <?php
    }

    public function reverse_checkbox( $args )
    {
        $options = get_option( $args['option'] );
        $option_checked = isset( $options[ $args['name'] ] ) ? $options[ $args['name'] ] : true;
        ?>
        <input name="<?php echo $args['option'].'['.$args['name'].']'; ?>" id="ctf_<?php echo $args['name']; ?>" type="checkbox" <?php if ( $option_checked ) echo "checked"; ?> />
        <?php if ( isset( $args['whatis'] ) ) : ?>
        <a class="ctf-tooltip-link" href="JavaScript:void(0);"><i class="fa fa-question-circle" aria-hidden="true"></i></a>
        <p class="ctf-tooltip ctf-more-info"><?php _e( $args['whatis'], 'custom-twitter-feeds' ); ?>.</p>
    <?php endif; ?>
        <?php
    }

    public function access_token_secret( $args )
    {
        $options = get_option( $args['option'] );
        $default = isset( $args['default'] ) ? $args['default'] : '';
        $option_string = ( isset( $options[ $args['name'] ] ) ) ? esc_attr( $options[ $args['name'] ] ) : $default;
        $option_checked = ( isset( $options['use_own_consumer'] ) ) ? $options['use_own_consumer'] : false;
        $type = ( isset( $args['type'] ) ) ? ' type="'. $args['type'].'"' : ' type="text"';
        $size = ( isset( $args['size'] ) ) ? ' size="'. $args['size'].'"' : '';
        $min = ( isset( $args['min'] ) ) ? ' min="'. $args['min'].'"' : '';
        $max = ( isset( $args['max'] ) ) ? ' max="'. $args['max'].'"' : '';
        $step = ( isset( $args['step'] ) ) ? ' step="'. $args['step'].'"' : '';
        ?>
        <input name="<?php echo $args['option'].'['.$args['name'].']'; ?>" id="ctf_<?php echo $args['name']; ?>" class="<?php echo $args['class']; ?>"<?php echo $type; ?><?php echo $size; ?><?php echo $min; ?><?php echo $max; ?><?php echo $step; ?> value="<?php echo $option_string; ?>" />
        <?php if ( isset( $args['example'] ) ) : ?>
        <span><?php echo $args['example']; ?></span>
    <?php endif; ?>

        <?php if ( isset( $args['whatis'] ) ) : ?>
        <a class="ctf-tooltip-link" href="JavaScript:void(0);"><i class="fa fa-question-circle" aria-hidden="true"></i></a>
        <p class="ctf-tooltip ctf-more-info"><?php _e( $args['whatis'], 'custom-twitter-feeds' ); ?>.</p>
    <?php endif; ?>

        <?php
    }

    public function feed_settings_radio( $args )
    {
        $options = get_option( $args['option'] );
        $option_checked = ( ( ! isset( $options[ 'type' ] ) && $args['name'] == 'usertimeline' ) || ( isset( $options[ 'type' ] ) && $options[ 'type' ] == $args['name'] ) ) ? true : false;
        $show_replies = ( isset( $options[ $args['name'].'_includereplies' ] ) ) ? $options[ $args['name'].'_includereplies' ] : false;
        $option_string = ( isset( $options[ $args['name'].'_text' ] ) ) ? esc_attr( $options[ $args['name'].'_text' ] ) : '';
        ?>
        <input type="radio" name="<?php echo $args['option'].'[type]'; ?>" class="ctf-feed-settings-radio" id="ctf_<?php echo $args['name'].'_radio'; ?>" value="<?php echo $args['name']; ?>" <?php if ( $option_checked ) echo "checked"; ?> />
        <label class="ctf-radio-label" for="ctf_<?php echo $args['name'].'_radio'; ?>"><?php _e( $args['label'], 'custom-twitter-feeds' ); ?></label>
        <?php if ( $args['has_input'] ) : ?>
        <input name="<?php echo $args['option'].'['.$args['name'].'_text'.']'; ?>" id="ctf_<?php echo $args['name'].'_text'; ?>" type="text" value="<?php esc_attr_e( $option_string ); ?>" size="25" />
    <?php endif; ?>
        <?php if ( isset( $args['whatis'] ) ) : ?>
        <a class="ctf-tooltip-link" href="JavaScript:void(0);"><i class="fa fa-question-circle" aria-hidden="true"></i></a>
        <p class="ctf-tooltip ctf-more-info"><?php _e( $args['whatis'], 'custom-twitter-feeds' ); ?>.</p>
    <?php endif; ?>
        <?php if ( $args['has_replies'] ) : ?>
        <span class="ctf_include_replies_toggle ctf_pro">
            <input name="<?php echo $args['option'].'['.$args['name'].'_includereplies]'; ?>" id="ctf_include_replies" type="checkbox" <?php if ( $show_replies ) echo "checked"; ?> />
            <label class="ctf-radio-label" for="ctf_include_replies"><?php _e( 'Include replies', 'custom-twitter-feeds' ); ?></label>
            <?php do_action( 'ctf_admin_upgrade_note' ); ?>
        </span>
    <?php endif; ?>
        <?php
        do_action( 'ctf_admin_feed_settings_radio_extra', $args );
    }

    public function feed_settings_radio_search( $args )
    {
        $options = get_option( $args['option'] );
        $option_checked = ( ( ! isset( $options[ 'type' ] ) && $args['name'] == 'usertimeline' ) || ( isset( $options[ 'type' ] ) && $options[ 'type' ] == $args['name'] ) ) ? true : false;
        $option_string = ( isset( $options[ $args['name'].'_text' ] ) ) ? esc_attr( $options[ $args['name'].'_text' ] ) : '';
        ?>
        <input type="radio" name="<?php echo $args['option'].'[type]'; ?>" class="ctf-feed-settings-radio" id="ctf_<?php echo $args['name'].'_radio'; ?>" value="<?php echo $args['name']; ?>" <?php if ( $option_checked ) echo "checked"; ?> />
        <label class="ctf-radio-label" for="ctf_<?php echo $args['name'].'_radio'; ?>"><?php echo $args['label']; ?></label>
        <?php if ( $args['has_input'] ) : ?>
        <input name="<?php echo $args['option'].'['.$args['name'].'_text'.']'; ?>" id="ctf_<?php echo $args['name'].'_text'; ?>" type="text" value="<?php esc_attr_e( $option_string ); ?>" size="25" />
    <?php endif; ?>
	    <?php if ( isset( $args['note_after_input'] ) ) : ?>
        <span class="ctf-note-after-input"><?php echo esc_attr( $args['note_after_input'] ); ?></span>
    <?php endif; ?>
        <?php if ( isset( $args['whatis'] ) ) : ?>
        <a class="ctf-tooltip-link" href="JavaScript:void(0);"><i class="fa fa-question-circle" aria-hidden="true"></i></a>
        <p class="ctf-tooltip ctf-more-info"><?php _e( $args['whatis'], 'custom-twitter-feeds' ); ?>.</p>
    <?php endif; ?>
        <?php
        do_action( 'ctf_admin_feed_settings_search_extra' );
    }

    public function width_and_height_settings( $args )
    {
        $options = get_option( $args['option'] );
        $default = isset( $args['default'] ) ? $args['default'] : '';
        $option_string = ( isset( $options[ $args['name'] ] ) ) ? esc_attr( $options[ $args['name'] ] ) : $default;
        $selected = ( isset( $options[ $args['name'] . '_unit' ] ) ) ? esc_attr( $options[ $args['name'] . '_unit' ] ) : $args['default_unit'];
        ?>
        <input name="<?php echo $args['option'].'['.$args['name'].']'; ?>" id="ctf_<?php echo $args['name']; ?>" class="<?php echo $args['class']; ?>" type="number" value="<?php echo $option_string; ?>" />
        <select name="<?php echo $args['option'].'['.$args['name'].'_unit]'; ?>" id="ctf_<?php echo $args['name'].'_unit'; ?>">
            <option value="px" <?php if ( $selected == "px" ) echo 'selected="selected"' ?> >px</option>
            <option value="%" <?php if ( $selected == "%" ) echo 'selected="selected"' ?> >%</option>
        </select>

        <?php if ( $args['name'] == 'width' ) :
        $checked = ( isset( $options[ $args['name'] . '_mobile_no_fixed' ] ) ) ? esc_attr( $options[ $args['name'] . '_mobile_no_fixed' ] ) : false; ?>
        <div id="ctf_width_options">
            <input name="<?php echo $args['option'].'[width_mobile_no_fixed]'; ?>" type="checkbox" id="ctf_width_mobile_no_fixed" <?php if ( $checked == true ) { echo "checked"; }?> /><label for="ctf_width_mobile_no_fixed"><?php _e('Set to be 100% width on mobile?', 'custom-twitter-feeds'); ?></label>
            <a class="ctf-tooltip-link" href="JavaScript:void(0);"><?php _e('What does this mean?', 'custom-facebook-feed'); ?></a>
            <p class="ctf-tooltip ctf-more-info"><?php _e("If you set a width on the feed then this will be used on mobile as well as desktop. Check this setting to set the feed width to be 100% on mobile so that it is responsive.", 'custom-twitter-feeds'); ?></p>
        </div>
    <?php endif; ?>
        <?php
    }

    public function cache_time( $args )
    {
	    $min_cache_time = 3600;
	    $options = get_option( $args['option'] );
	    $unrestricted = isset( $options['have_own_tokens'] ) && $options['have_own_tokens'];
	    if ( $unrestricted ) {
		    $min_cache_time = 1;
	    }
	    $default = 3;
	    $option_string = ( isset( $options[ $args['name'] ] ) ) ? esc_attr( $options[ $args['name'] ] ) : $default;

	    $selected = ( isset( $options[ $args['name'] . '_unit' ] ) ) ? esc_attr( $options[ $args['name'] . '_unit' ] ) : '3600';
	    $actual_time = (int)$option_string * (int)$selected;
	    $show_notice = false;
	    if ( get_transient( 'ct_feed_forced_cache_time_raise' ) ) {
	        $show_notice = true;
        }
	    if ( $actual_time < $min_cache_time ) {
	        set_transient( 'ct_feed_forced_cache_time_raise', 'true', 60 * 60 * 48 );
		    $show_notice = true;
		    $option_string = max( 1, $min_cache_time / 3600 );
            $selected = "3600";
	    } else {
		    $selected = ( isset( $options[ $args['name'] . '_unit' ] ) ) ? esc_attr( $options[ $args['name'] . '_unit' ] ) : '3600';
	    }

        ?>
        <input name="<?php echo $args['option'].'['.$args['name'].']'; ?>" id="ctf_<?php echo $args['name']; ?>" class="<?php echo $args['class']; ?>" type="number" value="<?php echo $option_string; ?>" />
        <select name="<?php echo $args['option'].'['.$args['name'].'_unit]'; ?>">
            <?php if ( $unrestricted ) : ?>
                <option value="60" <?php if ( $selected == "60" ) echo 'selected="selected"' ?> ><?php esc_attr_e( 'Minutes' ); ?></option>
            <?php endif; ?>
            <option value="3600" <?php if ( $selected == "3600" ) echo 'selected="selected"' ?> ><?php esc_attr_e( 'Hours' ); ?></option>
            <option value="86400" <?php if ( $selected == "86400" ) echo 'selected="selected"' ?> ><?php esc_attr_e( 'Days' ); ?></option>
        </select>&nbsp;
        <input id="ctf-clear-cache" class="button-secondary" style="margin-top: 1px;" type="submit" value="<?php esc_attr_e( 'Clear Twitter Cache' ); ?>" />
        <a class="ctf-tooltip-link" href="JavaScript:void(0);"><i class="fa fa-question-circle" aria-hidden="true"></i></a>
        <p class="ctf-tooltip ctf-more-info"><?php _e( 'Clicking this button will clear all cached data for your Twitter feeds', 'custom-twitter-feeds' ); ?>.</p>
        <?php if ( $show_notice ) : ?>
            <p class="ctf-more-info"><?php _e( 'Due to changes in Twitter\'s API usage policy, the minimum caching time for the plugin is 1 hour. To check for Tweets more often either create your own Twitter Developer app or upgrade to the Pro version.', 'custom-twitter-feeds' ); ?></p>
        <?php endif; ?>
        <?php
    }

    public function customize_date_format( $args )
    {
        $options = get_option( $args['option'] );
        $ctf_date_formatting = ( isset( $options[ $args['name'] ] ) ) ? esc_attr( $options[ $args['name'] ] ) : '';
        $original = strtotime( '2016-02-25T17:30:00+0000' );
        ?>
        <select name="<?php echo $args['option'].'['.$args['name'].']'; ?>" id="ctf_<?php echo $args['name']; ?>">
            <option value="1" <?php if ( $ctf_date_formatting == "1" ) echo 'selected="selected"'; ?> ><?php _e( '2h / 25 Feb' ); ?></option>
            <option value="2" <?php if ( $ctf_date_formatting == "2" ) echo 'selected="selected"'; ?> ><?php echo date( 'F j', $original ); ?></option>
            <option value="3" <?php if ( $ctf_date_formatting == "3" ) echo 'selected="selected"'; ?> ><?php echo date( 'F j, Y', $original ); ?></option>
            <option value="4" <?php if ( $ctf_date_formatting == "4" ) echo 'selected="selected"'; ?> ><?php echo date( 'm.d', $original ); ?></option>
            <option value="5" <?php if ( $ctf_date_formatting == "5" ) echo 'selected="selected"'; ?> ><?php echo date( 'm.d.y', $original ); ?></option>
        </select>
        <?php if ( isset( $args['whatis'] ) ) : ?>
        <a class="ctf-tooltip-link" href="JavaScript:void(0);"><i class="fa fa-question-circle" aria-hidden="true"></i></a>
        <p class="ctf-tooltip ctf-more-info"><?php _e( $args['whatis'], 'custom-twitter-feeds' ); ?>.</p>
    <?php endif; ?>
        <?php
    }

    public function customize_custom_date_format( $args )
    {
        $options = get_option( $args['option'] );
        $option_string = ( isset( $options[ $args['name'] ] ) ) ? esc_attr( $options[ $args['name'] ] ) : '';
        ?>
        <input name="<?php echo $args['option'].'['.$args['name'].']'; ?>" id="ctf_<?php echo $args['name']; ?>" type="text" value="<?php esc_attr_e( $option_string ); ?>" size="10" placeholder="Eg. F jS, Y" />
        <a href="https://smashballoon.com/custom-twitter-feeds/docs/date/" class="cff-external-link" target="_blank"><?php _e( 'Examples' , 'custom-twitter-feeds'); ?></a>
        <?php
    }

    public function customize_custom_time_translations( $args )
    {
        $options = get_option( $args['option'] );
        $option_m = ( isset( $options['mtime'] ) ) ? esc_attr( $options['mtime'] ) : '';
        $option_h = ( isset( $options['htime'] ) ) ? esc_attr( $options['htime'] ) : '';
        $option_now = ( isset( $options['nowtime'] ) ) ? esc_attr( $options['nowtime'] ) : '';

        ?>
        <input name="<?php echo $args['option'].'[mtime]'; ?>" id="ctf_translate_minute" type="text" value="<?php esc_attr_e( $option_m ); ?>" size="5" />
        <label for=ctf_translate_minute"><?php _e( 'translation for "m" (minutes)', 'custom-twitter-feeds' ); ?></label><br>
        <input name="<?php echo $args['option'].'[htime]'; ?>" id="ctf_translate_hour" type="text" value="<?php esc_attr_e( $option_h ); ?>" size="5" />
        <label for=ctf_translate_hour"><?php _e( 'translation for "h" (hours)', 'custom-twitter-feeds' ); ?></label><br>
        <input name="<?php echo $args['option'].'[nowtime]'; ?>" id="ctf_translate_now" type="text" value="<?php esc_attr_e( $option_now ); ?>" size="5" />
        <label for=ctf_translate_now"><?php _e( 'translation for "now"', 'custom-twitter-feeds' ); ?></label><br>
        <?php
    }

    public function include_exclude_checkbox( $args )
    {
        $options = get_option( $args['option'] );
        foreach ( $args['fields'] as $field ) {
            $option_checked = isset(  $options[$field[0]] ) ?  $options[$field[0]] : true;
            ?>
            <input name="<?php echo $args['option'] . '[' . $field[0] . ']'; ?>"
                   id="ctf_<?php echo $field[0]; ?>" type="checkbox"
                <?php if ( $option_checked ) {
                    echo "checked";
                } ?> />
            <label for=ctf_<?php echo $field[0]; ?>"><?php _e( $field[1], 'custom-twitter-feeds' ); ?></label><br>
            <?php
        } // end foreach
    }

    public function text_size( $args )
    {
        $options = get_option( $args['option'] );
        $ctf_text_size = ( isset( $options[ $args['name'] ] ) ) ? esc_attr( $options[ $args['name'] ] ) : '';
        ?>
        <select name="<?php echo $args['option'].'['.$args['name'].']'; ?>" id="ctf_<?php echo $args['name']; ?>">
            <option value="inherit" <?php if ( $ctf_text_size == "inherit" ) echo 'selected="selected"' ?> >Inherit</option>
            <option value="10" <?php if ( $ctf_text_size == "10" ) echo 'selected="selected"' ?> >10px</option>
            <option value="11" <?php if ( $ctf_text_size == "11" ) echo 'selected="selected"' ?> >11px</option>
            <option value="12" <?php if ( $ctf_text_size == "12" ) echo 'selected="selected"' ?> >12px</option>
            <option value="13" <?php if ( $ctf_text_size == "13" ) echo 'selected="selected"' ?> >13px</option>
            <option value="14" <?php if ( $ctf_text_size == "14" ) echo 'selected="selected"' ?> >14px</option>
            <option value="16" <?php if ( $ctf_text_size == "16" ) echo 'selected="selected"' ?> >16px</option>
            <option value="18" <?php if ( $ctf_text_size == "18" ) echo 'selected="selected"' ?> >18px</option>
            <option value="20" <?php if ( $ctf_text_size == "20" ) echo 'selected="selected"' ?> >20px</option>
            <option value="24" <?php if ( $ctf_text_size == "24" ) echo 'selected="selected"' ?> >24px</option>
            <option value="28" <?php if ( $ctf_text_size == "28" ) echo 'selected="selected"' ?> >28px</option>
            <option value="32" <?php if ( $ctf_text_size == "32" ) echo 'selected="selected"' ?> >32px</option>
            <option value="36" <?php if ( $ctf_text_size == "36" ) echo 'selected="selected"' ?> >36px</option>
            <option value="42" <?php if ( $ctf_text_size == "42" ) echo 'selected="selected"' ?> >42px</option>
            <option value="48" <?php if ( $ctf_text_size == "48" ) echo 'selected="selected"' ?> >48px</option>
            <option value="54" <?php if ( $ctf_text_size == "54" ) echo 'selected="selected"' ?> >54px</option>
            <option value="60" <?php if ( $ctf_text_size == "60" ) echo 'selected="selected"' ?> >60px</option>
        </select>
        <?php if ( isset( $args['whatis'] ) ) : ?>
        <a class="ctf-tooltip-link" href="JavaScript:void(0);"><i class="fa fa-question-circle" aria-hidden="true"></i></a>
        <p class="ctf-tooltip ctf-more-info"><?php _e( $args['whatis'], 'custom-twitter-feeds' ); ?>.</p>
    <?php endif; ?>
        <?php
    }

    public function text_weight( $args )
    {
        $options = get_option( $args['option'] );
        $ctf_text_weight = ( isset( $options[ $args['name'] ] ) ) ? esc_attr( $options[ $args['name'] ] ) : '';
        ?>
        <select name="<?php echo $args['option'].'['.$args['name'].']'; ?>" id="ctf_<?php echo $args['name']; ?>">
            <option value="inherit" <?php if ( $ctf_text_weight == "inherit" ) echo 'selected="selected"'; ?> >Inherit</option>
            <option value="normal" <?php if ( $ctf_text_weight == "normal" ) echo 'selected="selected"'; ?> >Normal</option>
            <option value="bold" <?php if ( $ctf_text_weight == "bold" ) echo 'selected="selected"'; ?> >Bold</option>
        </select>
        <?php if ( isset( $args['whatis'] ) ) : ?>
        <a class="ctf-tooltip-link" href="JavaScript:void(0);"><i class="fa fa-question-circle" aria-hidden="true"></i></a>
        <p class="ctf-tooltip ctf-more-info"><?php _e( $args['whatis'], 'custom-twitter-feeds' ); ?>.</p>
    <?php endif; ?>
        <?php
    }

    public function feed_settings_timezone( $args )
    {
        $options = get_option( $args['option'] );
        $ctf_timezone = ( isset( $options[ $args['name'] ] ) ) ? esc_attr( $options[ $args['name'] ] ) : '';
        ?>
        <select name="<?php echo $args['option'].'['.$args['name'].']'; ?>" id="ctf_<?php echo $args['name']; ?>" style="width: 300px;">
            <option value="default" <?php if( $ctf_timezone == "default" ) echo 'selected="selected"' ?> ><?php _e( 'default from Twitter' ) ?></option>
            <option value="Pacific/Midway" <?php if( $ctf_timezone == "Pacific/Midway" ) echo 'selected="selected"' ?> ><?php _e( '(GMT11:00) Midway Island, Samoa' ) ?></option>
            <option value="America/Adak" <?php if( $ctf_timezone == "America/Adak" ) echo 'selected="selected"' ?> ><?php _e( '(GMT10:00) HawaiiAleutian' ) ?></option>
            <option value="Etc/GMT+10" <?php if( $ctf_timezone == "Etc/GMT+10" ) echo 'selected="selected"' ?> ><?php _e( '(GMT10:00) Hawaii' ) ?></option>
            <option value="Pacific/Marquesas" <?php if( $ctf_timezone == "Pacific/Marquesas" ) echo 'selected="selected"' ?> ><?php _e( '(GMT09:30) Marquesas Islands' ) ?></option>
            <option value="Pacific/Gambier" <?php if( $ctf_timezone == "Pacific/Gambier" ) echo 'selected="selected"' ?> ><?php _e( '(GMT09:00) Gambier Islands' ) ?></option>
            <option value="America/Anchorage" <?php if( $ctf_timezone == "America/Anchorage" ) echo 'selected="selected"' ?> ><?php _e( '(GMT09:00) Alaska' ) ?></option>
            <option value="America/Ensenada" <?php if( $ctf_timezone == "America/Ensenada" ) echo 'selected="selected"' ?> ><?php _e( '(GMT08:00) Tijuana, Baja California' ) ?></option>
            <option value="Etc/GMT+8" <?php if( $ctf_timezone == "Etc/GMT+8" ) echo 'selected="selected"' ?> ><?php _e( '(GMT08:00) Pitcairn Islands' ) ?></option>
            <option value="America/Los_Angeles" <?php if( $ctf_timezone == "America/Los_Angeles" ) echo 'selected="selected"' ?> ><?php _e( '(GMT08:00) Pacific Time (US & Canada)' ) ?></option>
            <option value="America/Denver" <?php if( $ctf_timezone == "America/Denver" ) echo 'selected="selected"' ?> ><?php _e( '(GMT07:00) Mountain Time (US & Canada)' ) ?></option>
            <option value="America/Chihuahua" <?php if( $ctf_timezone == "America/Chihuahua" ) echo 'selected="selected"' ?> ><?php _e( '(GMT07:00) Chihuahua, La Paz, Mazatlan' ) ?></option>
            <option value="America/Dawson_Creek" <?php if( $ctf_timezone == "America/Dawson_Creek" ) echo 'selected="selected"' ?> ><?php _e( '(GMT07:00) Arizona' ) ?></option>
            <option value="America/Belize" <?php if( $ctf_timezone == "America/Belize" ) echo 'selected="selected"' ?> ><?php _e( '(GMT06:00) Saskatchewan, Central America' ) ?></option>
            <option value="America/Cancun" <?php if( $ctf_timezone == "America/Cancun" ) echo 'selected="selected"' ?> ><?php _e( '(GMT06:00) Guadalajara, Mexico City, Monterrey' ) ?></option>
            <option value="Chile/EasterIsland" <?php if( $ctf_timezone == "Chile/EasterIsland" ) echo 'selected="selected"' ?> ><?php _e( '(GMT06:00) Easter Island' ) ?></option>
            <option value="America/Chicago" <?php if( $ctf_timezone == "America/Chicago" ) echo 'selected="selected"' ?> ><?php _e( '(GMT06:00) Central Time (US & Canada)' ) ?></option>
            <option value="America/New_York" <?php if( $ctf_timezone == "America/New_York" ) echo 'selected="selected"' ?> ><?php _e( '(GMT05:00) Eastern Time (US & Canada)' ) ?></option>
            <option value="America/Havana" <?php if( $ctf_timezone == "America/Havana" ) echo 'selected="selected"' ?> ><?php _e( '(GMT05:00) Cuba' ) ?></option>
            <option value="America/Bogota" <?php if( $ctf_timezone == "America/Bogota" ) echo 'selected="selected"' ?> ><?php _e( '(GMT05:00) Bogota, Lima, Quito, Rio Branco' ) ?></option>
            <option value="America/Caracas" <?php if( $ctf_timezone == "America/Caracas" ) echo 'selected="selected"' ?> ><?php _e( '(GMT04:30) Caracas' ) ?></option>
            <option value="America/Santiago" <?php if( $ctf_timezone == "America/Santiago" ) echo 'selected="selected"' ?> ><?php _e( '(GMT04:00) Santiago' ) ?></option>
            <option value="America/La_Paz" <?php if( $ctf_timezone == "America/La_Paz" ) echo 'selected="selected"' ?> ><?php _e( '(GMT04:00) La Paz' ) ?></option>
            <option value="Atlantic/Stanley" <?php if( $ctf_timezone == "Atlantic/Stanley" ) echo 'selected="selected"' ?> ><?php _e( '(GMT04:00) Faukland Islands' ) ?></option>
            <option value="America/Campo_Grande" <?php if( $ctf_timezone == "America/Campo_Grande" ) echo 'selected="selected"' ?> ><?php _e( '(GMT04:00) Brazil' ) ?></option>
            <option value="America/Goose_Bay" <?php if( $ctf_timezone == "America/Goose_Bay" ) echo 'selected="selected"' ?> ><?php _e( '(GMT04:00) Atlantic Time (Goose Bay)' ) ?></option>
            <option value="America/Glace_Bay" <?php if( $ctf_timezone == "America/Glace_Bay" ) echo 'selected="selected"' ?> ><?php _e( '(GMT04:00) Atlantic Time (Canada)' ) ?></option>
            <option value="America/St_Johns" <?php if( $ctf_timezone == "America/St_Johns" ) echo 'selected="selected"' ?> ><?php _e( '(GMT03:30) Newfoundland' ) ?></option>
            <option value="America/Araguaina" <?php if( $ctf_timezone == "America/Araguaina" ) echo 'selected="selected"' ?> ><?php _e( '(GMT03:00) UTC3' ) ?></option>
            <option value="America/Montevideo" <?php if( $ctf_timezone == "America/Montevideo" ) echo 'selected="selected"' ?> ><?php _e( '(GMT03:00) Montevideo' ) ?></option>
            <option value="America/Miquelon" <?php if( $ctf_timezone == "America/Miquelon" ) echo 'selected="selected"' ?> ><?php _e( '(GMT03:00) Miquelon, St. Pierre' ) ?></option>
            <option value="America/Godthab" <?php if( $ctf_timezone == "America/Godthab" ) echo 'selected="selected"' ?> ><?php _e( '(GMT03:00) Greenland' ) ?></option>
            <option value="America/Argentina/Buenos_Aires" <?php if( $ctf_timezone == "America/Argentina/Buenos_Aires" ) echo 'selected="selected"' ?> ><?php _e( '(GMT03:00) Buenos Aires' ) ?></option>
            <option value="America/Sao_Paulo" <?php if( $ctf_timezone == "America/Sao_Paulo" ) echo 'selected="selected"' ?> ><?php _e( '(GMT03:00) Brasilia' ) ?></option>
            <option value="America/Noronha" <?php if( $ctf_timezone == "America/Noronha" ) echo 'selected="selected"' ?> ><?php _e( '(GMT02:00) MidAtlantic' ) ?></option>
            <option value="Atlantic/Cape_Verde" <?php if( $ctf_timezone == "Atlantic/Cape_Verde" ) echo 'selected="selected"' ?> ><?php _e( '(GMT01:00) Cape Verde Is.' ) ?></option>
            <option value="Atlantic/Azores" <?php if( $ctf_timezone == "Atlantic/Azores" ) echo 'selected="selected"' ?> ><?php _e( '(GMT01:00) Azores' ) ?></option>
            <option value="Europe/Belfast" <?php if( $ctf_timezone == "Europe/Belfast" ) echo 'selected="selected"' ?> ><?php _e( '(GMT) Greenwich Mean Time : Belfast' ) ?></option>
            <option value="Europe/Dublin" <?php if( $ctf_timezone == "Europe/Dublin" ) echo 'selected="selected"' ?> ><?php _e( '(GMT) Greenwich Mean Time : Dublin' ) ?></option>
            <option value="Europe/Lisbon" <?php if( $ctf_timezone == "Europe/Lisbon" ) echo 'selected="selected"' ?> ><?php _e( '(GMT) Greenwich Mean Time : Lisbon' ) ?></option>
            <option value="Europe/London" <?php if( $ctf_timezone == "Europe/London" ) echo 'selected="selected"' ?> ><?php _e( '(GMT) Greenwich Mean Time : London' ) ?></option>
            <option value="Africa/Abidjan" <?php if( $ctf_timezone == "Africa/Abidjan" ) echo 'selected="selected"' ?> ><?php _e( '(GMT) Monrovia, Reykjavik' ) ?></option>
            <option value="Europe/Amsterdam" <?php if( $ctf_timezone == "Europe/Amsterdam" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+01:00) Amsterdam, Berlin, Bern, Rome, Stockholm, Vienna' ) ?></option>
            <option value="Europe/Belgrade" <?php if( $ctf_timezone == "Europe/Belgrade" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+01:00) Belgrade, Bratislava, Budapest, Ljubljana, Prague' ) ?></option>
            <option value="Europe/Brussels" <?php if( $ctf_timezone == "Europe/Brussels" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+01:00) Brussels, Copenhagen, Madrid, Paris' ) ?></option>
            <option value="Africa/Algiers" <?php if( $ctf_timezone == "Africa/Algiers" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+01:00) West Central Africa' ) ?></option>
            <option value="Africa/Windhoek" <?php if( $ctf_timezone == "Africa/Windhoek" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+01:00) Windhoek' ) ?></option>
            <option value="Asia/Beirut" <?php if( $ctf_timezone == "Asia/Beirut" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+02:00) Beirut' ) ?></option>
            <option value="Africa/Cairo" <?php if( $ctf_timezone == "Africa/Cairo" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+02:00) Cairo' ) ?></option>
            <option value="Asia/Gaza" <?php if( $ctf_timezone == "Asia/Gaza" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+02:00) Gaza' ) ?></option>
            <option value="Africa/Blantyre" <?php if( $ctf_timezone == "Africa/Blantyre" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+02:00) Harare, Pretoria' ) ?></option>
            <option value="Asia/Jerusalem" <?php if( $ctf_timezone == "Asia/Jerusalem" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+02:00) Jerusalem' ) ?></option>
            <option value="Europe/Minsk" <?php if( $ctf_timezone == "Europe/Minsk" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+02:00) Minsk' ) ?></option>
            <option value="Asia/Damascus" <?php if( $ctf_timezone == "Asia/Damascus" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+02:00) Syria' ) ?></option>
            <option value="Europe/Moscow" <?php if( $ctf_timezone == "Europe/Moscow" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+03:00) Moscow, St. Petersburg, Volgograd' ) ?></option>
            <option value="Africa/Addis_Ababa" <?php if( $ctf_timezone == "Africa/Addis_Ababa" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+03:00) Nairobi' ) ?></option>
            <option value="Asia/Tehran" <?php if( $ctf_timezone == "Asia/Tehran" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+03:30) Tehran' ) ?></option>
            <option value="Asia/Dubai" <?php if( $ctf_timezone == "Asia/Dubai" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+04:00) Abu Dhabi, Muscat' ) ?></option>
            <option value="Asia/Yerevan" <?php if( $ctf_timezone == "Asia/Yerevan" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+04:00) Yerevan' ) ?></option>
            <option value="Asia/Kabul" <?php if( $ctf_timezone == "Asia/Kabul" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+04:30) Kabul' ) ?></option>
            <option value="Asia/Yekaterinburg" <?php if( $ctf_timezone == "Asia/Yekaterinburg" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+05:00) Ekaterinburg' ) ?></option>
            <option value="Asia/Tashkent" <?php if( $ctf_timezone == "Asia/Tashkent" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+05:00) Tashkent' ) ?></option>
            <option value="Asia/Kolkata" <?php if( $ctf_timezone == "Asia/Kolkata" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+05:30) Chennai, Kolkata, Mumbai, New Delhi' ) ?></option>
            <option value="Asia/Katmandu" <?php if( $ctf_timezone == "Asia/Katmandu" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+05:45) Kathmandu' ) ?></option>
            <option value="Asia/Dhaka" <?php if( $ctf_timezone == "Asia/Dhaka" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+06:00) Astana, Dhaka' ) ?></option>
            <option value="Asia/Novosibirsk" <?php if( $ctf_timezone == "Asia/Novosibirsk" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+06:00) Novosibirsk' ) ?></option>
            <option value="Asia/Rangoon" <?php if( $ctf_timezone == "Asia/Rangoon" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+06:30) Yangon (Rangoon)' ) ?></option>
            <option value="Asia/Bangkok" <?php if( $ctf_timezone == "Asia/Bangkok" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+07:00) Bangkok, Hanoi, Jakarta' ) ?></option>
            <option value="Asia/Krasnoyarsk" <?php if( $ctf_timezone == "Asia/Krasnoyarsk" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+07:00) Krasnoyarsk' ) ?></option>
            <option value="Asia/Hong_Kong" <?php if( $ctf_timezone == "Asia/Hong_Kong" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+08:00) Beijing, Chongqing, Hong Kong, Urumqi' ) ?></option>
            <option value="Asia/Irkutsk" <?php if( $ctf_timezone == "Asia/Irkutsk" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+08:00) Irkutsk, Ulaan Bataar' ) ?></option>
            <option value="Australia/Perth" <?php if( $ctf_timezone == "Australia/Perth" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+08:00) Perth' ) ?></option>
            <option value="Australia/Eucla" <?php if( $ctf_timezone == "Australia/Eucla" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+08:45) Eucla' ) ?></option>
            <option value="Asia/Tokyo" <?php if( $ctf_timezone == "Asia/Tokyo" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+09:00) Osaka, Sapporo, Tokyo' ) ?></option>
            <option value="Asia/Seoul" <?php if( $ctf_timezone == "Asia/Seoul" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+09:00) Seoul' ) ?></option>
            <option value="Asia/Yakutsk" <?php if( $ctf_timezone == "Asia/Yakutsk" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+09:00) Yakutsk' ) ?></option>
            <option value="Australia/Adelaide" <?php if( $ctf_timezone == "Australia/Adelaide" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+09:30) Adelaide' ) ?></option>
            <option value="Australia/Darwin" <?php if( $ctf_timezone == "Australia/Darwin" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+09:30) Darwin' ) ?></option>
            <option value="Australia/Brisbane" <?php if( $ctf_timezone == "Australia/Brisbane" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+10:00) Brisbane' ) ?></option>
            <option value="Australia/Hobart" <?php if( $ctf_timezone == "Australia/Hobart" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+10:00) Sydney' ) ?></option>
            <option value="Asia/Vladivostok" <?php if( $ctf_timezone == "Asia/Vladivostok" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+10:00) Vladivostok' ) ?></option>
            <option value="Australia/Lord_Howe" <?php if( $ctf_timezone == "Australia/Lord_Howe" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+10:30) Lord Howe Island' ) ?></option>
            <option value="Etc/GMT11" <?php if( $ctf_timezone == "Etc/GMT11" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+11:00) Solomon Is., New Caledonia' ) ?></option>
            <option value="Asia/Magadan" <?php if( $ctf_timezone == "Asia/Magadan" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+11:00) Magadan' ) ?></option>
            <option value="Pacific/Norfolk" <?php if( $ctf_timezone == "Pacific/Norfolk" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+11:30) Norfolk Island' ) ?></option>
            <option value="Asia/Anadyr" <?php if( $ctf_timezone == "Asia/Anadyr" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+12:00) Anadyr, Kamchatka' ) ?></option>
            <option value="Pacific/Auckland" <?php if( $ctf_timezone == "Pacific/Auckland" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+12:00) Auckland, Wellington' ) ?></option>
            <option value="Etc/GMT12" <?php if( $ctf_timezone == "Etc/GMT12" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+12:00) Fiji, Kamchatka, Marshall Is.' ) ?></option>
            <option value="Pacific/Chatham" <?php if( $ctf_timezone == "Pacific/Chatham" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+12:45) Chatham Islands' ) ?></option>
            <option value="Pacific/Tongatapu" <?php if( $ctf_timezone == "Pacific/Tongatapu" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+13:00) Nuku\'alofa' ) ?></option>
            <option value="Pacific/Kiritimati" <?php if( $ctf_timezone == "Pacific/Kiritimati" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+14:00) Kiritimati' ) ?></option>
        </select>
        <?php if ( isset( $args['whatis'] ) ) : ?>
        <a class="ctf-tooltip-link" href="JavaScript:void(0);"><i class="fa fa-question-circle" aria-hidden="true"></i></a>
        <p class="ctf-tooltip ctf-more-info"><?php _e( $args['whatis'], 'custom-twitter-feeds' ); ?>.</p>
    <?php endif; ?>
        <?php
    }

	public function layout( $args ) {
		$selected_type = $args['layout_selections']['layout'];
		$layout_types = array(
			'list' => __( 'List', 'instagram-feed' ),
			'carousel' => __( 'Carousel', 'instagram-feed' ),
			'masonry' => __( 'Masonry', 'instagram-feed' ),
		);
		$layout_images = array(
			'list' => CTF_PLUGIN_URL . 'img/list.png',
			'carousel' => CTF_PLUGIN_URL . 'img/carousel.png',
			'masonry' => CTF_PLUGIN_URL . 'img/masonry.png',
		);
		?>

		<?php foreach( $layout_types as $layout_type => $label ) : ?>
            <div class="ctf_layout_cell <?php if($selected_type === $layout_type) echo "ctf_layout_selected"; ?>">
                <input class="ctf_layout_type" id="ctf_layout_type_<?php esc_attr_e( $layout_type ); ?>" name="<?php echo $args['option'].'[layout]'; ?>" type="radio" value="<?php esc_attr_e( $layout_type ); ?>" <?php if ( $selected_type === $layout_type ) echo 'checked'; ?>/><label for="ctf_layout_type_<?php esc_attr_e( $layout_type ); ?>"><span class="ctf_label"><?php echo esc_html( $label ); ?></span><img src="<?php echo $layout_images[ $layout_type ]; ?>" /></label>

                <a href="https://smashballoon.com/custom-twitter-feeds/?utm_campaign=twitter-free&utm_source=settings&utm_medium=layout" target="_blank" class="ctf_lock"><i class="fa fa-rocket"></i>Pro</a>
            </div>
        <?php endforeach; ?>
        <div class="ctf_layout_options_wrap">
            <div class="ctf_layout_settings ctf_layout_type_list">
                <i class="fa fa-info-circle" aria-hidden="true" style="margin-right: 8px;"></i><span class="ctf_note" style="margin-left: 0;"><?php _e('A full-width list of tweets.'); ?></span>
            </div>
            <div class="ctf_layout_settings ctf_layout_type_masonry">
                <p class="ctf_note" style="margin: 0 0 15px 0;"><a href="">Upgrade to the Pro version to use the Masonry layout option</a></p>
                <div class="ctf_layout_setting">
                    <i class="fa fa-info-circle" aria-hidden="true" style="margin-right: 8px;"></i><span class="ctf_note" style="margin-left: 0;"><?php _e('Tweets in columns with no empty space between them.'); ?></span>
                </div>
                <div class="ctf_layout_setting">
                    <label><?php _e('Desktop Columns', 'instagram-feed'); ?></label><code class="ctf_shortcode"> masonrycols
                        Eg: masonrycols=4</code>
                    <br />
                    <select name="<?php echo $args['option'].'[masonrycols]'; ?>" id="ctf_masonrycols">
                        <?php
                        $cols_options = array(1,2,3,4,5,6);
                        foreach ( $cols_options as $option ) :
                            ?>
                            <option value="<?php echo esc_attr( $option ); ?>" <?php if((int)$args['layout_selections']['masonrycols'] == (int)$option) echo 'selected="selected"' ?> ><?php echo esc_html( $option ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ctf_layout_setting">
                    <label><?php _e('Moblie Columns', 'instagram-feed'); ?></label><code class="ctf_shortcode"> masonrymobilecols
                        Eg: masonrymobilecols=2</code>
                    <br />
                    <select name="<?php echo $args['option'].'[masonrymobilecols]'; ?>" id="ctf_masonrymobilecols">
                        <?php
                        $cols_options = array(1,2);
                        foreach ( $cols_options as $option ) :
                            ?>
                            <option value="<?php echo esc_attr( $option ); ?>" <?php if((int)$args['layout_selections']['masonrymobilecols'] == (int)$option) echo 'selected="selected"' ?> ><?php echo esc_html( $option ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="ctf_layout_settings ctf_layout_type_carousel">
                <p class="ctf_note" style="margin: 0 0 15px 0;"><a href="">Upgrade to the Pro version to use the Carousel layout option</a></p>
                <div class="ctf_layout_setting">
                    <i class="fa fa-info-circle" aria-hidden="true" style="margin-right: 8px;"></i><span class="ctf_note" style="margin-left: 0;"><?php _e('Posts are displayed in a slideshow carousel.', 'instagram-feed'); ?></span>
                </div>
                <div class="ctf_layout_setting">
                    <label><?php _e('Desktop Columns', 'instagram-feed'); ?></label><code class="ctf_shortcode"> carouselcols
                        Eg: carouselcols=4</code>
                    <br />
                    <select name="<?php echo $args['option'].'[carouselcols]'; ?>" id="ctf_carouselcols">
                        <?php
                        $cols_options = array(1,2,3,4,5,6);
                        foreach ( $cols_options as $option ) :
                            ?>
                            <option value="<?php echo esc_attr( $option ); ?>" <?php if((int)$args['layout_selections']['carouselcols'] == (int)$option) echo 'selected="selected"' ?> ><?php echo esc_html( $option ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ctf_layout_setting">
                    <label><?php _e('Moblie Columns', 'instagram-feed'); ?></label><code class="ctf_shortcode"> carouselmobilecols
                        Eg: carouselmobilecols=2</code>
                    <br />
                    <select name="<?php echo $args['option'].'[carouselmobilecols]'; ?>" id="ctf_carouselmobilecols">
                        <?php
                        $cols_options = array(1,2);
                        foreach ( $cols_options as $option ) :
                            ?>
                            <option value="<?php echo esc_attr( $option ); ?>" <?php if((int)$args['layout_selections']['carouselmobilecols'] == (int)$option) echo 'selected="selected"' ?> ><?php echo esc_html( $option ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ctf_layout_setting">
                    <label><?php _e('Loop Type', 'instagram-feed'); ?></label><code class="ctf_shortcode"> carouselloop
                        Eg: carouselloop=rewind
                        carouselloop=infinity</code>
                    <br />
                    <select name="<?php echo $args['option'].'[carouselloop]'; ?>" id="ctf_carousel_loop">
                        <option value="none" <?php if($args['layout_selections']['carouselloop'] == "none") echo 'selected="selected"' ?> ><?php _e( 'None', 'instagram-feed'); ?></option>
                        <option value="rewind" <?php if($args['layout_selections']['carouselloop'] == "rewind") echo 'selected="selected"' ?> ><?php _e( 'Rewind', 'instagram-feed'); ?></option>
                        <option value="infinity" <?php if($args['layout_selections']['carouselloop'] == "infinity") echo 'selected="selected"' ?> ><?php _e( 'Infinity', 'instagram-feed'); ?></option>
                    </select>
                </div>
                <div class="ctf_layout_setting">
                    <label><?php _e('Navigation Arrows', 'instagram-feed'); ?></label><code class="ctf_shortcode"> carouselarrows
                        Eg: carouselarrows=below</code>
                    <br />
                    <select name="<?php echo $args['option'].'[carouselarrows]'; ?>" id="ctf_carousel_loop">
                        <option value="onhover" <?php if($args['layout_selections']['carouselarrows'] == "onhover") echo 'selected="selected"' ?> ><?php _e( 'Show on Hover', 'instagram-feed'); ?></option>
                        <option value="below" <?php if($args['layout_selections']['carouselarrows'] == "below") echo 'selected="selected"' ?> ><?php _e( 'Show below feed', 'instagram-feed'); ?></option>
                        <option value="hide" <?php if($args['layout_selections']['carouselarrows'] == "hide") echo 'selected="selected"' ?> ><?php _e( 'Hide arrows', 'instagram-feed'); ?></option>
                    </select>
                </div>
                <div class="ctf_layout_setting">
                    <label><?php _e('Carousel Height', 'instagram-feed'); ?></label><code class="ctf_shortcode"> carouselarrows
                        Eg: carouselarrows=below</code>
                    <br />
                    <select name="<?php echo $args['option'].'[carouselheight]'; ?>" id="ctf_carousel_loop">
                        <option value="tallest" <?php if($args['layout_selections']['carouselheight'] == "tallest") echo 'selected="selected"' ?> ><?php _e( 'Always set to tallest post', 'instagram-feed'); ?></option>
                        <option value="clickexpand" <?php if($args['layout_selections']['carouselheight'] == "clickexpand") echo 'selected="selected"' ?> ><?php _e( 'Set to shortest post, button to expand', 'instagram-feed'); ?></option>
                        <option value="auto'" <?php if($args['layout_selections']['carouselheight'] == "auto'") echo 'selected="selected"' ?> ><?php _e( 'Automatically set to post height (forces single column)', 'instagram-feed'); ?></option>
                    </select>
                </div>
                <div class="ctf_layout_setting">
                    <input type="checkbox" name="<?php echo $args['option'].'[carouselautoplay]'; ?>" id="ctf_carousel_autoplay" <?php if($args['layout_selections']['carouselautoplay'] == true) echo 'checked="checked"' ?> />
                    <label><?php _e("Enable Autoplay", 'instagram-feed'); ?></label><code class="ctf_shortcode"> carouselautoplay
                        Eg: carouselautoplay=true</code>
                </div>
                <div class="ctf_layout_setting">
                    <label><?php _e("Interval Time", 'instagram-feed'); ?></label><code class="ctf_shortcode"> carouseltime
                        Eg: carouseltime=8000</code>
                    <br />
                    <input name="<?php echo $args['option'].'[carouseltime]'; ?>" type="text" value="<?php esc_attr_e( $args['layout_selections']['carouseltime'] ); ?>" size="6" /><?php _e("miliseconds", 'instagram-feed'); ?>
                </div>
            </div>

        </div>
		<?php
	}

    public function custom_code( $args )
    {
        $options = get_option( $args['option'] );
        $option_string = ( isset( $options[ $args['name'] ] ) ) ? esc_attr( $options[ $args['name'] ] ) : '';
        ?>
        <p><?php _e( $args['description'], 'custom-twitter-feeds' ) ; ?></p>
        <textarea name="<?php echo $args['option'].'['.$args['name'].']'; ?>" id="ctf_<?php echo $args['name']; ?>" style="width: 70%;" rows="7"><?php esc_attr_e( stripslashes( $option_string ) ); ?></textarea>
        <?php if ( isset( $args['extra'] ) ) { _e( '<p class="ctf_note">'.$args['extra'].'</p>', 'custom-twitter-feeds' ); } ?>
        <?php
    }

	public function gdpr( $args ) {
		$options = get_option( $args['option'] );
		$gdpr = ( isset( $options[ $args['name'] ] ) ) ? esc_attr( $options[ $args['name'] ] ) : '';
		$select_options = array(
			array(
				'label' => __( 'Automatic', 'custom-twitter-feeds' ),
				'value' => 'auto'
			),
			array(
				'label' => __( 'Yes', 'custom-twitter-feeds' ),
				'value' => 'yes'
			),
			array(
				'label' => __( 'No', 'custom-twitter-feeds' ),
				'value' => 'no'
			)
		)
		?>
		<?php
		$gdpr_list = "<ul class='ctf-list'>
                            	<li>" . __( 'Avatars will be replaced with a Twitter logo in the feed header.', 'custom-twitter-feeds' ) . "</li>
                            	<li>" . __( 'Avatars will not display in Tweets.', 'custom-twitter-feeds' ) . "</li>
                                <li>" . __( 'Twitter intents (for replying, retweeting, and liking) will be plain links.', 'custom-twitter-feeds' ) . "</li>
                            </ul>";
		?>
        <div>
            <select name="<?php echo $args['option'].'['.$args['name'].']'; ?>" id="ctf_gdpr_setting">
				<?php foreach ( $select_options as $select_option ) :
					$selected = $select_option['value'] === $gdpr ? ' selected' : '';
					?>
                    <option value="<?php echo esc_attr( $select_option['value'] ); ?>"<?php echo $selected; ?> ><?php echo esc_html( $select_option['label'] ); ?></option>
				<?php endforeach; ?>
            </select>
            <a class="ctf-tooltip-link" href="JavaScript:void(0);"><?php _e('What does this mean?', 'custom-twitter-feeds'); ?></a>
            <div class="ctf-tooltip ctf-more-info gdpr_tooltip">

                <p><span><?php _e("Yes", 'custom-twitter-feeds' ); ?>:</span> <?php _e("Enabling this setting prevents all images and videos from being loaded directly from Twitter's servers (CDN) to prevent any requests to external websites in your browser. To accommodate this, some features of the plugin will be disabled or limited.", 'custom-twitter-feeds' ); ?> <a href="JavaScript:void(0);" class="ctf_show_gdpr_list"><?php _e( 'What will be limited?', 'custom-twitter-feeds' ); ?></a></p>

				<?php echo "<div class='ctf_gdpr_list'>" . $gdpr_list . '</div>'; ?>


                <p><span><?php _e("No", 'custom-twitter-feeds' ); ?>:</span> <?php _e("The plugin will still make some requests to load and display images and videos directly from Twitter.", 'custom-twitter-feeds' ); ?></p>


                <p><span><?php _e("Automatic", 'custom-twitter-feeds' ); ?>:</span> <?php echo sprintf( __( 'The plugin will only load images and videos directly from Twitter if consent has been given by one of these integrated %s', 'custom-twitter-feeds' ), '<a href="https://smashballoon.com/doc/gdpr-plugin-list/?twitter" target="_blank" rel="noopener">' . __( 'GDPR cookie plugins', 'custom-twitter-feeds' ) . '</a>' ); ?></p>

                <p><?php echo sprintf( __( '%s to learn more about GDPR compliance in the Custom Twitter Feed plugin.', 'custom-twitter-feeds' ), '<a href="https://smashballoon.com/doc/custom-twitter-feeds-gdpr-compliance/?twitter" target="_blank" rel="noopener">'. __( 'Click here', 'custom-twitter-feeds' ).'</a>' ); ?></p>
            </div>
        </div>

		<?php if ( ! CTF_GDPR_Integrations::gdpr_tests_successful( isset( $_GET['retest'] ) ) ) :
			$errors = CTF_GDPR_Integrations::gdpr_tests_error_message();
			?>
            <div class="ctf-box ctf_gdpr_error">
                <div class="ctf-box-setting">
                    <p>
                        <strong><?php _e( 'Error:', 'custom-twitter-feeds' ); ?></strong> <?php _e("Due to a configuration issue on your web server, the GDPR setting is unable to be enabled. Please see below for more information.", 'custom-twitter-feeds' ); ?></p>
                    <p>
						<?php echo $errors; ?>
                    </p>
                </div>
            </div>
		<?php else: ?>

            <div class="ctf_gdpr_auto">
				<?php if ( CTF_GDPR_Integrations::gdpr_plugins_active() ) :
					$active_plugin = CTF_GDPR_Integrations::gdpr_plugins_active();
					?>
                    <div class="ctf_gdpr_plugin_active">
                        <div class="ctf_active">
                            <p>
                                <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="check-circle" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="svg-inline--fa fa-check-circle fa-w-16 fa-2x"><path fill="currentColor" d="M504 256c0 136.967-111.033 248-248 248S8 392.967 8 256 119.033 8 256 8s248 111.033 248 248zM227.314 387.314l184-184c6.248-6.248 6.248-16.379 0-22.627l-22.627-22.627c-6.248-6.249-16.379-6.249-22.628 0L216 308.118l-70.059-70.059c-6.248-6.248-16.379-6.248-22.628 0l-22.627 22.627c-6.248 6.248-6.248 16.379 0 22.627l104 104c6.249 6.249 16.379 6.249 22.628.001z" class=""></path></svg>
                                <b><?php echo sprintf( __( '%s detected', 'custom-twitter-feeds' ), $active_plugin ); ?></b>
                                <br />
								<?php _e( 'Some Custom Twitter Feed features will be limited for visitors to ensure GDPR compliance until they give consent.', 'custom-twitter-feeds' ); ?>
                                <a href="JavaScript:void(0);" class="ctf_show_gdpr_list"><?php _e( 'What will be limited?', 'custom-twitter-feeds' ); ?></a>
                            </p>
							<?php echo "<div class='ctf_gdpr_list'>" . $gdpr_list . '</div>'; ?>
                        </div>

                    </div>
				<?php else: ?>
                    <div class="ctf-box">
                        <div class="ctf-box-setting">
                            <p><?php _e( 'No GDPR consent plugin detected. Install a compatible <a href="https://smashballoon.com/doc/gdpr-plugin-list/?twitter">GDPR consent plugin</a>, or manually enable the setting above to display a GDPR compliant version of the feed to all visitors.', 'custom-twitter-feeds' ); ?></p>
                        </div>
                    </div>
				<?php endif; ?>
            </div>

            <div class="ctf-box ctf_gdpr_yes">
                <div class="ctf-box-setting">
                    <p><?php _e( "No requests will be made to third-party websites. To accommodate this, some features of the plugin will be limited:", 'custom-twitter-feeds' ); ?></p>
					<?php echo $gdpr_list; ?>
                </div>
            </div>

            <div class="ctf-box ctf_gdpr_no">
                <div class="ctf-box-setting">
                    <p><?php _e( "The plugin will function as normal and load images and videos directly from Twitter.", 'custom-twitter-feeds' ); ?></p>
                </div>
            </div>

		<?php endif;
	}

    public function clear_persistent_cache_button( $args ) {
        ?>
        <input id="ctf-clear-persistent-cache" class="button-secondary" style="margin-top: 1px;" type="submit" value="<?php esc_attr_e( 'Clear Persistent Caches' ); ?>" />
        <a class="ctf-tooltip-link" href="JavaScript:void(0);"><i class="fa fa-question-circle" aria-hidden="true"></i></a>
        <p class="ctf-tooltip ctf-more-info"><?php _e( 'Clicking this button will clear all cached data for search and hashtag feeds that have persistent caches', 'custom-twitter-feeds' ); ?>.</p>
        <?php
    }

    public function validate_ctf_options( $input )
    {
        if ( isset( $input['tab'] )  && ! isset( $_POST['just_tokens'] ) ) {
            wp_cache_delete ( 'alloptions', 'options' );

            $ctf_options = get_option( 'ctf_options', array() );

            if ( $input['tab'] === 'configure' && isset( $input['usertimeline_text'] ) ) {

                $feed_types = apply_filters( 'ctf_admin_feed_type_list', '' );
                $cron_clear_cache = isset( $input['cron_cache_clear'] ) ? $input['cron_cache_clear'] : 'no';
                $ctf_options['have_own_tokens'] = false;
                $ctf_options['use_own_consumer'] = false;
                $ctf_options['preserve_settings'] = false;
                $ctf_options['usertimeline_includereplies'] = false;
                $ctf_options['hometimeline_includereplies'] = false;
                $ctf_options['mentionstimeline_includereplies'] = false;

                foreach ( $input as $key => $val ) {
                    if ( $key == 'search_text' || $key == 'usertimeline_text' || $key == 'hashtag_text' ) {
                        $ctf_options[$key] = apply_filters( 'ctf_admin_validate_' . $key, $val );
                    } elseif ( $key == 'ajax_theme' || $key == 'use_own_consumer' || $key == 'have_own_tokens' || $key == 'preserve_settings' ||
                               $key == 'usertimeline_includereplies' || $key == 'hometimeline_includereplies' || $key == 'mentionstimeline_includereplies' ) {
                        if ( $val != 'on' ) {
                            $ctf_options[$key] = false;
                        } else {
                            $ctf_options[$key] = true;
                        }
                    } else {
                        $ctf_options[$key] = sanitize_text_field( $val );
                    }
                }

                $ctf_options['includereplies'] = apply_filters( 'ctf_admin_set_include_replies', $ctf_options );

                // delete feeds cached in transients
                ctf_clear_cache();
	            delete_transient( 'ctf_reauthenticate' );

	            // process force cache to clear on interval
                $cache_time = isset( $input['cache_time'] ) ? (int) $input['cache_time'] : 1;
                $cache_time_unit = isset( $input['cache_time_unit'] ) ? (int) $input['cache_time_unit'] : 3600;

                if ( $cron_clear_cache == 'no' ) {
                    wp_clear_scheduled_hook( 'ctf_cron_job' );
                } elseif ( $cron_clear_cache == 'yes' ) {
                    //Clear the existing cron event
                    wp_clear_scheduled_hook( 'ctf_cron_job' );

                    //Set the event schedule based on what the caching time is set to
                    if ( $cache_time_unit == 3600 && $cache_time > 5 ) {
                        $ctf_cron_schedule = 'twicedaily';
                    } elseif ( $cache_time_unit == 86400 ) {
                        $ctf_cron_schedule = 'daily';
                    } else {
                        $ctf_cron_schedule = 'hourly';
                    }

                    wp_schedule_event( time(), $ctf_cron_schedule, 'ctf_cron_job' );
                }
            } elseif ( $input['tab'] === 'customize' && isset( $input['class'] ) ) {

                $cron_clear_cache = isset( $input['cron_cache_clear'] ) ? $input['cron_cache_clear'] : 'no';
                $checkbox_settings = array( 'width_mobile_no_fixed', 'include_retweeter', 'include_avatar', 'include_author', 'include_logo', 'include_text', 'include_media_placeholder',
                    'include_date', 'include_actions', 'include_twitterlink', 'ajax_theme', 'include_linkbox', 'creditctf', 'showbutton', 'showheader', 'persistentcache', 'selfreplies',
                    'disableintents', 'disableawesome', 'shorturls' );
                $checkbox_settings = apply_filters( 'ctf_admin_customize_checkbox_settings', $checkbox_settings );
                $leave_spaces = array( 'headertext', 'translate_minute', 'translate_hour', 'custom_css', 'custom_js' );

                foreach ( $checkbox_settings as $checkbox_setting ) {
                    $ctf_options[$checkbox_setting] = 0;
                }

                foreach ( $input as $key => $val ) {
                    if ( in_array( $key, $checkbox_settings ) ) {
                        if ( $val != 'on' ) {
                            $ctf_options[$key] = false;
                        } else {
                            $ctf_options[$key] = true;
                        }
                    } else {
                        if ( in_array( $key, $leave_spaces ) ) {
                            $ctf_options[$key] = $val;
                        } else {
                            $ctf_options[$key] = sanitize_text_field( $val );
                        }
                    }
                }

                // delete feeds cached in transients
                ctf_clear_cache();

                // process force cache to clear on interval
                $cache_time = isset( $input['cache_time'] ) ? (int) $input['cache_time'] : 1;
                $cache_time_unit = isset( $input['cache_time_unit'] ) ? (int) $input['cache_time_unit'] : 3600;

                if ( $cron_clear_cache == 'no' ) {
                    wp_clear_scheduled_hook( 'ctf_cron_job' );
                } elseif ( $cron_clear_cache == 'yes' ) {
                    //Clear the existing cron event
                    wp_clear_scheduled_hook( 'ctf_cron_job' );

                    //Set the event schedule based on what the caching time is set to
                    if ( $cache_time_unit == 3600 && $cache_time > 5 ) {
                        $ctf_cron_schedule = 'twicedaily';
                    } elseif ( $cache_time_unit == 86400 ) {
                        $ctf_cron_schedule = 'daily';
                    } else {
                        $ctf_cron_schedule = 'hourly';
                    }

                    wp_schedule_event( time(), $ctf_cron_schedule, 'ctf_cron_job' );
                }
            } elseif ( $input['tab'] === 'style' && isset( $input['headertext'] ) ) {
                $checkbox_settings = array( 'showbio', 'disablelinks', 'linktexttotwitter' );
                $leave_spaces = array( 'headertext' );

                foreach ( $checkbox_settings as $checkbox_setting ) {
                    $ctf_options[$checkbox_setting] = false;
                }

                foreach ( $input as $key => $val ) {
                    if ( in_array( $key, $checkbox_settings ) ) {
                        if ( $val != 'on' ) {
                            $ctf_options[$key] = false;
                        } else {
                            $ctf_options[$key] = true;
                        }
                    } else {
                        if ( in_array( $key, $leave_spaces ) ) {
                            $ctf_options[$key] = $val;
                        } else {
                            $ctf_options[$key] = sanitize_text_field( $val );
                        }
                    }
                }
            }

            return $ctf_options;
        } elseif ( isset( $input['access_token'] ) ) {
            wp_cache_delete ( 'alloptions', 'options' );

            $new = get_option( 'ctf_options', array() );
            $new['access_token'] = $input['access_token'];
            $new['access_token_secret'] = $input['access_token_secret'];

            return $new;

        }
        wp_cache_delete ( 'alloptions', 'options' );

        $new = get_option( 'ctf_options', array() );

        return $new;
    }
}
