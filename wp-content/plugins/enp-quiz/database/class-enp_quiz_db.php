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

    public function fetchOne($sql, $params = []) {
		$stmt = $this->query($sql, $params);
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	public function fetchAll($sql, $params = []) {
		$stmt = $this->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/*
     * Get Quizzes
     *
     */
    public function getQuizzes($where = []) {

		$params = $this->buildParams($where);
    	$sql = "SELECT * from ".$this->quiz_table." WHERE quiz_is_deleted = 0";
    	
    	if($where) {
    		$sql .= $this->buildWhere($params, true);
    	}
    	
    	return $this->fetchAll($sql, $params);
    }

    /*
     * Get Sites
     *
     */
    public function getSites($where = []) {

		$params = $this->buildParams($where);
    	$sql = "SELECT * from ".$this->embed_site_table;
    	
    	if($where) {
    		$sql .= $this->buildWhere($params, true);
    	}
    	
    	return $this->fetchAll($sql, $params);
    }

    /*
     * Get Embeds
     *
     */
    public function getEmbeds($where = []) {

		$params = $this->buildParams($where);
    	$sql = "SELECT * from ".$this->embed_quiz_table;
    	
    	if($where) {
    		$sql .= $this->buildWhere($params, true);
    	}
    	
    	return $this->fetchAll($sql, $params);
    }

    // TOTALS
    public function getResponsesCorrectTotal() {
        $sql = "SELECT COUNT(*) from ".$this->response_question_table." WHERE response_correct = 1";
        return (int) $this->fetchOne($sql)['COUNT(*)'];
    }

    public function getResponsesIncorrectTotal() {
        $sql = "SELECT COUNT(*) from ".$this->response_question_table." WHERE response_correct = 0";
        return (int) $this->fetchOne($sql)['COUNT(*)'];
    }

    public function getMCQuestionsTotal() {
        $sql = "SELECT COUNT(*) from ".$this->question_table." WHERE question_type = 'mc'";
        return (int) $this->fetchOne($sql)['COUNT(*)'];
    }

    public function getSliderQuestionsTotal() {
        $sql = "SELECT COUNT(*) from ".$this->question_table." WHERE question_type = 'slider'";
        return (int) $this->fetchOne($sql)['COUNT(*)'];
    }

    public function getUniqueUsersTotal() {
        $sql = "SELECT COUNT(DISTINCT user_id) as users
                    FROM ".$this->response_quiz_table;

        return (int) $this->fetchOne($sql)['users'];

    }
    public function buildWhere($params, $where = true) {
    	$sql = '';
    	if($where === true) {
    		$sql = ' WHERE ';
    	}
    	if(!empty($params)) {
    		$i = 1;
    		foreach($params as $key => $val) {
    			if(is_array($val)) {
    				// for things like 'date > :date'
    				$sql .= $val['key'].' '.$val['operator'].' '.$val['val'];
    			} else {
    				$sql .= $key.' = '.$val;
    			}
    			if($i !== count($params)) {
	                // not the last one, so add an AND statement
	                $where .= " AND ";
	                $i++;
	            }
    		}
    	}
    	return $sql;
    }

    /**
     * Builds out bound parameters in the array by adding a : to the beginning of the array keys
     *
     * @param $params ARRAY
     * @return ARRAY
     */
    public function buildParams($params) {
        $bound = [];

        foreach($params as $key => $val) {
            $bound[$key] = $val;
        }

        return $bound;
    }
}
