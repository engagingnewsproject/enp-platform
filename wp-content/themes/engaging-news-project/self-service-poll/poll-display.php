<?php 
$poll = $wpdb->get_row("
  SELECT * FROM enp_poll 
  WHERE guid = '" . $_GET["guid"] . "' ");
?>

<?php if ( $poll ) { ?>
<form id="poll-form" class="form-horizontal bootstrap" role="form" method="post" action="<?php echo get_stylesheet_directory_uri(); ?>/self-service-poll/include/process-poll-response.php">
  <input type="hidden" name="input-id" id="input-id" value="<?php echo $poll->id; ?>">
  <input type="hidden" name="input-guid" id="input-guid" value="<?php echo $poll->guid; ?>">
  <h3><?php echo $poll->title; ?></h3>
  <p><?php echo $poll->question; ?></p>
  
  <?php if ( $poll->poll_type == "multiple-choice" ) { ?>
  <input type="hidden" name="correct-option-id" id="correct-option-id" value="1">
  <input type="hidden" name="correct-option-value" id="correct-option-value" value="option1">
  <div class="form-group">
    <div class="radio">
      <label>
        <input type="hidden" name="option-radio-id-1" id="option-radio-id-1" value="value1">
        <input type="radio" name="pollRadios" id="option-radio-1" value="1" >
        Option one is this and that&mdash;be sure to include why it's great
      </label>
    </div>
    <div class="radio">
      <label>
        <input type="hidden" name="option-radio-id-2" id="option-radio-id-2" value="value2">
        <input type="radio" name="pollRadios" id="option-radio-2" value="2" >
        Option two can be something else and selecting it will deselect option one
      </label>
    </div>
    <div class="radio">
      <label>
        <input type="hidden" name="option-radio-id-3" id="option-radio-id-3" value="value3">
        <input type="radio" name="pollRadios" id="option-radio-3" value="3" >
        Option three is this and that&mdash;be sure to include why it's great
      </label>
    </div>
    <div class="radio">
      <label>
        <input type="hidden" name="option-radio-id-4" id="option-radio-id-4" value="value4">
        <input type="radio" name="pollRadios" id="option-radio-4" value="4">
        Option four can be something else and selecting it will deselect option one
      </label>
    </div>
	</div>	
  <?php } ?>
  
  <?php if ( $poll->poll_type == "slider" ) { ?>
    <div class="form-group">
      <div class="col-xs-2">
	      <input class="form-control" type="text" id="slider-value" />
      </div>
      <div class="col-xs-4">
	      <input type="text" id="foo" class="span2" value="" data-slider-min="-20" data-slider-max="20" data-slider-step="1" data-slider-value="-14" data-slider-orientation="horizontal" data-slider-selection="after"data-slider-tooltip="hide" />
      </div>
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