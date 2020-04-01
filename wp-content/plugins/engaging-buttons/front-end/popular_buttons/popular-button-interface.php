<?php

/*
*   Returns popular posts for the passed args or array of all popular post
*   objects if passed without any arguments (defaults)
*   USAGE: $args is optionally a btn slug so you can do
*   get_popular_posts('respect', 'page');
*   or
*   get_popular_posts('recommend');
*
*/
function enp_get_popular_posts($args = false, $btn_type = false) {
    $args = enp_process_popular_args($args, $btn_type);
    return enp_get_popular_buttons($args);
}

/*
*   Use this to get the comments object. It sets the args for comments
*   automatically
*   $respect_post_popular_comments = enp_get_popular_comments('respect', 'page');
*   var_dump($respect_post_popular_comments); // Returns top 20 comments for Respect Button on 'page' post type
*/
function enp_get_popular_comments($args = false, $btn_type = false) {
    $args = enp_process_popular_args($args, $btn_type, true);
    return enp_get_popular_buttons($args);
}


// Used by enp_get_popular_comments and enp_get_popular_posts
// to process the arguments and return the correct object(s)
function enp_get_popular_buttons($args = array()) {
    // create a new object based on the arguments sent
    $pop_posts = new Enp_Popular_Loop($args);

    if(!empty($args['btn_slug'])) {
        // return the object
        return $pop_posts;
    } else {
        // return all popular posts if there are no button slugs
        return $pop_posts->get_all_popular_buttons($args);
    }
}

/*
*
*   Processes arguments passed to enp_get_popular_comments and enp_get_popular_posts
*   for an easier convention of creating arguments so we can write our functions like:
*   enp_get_popular_posts('respect');
*   enp_get_popular_comments('recommend', 'post');
*
*/
function enp_process_popular_args($args = false, $btn_type = false, $comments = false) {
    // if there were no args, create an empty array
    // to pass to our popular button class
    if($args === false) {
        $args = array();
    } elseif(is_string($args)) {
        // we have a string! set it as the btn_slug
        $args = array('btn_slug' => $args);
    }
    //
    if($btn_type !== false) {
        $args['btn_type'] = $btn_type;
    }

    if($comments === true) {
        $args['comments'] = true;
    }

    return $args;
}

/*
*
*   Appends popular post sections to the bottom of single/singular
*   posts if they're turned on from the admin panel.
*   uses get_option('enp_display_popular_slugs')
*
*/
function enp_append_popular_posts($content) {
    if(is_single() || is_singular()) {
        // check the settings
        $enp_append_popular_slugs = get_option('enp_display_popular_slugs');
        if(!empty($enp_append_popular_slugs)) {
            foreach($enp_append_popular_slugs as $slug) {
                $posts = enp_get_popular_posts($slug);
                $content .= $posts->popular_loop(5);
            }
        }
    }

    return $content;
}
add_filter('the_content', 'enp_append_popular_posts');


/*
*   Example code to do something with popular comments
*
function enp_append_popular_comments($content) {
    if(is_single() || is_singular()) {
        // check the settings
        $enp_append_popular_slugs = get_option('enp_display_popular_slugs');
        if(!empty($enp_append_popular_slugs)) {
            foreach($enp_append_popular_slugs as $slug) {
                $posts = enp_get_popular_comments($slug);
                $content .= $posts->popular_loop(5);
            }
        }
    }

    return $content;
}
add_filter('the_content', 'enp_append_popular_comments');
*/
?>
