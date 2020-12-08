<?php
$optimize_code = get_option( 'googleanalytics_optimize_code' );
$universal     = get_option( 'googleanalytics_enable_universal_analytics', true );
$anonymization = get_option( 'googleanalytics_ip_anonymization', true );
$gdpr_config   = get_option( 'googleanalytics_gdpr_config');
?>
<div id="adblocker-notice" class="notice notice-error is-dismissible">
	<p>
		<?php echo esc_html__( 'It appears you have an ad blocker enabled. To avoid affecting this plugin\'s functionality, please disable while using its admin configurations and registrations. Thank you.', 'sharethis-share-buttons' ); ?>
	</p>
</div>
<div id="detectadblock">
	<div class="adBanner">
	</div>
</div>
<div id="ga_access_code_modal" class="ga-modal" tabindex="-1">
    <div class="ga-modal-dialog">
        <div class="ga-modal-content">
            <div class="ga-modal-header">
                <span id="ga_close" class="ga-close">&times;</span>
                <h4 class="ga-modal-title"><?php _e( 'Please paste the access code obtained from Google below:' ) ?></h4>
            </div>
            <div class="ga-modal-body">
                <div id="ga_code_error" class="ga-alert ga-alert-danger" style="display: none;"></div>
                <label for="ga_access_code"><strong><?php _e( 'Access Code' ); ?></strong>:</label>
                &nbsp;<input id="ga_access_code_tmp" type="text"
                             placeholder="<?php _e( 'Paste your access code here' ) ?>"/>
                <div class="ga-loader-wrapper">
                    <div class="ga-loader"></div>
                </div>
            </div>
            <div class="ga-modal-footer">
                <button id="ga_btn_close" type="button" class="button">Close</button>
                <button type="button" class="button-primary"
                        id="ga_save_access_code"
                        onclick="ga_popup.saveAccessCode( event )"><?php _e( 'Save Changes' ); ?></button>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php echo $data[ 'debug_modal' ] ?>
<div class="wrap ga-wrap">
    <h2>Google Analytics - <?php _e( 'Settings' ); ?></h2>
    <div class="ga_container">
        <?php if ( ! empty( $data['error_message'] ) ) : ?>
            <?php echo $data['error_message']; ?>
        <?php endif; ?>
        <form id="ga_form" method="post" action="options.php">
            <?php settings_fields( 'googleanalytics' ); ?>
            <input id="ga_access_code" type="hidden"
                   name="<?php echo esc_attr( Ga_Admin::GA_OAUTH_AUTH_CODE_OPTION_NAME ); ?>" value=""/>
            <table class="form-table">
                <tr valign="top">
                    <?php if ( ! empty( $data['popup_url'] ) ): ?>
                        <th scope="row">
                            <label <?php echo ( ! Ga_Helper::are_features_enabled() ) ? 'class="label-grey ga-tooltip"' : '' ?>><?php echo _e( 'Google Profile' ) ?>
                                :
                                <span class="ga-tooltiptext ga-tt-abs"><?php _e( $tooltip ); ?></span>
                            </label>
                        </th>
                        <td <?php echo ( ! Ga_Helper::are_features_enabled() ) ? 'class="ga-tooltip"' : ''; ?>>
                            <?php echo $data[ 'auth_button' ] ?>
                            <span class="ga-tooltiptext"><?php _e( $tooltip ); ?></span>
                            <?php if ( ! empty( $data[ Ga_Admin::GA_WEB_PROPERTY_ID_MANUALLY_OPTION_NAME ] ) ): ?>
                                <div class="ga_warning">
                                    <strong><?php _e( 'Notice' ) ?></strong>:&nbsp;<?php _e( 'Please uncheck the "Manually enter Tracking ID" option to authenticate and view statistics.' ); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>

                    <?php if ( ! empty( $data['ga_accounts_selector'] ) ): ?>
                        <th scope="row"><?php echo _e( 'Google Analytics Account' ) ?>:</th>
                    <?php endif; ?>
                </tr>
                <?php if ( ! empty( $data['ga_accounts_selector'] ) ): ?>
                    <tr valign="top">
                        <td>
                            <?php echo $data['ga_accounts_selector']; ?>
                        </td>
                    </tr>
                <?php endif; ?>
                <tr valign="top">
                    <th scope="row">
                        <div class="checkbox">
                            <label class="ga_checkbox_label <?php echo Ga_Helper::get_code_manually_label_classes() ?>"
                                   for="ga_enter_code_manually"> <input
                                    <?php if ( Ga_Helper::are_features_enabled() ) : ?>
                                        onclick="ga_events.click( this, ga_events.codeManuallyCallback( <?php echo Ga_Helper::are_features_enabled() ? 1 : 0; ?> ) )"
                                    <?php endif; ?>
                                        type="checkbox"
                                    <?php echo ( ! Ga_Helper::are_features_enabled() ) ? 'disabled="disabled"' : ''; ?>
                                        name="<?php echo esc_attr( Ga_Admin::GA_WEB_PROPERTY_ID_MANUALLY_OPTION_NAME ); ?>"
                                        id="ga_enter_code_manually"
                                        value="1"
                                    <?php echo( ( $data[ Ga_Admin::GA_WEB_PROPERTY_ID_MANUALLY_OPTION_NAME ] || ! Ga_Helper::are_terms_accepted() ) ? 'checked="checked"' : '' ); ?>/>&nbsp;
                                <?php _e( 'Manually enter Tracking ID' ) ?>
                                <span class="ga-tooltiptext ga-tt-abs"><?php _e( $tooltip ); ?></span>
                            </label>
                            <?php if ( ! Ga_Helper::are_features_enabled() ) : ?>
                                <input id="ga_enter_code_manually_hidden" type="hidden"
                                       name="<?php echo esc_attr( Ga_Admin::GA_WEB_PROPERTY_ID_MANUALLY_OPTION_NAME ); ?>"
                                       value="1"/>
                            <?php endif; ?>
                        </div>
                    </th>
                    <td></td>
                </tr>
                <tr valign="top"
                    id="ga_manually_wrapper" <?php echo( ( $data[ Ga_Admin::GA_WEB_PROPERTY_ID_MANUALLY_OPTION_NAME ] || ! Ga_Helper::are_features_enabled() ) ? '' : 'style="display: none"' ); ?> >

                    <th scope="row"><?php _e( 'Tracking ID' ) ?>:</th>
                </tr>
                <tr valing="top">
                    <td>
                        <input type="text"
                               name="<?php echo esc_attr( Ga_Admin::GA_WEB_PROPERTY_ID_MANUALLY_VALUE_OPTION_NAME ); ?>"
                               value="<?php echo esc_attr( $data[ Ga_Admin::GA_WEB_PROPERTY_ID_MANUALLY_VALUE_OPTION_NAME ] ); ?>"
                               id="ga_manually_input"/>&nbsp;
                        <div class="ga_warning">
                            <strong><?php _e( 'Warning' ); ?></strong>:&nbsp;<?php _e( 'If you enter your Tracking ID manually, Analytics statistics will not be shown.' ); ?>
                            <br>
                            <?php _e( 'We strongly recommend to authenticate with Google using the button above.' ); ?>
                        </div>
                    </td>
                </tr>
                <tr valign="top" id="ga_roles_wrapper">
                    <th scope="row">
                        <label <?php echo ( ! Ga_Helper::are_features_enabled() ) ? 'class="label-grey ga-tooltip"' : '' ?>><?php _e( 'Exclude Tracking for Roles' ) ?>
                            :
                            <span class="ga-tooltiptext ga-tt-abs"><?php _e( $tooltip ); ?></span>
                        </label>
                    </th>
                </tr>
                <tr valign="top">
                    <td>
                        <?php
                        if ( ! empty( $data['roles'] ) ) {
                            $roles = $data['roles'];
                            foreach ( $roles as $role ) {
                                ?>
                                <div class="checkbox">
                                    <label class="ga_checkbox_label <?php echo ( ! Ga_Helper::are_features_enabled() ) ? 'label-grey ga-tooltip' : ''; ?>"
                                           for="checkbox_<?php echo $role['id']; ?>">
                                        <input id="checkbox_<?php echo $role['id']; ?>" type="checkbox"
                                            <?php echo ( ! Ga_Helper::are_features_enabled() ) ? 'disabled="disabled"' : ''; ?>
                                               name="<?php echo esc_attr( Ga_Admin::GA_EXCLUDE_ROLES_OPTION_NAME . "[" . $role['id'] . "]" ); ?>"
                                               id="<?php echo esc_attr( $role['id'] ); ?>"
                                            <?php echo esc_attr( ( $role['checked'] ? 'checked="checked"' : '' ) ); ?> />&nbsp;
                                        <?php echo esc_html( $role['name'] ); ?>
                                        <span class="ga-tooltiptext"><?php _e( $tooltip ); ?></span>
                                    </label>
                                </div>
                                <?php
                            }
                        }
                        ?>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e( 'Enable IP Anonymization' ) ?>:</th>
                </tr>
                <tr valign="top">
                    <td>
                        <label class="ga-switch <?php echo ( ! Ga_Helper::are_features_enabled() ) ? 'label-grey ga-tooltip' : '' ?>">
	                        <?php if ( Ga_Helper::are_features_enabled() ) : ?>
                            <input id="ga-anonymization" name="googleanalytics_ip_anonymization"
                                   type="checkbox" <?php echo checked( $anonymization, 'on' ); ?>>

                            <div id="ga-slider" class="ga-slider round"></div>
	                        <?php else: ?>
	                        <input id="ga-anonymization" name="googleanalytics_ip_anonymization"
	                               type="checkbox" disabled="disabled">

	                        <div id="ga-slider" class="ga-slider round"></div>
	                        <span class="ga-tooltiptext ga-tt-abs"><?php _e( $tooltip ); ?></span>
	                        <?php endif; ?>
                        </label>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e( 'If using Google Optimize, enter optimize code here' ) ?>:</th>
                </tr>
                <tr valign="top">
                    <td>
	                    <label class="ga-text <?php echo ( ! Ga_Helper::are_features_enabled() ) ? 'label-grey ga-tooltip' : '' ?>">
		                    <?php if ( Ga_Helper::are_features_enabled() ) : ?>
			                    <input id="ga-optimize" name="googleanalytics_optimize_code"
			                           type="text" placeholder="GTM-XXXXXX" value="<?php echo esc_attr( $optimize_code ); ?>">
		                    <?php else: ?>
			                    <input id="ga-optimize" name="googleanalytics_optimize_code"
			                           type="text" placeholder="GTM-XXXXXX" value="<?php echo esc_attr( $optimize_code ); ?>" readonly>
			                    <span class="ga-tooltiptext ga-tt-abs"><?php _e( $tooltip ); ?></span>
		                    <?php endif; ?>
	                    </label>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e( 'Disable all features' ) ?>:</th>
                </tr>
                <tr valign="top">
                    <td>
	                    <label class="ga-switch <?php echo ( ! Ga_Helper::are_features_enabled() ) ? 'label-grey ga-tooltip' : '' ?>">
		                    <?php if ( Ga_Helper::are_features_enabled() ) : ?>
			                    <input id="ga-disable" name="<?php echo Ga_Admin::GA_DISABLE_ALL_FEATURES; ?>"
			                           type="checkbox">
			                    <div id="ga-slider" class="ga-slider-disable ga-slider round"></div>
		                    <?php else: ?>
			                    <input id="ga-disable" name="<?php echo Ga_Admin::GA_DISABLE_ALL_FEATURES; ?>"
			                           type="checkbox" disabled="disabled">
			                    <div id="ga-slider" class="ga-slider-disable ga-slider round"></div>
			                    <span class="ga-tooltiptext ga-tt-abs"><?php _e( $tooltip ); ?></span>
		                    <?php endif; ?>
                        </label>
                    </td>
                </tr>
                <?php include plugin_dir_path(__FILE__) . 'templates/gdpr.php'; ?>
            </table>

            <p class="submit">
                <input type="submit" class="button-primary"
                       value="<?php _e( 'Save Changes' ) ?>"/>
            </p>
        </form>
        <?php if(empty($gdpr_config)) : ?>
        <div class="sidebar-ad">
            <h2 style="text-decoration: underline;">
                <?php esc_html_e('Check out our new GDPR Compliance Tool!', 'googleanalytics'); ?>
            </h2>
            <div class="row">
                <div class="col-md-12">
                    <img src="<?php echo trailingslashit(get_home_url()) . 'wp-content/plugins/googleanalytics/assets/images/gdpr-ex.png'; ?>" />
                </div>
                <div class="col-md-6">
                    <h3><?php esc_html_e('Confirm Consent', 'googleanalytics'); ?></h3>
                    <p>
                        <?php esc_html_e(
                            'A simple and streamlined way to confirm a user’s initial acceptance or rejection of cookie collection',
                            'googleanalytics'
                        ); ?>
                    </p>
                </div>
                <div class="col-md-6">
                    <h3><?php esc_html_e('Select Purpose', 'googleanalytics'); ?></h3>
                    <p>
                        <?php esc_html_e(
                            'A transparent system of verifying the intent of collecting a user’s cookies, and giving the option to opt in or out',
                            'googleanalytics'
                        ); ?>
                    </p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <h3><?php esc_html_e('Indicate Company', 'googleanalytics'); ?></h3>
                    <p>
                        <?php esc_html_e(
                            'A comprehensive record of company-level information that allows users to monitor and control the recipients of cookie collection',
                            'googleanalytics'
                        ); ?>
                    </p>
                </div>
                <div class="col-md-6">
                    <h3><?php esc_html_e('Access Data Rights', 'googleanalytics'); ?></h3>
                    <p>
                        <?php esc_html_e(
                            'A centralized database where users can review the latest privacy policies and information pertaining to their cookie collection',
                            'googleanalytics'
                        ); ?>
                    </p>
                </div>
            </div>
            <div class="row register-section">
	            <?php if ( Ga_Helper::are_features_enabled() ) : ?>
		            <td>
			            <button class="gdpr-enable"><?php esc_html_e('Enable'); ?></button>
		            </td>
	            <?php else : ?>
		            <td>
			            <label class="<?php echo ( ! Ga_Helper::are_features_enabled() ) ? 'label-grey ga-tooltip' : '' ?>">
				            <button class="gdpr-enable" disabled="disabled"><?php esc_html_e('Enable'); ?></button>
				            <span class="ga-tooltiptext ga-tt-abs"><?php _e( $tooltip ); ?></span>
			            </label>
		            </td>
	            <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php if ( $data['debug_info'] ) : ?>
        <tr valign="top">
            <td colspan="2">
                <p>If you are still experiencing an issue, we are here to help! We recommend clickingthe "Send Debugging Info" button below and pasting the information within an email to support@sharethis.com.</p>
                <p>
                    <button id="ga_debug_button" class="button button-secondary" onclick="ga_debug.open_modal( event )" >Send Debugging Info</button>
                    <?php if ( ! empty( $data['ga_accounts_selector'] ) ): ?>
                        <?php echo $data[ 'auth_button' ] ?>
                        <br>
                        <small class="notice">
                            *If you reset your google password you MUST re-authenticate to continue viewing your analytics dashboard.
                        </small>
                    <?php endif; ?>
                </p>
            </td>
        </tr>
    <?php endif; ?>

    <p class="ga-love-text"><?php _e( 'Love this plugin?' ); ?> <a
            href="https://wordpress.org/support/plugin/googleanalytics/reviews/#new-post"><?php _e( ' Please help spread the word by leaving a 5-star review!' ); ?> </a>
    </p>
</div>
<script type="text/javascript">
    const GA_DISABLE_FEATURE_URL = '<?php echo Ga_Helper::create_url(admin_url(Ga_Helper::GA_SETTINGS_PAGE_URL), array(Ga_Controller_Core::ACTION_PARAM_NAME => 'ga_action_disable_all_features')); ?>';
    const GA_ENABLE_FEATURE_URL = '<?php echo Ga_Helper::create_url(admin_url(Ga_Helper::GA_SETTINGS_PAGE_URL), array(Ga_Controller_Core::ACTION_PARAM_NAME => 'ga_action_enable_all_features')); ?>';
    jQuery(document).ready(function () {
        ga_switcher.init('<?php echo $data[ Ga_Admin::GA_DISABLE_ALL_FEATURES ]; ?>');
    });
</script>
