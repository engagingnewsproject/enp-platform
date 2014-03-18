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
    
    if ( $quiz->quiz_type == "slider" ) {
      
      $answer_array = explode(' to ', $quiz_response->correct_option_value);
      
      if ( $answer_array[0] == $answer_array[1] ) {
        $exact_value = true;
        $display_answer = $answer_array[0];
      }
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
        
        if ( $is_correct ) {
          $correct_answer_message = $slider_options->correct_answer_message ?
            $slider_options->correct_answer_message : 
            "Your answer of [user_answer] is within the acceptable range of [lower_range] to [upper_range], with the exact answer being [correct_value].";
    
          $correct_answer_message = str_replace('[user_answer]',$quiz_response_option_value, $correct_answer_message);
          $correct_answer_message = str_replace('[lower_range]', $answer_array[0], $correct_answer_message);
          $correct_answer_message = str_replace('[upper_range]', $answer_array[1], $correct_answer_message);
          // TODO need the exact value...query the db:$slider_options->slider_correct_answer
          $correct_answer_message = str_replace('[correct_value]', $answer_array[0], $correct_answer_message);
        } else {
          $incorrect_answer_message = $slider_options->incorrect_answer_message ?
            $slider_options->incorrect_answer_message : 
            "Your answer is [user_answer], but the correct answer is within the range of [lower_range] to [upper_range].  The exact answer is [correct_value].";
    
          $incorrect_answer_message = str_replace('[user_answer]', $quiz_response_option_value, $incorrect_answer_message);
          $incorrect_answer_message = str_replace('[lower_range]', $answer_array[0], $incorrect_answer_message);
          $incorrect_answer_message = str_replace('[upper_range]', $answer_array[1], $incorrect_answer_message);
          // TODO need the exact value...query the db:$slider_options->slider_correct_answer
          $incorrect_answer_message = str_replace('[correct_value]', $answer_array[0], $incorrect_answer_message);
        }
        
        include(locate_template('self-service-quiz/quiz-answer.php')); 
        ?>
        
        <p>Thanks for taking our quiz!  <a href="<?php echo get_site_url() . '/iframe-quiz/?guid=' . $_GET["guid"];?>" class="btn btn-info">Return to question</a></p>
      </div>
      <div class="form-group iframe-credits">
        <div class="col-sm-12">
          <p>Built by the <a href="<?php echo get_site_url() ?>">Engaging News Project</a></p>
        </div>
      </div>

</div> <!-- end #main_content -->

<?php get_footer(); ?>