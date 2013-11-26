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
    <span class="bootstrap"><hr></span>
    <?php get_template_part('self-service-poll/poll-display', 'page'); ?>
    <div class="clear"></div>
    <span class="bootstrap"><hr></span>
		<h3>iframe Code</h3>
		<div class="bootstrap">
      <?php $iframe_url = get_site_url() . '/iframe-poll/?guid=' . $_GET["guid"]; ?>
      <p>Copy and paste this code into your target website.  <a href="<?php echo $iframe_url ?>" target="_blank">Preview iframe</a>.</p>
	    <div class="form-group"><textarea class="form-control" rows="5"><?php echo '<iframe height="450" width="475" frameborder="0" hspace="0" src="' . $iframe_url . '"></iframe>' ?></textarea></div>
      <div class="clear"></div>
	    <div class="form-group"><p><a href="configure-poll/?edit_guid=<?php echo $_GET["guid"] ?>" class="btn btn-info btn-xs active" role="button">Edit poll</a> | <a href="list-polls/?delete_guid=<?php echo $_GET["guid"] ?>" onclick="return confirm('Are you sure you want to delete this poll?')" class="btn btn-danger btn-xs active" role="button">Delete poll</a> | <a href="list-polls/" class="btn btn-primary btn-xs active" role="button">Back to polls</a></p></div>
    </div>
    
		<?php if ( 'on' == get_option('trim_show_pagescomments') ) comments_template('', true); ?>
	</div> <!-- end #left_area -->

	<?php get_sidebar(); ?>
</div> <!-- end #main_content -->

<?php get_footer(); ?>