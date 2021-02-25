<?php
/**
* Overrides data for the question object and sets it from the AB Test results instead
* Create a question object
* @param $question_id = the id of the question you want to get
* @return question object
*/
class Enp_quiz_Question_AB_test_result extends Enp_quiz_Question {
    public static $results;

    public function __construct($question_id, $ab_test_id = false) {
        parent::__construct($question_id);

        if($ab_test_id === false) {
            return false;
        }

        $this->get_ab_test_question_results($question_id, $ab_test_id);
    }

    public function get_ab_test_question_results($question_id, $ab_test_id) {
        self::$results = $this->select_ab_test_question_results($question_id, $ab_test_id);
        if(self::$results !== false) {
            self::$results = $this->set_ab_test_question_results();
        }
        return self::$results;
    }

    public function select_ab_test_question_results($question_id, $ab_test_id) {
        $pdo = new enp_quiz_Db();
        // Do a select query to see if we get a returned row
        $params = array(
            ":ab_test_id" => $ab_test_id,
            ":question_id" => $question_id
        );
        $sql = "SELECT * from ".$pdo->response_ab_test_table." ab_response
            INNER JOIN ".$pdo->response_question_table." question_response
                    ON ab_response.response_quiz_id = question_response.response_quiz_id
                 WHERE ab_response.ab_test_id = :ab_test_id
                   AND question_response.question_id = :question_id
                   AND question_response.response_question_is_deleted = 0";
        $stmt = $pdo->query($sql, $params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // return the found results
        return $results;
    }

    public function set_ab_test_question_results() {
        $this->question_views = $this->set_question_views();
        $this->question_responses = $this->set_question_responses();
        $this->question_responses_correct = $this->set_question_responses_correct();
        $this->question_responses_incorrect = $this->set_question_responses_incorrect();
        $this->question_responses_correct_percentage = $this->set_question_responses_correct_percentage();
        $this->question_responses_incorrect_percentage = $this->set_question_responses_incorrect_percentage();
    }

    public function set_question_views() {
        return count(self::$results);
    }

    public function set_question_responses() {
        $responses = 0;
        if(!empty(self::$results)) {
            foreach(self::$results as $result) {
                if($result['question_responded'] === '1') {
                    $responses++;
                }
            }
        }
        return $responses;
    }

    /**
    * Set the question_responses_correct for our Quiz Object
    * @param self::$results = all results from select_ab_test_question_results() query
    * @return (string) question_responses_correct
    */
    protected function set_question_responses_correct() {
        $question_responses_correct = 0;

        if(!empty(self::$results)) {
            foreach(self::$results as $result) {
                if($result['question_responded'] === '1' && $result['response_correct'] === '1') {
                    $question_responses_correct++;
                }
            }
        }

        return $question_responses_correct;
    }

    /**
    * Set the question_responses_incorrect for our Quiz Object
    * @param self::$results = all results from select_ab_test_question_results() query
    * @return (string) question_responses_incorrect
    */
    protected function set_question_responses_incorrect() {
        $question_responses_incorrect = 0;

        if(!empty(self::$results)) {
            foreach(self::$results as $result) {
                if($result['question_responded'] === '1' && $result['response_correct'] === '0') {
                    $question_responses_incorrect++;
                }
            }
        }

        return $question_responses_incorrect;
    }

    /**
    * Set the question_responses_correct_percentage for our Quiz Object
    * @param self::$results = all results from select_ab_test_question_results() query
    * @return (string) question_responses_correct_percentage (ex. 10)
    */
    protected function set_question_responses_correct_percentage() {
        $question_responses_correct_percentage = 0;

        if((int) $this->question_responses !== 0) {
            $question_responses_correct_percentage = $this->question_responses_correct / $this->question_responses;
        }

        return $question_responses_correct_percentage;
    }

    /**
    * Set the question_responses_incorrect_percentage for our Quiz Object
    * @param self::$results = all results from select_ab_test_question_results() query
    * @return (string) question_responses_incorrect_percentage (ex. 10)
    */
    protected function set_question_responses_incorrect_percentage() {
        $question_responses_incorrect_percentage = 0;

        if((int) $this->question_responses !== 0) {
            $question_responses_incorrect_percentage = $this->question_responses_incorrect / $this->question_responses;
        }

        return $question_responses_incorrect_percentage;
    }



}
