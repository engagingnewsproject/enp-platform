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
		echo $_GET["guid"];
	    $poll = $wpdb->get_row("
			SELECT * FROM enp_poll 
			WHERE guid = '" . $_GET["guid"] . "'"); 
	    echo $poll->ID . '<br>';
	    echo $poll->title .  '<br>';
	    echo $poll->question . '<br>';
	    ?>
	    <input class="form-control" type="text" id="slider-value" />
		<input type="text" id="foo" class="span2" value="" data-slider-min="-20" data-slider-max="20" data-slider-step="1" data-slider-value="-14" data-slider-orientation="horizontal" data-slider-selection="after"data-slider-tooltip="hide" />
    
		<textarea class="form-control" rows="3"><?php echo '<iframe height="450" width="475" frameborder="0" hspace="0" src="http://localhost:8888/enp/iframe-poll/?guid=' . $poll->guid . '"></iframe>' ?></textarea>
	    <p><a href="/enp/configure-poll/?edit_guid=<?php echo $poll->guid ?>">Edit poll</a></p>
	    <p><a href="/enp/list-polls/?delete_guid=<?php echo $poll->guid ?>" onclick="return confirm('Are you sure you want to delete this poll?')">Delete poll</a></p>
		<p><a href="/enp/list-polls/">Back to polls</a></p>
    
		<?php if ( 'on' == get_option('trim_show_pagescomments') ) comments_template('', true); ?>
	</div> <!-- end #left_area -->

	<?php get_sidebar(); ?>
</div> <!-- end #main_content -->

<?php get_footer(); ?>