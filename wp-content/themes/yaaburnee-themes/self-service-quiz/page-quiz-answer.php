<?php
/*
Template Name: Quiz Answer
*/
?>

<!DOCTYPE html>
<!--[if IE 6]>
<html id="ie6" <?php language_attributes(); ?>>
<![endif]-->
<!--[if IE 7]>
<html id="ie7" <?php language_attributes(); ?>>
<![endif]-->
<!--[if IE 8]>
<html id="ie8" <?php language_attributes(); ?>>
<![endif]-->
<!--[if !(IE 6) | !(IE 7) | !(IE 8)  ]><!-->
<html <?php language_attributes(); ?>>
<!--<![endif]-->
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" type="text/css" media="screen" />
  <link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri() . '/self-service-quiz/css/iframe.css'; ?>" type="text/css" media="screen" />
  <?php do_action('et_head_meta'); ?>
</head>
<body <?php body_class(); ?>>

  <?php 
    $quiz = $wpdb->get_row("
      SELECT * FROM enp_quiz 
      WHERE guid = '" . $_GET["guid"] . "' ");
  
    $quiz_background_color = $wpdb->get_var("
      SELECT value FROM enp_quiz_options
      WHERE field = 'quiz_background_color' AND quiz_id = " . $quiz->ID);
    
    $quiz_text_color = $wpdb->get_var("
      SELECT value FROM enp_quiz_options
      WHERE field = 'quiz_text_color' AND quiz_id = " . $quiz->ID);
    
    $quiz_display_border = $wpdb->get_var("
      SELECT value FROM enp_quiz_options
      WHERE field = 'quiz_display_border' AND quiz_id = " . $quiz->ID);
  
    $quiz_display_width = $wpdb->get_var("
      SELECT value FROM enp_quiz_options
      WHERE field = 'quiz_display_width' AND quiz_id = " . $quiz->ID);
    
    $quiz_display_padding = $wpdb->get_var("
      SELECT value FROM enp_quiz_options
      WHERE field = 'quiz_display_padding' AND quiz_id = " . $quiz->ID);
    
    $quiz_show_title = $wpdb->get_var("
      SELECT value FROM enp_quiz_options
      WHERE field = 'quiz_show_title' AND quiz_id = " . $quiz->ID);
  ?>

<div style="background:<?php echo $quiz_background_color ;?>;color:<?php echo $quiz_text_color ;?>;width:<?php echo $quiz_display_width ;?>;padding:<?php echo $quiz_display_padding ;?>;border:<?php echo $quiz_display_border ;?>;" class="bootstrap quiz-answer">
    <?php 
    $quiz_response = $wpdb->get_row("SELECT * FROM enp_quiz_responses WHERE ID = " . $_GET["response_id"] ); 

    $exact_value = false;
    $display_answer = $quiz_response->correct_option_value;
  
    if ( $quiz->quiz_type == "multiple-choice" ) {
      $mc_options = $wpdb->get_row("
        SELECT correct.value 'correct_answer_message', incorrect.value 'incorrect_answer_message'
        FROM enp_quiz_options po
        LEFT OUTER JOIN enp_quiz_options correct ON correct.field = 'correct_answer_message' AND po.quiz_id = correct.quiz_id
        LEFT OUTER JOIN enp_quiz_options incorrect ON incorrect.field = 'incorrect_answer_message' AND po.quiz_id = incorrect.quiz_id
        WHERE po.quiz_id = " . $quiz->ID . "
        GROUP BY po.quiz_id;");
    } else if ( $quiz->quiz_type == "slider" ) {
      
      $answer_array = explode(' to ', $quiz_response->correct_option_value);
      
      if ( $answer_array[0] == $answer_array[1] ) {
        $exact_value = true;
        $display_answer = $answer_array[0];
      }
      
      $wpdb->query('SET OPTION SQL_BIG_SELECTS = 1');
      $slider_options = $wpdb->get_row("
        SELECT po_high_answer.value 'slider_high_answer', po_low_answer.value 'slider_low_answer', po_correct_answer.value 'slider_correct_answer', po_correct_message.value 'correct_answer_message', po_incorrect_message.value 'incorrect_answer_message'
        FROM enp_quiz_options po
        LEFT OUTER JOIN enp_quiz_options po_high_answer ON po_high_answer.field = 'slider_high_answer' AND po.quiz_id = po_high_answer.quiz_id
        LEFT OUTER JOIN enp_quiz_options po_low_answer ON po_low_answer.field = 'slider_low_answer' AND po.quiz_id = po_low_answer.quiz_id
        LEFT OUTER JOIN enp_quiz_options po_correct_answer ON po_correct_answer.field = 'slider_correct_answer' AND po.quiz_id = po_correct_answer.quiz_id
        LEFT OUTER JOIN enp_quiz_options po_correct_message ON po_correct_message.field = 'correct_answer_message' AND po.quiz_id = po_correct_message.quiz_id
        LEFT OUTER JOIN enp_quiz_options po_incorrect_message ON po_incorrect_message.field = 'incorrect_answer_message' AND po.quiz_id = po_incorrect_message.quiz_id
        WHERE po.quiz_id = " . $quiz->ID . "
        GROUP BY po.quiz_id;");
    } else {
      $exact_value = true;
    }
    ?>
    <div class="col-sm-12">
        <?php 
        $is_correct = $quiz_response->is_correct;
        $correct_option_id = $quiz_response->correct_option_id; 
        $quiz_response_option_value = $quiz_response->quiz_option_value;
        $question_text = esc_attr($quiz->question);
        
        if ( $quiz->quiz_type == "multiple-choice" ) {
          $correct_answer_message = $mc_options->correct_answer_message; 
          $incorrect_answer_message = $mc_options->incorrect_answer_message;
          
          if ( $is_correct ) {
            $correct_answer_message = str_replace('[user_answer]', $display_answer, $correct_answer_message);
            $correct_answer_message = str_replace('[correct_value]', $display_answer, $correct_answer_message);
          } else {
            $incorrect_answer_message = str_replace('[user_answer]', $quiz_response_option_value, $incorrect_answer_message);
            $incorrect_answer_message = str_replace('[correct_value]', $display_answer, $incorrect_answer_message);
          }
        } else if ( $quiz->quiz_type == "slider" ) {
          if ( $is_correct ) {
            $correct_answer_message = $slider_options->correct_answer_message;
    
            $correct_answer_message = str_replace('[user_answer]',$quiz_response_option_value, $correct_answer_message);
            $correct_answer_message = str_replace('[lower_range]', $slider_options->slider_low_answer, $correct_answer_message);
            $correct_answer_message = str_replace('[upper_range]', $slider_options->slider_high_answer, $correct_answer_message);
            $correct_answer_message = str_replace('[correct_value]', $slider_options->slider_correct_answer, $correct_answer_message);
          } else {
            $incorrect_answer_message = $slider_options->incorrect_answer_message;
            
            $incorrect_answer_message = str_replace('[user_answer]', $quiz_response_option_value, $incorrect_answer_message);
            $incorrect_answer_message = str_replace('[lower_range]', $slider_options->slider_low_answer, $incorrect_answer_message);
            $incorrect_answer_message = str_replace('[upper_range]', $slider_options->slider_high_answer, $incorrect_answer_message);
            $incorrect_answer_message = str_replace('[correct_value]', $slider_options->slider_correct_answer, $incorrect_answer_message);
          }
        }
        
        
        include(locate_template('self-service-quiz/quiz-answer.php')); 
        ?>
        
        <p>Thanks for taking our quiz!  <a href="<?php echo get_site_url() . '/iframe-quiz/?guid=' . $_GET["guid"];?>" class="btn btn-info btn-xs">Return to question</a></p>
      </div>
      <div class="form-group iframe-credits">
        <div class="col-sm-12">
          <p>Built by the <a href="<?php echo get_site_url() ?>">Engaging News Project</a></p>
        </div>
      </div>

</div> <!-- end #main_content -->

<?php get_footer(); ?>