<?
// The optin message
function enp_data_optin_message() {?>
    <div class="updated notice">
        <h3>Thanks for activating the Engaging Buttons plugin!</h3>
        <p><a href="http://engagingnewsproject.org">The Engaging News Project</a> made the Engaging Buttons after we found that <a href="http://engagingnewsproject.org/research/social-media-buttons/">people were more likely to click “Respect” over “Like” in comment sections</a>.</p>
        <p>Help us improve the Engaging Buttons plugin by sending your click data to the Engaging News Project. This also helps us provide more free, high-quality research.</p>
        <p>

        <form class="enp-button-data-optin" method="post" action="options.php">
            <input type="hidden" name="enp_button_allow_data_tracking" value="1" />
            <input type="hidden" name="enp_button_redirect_to_settings_page" value="1" />
            <?php settings_fields( 'enp_button_data_optin' ); ?>
            <?php do_settings_sections( 'enp_button_data_optin' ); ?>

            <?php submit_button('Yes, send button data to help improve the Engaging Buttons plugin'); ?>
        </form>

        <p><a href="<? echo admin_url( 'options-general.php?page=enp_button_page');?>">No thanks, just take me to the settings page.</a></p>
    </div>
<?
}

// create the settings for the opt-in and redirect options
add_action( 'admin_init', 'enp_button_data_optin' );
function enp_button_data_optin() {
    // this gets passed to the same function that processes it in admin-save.php
    register_setting( 'enp_button_data_optin', 'enp_button_allow_data_tracking' );
    // passed value so we know to redirect
    register_setting( 'enp_button_data_optin', 'enp_button_redirect_to_settings_page' );
}

// Redirect to settings page if this form is submitted
add_filter( 'pre_update_option_enp_button_redirect_to_settings_page', 'redirect_enp_button_redirect_to_settings_page', 99, 2 );

function redirect_enp_button_redirect_to_settings_page() {
    // redirect to settings page if this form is submitted
    wp_redirect( admin_url('options-general.php?page=enp_button_page') ); exit;
}
?>
