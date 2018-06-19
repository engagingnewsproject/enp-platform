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

class Enp_quiz_Save_quiz_take_Response_quiz extends Enp_quiz_Save_quiz_take {
    public static $response;

    public function __construct() {

    }

    /**
    * Connects to DB and inserts the user response.
    * @param $response (array) data we'll be saving to the response table
    * @return builds and returns a response message
    */
    public function insert_response_quiz($response) {
        // connect to PDO
        $pdo = new enp_quiz_Db();
        // Get our Parameters ready
        $params = array(':quiz_id'      => $response['quiz_id'],
                        ':user_ip'      => '',
                        ':user_id'      => $response['user_id'],
                        ':quiz_viewed'  => 1,
                        ':response_quiz_created_at' => $response['response_quiz_updated_at'],
                        ':response_quiz_viewed_at' => '1970-01-01 06:00:00',
                        ':response_quiz_updated_at' => $response['response_quiz_updated_at']					
                       );
        // write our SQL statement
        $sql = "INSERT INTO ".$pdo->response_quiz_table." (
                                            quiz_id,
                                            user_ip,
                                            user_id,
                                            quiz_viewed,
                                            response_quiz_created_at,
                                            response_quiz_updated_at,
											response_quiz_viewed_at
                                        )
                                        VALUES(
                                            :quiz_id,
                                            :user_ip,
                                            :user_id,
                                            :quiz_viewed,
                                            :response_quiz_created_at,
                                            :response_quiz_updated_at,
											:response_quiz_viewed_at
                                        )";
        // insert the mc_option into the database
        $stmt = $pdo->query($sql, $params);

        // success!
        if($stmt !== false) {
            // add our response ID to the array we're working with
            $response['response_quiz_id'] = $pdo->lastInsertId();
            // set-up our response array
            $return = array(
                                        'response_quiz_id' => $response['response_quiz_id'],
                                        'status'       => 'success',
                                        'action'       => 'insert'
                                );

            // merge the response arrays
            $return = array_merge($response, $return);

        } else {
            // handle errors
            $return['error'] = 'Save response failed.';
        }
        // return response
        return $return;
    }

    /**
    * Connects to DB and update the user response when quiz started
    * @param $response (array) data we'll be saving to the response quiz table
    * @return builds and returns a response message
    */
    public function update_response_quiz_started($response) {

        // connect to PDO
        $pdo = new enp_quiz_Db();
        // Get our Parameters ready
        $params = array(':response_quiz_id'      => $response['response_quiz_id'],
                        ':quiz_id'      => $response['quiz_id'],
                        ':quiz_started' => 1,
                        ':response_quiz_updated_at' => $response['response_quiz_updated_at']
                    );
        // write our SQL statement
        $sql = "UPDATE ".$pdo->response_quiz_table."
                   SET  quiz_started = :quiz_started,
                        response_quiz_updated_at = :response_quiz_updated_at
                 WHERE  response_quiz_id = :response_quiz_id
                   AND  quiz_id = :quiz_id";
        // insert the mc_option into the database
        $stmt = $pdo->query($sql, $params);

        // success!
        if($stmt !== false) {

            // set-up our response array
            $return = array(
                                        'response_quiz_id' => $response['response_quiz_id'],
                                        'status'       => 'success',
                                        'action'       => 'updated_quiz_started'
                                );

            // merge the response arrays
            $return = array_merge($response, $return);

        } else {
            // handle errors
            $return['error'] = 'Save response failed.';
        }
        // return response
        return $return;
    }

    /**
    * Connects to DB and update the user response when quiz started
    * @param $response (array) data we'll be saving to the response quiz table
    * @return builds and returns a response message
    */
    protected function update_response_quiz_completed($response) {
        // connect to PDO
        $pdo = new enp_quiz_Db();
        // Get our Parameters ready
        $params = array(':response_quiz_id'      => $response['response_quiz_id'],
                        ':quiz_id'     => $response['quiz_id'],
                        ':quiz_completed' => 1,
                        ':quiz_score'  => $response['quiz_end']['score'],
                        ':response_quiz_updated_at' => $response['response_quiz_updated_at']
                    );
        // write our SQL statement
        $sql = "UPDATE ".$pdo->response_quiz_table."
                   SET  quiz_completed = :quiz_completed,
                        quiz_score = :quiz_score,
                        response_quiz_updated_at = :response_quiz_updated_at
                 WHERE  response_quiz_id = :response_quiz_id
                   AND  quiz_id = :quiz_id";
        // insert the mc_option into the database
        $stmt = $pdo->query($sql, $params);

        // success!
        if($stmt !== false) {

            // set-up our response array
            $return = array(
                                        'response_quiz_id' => $response['response_quiz_id'],
                                        'status'       => 'success',
                                        'action'       => 'updated_quiz_completed'
                                );

            // merge the response arrays
            $return = array_merge($response, $return);

        } else {
            // handle errors
            $return['error'] = 'Save response failed.';
        }
        // return response
        return $return;
    }

    /**
    * Connects to DB and update the user response when quiz restarted
    * @param $response (array) data we'll be saving to the response quiz table
    * @return builds and returns a response message
    */
    public function update_response_quiz_restarted($response) {

        // connect to PDO
        $pdo = new enp_quiz_Db();
        // Get our Parameters ready
        $params = array(':response_quiz_id'      => $response['response_quiz_id'],
                        ':quiz_id'      => $response['quiz_id'],
                        ':quiz_restarted' => 1,
                        ':response_quiz_updated_at' => $response['response_quiz_updated_at']
                    );
        // write our SQL statement
        $sql = "UPDATE ".$pdo->response_quiz_table."
                   SET  quiz_restarted = :quiz_restarted,
                        response_quiz_updated_at = :response_quiz_updated_at
                 WHERE  response_quiz_id = :response_quiz_id
                   AND  quiz_id = :quiz_id";
        // insert the mc_option into the database
        $stmt = $pdo->query($sql, $params);

        // success!
        if($stmt !== false) {

            // set-up our response array
            $return = array(
                                        'response_quiz_id' => $response['response_quiz_id'],
                                        'status'       => 'success',
                                        'action'       => 'updated_quiz_started'
                                );

            // merge the response arrays
            $return = array_merge($response, $return);

        } else {
            // handle errors
            $return['error'] = 'Save response failed.';
        }
        // return response
        return $return;
    }


}
