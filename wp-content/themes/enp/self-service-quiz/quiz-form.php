<?
    function debug_to_console( $data ) {

        if ( is_array( $data ) )
            $output = "<script>console.log( 'Debug Objects: " . implode( ',', $data) . "' );</script>";
        else
            $output = "<script>console.log( 'Debug Objects: " . $data . "' );</script>";

        echo $output;
    }
    ?>

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
    } elseif ($_GET["add_question"] == 2) {
        $add_question = false;
        $insert_question = false; // redo2: was true ||KVB
        $insert_question_pass = 2;
        $new_quiz = false;
        $first_question = false;
    } elseif ($_GET["add_question"] == 1 && !$_GET["edit_guid"]) {
        $add_question = true;
        $insert_question = false;
        $new_quiz = true;
        $first_question = false;
    } elseif (!$_GET["edit_guid"]) {
        $add_question = true;
        $insert_question = false;
        $new_quiz = true;
        $first_question = true;
    } else {
//	    $first_question = true; // necessary? ||KVB

    }
    $update_question = false;
    $enp_quiz_next = '';
    $prevQuizID = '';
    $nextQuizId = '';
    //    $old_next_quiz_id = '';
    if ( $_GET["insertQuestion"] == 1 ) {
        // Get the last active question created
        $prevQuestionRowSQL = $wpdb->prepare(
                                    "SELECT * FROM enp_quiz_next
                                    WHERE parent_guid = '%s' AND next_quiz_id = '0'",
                                    $_GET["edit_guid"]
                                );
        $prevQuestions = $wpdb->get_results($prevQuestionRowSQL);

        // Now we have all questions that are marked with the next question as 0 (meaning they're the last ones)
        // BUT - Deleted questions also get set as the last question = 0
        // SO we have to check to make sure it's an active one
        foreach($prevQuestions as $prevQuestion) {
            $prevQuestionIsActive = $wpdb->get_row("SELECT * FROM enp_quiz WHERE ID = $prevQuestion->curr_quiz_id");
            // if it's active, set it as our prevQuestionRow
            if($prevQuestionIsActive->active == 1) {
                $prevQuestionRow = $prevQuestionIsActive;
                // break out of the foreach if we found our active one,
                // since there can't be two last questions that are active
                break;
            }
        }

        // selects the previous question data from enp_quiz_next
        $prevQuestionNextRow = $wpdb->get_row("SELECT * FROM enp_quiz_next WHERE curr_quiz_id = '" . $prevQuestionRow->ID . "'");
//	    if ( $prevQuestionNextRow->newQuizFlag == 1 ) {$first_question = true;}
        $first_question = false;
        $update_question = false;

        $prevQuizID = $prevQuestionNextRow->curr_quiz_id;
        // This SHOULD always be 0
        $nextQuizID = $prevQuestionNextRow->next_quiz_id;
        $prevParentGUID = $prevQuestionNextRow->parent_guid;
        // set the title from the enp_quiz table on the original query
        $prevParentTitle = $prevQuestionRow->title;
        $insert_question = true;
    } elseif ( $_GET["edit_guid"] ) {
        $quiz = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM enp_quiz WHERE guid = '%s'",
                $_GET["edit_guid"]
            )
        );
        $quiz_next = $wpdb->get_row("SELECT * FROM enp_quiz_next WHERE curr_quiz_id = '" . $quiz->ID . "'");
        if ( $quiz_next->newQuizFlag == 1 ) {$first_question = true;}
        $update_question = true;
    }
    if ($_GET["parent_guid"]) {
        $parent_quiz = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM enp_quiz WHERE guid = '%s'",
                $_GET["parent_guid"]
            )
        );
    }
    if ($_GET["curr_quiz_id"]) {
        $curr_quiz_id = $_GET["curr_quiz_id"];
        $parent_guid = $_GET["parent_guid"];
        $parent_title = $parent_quiz->title;
        $get_enp_quiz_next_row = $wpdb->get_row("SELECT * FROM enp_quiz_next WHERE curr_quiz_id = '" . $curr_quiz_id . "'");
        $enp_quiz_next = $get_enp_quiz_next_row->enp_quiz_next;
        // This old_next_quiz_id isn't referenced anywhere that's used
        $old_next_quiz_id = $get_enp_quiz_next_row->next_quiz_id;
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

    // Write the total number of questions so it's always up to date on the summary preview.
    // Otherwise the UX feels off, like something is wrong, when it's not
    $how_many_active_questions = 0;
    if($_GET["edit_guid"]) {
        // $how_many_questions_SQL = $wpdb->prepare("SELECT COUNT(*) FROM enp_quiz_next WHERE parent_guid= '%s'", $_GET["edit_guid"]);
        $how_many_questions_SQL = $wpdb->prepare("SELECT curr_quiz_id FROM enp_quiz_next WHERE parent_guid= '%s'", $_GET["edit_guid"]);
        // see if they're active, b/c deleted questions will throw this number off
        $how_many_questions = $wpdb->get_results($how_many_questions_SQL);

        foreach($how_many_questions as $q) {
            // loop through each question and see if it's active
            $is_q_active_SQL = $wpdb->prepare("SELECT active FROM enp_quiz WHERE ID= '%s' LIMIT 1", $q->curr_quiz_id);
            $is_q_active = $wpdb->get_var($is_q_active_SQL);

            if($is_q_active == 1) {
                $how_many_active_questions++;
            }
        }

    }
    if($how_many_active_questions === 0) {
        // we're on a new one, so there's only one question
        $how_many_active_questions = 1;
    }

    // write it to localStorage
    if(!empty($how_many_active_questions)) { ?>
        <script>
            localStorage.setItem('questionCount', '<?php echo $how_many_active_questions; ?>');
        </script>
    <? }

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
                <?php if ($first_question == true) { include(locate_template('self-service-quiz/quiz-form-styling-options.php')); } ?>
                <div class="panel panel-info aanswer-settings">
                    <div class="panel-heading">Advanced Answer Settings - Optional</div>
                    <div class="panel-body" id="quiz-answers">
                        <?php include(locate_template('self-service-quiz/quiz-form-aanswer-options.php')); ?>
                    </div>
                </div>
                <!--summary settings panel begins -->
                <?php if ($first_question == true) { ?>
                    <div class="panel panel-info summary-settings">
                        <div class="panel-heading">Advanced Summary Settings - Optional</div>
                        <div class="panel-body" id="quiz-answers">
                            <?php include(locate_template('self-service-quiz/quiz-form-summary-options.php')); ?>
                        </div>
                    </div>
                <?php } ?>
                <!--summary settings panel ends -->
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
                        <input type="hidden" name="edit-next-guid" id="edit-next-guid" value="">
                        <?php
                        // next_quiz_id
                        if($quiz_next) {
                            $edit_next_id = $quiz_next->next_quiz_id;
                        } else {
                            $edit_next_id = 0;
                        }


                        // if new quiz flag is true, and we're adding the last question and need to insert it into the database
                        if ($new_quiz && $edit_next_id == 0) {
                            echo "<button id=\"addQuestionSubmit\" class=\"btn btn-primary\"><i class=\"fa fa-plus-circle\"></i> Save and Add Question</button>";
                        }

                        // if we're editing a question, and it's NOT the last question in the quiz
                        elseif ($update_question == true && $edit_next_id != 0) {

                            if ($edit_next_id != 0) {
                                $editNextRow = $wpdb->get_row($wpdb->prepare("SELECT * FROM enp_quiz WHERE ID = %d", $edit_next_id));
                                $edit_next_guid = $editNextRow->guid;
                                // debug_to_console( "edit_next_guid: " . $edit_next_guid); // remove debugToConsole||KVB
                                echo "<button id=\"questionSubmitEditNext\" class=\"btn btn-primary\">Save and Edit Next</button>";
                            } else {
                                // debug_to_console( "not new, but no next_edit_guid returned "); // remove debugToConsole||KVB
                            }
                        }

                        // if we're editing (updating) the last question in a quiz.
                        elseif($update_question == true && $edit_next_id == 0) {
                            echo "<button id=\"updateQuestionAddNext\" class=\"btn btn-primary\"><i class=\"fa fa-plus-circle\"></i> Save and Add Question</button>";
                            // $parent_guid isn't being set right. instead of refactoring, I'm fixing it here so it doesn't create a new unintentional bug
                            $parent_guid = $wpdb->get_var("SELECT parent_guid FROM enp_quiz_next WHERE curr_quiz_id = '" . $curr_quiz_id . "'");

                        }

                        // if we're adding a new question (inserting) and want to keep adding new questions afterwards
                        // NOTE: This *should* be the same as if ($new_quiz && $edit_next_id == 0), but the variables
                        //       get set differently when $_GET["insertQuestion"] == 1 is posted (from clicking My Quizzes > Options > Add Question)

                        elseif($_GET["insertQuestion"] == 1 && $edit_next_id == 0) {
                            echo "<button id=\"addQuestionInsertSubmit\" class=\"btn btn-primary\"><i class=\"fa fa-plus-circle\"></i> Save and Add Question</button>";
                        }
                        else {
                            // Do nothing.
                        }

                        ?>

                        <div class="pull-right">
                        <?php if ($quiz) { ?>
                            <a href="view-quiz?guid=<?php echo $quiz->guid ?>" class="text-danger" role="button">Cancel</a>
                        <?php } elseif ( !$first_question ){ ?>
                            <a href="quiz-tool" class="text-danger" role="button">Cancel</a>
                        <?php } elseif ( $first_question ){ ?>
                            <a href="quiz-tool" class="text-danger" role="button">Cancel</a>
                        <?php } elseif ( $add_question == true ){ ?>
                            <a href="quiz-tool/?cancelInsertion=1&enp_quiz_next=<?php echo $enp_quiz_next; ?>" class="text-danger" role="button">Cancel</a>
                        <?php } ?>
                        &nbsp;&nbsp;
                        <!-- SAVE BUTTON GOES HERE -->
                        <button type="submit" id="questionSubmit" class="btn btn-primary"><?php echo $quiz ? "Save Changes" : "Save"; ?></button>
                        <!-- JavaScript -->
                        <script type="text/javascript">
                            (function ($) {
                                // should deprecate ||KVB
                                $('#insertQuestionSubmit').click(function (e) { // insert question to existing quiz
                                    e.preventDefault();
                                    <?php if ( $insert_question == true ) {

                                    } else {
                                        echo "$('#quiz-new-question').val('newQuizAddQuestion_shouldNotHappen');";
                                    }?>
                                    $('#quiz-form').submit();
                                    return false;
                                });
                                $('#addQuestionSubmit').click(function (e) {
                                    e.preventDefault();
                                    <?php if ( $add_question == true ) {
                                        echo "$('#quiz-new-question').val('updateQuizAddQuestion');";
                                        echo "$('#curr-quiz-id').val('".$curr_quiz_id."');";
                                        echo "$('#parent-guid').val('".$parent_guid."');";
                                        echo "$('#parent-title').val('".$parent_title."');";
                                        echo "$('#enp-quiz-next').val('".$enp_quiz_next."');";
                                    } else {
                                        echo "$('#quiz-new-question').val('newQuizAddQuestion_shouldNotHappen');";
                                    }?>
                                    $('#quiz-form').submit();
                                    return false;
                                });

                                $('#addQuestionInsertSubmit').click(function (e) {
                                    e.preventDefault();
                                    <?
                                        echo "$('#quiz-new-question').val('insertQuestionAddQuestion');";
                                        echo "$('#parent-guid').val('".$prevParentGUID."');";
                                        echo "$('#parent-title').val('".$prevParentTitle."');";
                                        echo "$('#prev-quiz-id').val('".$prevQuizID."');";
                                        echo "$('#next-quiz-id').val('".$nextQuizID."');";

                                    ?>
                                    $('#quiz-form').submit();
                                    return false;
                                });

                                $('#questionSubmitEditNext').click(function (e) {
                                    e.preventDefault();
                                    <?php if ( $update_question == true ) {
                                        echo "$('#quiz-new-question').val('finishQuizUpdateEditNext');";
                                        echo "$('#curr-quiz-id').val('".$curr_quiz_id."');";
                                        echo "$('#parent-guid').val('".$parent_guid."');";
                                        echo "$('#parent-title').val('".$parent_title."');";
                                        echo "$('#enp-quiz-next').val('".$enp_quiz_next."');";
                                        echo "$('#edit-next-guid').val('".$edit_next_guid."');";
                                    } ?>
                                    $('#quiz-form').submit();
                                    return false;
                                });

                                $('#updateQuestionAddNext').click(function (e) {
                                    e.preventDefault();
                                    <?
                                    // This is poorly named because updateQuizAddQuestion was already in use
                                    // (which was poorly named, because updateQuizAddQuestion is really inserting, not updating)
                                    echo "$('#quiz-new-question').val('updateQuestionAddNextQuestion');";
                                    // set values
                                    echo "$('#curr-quiz-id').val('".$curr_quiz_id."');";
                                    echo "$('#parent-guid').val('".$parent_guid."');";
                                    echo "$('#parent-title').val('".$parent_title."');";
                                    echo "$('#enp-quiz-next').val('".$enp_quiz_next."');";
                                    ?>

                                    $('#quiz-form').submit();
                                    return false;
                                });

                                $('#questionSubmit').click(function (e) {
                                    e.preventDefault();

                                    console.log('Question being submitted');
                                    //return false;

                                    <?php if ( $update_question == true ) {
                                        echo "$('#quiz-new-question').val('finishQuizUpdate');";
                                        echo "$('#curr-quiz-id').val('".$curr_quiz_id."');";
                                        echo "$('#parent-guid').val('".$parent_guid."');";
                                        echo "$('#parent-title').val('".$parent_title."');";
                                        echo "$('#enp-quiz-next').val('".$enp_quiz_next."');";
                                    } elseif ( $insert_question == true ) {
                                        echo "$('#quiz-new-question').val('finishNewQuestionOnInsert');";
                                        echo "$('#parent-guid').val('".$prevParentGUID."');";
                                        echo "$('#parent-title').val('".$prevParentTitle."');";
                                        echo "$('#prev-quiz-id').val('".$prevQuizID."');";
                                        echo "$('#next-quiz-id').val('".$nextQuizID."');";
                                    } else {
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
                        </div>
                    </div>
                </div>
            </form>
            <a href="quiz-tool/" class="btn-xs" role="button"><i class="fa fa-arrow-left"></i> Back to Quizzes</a>
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
                        href="quiz-tool/" class="btn btn-primary btn-xs active" role="button">Back to
                        Quizzes</a></p>
            </div>
        </div>
    <?php } ?>
    </div>
    <!-- end .post-content -->
