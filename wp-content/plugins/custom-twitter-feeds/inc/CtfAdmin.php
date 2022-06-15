<?php
/**
 * Class CtfAdmin
 *
 * Uses the Settings API to create easily customizable settings pages and tabs
 */
use TwitterFeed\CTF_GDPR_Integrations;
use TwitterFeed\CTF_Feed_Locator;
use TwitterFeed\Admin\CTF_Notifications;


// Don't load directly
if ( ! defined( 'ABSPATH' ) ) {
    die( '-1' );
}

class CtfAdmin
{
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_init', array( $this, 'ctf_current_user_can' ) );
    }

    public function ctf_current_user_can( $cap ) {
		return ctf_current_user_can( $cap );
    }

    public function add_menu()
    {
		$cap = ctf_get_manage_options_cap();

		$notice = '';

		$ctf_notifications = new CTF_Notifications();
		$notifications = $ctf_notifications->get();

		$notice_bubble = '';
		if ( empty( $notice ) && ! empty( $notifications ) && is_array( $notifications ) ) {
			$notice_bubble = ' <span class="ctf-notice-alert"><span>'.count( $notifications ).'</span></span>';
		}

        add_menu_page(
            __( 'Twitter Feeds', 'custom-twitter-feeds' ),
            __( 'Twitter Feeds', 'custom-twitter-feeds' ). $notice_bubble . $notice,
            $cap,
            'custom-twitter-feeds',
            'sb_twitter_settings_page'
        );

        add_submenu_page(
            'custom-twitter-feeds',
            __( 'Upgrade to Pro', 'custom-twitter-feeds' ),
            __( '<span class="ctf_get_pro">Upgrade to Pro</span>', 'custom-twitter-feeds' ),
            $cap,
            'https://smashballoon.com/custom-twitter-feeds/demo/?utm_campaign=twitter-free&utm_source=menu-link&utm_medium=upgrade-link',
            ''
        );

        //Show a Instagram plugin menu item if it isn't already installed
        if( !is_plugin_active( 'instagram-feed/instagram-feed.php' ) && !is_plugin_active( 'instagram-feed-pro/instagram-feed.php' ) ){
            add_submenu_page(
                'custom-twitter-feeds',
                __( 'Instagram Feed', 'custom-twitter-feeds' ),
                '<span class="ctf_get_sbi">' . __( 'Instagram Feed', 'custom-twitter-feeds' ) . '</span>',
                'manage_options',
                'admin.php?page=custom-twitter-feeds&tab=more',
                ''
            );
        }

        //Show a Instagram plugin menu item if it isn't already installed
        if( !is_plugin_active( 'custom-facebook-feed/custom-facebook-feed.php' ) && !is_plugin_active( 'custom-facebook-feed-pro/custom-facebook-feed.php' ) ){
            add_submenu_page(
                'custom-twitter-feeds',
                __( 'Facebook Feed', 'custom-twitter-feeds' ),
                '<span class="ctf_get_cff">' . __( 'Facebook Feed', 'custom-twitter-feeds' ) . '</span>',
                'manage_options',
                'admin.php?page=custom-twitter-feeds&tab=more',
                ''
            );
        }

        //Show a YouTube plugin menu item if it isn't already installed
        if( !is_plugin_active( 'feeds-for-youtube/youtube-feed.php' ) && !is_plugin_active( 'youtube-feed-pro/youtube-feed.php' ) ){
            add_submenu_page(
                'custom-twitter-feeds',
                __( 'YouTube Feed', 'custom-twitter-feeds' ),
                '<span class="ctf_get_yt">' . __( 'YouTube Feed', 'custom-twitter-feeds' ) . '</span>',
                'manage_options',
                'admin.php?page=custom-twitter-feeds&tab=more',
                ''
            );
        }
    }


    public static function get_active_tab( $tab = '' )
    {
		return 'configure';
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
}

function sb_twitter_settings_page() {
    $link = admin_url( 'admin.php?page=ctf-settings' );
    ?>
    <div id="ctf_admin">
        <div class="ctf_notice">
            <strong><?php esc_html_e( 'The Twitter Feed Settings page has moved!', 'custom-twitter-feeds' ); ?></strong>
            <a href="<?php echo esc_url( $link ); ?>"><?php esc_html_e( 'Click here to go to the new page.', 'custom-twitter-feeds' ); ?></a>
        </div>
    </div>
    <?php
}
