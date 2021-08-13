<div id="ctf-admin" class="wrap">
	<?php do_action( 'ctf_admin_overview_before_title' ); ?>

    <?php
	$lite_notice_dismissed = get_transient( 'twitter_feed_dismiss_lite' );

	if ( ! $lite_notice_dismissed ) :
		?>
        <div id="ctf-notice-bar" style="display:none">
            <span class="ctf-notice-bar-message"><?php _e( 'You\'re using Custom Twitter Feeds Lite. To unlock more features consider <a href="https://smashballoon.com/custom-twitter-feeds/?utm_campaign=twitter-free&utm_source=noticebar&utm_medium=litenotice" target="_blank" rel="noopener noreferrer">upgrading to Pro</a>.', 'custom-twitter-feeds'); ?></span>
            <button type="button" class="dismiss" title="<?php _e( 'Dismiss this message.', 'custom-twitter-feeds'); ?>" data-page="overview">
            </button>
        </div>
	<?php endif; ?>

    <h1>Custom Twitter Feeds</h1>
    <?php
    // this controls which view is included based on the selected tab
    if ( ! isset ( $tab ) ) {
        $tab = isset( $_GET["tab"] ) ? $_GET["tab"] : '';
    }
    $active_tab = CtfAdmin::get_active_tab( $tab );
    ?>

    <!-- Display the tabs along with styling for the 'active' tab -->
    <h2 class="nav-tab-wrapper">
        <a href="admin.php?page=custom-twitter-feeds&tab=configure" class="nav-tab <?php if ( $active_tab == 'configure' ){ echo 'nav-tab-active'; } ?>"><?php _e( '1. Configure', 'ctf' ); ?></a>
        <a href="admin.php?page=custom-twitter-feeds&tab=customize" class="nav-tab <?php if ( $active_tab == 'customize' ){ echo 'nav-tab-active'; } ?>"><?php _e( '2. Customize', 'ctf' ); ?></a>
        <a href="admin.php?page=custom-twitter-feeds&tab=style" class="nav-tab <?php if ( $active_tab == 'style' ){ echo 'nav-tab-active'; } ?>"><?php _e( '3. Style', 'ctf' ); ?></a>
        <a href="admin.php?page=custom-twitter-feeds&tab=display" class="nav-tab <?php if ( $active_tab == 'display' ){ echo 'nav-tab-active'; } ?>"><?php _e( '4. Display Your Feed', 'ctf' ); ?></a>
        <a href="admin.php?page=custom-twitter-feeds&tab=support" class="nav-tab <?php if ( $active_tab == 'support' ){ echo 'nav-tab-active'; } ?>"><?php _e( 'Support', 'ctf' ); ?></a>
        <a href="admin.php?page=custom-twitter-feeds&amp;tab=more" class="nav-tab <?php echo $active_tab == 'more' ? 'nav-tab-active' : ''; ?>"><?php _e('More Social Feeds', 'ctf'); ?>
        </a>
    </h2>
    <?php

    if ( isset( $active_tab ) ) {
        if ( $active_tab === 'customize' ) {
            require_once CTF_URL . 'views/admin/customize.php';
        } elseif ( $active_tab === 'style' ) {
            require_once CTF_URL . 'views/admin/style.php';
        }  elseif ( $active_tab === 'configure' ) {
            require_once CTF_URL . 'views/admin/configure.php';
        } elseif ( $active_tab === 'display' ) {
            require_once CTF_URL .'views/admin/display.php';
        } elseif ( $active_tab === 'allfeeds' ) {
	        require_once CTF_URL .'views/admin/locator-summary.php';
        } elseif ( $active_tab === 'support' ) {
            require_once CTF_URL .'views/admin/support.php';
        } elseif ( $active_tab === 'more' ) {
            require_once CTF_URL .'views/admin/more-social-feeds.php';
        }
    }
    ?>

    <p><span class="fa fa-life-ring" aria-hidden="true"></span>&nbsp; <?php _e('Need help setting up the plugin? Check out our <a href="https://smashballoon.com/custom-twitter-feeds/free/?utm_campaign=twitter-free&utm_source=settings&utm_medium=helpsetup" target="_blank">setup directions</a>', 'custom-twitter-feeds'); ?></p>

    <div class="ctf-quick-start">
        <h3><span class="fa fa-rocket" aria-hidden="true"></span>&nbsp; <?php _e( 'Display your feed', 'custom-twitter-feeds'); ?></h3>
        <p><?php _e( "Copy and paste this shortcode directly into the page, post or widget where you'd like to display the feed:", "custom-twitter-feeds" ); ?>
        <input type="text" value="[custom-twitter-feeds]" size="18" readonly="readonly" style="text-align: center;" onclick="this.focus();this.select()" title="<?php _e( 'To copy, click the field then press Ctrl + C (PC) or Cmd + C (Mac).', 'custom-twitter-feeds' ); ?>" /></p>
        <p><?php _e( "Find out how to display <a href='?page=custom-twitter-feeds&tab=display'>multiple feeds</a>.", "custom-twitter-feeds" ); ?></p>
    </div>

    <a href="https://smashballoon.com/custom-twitter-feeds/demo?utm_campaign=twitter-free&utm_source=settings&utm_medium=pronotice" target="_blank" class="ctf-pro-notice">
        <img src="<?php echo plugins_url( '../../img/pro-notice.png?1' , __FILE__ ) ?>" alt="Custom Twitter Feeds Pro" />
    </a>

    <p class="ctf-footnote dashicons-before dashicons-admin-plugins"> Check out our free plugins: <a href="https://wordpress.org/plugins/custom-facebook-feed/" target="_blank">Facebook</a>, <a href="https://wordpress.org/plugins/instagram-feed/" target="_blank">Instagram</a>, and <a href="https://wordpress.org/plugins/feeds-for-youtube/" target="_blank">YouTube</a>.</p>
</div>