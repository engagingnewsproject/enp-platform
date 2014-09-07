<?php
include('../../../../../wp-config.php');
global $wpdb;
if( $_POST['input-question'] ) {
  $date = date('Y-m-d H:i:s');
  $quiz_updated = false;
  
  if ( $_POST['input-guid'] ) {
    $guid = $_POST['input-guid'];
    $quiz_updated = true;
  } else {
    $guid = uniqid('', true) . '_' . md5(mt_rand());
  }
  
  $quiz_type = $_POST['quiz-type'];
  
  $quiz_id = processQuiz($quiz_type, $guid, $date, $wpdb);

  processAnswers($quiz_id, $quiz_type, $date, $wpdb);
  
  processStyleOptions($quiz_id, $date, $wpdb);

  if( $_POST['quiz-new-question'] == "newQuestion" ) {
  	$curr_quiz_id = $_POST['curr-quiz-id'];
  	$next_quiz_id = $quiz_id;
  	$enp_quiz_next = processNextQuestion($curr_quiz_id, $next_quiz_id, $wpdb);
	echo "<script>console.log('newQuiz');</script>";		 // remove console.log ||KVB
	echo "<script>console.log('".$curr_quiz_id."');</script>";		 // remove console.log ||KVB
	echo "<script>console.log('".$enp_quiz_next."');</script>";			 // remove console.log ||KVB
	header("Location: " . get_site_url() . "/configure-quiz?curr=" . $curr_quiz_id );
  } else if( $_POST['quiz-new-question'] == "newQuestionAddQuestion" ) {
  	$curr_quiz_id = $_POST['curr-quiz-id'];
  	$next_quiz_id = $quiz_id;
  	$enp_quiz_next = processNextQuestion($curr_quiz_id, $next_quiz_id, $wpdb);
	echo "<script>console.log('newQuiz');</script>";		 // remove console.log ||KVB
	echo "<script>console.log('".$curr_quiz_id."');</script>";		 // remove console.log ||KVB
	echo "<script>console.log('".$enp_quiz_next."');</script>";			 // remove console.log ||KVB
	header("Location: " . get_site_url() . "/configure-quiz?curr=" . $curr_quiz_id );
  } else if( $_POST['quiz-new-question'] == "noNewQuestionAddQuestion" ) {
  	$curr_quiz_id = $_POST['curr-quiz-id'];
  	$next_quiz_id = $quiz_id;
  	$enp_quiz_next = processNextQuestion($curr_quiz_id, $next_quiz_id, $wpdb);
	echo "<script>console.log('noNewQuiz');</script>";
	header("Location: " . get_site_url() . "/view-quiz?guid=" . $guid . ($quiz_updated ? "&quiz_updated=1" : "&quiz_updated=2") );
  } else if( $_POST['quiz-new-question'] == "noNewQuestion" ) {
	echo "<script>console.log('noNewQuiz');</script>";
	header("Location: " . get_site_url() . "/view-quiz?guid=" . $guid . ($quiz_updated ? "&quiz_updated=1" : "&quiz_updated=2") );
  }
  //NTH Check for update errors in DB and show gracefully to the user
  
}
function processQuiz($quiz_type, $guid, $date, $wpdb) {
  $user_ID = get_current_user_id(); 
  $title = stripslashes($_POST['input-title']);
  $question = stripslashes($_POST['input-question']);
  $quiz_id = $_POST['input-id'];
  
  if ( $_POST['input-guid'] ) {
    updateQuiz($title, $question, $user_ID, $guid, $date, $wpdb);
  } else {
    $quiz_id = createQuiz($title, $question, $quiz_type, $user_ID, $guid, $date, $wpdb);
  }
  
  return $quiz_id; 
}

function updateQuiz($title, $question, $user_ID, $guid, $date, $wpdb) {
  $wpdb->update( 
  	'enp_quiz', 
  	array( 
  	  'title' => $title,	
      'question' => $question,	
      'last_modified_user_id' => $user_ID,
      'last_modified_datetime' => $date
  	), 
  	array( 'guid' => $guid ), 
  	array( 
  	  '%s',	
      '%s',	
      '%d',
      '%s'
  	), 
  	array( '%s' )
  );
}

function createQuiz($title, $question, $quiz_type, $user_ID, $guid, $date, $wpdb) {  
  $wpdb->insert( 'enp_quiz', 
    array(
        'guid' => $guid, 
        'user_id' => $user_ID , 
        'title' => $title, 
        'question' => $question, 
        'quiz_type' => $quiz_type, 
        'create_datetime' => $date, 
        'last_modified_datetime' => $date, 
        'last_modified_user_id' => $user_ID, 
        'active' => 1 ),
    array(
        '%s', 
        '%d', 
        '%s', 
        '%s', 
        '%s', 
        '%s', 
        '%s', 
        '%d', 
        '%d'  )
    );
  
  return $wpdb->insert_id;
}

function processNextQuestion($curr_quiz_id, $next_quiz_id, $wpdb) {  
  $wpdb->insert( 'enp_quiz_next', 
    array(
        'curr_quiz_id' => $curr_quiz_id, 
        'next_quiz_id' => $next_quiz_id ),
    array(
        '%d', 
        '%d' )
    );
  
  return $wpdb->insert_id;
}

function processAnswers($quiz_id, $quiz_type, $date, $wpdb) {
  // Delete all options
  $wpdb->delete( 'enp_quiz_options', array( 'quiz_id' => $quiz_id ), array( '%d' ) );
  
  if ( $quiz_type == "multiple-choice") {
    processMCAnswers($quiz_id, $date, $wpdb);
  } else {
    processSliderOptions($quiz_id, $date, $wpdb);
  }
  
  processAnswerMessages($quiz_id, $quiz_type, $date, $wpdb);
}

function processMCAnswers($quiz_id, $date, $wpdb) {
  $mc_answer_count = $_POST['mc-answer-count'];
  $correct_option = $_POST['correct-option'];

  for ($i = 1; $i <= $mc_answer_count; $i++) {
    if ( !empty($_POST['mc-answer-' . $i]) ) {
      $wpdb->insert( 'enp_quiz_options', 
          array(
              'quiz_id' => $quiz_id, 
              'field' => 'answer_option', 
              'value' => stripslashes($_POST['mc-answer-' . $i]), 
              'create_datetime' => $date, 
              'display_order' => $_POST['mc-answer-order-' . $i] )
          , array( 
              '%d',
      	      '%s', 
      	      '%s', 
              '%s', 
              '%d' 
      	  ));
      
      if ( $correct_option == 'mc-answer-' . $i) {
        $correct_option_id = $wpdb->insert_id;
        $wpdb->insert( 'enp_quiz_options', 
            array(  
                'quiz_id' => $quiz_id, 
                'field' => 'correct_option', 
                'value' => $correct_option_id, 
                'create_datetime' => $date, 
                'display_order' => 0 )
            , array( 
                '%d',
        	    '%s', 
        	    '%s', 
                '%s', 
                '%d' 
        	  ));
      }
    }
  }
}

function processSliderOptions($quiz_id, $date, $wpdb) {
  $slider_high = $_POST['slider-high'];
  $slider_low = $_POST['slider-low'];
  $slider_start = $_POST['slider-start'];
  $slider_increment = $_POST['slider-increment'];
  $slider_correct_answer = $_POST['slider-correct-answer'];
  $use_slider_range = $_POST['use-slider-range'];
  $slider_high_answer = $_POST['slider-high-answer'];
  $slider_low_answer = $_POST['slider-low-answer'];
  $slider_label = stripslashes($_POST['slider-label']);
  
  if ( !isset($_POST['use-slider-range']) ) {
    $slider_low_answer = $slider_correct_answer;
    $slider_high_answer = $slider_correct_answer;
  }
  
  // Add new options
  $wpdb->insert( 'enp_quiz_options', 
      array( 
          'quiz_id' => $quiz_id, 
          'field' => 'slider_high', 
          'value' => $slider_high, 
          'create_datetime' => $date, 
          'display_order' => 0),
      array( 
          '%d', 
          '%s', 
          '%d',
          '%s', 
          '%d')
      );
          
  $wpdb->insert( 'enp_quiz_options', 
      array( 
          'quiz_id' => $quiz_id, 
          'field' => 'slider_low', 
          'value' => $slider_low, 
          'create_datetime' => $date, 
          'display_order' => 0),
      array( 
          '%d', 
          '%s', 
          '%d',
          '%s', 
          '%d')
      );
    
  $wpdb->insert( 'enp_quiz_options', 
      array( 
          'quiz_id' => $quiz_id, 
          'field' => 'slider_start', 
          'value' => $slider_start, 
          'create_datetime' => $date, 
          'display_order' => 0),
      array( 
          '%d', 
          '%s', 
          '%d',
          '%s', 
          '%d')      
      );
    
  $wpdb->insert( 'enp_quiz_options', 
      array( 
          'quiz_id' => $quiz_id, 
          'field' => 'slider_increment', 
          'value' => $slider_increment, 
          'create_datetime' => $date, 
          'display_order' => 0),
      array( 
          '%d', 
          '%s', 
          '%d',
          '%s', 
          '%d')    
      );
    
  $wpdb->insert( 'enp_quiz_options', 
      array( 
          'quiz_id' => $quiz_id, 
          'field' => 'slider_correct_answer', 
          'value' => $slider_correct_answer, 
          'create_datetime' => $date, 
          'display_order' => 0),
      array( 
          '%d', 
          '%s', 
          '%d',
          '%s', 
          '%d')    
      );
  
  $wpdb->insert( 'enp_quiz_options', 
      array( 
          'quiz_id' => $quiz_id, 
          'field' => 'slider_high_answer', 
          'value' => $slider_high_answer, 
          'create_datetime' => $date, 
          'display_order' => 0),
      array( 
          '%d', 
          '%s', 
          '%d',
          '%s', 
          '%d')    
      );
    
  $wpdb->insert( 'enp_quiz_options', 
      array( 
          'quiz_id' => $quiz_id, 
          'field' => 'slider_low_answer', 
          'value' => $slider_low_answer, 
          'create_datetime' => $date, 
          'display_order' => 0),
      array( 
          '%d', 
          '%s', 
          '%d',
          '%s', 
          '%d')    
      );
    
  $wpdb->insert( 'enp_quiz_options', 
      array( 
          'quiz_id' => $quiz_id, 
          'field' => 'slider_label', 
          'value' => $slider_label, 
          'create_datetime' => $date, 
          'display_order' => 0),
      array( 
          '%d', 
          '%s', 
          '%s',
          '%s', 
          '%d')    
      );
  
}

function processAnswerMessages($quiz_id, $quiz_type, $date, $wpdb) {
  $default_mc_correct_answer_message = "Your answer of [user_answer] is correct!"; 
  $default_mc_incorrect_answer_message = "Your answer is [user_answer], but the correct answer is [correct_value]."; 

  $default_slider_correct_answer_message = "Your answer of [user_answer] is correct!";   
  $default_slider_incorrect_answer_message = "Your answer is [user_answer], but the correct answer is [correct_value]."; 

  $default_slider_range_correct_answer_message = "Your answer of [user_answer] is within the acceptable range of [lower_range] to [upper_range], with the exact answer being [correct_value].";
  $default_slider_range_incorrect_answer_message = "Your answer is [user_answer], but the correct answer is within the range of [lower_range] to [upper_range].  The exact answer is [correct_value]."; 
  
  
  if ( $_POST['input-correct-answer-message'] ) {
    $correct_answer_message = stripslashes($_POST['input-correct-answer-message']);
  } else if ( $quiz_type == "multiple-choice" ) {
    // Multiple Choice Answers
    $correct_answer_message = $default_mc_correct_answer_message;
  } else {
    //Slider 
    if ( !isset($_POST['use-slider-range']) ) {
      // Exact Value Slider
      $correct_answer_message = $default_slider_correct_answer_message;
    } else {
      $correct_answer_message = $default_slider_range_correct_answer_message;
    }
  }
  
  if ( $_POST['input-incorrect-answer-message'] ) {
    $incorrect_answer_message = stripslashes($_POST['input-incorrect-answer-message']);
  } else if ( $quiz_type == "multiple-choice" ) {
    // Multiple Choice Answers
    $incorrect_answer_message = $default_mc_incorrect_answer_message;
  } else {
    //Slider 
    if ( !isset($_POST['use-slider-range']) ) {
      // Exact Value Slider
      $incorrect_answer_message = $default_slider_incorrect_answer_message;
    } else {
      $incorrect_answer_message = $default_slider_range_incorrect_answer_message;
    }
  }
  
  $wpdb->insert( 'enp_quiz_options', 
      array( 'quiz_id' => $quiz_id, 'field' => 'correct_answer_message', 'value' => $correct_answer_message, 'create_datetime' => $date, 'display_order' => 0 ),
      array( 
          '%d', 
          '%s', 
          '%s',
          '%s', 
          '%d')    
    );
  
  $wpdb->insert( 'enp_quiz_options', 
      array( 'quiz_id' => $quiz_id, 'field' => 'incorrect_answer_message', 'value' => $incorrect_answer_message, 'create_datetime' => $date, 'display_order' => 0 ),
      array( 
          '%d', 
          '%s', 
          '%s',
          '%s', 
          '%d')    
    );
}

function processStyleOptions($quiz_id, $date, $wpdb) {
  $quiz_background_color = $_POST['quiz-background-color'] ? stripslashes($_POST['quiz-background-color']) : "#ffffff";
  $quiz_text_color = $_POST['quiz-text-color'] ? stripslashes($_POST['quiz-text-color']) : "#000000";
  // $quiz_display_border = $_POST['quiz-display-border'];
  $quiz_display_width = $_POST['quiz-display-width'] ? stripslashes($_POST['quiz-display-width']) : "336px";
  $quiz_display_height = $_POST['quiz-display-height'] ? stripslashes($_POST['quiz-display-height']) : "280px";
  // $quiz_display_padding = $_POST['quiz-display-padding'];
  $quiz_show_title = stripslashes($_POST['quiz-show-title']);
  $quiz_display_css = stripslashes($_POST['quiz-display-css']);
  
  $wpdb->insert( 'enp_quiz_options', 
      array( 'quiz_id' => $quiz_id, 'field' => 'quiz_background_color', 'value' => $quiz_background_color, 'create_datetime' => $date, 'display_order' => 0 ),
      array( 
          '%d', 
          '%s', 
          '%s',
          '%s', 
          '%d')    
    );
  
  $wpdb->insert( 'enp_quiz_options', array( 'quiz_id' => $quiz_id, 'field' => 'quiz_text_color', 'value' => $quiz_text_color, 'create_datetime' => $date, 'display_order' => 0 ),
      array( 
          '%d', 
          '%s', 
          '%s',
          '%s', 
          '%d')   );
  
  $wpdb->insert( 'enp_quiz_options', array( 'quiz_id' => $quiz_id, 'field' => 'quiz_display_width', 'value' => $quiz_display_width, 'create_datetime' => $date, 'display_order' => 0 ),
      array( 
          '%d', 
          '%s', 
          '%s',
          '%s', 
          '%d')   );
  
  $wpdb->insert( 'enp_quiz_options', array( 'quiz_id' => $quiz_id, 'field' => 'quiz_display_height', 'value' => $quiz_display_height, 'create_datetime' => $date, 'display_order' => 0 ),
      array( 
          '%d', 
          '%s', 
          '%s',
          '%s', 
          '%d')   );
  
  $wpdb->insert( 'enp_quiz_options', array( 'quiz_id' => $quiz_id, 'field' => 'quiz_show_title', 'value' => $quiz_show_title, 'create_datetime' => $date, 'display_order' => 0 ),
      array( 
          '%d', 
          '%s', 
          '%s',
          '%s', 
          '%d')   );
  
  $wpdb->insert( 'enp_quiz_options', array( 'quiz_id' => $quiz_id, 'field' => 'quiz_display_css', 'value' => $quiz_display_css, 'create_datetime' => $date, 'display_order' => 0 ),
      array( 
          '%d', 
          '%s', 
          '%s',
          '%s', 
          '%d')   );
  
  // $wpdb->insert( 'enp_quiz_options', array( 'quiz_id' => $quiz_id, 'field' => 'quiz_display_padding', 'value' => $quiz_display_padding, 'create_datetime' => $date, 'display_order' => 0 ));
  
  // $wpdb->insert( 'enp_quiz_options', array( 'quiz_id' => $quiz_id, 'field' => 'quiz_display_border', 'value' => $quiz_display_border, 'create_datetime' => $date, 'display_order' => 0 ));
}
?>