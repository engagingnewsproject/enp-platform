<?php
/**
 * Save a question view
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

class Enp_quiz_Save_quiz_take_Question_view extends Enp_quiz_Save_quiz_take {
    public static $return = array('error'=>array());

    public function __construct($question_id) {
        // check for validity real quick
        $valid = $this->validate_question_view_data($question_id);
        // invalid, don't attempt save
        if($valid === false) {
            return self::$return;
        }
        // everything looks good! update a quiz view
        self::$return = $this->update_question_view($question_id);
    }



    protected function validate_question_view_data($question_id) {
        $valid = false;
        // check to make sure we have a question id
        if(empty($question_id)) {
            self::$return['error'][] = 'No Question ID set.';
        }
        if(empty(self::$return['error'])) {
            $valid = true;
        }
        return $valid;
    }

    /**
    * Connects to DB and inserts the user response.
    * @param $response (array) data we'll be saving to the response table
    * @return builds and returns a response message
    */
    protected function update_question_view($question_id) {

        // connect to PDO
        $pdo = new enp_quiz_Db();
        // Get our Parameters ready
        $params = array(':question_id' => $question_id,
                    );
        // write our SQL statement
        // write our SQL statement
        $sql = "UPDATE ".$pdo->question_table."
                   SET  question_views = question_views + 1
                 WHERE  question_id = :question_id";
        // update the question view the database
        $stmt = $pdo->query($sql, $params);

        // success!
        if($stmt !== false) {

            // set-up our response array
            $return = array(
                                        'question_id' => $question_id,
                                        'status'       => 'success',
                                        'action'       => 'update_question_views'
                                );

            // merge the response arrays
            self::$return = array_merge($return, self::$return);
            // see what type of question we're working on and save that response
        } else {
            // handle errors
            self::$return['error'][] = 'Update question view failed.';
        }
        // return response
        return self::$return;
    }
}
