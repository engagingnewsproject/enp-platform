<?
/*
*   popular-button-save.php
*   Functions for processing and saving the popular buttons arrays to wp_options table
*
*   since v 0.0.3
*/


// Main wrapper function for building all the popular button data
function enp_popular_button_save() {

    // get all our active slugs
    $enp_button_slugs = get_option('enp_button_slugs');

    // Quit the call if there are no settings
    if($enp_button_slugs === false) {
        return false;
    }

    // loop through each active button slug
    foreach($enp_button_slugs as $btn_slug) {
        // set flag to see if we should process comments
        $process_comments = false;

        // generate the meta_key we want
        $meta_key = 'enp_button_'.$btn_slug;

        // get our active button types
        $btn_info = get_option($meta_key);

        // loop through each button type
        foreach($btn_info['btn_type'] as $key=>$value) {

            // check if the button type is active
            if($value === '1') {

                $popular_args = array(
                    'meta_key'      => $meta_key,
                    'orderby'       => 'meta_value_num',
                    'order'         => 'DESC'
                );

                // if it's a comment, we need to process it differently
                if($key === 'comment') {
                    $process_comments = true;
                } else {
                    // reset the array with the new post type
                    $popular_post_args = array( 'post_type'     => $key,
                                                'posts_per_page'=> 20 // TODO: Override this via Admin option;
                                              );
                    $popular_post_args = array_merge($popular_args, $popular_post_args);
                    enp_popular_posts_save($btn_slug, $popular_post_args);
                }

            } // end if

        } // end foreach btn_type

        if($process_comments === true) {
            // strip out popular args post type
            enp_popular_comments_save($btn_slug, $btn_info['btn_type'], $popular_args);
        }

        // pass the slug to our all posts save function
        enp_all_popular_posts_save($btn_slug, $btn_info);

    } // end foreach btn_slug



}

//
function enp_popular_comments_save($btn_slug, $post_types, $args) {

    // all comments by btn slug (combines pages, posts, etc. anywhere the button is shown)
    $comment_args = array(
            'fields' => 'ids',
            'status' => 'approve',
            'number' => 20 // TODO: Override this via Admin option
        );

    $args = array_merge($comment_args, $args);

    $comments_query = new WP_Comment_Query;
    $comments = $comments_query->query( $args );


    $popular_comments = enp_build_popular_array($btn_slug, $comments, 'comment');

    update_option('enp_button_popular_'.$btn_slug.'_comments', $popular_comments);


    // Loop through all the passed post_types and
    // save all comments by post type
    // ex: enp_button_popular_respect_page_comments
    foreach($post_types as $key=>$value) {
        // check if the button type is active
        if($value === '1' && $key !== 'comment') {
            // build the arguments
            $post_type_args = array('post_type'=>$key);
            $post_type_args = array_merge($args, $post_type_args);
            // generate the query
            $comments_query = new WP_Comment_Query;
            $comments = $comments_query->query( $post_type_args );
            // build the array of popular ids and counts
            $popular_comments = enp_build_popular_array($btn_slug, $comments, 'comment');
            // save the array
            update_option('enp_button_popular_'.$btn_slug.'_'.$key.'_comments', $popular_comments);

        }
    }
}


/*
*   Save most clicked by slug and post type in wp_options
*   'enp_button_popular_'.$btn_slug.'_'.$post_type
*/
function enp_popular_posts_save($btn_slug, $args) {

    $pop_posts = get_posts( $args );
    $post_type = $args['post_type'];

    $popular_posts = enp_build_popular_array($btn_slug, $pop_posts, $post_type);

    update_option('enp_button_popular_'.$btn_slug.'_'.$post_type, $popular_posts);

    // Restore original Post Data
    wp_reset_postdata();
}



/*
*   Loop through the returned ids, get the count, and return
*   an array of arrays of ids + button count
*/
function enp_build_popular_array($btn_slug, $pop_posts, $post_type) {
    $popular_array = array();

    foreach ($pop_posts as $pop) {
        if($post_type === 'comment') {
            $id = $pop;
            $label = 'comment';
        } else {
            $id = $pop->ID;
            $label = 'post';
        }

        $btn_count_args = array('post_id' => $id,'btn_slug' => $btn_slug,'btn_type' => $post_type);
        $btn_count = get_single_btn_count($btn_count_args);

        $popular_array[] = array(
                                $label.'_id' => $id,
                                'btn_count' => $btn_count
                            );
    }

    return $popular_array;
}

/*
*   Save most clicked overall slug (all post types, no comments) in wp_options
*   'enp_button_popular_'.$btn_slug
*   Build the field based on the saved popular posts in wp_options
*/
function enp_all_popular_posts_save($btn_slug, $btn_info) {
    // loop through each button type
    $all_popular_posts = array();
    foreach($btn_info['btn_type'] as $key=>$value) {
        // check if the button type is active and is not comments
        if($value === '1' && $key !== 'comment') {
            // get the arrays
            $pop_posts = get_option('enp_button_popular_'.$btn_slug.'_'.$key);
            if(!empty($pop_posts)) {
                $all_popular_posts = array_merge($all_popular_posts, $pop_posts);
            }
        }
    }

    // rearrange the array to be in order of most clicks to least
    if(!empty($all_popular_posts)) {
        usort($all_popular_posts, 'enp_sort_popular_post_order'); // enp_sort_popular_post_order is a function, oddly
    }

    update_option( 'enp_button_popular_'.$btn_slug, $all_popular_posts );
}


// Sorts multidemensional array from high to low by btn_count value
function enp_sort_popular_post_order($a, $b) {
    return ($a['btn_count'] < $b['btn_count']) ? 1 : -1;
}

?>
