<?php
/**
 * Saves responses from AB Tets
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

class Enp_quiz_Save_quiz_take_Response_ab_test extends Enp_quiz_Save_quiz_take {
    public static $return = array('error'=>array());

    public function __construct($ab_test_id = false, $response_quiz_id = false) {
        if($ab_test_id === false) {
            self::$return['error'][] = 'No AB Test ID set.';
        }

        if($ab_test_id === false) {
            self::$return['error'][] = 'No Response Quiz ID set.';
        }

        if(!empty(self::$return['error'])) {
            // if we have errors, don't try to save
            return self::$return;
        }
        // everything looks good! update a quiz view
        self::$return = $this->insert_response_ab_test($ab_test_id, $response_quiz_id);
    }


    /**
    * Connects to DB and inserts the user response.
    * @param $response (array) data we'll be saving to the response table
    * @return builds and returns a response message
    */
    private function insert_response_ab_test($ab_test_id, $response_quiz_id) {

        // connect to PDO
        $pdo = new enp_quiz_Db();
        // Get our Parameters ready
        $params = array(':ab_test_id' => $ab_test_id,
                        ':response_quiz_id' => $response_quiz_id
                    );

        $sql = "INSERT INTO ".$pdo->response_ab_test_table." (
                    response_quiz_id,
                    ab_test_id
                )
                VALUES(
                    :response_quiz_id,
                    :ab_test_id
                )";
        // update the question view the database
        $stmt = $pdo->query($sql, $params);

        // success!
        if($stmt !== false) {

            // set-up our response array
            $return = array(
                                        'result_ab_test_id' => $pdo->lastInsertId(),
                                        'status'       => 'success',
                                        'action'       => 'insert_ab_test_result'
                                );

            // merge the response arrays
            self::$return = array_merge($return, self::$return);
            // see what type of question we're working on and save that response
        } else {
            // handle errors
            self::$return['error'][] = 'Insert ab_test_result_id failed.';
        }
        // return response
        return self::$return;
    }
}
