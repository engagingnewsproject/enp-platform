<?php
/*
Template Name: List Quizzes
*/
?>
<?php get_header(); ?>
<? 
if ( $_GET["delete_guid"] ) {
  $wpdb->delete( 'enp_quiz', array( 'guid' => $_GET["delete_guid"] ) );
  echo "<span 'success'>Quiz deleted successfully.</span>";
}
?>

<div id="main_content" class="clearfix">
	<div id="left_area" class="bootstrap">
		<?php get_template_part('includes/breadcrumbs', 'page'); ?>
    <?php
    $user_ID = get_current_user_id(); 
    
    if ( $user_ID ) {
    ?>
    <h2>My Quizzes</h2>
    <p><a href="configure-quiz/" class="btn btn-primary btn-sm active" role="button">New quiz</a></p>
    <?php
    $quizzes= $wpdb->get_results( 
    	"
    	SELECT * 
    	FROM enp_quiz
    	WHERE user_id = " . $user_ID 
    );
        
    if ( $quizzes )
    {
      echo "<div class='table-responsive'>";
      echo "<table class='table'>";
      echo "<thead><tr>
              <th>Title</th>
              <th>Question</th>
              <th>Unique Views</th>
              <th>Correct Respones</th>
            </tr></thead>";
    	foreach ( $quizzes as $quiz )
    	{
    		?>
        <tr>
          <td><a href="view-quiz?guid=<?php echo $quiz->guid ?>"><?php echo $quiz->title; ?></a></td>
          <td><?php echo $quiz->question; ?></td>
        <?php
        $wpdb->get_var( 
        	"
          SELECT ip_address
          FROM enp_quiz_responses	 
          WHERE quiz_id = " . $quiz->ID . 
          " GROUP BY ip_address"
        );
        
        ?>
        <td><a href="quiz-report/?guid=<?php echo $quiz->guid ?>" class="btn btn-warning btn-xs active" role="button"><?php echo $wpdb->num_rows ?></a></td>
        <?php
        $correct_response_count = $wpdb->get_var( 
        	"
        	SELECT COUNT(*) 
        	FROM enp_quiz_responses
        	WHERE is_correct = 1 AND quiz_id = " . $quiz->ID
        );
        
        ?>
          <td><a href="quiz-report/?guid=<?php echo $quiz->guid ?>" class="btn btn-warning btn-xs active" role="button"><?php echo $correct_response_count?></a></td>
          <td><a href="configure-quiz/?edit_guid=<?php echo $quiz->guid ?>" class="btn btn-info btn-xs active" role="button">Edit</a></td>
          <td><a href="list-quizzes/?delete_guid=<?php echo $quiz->guid ?>" onclick="return confirm('Are you sure you want to delete this quiz?')" class="btn btn-danger btn-xs active" role="button">Delete</a></td>
        </tr>
    		<?php
    	}	
      
      echo "</table>";
      echo "</div>";
    }
    else
    {
    	?>
    	<h2>No quizzes found for the current user</h2>
    	<?php
    }
    ?>
    
    <?php
    } else {
    ?>
      <p>Please login to start creating quizzes!</p>
    <?php } ?>
    
		<?php if ( 'on' == get_option('trim_show_pagescomments') ) comments_template('', true); ?>
	</div> <!-- end #left_area -->

	<?php get_sidebar(); ?>
</div> <!-- end #main_content -->

<?php get_footer(); ?>