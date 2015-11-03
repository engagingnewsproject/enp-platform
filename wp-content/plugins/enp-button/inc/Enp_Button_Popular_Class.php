<?
/*
* Enp_Button_Popular Class
* Get post IDs by most clicked button slugs
*
* since v 0.0.3
*/

/*
USAGE: Used to get popular posts so you can loop through them
      to use however you want

$args = array(
              'btn_slug'=>'Respect',
              'btn_type'=>'comment'
            );

$pop_posts = new Enp_Popular_Buttons($args); // object{
                                                    'btn_slug' => 'respect';
                                                    'btn_name' => 'Respect';
                                                    'btn_past_tense_name' => 'Respected';
                                                    'btn_type' => 'comment';
                                                    'popular_posts' =array(                    // this array is useful for basic foreach loops
                                                                        array('post_id'=>1,
                                                                              'btn_count'=>89
                                                                              ),
                                                                        array('post_id'=>19,
                                                                              'btn_count'=>59
                                                                              ),
                                                                        array('post_id'=>6,
                                                                              'btn_count'=>16
                                                                              )
                                                                        );
                                                    'popular_posts_by_id' = array([1]=>1, // this array is useful for wp_query loops
                                                                                  [2]=>19,
                                                                                  [3]=>6)
                                                    }

echo '<h2>Most '.$pop_posts->get_btn_past_tense_name().' '.$pop_posts->get_btn_past_tense_name().'</h2>';
foreach($pop_posts->popular_posts as $pop) {
    $post_id = $pop['post_id'];
    $btn_clicks = $pop['btn_count'];
    echo '<h3><a href="'.get_permalink($post_id).'">'.get_the_title($post_id).'</a></h3>';
}

// with WP_Query
$query_args = array(
                    'post_type' => $pop_posts->get_btn_types();
                    'post__in' => $pop_posts->get_pop_posts_by_id(); // returns array of popular posts
$pop_posts_query = new WP_Query( $args );

// The Loop
if ( $pop_posts_query->have_posts() ) :
    while ( $pop_posts_query->have_posts() ) : $pop_posts_query->the_post();

        echo '<li>' . get_the_title() . '</li>';

    endwhile;
endif;

wp_reset_postdata();
*/

class Enp_Popular_Buttons extends Enp_Button {
    public $popular_posts; //array of popular post IDs

    public function __construct($args = array()) {
         $default_args = array(
            'btn_slug' => false, // set to slug string or array of strings, "respect", "recommend", "important". also accepts array
            'btn_type' => false // slug of the post type. post, page, comment, or cpt slug
        );

        $args = array_merge($default_args, $args);

        $this->popular_posts = $this->set_popular_posts($args);
        $this->btn_type = $args['btn_type'];
    }


    protected function set_popular_posts($args) {
        return array('Testing');
    }



}

?>
