<?
/**
* Create a mc_option object
* @param $mc_option_id = the id of the mc_option you want to get
* @return mc_option object
*/
class Enp_quiz_MC_option {
    public  $mc_option_id,
            $mc_option_content,
            $mc_option_correct,
            $mc_option_order,
            $mc_option_responses,
            $mc_option_is_deleted;

    protected static $mc_option;


    public function __construct($mc_option_id) {
        // returns false if no mc_option found
        $this->get_mc_option_by_id($mc_option_id);
    }

    /**
    *   Build mc_option object by id
    *
    *   @param  $mc_option_id = mc_option_id that you want to select
    *   @return mc_option object, false if not found
    **/
    public function get_mc_option_by_id($mc_option_id) {
        self::$mc_option = $this->select_mc_option_by_id($mc_option_id);
        if(self::$mc_option !== false) {
            self::$mc_option = $this->set_mc_option_object_values();
        }
        return self::$mc_option;
    }

    /**
    *   For using PDO to select one mc_option row
    *
    *   @param  $mc_option_id = mc_option_id that you want to select
    *   @return row from database table if found, false if not found
    **/
    public function select_mc_option_by_id($mc_option_id) {
        $pdo = new enp_quiz_Db();
        // Do a select query to see if we get a returned row
        $params = array(
            ":mc_option_id" => $mc_option_id
        );
        $sql = "SELECT * from ".$pdo->question_mc_option_table." WHERE
                mc_option_id = :mc_option_id
                AND mc_option_is_deleted = 0";
        $stmt = $pdo->query($sql, $params);
        $mc_option_row = $stmt->fetch();
        // return the found mc_option row
        return $mc_option_row;
    }

    /**
    * Hook up all the values for the object
    * @param $mc_option = row from the mc_option_table
    */
    protected function set_mc_option_object_values() {
        $this->mc_option_id = $this->set_mc_option_id();
        $this->mc_option_content = $this->set_mc_option_content();
        $this->mc_option_correct = $this->set_mc_option_correct();
        $this->mc_option_order = $this->set_mc_option_order();
        $this->mc_option_responses = $this->set_mc_option_responses();
        $this->mc_option_is_deleted = $this->set_mc_option_is_deleted();
    }

    /**
    * Set the mc_option_id for our Question Object
    * @param $mc_option = mc_option row from mc_option database table
    * @return mc_option_id field from the database
    */
    protected function set_mc_option_id() {
        $mc_option_id = self::$mc_option['mc_option_id'];
        return $mc_option_id;
    }

    /**
    * Set the mc_option_content for our Quiz Object
    * @param $mc_option = mc_option row from mc_option database table
    * @return mc_option_content field from the database
    */
    protected function set_mc_option_content() {
        $mc_option_content = stripslashes(self::$mc_option['mc_option_content']);
        return $mc_option_content;
    }

    /**
    * Set the mc_option_correct for our Quiz Object
    * @param $mc_option = mc_option row from mc_option database table
    * @return mc_option_correct field from the database
    */
    protected function set_mc_option_correct() {
        $mc_option_correct = self::$mc_option['mc_option_correct'];
        return $mc_option_correct;
    }

    /**
    * Set the mc_option_order for our Quiz Object
    * @param $mc_option = mc_option row from mc_option database table
    * @return mc_option_order field from the database
    */
    protected function set_mc_option_order() {
        $mc_option_order = self::$mc_option['mc_option_order'];
        return $mc_option_order;
    }

    /**
    * Set the mc_option_responses for our Quiz Object
    * @param $mc_option = mc_option row from mc_option database table
    * @return mc_option_responses field from the database
    */
    protected function set_mc_option_responses() {
        $mc_option_responses = self::$mc_option['mc_option_responses'];
        return $mc_option_responses;
    }

    /**
    * Set the mc_option_is_deleted for our Quiz Object
    * @param $mc_option = mc_option row from mc_option database table
    * @return mc_option_order field from the database
    */
    protected function set_mc_option_is_deleted() {
        $mc_option_is_deleted = self::$mc_option['mc_option_is_deleted'];
        return $mc_option_is_deleted;
    }

    /**
    * Get the mc_option_id for our Quiz Object
    * @param $mc_option = mc_option object
    * @return mc_option_id from the object
    */
    public function get_mc_option_id() {
        $mc_option_id = $this->mc_option_id;
        return $mc_option_id;
    }

    /**
    * Get the mc_option_content for our Quiz Object
    * @param $mc_option = mc_option object
    * @return mc_option_content from the object
    */
    public function get_mc_option_content() {
        $mc_option_content = $this->mc_option_content;
        return $mc_option_content;
    }

    /**
    * Get the mc_option_correct for our Quiz Object
    * @param $mc_option = mc_option object
    * @return mc_option_correct from the object
    */
    public function get_mc_option_correct() {
        $mc_option_correct = $this->mc_option_correct;
        return $mc_option_correct;
    }

    /**
    * Get the mc_option_order for our Quiz Object
    * @param $mc_option = mc_option object
    * @return mc_option_order from the object
    */
    public function get_mc_option_order() {
        $mc_option_order = $this->mc_option_order;
        return $mc_option_order;
    }

    /**
    * Get the mc_option_responses for our Quiz Object
    * @param $mc_option = mc_option object
    * @return mc_option_responses from the object
    */
    public function get_mc_option_responses() {
        $mc_option_responses = $this->mc_option_responses;
        return $mc_option_responses;
    }


    /**
    * Get the mc_option_is_deleted for our Quiz Object
    * @param $mc_option = mc_option object
    * @return mc_option_is_deleted from the object
    */
    public function get_mc_option_is_deleted() {
        $mc_option_is_deleted = $this->mc_option_is_deleted;
        return $mc_option_is_deleted;
    }

    /**
    * Get the built question with all MC Option data for taking a quiz
    *
    * @param $mc_option = mc_option object
    * @return array with full mc_option data for taking quizzes or converting to JSON
    */
    public function get_take_mc_option_array() {
        // for now, just cast object to array. Later we may need to process this more
        return (array) $this;
    }

    /**
    * Get the value we should be saving on a mc_option
    * get posted if present, if not, get object. This is so we give them their
    * current entry if we don't *actually* save yet.
    * @param $string = what you want to get ('mc_option_content', 'mc_option_explanation', whatever)
    * @param $i = which mc_option you're trying to get a value from
    * @return $value
    */
    /* I don't think we need this anymore. MC_options always have an ID now
    public function get_value($key, $question_id, $mc_option_id) {
        $value = '';
        if(isset($_POST['enp_question'])) {
            $posted_value = $_POST['enp_question'];
            // find our question_id
            foreach($posted_value as $question) {
                // see if we matched our question_id
                if($question['question_id'] === $question_id) {
                    // check all the mc_options
                    foreach($question['mc_option'] as $mc_option) {
                        // match the $mc_option_id
                        if( $mc_option['mc_option_id'] === $mc_option_id ) {
                            // we've got the value!
                            $value = stripslashes($mc_option[$key]);
                        }
                    }

                }

            }

        }
        // if the value didn't get set, try with our object
        if($value === '') {
            $get_obj_value = 'get_'.$key;
            $obj_value = $this->$get_obj_value();
            if($obj_value !== null) {
                $value = $obj_value;
            }
        }
        // send them back whatever the value should be
        return $value;
    }*/
}
?>
