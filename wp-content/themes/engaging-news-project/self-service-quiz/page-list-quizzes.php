<?php
/*
Template Name: List Quizzes
*/
?>
<?php get_header(); ?>
<? 
if ( $_GET["delete_guid"] ) {
  $wpdb->delete( 'enp_quiz', array( 'guid' => $_GET["delete_guid"] ) );
  $quiz_notifications =  "<span class='quiz-notification success'><span class='glyphicon glyphicon-info-sign'></span> Quiz successfully deleted.</span>";
}
?>

<div id="main_content" class="clearfix">
	<div id="left_area" >
		<?php get_template_part('includes/breadcrumbs', 'page'); ?>
    <?php
    $user_ID = get_current_user_id(); 
    
    if ( $user_ID ) {
       echo $quiz_notifications;
    ?>
    <h1>My Quizzes</h1>
    <div class="bootstrap">
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
                  <th></th>
                  <th>Title</th>
                  <th>Type</th>
                  <th>Unique Views</th>
                  <th>Correct Respones</th>
                  <th>% Answering</th>
                  <th></th>
                  <th></th>
                </tr></thead>";
          foreach ( $quizzes as $quiz )
          {
            ?>
            <tr>
              <td><?php echo $quiz->ID; ?></td>
              <td><a href="view-quiz?guid=<?php echo $quiz->guid ?>"><?php echo $quiz->title; ?></a></td>
              <td><?php echo $quiz->quiz_type == "slider" ? "Slider" : "Multiple Choice"; ?></td>
            <?php
            $wpdb->get_var( 
              "
              SELECT ip_address
              FROM enp_quiz_responses   
              WHERE quiz_id = " . $quiz->ID . 
              " GROUP BY ip_address"
            );
    
            $unique_view_count = $wpdb->num_rows;
            
            ?>
            <td><a href="quiz-report/?guid=<?php echo $quiz->guid ?>" class="btn btn-warning btn-xs active" role="button"><?php echo $unique_view_count; ?></a></td>
            <?php
            $correct_response_count = $wpdb->get_var( 
              "
              SELECT COUNT(*) 
              FROM enp_quiz_responses
              WHERE is_correct = 1 AND quiz_id = " . $quiz->ID
            );
            
            ?>
              <td><a href="quiz-report/?guid=<?php echo $quiz->guid ?>" class="btn btn-warning btn-xs active" role="button"><?php echo $correct_response_count?></a></td>
            <?php
            $quiz_total_view_count = $wpdb->get_var( 
              "
              SELECT COUNT(*) 
              FROM enp_quiz_responses
              WHERE correct_option_value = 'quiz-viewed-by-user' AND quiz_id = " . $quiz->ID
            );
            
            ?>
              <td><a href="quiz-report/?guid=<?php echo $quiz->guid ?>" class="btn btn-warning btn-xs active" role="button"><?php echo ROUND($unique_view_count/$quiz_total_view_count*100, 2) ?>%</a></td>
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
        </div>
	</div> <!-- end #left_area -->

	<?php get_sidebar(); ?>
</div> <!-- end #main_content -->

<?php get_footer(); ?>