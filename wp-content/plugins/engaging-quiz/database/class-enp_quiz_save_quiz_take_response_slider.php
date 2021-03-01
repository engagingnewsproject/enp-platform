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

class Enp_quiz_Save_quiz_take_Response_Slider extends Enp_quiz_Save_quiz_take_Response_question {

    public function __construct() {

    }

    /**
    * Connects to DB and inserts the user response.
    * @param $response (array) data we'll be saving to the response table
    * @return builds and returns a response message
    */
    protected function insert_response_slider($response) {
        // connect to PDO
        $pdo = new enp_quiz_Db();
        // Get our Parameters ready
        $params = array(':response_quiz_id' => $response['response_quiz_id'],
                        ':response_question_id' => $response['response_question_id'],
                        ':slider_id'=> $response['slider_id'],
                        ':response_slider' => $response['question_response']
                    );
        // write our SQL statement
        $sql = "INSERT INTO ".$pdo->response_slider_table." (
                                            response_quiz_id,
                                            response_question_id,
                                            slider_id,
                                            response_slider
                                        )
                                        VALUES(
                                            :response_quiz_id,
                                            :response_question_id,
                                            :slider_id,
                                            :response_slider
                                        )";
        // insert the slider into the database
        $stmt = $pdo->query($sql, $params);

        // success!
        if($stmt !== false) {
            // add our response ID to the array we're working with
            $response['response_slider_id'] = $pdo->lastInsertId();
            // set-up our response array
            $response_response = array(
                                        'response_slider_id' => $response['response_slider_id'],
                                        'status'       => 'success',
                                        'action'       => 'insert'
                                );

            // we added a slider successfully, let them know!
            return $response;
        } else {
            // handle errors
            return false;
        }
    }
}
?>
