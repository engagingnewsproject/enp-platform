<?php
/*
Template Name: Poll Report
*/
?>
<?php get_header(); ?>

<div id="main_content" class="clearfix">
	<div id="left_area">
    <h1>Poll Report</h1>
		<?php get_template_part('includes/breadcrumbs', 'page'); ?>
    <h2>Detailed responses</h2>
    <p>Coming soon!</p>
    <h2>Chart of responses</h2>
    <p>Coming soon!</p>
		<?php if ( 'on' == get_option('trim_show_pagescomments') ) comments_template('', true); ?>
	</div> <!-- end #left_area -->

	<?php get_sidebar(); ?>
</div> <!-- end #main_content -->

<?php get_footer(); ?>