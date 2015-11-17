<?
/*
*   Ajax Save Functions
*   Receive, process, and save data from Ajax Requests
*
*   since v0.0.1
*/


/*
*
*   Increase the click count by one before saving
*
*/
function changeClickCount($prev_clicks, $operator) {
    $prev_clicks = (int)$prev_clicks;

    if($operator === '+') { // add
        if($prev_clicks !== false) {
            $new_clicks = $prev_clicks + 1;
        } else {
            $new_clicks = 1;
        }
    } else { // $operator = '-' // subtract
        $new_clicks = $prev_clicks - 1;
    }

    return $new_clicks;
}


/*
*
*   Ajax increase button count on click
*
*/

add_action( 'wp_ajax_enp_update_button_count', 'enp_update_button_count' );
add_action( 'wp_ajax_nopriv_enp_update_button_count', 'enp_update_button_count_not_logged_in' );

function enp_update_button_count_not_logged_in() {
    // check if logged in is set
    $require_logged_in = enp_require_logged_in();

    if($require_logged_in === true) {
        // throw an error
        $btn_slug = $_REQUEST['slug'];
        $pid = $_REQUEST['pid'];
        $btn_type = $_REQUEST['type'];

        // redirect url
        if($btn_type == 'post') {
            $redirect = get_permalink($pid).'/#enp-btns-wrap-'.$btn_type.'-'.$pid;
        } elseif($btn_type == 'comment') {
            $redirect = get_comment_link($pid);
        } else {
            // what kind are we on then?
            $redirect = site_url();
        }

        $login_url = wp_login_url($redirect);


        // return response
        die(json_encode(
            array(
                'response_status'=>'error',
                'pid' => $pid,
                'btn_type'=> $btn_type,
                'btn_slug'=> $btn_slug,
                'message' => 'You must be <a href="'.$login_url.'">logged in</a> to click this button. Please <a href="'.$login_url.'">Log In</a> and try again.'
            )
        ));

    } elseif($require_logged_in === false) {
        // they're not logged in, and we're not requiring logged in, so we can run this
        enp_update_button_count();
    }

}


function enp_update_button_count() {
    $pid = $_REQUEST['pid'];
    $btn_slug = $_REQUEST['slug'];
    $btn_type = $_REQUEST['type']; // post or comment? We don't need the specific post type
    $operator = $_REQUEST['operator'];
    $user_id = $_REQUEST['user_id'];

    enp_process_update_button_count($pid, $btn_slug, $btn_type, $operator, $user_id);
}



function enp_process_update_button_count($pid, $btn_slug, $btn_type, $operator, $user_id) {
    // Instantiate WP_Ajax_Response
    $response = new WP_Ajax_Response;

    // Verify Nonces
    if(wp_verify_nonce( $_REQUEST['nonce'], 'enp_button_'.$btn_type.'_'.$btn_slug.'_' . $pid )) {
        global $wpdb;

        if($btn_type === 'post') {
            // set our function names
            $get_meta = 'get_post_meta';
            $update_meta = 'update_post_meta';
        } elseif($btn_type === 'comment') {
            // set our function names
            $get_meta = 'get_comment_meta';
            $update_meta = 'update_comment_meta';
        } else {
            // wait, what kind of post is it then?
            return;
        }

        // get post or comment meta and update it
        $prev_clicks = $get_meta( $pid, 'enp_button_'.$btn_slug, true);

        // increase the click by one
        $new_clicks = changeClickCount($prev_clicks, $operator);

        // update the post or comment meta
        $update_meta( $pid, 'enp_button_'.$btn_slug, $new_clicks );

        // switch the operator for the next post
        if($operator === '+') {
            $new_operator = '-';
        } else {
            $new_operator = '+';
        }


        // update the user if there's an ID to use
        $enp_clicked_btn_HTML = '';
        if( $user_id !== '0' ) {

            // get their previous clicks
            $user_clicks = get_user_meta($user_id, 'enp_button_'.$btn_type.'_'.$btn_slug, true);
            if(empty($user_clicks)) {
                // if it's empty, it'll return an empty string, but we want an array
                $user_clicks = array();
            }
            // are we increasing or decreasing?
            if($operator === '+') {
                // add the Button ID to the Array
                $key = array_search($pid, $user_clicks);

                if($key === false) {
                    // They haven't clicked this one before, so add it
                    $user_clicks[] = $pid;
                }
            } else {
                // search the array and return the key/index
                $key = array_search($pid, $user_clicks);
                if($key !== false) {
                    // remove this one from the array
                    array_splice($user_clicks, $key, 1);
                }
            }
            update_user_meta( $user_id, 'enp_button_'.$btn_type.'_'.$btn_slug, $user_clicks );

            // Build button message text
            $args = array(
                        'post_id' => $pid,
                        'btn_type' => $btn_type
                    );
            $enp_btns = enp_get_all_btns($args);
            $enp_user = new Enp_Button_User(array('user_id' => $user_id));
            $enp_clicked_btn_HTML = enp_user_clicked_buttons_HTML($enp_user, $enp_btns, $btn_type, $pid);

        } else {
            $enp_clicked_btn_HTML = ''; // we need to check localStorage for button clicks
        }

        // update our rebuild flag
        update_option('enp_rebuild_popular_data', '1');

        $response->add( array(
            'data'  => 'success',
            'supplemental' => array(
                'pid' => $pid,
                'slug' => $btn_slug,
                'type' => $btn_type,
                'message' => 'The click on '.$pid.' has been registered!',
                'count' => $new_clicks,
                'old_count' => $prev_clicks,
                'new_operator' => $new_operator,
                'user_clicked_message' => $enp_clicked_btn_HTML
                ),
            )
        );
    } else {
        $response->add( array(
            'data'  => 'error',
            'supplemental' => array(
                'pid' => $pid,
                'slug' => $btn_slug,
                'type' => $btn_type,
                'message' => 'We couldn\'t update the '.ucfirst($btn_slug).' button count. Reload this page and try again.',
                ),
            )
        );
    }
    // Send the response back
    $response->send();

    // Always end with an exit on ajax
    exit();
}

/*
*
*   Ajax increase button count on click
*
*/

add_action( 'wp_ajax_enp_send_button_count', 'enp_send_button_count' );
add_action( 'wp_ajax_nopriv_enp_send_button_count', 'enp_send_button_count' );

function enp_send_button_count() {
    $pid = $_REQUEST['pid'];
    $btn_slug = $_REQUEST['slug'];
    $btn_type = $_REQUEST['type']; // post or comment? We don't need the specific post type

    // Instantiate WP_Ajax_Response
    $response = new WP_Ajax_Response;

    // check to see if they're allowing us to collect data.
    $send_enp_data = get_option('enp_button_allow_data_tracking');

    if($send_enp_data === '1') {
        // url
        if($btn_type == 'comment') {
            $button_url = get_comment_link($pid);
        } else {
            $button_url = get_permalink($pid);
        }

        // send the data to engaging news project for research
        $data = array(
                'button_id' => $pid,
                'slug'      => $btn_slug,
                'type'      => $btn_type,
                'button_url'=> $button_url
            );

        $send = new Enp_Send_Data();
        $send->send_click_data($data);

        $response->add( array(
            'data'  => 'success',
            'supplemental' => array(
                'message' => 'Click data has been sent to the Engaging News Project.',
                ),
            )
        );
    } else {
        $response->add( array(
            'data'  => 'error',
            'supplemental' => array(
                'message' => 'Sending click data is disabled.',
                ),
            )
        );
    }

    // Send the response back
    $response->send();

    // Always end with an exit on ajax
    exit();
}

?>
