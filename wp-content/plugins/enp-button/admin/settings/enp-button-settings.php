<?php

//  Create link to the menu page.
add_action('admin_menu', 'enp_create_menu');
function enp_create_menu() {
    //create new top-level menu
    add_options_page('Engaging Buttons', 'Engaging Buttons', 'manage_options', 'enp_button_page', 'enp_button_page', 'dashicons-megaphone', 100);
}


// Add filters for any fields that need extra work before saving

// We can't register dynamically named variables, so we're going to
// create everything under enp_buttons and parse from there
add_filter( 'pre_update_option_enp_buttons', 'set_enp_buttons_values', 10, 2 );

// If they don't want data reporting, give them a little, "please, please, PLEASE add reporting!" notification
add_filter( 'pre_update_option_enp_button_allow_data_tracking', 'set_enp_button_allow_data_tracking', 10, 2 );


// Create settings fields.
add_action( 'admin_init', 'enp_button_data' );
function enp_button_data() {

    // button type
    register_setting( 'enp_button_settings', 'enp_buttons' );

    // global enp_button settings
    register_setting( 'enp_button_settings', 'enp_button_must_be_logged_in' );
    register_setting( 'enp_button_settings', 'enp_button_allow_data_tracking' );
    register_setting( 'enp_button_settings', 'enp_button_promote_enp' );

    // style settings
    register_setting( 'enp_button_settings', 'enp_button_style' );
    register_setting( 'enp_button_settings', 'enp_button_icons' );
}

// enqueue our scripts
function enp_enqueue_admin_scripts() {
    wp_register_style('enp-admin-styles', plugins_url( 'enp-button/admin/css/enp-admin-styles.css'));
    wp_enqueue_style( 'enp-admin-styles');

    wp_register_style('enp-front-end-button-styles', plugins_url( 'enp-button/front-end/css/enp-button-style.css'));
    wp_enqueue_style( 'enp-front-end-button-styles');

    wp_register_script('enp-admin-scripts', plugins_url( 'enp-button/admin/js/enp-admin-scripts.js'), array( 'jquery' ), false, true );
    wp_enqueue_script( 'enp-admin-scripts');
}
add_action( 'admin_enqueue_scripts', 'enp_enqueue_admin_scripts' );


/**
* Step 3: Create the markup for the options page
*/
function enp_button_page() { ?>
<svg style="display: none;">
    <symbol id="icon-remove" viewBox="0 0 1024 1024">
        <path d="M512 42.667q95.667 0 182.5 37.167t149.667 100 100 149.667 37.167 182.5-37.167 182.5-100 149.667-149.667 100-182.5 37.167-182.5-37.167-149.667-100-100-149.667-37.167-182.5 37.167-182.5 100-149.667 149.667-100 182.5-37.167zM512 128q-78 0-149.167 30.5t-122.5 81.833-81.833 122.5-30.5 149.167 30.5 149.167 81.833 122.5 122.5 81.833 149.167 30.5 149.167-30.5 122.5-81.833 81.833-122.5 30.5-149.167-30.5-149.167-81.833-122.5-122.5-81.833-149.167-30.5zM632.667 348.333q17.667 0 30.333 12.5t12.667 30.167-12.667 30.333l-90.667 90.667 90.667 90.333q12.667 12.667 12.667 30 0 17.667-12.667 30.167t-30.333 12.5-30-12.333l-90.667-90.333-90.333 90.333q-12.333 12.333-30.333 12.333-17.667 0-30.167-12.333t-12.5-30q0-18 12.333-30.333l90.667-90.333-90.667-90.667q-12.333-12.333-12.333-30t12.5-30.333 30.167-12.667 30.333 12.667l90.333 90.667 90.667-90.667q12.667-12.667 30-12.667z"></path>
    </symbol>
    <symbol id="icon-add" viewBox="0 0 1024 1024">
        <path d="M512 42.667q95.667 0 182.5 37.167t149.667 100 100 149.667 37.167 182.5-37.167 182.5-100 149.667-149.667 100-182.5 37.167-182.5-37.167-149.667-100-100-149.667-37.167-182.5 37.167-182.5 100-149.667 149.667-100 182.5-37.167zM512 128q-78 0-149.167 30.5t-122.5 81.833-81.833 122.5-30.5 149.167 30.5 149.167 81.833 122.5 122.5 81.833 149.167 30.5 149.167-30.5 122.5-81.833 81.833-122.5 30.5-149.167-30.5-149.167-81.833-122.5-122.5-81.833-149.167-30.5zM512 298.667q17.667 0 30.167 12.5t12.5 30.167v128h128q17.667 0 30.167 12.5t12.5 30.167-12.5 30.167-30.167 12.5h-128v128q0 17.667-12.5 30.167t-30.167 12.5-30.167-12.5-12.5-30.167v-128h-128q-17.667 0-30.167-12.5t-12.5-30.167 12.5-30.167 30.167-12.5h128v-128q0-17.667 12.5-30.167t30.167-12.5z"></path>
    </symbol>
</svg>

<div class="wrap engaging-buttons-options">

    <form class="engaging-buttons-form" method="post" action="options.php">
        <h1>Engaging Buttons Settings</h1>

        <?
        // return all buttons and build off of current options
        $enp_buttons = get_option('enp_buttons');

        // general settings
        $btn_must_be_logged_in = get_option('enp_button_must_be_logged_in');
        $btn_allow_data_tracking = get_option('enp_button_allow_data_tracking');
        $btn_promote_enp = get_option('enp_button_promote_enp');

        // style settings
        $enp_btn_style = get_option('enp_button_style');
        $enp_btn_icons = get_option('enp_button_icons');

        if(empty($enp_btn_style) || $enp_btn_style === false) {
            $enp_btn_style = 'plain-buttons';
        }

        if($enp_btn_icons == 1) {
            $enp_btn_icon_class = 'enp-icon-state';
        } else {
            $enp_btn_icon_class = 'no-enp-icon-state';
        }

        // build the buttons form
        $registered_content_types = registeredContentTypes();?>

        <div class="enp-btn-global-settings-wrap">
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            Engaging Button Global Setting
                        </th>
                        <td>
                            <fieldset>
                                <label for="enp_button_must_be_logged-in">
                                    <input type="checkbox" name="enp_button_must_be_logged_in" <?php checked(true, $btn_must_be_logged_in);?> value="1" /> Users must be logged in to click the button(s)
                                </label>
                                <label for="enp_button_allow_data_tracking">
                                    <input type="checkbox" name="enp_button_allow_data_tracking" aria-describedby="enp-button-allow-data-tracking-description" <?php checked(true, $btn_allow_data_tracking);?> value="1" /> Allow data collection
                                    <p id="enp-button-allow-data-tracking-description" class="description">This allows the <a href="http://engagingnewsproject.org">Engaging News Project</a>, an academic nonprofit at the University of Texas at Austin, to record data on the buttons so they can continue to provide free, open-source plugins and research. No personal information is recorded. Learn more about what data is tracked and how it is used here.</p>
                                </label>
                                <label for="enp_button_promote_enp">
                                    <input type="checkbox" name="enp_button_promote_enp" aria-describedby="enp-button-promote-enp-description" <?php checked(true, $btn_promote_enp);?> value="1" /> Display "Respect Button Powered by the Engaging News Project"
                                    <p id="enp-button-promote-enp-description" class="description">Small text displayed beneath the WordPress comments section.</p>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="enp-btn-global-settings-wrap">
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            Engaging Button Style Setting
                            <div class="enp-btns-wrap <?echo $enp_btn_icon_class;?> enp-btn-view-<? echo $enp_btn_style;?>">
                                <ul class="enp-btns">
                                    <li class="enp-btn-wrap">
                                        <a href="#" class="enp-btn enp-btn--user-has-not-clicked"><span class="enp-btn__name">Respect</span><span class="enp-btn__count">75</span></a>
                                    </li>
                                </ul>
                            </div>
                        </th>
                        <td>
                            <fieldset>
                                <label>
                                    <input class="btn-style-input" type="radio" name="enp_button_style" aria-describedby="enp-button-style-description" value="plain-buttons" <? checked('plain-buttons', $enp_btn_style);?>/> Plain Buttons
                                </label>
                                <label>
                                    <input class="btn-style-input" type="radio" name="enp_button_style" aria-describedby="enp-button-style-description" value="count-block" <? checked('count-block', $enp_btn_style);?>/> Button with Block Count
                                </label>
                                <label>
                                    <input class="btn-style-input" type="radio" name="enp_button_style" aria-describedby="enp-button-style-description" value="count-block-inverse" <? checked('count-block-inverse', $enp_btn_style);?>/> Button with Block Count (Lighter Count Background)
                                </label>
                                <label>
                                    <input class="btn-style-input" type="radio" name="enp_button_style" aria-describedby="enp-button-style-description" value="count-curve" <? checked('count-curve', $enp_btn_style);?>/> Button with Curved Count
                                </label>
                                <label>
                                    <input class="btn-style-input" type="radio" name="enp_button_style" aria-describedby="enp-button-style-description" value="detached-count" <? checked('detached-count', $enp_btn_style);?>/> Button with Detached Count
                                </label>
                                <label>
                                    <input class="btn-style-input" type="radio" name="enp_button_style" aria-describedby="enp-button-style-description" value="plain-count-w-count-bg" <? checked('plain-count-w-count-bg', $enp_btn_style);?>/> Plain Text with Count Background
                                </label>
                                <p id="enp-button-style-description" class="description">Choose your preferred button style.</p>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                        </th>
                        <td>
                            <fieldset>
                                <label for="enp_button_icons">
                                    <input type="checkbox" class="btn-icon-input" name="enp_button_icons" <?php checked(true, $enp_btn_icons);?> value="1" /> Display Icons with Buttons
                                </label>
                            </fieldset>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>

        <h3>Your Site's Engaging Buttons</h3>

        <? buttonCreateForm($enp_buttons, $registered_content_types); ?>

        <?php settings_fields( 'enp_button_settings' ); ?>
        <?php do_settings_sections( 'enp_button_settings' ); ?>

        <?php submit_button(); ?>

    </form>

    <p>The Respect Button plugin is made by the <a href="http://engagingnewsproject.org">Engaging News Project</a>, a nonprofit at the University of Texas at Austin that researches COMMERCIALLY-VIABLE and DEMOCRATICALLY-BENEFICIAL ways to improve ONLINE NEWS</p>
</div>
<?php
}
