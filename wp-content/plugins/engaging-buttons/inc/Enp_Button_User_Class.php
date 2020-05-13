<?php
/*
* Enp_Button Class
* Creates and allows access to the Enp_Button object for use by
* WordPress admin and front-end
*
* since v 0.0.2
*/

class Enp_Button_User {
    public $user_id;
    // public $respect, $recommend, etc - We're dynamically creating objects based on the btn_slugs they want

    public function __construct($args = array()) {
        // arguments for querying the Enp_Button_user object
        $default_args = array(
            'user_id' => get_current_user_id(), // try to set the current user id
            'btn_slug' => false, // set to slug string or array of strings, "respect", "recommend", "important". also accepts array
            'btn_type' => false // post or comment. we don't care if it's a page or cpt
        );
        // merge the default_args and args arrays
        $args = array_merge($default_args, $args);

        $this->user_id = $args['user_id'];
        // sets both $this->clicked_posts and $this->clicked_comments
        $this->set_user_clicks($args);

    }

    protected function set_user_clicks($args) {

        $btn_slugs = $this->set_btn_slugs($args);
        if($btn_slugs === false) {
            return false; // no slugs, get outta
        }

        $btn_types = $this->set_btn_types($args);

        $clicked_btns = array();

        // if we have a string, lets turn it into an array to keep our processing
        // code more DRY
        if(!is_array($btn_slugs)) {
            $btn_slugs = array($btn_slugs);
        }
        if(!is_array($btn_types)) {
            $btn_types = array($btn_types);
        }

        foreach($btn_slugs as $btn_slug) {
            // create an empty array for this btn_slug
            ${'clicked_'.$btn_slug} = array();
            // loop through the slugs
            foreach($btn_types as $btn_type) {
                // for each btn_type, push the user meta for that button to the clicked_btn_slug array as an associative array
                ${'clicked_'.$btn_slug}[$btn_type] = get_user_meta($this->user_id, 'enp_button_'.$btn_type.'_'.$btn_slug, true);
            }
            // dynamic class variable name... ugly, but awesome
            // sets $this->recommend or $this->respect, etc, as the
            // associative array we created
            $this->$btn_slug = ${'clicked_'.$btn_slug};
        }


    }

    protected function set_btn_slugs($args) {
        $btn_slugs = $args['btn_slug'];
        if($btn_slugs === false) {
            $btn_slugs = get_option('enp_button_slugs');
        }

        return $btn_slugs;
    }

    protected function set_btn_types($args) {
        $btn_types = $args['btn_type'];
        if($btn_types === false) {
            // set it as both post and comment
            $btn_types = array('post', 'comment');
        }

        return $btn_types;
    }


    /*
    *   A function for getting the clicks you want
    *   returns an array of all clicks for that button for the requested user
    *
    *   USAGE: $btn_user = new Enp_Button_User();
    *          $respect_post_clicks = $btn_user->get_user_clicks('respect', 'post');
    *          $respect_clicks = $btn_user->get_user_clicks('respect');
    *
    */
    public function get_user_clicks($btn_slug, $btn_type = false) {
        //var_dump($this);
        if($btn_type !== false && $btn_slug !== null) {
            // This gives us an illegal offset error
            // $user_clicks = $this->$btn_slug[$btn_type];
            // But settings the user_clicks, then getting user_clicks[$btn_type]
            // doesn't throw the error for some reason
            $user_clicks = $this->$btn_slug;
            $user_clicks = $user_clicks[$btn_type];
        } else {
            $user_clicks = $this->$btn_slug;
        }

        return $user_clicks;

    }

    /*
    *
    *   Bool check to see if a user has clicked a button or not
    *
    */
    public function has_user_clicked($btn, $args) {
        // check to see if there's even a user and that the btn is active
        if($this->user_id == 0 ) {
            return false;
        }

        $default_args = array(
            'btn_slug' => false, // set to slug string or array of strings, "respect", "recommend", "important". also accepts array
            'btn_type' => false, // post or comment. we don't care if it's a page or cpt
            'post_id' => false // the post id of the button you want to see if they've clicked
        );

        // merge the default_args and args arrays
        $args = array_merge($default_args, $args);

        if($this->is_btn_active($btn, $args) === false) {
            return false;
        }

        // get the user's clicks
        $user_clicks = $this->get_user_clicks($args['btn_slug'], $args['btn_type']);

        if(empty($user_clicks)) {
            // there's no value set yet
            return false;
        };

        // see if the post id is in their array for those button clicks
        return in_array($args['post_id'], $user_clicks);
    }


    public function is_btn_active($btn, $args) {

        if($btn->btn_type === false) {
            return false;
        }

        $is_active = false;



        if($args['btn_type'] === 'comment') {
            // check if the comments are active
            if($btn->btn_type['comment'] === '1') {
                $is_active = true;
            }
        } else {
            // check if ANY btn_type is active
            $is_active = in_array('1', $btn->btn_type);
        }


        return $is_active;
    }

}

?>
