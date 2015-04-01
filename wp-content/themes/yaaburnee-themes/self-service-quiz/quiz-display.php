
<?php
$quiz = (isset($_GET["guid"]) && $_GET["guid"] != "") ? $wpdb->get_row("
    SELECT * FROM enp_quiz 
    WHERE guid = '" . $_GET["guid"] . "' ") : $wpdb->get_row("
    SELECT * FROM enp_quiz
    WHERE guid = '" . $_GET["summary"] . "' ");

$flag = (isset($_GET["guid"]) && $_GET["guid"] != "") ? 'quiz' : 'other';
$flag = (isset($_GET["summary"]) && $_GET["summary"] != "") ? 'summary' : $flag;


$summary_message = $wpdb->get_var("
    SELECT value FROM enp_quiz_options
    WHERE quiz_id = '" . $quiz->ID . "' AND field = 'summary_message' ");

$nextQuiz = $wpdb->get_row("
    SELECT * FROM enp_quiz_next
    WHERE curr_quiz_id = '" . $quiz->ID . "' ");

if($nextQuiz) {
    $nextGUID = $wpdb->get_row("
        SELECT * FROM enp_quiz
        WHERE id = '" . $nextQuiz->next_quiz_id . "' ");

    $parentGUID = $nextQuiz->parent_guid;

    $parentID = $wpdb->get_var("
        SELECT id FROM enp_quiz
        WHERE guid = '" . $parentGUID . "' ");
}

if ( is_page('iframe-quiz') && !$_GET["quiz_preview"] ) {
    $date = date('Y-m-d H:i:s');
    $guid = $_POST['input-guid'];
    $correct_option_id = -1;
    $correct_option_value = 'quiz-viewed-by-user';
    $quiz_answer_id = -1;
    $quiz_answer_value = -1;
    $is_correct = 0;

    $wpdb->insert( 'enp_quiz_responses',
        array( 'quiz_id' => $quiz->ID , 'quiz_option_id' => $quiz_answer_id, 'quiz_option_value' => $quiz_answer_value,
            'correct_option_id' => $correct_option_id, 'correct_option_value' => $correct_option_value,
            'is_correct' => $is_correct, 'ip_address' => $_SERVER['REMOTE_ADDR'], 'response_datetime' => $date,
            'preview_response' => 0 ));
    $id = $wpdb->insert_id;
}

if ($_GET["quiz_preview"]) {
    $preview_response = "?preview_response=1";
}

if ($parentID > 0) {
    $quiz_background_color = $wpdb->get_var("
    SELECT value FROM enp_quiz_options
    WHERE field = 'quiz_background_color' AND quiz_id = " . $parentID);

    $quiz_text_color = $wpdb->get_var("
    SELECT value FROM enp_quiz_options
    WHERE field = 'quiz_text_color' AND quiz_id = " . $parentID);

    // $quiz_display_border = $wpdb->get_var("
    //   SELECT value FROM enp_quiz_options
    //   WHERE field = 'quiz_display_border' AND quiz_id = " . $quiz->ID);

    $quiz_display_width = $wpdb->get_var("
    SELECT value FROM enp_quiz_options
    WHERE field = 'quiz_display_width' AND quiz_id = " . $parentID);

    $quiz_display_height = $wpdb->get_var("
    SELECT value FROM enp_quiz_options
    WHERE field = 'quiz_display_height' AND quiz_id = " . $parentID);

    $quiz_display_padding = $wpdb->get_var("
		   SELECT value FROM enp_quiz_options
		   WHERE field = 'quiz_display_padding' AND quiz_id = " . $parentID);

    $quiz_show_title = $wpdb->get_var("
    SELECT value FROM enp_quiz_options
    WHERE field = 'quiz_show_title' AND quiz_id = " . $parentID);

    $quiz_display_css = $wpdb->get_var("
    SELECT value FROM enp_quiz_options
    WHERE field = 'quiz_display_css' AND quiz_id = " . $parentID);

    // Prevent slider label from cutting off due to short questions
    $slider_padding = $quiz->quiz_type == "slider" ? "padding-top: 4px;" : "";
    // slider padding causing unnecessary scroll bars, so removing -brettbowlin
    $slider_padding = "";

} else {
    $quiz_background_color = $wpdb->get_var("
    SELECT value FROM enp_quiz_options
    WHERE field = 'quiz_background_color' AND quiz_id = " . $quiz->ID);

    $quiz_text_color = $wpdb->get_var("
    SELECT value FROM enp_quiz_options
    WHERE field = 'quiz_text_color' AND quiz_id = " . $quiz->ID);

    // $quiz_display_border = $wpdb->get_var("
    //   SELECT value FROM enp_quiz_options
    //   WHERE field = 'quiz_display_border' AND quiz_id = " . $quiz->ID);

    $quiz_display_width = $wpdb->get_var("
    SELECT value FROM enp_quiz_options
    WHERE field = 'quiz_display_width' AND quiz_id = " . $quiz->ID);

    $quiz_display_height = $wpdb->get_var("
    SELECT value FROM enp_quiz_options
    WHERE field = 'quiz_display_height' AND quiz_id = " . $quiz->ID);

    $quiz_display_padding = $wpdb->get_var("
		 SELECT value FROM enp_quiz_options
		 WHERE field = 'quiz_display_padding' AND quiz_id = " . $quiz->ID);

    $quiz_show_title = $wpdb->get_var("
    SELECT value FROM enp_quiz_options
    WHERE field = 'quiz_show_title' AND quiz_id = " . $quiz->ID);

    $quiz_display_css = $wpdb->get_var("
    SELECT value FROM enp_quiz_options
    WHERE field = 'quiz_display_css' AND quiz_id = " . $quiz->ID);

    // Prevent slider label from cutting off due to short questions
    $slider_padding = $quiz->quiz_type == "slider" ? "padding-top: 4px;" : "";
    // slider padding causing unnecessary scroll bars, so removing -brettbowlin
    $slider_padding = "";

}

/*
  $quiz_background_color = $wpdb->get_var("
    SELECT value FROM enp_quiz_options
    WHERE field = 'quiz_background_color' AND quiz_id = " . $quiz->ID);

		$quiz_text_color = $wpdb->get_var("
    SELECT value FROM enp_quiz_options
    WHERE field = 'quiz_text_color' AND quiz_id = " . $quiz->ID);

		// $quiz_display_border = $wpdb->get_var("
		//   SELECT value FROM enp_quiz_options
		//   WHERE field = 'quiz_display_border' AND quiz_id = " . $quiz->ID);

		$quiz_display_width = $wpdb->get_var("
    SELECT value FROM enp_quiz_options
    WHERE field = 'quiz_display_width' AND quiz_id = " . $quiz->ID);

		$quiz_display_height = $wpdb->get_var("
    SELECT value FROM enp_quiz_options
    WHERE field = 'quiz_display_height' AND quiz_id = " . $quiz->ID);

		$quiz_display_padding = $wpdb->get_var("
		 SELECT value FROM enp_quiz_options
		 WHERE field = 'quiz_display_padding' AND quiz_id = " . $quiz->ID);

		$quiz_show_title = $wpdb->get_var("
    SELECT value FROM enp_quiz_options
    WHERE field = 'quiz_show_title' AND quiz_id = " . $quiz->ID);

		$quiz_display_css = $wpdb->get_var("
    SELECT value FROM enp_quiz_options
    WHERE field = 'quiz_display_css' AND quiz_id = " . $quiz->ID);

		// Prevent slider label from cutting off due to short questions
		$slider_padding = $quiz->quiz_type == "slider" ? "padding-top: 4px;" : "";
		// slider padding causing unnecessary scroll bars, so removing -brettbowlin
		$slider_padding = "";

	}

?>
<div style="box-sizing:border-box; background:<?php echo $quiz_background_color ;?>;color:<?php echo $quiz_text_color ;?>; width: <?php echo $quiz_display_width ;?>; height:<?php echo $quiz_display_height ;?>; padding:<?php echo $quiz_display_padding ;?>; <?php echo $quiz_display_css; ?><?php echo $slider_padding; ?>" class="quiz-display">

		<?php if ( $flag == 'quiz' ) { ?>
			<form id="quiz-display-form" class="form-horizontal bootstrap" role="form" method="post" action="<?php echo get_stylesheet_directory_uri(); ?>/self-service-quiz/include/process-quiz-response.php">

  // Prevent slider label from cutting off due to short questions  
  $slider_padding = $quiz->quiz_type == "slider" ? "padding-top: 4px;" : "";
  // slider padding causing unnecessary scroll bars, so removing -brettbowlin
  $slider_padding = "";
*/

?>
<div style="box-sizing:border-box; background:<?php echo $quiz_background_color ;?>; color:<?php echo $quiz_text_color ;?>; padding: 10px 0; height: 100%; width: 100%; <?php echo $quiz_display_css; ?><?php echo $slider_padding; ?>; overflow: auto;" class="quiz-display">

    <?php if ( $flag == 'quiz' ) { ?>
        <form id="quiz-display-form" class="form-horizontal bootstrap" role="form" method="post" action="<?php echo get_stylesheet_directory_uri(); ?>/self-service-quiz/include/process-quiz-response.php">
            <script>
                localStorage.setItem('referURL', '<?php echo $_GET["refer"]; ?>');
                var referURL = localStorage.getItem('referURL');
                //					console.log("referURL="+referURL);

                function getQueryVariable(variable)
                {
                    var query = window.location.search.substring(1);
                    var vars = query.split("&");
                    for (var i=0;i<vars.length;i++) {
                        var pair = vars[i].split("=");
                        if(pair[0] == variable){return pair[1];}
                    }
                    return(false);
                }
                var passReferURL = decodeURIComponent(getQueryVariable("refer"));
                //console.log('preHiddenInput: '+passReferURL);
                document.write('<input type="hidden" name="referURL" id="referURL" value="'+passReferURL+'">');
            </script>

            <input type="hidden" name="quiz_preview" id="preview" value="<?php echo $_GET["quiz_preview"]; ?>">
            <input type="hidden" name="input-id" id="input-id" value="<?php echo $quiz->ID; ?>">
            <input type="hidden" name="input-guid" id="input-guid" value="<?php echo $quiz->guid; ?>">
            <input type="hidden" name="quiz-type" id="quiz-type" value="<?php echo $quiz->quiz_type; ?>">
            <input type="hidden" name="quiz-next" id="quiz-next" value="<?php echo $nextQuiz->next_quiz_id; ?>">
            <h3 class="col-sm-12 title" <?php echo $quiz_show_title ? "": "style='display:none;'"; ?>><?php echo $quiz->title; ?></h3>
            <div class="col-sm-12"><p><?php echo $quiz->question; ?></p></div>

            <?php if ( $quiz->quiz_type == "multiple-choice" ) { ?>
                <input type="hidden" name="correct-option-id" id="correct-option-id" value="1">
                <input type="hidden" name="correct-option-value" id="correct-option-value" value="option1">
                <!-- <div class="form-group"> -->
                <?php
                $mc_answers = $wpdb->get_results("
						SELECT * FROM enp_quiz_options
						WHERE field = 'answer_option' AND quiz_id = " . $quiz->ID . " ORDER BY `display_order` ASC");


                foreach ( $mc_answers as $mc_answer ) {
                    ?>
                    <div class="form-group mc-radio-answers">
                        <div class="col-sm-12">
                            <div class="input-group">
              <span class="input-group-addon input-group-sm">
                <input type="hidden" name="option-radio-id-<?php echo $mc_answer->ID; ?>" id="option-radio-id-<?php echo $mc_answer->ID; ?>" value="<?php echo $mc_answer->value; ?>">
                <input type="radio" name="mc-radio-answers" id="option-radio-<?php echo $mc_answer->ID; ?>" value="<?php echo $mc_answer->ID; ?>" >
              </span>
                                <label for="option-radio-<?php echo $mc_answer->ID; ?>" class="form-control mc-radio-answer-label input-sm" id="<?php echo $mc_answer->ID; ?>"><?php echo $mc_answer->value; ?></label>
                            </div><!-- /input-group -->
                        </div>
                    </div>

                <?php
                }
                ?>
                <!-- </div>   -->
            <?php } ?>

            <?php if ( $quiz->quiz_type == "slider" ) {
                $wpdb->query('SET OPTION SQL_BIG_SELECTS = 1');
                $slider_options = $wpdb->get_row("
        SELECT po_high.value 'slider_high', po_low.value 'slider_low', po_start.value 'slider_start', po_increment.value 'slider_increment', po_high_answer.value 'slider_high_answer', po_low_answer.value 'slider_low_answer', po_label.value 'slider_label'
        FROM enp_quiz_options po
        LEFT OUTER JOIN enp_quiz_options po_high ON po_high.field = 'slider_high' AND po.quiz_id = po_high.quiz_id
        LEFT OUTER JOIN enp_quiz_options po_low ON po_low.field = 'slider_low' AND po.quiz_id = po_low.quiz_id
        LEFT OUTER JOIN enp_quiz_options po_start ON po_start.field = 'slider_start' AND po.quiz_id = po_start.quiz_id
        LEFT OUTER JOIN enp_quiz_options po_increment ON po_increment.field = 'slider_increment' AND po.quiz_id = po_increment.quiz_id
        LEFT OUTER JOIN enp_quiz_options po_high_answer ON po_high_answer.field = 'slider_high_answer' AND po.quiz_id = po_high_answer.quiz_id
        LEFT OUTER JOIN enp_quiz_options po_low_answer ON po_low_answer.field = 'slider_low_answer' AND po.quiz_id = po_low_answer.quiz_id
        LEFT OUTER JOIN enp_quiz_options po_label ON po_label.field = 'slider_label' AND po.quiz_id = po_label.quiz_id
        WHERE po.quiz_id = " . $quiz->ID . "
        GROUP BY po.quiz_id;");

                ?>
                <div class="form-group slider-iframe-display">
                    <div class="col-md-2 slider-value">
                        <input type="hidden" name="slider-high-answer" id="slider-high-answer" value="<?php echo $slider_options->slider_high_answer ?>" />
                        <input type="hidden" name="slider-low-answer" id="slider-low-answer" value="<?php echo $slider_options->slider_low_answer ?>" />
                        <input type="hidden" name="slider-value" id="slider-value" value="<?php echo $slider_options->slider_start ?>" />
                        <span class="badge" id="slider-value-label"><?php echo $slider_options->slider_start; echo $slider_options->slider_label == '%' ? '' : ' '; echo $slider_options->slider_label; ?></span>
                    </div>
                    <div class="col-md-10">
                        <?php include(locate_template('self-service-quiz/slider-display.php')); ?>
                    </div>
                </div>
                <div class="form-group">
                    <div class="clear col-sm-12"></div>
                </div>
            <?php } ?>

            <div class="col-sm-12">
                <? if($nextQuiz) { ?>
                    <button type="submit" class="btn btn-sm btn-primary">Continue</button>
                <? } else { ?>
                    <button type="submit" class="btn btn-sm btn-primary">Submit</button>
                <? } ?>
            </div>
            <div class="form-group iframe-credits">
                <div class="col-sm-12">
                    <p>Built by the <a href="http://engagingnewsproject.org/" target="_blank">Engaging News Project</a></p>
                </div>
            </div>
        </form>
    <?php } elseif ($flag == 'summary')  { ?>
        <!-- Adding Summary in place -->

        <form id="quiz-display-form" class="form-horizontal bootstrap" role="form" action="">
            <div class="col-sm-12">
                <?php include(locate_template('self-service-quiz/quiz-summary.php')); ?>
                <p><a href="<?php echo get_site_url() . '/iframe-quiz/?guid=' . $quiz->guid;  echo (isset($_GET["quiz_preview"]) && ('' != $_GET["quiz_preview"])) ? '&quiz_preview=true' : '';?>" class="btn btn-sm btn-primary">Return to the beginning</a></p>
                <script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid=ra-5420b26c5d05a323"></script>
                <!-- Go to www.addthis.com/dashboard to customize your tools -->
                <script>
                    document.write('<div class="addthis_sharing_toolbox" data-url="'+ localStorage.getItem('refer') +'" data-title="Try this quiz from Engaging News Project!" style="margin-top:5px;"></div>');
                </script>
                <div class="form-group iframe-credits">
                    <div class="col-sm-12">
                        <p>Built by the <a href="http://engagingnewsproject.org/" target="_blank">Engaging News Project</a></p>
                    </div>
                </div>
            </div>
        </form>

        <!-- end of summary -->
    <?php } else { ?>
        <p>Sorry, no quiz found.  Please try adding the <a href="http://engagingnewsproject.org/create-a-quiz/">quiz</a> again.</p>
    <?php } ?>
</div>

<?php
if($nextQuiz->newQuizFlag == 1 && $flag  != 'summary') { ?>
    <script>
        localStorage.clear();
        var passReferURL = document.referrer;
        localStorage.setItem('refer', passReferURL);
    </script>
<?php } ?>