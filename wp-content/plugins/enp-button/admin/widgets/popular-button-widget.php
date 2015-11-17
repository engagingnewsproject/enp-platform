<?
/**
 * Adds Enp_Popular_Widget widget.
 */

class Enp_Popular_Widget extends WP_Widget {

    /**
     * Register widget with WordPress.
     */
    function __construct() {
        parent::__construct(
            'enp_popular_widget', // Base ID
            __( 'Engaging Posts', 'text_domain' ), // Name
            array( 'description' => __( 'Display a list of your most engaging posts.', 'text_domain' ), ) // Args
        );
    }

    /**
     * Front-end display of widget.
     *
     * @see WP_Widget::widget()
     *
     * @param array $args     Widget arguments.
     * @param array $instance Saved values from database.
     */
    public function widget( $args, $instance ) {
        echo $args['before_widget'];
        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
        }

        $atts = array();
        $defaults = array(
                      'slug' => false,
                      'type' => false,
                      'how-many' => 5
                    );

        $atts['slug'] = ! empty( $instance['slug'] ) ? $instance['slug'] : '';
        $atts['type'] = ! empty( $instance['type'] ) ? $instance['type'] : 'all';
        $atts['how-many'] = ! empty( $instance['how_many'] ) ? $instance['how_many'] : '5';

        // if we don't have a slug, something is wrong
        if(empty($atts['slug'])) {
            return false;
        }

        add_filter('enp_popular_widget_posts_loop_before_html', array($this, 'widget_popular_posts_before'), 10, 2);
        add_filter('enp_popular_widget_post_html', 'enp_default_pop_post_html', 10, 4);
        add_filter('enp_popular_widget_posts_loop_after_html', 'enp_default_pop_posts_loop_after', 10, 2);

        echo enp_popular_posts_HTML($atts, 'widget_');

        echo $args['after_widget'];


    }

    public function widget_popular_posts_before($html, $pop_posts) {
        $html .= '<ul class="enp-popular-posts-list-widget enp-popular-posts-list-widget--'.$pop_posts->btn_slug.'">';
        return $html;
    }

    /**
     * Back-end widget form.
     *
     * @see WP_Widget::form()
     *
     * @param array $instance Previously saved values from database.
     */
    public function form( $instance ) {
        // generate the Enp_Button object
        $enp_btns = new Enp_Button();
        $enp_btns = $enp_btns->get_btns();
        if(empty($enp_btns)) {
            echo 'You don\'t have any active Engaging Buttons. Go to Settings > Engaging Buttons to create your Engaging Buttons.';
            return false;
        }

        $active_types = array();
        foreach($enp_btns as $enp_btn) {
             foreach($enp_btn->btn_type as $key => $value) {
                // check to see if it's active
                if($value === '1') {
                    // check to see if it's already in the array
                    if(!in_array($key, $active_types)) {
                        array_push($active_types, $key);
                    }
                }
             }
        }

        $title = ! empty( $instance['title'] ) ? $instance['title'] : 'Top Posts';
        $slug = ! empty( $instance['slug'] ) ? $instance['slug'] : '';
        $type = ! empty( $instance['type'] ) ? $instance['type'] : '';
        $how_many = ! empty( $instance['how_many'] ) ? $instance['how_many'] : '5';
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'slug' ); ?>"><?php _e( 'Data from which Engaging Button?' ); ?></label><br/>
            <select id="<?php echo $this->get_field_id( 'slug' ); ?>" name="<?php echo $this->get_field_name( 'slug' ); ?>">
            <? foreach($enp_btns as $enp_btn) {
                echo '<option value="'.$enp_btn->get_btn_slug().'" '.selected( $slug, $enp_btn->get_btn_slug(), false ).'>'.$enp_btn->get_btn_name().'</option>';
            }?>
            </select>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id( 'type' ); ?>"><?php _e( 'Data from which Post Type?' ); ?></label><br/>
            <select id="<?php echo $this->get_field_id( 'type' ); ?>" name="<?php echo $this->get_field_name( 'type' ); ?>">
                <option value="all_post_types" <? selected( $type, 'all_post_types' );?>>All</option>
            <? foreach($active_types as $active_type) {
                echo '<option value="'.$active_type.'" '.selected( $type, $active_type, false ).'>'.ucfirst($active_type).'</option>';
            }?>
            </select>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id( 'how_many' ); ?>"><?php _e( 'How many post links do you want to display? Min 1, Max 20' ); ?></label>
            <input id="<?php echo $this->get_field_id( 'how_many' ); ?>" name="<?php echo $this->get_field_name( 'how_many' ); ?>" type="number" value="<?php echo esc_attr( $how_many ); ?>" min="1" max="20" />
        </p>


        <?php
    }

    /**
     * Sanitize widget form values as they are saved.
     *
     * @see WP_Widget::update()
     *
     * @param array $new_instance Values just sent to be saved.
     * @param array $old_instance Previously saved values from database.
     *
     * @return array Updated safe values to be saved.
     */
    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
        $instance['slug'] = ( ! empty( $new_instance['slug'] ) ) ? strip_tags( $new_instance['slug'] ) : '';
        $instance['type'] = ( ! empty( $new_instance['type'] ) ) ? strip_tags( $new_instance['type'] ) : '';
        $instance['how_many'] = ( ! empty( $new_instance['how_many'] ) ) ? strip_tags( $new_instance['how_many'] ) : '';

        return $instance;
    }

} // class Enp_Popular_Widget

// register Enp_Popular_Widget widget
function register_enp_popular_widget() {
    register_widget( 'Enp_Popular_Widget' );
}
add_action( 'widgets_init', 'register_enp_popular_widget' );
?>
