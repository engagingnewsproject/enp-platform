<?php
/**
* Extremely bare wrapper based on
* http://codereview.stackexchange.com/questions/52414/my-simple-pdo-wrapper-class
* & http://stackoverflow.com/questions/20664450/is-a-pdo-wrapper-really-overkill
* to make opening PDO connections and preparing, binding, and executing connections
* faster.
*
**/

class enp_quiz_Db extends PDO {

	public function __construct() {
		// check if a connection already exists
		try {
			// config file for connection info and necessary variables
			include($_SERVER["DOCUMENT_ROOT"].'/enp-quiz-database-config.php');
			// Table names for dynamic reference
			$this->quiz_table = $enp_quiz_table_quiz;
			$this->quiz_option_table = $enp_quiz_table_quiz_option;
			$this->question_table = $enp_quiz_table_question;
			$this->question_mc_option_table = $enp_quiz_table_question_mc_option;
			$this->question_slider_table = $enp_quiz_table_question_slider;
			$this->ab_test_table = $enp_quiz_table_ab_test;
			$this->response_quiz_table = $enp_quiz_table_response_quiz;
			$this->response_question_table = $enp_quiz_table_response_question;
			$this->response_mc_table = $enp_quiz_table_response_mc;
			$this->response_slider_table = $enp_quiz_table_response_slider;
			$this->response_ab_test_table = $enp_quiz_table_ab_test_response;
			$this->embed_site_table = $enp_quiz_table_embed_site;
			$this->embed_site_type_table = $enp_quiz_table_embed_site_type;
			$this->embed_site_br_site_type_table = $enp_quiz_table_embed_site_br_site_type;
			$this->embed_quiz_table = $enp_quiz_table_embed_quiz;

			// set options for PDO connection
			$options = array(
				PDO::ATTR_PERSISTENT => true,
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
			);
			// create the new connection
            parent::__construct('mysql:host='.$enp_db_host.';dbname='.$enp_db_name,
								$enp_db_user,
								$enp_db_password,
								$options);
        } catch (Exception $e) {
            $this->errors = $e->getMessage();
        }
	}

	public function query($sql, $params = null) {
		$stmt = $this->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
