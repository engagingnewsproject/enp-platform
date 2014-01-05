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
        <?php if ( $quiz_response->is_correct == 1) { ?>
          <h3><span class="glyphicon glyphicon-check"></span> Congratulations!</h3>
          <div class="alert alert-success">
            <?php if ( $quiz_response->correct_option_id == -2 && $exact_value ) { ?>
              Your answer of <i><?php echo $quiz_response->quiz_option_value ?></i> is within the correct range of <i><?php echo $display_answer ?></i>.
            <?php } else { ?>
              <i><?php echo $display_answer ?></i> is the correct answer!
            <?php } ?>
          </div>
        <?php } else { ?>
          <h3><span class="glyphicon glyphicon-info-sign"></span> Sorry!</h3>
          <div class="alert alert-info">Your answer is <i><?php echo $quiz_response->quiz_option_value ?></i>, but the correct answer is <?php echo !$exact_value ? "within the range of " : ""; ?><i><?php echo $display_answer ?></i>.</div>
        <?php } ?>
        
        <p>Thanks for taking our quiz!</p>
      </div>
      <div class="form-group iframe-credits">
        <div class="col-sm-12">
          <p>Built by the <a href="<?php echo get_site_url() ?>">Engaging News Project</a></p>
        </div>
      </div>

</div> <!-- end #main_content -->

<?php get_footer(); ?>