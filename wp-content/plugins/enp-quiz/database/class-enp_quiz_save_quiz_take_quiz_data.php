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

}
