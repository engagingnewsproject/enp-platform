<?
/**
* Create a mc_option object
* @param $mc_option_id = the id of the mc_option you want to get
* @return mc_option object
*/
class Enp_quiz_MC_option_AB_test_result extends Enp_quiz_MC_option {
    public static $results;

    public function __construct($mc_option_id, $ab_test_id) {
        parent::__construct($mc_option_id);

        if($ab_test_id === false) {
            return false;
        }

        $this->get_ab_test_mc_option_results($ab_test_id);
    }

    public function get_ab_test_mc_option_results($ab_test_id) {
        self::$results = $this->select_ab_test_question_results($ab_test_id);
        if(self::$results !== false) {
            self::$results = $this->set_ab_test_mc_option_responses();
        }
        return self::$results;
    }

    public function select_ab_test_question_results($ab_test_id) {
        $pdo = new enp_quiz_Db();
        // Do a select query to see if we get a returned row
        $params = array(
            ":ab_test_id" => $ab_test_id,
            ":mc_option_id" => $this->get_mc_option_id()
        );
        $sql = "SELECT * from ".$pdo->response_ab_test_table." ab_response
            INNER JOIN ".$pdo->response_mc_table." mc_response
                    ON ab_response.response_quiz_id = mc_response.response_quiz_id
                 WHERE ab_response.ab_test_id = :ab_test_id
                   AND mc_response.mc_option_id = :mc_option_id";
        $stmt = $pdo->query($sql, $params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // return the found results
        return $results;
    }


    public function set_ab_test_mc_option_responses() {
        $this->mc_option_responses = count(self::$results);
    }

}
