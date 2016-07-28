<?
/**
* Create a slider object and include ab test results
* @param $slider_id = the id of the slider you want to get
* @return slider object
*/
class Enp_quiz_Slider_AB_test_result extends Enp_quiz_Slider_Result {

    public function __construct($slider_id, $ab_test_id) {
        if($ab_test_id === false) {
            return false;
        }

        parent::__construct($slider_id);

        $this->get_ab_test_slider_results($ab_test_id);
    }

    public function get_ab_test_slider_results($ab_test_id) {
        self::$results = $this->select_ab_test_slider_results($ab_test_id);
        if(self::$results !== false) {
            self::$results = $this->set_slider_responses();
        }
        return self::$results;
    }

    public function select_ab_test_slider_results($ab_test_id) {
        $pdo = new enp_quiz_Db();
        // Do a select query to see if we get a returned row
        $params = array(
            ":ab_test_id" => $ab_test_id,
            ":slider_id" => $this->get_slider_id()
        );
        $sql = "SELECT response_slider from ".$pdo->response_ab_test_table." ab_response
            INNER JOIN ".$pdo->response_slider_table." slider_response
                    ON ab_response.response_quiz_id = slider_response.response_quiz_id
                 WHERE ab_response.ab_test_id = :ab_test_id
                   AND slider_response.slider_id = :slider_id
                   AND slider_response.response_slider_is_deleted = 0";
        $stmt = $pdo->query($sql, $params);
        $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
        // return the found results
        return $results;
    }

}
