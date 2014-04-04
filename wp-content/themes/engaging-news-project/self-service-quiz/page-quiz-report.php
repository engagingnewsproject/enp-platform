<?php
/*
Template Name: Quiz Report
*/
?>
<?php get_header(); ?>

<div id="main_content" class="clearfix quiz-report">
	<div id="left_area">
    <? 

    if ( $_GET["message"] && $_GET["message"] == "responses_deleted") {
      $quiz_notifications =  "
        <div class='bootstrap'>
          <div class='alert alert-success alert-dismissable'>
            <span class='glyphicon glyphicon-info-sign'></span> Quiz responses successfully deleted.
            <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
          </div>
          <div class='clear'></div>
        </div>";
    }
    
    echo $quiz_notifications;
    ?>
    
		<?php get_template_part('includes/breadcrumbs', 'page'); ?>
    <?php
    $user_ID = get_current_user_id(); 
    
    if ( $user_ID ) {
    ?>
    <?php
    
    $quiz = $wpdb->get_row("
      SELECT * FROM enp_quiz 
      WHERE guid = '" . $_GET["guid"] . "' ");
    
    $ignored_ip_list = $wpdb->get_var("
      SELECT value
      FROM enp_quiz_options
      WHERE field = 'report_ignored_ip_addresses' AND
      quiz_id = " . $quiz->ID);
      
    //echo "1: " . $ignored_ip_list . "<br>";
      
    $ignored_ip_sql = "('" . $ignored_ip_list . "";
    
    //echo "2: " . $ignored_ip_sql . "<br>";
    
    $ignored_ip_sql = str_replace(",", "','",$ignored_ip_sql);
    
    //echo "3: " . $ignored_ip_sql . "<br>";
    
    $ignored_ip_sql .= "')";
    
    //echo "4: " . $ignored_ip_sql;
    
    $ignored_ip_sql = " ip_address NOT IN " . $ignored_ip_sql . " AND ";
      
    $mc_answers = $wpdb->get_results("
      SELECT * FROM enp_quiz_options
      WHERE field = 'answer_option' AND quiz_id = " . $quiz->ID . 
      " ORDER BY `display_order`");
      
    $correct_answer_info = $wpdb->get_row("
      SELECT * FROM enp_quiz_options
      WHERE field = 'correct_option' AND
      quiz_id = " . $quiz->ID);
      
    $correct_answer_id = $correct_answer_info->ID;
    $correct_answer_value = $correct_answer_info->value;
    
    $quiz_response_count = $wpdb->get_var( 
      "
      SELECT COUNT(*) 
      FROM enp_quiz_responses
      WHERE 
      #preview_response = false AND
      " . $ignored_ip_sql . "
      correct_option_id != '-1' AND quiz_id = " . $quiz->ID
    );
    
    // USE this to get the current correct answer count 
    // WHERE is_correct = 1 AND quiz_option_id = " . $correct_answer_value . " AND quiz_id = " . $quiz->ID
    $correct_response_count = $wpdb->get_var( 
      "
      SELECT COUNT(*) 
      FROM enp_quiz_responses
      WHERE 
      #preview_response = false AND
      " . $ignored_ip_sql . "
      is_correct = 1 AND quiz_id = " . $quiz->ID
    );
  
    $quiz_total_view_count = $wpdb->get_var( 
      "
      SELECT COUNT(*) 
      FROM enp_quiz_responses
      WHERE 
      #preview_response = false AND
      " . $ignored_ip_sql . "
      correct_option_value = 'quiz-viewed-by-user' AND quiz_id = " . $quiz->ID
    );
    
    $wpdb->get_var( 
      "
      SELECT ip_address
      FROM enp_quiz_responses   
      WHERE 
      #preview_response = false AND
      " . $ignored_ip_sql . "
      quiz_id = " . $quiz->ID . 
      " GROUP BY ip_address"
    );

    $unique_view_count = $wpdb->num_rows;
    
    $wpdb->get_var( 
      "
      SELECT ip_address
      FROM enp_quiz_responses   
      WHERE
      #preview_response = false AND
      " . $ignored_ip_sql . "
      correct_option_value != 'quiz-viewed-by-user' 
      AND quiz_id = " . $quiz->ID . 
      " GROUP BY ip_address"
    );

    $unique_answer_count = $wpdb->num_rows;
    
    if ( $quiz->quiz_type == "slider") {
      $slider_options = $wpdb->get_row("
        SELECT po_high_answer.value 'slider_high_answer', po_low_answer.value 'slider_low_answer', 
          po_correct_answer.value 'slider_correct_answer'
        FROM enp_quiz_options po
        LEFT OUTER JOIN enp_quiz_options po_high_answer ON po_high_answer.field = 'slider_high_answer' AND po.quiz_id = po_high_answer.quiz_id
        LEFT OUTER JOIN enp_quiz_options po_low_answer ON po_low_answer.field = 'slider_low_answer' AND po.quiz_id = po_low_answer.quiz_id
        LEFT OUTER JOIN enp_quiz_options po_correct_answer ON po_correct_answer.field = 'slider_correct_answer' AND po.quiz_id = po_correct_answer.quiz_id
        WHERE po.quiz_id = " . $quiz->ID . "
        GROUP BY po.quiz_id;");
        
      $count_answering_above = $wpdb->get_var( 
        "SELECT COUNT(*) 
        FROM `enp_quiz_responses` 
        WHERE 
        #preview_response = false AND
        " . $ignored_ip_sql . "
        correct_option_id != '-1' 
        AND quiz_option_value > " . $slider_options->slider_high_answer . " 
        AND `quiz_id` = " . $quiz->ID
      );
      
      $count_answering_below = $wpdb->get_var( 
        "SELECT COUNT(*) 
        FROM `enp_quiz_responses` 
        WHERE 
        #preview_response = false AND
        " . $ignored_ip_sql . "
        correct_option_id != '-1' 
        AND quiz_option_value < " . $slider_options->slider_low_answer . " AND `quiz_id` = " . $quiz->ID
      );
      
      
      // $exact_match_count = $wpdb->get_var( 
//         "SELECT COUNT(*) 
//         FROM `enp_quiz_responses` 
//         WHERE 
//         #preview_response = false AND
//         " . $ignored_ip_sql . "
//         correct_option_id != '-1' 
//         AND quiz_option_value = " . $slider_options->slider_correct_answer . " AND `quiz_id` = " . $quiz->ID
//       );
      
       
      if ($quiz_response_count > 0) {
          $percentage_answering_above = 
            ROUND($count_answering_above/$quiz_response_count*100, 2);
          $percentage_answering_below = 
            ROUND($count_answering_below/$quiz_response_count*100, 2);
      }
    }
    ?>
    <h1>Quiz Report: <b><?php echo esc_attr($quiz->title); ?></b></h1>
    <br>
    <div class="bootstrap">
        <div class="panel panel-info">
          <!-- Default panel contents -->
          <div class="panel-heading">Quiz Preview</div>
          <div class="panel-body preview-quiz">
            <?php 
            $quiz_display_width = $wpdb->get_var("
              SELECT value FROM enp_quiz_options
              WHERE field = 'quiz_display_width' AND quiz_id = " . $quiz->ID);
    
            $quiz_display_height = $wpdb->get_var("
              SELECT value FROM enp_quiz_options
              WHERE field = 'quiz_display_height' AND quiz_id = " . $quiz->ID);
        
            $iframe_url = get_site_url() . '/iframe-quiz/?guid=' . $_GET["guid"];
      
            echo '<iframe height="' . $quiz_display_height . '" width="' . $quiz_display_width . '" src="' . $iframe_url . '&amp;preview=true"></iframe>';  
            ?>
          </div>
        </div>
    </div>
    <?php if ( $quiz_response_count > 0 ) {  ?>
    <div id="<?php echo $quiz->quiz_type == "multiple-choice" ? "quiz-mc-answer-pie-graph" : "quiz-slider-answer-pie-graph" ; ?>"></div>
    <?php if ( $quiz->quiz_type == "multiple-choice") { ?>
    <?php } ?>
    <?php //include(locate_template('self-service-quiz/quiz-detailed-responses.php')); ?>
    <div class="bootstrap">
      <div class="panel panel-info">
        <!-- Default panel contents -->
        <div class="panel-heading">Quiz statistics</div>
        <div class="input-group">
          <span class="input-group-addon">Total responses: </span>
          <label class="form-control"><?php echo $quiz_response_count; ?></label>
        </div>
        <div class="input-group">
          <span class="input-group-addon">Incorrect responses: </span>
          <label class="form-control"><?php echo $quiz_response_count-$correct_response_count; ?></label>
        </div>
        <div class="input-group">
          <span class="input-group-addon">Correct responses: </span>
          <label class="form-control"><?php echo $correct_response_count; ?></label>
        </div>
        <?php //if ($quiz->quiz_type == "slider") { ?>
        <!-- <div class="input-group">
          <span class="input-group-addon">Exact matches: </span>
          <label class="form-control"><?php //echo $exact_match_count; ?></label>
          <input type="hidden" id="exact-matches" value="<?php //echo $exact_match_count ?>">
        </div> -->
        <?php //}?>
        <div class="input-group">
          <span class="input-group-addon">Percentage correct: </span>
          <label class="form-control"><?php echo ROUND($correct_response_count/$quiz_response_count*100, 2); ?>%</label>
          <input type="hidden" id="percentage-correct" value="<?php echo ROUND($correct_response_count/$quiz_response_count*100, 2); ?>">
        </div>
        <?php if ($quiz->quiz_type == "slider") { ?>
        <!-- <div class="input-group">
          <span class="input-group-addon">Percentage exact: </span>
          <label class="form-control"><?php echo ROUND($exact_match_count/$quiz_response_count*100, 2); ?>%</label>
          <input type="hidden" id="percentage-exact" value="<?php echo ROUND($exact_match_count/$quiz_response_count*100, 2); ?>">
        </div> -->
        <div class="input-group">
          <span class="input-group-addon">Percentage answering above: </span>
          <label class="form-control"><?php echo $percentage_answering_above; ?>%</label>
          <input type="hidden" id="percentage-answering-above" value="<?php echo $percentage_answering_above ?>">
        </div>
        <div class="input-group">
          <span class="input-group-addon">Percentage answering below: </span>
          <label class="form-control"><?php echo $percentage_answering_below; ?>%</label>
          <input type="hidden" id="percentage-answering-below" value="<?php echo $percentage_answering_below ?>">
        </div>
        <?php }?>
        <div class="input-group">
          <span class="input-group-addon">Total views: </span>
          <label class="form-control"><?php echo $quiz_total_view_count; ?></label>
        </div>
        <div class="input-group">
          <span class="input-group-addon">Unique views: </span>
          <label class="form-control"><?php echo $unique_view_count; ?></label>
        </div>
        <div class="input-group">
          <span class="input-group-addon">Percentage answering: </span>
          <label class="form-control"><?php echo ROUND($unique_view_count/$unique_answer_count*100, 2); ?>%</label>
        </div>
      </div>
    </div>
    
    <?php if ( $quiz->quiz_type == "multiple-choice") { ?>
    <div class="bootstrap">
      <div class="panel panel-info">
        <!-- Default panel contents -->
        <div class="panel-heading">Response Detail</div>
          <div class='table-responsive'>
            <table class='table'>
              <thead><tr>
                <!-- <th>ID</th> -->
                <th>Answer</th>
                <th># Responded</th>
                <th>Display Order</th>
                <!-- <th>% Selected</th> -->
              </tr></thead>
              <?php
              foreach ( $mc_answers as $mc_answer ) { 
                $quiz_responses[$mc_answer->ID] = $wpdb->get_var( 
                  "SELECT COUNT(*) 
                  FROM enp_quiz_responses
                  WHERE 
                  #preview_response = false AND
                  " . $ignored_ip_sql . "
                  quiz_option_id = " . $mc_answer->ID . "
                  AND quiz_id = " . $quiz->ID
                );
                ?>
                <tr class="<?php echo $correct_answer_id == $mc_answer->ID ? "correct" : ""; ?>">
                  <!-- <td><?php //echo $mc_answer->ID ?></td> -->
                  <td><input type="hidden" class="form-control quiz-responses-option" id="<?php echo $mc_answer->ID ?>" value="<?php echo $mc_answer->value ?>"><?php echo $mc_answer->value ?></td>
                  <td><input type="hidden" class="form-control quiz-responses-option-count" id="quiz-responses-option-count-<?php echo $mc_answer->ID ?>" value="<?php echo $quiz_responses[$mc_answer->ID] ?>"><?php echo $quiz_responses[$mc_answer->ID] ?></td>
                  <td><?php echo $mc_answer->display_order ?></td>
                  <!-- <td><?php// echo ROUND($quiz_responses[$mc_answer->ID]/$quiz_response_count*100, 2) ?>%</td> -->
                </tr>
                <?php
              }
              ?>
            </table>
          </div>
          
        </div>
    </div><!-- END Bootstrap -->
    <?php } ?>
    
    <?php } else { ?>
    <div class="bootstrap">
        <div class="panel panel-info">
          <!-- Default panel contents -->
          <div class="panel-heading">Quiz Report</div>
          <div class="panel-body preview-quiz">
            <p>No responses for this quiz just yet!</p>
          </div>
        </div>
    </div>
    <?php } ?>
    
    <div class="bootstrap">
        <div class="panel panel-info">
          <!-- Default panel contents -->
          <div class="panel-heading">Quiz Report Options</div>
          <div class="panel-body">
            <form id="quiz-report-form" class="form-horizontal" role="form" method="post" action="<?php echo get_stylesheet_directory_uri(); ?>/self-service-quiz/include/process-quiz-report-form.php">
              <input type="hidden" name="input-id" id="input-id" value="<?php echo $quiz->ID; ?>">
    		  <input type="hidden" name="input-guid" id="input-guid" value="<?php echo $quiz->guid; ?>">
              
              <!-- BEGIN QUIZ QUESTION -->
	          <div class="form-group">
	            <label for="input-question" class="col-sm-3">Ignored IP Addresses <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="top" title="Specify a comma separated list of IP Addresses to exclude form this report"></span></label>
	            <div class="col-sm-9">
                  <textarea class="form-control" rows="2" name="input-report-ip-addresses" id="input-report-ip-addresses" placeholder="Enter IP addresses to ignore (comma separated)"><?php echo esc_attr($ignored_ip_list); ?></textarea>
	            </div>
	          </div>
              
              <div class="form-group">
  	            <div class="col-sm-12">
                    <button id="add-my-ip" class="btn btn-sm btn-primary add-my-ip">Add my current IP Address</button>
  	            </div>
              </div>
              <!-- END QUIZ QUESTION -->
              
	          <div class="form-group">
                <div class="col-sm-3">
                </div>
	            <div class="col-sm-9">
	              <button type="submit" class="btn btn-primary">Update</button>
	            </div>
	          </div>
          </div>
        </div>
    </div>
    
    <div class="bootstrap"><p><a href="view-quiz?guid=<?php echo $quiz->guid ?>" class="btn btn-primary btn-xs active">View Quiz</a> | <?php if ( $quiz_response_count > 0 ) {  ?> <a href="<?php echo get_stylesheet_directory_uri(); ?>/self-service-quiz/include/process-quiz-delete-responses.php?guid=<?php echo $quiz->guid ?>" class="btn btn-danger btn-xs active delete-responses-button" role="button">Delete Responses</a> | <?php }  ?><a href="list-quizzes/" class="btn btn-primary btn-xs active" role="button">Back to Quizzes</a></p></div>
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