<?
/*
*   Filters for interacting with the Enp_Popular_Loop class and displaying
*   our popular posts. You can copy/paste this file into your theme and run
*   the enp_remove_popular_posts_filters() function at the top to override all
*   HTML.
*   since v.0.0.5
*/

// remove all the filters on this file
function enp_remove_popular_posts_filters() {
    remove_filter('enp_popular_posts_loop_wrap', 'enp_default_pop_posts_loop_wrap');
    remove_filter('enp_popular_posts_loop_before_html', 'enp_default_pop_posts_loop_before');
    remove_filter('enp_popular_posts_loop_after_html', 'enp_default_pop_posts_loop_after');
    remove_filter('enp_popular_post_html', 'enp_default_pop_post_html');
}

// Wrap HTML for each popular post section
function enp_default_pop_posts_loop_wrap($html, $pop_posts) {
    $pop_html = '<aside class="enp-popular-posts enp-popular-posts--'.$pop_posts->btn_slug.'">'
                    .$html
                .'</aside>';

    return $pop_html;
}
add_filter('enp_popular_posts_loop_wrap', 'enp_default_pop_posts_loop_wrap', 10, 2 );


// Adds a section title and the UL to each popular post section
function enp_default_pop_posts_loop_before($html, $pop_posts){
    $html = '<h3 class="enp-popular-posts-section-title enp-popular-posts-section-title--'.$pop_posts->btn_slug.'">Most '.$pop_posts->btn_past_tense_name.' '.$pop_posts->get_btn_type_name().'</h3>
            <ul class="enp-popular-posts-list enp-popular-posts-list--'.$pop_posts->btn_slug.'">'.$html;
    return $html;
}
add_filter('enp_popular_posts_loop_before_html', 'enp_default_pop_posts_loop_before', 10, 2);

// Adds closing UL to each popular post section
function enp_default_pop_posts_loop_after($html, $pop_posts){
    $html .= '</ul>';
    return $html;
}
add_filter('enp_popular_posts_loop_after_html', 'enp_default_pop_posts_loop_after', 10, 2);


// creates our popular post list items
function enp_default_pop_post_html($html, $pop_id, $pop_count, $pop_posts){
    $html .= '<li class="enp-popular-posts-list-item enp-popular-posts-list-item--'.$pop_posts->btn_slug.'">
                <a class="enp-popular-posts-list-link enp-popular-posts-list-link--'.$pop_posts->btn_slug.'" href="'.get_permalink($pop_id).'">
                    <span class="enp-popular-posts-list-title enp-popular-posts-list-title--'.$pop_posts->btn_slug.'">'.get_the_title($pop_id).'</span>
                     <span class="enp-popular-posts-list-btn-count enp-popular-posts-list-btn-count--'.$pop_posts->btn_slug.'">'.$pop_count.'</span>
                </a>
            </li>';
    return $html;
}
add_filter('enp_popular_post_html', 'enp_default_pop_post_html', 10, 4);


/*
*   Example code for doing something with popular comment html filters
*
function enp_pop_comment_html($html, $comment_id, $btn_count, $pop_comments_obj){
    $html = '<h3>Comment '.$comment_id.' has '.$btn_count.' clicks!</h3>'
            .get_comment_text( $comment_id );

    return $html;
}
add_filter('enp_popular_comment_html', 'enp_pop_comment_html', 10, 4);
*/
?>
