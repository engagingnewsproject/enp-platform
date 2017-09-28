<?php

namespace Roots\Sage\Extras;

use Roots\Sage\Setup;

/**
 * Add <body> classes
 */
function body_class($classes) {
  // Add page slug if it doesn't exist
  if (is_single() || is_page() && !is_front_page()) {
    if (!in_array(basename(get_permalink()), $classes)) {
      $classes[] = basename(get_permalink());
    }
  }

  // Add class if sidebar is active
  if (Setup\display_sidebar()) {
    $classes[] = 'sidebar-primary';
  }

  return $classes;
}
add_filter('body_class', __NAMESPACE__ . '\\body_class');

/**
 * Clean up the_excerpt()
 */
function excerpt_more() {
  return ' &hellip; <a href="' . get_permalink() . '">' . __('Read more', 'sage') . '</a>';
}
add_filter('excerpt_more', __NAMESPACE__ . '\\excerpt_more');

/**
 * Add Google Analytics to Login page
 */

function ga_enqueue_script() {
  ?>

  <script>
    (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
    (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
    m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
    })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

    ga('create', 'UA-52471115-4', 'auto');
    ga('send', 'pageview');
  </script>

  <?php
}
add_action( 'login_enqueue_scripts', __NAMESPACE__ . '\\ga_enqueue_script', 10 );


/**
 * Determine if the current page is a parent or is a sub page of the page
 */
function is_tree() {      // $pid = The ID of the page we're looking for pages underneath
    global $post;              // load details about this page

    if( $post === null ) return false;

    $children = get_children(array('post_parent' => $post->ID, 'post_status'=>'publish'));
    //var_dump($children);
    if( !empty($children) || ( is_page() && $post->post_parent != 0 ) )
      return true;
    else
      return false;
}

if(!function_exists('get_post_top_ancestor_id')){
/**
 * Gets the id of the topmost ancestor of the current page. Returns the current
 * page's id if there is no parent.
 *
 * @uses object $post
 * @return int
 */
function get_post_top_ancestor_id(){
    global $post;

    if($post->post_parent){
        $ancestors = array_reverse(get_post_ancestors($post->ID));
        return $ancestors[0];
    }

    return $post->ID;
}}

/**
 * Filter wrapper for custom post types
 */
//add_filter('sage/wrap_base', __NAMESPACE__ . '\\sage_wrap_base_cpts'); // Add our function to the sage/wrap_base filter

function sage_wrap_base_cpts($templates) {
  $cpt = get_post_type(); // Get the current post type

  if ($cpt) {
     array_unshift($templates, 'base-' . $cpt . '.php'); // Shift the template to the front of the array
  }
  return $templates; // Return our modified array with base-$cpt.php at the front of the queue
}


/* Twitter Plugin Alterations */

function modify_wptt_TwitterTweets_widget_title( $text, $instance ) {
  return $text;
}
// TODO: add follow link to wptt widget title Note: not possible since plugin does not provide requisite hooks
// add_filter('widget_title', __NAMESPACE__ . '\\modify_wptt_TwitterTweets_widget_title', 100, 2);

/**
 * Dequeue wptt_TwitterTweets plugin styles
 */
function dequeue_wptt_TwitterTweets_styles() {
  wp_dequeue_style('wptt_front');
}
add_action('wp_enqueue_scripts', __NAMESPACE__ . '\\dequeue_wptt_TwitterTweets_styles', 100);



/*
* REMOVING theme wrapper when using the iframe-quiz
* We can probably remove this after moving to quiz tool v2
*/

add_filter('sage/wrap_base', __NAMESPACE__ . '\\sage_wrap_remove_base'); // Add our function to the sage/wrap_base filter

function sage_wrap_remove_base($templates) {
    if (is_page_template('base-iframe-quiz.php')) {
       array_unshift($templates, 'base-iframe-quiz.php'); // Shift the template to the front of the array
   } elseif (is_page_template('base-quiz-answer.php')) {
      array_unshift($templates, 'base-quiz-answer.php'); // Shift the template to the front of the array
    }


    return $templates; // Return our modified array with base-$cpt.php at the front of the queue
}

/*
* END REMOVING theme wrapper code
*/
