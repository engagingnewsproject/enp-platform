<?php

// we'll need to run some MYSQL when we update the social share code so that all quizzes have all the info they need
// PDO to grab ALL unique Quiz IDs
$save_quiz = new Enp_quiz_Save_quiz();

// query to get ALL quizzes
// for now,  just do one
// foreach($quizzes as $quiz)...

    $quiz_id = 1;
    $quiz = new Enp_quiz_Quiz($quiz_id);
    // turn it into an array
    $quiz = (array) $quiz;
    // we have to set this as the quiz created by value so it will allow us to update
    // in the future, we can set it to an admin value once we allow that
    $quiz['quiz_updated_by'] = get_current_user_id();
    $quiz['quiz_updated_at'] = date("Y-m-d H:i:s");
    $save_quiz->save($quiz);

// endforeach



?>
