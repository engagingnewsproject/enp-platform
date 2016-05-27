<?
/**
* Create a question object
* @param $question_id = the id of the question you want to get
* @return question object
*/
class Enp_quiz_Question {
    public  $quiz_id,
            $question_id,
            $question_title,
            $question_image,
            $question_image_src,
            $question_image_srcset,
            $question_image_alt,
            $question_type,
            $question_explanation,
            $question_order,
            $question_views,
            $question_responses,
            $question_responses_correct,
            $question_responses_incorrect,
            $question_responses_correct_percentage,
            $question_responses_incorrect_percentage,
            $question_score_average,
            $question_time_spent,
            $question_time_spent_average,
            $mc_options = array(),
            $slider = '';

    protected static $question;


    public function __construct($question_id) {
        // returns false if no question found
        $this->get_question_by_id($question_id);
    }

    /**
    *   Build question object by id
    *
    *   @param  $question_id = question_id that you want to select
    *   @return question object, false if not found
    **/
    public function get_question_by_id($question_id) {
        self::$question = $this->select_question_by_id($question_id);
        if(self::$question !== false) {
            self::$question = $this->set_question_object_values();
        }
        return self::$question;
    }

    /**
    *   For using PDO to select one question row
    *
    *   @param  $question_id = question_id that you want to select
    *   @return row from database table if found, false if not found
    **/
    public function select_question_by_id($question_id) {
        $pdo = new enp_quiz_Db();
        // Do a select query to see if we get a returned row
        $params = array(
            ":question_id" => $question_id
        );
        $sql = "SELECT * from ".$pdo->question_table." WHERE
                question_id = :question_id
                AND question_is_deleted = 0";
        $stmt = $pdo->query($sql, $params);
        $question_row = $stmt->fetch();
        // return the found question row
        return $question_row;
    }

    /**
    * Hook up all the values for the object
    * @param $question = row from the question_table
    */
    protected function set_question_object_values() {
        $this->question_id = $this->set_question_id();
        $this->quiz_id = $this->set_quiz_id();
        $this->question_title = $this->set_question_title();
        $this->question_image = $this->set_question_image();
        $this->question_image_src = $this->set_question_image_src();
        $this->question_image_srcset = $this->set_question_image_srcset();
        $this->question_image_alt = $this->set_question_image_alt();
        $this->question_type = $this->set_question_type();
        $this->question_explanation = $this->set_question_explanation();
        $this->question_order = $this->set_question_order();
        // response/view data
        $this->question_views = $this->set_question_views();
        $this->question_responses = $this->set_question_responses();
        $this->question_responses_correct = $this->set_question_responses_correct();
        $this->question_responses_incorrect = $this->set_question_responses_incorrect();
        $this->question_responses_correct_percentage = $this->set_question_responses_correct_percentage();
        $this->question_responses_incorrect_percentage = $this->set_question_responses_incorrect_percentage();
        $this->question_score_average = $this->set_question_score_average();
        $this->question_time_spent = $this->set_question_time_spent();
        $this->question_time_spent_average = $this->set_question_time_spent_average();

        $this->question_is_deleted = $this->set_question_is_deleted();
        // we need to know both mc option ids and slider ids
        // when creating a quiz. We could limit this by adding a "published"
        // check on the question or quiz
        $this->mc_options = $this->set_mc_options();
        $this->slider = $this->set_slider();
    }

    /**
    * Set the question_id for our Question Object
    * @param $question = question row from question database table
    * @return question_id field from the database
    */
    protected function set_question_id() {
        $question_id = self::$question['question_id'];
        return $question_id;
    }

    /**
    * Set the quiz_id for our Question Object
    * @param $question = question row from question database table
    * @return quiz_id field from the database
    */
    protected function set_quiz_id() {
        $quiz_id = self::$question['quiz_id'];
        return $quiz_id;
    }

    /**
    * Set the question_title for our Quiz Object
    * @param $question = question row from question database table
    * @return question_title field from the database
    */
    protected function set_question_title() {
        $question_title = stripslashes(self::$question['question_title']);
        return $question_title;
    }

    /**
    * Set the question_image path for our Quiz Object
    * @param $question = question row from question database table
    * @return question_image path from the database
    */
    protected function set_question_image() {
        $question_image = self::$question['question_image'];

        return $question_image;
    }

    /**
    * Set the question_image for our Quiz Object
    * We want to set the url, but the question_image just sets the filename
    * we need to build it based on our ENP_QUIZ_IMAGE_URL, quiz_id and question_id
    * @param $question = question object
    * @return question_image from the object
    */
    public function set_question_image_src() {
        $question_image_src = '';
        $question_image = $this->question_image;
        if(!empty($question_image)) {
            $question_image_src = ENP_QUIZ_IMAGE_URL.$this->quiz_id.'/'.$this->question_id.'/'.$question_image;
        }
        return $question_image_src;
    }

    /**
    * Set the question_image for our Quiz Object
    * @param $question = question object
    * @return question_image from the object
    */
    public function set_question_image_srcset() {
        if(empty($this->question_image)) {
            return '';
        }
        // -original is our stored filename so we can explode by that
        // and generate our new filenames
        $filename = explode('-original', $this->question_image);
        $name = $filename[0];
        $ext = $filename[1];
        $sizes = array(1000,740,580,320,200);
        $srcset = '';
        foreach($sizes as $size) {
            $srcset .= ENP_QUIZ_IMAGE_URL.$this->quiz_id.'/'.$this->question_id.'/'.$name.$size.'w'.$ext.' '.($size+10).'w,';
        }
        // remove the trailing comma
        $srcset = rtrim($srcset, ",");
        return $srcset;
    }

    /**
    * Set the question_image_alt for our Quiz Object
    * @param $question = question row from question database table
    * @return question_image_alt field from the database
    */
    protected function set_question_image_alt() {
        $question_image_alt = stripslashes(self::$question['question_image_alt']);
        return $question_image_alt;
    }

    /**
    * Set the question_type for our Quiz Object
    * @param $question = question row from question database table
    * @return question_type field from the database
    */
    protected function set_question_type() {
        $question_type = stripslashes(self::$question['question_type']);
        return $question_type;
    }

    /**
    * Set the question_explanation for our Quiz Object
    * @param $question = question row from question database table
    * @return question_explanation field from the database
    */
    protected function set_question_explanation() {
        $question_explanation = stripslashes(self::$question['question_explanation']);
        return $question_explanation;
    }

    /**
    * Set the question_order for our Quiz Object
    * @param $question = question row from question database table
    * @return question_order field from the database
    */
    protected function set_question_order() {
        $question_order = self::$question['question_order'];
        return $question_order;
    }

    /**
    * Set the question_is_deleted for our Quiz Object
    * @param $question = question row from question database table
    * @return question_is_deleted field from the database
    */
    protected function set_question_is_deleted() {
        $question_is_deleted = self::$question['question_is_deleted'];
        return $question_is_deleted;
    }

    /**
    * Set the mc_options for our Questions Object
    * @param $quiz_id
    * @return mc_options array of ids array(3,4,5) from the database
    */
    protected function set_mc_options() {
        $question_id = self::$question['question_id'];

        $pdo = new enp_quiz_Db();
        // Do a select query to see if we get a returned row
        $params = array(
            ":question_id" => $question_id
        );
        $sql = "SELECT mc_option_id from ".$pdo->question_mc_option_table." WHERE
                question_id = :question_id
                AND mc_option_is_deleted = 0
                ORDER BY mc_option_order ASC";
        $stmt = $pdo->query($sql, $params);
        $mc_option_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $mc_options = array();

        foreach($mc_option_rows as $row => $mc_option) {

            $mc_options[] = (int) $mc_option['mc_option_id'];
        }
        return $mc_options;
    }

    /**
    * Set the slider for our Question Object
    * @param $quiz_id
    * @return slider id from the database
    */
    protected function set_slider() {
        $question_id = self::$question['question_id'];

        $pdo = new enp_quiz_Db();
        // Do a select query to see if we get a returned row
        $params = array(
            ":question_id" => $question_id
        );
        $sql = "SELECT slider_id from ".$pdo->question_slider_table." WHERE
                question_id = :question_id
                AND slider_is_deleted = 0";
        $stmt = $pdo->query($sql, $params);
        $slider_id = $stmt->fetch();
        return $slider_id['slider_id'];
    }

    /**
    * Set the question_views for our Quiz Object
    * @param $question = question row from question database table
    * @return question_views field from the database
    */
    protected function set_question_views() {
        $question_views = self::$question['question_views'];
        return $question_views;
    }

    /**
    * Set the question_responses for our Quiz Object
    * @param $question = question row from question database table
    * @return question_responses field from the database
    */
    protected function set_question_responses() {
        $question_responses = self::$question['question_responses'];
        return $question_responses;
    }

    /**
    * Set the question_responses_correct for our Quiz Object
    * @param $question = question row from question database table
    * @return question_responses_correct field from the database
    */
    protected function set_question_responses_correct() {
        $question_responses_correct = self::$question['question_responses_correct'];
        return $question_responses_correct;
    }

    /**
    * Set the question_responses_incorrect for our Quiz Object
    * @param $question = question row from question database table
    * @return question_responses_incorrect field from the database
    */
    protected function set_question_responses_incorrect() {
        $question_responses_incorrect = self::$question['question_responses_incorrect'];
        return $question_responses_incorrect;
    }

    /**
    * Set the question_responses_correct_percentage for our Quiz Object
    * @param $question = question row from question database table
    * @return question_responses_correct_percentage field from the database
    */
    protected function set_question_responses_correct_percentage() {
        $question_responses_correct_percentage = self::$question['question_responses_correct_percentage'];
        return $question_responses_correct_percentage;
    }

    /**
    * Set the question_responses_incorrect_percentage for our Quiz Object
    * @param $question = question row from question database table
    * @return question_responses_incorrect_percentage field from the database
    */
    protected function set_question_responses_incorrect_percentage() {
        $question_responses_incorrect_percentage = self::$question['question_responses_incorrect_percentage'];
        return $question_responses_incorrect_percentage;
    }

    /**
    * Set the question_score_average for our Quiz Object
    * @param $question = question row from question database table
    * @return question_score_average field from the database
    */
    protected function set_question_score_average() {
        $question_score_average = self::$question['question_score_average'];
        return $question_score_average;
    }

    /**
    * Set the question_time_spent for our Quiz Object
    * @param $question = question row from question database table
    * @return question_time_spent field from the database
    */
    protected function set_question_time_spent() {
        $question_time_spent = self::$question['question_time_spent'];
        return $question_time_spent;
    }

    /**
    * Set the question_time_spent_average for our Quiz Object
    * @param $question = question row from question database table
    * @return question_time_spent_average field from the database
    */
    protected function set_question_time_spent_average() {
        $question_time_spent_average = self::$question['question_time_spent_average'];
        return $question_time_spent_average;
    }

    /**
    * Get the question_id for our Quiz Object
    * @param $question = question object
    * @return question_id from the object
    */
    public function get_question_id() {
        $question_id = $this->question_id;
        return $question_id;
    }

    /**
    * Get the quiz_id for our Quiz Object
    * @param $question = question object
    * @return quiz_id from the object
    */
    public function get_quiz_id() {
        $quiz_id = $this->quiz_id;
        return $quiz_id;
    }

    /**
    * Get the question_title for our Quiz Object
    * @param $question = question object
    * @return question_title from the object
    */
    public function get_question_title() {
        $question_title = $this->question_title;
        return $question_title;
    }

    /**
    * Get the question_image for our Quiz Object
    * @param $question = question object
    * @return question_image from the object
    */
    public function get_question_image() {
        $question_image = $this->question_image;
        return $question_image;
    }

    /**
    * Get the question_image_src for our Quiz Object
    * @param $question = question object
    * @return question_image_src from the object
    */
    public function get_question_image_src() {
        $question_image_src = $this->question_image_src;
        return $question_image_src;
    }

    /**
    * Get the question_image_srcset for our Quiz Object
    * @param $question = question object
    * @return question_image_srcset from the object
    */
    public function get_question_image_srcset() {
        $question_image_srcset = $this->question_image_srcset;
        return $question_image_srcset;
    }

    public function get_question_image_thumbnail() {
        if(empty($this->question_image)) {
            return '';
        }
        $filename = explode('-original', $this->question_image);
        $name = $filename[0];
        $ext = $filename[1];
        $thumbnail = $name.'-w200'.$ext;
        return $thumbnail;
    }
    /**
    * Get the question_image_alt for our Quiz Object
    * @param $question = question object
    * @return question_image_alt from the object
    */
    public function get_question_image_alt() {
        $question_image_alt = $this->question_image_alt;
        return $question_image_alt;
    }

    /**
    * Get the question_type for our Quiz Object
    * @param $question = question object
    * @return question_type from the object
    */
    public function get_question_type() {
        $question_type = $this->question_type;
        return $question_type;
    }

    /**
    * Get the question_explanation for our Quiz Object
    * @param $question = question object
    * @return question_explanation from the object
    */
    public function get_question_explanation() {
        $question_explanation = $this->question_explanation;
        return $question_explanation;
    }

    /**
    * Get the question_order for our Quiz Object
    * @param $question = question object
    * @return question_order from the object
    */
    public function get_question_order() {
        $question_order = $this->question_order;
        return $question_order;
    }

    /**
    * Get the question_is_deleted for our Quiz Object
    * @param $question = question object
    * @return question_is_deleted from the object
    */
    public function get_question_is_deleted() {
        $question_is_deleted = $this->question_is_deleted;
        return $question_is_deleted;
    }

    /**
    * Get the mc_options for our Question Object
    * @param $question = question object
    * @return array of mc_option_id's as integers
    */
    public function get_mc_options() {
        $mc_options = $this->mc_options;
        return $mc_options;
    }

    /**
    * Get the slider_id for our Question Object
    * @param $question = question object
    * @return slider_id
    */
    public function get_slider() {
        $slider = $this->slider;
        return $slider;
    }

    /**
    * Get the question_views for our Question Object
    * @param $question = question object
    * @return int question_views
    */
    public function get_question_views() {
        $question_views = $this->question_views;
        return $question_views;
    }

    /**
    * Get the question_responses for our Question Object
    * @param $question = question object
    * @return int question_responses
    */
    public function get_question_responses() {
        $question_responses = $this->question_responses;
        return $question_responses;
    }

    /**
    * Get the question_responses_correct for our Question Object
    * @param $question = question object
    * @return int question_responses_correct
    */
    public function get_question_responses_correct() {
        $question_responses_correct = $this->question_responses_correct;
        return $question_responses_correct;
    }

    /**
    * Get the question_incorrect for our Question Object
    * @param $question = question object
    * @return int question_responses_incorrect
    */
    public function get_question_incorrect() {
        $question_incorrect = $this->question_incorrect;
        return $question_incorrect;
    }

    /**
    * Get the question_responses_correct_percentage for our Question Object
    * and format it as a percentage (43%);
    * @param $question = question object
    * @return string '44%';
    */
    public function get_question_responses_correct_percentage() {
        $question_responses_correct_percentage = round($this->question_responses_correct_percentage * 100 );
        return $question_responses_correct_percentage;
    }

    /**
    * Get the question_responses_incorrect_percentage for our Question Object
    * and format it as a percentage (43%);
    * @param $question = question object
    * @return string '44%';
    */
    public function get_question_responses_incorrect_percentage() {
        $question_responses_incorrect_percentage = round($this->question_responses_incorrect_percentage * 100 );
        return $question_responses_incorrect_percentage;
    }

    /**
    * Get the question_score_average for our Question Object
    * @param $question = question object
    * @return array of mc_option_id's as integers
    */
    public function get_question_score_average() {
        $question_score_average = $this->question_score_average;
        return $question_score_average;
    }

    /**
    * Get the question_time_spent for our Question Object
    * @param $question = question object
    * @return array of mc_option_id's as integers
    */
    public function get_question_time_spent() {
        $question_time_spent = $this->question_time_spent;
        return $question_time_spent;
    }

    /**
    * Get the question_views for our Question Object
    * @param $question = question object
    * @return array of mc_option_id's as integers
    */
    public function get_question_time_spent_average() {
        $question_time_spent_average = $this->question_time_spent_average;
        return $question_time_spent_average;
    }

    /**
    * Get the built question with all MC Option / Slider data for taking quizzes
    *
    * @param $question = question object
    * @return array with full question data for taking quizzes or converting to JSON
    */
    public function get_take_question_array() {
        // cast object to array
        $question_array = (array) $this;
        // remove what we don't need
        unset($question_array['quiz_id']);
        unset($question_array['mc_options']);
        // get question type
        $question_type = $this->get_question_type();
        // get question images info
        $question_array['question_image_src'] = $this->get_question_image_src();
        $question_array['question_image_srcset'] = $this->get_question_image_srcset();
        // if mc, get mc options
        if($question_type === 'mc') {
            // get the mc options
            $mc_option_ids = $this->get_mc_options();
            // create a mc_options_array
            $question_array['mc_option'] = array();
            // create the MC Options
            foreach($mc_option_ids as $mc_option_id) {
                // build mc option object
                $mc_option = new Enp_quiz_MC_option($mc_option_id);
                // cast object to array in question_array
                $mc_option_array = $mc_option->get_take_mc_option_array();
                $question_array['mc_option'][] = $mc_option_array;
            }
        } elseif($question_type === 'slider') {
            $slider_id = $this->get_slider();
            $slider = new Enp_quiz_Slider($slider_id);
            $question_array['slider'] = (array) $slider;
        }
        return $question_array;
    }

    public function get_take_question_json() {
        $question = $this->get_take_question_array();
        return json_encode($question);
    }

    /**
    * Get the value we should be saving on a question
    * get posted if present, if not, get object. This is so we give them their
    * current entry if we didn't *actually* save yet
    * (like if there was an error on save they won't lose all their work).
    * @param $string = what you want to get ('question_title', 'question_explanation', whatever)
    * @param $quetion_id = which question you're trying to get a value from
    * @return $value
    */
    /* I don't think we need this anymore. Questions always have an ID now
    public function get_value($key, $question_id) {
        $value = '';
        if(isset($_POST['enp_question'])) {
            $posted_value = $_POST['enp_question'];
            // find our question_id
            foreach($posted_value as $question) {
                // see if we matched our question_id
                if($question['question_id'] === $question_id) {
                    $value = stripslashes($question[$key]);
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
    }
    */
}
?>
