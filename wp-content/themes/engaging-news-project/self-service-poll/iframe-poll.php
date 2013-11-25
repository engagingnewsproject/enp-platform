<?php
/*
Template Name: iframe Poll
*/
?>

<div class="content">
    <?php 
    $poll = $wpdb->get_row("SELECT * FROM enp_poll WHERE guid = " . $_GET["guid"] ); 
    ?>
    
    <form id="poll-form" class="form-horizontal" role="form" method="post" action="/enp/wp-content/themes/engaging-news-project/self-service-poll/process-poll-response.php">
      <input type="hidden" name="input-id" id="input-id" value="<?php echo $poll->id; ?>">
      <input type="hidden" name="input-guid" id="input-guid" value="<?php echo $poll->guid; ?>">
      <h2><?php echo $poll->title; ?></h2>
      <p>Question: <?php echo $poll->question; ?></p>
      
      <input class="form-control" type="text" id="slider-value" />
  	  <input type="text" id="foo" class="span2" value="" data-slider-min="-20" data-slider-max="20" data-slider-step="1" data-slider-value="-14" data-slider-orientation="horizontal" data-slider-selection="after"data-slider-tooltip="hide" />
    
      <div class="form-group">
        <div class="col-sm-offset-2 col-sm-10">
          <button type="submit" class="btn btn-primary">Submit</button>
        </div>
      </div>
    </form>

</div> <!-- end #main_content -->

<?php get_footer(); ?>