<?php
/*
Template Name: List Quizzes
*/
?>
<?php get_header(); ?>
<? 
$user_ID = get_current_user_id(); 

if ( $user_ID && $_GET["delete_guid"] ) {
  $wpdb->delete( 'enp_quiz', array( 'guid' => $_GET["delete_guid"] ) );
  $quiz_notifications =  "
    <div class='bootstrap'>
      <div class='alert alert-success alert-dismissable'>
        <span class='glyphicon glyphicon-info-sign'></span> Quiz successfully deleted.
        <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
      </div>
      <div class='clear'></div>
    </div>";
}
?>

<div id="main_content" class="clearfix">
	<div id="left_area" >
		<?php get_template_part('includes/breadcrumbs', 'page'); ?>
    <?php
    if ( $user_ID ) {
       echo $quiz_notifications;
       $quizzes= $wpdb->get_results( 
         "
         SELECT * 
         FROM enp_quiz
         WHERE user_id = " . $user_ID 
       );
    ?>
      <h1>My Quizzes</h1>
      <div class="bootstrap">
        <p><a href="configure-quiz/" class="btn btn-primary btn-sm active" role="button">New quiz</a></p>
        <?php
        if ( $quizzes ) {
        ?>
        <div class="panel panel-info">
          <!-- Default panel contents -->
          <div class="panel-heading">My Quizzes</div>
          <div class='table-responsive'>
            <table class='table'>
              <thead><tr>
                <th>#</th>
                <th>Title</th>
                <th>Type</th>
                <th>Unique Views</th>
                <th>Correct Respones</th>
                <th>% Answering</th>
                <th></th>
                <th></th>
              </tr></thead>
          <?php
          foreach ( $quizzes as $quiz )
          {
            $wpdb->get_var( 
              "
              SELECT ip_address
              FROM enp_quiz_responses   
              WHERE quiz_id = " . $quiz->ID . 
              " GROUP BY ip_address"
            );

            $unique_view_count = $wpdb->num_rows;
          
            $correct_response_count = $wpdb->get_var( 
              "
              SELECT COUNT(*) 
              FROM enp_quiz_responses
              WHERE is_correct = 1 AND quiz_id = " . $quiz->ID
            );
          
            $quiz_total_view_count = $wpdb->get_var( 
              "
              SELECT COUNT(*) 
              FROM enp_quiz_responses
              WHERE correct_option_value = 'quiz-viewed-by-user' AND quiz_id = " . $quiz->ID
            );
            
            $wpdb->get_var( 
              "
              SELECT ip_address
              FROM enp_quiz_responses   
              WHERE correct_option_value != 'quiz-viewed-by-user' 
              AND quiz_id = " . $quiz->ID . 
              " GROUP BY ip_address"
            );

            $unique_answer_count = $wpdb->num_rows;
            
            $percent_answering = $unique_answer_count > 0 ? 
              ROUND($unique_view_count/$unique_answer_count*100, 2) : 0;
            ?>
            <tr>
              <td><?php echo $quiz->ID; ?></td>
              <td><a href="view-quiz?guid=<?php echo $quiz->guid ?>"><?php echo $quiz->title; ?></a></td>
              <td><?php echo $quiz->quiz_type == "slider" ? "Slider" : "Multiple Choice"; ?></td>
              <td><a href="quiz-report/?guid=<?php echo $quiz->guid ?>" class="btn btn-warning btn-xs active" role="button"><?php echo $unique_view_count; ?></a></td>
              <td><a href="quiz-report/?guid=<?php echo $quiz->guid ?>" class="btn btn-warning btn-xs active" role="button"><?php echo $correct_response_count; ?></a></td>
              <td><a href="quiz-report/?guid=<?php echo $quiz->guid ?>" class="btn btn-warning btn-xs active" role="button"><?php echo $percent_answering; ?>%</a></td>
              <?php if ( !$quiz->locked ) { ?>
                <td><a href="configure-quiz/?edit_guid=<?php echo $quiz->guid ?>" class="btn btn-info btn-xs active" role="button">Edit</a></td>
              <?php } else { ?>
                <td>Locked
                  <!-- <span class="glyphicon glyphicon-ban-circle" data-toggle="tooltip" data-placement="top" title="This quiz is locked from editing."></span> -->
                </td>
              <?php } ?>
              <td><a href="list-quizzes/?delete_guid=<?php echo $quiz->guid ?>" onclick="return confirm('Are you sure you want to delete this quiz?')" class="btn btn-danger btn-xs active" role="button">Delete</a></td>
            </tr>
            <?php
          }  
      
          echo "</table>";
          echo "</div>";
          echo "</div>";
        }
        else
        {
          ?>
          <p>Welcome!  Click <i><a href="configure-quiz/">New quiz</a></i> to get started!</p>
          <?php
        }
        ?>
        </div>
    
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