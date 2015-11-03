<?
/*
*   popular-button-save.php
*   Functions for processing and saving the popular buttons arrays to wp_options table
*
*   since v 0.0.3
*/


function enp_popular_button_save() {

    // get all our active slugs
    $enp_button_slugs = get_option('enp_button_slugs');

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
                    // reset the array
                    $popular_post_args = array('post_type' => $key);
                    $popular_post_args = array_merge($popular_args, $popular_post_args);
                    enp_popular_posts_save($btn_slug, $popular_post_args);
                }

            } // end if

        } // end foreach btn_type

        if($process_comments === true) {
            // strip out popular args post type
            enp_popular_comments_save($btn_slug, $btn_info['btn_type'], $popular_args);
        }
    } // end foreach btn_slug

}


function enp_popular_comments_save($btn_slug, $post_types, $args) {
    $comment_args = array(
            'fields' => 'ids',
            'status' => 'approve',
        );

    $args = array_merge($comment_args, $args);

    $comments_query = new WP_Comment_Query;
    $comments = $comments_query->query( $args );

    $popular_comments = enp_build_popular_comments_array($btn_slug, $comments);

    // all comments
    update_option('enp_button_popular_'.$btn_slug.'_comments', $popular_comments);

    // all comments by post type
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
            $popular_comments = enp_build_popular_comments_array($btn_slug, $comments);
            // save the array
            update_option('enp_button_popular_'.$btn_slug.'_'.$key.'_comments', $popular_comments);

        }
    }
}



function enp_popular_posts_save($btn_slug, $args) {


    // TODO: Override this via Admin option
    $args['posts_per_page']= 20; // limit to 20.

    $pop_posts = get_posts( $args );

    $popular_posts = array();

    foreach ($pop_posts as $post) {
        // clear the array

        $post_id = $post->ID;
        $btn_count_args = array(
           'post_id' => $post_id,
           'btn_slug' => $btn_slug,
           'btn_type' => $args['post_type']
        );

        $btn_count = get_single_btn_count($btn_count_args);
        $popular_posts[] = array(
                                'post_id' => $post_id,
                                'btn_count' => $btn_count
                            );
    }

    update_option('enp_button_popular_'.$btn_slug.'_'.$args['post_type'], $popular_posts);

    // Restore original Post Data
    wp_reset_postdata();
}


function enp_build_popular_comments_array($btn_slug, $comments) {
    $popular_comments = array();

    foreach($comments as $comment_id) {
        $btn_count = get_comment_meta($comment_id, 'enp_button_'.$btn_slug, true);
        $popular_comments[] = array(
                                    'comment_id'=>$comment_id,
                                    'btn_count'=>$btn_count
                                );
    }

    return $popular_comments;
}

?>
