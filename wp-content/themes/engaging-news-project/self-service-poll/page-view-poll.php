<?php
/*
Template Name: View Poll
*/
?>
<?php get_header(); ?>

<div id="main_content" class="clearfix">
	<div id="left_area">
		<?php get_template_part('includes/breadcrumbs', 'page'); ?>

    <h1>Poll</h1>
    <hr>
    <?php get_template_part('self-service-poll/poll-display', 'page'); ?>
    <hr>
		<h3>iframe Code</h3>
		<p>Copy and paste this code into your target website.</p>
		<textarea class="form-control" rows="5"><?php echo '<iframe height="450" width="475" frameborder="0" hspace="0" src="http://localhost:8888/enp/iframe-poll/?guid=' . $_GET["guid"] . '"></iframe>' ?></textarea>
	    <p><a href="/enp/configure-poll/?edit_guid=<?php echo $_GET["guid"] ?>">Edit poll</a></p>
	    <p><a href="/enp/list-polls/?delete_guid=<?php echo $_GET["guid"] ?>" onclick="return confirm('Are you sure you want to delete this poll?')">Delete poll</a></p>
		<p><a href="/enp/list-polls/">Back to polls</a></p>
    
		<?php if ( 'on' == get_option('trim_show_pagescomments') ) comments_template('', true); ?>
	</div> <!-- end #left_area -->

	<?php get_sidebar(); ?>
</div> <!-- end #main_content -->

<?php get_footer(); ?>