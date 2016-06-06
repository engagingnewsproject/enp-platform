<?php
/**
 * Save Take Quiz Data
 *
 * @link       http://engagingnewsproject.org
 * @since      0.0.1
 *
 * @package    Enp_quiz
 * @subpackage Enp_quiz/includes
 *
 *
 * @since      0.0.1
 * @package    Enp_quiz
 * @subpackage Enp_quiz/database
 * @author     Engaging News Project <jones.jeremydavid@gmail.com>
 */

class Enp_quiz_Save_quiz_take_Quiz_data extends Enp_quiz_Save_quiz_take {
    public static $quiz,
                  $return = array('error'=>array());

    public function __construct($quiz) {
        // TODO: Move this validity check to each individual
        //       pdo query and update the functions that call it

        // check for validity real quick
        $valid = $this->validate_quiz($quiz);
        // invalid, don't attempt save
        if($valid === false) {
            return self::$return;
        }
    }



    protected function validate_quiz($quiz) {
        // check to make sure we have a question id
        $quiz_id = $quiz->quiz_id;
        if(empty($quiz_id)) {
            self::$return['error'][] = 'Quiz not found.';
        } else {
            self::$quiz = $quiz;
        }
    }

    /**
    * Connects to DB and increase the quiz views by one.
    * @param $response (array) data we'll be saving to the response table
    * @return builds and returns a response message
    */
    public function update_quiz_views() {

        // connect to PDO
        $pdo = new enp_quiz_Db();
        // Get our Parameters ready
        $params = array(':quiz_id' => self::$quiz->get_quiz_id());
        // write our SQL statement
        $sql = "UPDATE ".$pdo->quiz_table."
                   SET  quiz_views = quiz_views + 1
                 WHERE  quiz_id = :quiz_id";
        // update the question view the database
        $stmt = $pdo->query($sql, $params);

        // success!
        if($stmt !== false) {

            // set-up our response array
            $return = array(
                                        'quiz_id' => self::$quiz->get_quiz_id(),
                                        'status'       => 'success',
                                        'action'       => 'update_quiz_views'
                                );

            // merge the response arrays
            self::$return = array_merge($return, self::$return);
            // see what type of question we're working on and save that response
        } else {
            // handle errors
            self::$return['error'][] = 'Update quiz views failed.';
        }
        // return response
        return self::$return;
    }

    /**
    * Connects to DB and increase the quiz view by one.
    * @param $response (array) data we'll be saving to the response table
    * @return builds and returns a response message
    */
    public function update_quiz_starts() {

        // connect to PDO
        $pdo = new enp_quiz_Db();
        // Get our Parameters ready
        $params = array(':quiz_id' => self::$quiz->get_quiz_id());
        // write our SQL statement
        $sql = "UPDATE ".$pdo->quiz_table."
                   SET  quiz_starts = quiz_starts + 1
                 WHERE  quiz_id = :quiz_id";
        // update the question start the database
        $stmt = $pdo->query($sql, $params);

        // success!
        if($stmt !== false) {

            // set-up our response array
            $return = array(
                                        'quiz_id' => self::$quiz->get_quiz_id(),
                                        'status'       => 'success',
                                        'action'       => 'update_quiz_starts'
                                );

            // merge the response arrays
            self::$return = array_merge($return, self::$return);
            // see what type of question we're working on and save that response
        } else {
            // handle errors
            self::$return['error'][] = 'Update quiz starts failed.';
        }
        // return response
        return self::$return;
    }

    /**
    * Connects to DB and increase the quiz finishes by one.
    * @param $response (array) data we'll be saving to the response table
    * @return builds and returns a response message
    */
    public function update_quiz_finishes($new_score) {

        // connect to PDO
        $pdo = new enp_quiz_Db();
        // Get our Parameters ready
        $params = array(
                        ':quiz_id' => self::$quiz->get_quiz_id(),
                        ':new_score' => $new_score,
                    );
        // write our SQL statement
        $sql = "UPDATE ".$pdo->quiz_table."
                   SET  quiz_score_average = (quiz_finishes * quiz_score_average + :new_score) / (quiz_finishes + 1),
                        quiz_finishes = quiz_finishes + 1
                 WHERE  quiz_id = :quiz_id";
        // update the question finishes the database
        $stmt = $pdo->query($sql, $params);

        // success!
        if($stmt !== false) {

            // set-up our response array
            $return = array(
                                        'quiz_id' => self::$quiz->get_quiz_id(),
                                        'status'       => 'success',
                                        'action'       => 'update_quiz_finishes'
                                );

            // merge the response arrays
            self::$return = array_merge($return, self::$return);
            // see what type of question we're working on and save that response
        } else {
            // handle errors
            self::$return['error'][] = 'Update quiz finishes failed.';
        }
        // return response
        return self::$return;
    }

    /*
    * Soft deletes all quiz responses that match the quiz_id
    * @param $quiz object
    * @return (mixed) pdo affected row count
    *         or false if not successful
    */
    public function delete_quiz_responses($quiz) {
        $quiz_id = $quiz->get_quiz_id();
        // connect to PDO
        $pdo = new enp_quiz_Db();
        // Get our Parameters ready
        $params = array(
                        ':quiz_id' => $quiz_id,
                    );
        // write our SQL statement
        $sql = "UPDATE ".$pdo->response_quiz_table."
                   SET  response_quiz_is_deleted = 1
                 WHERE  quiz_id = :quiz_id";
        // update the quiz responses the database
        $stmt = $pdo->query($sql, $params);

        if($stmt !== false) {
            // reset the compiled stats on the quiz table too
            $this->reset_quiz_data($quiz_id);
            $question_ids = $quiz->get_questions();
            // soft delete question responses & data
            $this->reset_all_quiz_questions_data($question_ids);
            // success!
            return $stmt->rowCount();
        } else {
            // error :(
            return false;
        }

    }

    /*
    * Resets compiled quiz data on quiz row to 0
    * @param $quiz_id (string or int)
    * @return (mixed) pdo affected row if sucess
    *         or false if not successful
    */
    protected function reset_quiz_data($quiz_id) {
        // connect to PDO
        $pdo = new enp_quiz_Db();
        // Get our Parameters ready
        $params = array(
                        ':quiz_id' => $quiz_id
                    );
        // write our SQL statement
        $sql = "UPDATE ".$pdo->quiz_table."
                   SET  quiz_views = 0,
                        quiz_starts = 0,
                        quiz_finishes = 0,
                        quiz_score_average = 0,
                        quiz_time_spent = 0,
                        quiz_time_spent_average = 0
                 WHERE  quiz_id = :quiz_id";
        // update the quiz data in the database
        $stmt = $pdo->query($sql, $params);

        if($stmt !== false) {
            // success! If this rowCount doesn't === 1 then we have a problem...
            return $stmt->rowCount();
        } else {
            // error :(
            return false;
        }
    }

    /*
    * Foreach question_id, soft delete the responses and reset the stats
    * @param $question_ids
    * @return nothing
    */
    protected function reset_all_quiz_questions_data($question_ids) {
        if(!empty($question_ids)) {
            foreach($question_ids as $question_id) {
                // soft delete responses
                $this->delete_question_responses($question_id);
                // reset stats
                $this->reset_question_data($question_id);
                // create the question object
                $question = new Enp_quiz_Question($question_id);
                // get the question type
                $question_type = $question->get_question_type();

                if($question_type === 'mc') {
                    $mc_option_ids = $question->get_mc_options();
                    foreach($mc_option_ids as $mc_option_id) {
                        // reset compiled mc option data
                        $this->reset_mc_option_data($mc_option_id);
                        // soft delete mc option response
                        $this->delete_mc_option_responses($mc_option_id);
                    }
                }
                elseif($question_type === 'slider') {
                    // get the slider id
                    $slider_id = $question->get_slider();
                    // soft delete the responses
                    $this->delete_slider_responses($slider_id);
                }

            }
        }

    }

    /*
    * Soft deletes all question responses that match the question_id
    * @param $question_id (string or int)
    * @return (mixed) pdo affected row count
    *         or false if not successful
    */
    protected function delete_question_responses($question_id) {
        // connect to PDO
        $pdo = new enp_quiz_Db();
        // Get our Parameters ready
        $params = array(
                        ':question_id' => $question_id
                    );
        // write our SQL statement
        $sql = "UPDATE ".$pdo->response_question_table."
                   SET  response_question_is_deleted = 1
                 WHERE  question_id = :question_id";
        // update the quiz responses the database
        $stmt = $pdo->query($sql, $params);

        if($stmt !== false) {
            // success!
            return $stmt->rowCount();
        } else {
            // error :(
            return false;
        }
    }

    /*
    * Resets compiled question data on question row to 0
    * @param $question_id (string or int)
    * @return (mixed) pdo row count if successful
    *         or false if not successful
    */
    protected function reset_question_data($question_id) {
        // connect to PDO
        $pdo = new enp_quiz_Db();
        // Get our Parameters ready
        $params = array(
                        ':question_id' => $question_id
                    );
        // write our SQL statement
        $sql = "UPDATE ".$pdo->question_table."
                   SET  question_views = 0,
                        question_responses = 0,
                        question_responses_correct = 0,
                        question_responses_incorrect = 0,
                        question_responses_correct_percentage = 0,
                        question_responses_incorrect_percentage = 0,
                        question_responses_incorrect = 0,
                        question_score_average = 0,
                        question_time_spent_average = 0
                 WHERE  question_id = :question_id";
        // update the quiz data in the database
        $stmt = $pdo->query($sql, $params);

        if($stmt !== false) {
            // success! If this rowCount doesn't === 1 then we have a problem...
            return $stmt->rowCount();
        } else {
            // error :(
            return false;
        }
    }

    /*
    * Soft deletes all mc option responses
    * @param $mc_option_id (string or int)
    * @return (mixed) pdo affected row count
    *         or false if not successful
    */
    protected function delete_mc_option_responses($mc_option_id) {
        // connect to PDO
        $pdo = new enp_quiz_Db();
        // Get our Parameters ready
        $params = array(
                        ':mc_option_id' => $mc_option_id
                    );
        // write our SQL statement
        $sql = "UPDATE ".$pdo->response_mc_table."
                   SET  response_mc_is_deleted = 1
                 WHERE  mc_option_id = :mc_option_id";
        // update the quiz responses the database
        $stmt = $pdo->query($sql, $params);

        if($stmt !== false) {
            // success!
            return $stmt->rowCount();
        } else {
            // error :(
            return false;
        }
    }

    /*
    * Resets compiled mc option data on mc option row to 0
    * @param $mc_option_id (string or int)
    * @return (mixed) row count if successful
    *         or false if not successful
    */
    protected function reset_mc_option_data($mc_option_id) {
        // connect to PDO
        $pdo = new enp_quiz_Db();
        // Get our Parameters ready
        $params = array(
                        ':mc_option_id' => $mc_option_id
                    );
        // write our SQL statement
        $sql = "UPDATE ".$pdo->question_mc_option_table."
                   SET  mc_option_responses = 0
                 WHERE  mc_option_id = :mc_option_id";
        // update the quiz data in the database
        $stmt = $pdo->query($sql, $params);

        if($stmt !== false) {
            // success! If this rowCount doesn't === 1 then we have a problem...
            return $stmt->rowCount();
        } else {
            // error :(
            return false;
        }
    }

    /*
    * Soft deletes all slider responses
    * @param $slider_id (string or int)
    * @return (mixed) pdo affected row count
    *         or false if not successful
    */
    protected function delete_slider_responses($slider_id) {
        // connect to PDO
        $pdo = new enp_quiz_Db();
        // Get our Parameters ready
        $params = array(
                        ':slider_id' => $slider_id
                    );
        // write our SQL statement
        $sql = "UPDATE ".$pdo->response_slider_table."
                   SET  response_slider_is_deleted = 1
                 WHERE  slider_id = :slider_id";
        // update the quiz responses the database
        $stmt = $pdo->query($sql, $params);

        if($stmt !== false) {
            // success!
            return $stmt->rowCount();
        } else {
            // error :(
            return false;
        }
    }
}
