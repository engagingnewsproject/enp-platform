<?php if (have_posts()) while (have_posts()) : the_post(); ?>
    <article class="entry post clearfix">
    <h1 class="main_title"><?php the_title(); ?></h1>
    <div class="post-content clearfix">
    <?php
    if ($_GET["add_question"] != 1 && $_GET["edit_guid"]) {
        $add_question = false;
        $insert_question = false; // redo2: was true ||KVB
        $new_quiz = false;
        $first_question = false;
    } elseif ($_GET["add_question"] == 1 && $_GET["edit_guid"]) {
        $add_question = true;
        $insert_question = false;
        $insert_question_pass = 2;
        $new_quiz = true;
        $first_question = false;
    } elseif ($_GET["add_question"] == 2 && $_GET["edit_guid"]) {
//    } elseif ($_GET["add_question"] == 2) {
        $add_question = false;
        $insert_question = false; // redo2: was true ||KVB
        $insert_question_pass = 2;
        $new_quiz = false;
        $first_question = false;
    } elseif ($_GET["add_question"] == 1 && !$_GET["edit_guid"]) {
        $add_question = true;
        $insert_question = false;
        $new_quiz = false;
        $first_question = false;
    } elseif (!$_GET["edit_guid"]) {
        $add_question = true;
        $insert_question = false;
        $new_quiz = true;
        $first_question = true;
    } else {
	    $first_question = true; // necessary? ||KVB

    }
    $update_question = false;
    $enp_quiz_next = '';
//    $old_enp_quiz_next = '';
//    $old_next_quiz_id = '';
    $prevQuizID = '';
    $nextQuizId = '';
    if ( $_GET["insertQuestion"] = 1 ) {
	    $prevQuestionRow = $wpdb->get_row("SELECT * FROM enp_quiz WHERE guid = '" . $_GET["edit_guid"] . "'");
	    $prevQuestionNextRow = $wpdb->get_row("SELECT * FROM enp_quiz_next WHERE curr_quiz_id = '" . $prevQuestionRow->ID . "'");
//	    if ( $prevQuestionNextRow->newQuizFlag == 1 ) {$first_question = true;}
	    $first_question = true;
	    $update_question = false;
		$prevQuizID = $prevQuestionNextRow->curr_quiz_id;
	    $nextQuizID = $prevQuestionNextRow->next_quiz_id;
	    $prevParentGUID = $prevQuestionNextRow->parent_guid;
	    $prevParentTitle = $prevQuestionNextRow->title;
	    $insert_question = true;
    } elseif ( $_GET["edit_guid"] ) {
	    $quiz = $wpdb->get_row("SELECT * FROM enp_quiz WHERE guid = '" . $_GET["edit_guid"] . "'");
        $is_first = $wpdb->get_row("SELECT * FROM enp_quiz_next WHERE curr_quiz_id = '" . $quiz->ID . "'");
        if ( $is_first->newQuizFlag == 1 ) {$first_question = true;}
	    $update_question = true;
//        $old_enp_quiz_next = $is_first->enp_quiz_next;
//        $old_next_quiz_id = $is_first->next_quiz_id;
    }
    if ($_GET["parent_guid"]) {
        $parent_quiz = $wpdb->get_row("SELECT * FROM enp_quiz WHERE guid = '" . $_GET["parent_guid"] . "'");
//                $first_question = true;
//                $new_quiz = true;
    }
    if ($_GET["curr_quiz_id"]) {
        $curr_quiz_id = $_GET["curr_quiz_id"];
        $parent_guid = $_GET["parent_guid"];
        $parent_title = $parent_quiz->title;
        $get_enp_quiz_next_row = $wpdb->get_row("SELECT * FROM enp_quiz_next WHERE curr_quiz_id = '" . $curr_quiz_id . "'");
        $enp_quiz_next = $get_enp_quiz_next_row->enp_quiz_next;
        $old_next_quiz_id = $get_enp_quiz_next_row->next_quiz_id;
//        if ($insert_question_pass = 2) {
//            $enp_quiz_next = $is_first->enp_quiz_next;
//        } else {
//            $enp_quiz_next = $_GET["enp_quiz_next"];
//        }
//                $insert_pre_id = $_GET["insert_pre_id"];
//                $insert_post_id = $_GET["insert_post_id"];

    } else {
        $curr_quiz_id = $quiz->ID;
        $parent_guid = $quiz->guid;
        $parent_title = $quiz->title;
    }
    if ($quiz) {
        $question_text = esc_attr($quiz->question);
    } else {
        $question_text = "Enter Quiz Question";
    }
    // Removing lock feature...remove permanently after more feedback
    //if ( !$quiz->locked ) {
    if (true) {
        ?>
        <div class="entry_content bootstrap <?php echo $quiz ? "edit_quiz" : "new_quiz" ?>">
            <?php if ($quiz->locked) { ?>
                <div class='bootstrap'>
                    <div class='alert alert-warning alert-dismissable'>
                        <span class='glyphicon glyphicon-warning-sign'></span> Quiz has received responses.
                        Editing could cause inconsistent reports.
                        <button type='button' class='close' data-dismiss='alert'
                                aria-hidden='true'>&times;</button>
                    </div>
                    <div class='clear'></div>
                </div>
            <?php } ?>
            <form id="quiz-form" class="form-horizontal" role="form" method="post"
                  action="<?php echo get_stylesheet_directory_uri(); ?>/self-service-quiz/include/process-quiz-form.php">
                <input type="hidden" name="input-id" id="input-id" value="<?php echo $quiz->ID; ?>">
                <input type="hidden" name="input-guid" id="input-guid" value="<?php echo $quiz->guid; ?>">
                <?php include(locate_template('self-service-quiz/quiz-form-options.php')); ?>
                <div class="panel panel-info quiz-answers-panel">
                    <div class="panel-heading">Quiz Answers</div>
                    <div class="panel-body" id="quiz-answers">
                        <?php include(locate_template('self-service-quiz/quiz-form-mc-options.php')); ?>
                        <?php include(locate_template('self-service-quiz/quiz-form-slider-options.php')); ?>
                    </div>
                </div>
                <?php // include(locate_template('self-service-quiz/quiz-form-styling-options.php'));
                if ($first_question == true) { include(locate_template('self-service-quiz/quiz-form-styling-options.php')); }
//                include(locate_template('self-service-quiz/quiz-form-styling-options.php'));
                ?>
                <div class="panel panel-info aanswer-settings">
                    <div class="panel-heading">Advanced Answer Settings - Optional</div>
                    <div class="panel-body" id="quiz-answers">
                        <?php include(locate_template('self-service-quiz/quiz-form-aanswer-options.php')); ?>
                    </div>
                </div>

                <div class="panel panel-info aanswer-settings">
                    <div class="panel-heading">Advanced Answer Settings - Optional</div>
                    <div class="panel-body" id="quiz-answers">
                        <?php include(locate_template('self-service-quiz/quiz-form-aanswer-options.php')); ?>
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-sm-12">
                        <input type="hidden" name="insert-question-pass" id="insert-question-pass" value="">
                        <input type="hidden" name="enp-quiz-next" id="enp-quiz-next" value="">
                        <input type="hidden" name="old-enp-quiz-next" id="old-enp-quiz-next" value="">
<!--                        <input type="hidden" name="old-next-quiz-id" id="old-next-quiz-id" value="">-->
	                    <input type="hidden" name="prev-quiz-id" id="prev-quiz-id" value="">
	                    <input type="hidden" name="next-quiz-id" id="next-quiz-id" value="">

	                    <input type="hidden" name="quiz-new-question" id="quiz-new-question" value="">
                        <input type="hidden" name="curr-quiz-id" id="curr-quiz-id" value="">
                        <input type="hidden" name="parent-guid" id="parent-guid" value="">
                        <input type="hidden" name="parent-title" id="parent-title" value="">
                        <!--                                <input type="hidden" name="insert-pre-id" id="insert-pre-id" value="">-->
                        <!--                                <input type="hidden" name="insert-post-id" id="insert-post-id" value="">-->
                        <?php if ($new_quiz) {
                            echo "<button id=\"addQuestionSubmit\" class=\"btn btn-primary\">Add Another Question to Quiz</button>"; // ||KVB
                        } else {
//                            echo "<button id=\"insertQuestionSubmit\" class=\"btn btn-primary\">Update &amp; Insert Another Question</button>"; // ||KVB
                        } ?>
                        <button type="submit" id="questionSubmit"
                                class="btn btn-primary"><?php /* echo $quiz ? "Update Quiz" : "Create Quiz"; */ ?><?php echo $quiz ? "Finish Update" : "Finish"; // ||KVB ?></button>

                        <script type="text/javascript">
                            (function ($) {
                                $('#insertQuestionSubmit').click(function (e) { // insert question to existing quiz
                                    e.preventDefault();
                                    <?php if ( $insert_question == true ) {
//                                         echo "$('#quiz-new-question').val('updateQuizInsertQuestion');";
//                                            echo "console.log('updateQuizInsertQuestion');"; // remove console.log ||KVB
//                                        echo "$('#insert-question-pass').val('".$insert_question_pass."');";
//                                            echo "console.log('insert-question-pass: ".$insert_question_pass."');"; // remove console.log ||KVB
//                                        echo "$('#curr-quiz-id').val('".$curr_quiz_id."');";
//                                            echo "console.log('curr-quiz-id: ".$curr_quiz_id."');"; // remove console.log ||KVB
//                                        echo "$('#parent-guid').val('".$parent_guid."');";
//                                            echo "console.log('parent-guid: ".$parent_guid."');"; // remove console.log ||KVB
//                                        echo "$('#parent-title').val('".$parent_title."');";
//                                            echo "console.log('parent-title: ".$parent_title."');"; // remove console.log ||KVB
//                                        echo "$('#enp-quiz-next').val('".$enp_quiz_next."');";
//                                            echo "console.log('enp-quiz-next: ".$enp_quiz_next."');"; // remove console.log ||KVB
//                                        echo "$('#old-enp-quiz-next').val('".$old_enp_quiz_next."');";
//                                            echo "console.log('old-enp-quiz-next: ".$old_enp_quiz_next."');"; // remove console.log ||KVB
//                                        echo "$('#old-curr-quiz-id').val('".curr_quiz_id."');";
//                                            echo "console.log('old-curr-quiz-id: ".$curr_quiz_id."');"; // remove console.log ||KVB
//                                        echo "$('#old-next-quiz-id').val('".$old_next_quiz_id."');";
//                                            echo "console.log('old-next-quiz-id: ".$old_next_quiz_id."');"; // remove console.log ||KVB




//                                        echo "console.log('updateQuizInsertQuestion');"; // remove console.log ||KVB
//                                        echo "$('#quiz-new-question').val('updateQuizInsertQuestion');";
//                                        echo "$('#curr-quiz-id').val('".$curr_quiz_id."');";
//                                        echo "$('#parent-guid').val('".$parent_guid."');";
//                                                    echo "console.log('".$parent_guid."');";
//                                        echo "$('#parent-title').val('".$parent_title."');";
//                                        echo "$('#enp-quiz-next').val('".$enp_quiz_next."');";
//                                        echo "$('#old-enp-quiz-next').val('".$old_enp_quiz_next."');";
//                                        echo "$('#old-next-quiz-id').val('".$old_next_quiz_id."');";
//                                                echo "$('#insert-pre-id').val('".$insert_pre_id."');";
                                    } else {
                                        echo "console.log('insertQuestionSubmit: should not happen');"; // remove console.log ||KVB
                                        echo "$('#quiz-new-question').val('newQuizAddQuestion_shouldNotHappen');";
                                        echo "$('#curr-quiz-id').val('".$curr_quiz_id."');";
                                        echo "$('#parent-guid').val('".$parent_guid."');";
                                        echo "$('#parent-title').val('".$parent_title."');";
                                    }?>
                                    $('#quiz-form').submit();
                                    return false;
                                });
                                $('#addQuestionSubmit').click(function (e) {
                                    e.preventDefault();
                                    <?php if ( $add_question == true ) {
                                        echo "console.log('updateQuizAddQuestion');"; // remove console.log ||KVB
                                        echo "$('#quiz-new-question').val('updateQuizAddQuestion');";
                                        echo "$('#curr-quiz-id').val('".$curr_quiz_id."');";
                                        echo "$('#parent-guid').val('".$parent_guid."');";
                                        echo "$('#parent-title').val('".$parent_title."');";
                                        echo "$('#enp-quiz-next').val('".$enp_quiz_next."');";
                                    } else {
                                        echo "console.log('addQuestionSubmit: should not happen');"; // remove console.log ||KVB
                                        echo "$('#quiz-new-question').val('newQuizAddQuestion_shouldNotHappen');";
                                        echo "$('#curr-quiz-id').val('".$curr_quiz_id."');";
                                        echo "$('#parent-guid').val('".$parent_guid."');";
                                        echo "$('#parent-title').val('".$parent_title."');";
                                    }?>
                                    $('#quiz-form').submit();
                                    return false;
                                });
                                $('#questionSubmit').click(function (e) {
                                    e.preventDefault();
                                    <?php if ( $update_question == true ) {
                                        echo "console.log('finishQuizUpdate');"; // remove console.log ||KVB
                                        echo "$('#quiz-new-question').val('finishQuizUpdate');";
                                        echo "$('#curr-quiz-id').val('".$curr_quiz_id."');";
                                        echo "$('#parent-guid').val('".$parent_guid."');";
                                        echo "$('#parent-title').val('".$parent_title."');";
                                        echo "$('#enp-quiz-next').val('".$enp_quiz_next."');";
//                                                echo "$('#insert-pre-id').val('".$insert_pre_id."');";
//                                                echo "$('#insert-post-id').val('".$insert_post_id."');";
                                    } elseif ( $insert_question == true ) {
                                        echo "$('#quiz-new-question').val('finishNewQuestionOnInsert');";
                                            echo "console.log('finishNewQuestionOnInsert');"; // remove console.log ||KVB
//                                        echo "$('#insert-question-pass').val('".$insert_question_pass."');";
//                                            echo "console.log('insert-question-pass: ".$insert_question_pass."');"; // remove console.log ||KVB
//                                        echo "$('#curr-quiz-id').val('".$curr_quiz_id."');";
//                                            echo "console.log('curr-quiz-id: ".$curr_quiz_id."');"; // remove console.log ||KVB
                                        echo "$('#parent-guid').val('".$prevParentGUID."');";
                                            echo "console.log('parent-guid: ".$prevParentGUID."');"; // remove console.log ||KVB
                                        echo "$('#parent-title').val('".$prevParentTitle."');";
                                            echo "console.log('parent-title: ".$prevParentTitle."');"; // remove console.log ||KVB
//                                        echo "$('#enp-quiz-next').val('".$enp_quiz_next."');";
//                                            echo "console.log('enp-quiz-next: ".$enp_quiz_next."');"; // remove console.log ||KVB
//                                        echo "$('#old-enp-quiz-next').val('".$old_enp_quiz_next."');";
//                                            echo "console.log('old-enp-quiz-next: ".$old_enp_quiz_next."');"; // remove console.log ||KVB
//                                        echo "$('#old-curr-quiz-id').val('".curr_quiz_id."');";
//                                            echo "console.log('old-curr-quiz-id: ".$curr_quiz_id."');"; // remove console.log ||KVB
//                                        echo "$('#old-next-quiz-id').val('".$old_next_quiz_id."');";
//                                            echo "console.log('old-next-quiz-id: ".$old_next_quiz_id."');"; // remove console.log ||KVB
									    echo "$('#prev-quiz-id').val('".$prevQuizID."');";
                                            echo "console.log('prev-quiz-id: ".$prevQuizID."');"; // remove console.log ||KVB
                                        echo "$('#next-quiz-id').val('".$nextQuizID."');";
                                            echo "console.log('next-quiz-id: ".$nextQuizID."');"; // remove console.log ||KVB
                                    } else {
                                        echo "console.log('finishNewQuiz');"; // remove console.log ||KVB
                                        echo "$('#quiz-new-question').val('finishNewQuiz');";
                                        echo "$('#curr-quiz-id').val('".$curr_quiz_id."');";
                                        echo "$('#parent-guid').val('".$parent_guid."');";
                                        echo "$('#parent-title').val('".$parent_title."');";
                                        echo "$('#enp-quiz-next').val('".$enp_quiz_next."');";
                                    } ?>
                                    $('#quiz-form').submit();
                                    return false;
                                });
                            })(jQuery);
                        </script>
                        <!--||KVB-->
                        <?php if ($quiz) { ?>
                            <a href="view-quiz?guid=<?php echo $quiz->guid ?>" class="btn btn-warning" role="button">Cancel</a>
                        <?php } elseif ( !$first_question ){ ?>
                            <a href="create-a-quiz" class="btn btn-warning" role="button">Cancel</a>
                        <?php } elseif ( $add_question == 2 ){ ?>
                            <a href="create-a-quiz/?cancelInsertion=1&enp_quiz_next=<?php echo $enp_quiz_next; ?>" class="btn btn-warning" role="button">Cancel Question Insertion</a>
                        <?php } ?>
                    </div>
                </div>
            </form>
            <a href="create-a-quiz/" class="btn btn-primary btn-xs active" role="button">Back to Quizzes</a>
            <?php wp_link_pages(array('before' => '<p><strong>' . esc_attr__('Pages', 'Trim') . ':</strong> ', 'after' => '</p>', 'next_or_number' => 'number')); ?>
        </div> <!-- end .entry_content -->

    <?php } else { ?>
        <p>This quiz is locked for editing, as responses have been received.</p>
        <div class="bootstrap">
            <div class="form-group">
                <p>
                    <a href="view-quiz?guid=<?php echo $quiz->guid ?>" class="btn btn-info btn-sm active"
                       role="button">View Quiz</a>
                <p><a href="configure-quiz" class="btn btn-info btn-xs active" role="button">New Quiz</a> | <a
                        href="create-a-quiz/" class="btn btn-primary btn-xs active" role="button">Back to
                        Quizzes</a></p>
            </div>
        </div>
    <?php } ?>
    </div>
    <!-- end .post-content -->
    </article> <!-- end .post -->
<?php endwhile; // end of the loop. ?>