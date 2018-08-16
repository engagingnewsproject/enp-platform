<?php
/**
 * Single Event Template
 * A single event. This displays the event title, description, meta, and
 * optionally, the Google map for the event.
 *
 * Override this template in your own theme by creating a file at [your-theme]/tribe-events/single-event.php
 *
 * @package TribeEventsCalendar
 * @version 4.6.19
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$events_label_singular = tribe_get_event_label_singular();
$events_label_plural   = tribe_get_event_label_plural();

$event_id = get_the_ID();


Tribe__Notices::remove_notice( 'event-past' );

// Timber and Tribe Events don't seem to play perfectly together. It's trying to render a post with an ID of 0. If that happens, don't run antyhing.
?>
<div id="tribe-events-content" class="tribe-events-single">
	<!-- Notices -->
	<?php if($event_id) :
		tribe_the_notices();
		the_title( '<h1 class="tribe-events-single-event-title h2">', '</h1>' );?>

		<div class="tribe-events-schedule tribe-clearfix">
			<?php echo tribe_events_event_schedule_details( $event_id, '<h2>', '</h2>' ); ?>
			<?php if ( tribe_get_cost() ) : ?>
				<span class="tribe-events-cost"><?php echo tribe_get_cost( null, true ) ?></span>
			<?php endif; ?>
		</div>
	<?php endif; ?>


	
	<!-- #tribe-events-header -->
	<?php 
	while ( have_posts() ) :  the_post(); ?>

		
		<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<!-- Event featured image, but exclude link -->
			<?php echo tribe_event_featured_image( $event_id, 'full', false ); ?>

			<!-- Event content -->
			<?php if($event_id) :?>
			<?php do_action( 'tribe_events_single_event_before_the_content' ) ?>
			<?php endif;?>

			<div class="tribe-events-single-event-description tribe-events-content">
				<?php the_content(); ?>
			</div>


			<?php if($event_id) :?>
			<!-- .tribe-events-single-event-description -->
			<?php do_action( 'tribe_events_single_event_after_the_content' ) ?>

			<!-- Event meta -->
			<?php do_action( 'tribe_events_single_event_before_the_meta' ) ?>
			<?php tribe_get_template_part( 'modules/meta' ); ?>
			<?php do_action( 'tribe_events_single_event_after_the_meta' ) ?>
			<?php endif;?>
		</div> <!-- #post-x -->
		<?php 
		if ( get_post_type() == Tribe__Events__Main::POSTTYPE && tribe_get_option( 'showComments', false ) ) comments_template() ?>

	<?php endwhile; ?>

</div><!-- #tribe-events-content -->


