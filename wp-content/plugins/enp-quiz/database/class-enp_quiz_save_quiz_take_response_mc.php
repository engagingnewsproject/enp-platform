<?php
/**
 * Save responses from people taking quizzes
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

class Enp_quiz_Save_quiz_take_Response_MC extends Enp_quiz_Save_quiz_take_Response_question {

    public function __construct() {

    }

    /**
    * Connects to DB and inserts the user response.
    * @param $response (array) data we'll be saving to the response table
    * @return builds and returns a response message
    */
    protected function insert_response_mc($response) {
        // connect to PDO
        $pdo = new enp_quiz_Db();
        // Get our Parameters ready
        $params = array(':response_quiz_id' => $response['response_quiz_id'],
                        ':response_question_id' => $response['response_question_id'],
                        ':mc_option_id'=> $response['question_response']
                    );
        // write our SQL statement
        $sql = "INSERT INTO ".$pdo->response_mc_table." (
                                            response_quiz_id,
                                            response_question_id,
                                            mc_option_id
                                        )
                                        VALUES(
                                            :response_quiz_id,
                                            :response_question_id,
                                            :mc_option_id
                                        )";
        // insert the mc_option into the database
        $stmt = $pdo->query($sql, $params);

        // success!
        if($stmt !== false) {
            // add our response ID to the array we're working with
            $response['response_mc_id'] = $pdo->lastInsertId();
            // set-up our response array
            $response_response = array(
                                        'response_mc_id' => $response['response_mc_id'],
                                        'status'       => 'success',
                                        'action'       => 'insert'
                                );
            // see what type of question we're working on and save that response
            if($response['question_type'] === 'mc') {
                // we added a mc_option successfully, let them know!
                return $response;
            }
        } else {
            // handle errors
            return false;
        }
    }

    /**
    * Connects to DB and inserts the user response.
    * @param $response (array) data we'll be saving to the response table
    * @return builds and returns a response message
    */
    protected function increase_mc_option_responses($mc_option_id) {

        // connect to PDO
        $pdo = new enp_quiz_Db();
        // Get our Parameters ready
        $params = array(':mc_option_id' => $mc_option_id,
                    );
        // write our SQL statement
        // write our SQL statement
        $sql = "UPDATE ".$pdo->question_mc_option_table."
                   SET  mc_option_responses = mc_option_responses + 1
                 WHERE  mc_option_id = :mc_option_id";
        // update the question view the database
        $stmt = $pdo->query($sql, $params);

        // success!
        if($stmt !== false) {

            // set-up our response array
            $return = array(
                                        'mc_option_id' => $mc_option_id,
                                        'status'       => 'success',
                                        'action'       => 'increase_mc_option_responses'
                                );

            // merge the response arrays
            self::$return = array_merge($return, self::$return);
            // see what type of question we're working on and save that response
        } else {
            // handle errors
            self::$return['error'][] = 'Increase MC Option Responses failed.';
        }
        // return response
        return self::$return;
    }
}
?>
