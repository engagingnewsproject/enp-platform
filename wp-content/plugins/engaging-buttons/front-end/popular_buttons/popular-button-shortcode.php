<?
/*
*   Shortcodes for outputting a number of popular items in a list
*   [engaging-posts]
*   [engaging-posts slug='respect' type='page' how-many='2']
*
*/


function enp_popular_posts($atts) {
    $atts = shortcode_atts(array(
      'slug' => false,
      'type' => false,
      'how-many' => 5
    ), $atts);

    // set the value
    $posts_html = '';

    // if no slug was passed, get all of the posts
    if($atts['slug'] === false) {
        $enp_btn_slugs = get_option('enp_button_slugs');
        if(!empty($enp_btn_slugs)) {
            foreach($enp_btn_slugs as $slug) {
                $atts['slug'] = $slug;
                $posts_html .= enp_popular_posts_HTML($atts);
            }
        }
    } else {
        $posts_html .= enp_popular_posts_HTML($atts);
    }


    return $posts_html;
}
add_shortcode('engaging-posts', 'enp_popular_posts');


function enp_popular_posts_HTML($atts, $filter_label = '') {
    $posts = enp_get_popular_posts($atts['slug'], $atts['type']);
    return $posts->popular_loop($atts['how-many'], $filter_label);
}

?>
