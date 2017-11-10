<?php
/**
* Overrides data for the quiz object and sets it from the AB Test results instead
* Create a quiz object
* @param $quiz_id = the id of the quiz you want to get
* @return quiz object
*/
class Enp_quiz_Quiz_AB_test_result extends Enp_quiz_Quiz {
    public $quiz_scores;
    public static $results;

    public function __construct($quiz_id, $ab_test_id = false) {
        parent::__construct($quiz_id);

        if($ab_test_id === false) {
            return false;
        }

        $this->get_ab_test_quiz_results($quiz_id, $ab_test_id);
    }

    public function get_ab_test_quiz_results($quiz_id, $ab_test_id) {
        self::$results = $this->select_ab_test_quiz_results($quiz_id, $ab_test_id);
        if(self::$results !== false) {
            self::$results = $this->set_ab_test_quiz_results();
        }
        return self::$results;
    }

    public function select_ab_test_quiz_results($quiz_id, $ab_test_id) {
        $pdo = new enp_quiz_Db();
        // Do a select query to see if we get a returned row
        $params = array(
            ":ab_test_id" => $ab_test_id,
            ":quiz_id" => $quiz_id
        );
        $sql = "SELECT quiz_viewed,
                       quiz_started,
                       quiz_completed,
                       quiz_restarted,
                       quiz_score
                  FROM ".$pdo->response_ab_test_table." ab_response
            INNER JOIN ".$pdo->response_quiz_table." quiz_response
                    ON ab_response.response_quiz_id = quiz_response.response_quiz_id
                 WHERE ab_response.ab_test_id = :ab_test_id
                   AND quiz_response.quiz_id = :quiz_id
                   AND quiz_response.response_quiz_is_deleted = 0";
        $stmt = $pdo->query($sql, $params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // return the found results
        return $results;
    }

    public function set_ab_test_quiz_results() {
        $this->quiz_views = $this->set_quiz_views();
        $this->quiz_starts = $this->set_quiz_starts();
        $this->quiz_finishes = $this->set_quiz_finishes();
        $this->quiz_scores = $this->set_quiz_scores();
        $this->quiz_score_average = $this->set_quiz_score_average();
    }

    public function set_quiz_views() {
        return count(self::$results);
    }

    public function set_quiz_starts() {
        $starts = 0;
        if(!empty(self::$results)) {
            foreach(self::$results as $result) {
                if($result['quiz_started'] === '1') {
                    $starts++;
                }
            }
        }
        return $starts;
    }

    public function set_quiz_finishes() {
        $finishes = 0;
        if(!empty(self::$results)) {
            foreach(self::$results as $result) {
                if($result['quiz_completed'] === '1') {
                    $finishes++;
                }
            }
        }
        return $finishes;
    }

    public function set_quiz_scores() {
        $scores = array();
        if(!empty(self::$results)) {
            foreach(self::$results as $result) {
                if($result['quiz_completed'] === '1') {
                    $scores[] = (int) round($result['quiz_score'] * 100);

                }
            }
        }
        return $scores;
    }

    public function set_quiz_score_average() {
        $average = 0;
        if(!empty($this->quiz_scores)) {
            $average = array_sum($this->quiz_scores)/count($this->quiz_scores);
        }

        return $average;
    }

    /**
    * Get the individual score data on each take of this quiz
    * @param $quiz = quiz object
    * @return array of all the scores
    */
    public function get_quiz_scores() {
        return $this->quiz_scores;
    }


}
