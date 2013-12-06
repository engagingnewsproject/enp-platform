<?php
/*
Template Name: Poll Answer
*/
?>

<div class="content">
    <?php 
    $poll_response = $wpdb->get_row("SELECT * FROM enp_poll_responses WHERE ID = " . $_GET["response_id"] ); 
    ?>
    
    <?php if ( $poll_response->is_correct == 1) { ?>
      <p>Congratulations!</p>
      <?php if ( $poll_response->correct_option_id == -2 ) { ?>
        <p>Your answer of <i><?php echo $poll_response->poll_option_value ?></i> is within the correct range of <i><?php echo $poll_response->correct_option_value ?></i>.</p>
      <?php } else { ?>
        <p><i><?php echo $poll_response->correct_option_value ?></i> is the correct answer!</p>
      <?php } ?>
    <?php } else { ?>
      <p>Sorry!</p>
      <p>Your answer is <i><?php echo $poll_response->poll_option_value ?></i>, but the correct answer is <i><?php echo $poll_response->correct_option_value ?></i></p>
    <?php } ?>
    
    <p>Thanks for taking our poll!</p>
    <p>Built by the <a href="http://engagingnewsproject.org">Engaging News Project</a></p>

</div> <!-- end #main_content -->

<?php get_footer(); ?>