<?php
/**
 * Save process for questions
 *
 * @link       http://engagingnewsproject.org
 * @since      0.0.1
 *
 * @package    Enp_quiz
 * @subpackage Enp_quiz/includes
 *
 * Called by Enp_quiz_Quiz_create and Enp_quiz_Quiz_preview
 *
 * This class defines all code for processing and saving questions
 * Questions that get passed here will already have been sanitized
 *
 * @since      0.0.1
 * @package    Enp_quiz
 * @subpackage Enp_quiz/database
 * @author     Engaging News Project <jones.jeremydavid@gmail.com>
 */
class Enp_quiz_Save_quiz_option extends Enp_quiz_Save_quiz {

    public function __construct() {

    }

    protected function save_quiz_option($quiz_option = false) {
        if($quiz_option === false) {
            return false;
        }
        // save each of the options we need
        // do a select query to see if we have the quiz_option
        $quiz_option_row = $this->select_quiz_option($quiz_option);
        // if we have the quiz option, then update it, if we don't, insert it
        if($quiz_option_row === false) {
            // the quiz_option_id wasn't found, so create a new one
            $this->insert_quiz_option($quiz_option);
        } else {
            // pdo fetch returns an array, so we have to get it
            $quiz_option_id = $quiz_option_row['quiz_option_id'];
            // we found a quiz_option_id for this option, so just update it
            $this->update_quiz_option($quiz_option, $quiz_option_id);
        }

    }

    protected function select_quiz_option($quiz_option) {
        $pdo = new enp_quiz_Db();
        // Do a select query to see if we get a returned row
        $params = array(
            ":quiz_id" => parent::$quiz['quiz_id'],
            ":quiz_option_name" => $quiz_option
        );
        $sql = "SELECT quiz_option_id from ".$pdo->quiz_option_table."
                 WHERE quiz_id = :quiz_id
                   AND quiz_option_name = :quiz_option_name" ;
        $stmt = $pdo->query($sql, $params);
        $option_row = $stmt->fetch();
        return $option_row;
    }

    protected function insert_quiz_option($quiz_option) {
        // connect to PDO
        $pdo = new enp_quiz_Db();
        // Get our Parameters ready
        $params = array(
                        ':quiz_id' => parent::$quiz['quiz_id'],
                        ':quiz_option_name' => $quiz_option,
                        ':quiz_option_value' => parent::$quiz[$quiz_option]
                    );
        // write our SQL statement
        $sql = "INSERT INTO ".$pdo->quiz_option_table." (
                                            quiz_id,
                                            quiz_option_name,
                                            quiz_option_value
                                        )
                                        VALUES(
                                            :quiz_id,
                                            :quiz_option_name,
                                            :quiz_option_value
                                        )";
        // insert the quiz into the database
        $stmt = $pdo->query($sql, $params);

        // success!
        if($stmt !== false) {
            $quiz_option_id = $pdo->lastInsertId();
            // set-up our response array
            $quiz_option_response = array(
                                        'quiz_option_id' => $quiz_option_id,
                                        'status'       => 'success',
                                        'action'       => 'insert'
                                );
            // pass the response array to our response object
            parent::$response_obj->set_quiz_option_response($quiz_option_response, $quiz_option);

        } else {
            self::$response_obj->add_error('Quiz option '.$quiz_option.' not be added to the database. Try again and if it continues to not work, send us an email with details of how you got to this error.');
        }

    }

    protected function update_quiz_option($quiz_option, $quiz_option_id) {
        // connect to PDO
        $pdo = new enp_quiz_Db();
        // Get our Parameters ready
        $params = array(
                        ':quiz_option_id'   => $quiz_option_id,
                        ':quiz_option_name' => $quiz_option,
                        ':quiz_option_value' => parent::$quiz[$quiz_option]
                    );

        $sql = "UPDATE ".$pdo->quiz_option_table."
                     SET quiz_option_value = :quiz_option_value
                   WHERE quiz_option_id = :quiz_option_id
                     AND quiz_option_name = :quiz_option_name
                ";

        $stmt = $pdo->query($sql, $params);

        // success!
        if($stmt !== false) {
            // set-up our response array
            $quiz_option_response = array(
                                        'quiz_option_id' => $quiz_option_id,
                                        'status'       => 'success',
                                        'action'       => 'update'
                                );
            // pass the response array to our response object
            parent::$response_obj->set_quiz_option_response($quiz_option_response, $quiz_option);
        } else {
            self::$response_obj->add_error('Quiz option '.$quiz_option.' could not be updated. Try again and if it continues to not work, send us an email with details of how you got to this error.');
        }
    }

}
