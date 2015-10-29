<?
/*
*   Front End Display Functions
*   Add functionality to the front-end of the website
*
*   since v0.0.1
*/





/*
*
*   Get all buttons. Doesn't really save much time, but
*   could be useful for adding a filter or hook later
*
*/

function enp_get_all_btns($args) {
    // we don't want to get any buttons that aren't a part of that post type
    // rather, we ONLY want ones that ARE a part of that post type

    $enp_btns = new Enp_Button($args);
    $enp_btns = $enp_btns->get_btns($args);

    return $enp_btns;
}

/*
*
*   Get a button by slug. Doesn't really save any time, but
*   could be useful for adding a filter or hook later
*
*/
function enp_get_btn($args) {
    $enp_btns = new Enp_Button($args);

    return $enp_btns;
}


/*
*
*   Get all Buttons, and append them to appropriate content
*
*/
function enp_append_post_btns( $content ) {
    global $post;
    $post_id = $post->ID;
    $post_type = get_post_type( $post );

    $args = array(
                'post_id' => $post_id,
                'btn_type' => $post_type
            );

    $content .= enp_btns_HTML($args);

    return $content;
}
add_filter( 'the_content', 'enp_append_post_btns' );

/*
*
*   Get all Buttons, and append them to each comment
*
*/
function enp_append_comment_btns( $content ) {
    global $comment;
    $comment_id = $comment->comment_ID;

    $args = array(
                'post_id' => $comment_id,
                'btn_type' => 'comment'
            );

    $content .= enp_btns_HTML($args);

    return $content;
}
add_filter( 'comment_text', 'enp_append_comment_btns' );



/*
*
*   Generate enp button html and return it
*
*/
function enp_btns_HTML($args) {
    // get the user
    $enp_user = new Enp_Button_User();

    $enp_btn_HTML = '';

    // get the button objects
    $enp_btns = enp_get_all_btns($args);

    if(empty($enp_btns)) {
        return false; // quit now if there aren't any buttons
    }

    // classes array for outputting in our HTML
    $classes = ["enp-btns"];

    if($args['btn_type'] === 'comment') {
        $btn_type = 'comment';
    } else {
        $btn_type = 'post';
    }

    $classes[] = 'enp-btns-'.$btn_type.'-'.$args['post_id'];

    // check if logged in is set
    $enp_btn_clickable = enp_btn_clickable();

    // check if the first one is full of null values
    if(enp_button_exists($enp_btns[0])) {
        $enp_btn_HTML = '<div id="enp-btns-wrap-'.$btn_type.'-'.$args['post_id'].'" class="enp-btns-wrap" data-btn-type="'.$btn_type.'">
                            <ul class="';
        foreach($classes as $class) {
            $enp_btn_HTML .= $class.' ';
        }
        $enp_btn_HTML .= '">';

        foreach($enp_btns as $enp_btn) {
            if(enp_button_exists($enp_btn)) {
                $enp_btn_HTML .= enp_btn_append_btn_HTML($enp_btn, $args, $enp_btn_clickable, $enp_user);
            }

            // process button names to pass to the Promote option later
            $enp_btn_names[] = $enp_btn->get_btn_name();
        }

        $enp_btn_HTML .= '</ul>';

        $enp_btn_HTML .= enp_user_clicked_buttons_HTML($enp_user, $enp_btns, $btn_type, $args['post_id']);

        if($enp_btn_clickable === false) {
            // append a login link and message
            // redirect them back to this button section
            if($btn_type === 'post') {
                $redirect = get_permalink().'/#enp-btns-wrap-'.$btn_type.'-'.$args['post_id'];
            } elseif($btn_type === 'comment') {
                $redirect = get_comment_link($args['post_id']);
            } else {
                // hmmm... which type are they on?
                $redirect = site_url();
            }

            $enp_btn_HTML .= '<p class="enp-btn-hint enp-hint--please-log-in">Please <a href="'.wp_login_url( $redirect ).'">Log In</a> to click the buttons</p>';
        }


        if($btn_type === 'post' && !empty($enp_btn_names)) {
            //check if promote_enp option is set
            $promote_enp = promote_enp();

            if($promote_enp === true) {
                $return = true;
                $enp_btn_HTML .= promote_enp_HTML($enp_btn_names, $return); // true returns instead of echos
            }
        }


        $enp_btn_HTML .= '</div>'; // close enp-btns-wrap


    }

    return $enp_btn_HTML;
}



/*
*
*   ENP Btn HTML for displaying on front-end
*
*/
function enp_btn_append_btn_HTML($enp_btn, $args, $enp_btn_clickable, $enp_user) {

    $post_id = $args['post_id'];
    // Create a nonce for this action
    if($args['btn_type'] === 'comment') {
        $type = 'comment';
    } else {
        $type = 'post';
    }

    // should we increase or decrease this count?
    $user_args = array(
        'btn_slug' => $enp_btn->get_btn_slug(),
        'btn_type' => $type,
        'post_id' => $args['post_id']
    );
    if($enp_user->has_user_clicked($user_args) === true) {
        $operator = '-';
        $click_class = 'enp-btn--user-clicked';
    } else {
        $operator = '+';
        $click_class = 'enp-btn--user-has-not-clicked';
    }

    $nonce = wp_create_nonce( 'enp_button_'.$type.'_'.$enp_btn->get_btn_slug().'_' . $post_id );
    // Get link to admin page to trash the post and add nonces to it
    $link_data = '<a href="?action=enp_update_button_count&slug='.$enp_btn->get_btn_slug().'&type='.$type.'&pid='. $post_id .'&nonce=' .$nonce . '"
            id="'.$enp_btn->get_btn_slug().'_'.$type.'_'. $post_id.'" class="enp-btn enp-btn--'.$enp_btn->get_btn_slug().' enp-btn--'.$type. ($enp_btn_clickable === false ? ' enp-btn--require-logged-in' : '').' '.$click_class.'" data-nonce="'. $nonce .'" data-pid="'. $post_id .'" data-btn-type="'.$type.'" data-btn-slug="'.$enp_btn->get_btn_slug().'" data-operator="'.$operator.'">';

    // while hard to read, this format is necessary with no breaks between span tags.
    // otherwise, WordPress's filter will add <br/>'s there. No good.
    $enp_btn_HTML = '<li id="'.$enp_btn->get_btn_slug().'-wrap" class="enp-btn-wrap enp-btn-wrap--'.$enp_btn->get_btn_slug().'">'.$link_data.'<span class="enp-btn__name enp-btn__name--'.$enp_btn->get_btn_slug().'">'
                                        .$enp_btn->get_btn_name().
                                    '</span><span class="enp-btn__count enp-btn__count--'.$enp_btn->get_btn_slug().($enp_btn->get_btn_count() > 0 ? '' : ' enp-btn__count--zero').'">'
                                        .$enp_btn->get_btn_count().'</span></a>
                            </li>';

    return $enp_btn_HTML;
}




/*
*
*   Basic check to make sure that our object isn't full of null values
*
*/
function enp_button_exists($enp_btn) {

    if($enp_btn->get_btn_slug() === NULL) {
        return false;
    } else {
        return true;
    }
}


/*
*
*   Return HTML to display if a user has clicked a button
*
*/

function enp_user_clicked_buttons_HTML($enp_user, $enp_btns, $btn_type, $post_id) {
    $user_clicked_btn_names = array();
    $user_clicked_btns_HTML = '';

    foreach($enp_btns as $enp_btn) {
        $args = array(
            'btn_slug' => $enp_btn->get_btn_slug(),
            'btn_type' => $btn_type,
            'post_id' => $post_id
        );
        if($enp_user->has_user_clicked($args) === true) {
            $user_clicked_btn_names[] = $enp_btn->get_btn_name();
        }
    }

    if(!empty($user_clicked_btn_names)) {

        $user_clicked_btns_HTML = enp_user_clicked_btns_text($user_clicked_btn_names, $btn_type);


    }


    return $user_clicked_btns_HTML;


}


function enp_user_clicked_btns_text($user_clicked_btn_names, $btn_type) {

    $user_clicked_btns_text = '';
    $important_text = '';

    if(!empty($user_clicked_btn_names)) {
        $user_clicked_btns_text .= '<p class="enp-btn-hint enp-user-clicked-hint">';

        $key = array_search("Important", $user_clicked_btn_names);
        if($key !== false) { // Important is found
            $important_text = 'This '.$btn_type.' is Important to you.';
            // remove it from the array
            array_splice($user_clicked_btn_names, $key, 1);
        }

        // check if the array is still not empty after potentially removing "Important"

        if(!empty($user_clicked_btn_names)) {
            $user_clicked_btns_text .= 'You ';

            $user_clicked_btns_text .= enp_build_name_text($user_clicked_btn_names);

            $user_clicked_btns_text .= ' this '.$btn_type.'.';
        }

        if(!empty($user_clicked_btns_text) && !empty($important_text)) {
            // add a space before the important text;
            $important_text = ' '.$important_text;
        }

        $user_clicked_btns_text .= $important_text;

        $user_clicked_btns_text .= '</p>';
    }

    return $user_clicked_btns_text;
}



/*
*
*   bool check to see if the promote Engaging News Project option is checked (true)
*
*/
function promote_enp() {
    $promote_enp = get_option('enp_button_promote_enp');
    if( $promote_enp == 1 ) {
        $promote_enp = true;
    } else {
        $promote_enp = false;
    }

    return $promote_enp;
}

/*
*
*   HTML for promote enp. Possibly add a filter so people can change the text?
*
*/
function promote_enp_HTML($enp_btn_names = false, $return = false) {

    if($enp_btn_names === false || empty($enp_btn_names)) {
        // we're in the comments section... gotta find all our button names
        $args = array('btn_type' => 'comment');

        // get all buttons that are active for comments
        $enp_btns = enp_get_all_btns($args);
        if($enp_btns === null) {
            return false; // quit now if it's all null
        }
        // check to make sure it's not just null values
        // check to make sure it's not just null values
        if(enp_button_exists($enp_btns[0])) {

            foreach($enp_btns as $enp_btn) {
                if(enp_button_exists($enp_btn)) {
                    $enp_btn_names[] = $enp_btn->get_btn_name();
                }
            }
        }


    }


    // Return Array of buttons being displayed
    $enp_btn_name_text = '';
    if(!empty($enp_btn_names)) {


        $enp_btn_name_text = enp_build_name_text($enp_btn_names);

        $names_count = count($enp_btn_names);
        if($names_count === 1 || $names_count === 0) {
            $button_pluralize = '';
        } else {
            $button_pluralize = 's';
        }
    }


    $promote_HTML = '<p class="enp-promote">'.$enp_btn_name_text.' Button'.$button_pluralize.' powered by the <a href="http://engagingnewsproject.org">Engaging News Project</a></p>';

    if($return === true) {
        return $promote_HTML; // return for appending to HTML
    } else {
        echo $promote_HTML; // echo for action hooks
    }
}


function enp_build_name_text($names) {
    $names_count = count($names);
    $name_text = '';
    $i = 1;
    foreach($names as $name) {
        // figure out if we need a comma, 'and', or nothing

        // we're on the last one (or first one)
        if($i === $names_count) {
            if($names_count > 2) {
                $name_text .= 'and '.$name;
            } elseif($names_count > 1) {
                $name_text .= 'and '.$name;
            } else { // first and last (only one))
                $name_text .= $name;
            }
        } elseif($i === 1) { // we're on the first one
                if($names_count > 2) {
                    $name_text .= $name.', '; // first one, and more to come
                } else {
                    $name_text .= $name.' '; // first one and only two
                }
        } else { // we're not on the first or last, so put a comma in there
            $name_text .= $name.', ';
        }

        $i++;
    }

    return $name_text;
}

/*
*
*   Append promote Engaging News Project to comments
*   There's no hook for after the comment list, so we have to inject it BEFORE the comment form
*   and hope the theme's formatting isn't too wonky
*/
add_action( 'comment_form_before', 'promote_enp_HTML');

?>
