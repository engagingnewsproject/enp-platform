<?php 
  $poll = $wpdb->get_row("
    SELECT * FROM enp_poll 
    WHERE guid = '" . $_GET["guid"] . "' ");
  
  if ( is_page('iframe-poll') ) {
    $date = date('Y-m-d H:i:s');
    $guid = $_POST['input-guid'];
    $correct_option_id = -1;
    $correct_option_value = -1;
    $poll_answer_id = -1;
    $poll_answer_value = -1;
    $is_correct = 0;

    $wpdb->insert( 'enp_poll_responses', 
    array( 'poll_id' => $poll->ID , 'poll_option_id' => $poll_answer_id, 'poll_option_value' => $poll_answer_value, 
      'correct_option_id' => $correct_option_id, 'correct_option_value' => $correct_option_value, 
      'is_correct' => $is_correct, 'ip_address' => $_SERVER['REMOTE_ADDR'], 'response_datetime' => $date ));
    $id = $wpdb->insert_id;
  }
?>

<?php if ( $poll ) { ?>
<form id="poll-form" class="form-horizontal bootstrap" role="form" method="post" action="<?php echo get_stylesheet_directory_uri(); ?>/self-service-poll/include/process-poll-response.php">
  <input type="hidden" name="input-id" id="input-id" value="<?php echo $poll->ID; ?>">
  <input type="hidden" name="input-guid" id="input-guid" value="<?php echo $poll->guid; ?>">
  <input type="hidden" name="poll-type" id="poll-type" value="<?php echo $poll->poll_type; ?>">
  <h3><?php echo $poll->title; ?></h3>
  <p><?php echo $poll->question; ?></p>
  
  <?php if ( $poll->poll_type == "multiple-choice" ) { ?>
  <input type="hidden" name="correct-option-id" id="correct-option-id" value="1">
  <input type="hidden" name="correct-option-value" id="correct-option-value" value="option1">
  <div class="form-group">
    <?php 
    $mc_answers = $wpdb->get_results("
      SELECT * FROM enp_poll_options
      WHERE field = 'answer_option' AND poll_id = " . $poll->ID . 
      " ORDER BY `display_order`");
      
    foreach ( $mc_answers as $mc_answer ) { 
    ?>
      <div class="radio">
        <label>
          <input type="hidden" name="option-radio-id-<?php echo $mc_answer->ID; ?>" id="option-radio-id-<?php echo $mc_answer->ID; ?>" value="<?php echo $mc_answer->value; ?>">
          <input type="radio" name="pollRadios" id="option-radio-<?php echo $mc_answer->ID; ?>" value="<?php echo $mc_answer->ID; ?>" >
          <?php echo $mc_answer->value; ?>
        </label>
      </div>
    <?php 
    }
    ?>
	</div>	
  <?php } ?>
  
  <?php if ( $poll->poll_type == "slider" ) { 
    $slider_options = $wpdb->get_row("
      SELECT po_high.value 'slider_high', po_low.value 'slider_low', po_start.value 'slider_start', po_increment.value 'slider_increment', po_high_answer.value 'slider_high_answer', po_low_answer.value 'slider_low_answer', po_label.value 'slider_label'
      FROM enp_poll_options po
      LEFT OUTER JOIN enp_poll_options po_high ON po_high.field = 'slider_high' AND po.poll_id = po_high.poll_id
      LEFT OUTER JOIN enp_poll_options po_low ON po_low.field = 'slider_low' AND po.poll_id = po_low.poll_id
      LEFT OUTER JOIN enp_poll_options po_start ON po_start.field = 'slider_start' AND po.poll_id = po_start.poll_id
      LEFT OUTER JOIN enp_poll_options po_increment ON po_increment.field = 'slider_increment' AND po.poll_id = po_increment.poll_id
      LEFT OUTER JOIN enp_poll_options po_high_answer ON po_high_answer.field = 'slider_high_answer' AND po.poll_id = po_high_answer.poll_id
      LEFT OUTER JOIN enp_poll_options po_low_answer ON po_low_answer.field = 'slider_low_answer' AND po.poll_id = po_low_answer.poll_id
      LEFT OUTER JOIN enp_poll_options po_label ON po_label.field = 'slider_label' AND po.poll_id = po_label.poll_id
      WHERE po.poll_id = " . $poll->ID . "
      GROUP BY po.poll_id");
    ?>
    <div class="form-group">
      <div class="col-xs-2">
	      <input type="hidden" name="slider-high-answer" id="slider-low-answer" value="<?php echo $slider_options->slider_high_answer ?>" />
        <input type="hidden" name="slider-low-answer" id="slider-low-answer" value="<?php echo $slider_options->slider_low_answer ?>" />
	      <input class="form-control" type="text" name="slider-value" id="slider-value" value="<?php echo $slider_options->slider_start ?>" />
      </div>
      <div class="col-xs-10">
        <?php include(locate_template('self-service-poll/slider-display.php')); ?>
      </div>
    </div>
    <div class="form-group">
	    <div class="clear"></div>
    </div>
  <?php } ?>
  
  <div class="form-group">
    <div class="col-sm-10">
      <button type="submit" class="btn btn-primary">Submit</button>
    </div>
  </div>
  <div class="form-group">
    <div class="col-sm-10">
      <p>Built by the <a href="<?php echo get_site_url() ?>">Engaging News Project</a></p>
    </div>
  </div>
</form>
<?php } else { ?>
<p>Sorry, no poll found.  Please try adding the <a href="<?php echo get_site_url() ?>/list-polls/">poll</a> again.</p>
<?php }?>