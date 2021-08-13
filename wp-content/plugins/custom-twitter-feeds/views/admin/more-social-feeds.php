<div class="ctf_more_plugins" id="ctf-admin-about">

    <div class="ctf-more-plugins-intro">
        <h3><?php _e( "Here's some more <span>free</span> plugins you might like!", 'ctf' ); ?></h3>
        <p><?php _e( "As you're already using one of our free plugins we thought we'd suggest some others you might like to. Check out our other free plugins below:", 'ctf' ); ?></p>
    </div>

    <?php function get_am_plugins() {

        $images_url = CTF_PLUGIN_URL . 'img/about/';

        return array(
            'instagram-feed/instagram-feed.php' => array(
                'icon' => $images_url . 'plugin-if.png',
                'name' => esc_html__( 'Instagram Feed', 'ctf' ),
                'desc' => esc_html__( 'Instagram Feed is a clean and beautiful way to add your Instagram posts to your website. Grab your visitors attention and keep them engaged with your site longer.', 'ctf' ),
                'url'  => 'https://downloads.wordpress.org/plugin/instagram-feed.zip',
                'pro'  => array(
                    'plug' => 'instagram-feed-pro/instagram-feed.php',
                    'icon' => $images_url . 'plugin-if.png',
                    'name' => esc_html__( 'Instagram Feed Pro', 'ctf' ),
                    'desc' => esc_html__( 'Instagram Feed is a clean and beautiful way to add your Instagram posts to your website. Grab your visitors attention and keep them engaged with your site longer.', 'ctf' ),
                    'url'  => 'https://smashballoon.com/instagram-feed/?utm_campaign=facebook-free&utm_source=cross&utm_medium=ctfinstaller',
                    'act'  => 'go-to-url',
                ),
            ),
            'custom-facebook-feed/custom-facebook-feed.php' => array(
                'icon' => $images_url . 'plugin-fb.png',
                'name' => esc_html__( 'Custom Facebook Feed', 'ctf' ),
                'desc' => esc_html__( 'Custom Facebook Feed makes displaying your Facebook posts easy. Keep your site visitors informed and increase engagement with your Facebook page by displaying a feed on your website.', 'ctf' ),
                'url'  => 'https://downloads.wordpress.org/plugin/custom-facebook-feed.zip',
                'pro'  => array(
                    'plug' => 'custom-facebook-feed-pro/custom-facebook-feed.php',
                    'icon' => $images_url . 'plugin-fb.png',
                    'name' => esc_html__( 'Custom Facebook Feed Pro', 'ctf' ),
                    'desc' => esc_html__( 'Custom Facebook Feed makes displaying your Facebook posts easy. Keep your site visitors informed and increase engagement with your Facebook page by displaying a feed on your website.', 'ctf' ),
                    'url'  => 'https://smashballoon.com/custom-facebook-feed/?utm_campaign=instagram-free&utm_source=cross&utm_medium=ctfinstaller',
                    'act'  => 'go-to-url',
                )
            ),

            'custom-twitter-feeds/custom-twitter-feed.php' => array(
                'icon' => $images_url . 'plugin-tw.jpg',
                'name' => esc_html__( 'Custom Twitter Feeds', 'ctf' ),
                'desc' => esc_html__( 'Custom Twitter Feeds is a highly customizable way to display tweets from your Twitter account. Promote your latest content and update your site content automatically.', 'ctf' ),
                'url'  => 'https://downloads.wordpress.org/plugin/custom-twitter-feeds.zip',
                'pro'  => array(
                    'plug' => 'custom-twitter-feeds-pro/custom-twitter-feed.php',
                    'icon' => $images_url . 'plugin-tw.jpg',
                    'name' => esc_html__( 'Custom Twitter Feeds Pro', 'ctf' ),
                    'desc' => esc_html__( 'Custom Twitter Feeds is a highly customizable way to display tweets from your Twitter account. Promote your latest content and update your site content automatically.', 'ctf' ),
                    'url'  => 'https://smashballoon.com/custom-twitter-feeds/?utm_campaign=instagram-free&utm_source=cross&utm_medium=ctfinstaller',
                    'act'  => 'go-to-url',
                )
            ),

            'feeds-for-youtube/youtube-feed.php' => array(
                'icon' => $images_url . 'plugin-yt.png',
                'name' => esc_html__( 'Feeds for YouTube', 'ctf' ),
                'desc' => esc_html__( 'Feeds for YouTube is a simple yet powerful way to display videos from YouTube on your website. Increase engagement with your channel while keeping visitors on your website.', 'ctf' ),
                'url'  => 'https://downloads.wordpress.org/plugin/feeds-for-youtube.zip',
                'pro'  => array(
                    'plug' => 'youtube-feed-pro/youtube-feed.php',
                    'icon' => $images_url . 'plugin-yt.png',
                    'name' => esc_html__( 'Feeds for YouTube Pro', 'ctf' ),
                    'desc' => esc_html__( 'Feeds for YouTube is a simple yet powerful way to display videos from YouTube on your website. Increase engagement with your channel while keeping visitors on your website.', 'ctf' ),
                    'url'  => 'https://smashballoon.com/youtube-feed/?utm_campaign=instagram-free&utm_source=cross&utm_medium=sbyinstaller',
                    'act'  => 'go-to-url',
                )
            ),
        );

    }

    function output_about_addons() {

        if ( version_compare( PHP_VERSION,  '5.3.0' ) <= 0
            || version_compare( get_bloginfo('version'), '4.6' , '<' ) ){
            return;
        }

        $all_plugins = get_plugins();
        $am_plugins  = get_am_plugins();
        $has_all_plugins = true;

        ?>
        <div id="ctf-admin-addons">
            <div class="addons-container">
                <?php
                foreach ( $am_plugins as $plugin => $details ) :

                    $free_only = true;
                    $plugin_data = get_the_plugin_data( $plugin, $details, $all_plugins, $free_only );
                    $plugin_slug = strtolower( str_replace( ' ', '_', $plugin_data['details']['name'] ) );

                    //Only show the plugin if both free/pro versions aren't already active
                    isset( $plugin_data['details']['plug'] ) ? $pro_plugin_source = $plugin_data['details']['plug'] : $pro_plugin_source = '';

                    if( !is_plugin_active( $plugin ) && !is_plugin_active( $pro_plugin_source ) ){
                        $has_all_plugins = false;
                        ?>
                        <div class="addon-container" id="install_<?php echo $plugin_slug; ?>">
                            <div class="addon-item">
                                <div class="details ctf-clear">
                                    <img src="<?php echo esc_url( $plugin_data['details']['icon'] ); ?>">
                                    <h5 class="addon-name">
                                        <?php echo esc_html( $plugin_data['details']['name'] ); ?>
                                    </h5>
                                    <p class="addon-desc">
                                        <?php echo wp_kses_post( $plugin_data['details']['desc'] ); ?>
                                    </p>
                                </div>
                                <div class="actions ctf-clear">
                                    <div class="status">
                                        <strong>
                                            <?php _e( 'Price:', 'ctf' );
                                            echo ' <span style="color: green;">';
                                            _e( 'Free', 'ctf' );
                                            echo '</span>'; ?>
                                        </strong>
                                    </div>
                                    <div class="action-button">
                                        <button class="<?php echo esc_attr( $plugin_data['action_class'] ); ?>" data-plugin="<?php echo esc_attr( $plugin_data['plugin_src'] ); ?>" data-type="plugin">
                                            <?php echo wp_kses_post( $plugin_data['action_text'] ); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php } ?>

                <?php endforeach;

                if( $has_all_plugins == true ){ ?>

                    <style type="text/css">.ctf-more-plugins-intro{display:none;}</style>
                    <h2><?php _e( 'You already have all of our free plugins. Awesome!', 'ctf' ); ?></h2>

                    <p><?php _e( 'Thank you so much for using our plugins. We appreciate you trusting us to power your social media feeds.', 'ctf' ); ?></p>
                    <p><?php _e( 'If you want to support us in our mission to make bringing social media content to your website both easy and reliable, then consider upgrading to one of our Pro plugins.', 'ctf' ); ?></p>

                    <div class="ctf-cols-4">
                        <?php //Show a list of Pro plugins which aren't currently active ?>
                        <?php foreach ( $am_plugins as $plugin => $details ) :

                            $plugin_data = get_the_plugin_data( $plugin, $details, $all_plugins );
                            $plugin_slug = strtolower( str_replace( ' ', '_', $plugin_data['details']['name'] ) );

                            isset( $plugin_data['details']['plug'] ) ? $pro_plugin_source = $plugin_data['details']['plug'] : $pro_plugin_source = '';
                            if( !is_plugin_active( $pro_plugin_source ) ){
                            ?>

                                <div class="addon-container" id="install_<?php echo $plugin_slug; ?>">
                                    <div class="addon-item">
                                        <div class="details ctf-clear">
                                            <img src="<?php echo esc_url( $plugin_data['details']['icon'] ); ?>">
                                            <h5 class="addon-name">
                                                <?php echo esc_html( $plugin_data['details']['name'] ); ?>
                                            </h5>
                                            <p class="addon-desc">
                                                <?php echo wp_kses_post( $plugin_data['details']['desc'] ); ?>
                                            </p>
                                        </div>
                                        <div class="actions ctf-clear">
                                            <div class="action-button">
                                                <a href="<?php echo esc_attr( $details['pro']['url'] ); ?>" target="_blank" class="status-go-to-url button button-primary">
                                                    <?php  _e( 'Upgrade to Pro', 'ctf' ); ?>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            <?php } ?>

                        <?php endforeach; ?>
                    </div>

                <?php } ?>

            </div>
        </div>
        <?php
    }


    function get_the_plugin_data( $plugin, $details, $all_plugins, $free_only = false ) {

        $have_pro = ( ! empty( $details['pro'] ) && ! empty( $details['pro']['plug'] ) );
        $show_pro = false;

        $plugin_data = array();

        if( $free_only ) $have_pro = false;

        if ( $have_pro ) {
            if ( array_key_exists( $plugin, $all_plugins ) ) {
                if ( is_plugin_active( $plugin ) ) {
                    $show_pro = true;
                }
            }
            if ( array_key_exists( $details['pro']['plug'], $all_plugins ) ) {
                $show_pro = true;
            }
            if ( $show_pro ) {
                $plugin  = $details['pro']['plug'];
                $details = $details['pro'];
            }
        }

        if( $free_only ) $show_pro = false;

        if ( array_key_exists( $plugin, $all_plugins ) ) {
            if ( is_plugin_active( $plugin ) ) {
                // Status text/status.
                $plugin_data['status_class'] = 'status-active';
                $plugin_data['status_text']  = esc_html__( 'Active', 'ctf' );
                // Button text/status.
                $plugin_data['action_class'] = $plugin_data['status_class'] . ' button button-secondary disabled';
                $plugin_data['action_text']  = esc_html__( 'Activated', 'ctf' );
                $plugin_data['plugin_src']   = esc_attr( $plugin );
            } else {
                // Status text/status.
                $plugin_data['status_class'] = 'status-inactive';
                $plugin_data['status_text']  = esc_html__( 'Inactive', 'ctf' );
                // Button text/status.
                $plugin_data['action_class'] = $plugin_data['status_class'] . ' button button-secondary';
                $plugin_data['action_text']  = esc_html__( 'Activate', 'ctf' );
                $plugin_data['plugin_src']   = esc_attr( $plugin );
            }
        } else {
            // Doesn't exist, install.
            // Status text/status.
            $plugin_data['status_class'] = 'status-download';
            if ( isset( $details['act'] ) && 'go-to-url' === $details['act'] ) {
                $plugin_data['status_class'] = 'status-go-to-url';
            }
            $plugin_data['status_text'] = esc_html__( 'Not Installed', 'ctf' );
            // Button text/status.
            $plugin_data['action_class'] = $plugin_data['status_class'] . ' button button-primary';
            $plugin_data['action_text']  = esc_html__( 'Install Plugin', 'ctf' );
            $plugin_data['plugin_src']   = esc_url( $details['url'] );
        }

        $plugin_data['details'] = $details;

        return $plugin_data;
    }


    output_about_addons();

    ?>
    <style>.ctf_quickstart, .ctf-pro-notice, .ctf_plugins_promo, .ctf_share_plugin{ display: none !Important; }</style>
</div>