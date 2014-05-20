<?php
/*
Template Name: Configure Quiz
*/
?>
<?php get_header(); ?>

<div id="main_content" class="clearfix">
	<div id="left_area">
		<?php get_template_part('includes/breadcrumbs', 'page'); ?>
    
		<?php 
    $user_ID = get_current_user_id(); 
    
    if ( $user_ID ) { ?>
      
  		<?php get_template_part('self-service-quiz/quiz-form', 'page'); ?>
  		<?php if ( 'on' == get_option('trim_show_pagescomments') ) comments_template('', true); ?>
      
    <?php
    } else {
    ?>
      <p>Please login to start creating quizzes!</p>
    <?php } ?>
	</div> <!-- end #left_area -->

	<?php get_sidebar(); ?>
</div> <!-- end #main_content -->

<?php get_footer(); ?>