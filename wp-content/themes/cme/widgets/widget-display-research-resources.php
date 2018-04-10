<?php
/*
Plugin Name: ENP Research Resources
Plugin URI: http://
Description: Displays downloads links for attached research resources
Author: Brett Bowlin
Version: 1.0
Author URI: http://brettbowlin.com
*/
use Roots\Sage\Assets;

// Block direct requests
if ( !defined('ABSPATH') )
	die('-1');


add_action( 'widgets_init', function(){
     register_widget( 'ENP_Research_Resources_Widget' );
});

/**
 * Adds My_Widget widget.
 */
class ENP_Research_Resources_Widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'ENP_Research_Resources_Widget', // Base ID
			__('ENP Research Resources', 'text_domain'), // Name
			array( 'description' => __( 'Display attached research resources (provided by custom fields)', 'text_domain' ),
					'classname' => 'research-resources' ) // Args
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

		global $post;

     	echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
		}
		// get current post
		// 1. look up resource data (get_research_resources)
		// 			- research paper, summary, resource links
		// 2. generate markup for resources
		//
		enp_resources( get_queried_object()->ID );

		echo $args['after_widget'];
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		else {
			$title = __( 'New title', 'text_domain' );
		}
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
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

		return $instance;
	}

} // class My_Widget

function enp_resources( $id = null ) {
	global $post;

	if( $id == null )
		$id = $post->ID;

	?>
	<div class="enp-resources">
	<?php
    
    $research_video = get_post_meta($post->ID, 'video_here', true);
    if ( $research_report ) { ?>
        <iframe src="<?php the_field('video_here'); ?>" frameborder="0" width="100%" height="200"></iframe>
    <?php }
    
	$research_report = get_post_meta($post->ID, 'report_here', true);
	if ( $research_report ) { ?>
		<p><a href="<?php the_field('report_here'); ?>" class="btn btn-primary btn-research-report" target="_blank"><img src="<?php echo Assets\asset_path('images/ico_paper_white@2x.png'); ?>" height="24" width="18">Download Full Report</a></p>
	<?php }

	$research_summary = get_post_meta($post->ID, 'summary_research_', true);
	if ( $research_summary ) { ?>
		<p><a href="<?php the_field('summary_research_'); ?>" class="btn btn-primary btn-primary--ghost btn-research-summary" target="_blank">Download Summary</a></p>
	<?php }

	$resource_links = get_post_meta($post->ID, 'research_resources', true);
	if ( !empty($resource_links) ) {
		$links = explode("\n", get_field('research_resources'));
		?>
		<h5 class="title">Resources</h5>
		<ul class="research-links">
		<?php foreach( $links as $link ) : $pieces = explode('|', $link); ?>
			<li><a href="<?php echo trim($pieces[1]); ?>" class="" target="_blank"><?php echo trim($pieces[0]); ?></a></li>
		</ul>
	<?php endforeach;
	}

}
function enp_display_resources() {

}
