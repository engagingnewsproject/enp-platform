<?php
/**
 * Plugin Name: CME Recent Posts Widget
 * Plugin URI: https://mediaengagement.org
 * Description:  Custom WP widget for recent custom post types
 * Author: Luke Carl Hartman
 * Version: 1.1
 * License: GPLv2 or later
 */
class CME_Widget_Recent_Posts extends WP_Widget
{
    /**
     * Sets up a new CME Recent Posts widget instance.
     *
     * @since 2.8.0
     */
    public function __construct()
    {
        $widget_ops = array(
            'classname'                   => 'cme_widget_recent_posts',
            'description'                 => __('Your site&#8217;s most recent research posts.'),
            'customize_selective_refresh' => true,
            'show_instance_in_rest'       => true,
        );
        parent::__construct('cme_widget_recent_posts', __('CME Recent Posts'), $widget_ops);
        $this->alt_option_name = 'cme_widget_recent_posts';
    }

    /**
     * Outputs the content for the current Recent Posts widget instance.
     *
     * @since 2.8.0
     *
     * @param array $args     Display arguments including 'before_title', 'after_title',
     *                        'before_widget', and 'after_widget'.
     * @param array $instance Settings for the current Recent Posts widget instance.
     */
    public function widget($args, $instance)
    {
        if (! isset($args['widget_id'])) {
            $args['widget_id'] = $this->id;
        }

        $default_title = __('Recent Posts');
        $title         = (! empty($instance['title'])) ? $instance['title'] : $default_title;

        /** This filter is documented in wp-includes/widgets/class-wp-widget-pages.php */
        $title = apply_filters('widget_title', $title, $instance, $this->id_base);

        $number = (! empty($instance['number'])) ? absint($instance['number']) : 5;
        if (! $number) {
            $number = 5;
        }
        $show_date = isset($instance['show_date']) ? $instance['show_date'] : false;

        $r = new WP_Query(
            /**
             * Filters the arguments for the Recent Posts widget.
             *
             * @since 3.4.0
             * @since 4.9.0 Added the `$instance` parameter.
             *
             * @see WP_Query::get_posts()
             *
             * @param array $args     An array of arguments used to retrieve the recent posts.
             * @param array $instance Array of settings for the current widget.
             */
            apply_filters(
                'widget_posts_args',
                array(
                    'posts_per_page'      => $number,
                    'no_found_rows'       => true,
                    'post_status'         => 'publish',
                    'post_type'           => array( 'research' ),
                    'ignore_sticky_posts' => true,
                ),
                $instance
            )
        );

        if (! $r->have_posts()) {
            return;
        }
        ?>

<?php echo $args['before_widget']; ?>

<?php
        if ($title) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        $format = current_theme_supports('html5', 'navigation-widgets') ? 'html5' : 'xhtml';

        /** This filter is documented in wp-includes/widgets/class-wp-nav-menu-widget.php */
        $format = apply_filters('navigation_widgets_format', $format);

        if ('html5' === $format) {
            // The title may be filtered: Strip out HTML and make sure the aria-label is never empty.
            $title      = trim(strip_tags($title));
            $aria_label = $title ? $title : $default_title;
            echo '<nav aria-label="' . esc_attr($aria_label) . '">';
        }
        ?>

<ul>
    <?php foreach ($r->posts as $recent_post) : ?>
    <?php
                $post_title   = get_the_title($recent_post->ID);
        $title        = (! empty($post_title)) ? $post_title : __('(no title)');
        $aria_current = '';

        if (get_queried_object_id() === $recent_post->ID) {
            $aria_current = ' aria-current="page"';
        }
        ?>
    <li class="post">
        <a href="<?php the_permalink($recent_post->ID); ?>"
            class="post-link" <?php echo $aria_current; ?>><?php echo $title; ?>
            <?php if ($show_date) : ?>
            <time class="post-date"><?php echo get_the_date('', $recent_post->ID); ?></time>
            <?php endif; ?>
        </a>
    </li>
    <?php endforeach; ?>
</ul>

<?php
        if ('html5' === $format) {
            echo '</nav>';
        }

        echo $args['after_widget'];
        wp_reset_postdata();
    }

    /**
     * Handles updating the settings for the current Recent Posts widget instance.
     *
     * @since 2.8.0
     *
     * @param array $new_instance New settings for this instance as input by the user via
     *                            WP_Widget::form().
     * @param array $old_instance Old settings for this instance.
     * @return array Updated settings to save.
     */
    public function update($new_instance, $old_instance)
    {
        $instance              = $old_instance;
        $instance['title']     = sanitize_text_field($new_instance['title']);
        $instance['number']    = (int) $new_instance['number'];
        $instance['show_date'] = isset($new_instance['show_date']) ? (bool) $new_instance['show_date'] : false;

        return $instance;
    }

    /**
     * Outputs the settings form for the Recent Posts widget.
     *
     * @since 2.8.0
     *
     * @param array $instance Current settings.
     */
    public function form($instance)
    {
        $title     = isset($instance['title']) ? esc_attr($instance['title']) : '';
        $number    = isset($instance['number']) ? absint($instance['number']) : 5;
        $show_date = isset($instance['show_date']) ? (bool) $instance['show_date'] : false;
        ?>
<p>
    <label
        for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
    <input class="widefat"
        id="<?php echo $this->get_field_id('title'); ?>"
        name="<?php echo $this->get_field_name('title'); ?>"
        type="text" value="<?php echo $title; ?>" />
</p>

<p>
    <label
        for="<?php echo $this->get_field_id('number'); ?>"><?php _e('Number of posts to show:'); ?></label>
    <input class="tiny-text"
        id="<?php echo $this->get_field_id('number'); ?>"
        name="<?php echo $this->get_field_name('number'); ?>"
        type="number" step="1" min="1" value="<?php echo $number; ?>"
        size="3" />
</p>

<p>
    <input class="checkbox" type="checkbox" <?php checked($show_date); ?> id="<?php echo $this->get_field_id('show_date'); ?>"
    name="<?php echo $this->get_field_name('show_date'); ?>"
    />
    <label
        for="<?php echo $this->get_field_id('show_date'); ?>"><?php _e('Display post date?'); ?></label>
</p>
<?php
    }
}

/**
 * Register the widget.
 *
 */
function cme_widget_recent_posts_init()
{
    if (!is_blog_installed()) {
        return;
    }

    register_widget('CME_Widget_Recent_Posts');
    do_action('widgets_init');
}

add_action('init', 'cme_widget_recent_posts_init', 2);

/**
 * Include CSS file for MyPlugin.
 */
function cme_widget_recent_posts_enqueue_styles()
{
    wp_register_style('style', plugin_dir_url(__FILE__) . 'assets/style.css');
    wp_enqueue_style('style');
}
add_action('wp_enqueue_scripts', 'cme_widget_recent_posts_enqueue_styles');
