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

// Set Icon State to FALSE if empty
add_filter( 'pre_update_option_enp_button_icons', 'set_enp_button_icons', 10, 2 );
// Set Icon State to FALSE if empty
add_filter( 'pre_update_option_enp_button_font', 'set_enp_button_font', 10, 2 );

// Color manipulation and styles saving
add_filter( 'pre_update_option_enp_button_color', 'set_enp_button_color', 10, 2 );
// Color manipulation and styles saving
add_filter( 'pre_update_option_enp_button_color_clicked', 'set_enp_button_color_clicked', 10, 2 );
// Color manipulation and styles saving
add_filter( 'pre_update_option_enp_button_color_active', 'set_enp_button_color_active', 10, 2 );
// Color manipulation and styles saving
add_filter( 'pre_update_option_enp_button_color_css', 'set_enp_button_color_css', 10, 2 );


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
    register_setting( 'enp_button_settings', 'enp_button_font' );
    register_setting( 'enp_button_settings', 'enp_button_color' );
    register_setting( 'enp_button_settings', 'enp_button_color_clicked' );
    register_setting( 'enp_button_settings', 'enp_button_color_active' );
    register_setting( 'enp_button_settings', 'enp_button_color_css' );
}

// enqueue our scripts
function enp_enqueue_admin_scripts() {
    wp_register_style('enp-admin-styles', plugins_url( 'engaging-buttons/admin/css/enp-admin-styles.css'));
    wp_enqueue_style( 'enp-admin-styles');

    wp_register_style('enp-front-end-button-styles', plugins_url( 'engaging-buttons/front-end/css/enp-button-admin-button-styles.min.css'));
    wp_enqueue_style( 'enp-front-end-button-styles');

    // Add the color picker css file
    wp_enqueue_style( 'wp-color-picker' );

    wp_register_script('enp-admin-scripts', plugins_url( 'engaging-buttons/admin/js/enp-admin-scripts.js'), array( 'jquery', 'wp-color-picker' ), false, true );
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
    <!-- SVG Icons for Engaging Buttons -->
    <symbol id="enp-btn--user-has-not-clicked" viewBox="0 0 1024 1024">
        <path d="M819.2 512c0 28.314-2.458 51.2-30.771 51.2h-225.229v225.229c0 28.262-22.886 30.771-51.2 30.771s-51.2-2.509-51.2-30.771v-225.229h-225.229c-28.262 0-30.771-22.886-30.771-51.2s2.509-51.2 30.771-51.2h225.229v-225.229c0-28.314 22.886-30.771 51.2-30.771s51.2 2.458 51.2 30.771v225.229h225.229c28.314 0 30.771 22.886 30.771 51.2z"></path>
    </symbol>
    <symbol id="enp-btn--user-clicked" viewBox="0 0 1024 1024">
        <path class="path1" d="M424.653 870.298c-22.272 0-43.366-10.394-56.883-28.314l-182.938-241.715c-23.808-31.386-17.613-76.083 13.824-99.891 31.488-23.91 76.186-17.613 99.994 13.824l120.371 158.925 302.643-485.99c20.838-33.382 64.87-43.622 98.355-22.784 33.434 20.787 43.725 64.819 22.835 98.304l-357.581 573.952c-12.39 20.019-33.843 32.512-57.344 33.587-1.126 0.102-2.15 0.102-3.277 0.102z"></path>
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
        $enp_btn_font = get_option('enp_button_font');
        $enp_btn_color = get_option('enp_button_color');
        $enp_btn_color_clicked = get_option('enp_button_color_clicked');
        $enp_btn_color_active = get_option('enp_button_color_active');

        if(empty($enp_btn_style) || $enp_btn_style === false) {
            $enp_btn_style = 'ghost';
        }

        if($enp_btn_icons === '0') {
            $enp_btn_icon_class = 'no-enp-icon-state';
            $enp_btn_icons = false;
        } else {
            $enp_btn_icon_class = 'enp-icon-state';
            $enp_btn_icons = true;
        }

        // used to eval if the checkbox should be on or off
        if($enp_btn_font === 'open_sans') {
            $enp_btn_font = true;
        } else {
            $enp_btn_font = false;
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
                                    <input type="checkbox" id="enp_button_must_be_logged-in" name="enp_button_must_be_logged_in" <?php checked(true, $btn_must_be_logged_in);?> value="1" /> Users must be logged in to click the button(s)
                                </label>
                                <label for="enp_button_allow_data_tracking">
                                    <input type="checkbox" id="enp_button_allow_data_tracking" name="enp_button_allow_data_tracking" aria-describedby="enp-button-allow-data-tracking-description" <?php checked(true, $btn_allow_data_tracking);?> value="1" /> Allow data collection
                                    <p id="enp-button-allow-data-tracking-description" class="description">This allows <a href="http://engagingnewsproject.org">The Engaging News Project</a>, an academic nonprofit at the University of Texas at Austin, to record data on the buttons so they can continue to provide free, open-source plugins and research. No personal information is recorded.</p>
                                </label>
                                <label for="enp_button_promote_enp">
                                    <input type="checkbox" id="enp_button_promote_enp" name="enp_button_promote_enp" aria-describedby="enp-button-promote-enp-description" <?php checked(true, $btn_promote_enp);?> value="1" /> Display "Respect Button Powered by the Engaging News Project"
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
                            Engaging Button Styles
                            <div class="enp-btn-view enp-btn-view-<? echo $enp_btn_style;?>">
                                <div class="enp-btns-wrap <?echo $enp_btn_icon_class;?>">
                                    <ul class="enp-btns">
                                        <li class="enp-btn-wrap">
                                            <a href="#" class="enp-btn enp-btn--user-has-not-clicked"><svg class="enp-icon"><use xlink:href="#enp-btn--user-has-not-clicked"></use></svg><span class="enp-btn__name">Respect</span><span class="enp-btn__count">75</span></a>
                                        </li>
                                    </ul>
                                </div>
                        </th>
                        <td>
                            <fieldset>
                                <p id="enp-button-style-description" class="description">Choose your preferred button style.</p>
                                <select class="btn-style-input" name="enp_button_style" aria-describedby="enp-button-style-description">
                                    <option value="ghost" <? selected('ghost', $enp_btn_style);?>/> Ghost
                                    </option>
                                    <option value="plain-buttons" <? selected('plain-buttons', $enp_btn_style);?>/> Plain Buttons
                                    </option>
                                    <option value="count-block" <? selected('count-block', $enp_btn_style);?>/> Button with Block Count
                                    </option>
                                    <option value="count-block-inverse" <? selected('count-block-inverse', $enp_btn_style);?>/> Button with Block Count (Lighter Count Background)
                                    </option>
                                    <option value="count-curve" <? selected('count-curve', $enp_btn_style);?>/> Button with Curved Count
                                    </option>
                                    <option value="detached-count" <? selected('detached-count', $enp_btn_style);?>/> Button with Detached Count
                                    </option>
                                    <option value="plain-text-w-count-bg" <? selected('plain-text-w-count-bg', $enp_btn_style);?>/> Plain Text with Count Background
                                    </option>
                                </select>

                                <label for="enp_button_icons">
                                    <input type="checkbox" class="btn-icon-input" id="enp_button_icons" name="enp_button_icons" <?php checked(true, $enp_btn_icons);?> /> Display Icons with Buttons
                                </label>
                                <label class="enp-button-font-label" for="enp_button_font">
                                    <input type="checkbox" id="enp_button_font"  aria-describedby="enp-button-font-description" name="enp_button_font" <?php checked(true, $enp_btn_font);?> /> Use Open Sans Font
                                </label>
                                <p id="enp-button-font-description" class="description">Use Open Sans if the buttons don't look right on your site.</p>


                            </fieldset>

                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="enp_button_color">
                                Button Color
                            </label>
                        </th>
                        <td>
                            <fieldset>
                                <input type="text" maxlength="7" class="btn-color-input" id="enp_button_color" name="enp_button_color" value="<?php echo $enp_btn_color;?>" />
                                <input type="hidden" maxlength="7" class="btn-color-clicked-input" name="enp_button_color_clicked" value="<?php echo $enp_btn_color_clicked;?>" />
                                <input type="hidden" maxlength="7" class="btn-color-active-input" name="enp_button_color_active" value="<?php echo $enp_btn_color_active;?>" />
                            </fieldset>

                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Advanced CSS</th>
                        <td>
                            <button class="advanced-css-control button-secondary">Show Advanced CSS</button>
                            <div class="advanced-css">
                                <label for="enp-css">This is the CSS that will be added to your site. If you want to change it more, copy/paste this CSS into your theme's CSS file and edit away!</label>
                                <textarea name="enp_button_color_css" class="wide-fat enp-css" id="enp-css" wrap="off" rows="15" readonly></textarea></td>
                            </div>
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
