<?
/*
* Enp_Button Class
* Creates and allows access to the Enp_Button object for use by
* WordPress admin and front-end
*
* since v 0.0.1
*/

class Enp_Button {
    public $btn_slug;
    public $btn_name;
    public $btn_type;
    public $btn_count;
    public $btn_lock;


    /*$args = array(
                'post_id' => 4,
                'btn_slug' => 'respect',
                'btn_type' => 'post'
            );
    $test_btn = new Enp_Button($args);
    var_dump($test_btn);*/

    //public function __construct($slug = false, $is_comment = false) {
    public function __construct($args = array()) {
        // arguments for querying the Enp_Button object
        $default_args = array(
            'post_id' => false, // set to post or comment id
            'btn_slug' => false, // set to slug string or array of strings, "respect", "recommend", "important". also accepts array
            'btn_type' => false // slug of the post type. post, page, comment, or cpt slug
        );

        $args = array_merge($default_args, $args);

        if($args['btn_slug'] === false) {
            // return all buttons if they didn't specify which one
            // USAGE: $enp_btns = new Enp_Button();
            //        $enp_btns = $enp_btns->get_btns();
            $this->get_btns($args);

        } else {
            // get the one they asked for
            // USAGE: $enp_btn = new Enp_Button('respect');
            $enp_btn = $this->set_btn($args);

        }

    }

    /*
    NOT IN USE
    public function get_btn($args) {
        $enp_btn_values = get_option('enp_buttons_'.$slug);

        return $this->set_btn($enp_btn_values);
    }*/


    protected function set_btn($args) {


        // try/catch to return exception if no button is found
        try {
             // get the data from wp_options

            if($args['btn_slug'] !== false) {
                $slug = $args['btn_slug'];
                $enp_btn = get_option('enp_button_'.$slug);
            } else {
                $enp_btn = false;
                throw new Exception('Enp_Button: No btn_slug set.');
            }

            if($enp_btn !== false) {
                $this->btn_type  =  $this->set_btn_type($enp_btn, $args);
                // check to see if the button types return true
                if($this->btn_type !== false) {
                    $this->btn_slug  =  $this->set_btn_slug($enp_btn);
                    // set all the attributes
                    $this->btn_name  =  $this->set_btn_name($enp_btn);

                    $this->btn_count =  $this->set_btn_count($enp_btn, $args);
                    $this->btn_lock  =  $this->set_btn_lock();
                } else {
                    // throw new Exception('Enp_Button: No button found for that btn_type');
                }

            } else {
                throw new Exception('Enp_Button: No button found');
            }
        } catch(Exception $e) {
            // return our exception
            echo $e->getMessage();
        }

    }

    /*
    *   set the button slug for the Enp_Button object
    */
    protected function set_btn_slug($enp_btn) {
        $slug = false;
        if(isset($enp_btn['btn_slug'])) {
            $slug = $enp_btn['btn_slug'];
        }
        //var_dump($slug);
        return $slug;
    }

    /*
    *
    *   set the button name for the Enp_Button object
    *
    */
    protected function set_btn_name($enp_btn) {
        $name = false;
        if(isset($enp_btn['btn_name'])) {
            $name = $enp_btn['btn_name'];
        }

        return $name;
    }


    /*
    *
    *   set the button type for the current Enp_Button object
    *   as an array of types - ie - ['btn_type'] => array('comment' => false, 'posts' => true)
    *
    */
    protected function set_btn_type($enp_btn, $args) {
        $btn_type = false;

        if(isset($enp_btn['btn_type'])) {
            $btn_type = $enp_btn['btn_type'];
        }

        // check if our args match at all. If there's a match, then we can return the object
        if($btn_type !== false && $args['btn_type'] !== false) {
            $btn_type_match = false;
            // this is a string, so we can check if that btn_type is set to true
            // because this will check for $btn_type['comment'] = '1', etc
            if( $btn_type[$args['btn_type']] !== false ) {
                $btn_type_match = true;
            }
        } else {
            $btn_type_match = true; // because there's nothing requested (false),
                                    // not really a match but we should return the object
        }

        if($btn_type_match === true) {
            return $btn_type;
        } else {
            return false;
        }

        // TODO?
        // If a custom post type gets added, this will throw a PHP notice
        // that $btn_type['custom_post'] is not set
        // The way to set it would be loop through ALL active post types
        // with registeredContentTypes() and set any post types that aren't
        // set as false. It's an extra check for something that's not a big
        // deal though, so I'm not sure if it's worth the resources or not


    }


    /*
    *
    *   Set the btn count value
    *
    */
    protected function set_btn_count($enp_btn, $args) {
        $enp_btn_count = false;

        if($args['btn_type'] === 'comment') {
            if($args['post_id'] !== false) {
                $comment_id = $args['post_id'];
            } else {
                global $comment;
                $comment_id = $comment->comment_ID;
            }

            $enp_btn_count = get_comment_meta($comment_id, 'enp_button_'.$enp_btn['btn_slug'], true);
        } elseif(!is_admin()) {
            $post_id = false;

            if($args['post_id'] !== false) {
                $post_id = $args['post_id'];
            } else {
                global $post;
                $post_id = $post->ID;
            }

            if($post_id !== false) {
                // individual post button
                $enp_btn_count = get_post_meta($post_id,'enp_button_'.$enp_btn['btn_slug'], true);
            } else {
                // TODO: get a global count?
                // $enp_btn_count = get_option('enp_button_'.$enp_btn['btn_slug']);
            }

        }

        // default 0 if nothing is found/posted yet
        $count = 0;

        if($enp_btn_count !== false) {
            $count = (int) $enp_btn_count;
        }

        return $count;
    }

    /*
    *
    *   Set the btn lock value. if count is 0 or greater, lock it
    *
    */
    protected function set_btn_lock() {
        $lock = false;
        // if btn_count is greater than 0, lock it
        if($this->btn_count > 0) {
            $lock = true;
        }

        return $lock;
    }


    /*
    *
    *   returns the button slug for the current Enp_Button object
    *   USAGE: $enp_btn = new Enp_Button('respect');
    *          $enp_btn->get_btn_slug; // 'respect'
    *
    */
    public function get_btn_slug() {
        return $this->btn_slug;
    }

    /*
    *
    *   returns the button name for the current Enp_Button object
    *   USAGE: $enp_btn = new Enp_Button('respect');
    *          $enp_btn->get_btn_name; // 'Respect'
    *
    */
    public function get_btn_name() {
        return $this->btn_name;
    }

    public function get_btn_types() {
        return $this->btn_type;
    }

    public function get_btn_count() {
        return $this->btn_count;
    }

    public function get_btn_lock() {
        return $this->btn_lock;
    }

    /*
    *
    *   get an individual button type
    *   returns array of types - ie - array('comment' => false, 'posts' => true)
    *
    */
    public function get_btn_type($type = false) {
        $btn_type = $this->btn_type;
        $get_btn_type = false;

        if($type !== false && isset($btn_type[$type])) {
            $get_btn_type = $btn_type[$type];
        }

        return $get_btn_type;
    }


    /*
    *
    *   Return all button slugs from enp_button_slugs in an array
    *   Used by function get_btns()
    *
    */
    public function get_btn_slugs() {
        $enp_button_slugs = get_option('enp_button_slugs');

        return $enp_button_slugs;
    }

    /*
    *   Return all buttons as an array of individual objects
    *   (ie- $this->get_btns() = array([0]=> object(Enp_Button){[btn_slug]=>'', [btn_name]=>''},
    *            [1]=> object(Enp_Button){[btn_slug]=>'', [btn_name]=>''});
    *
    *   USAGE
    *   $enp_btns = new Enp_Button();
    *   $enp_btns = $enp_btns->get_btns();
    *   foreach($enp_btns as $enp_btn) {
    *       echo '<h1>'.$enp_btn->get_btn_name().'</h1>';
    *       // Outputs button name (ie- Recommend, Respect, Important, ...)
    *   }
    *
    */
    public function get_btns($args) {
        $enp_btns = $this->get_btn_slugs();

        $enp_btns_obj = array();

        foreach($enp_btns as $slug) {
            $args['btn_slug'] = $slug;
            $enp_btns_obj[] = new Enp_Button($args);
        }

        if($enp_btns_obj !== null) {
            $i = 0;
            foreach($enp_btns_obj as $obj) {
                 // remove any null objects
                if($obj->btn_slug === NULL) {
                    unset($enp_btns_obj[$i]); //removes the array at given index
                    $reindex = array_values($enp_btns_obj); //normalize index
                    $enp_btns_obj = $reindex; //update variable
                }

                $i++;
            }
        }

        return $enp_btns_obj;
    }

}



?>
