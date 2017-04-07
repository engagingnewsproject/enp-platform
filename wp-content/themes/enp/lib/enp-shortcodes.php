<?php

/**
* Defines all attributes in the shortcode for extracting
*/
function enp_list_posts_atts() {
    return array (
        'title'=> '',
        'type' => 'post',
        'order' => 'DESC',
        'orderby' => 'date',
        'posts' => 3,
        'category' => '',
        'classes' => '',
        'exclude' => '',  // comma separated integers 1,2,4,5
        'include' => '',
        'taxonomy' => '',
        'field' => '',
        'terms' => '', // comma separated integers or slugs 1,2,4,5
        'excerpt' => false);
}
// ENP Custom Shortcodes
add_shortcode( 'enp-list-posts', 'enp_list_posts' );

function enp_list_posts($atts) {
    // define attributes and their defaults
    extract( shortcode_atts( enp_list_posts_atts(), $atts ) );

    $enp_query = enp_list_posts_query($atts);
    // if we didn't give them as many posts as they wanted, fill it in with random posts
    if ( $enp_query->post_count < (int) $posts) {
        $enp_query = enp_list_posts_need_more_posts($enp_query, $atts);
    }

    // run the loop based on the query
    if ( $enp_query->have_posts() ) :
        ob_start();
            echo enp_list_posts_html($enp_query, $title, $classes, $excerpt);
        $enp_posts = ob_get_clean();
        // reset the query
        wp_reset_query();
        return $enp_posts;
    endif;
}

function enp_list_posts_html($enp_query, $title, $classes, $excerpt) {
    if ( $enp_query->have_posts() ) :
        $html = '';
        $html .= ($title !== '' ? '<h3>'.$title.'</h3>' : '');
        $html .= '<ul class="enp-list-posts'.( !empty($classes) ? ' '.$classes : '').'">';
          while($enp_query->have_posts()) : $enp_query->the_post();
            $post_id = get_the_ID();

            $html .= "<li class='enp-list-posts__post' id='post-$post_id'>
                <h4 class='enp-list-posts__title'><a class='enp-list-posts__link' href='".get_permalink()."'>".get_the_title()."</a></h4>";

                if($excerpt === 'true') {
                    $html .= "<span class='enp-list-posts__excerpt'>".enp_list_posts_excerpt( $post_id, 30 )."</span>";
                }
            $html .= '</li>';

        endwhile;
        $html .= '</ul>';
    endif;
    return $html;
}
/**
* Builds and runs the WP query for getting our posts
* @return WP_Query()
*/
function enp_list_posts_query($atts) {
    // define attributes and their defaults
    extract( shortcode_atts( enp_list_posts_atts(), $atts ) );
    // define query parameters based on attributes
    $options = array(
        'post_type' => $type,
        'order' => $order,
        'orderby' => $orderby,
        'posts_per_page' => $posts,
        'category_name' => $category,
    );

    // We can't use post__not_in and post__in in the same query,
    // so we have to pick one
    if(!empty($exclude)) {
        $options['post__not_in'] = explode(',', $exclude);
    } elseif(!empty($include)) {
        $options['post__in'] = explode(',', $include);
    }

    if(!empty($taxonomy)) {
        $tax = array('tax_query' => array(
                            array(
                                'taxonomy' => $taxonomy,
                                'field'    => $field,
                                'terms'    => explode(',', $terms),
                            ),
                        )
                    );
        $options = array_merge($options, $tax);
    }

    return new WP_Query( $options );
}

/**
* Get random posts to fill in to get up to the requested count (if possbile)
* by running another WP_Query without the taxonomy query stuff
*
* @param $enp_query (WP_Query OBJECT)
* @return new $enp_query WP_Query OBJECT with posts filled in
*/
function enp_list_posts_need_more_posts($enp_query, $atts) {
    // define attributes and their defaults
    extract( shortcode_atts( enp_list_posts_atts(), $atts ) );

    // get all the returned post_IDs from the query
    $returned_post_ids = wp_list_pluck($enp_query->posts, 'ID');
    // get our original excluded post id(s) from the intial shortcode request
    $original_excluded = explode(',', $exclude);
    // combine the arrays
    $exclude_posts = array_merge($original_excluded, $returned_post_ids);
    // turn them into a string to match our formatting for the shortcode
    $atts['exclude'] = implode($exclude_posts, ',');
    // remove the taxonomy query part to increase the odds we'll get more posts
    $atts['taxonomy'] = '';
    // reduce our count request so we don't get too many posts
    $atts['posts'] = (int) $atts['posts'] - (int) $enp_query->post_count;
    // run a new query to fill in the remaining posts
    $second_enp_query = enp_list_posts_query($atts);
    // merge the posts from the queries
    $enp_query->posts = array_merge($enp_query->posts, $second_enp_query->posts);

    // set post count correctly so our looping gets all the posts
    $enp_query->post_count = count( $enp_query->posts );

    return $enp_query;
}
/**
 * Function to create an excerpt for the post.
 *
 * @since 1.6
 *
 * @param int        $id Post ID
 * @param int|string $excerpt_length Length of the excerpt in words
 * @return string Excerpt
 */
function enp_list_posts_excerpt( $id, $excerpt_length = 0, $use_excerpt = true ) {
	$content = $excerpt = '';

	if ( $use_excerpt ) {
		$content = get_post( $id )->post_excerpt;
	}
	if ( '' == $content ) {
		$content = get_post( $id )->post_content;
	}

	$output = strip_tags( strip_shortcodes( $content ) );

	if ( $excerpt_length > 0 ) {
		$output = wp_trim_words( $output, $excerpt_length );
	}

	/**
	 * Filters excerpt generated by CRP.
	 *
	 * @since	1.9
	 *
	 * @param	array	$output			Formatted excerpt
	 * @param	int		$id				Post ID
	 * @param	int		$excerpt_length	Length of the excerpt
	 * @param	boolean	$use_excerpt	Use the excerpt?
	 */
	return apply_filters( 'enp_list_posts_excerpt', $output, $id, $excerpt_length, $use_excerpt );
}

/**
* Outputs related posts based on the categories of the passed post ID
*/
function enp_list_related_research($post_id, $numberposts, $show_excerpt = '') {
    $research_category = wp_get_post_terms( $post_id, 'research-categories', array('fields'=>'slugs') );

    echo do_shortcode('[enp-list-posts
    title="Related Research"
    exclude='.$post_id.'
    type=research
    taxonomy=research-categories
    field=slug
    terms='.implode($research_category,',').'
    orderby=rand
    posts='.$numberposts.'
    excerpt='.$show_excerpt.']');
}

?>
