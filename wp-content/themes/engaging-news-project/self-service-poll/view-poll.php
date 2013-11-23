<?php
/*
Template Name: View Poll
*/
?>
<?php get_header(); ?>

<div id="main_content" class="clearfix">
	<div id="left_area">
		<?php get_template_part('includes/breadcrumbs', 'page'); ?>
    <?php 
    $poll = $wpdb->get_row("SELECT * FROM enp_poll WHERE ID = " . $_GET["id"] ); 
    echo $poll->ID . '<br>';
    echo $poll->title .  '<br>';
    echo $poll->question . '<br>';
    ?>
    <p><a href="/enp/configure-poll/?edit_id=<?php echo $poll->ID ?>">Edit poll</a></p>
    <p><a href="/enp/configure-poll/?delete_id=<?php echo $poll->ID ?>" onclick="return confirm('Are you sure you want to delete this poll?')">Delete poll</a></p>
    
		<?php if ( 'on' == get_option('trim_show_pagescomments') ) comments_template('', true); ?>
	</div> <!-- end #left_area -->

	<?php get_sidebar(); ?>
</div> <!-- end #main_content -->

<?php get_footer(); ?>