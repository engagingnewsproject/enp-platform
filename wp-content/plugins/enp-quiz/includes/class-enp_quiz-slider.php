<?
/**
* Create a slider object
* @param $slider_id = the id of the slider you want to get
* @return slider object
*/
class Enp_quiz_Slider {
    public  $slider_id,
            $slider_range_low,
            $slider_range_high,
            $slider_correct_low,
            $slider_correct_high,
            $slider_start,
            $slider_increment,
            $slider_prefix,
            $slider_suffix,
            $slider_input_size,
            $slider_is_deleted;

    public function __construct($slider_id) {
        // returns false if no slider found
        $this->get_slider_by_id($slider_id);
    }

    /**
    *   Build slider object by id
    *
    *   @param  $slider_id = slider_id that you want to select
    *   @return slider object, false if not found
    **/
    public function get_slider_by_id($slider_id) {
        $slider = $this->select_slider_by_id($slider_id);
        if($slider !== false) {
            $slider = $this->set_slider_object_values($slider);
        }
        return $slider;
    }

    /**
    *   For using PDO to select one slider row
    *
    *   @param  $slider_id = slider_id that you want to select
    *   @return row from database table if found, false if not found
    **/
    public function select_slider_by_id($slider_id) {
        $pdo = new enp_quiz_Db();
        // Do a select query to see if we get a returned row
        $params = array(
            ":slider_id" => $slider_id
        );
        $sql = "SELECT * from ".$pdo->question_slider_table." WHERE
                slider_id = :slider_id
                AND slider_is_deleted = 0";
        $stmt = $pdo->query($sql, $params);
        $slider_row = $stmt->fetch();
        // return the found slider row
        return $slider_row;
    }

    /**
    * Hook up all the values for the object
    * @param $slider = row from the slider_table
    */
    protected function set_slider_object_values($slider) {
        $this->slider_id = $this->set_slider_id($slider);
        $this->slider_range_low = $this->set_slider_range_low($slider);
        $this->slider_range_high = $this->set_slider_range_high($slider);
        $this->slider_correct_low = $this->set_slider_correct_low($slider);
        $this->slider_correct_high = $this->set_slider_correct_high($slider);
        $this->slider_increment = $this->set_slider_increment($slider);
        $this->slider_start = $this->set_slider_start();
        $this->slider_prefix = $this->set_slider_prefix($slider);
        $this->slider_suffix = $this->set_slider_suffix($slider);
        $this->slider_input_size = $this->set_slider_input_size();
        $this->slider_is_deleted = $this->set_slider_is_deleted($slider);
    }

    /**
    * Set the slider_id for our Question Object
    * @param $slider = slider row from slider database table
    * @return slider_id field from the database
    */
    protected function set_slider_id($slider) {
        return $slider['slider_id'];
    }

    /**
    * Set the slider_range_low for our Slider Object
    * @param $slider = slider row from slider database table
    * @return slider_range_low field from the database
    */
    protected function set_slider_range_low($slider) {
        $slider_range_low = (float) $slider['slider_range_low'];
        return $slider_range_low;
    }

    /**
    * Set the slider_range_high for our Slider Object
    * @param $slider = slider row from slider database table
    * @return slider_range_high field from the database
    */
    protected function set_slider_range_high($slider) {
        $slider_range_high = (float) $slider['slider_range_high'];
        return $slider_range_high;
    }

    /**
    * Set the slider_correct_low for our Slider Object
    * @param $slider = slider row from slider database table
    * @return slider_correct_low field from the database
    */
    protected function set_slider_correct_low($slider) {
        $slider_correct_low = (float) $slider['slider_correct_low'];
        return $slider_correct_low;
    }

    /**
    * Set the slider_correct_high for our Slider Object
    * @param $slider = slider row from slider database table
    * @return slider_correct_high field from the database
    */
    protected function set_slider_correct_high($slider) {
        $slider_correct_high = (float) $slider['slider_correct_high'];
        return $slider_correct_high;
    }

    /**
    * Set the slider_start for our Slider Object
    * @param $slider object
    * @return slider_start value
    */
    protected function set_slider_start() {
        $low = $this->slider_range_low;
        $high = $this->slider_range_high;
        $interval = $this->slider_increment;
        $total_intervals = ($high - $low) / $interval;
        $middle_interval = (($total_intervals/2)*$interval) + $low;
        $remainder = fmod($middle_interval, $interval); // floating point modulo
        $slider_start = $middle_interval - $remainder;
        return $slider_start;
    }

    /**
    * Set the slider_increment for our Slider Object
    * @param $slider = slider row from slider database table
    * @return slider_increment field from the database
    */
    protected function set_slider_increment($slider) {
        $slider_increment = (float) $slider['slider_increment'];
        return $slider_increment;
    }

    /**
    * Set the slider_prefix for our Slider Object
    * @param $slider = slider row from slider database table
    * @return slider_prefix field from the database
    */
    protected function set_slider_prefix($slider) {
        return $slider['slider_prefix'];
    }

    /**
    * Set the slider_suffix for our Slider Object
    * @param $slider = slider row from slider database table
    * @return slider_suffix field from the database
    */
    protected function set_slider_suffix($slider) {
        return $slider['slider_suffix'];
    }

    /**
    * Set the slider_input_size for our Slider Object
    * @param $slider = slider row from slider database table
    * @return slider_input_size field from length of range high
    */
    protected function set_slider_input_size() {
        return strlen($this->slider_range_high);
    }

    /**
    * Set the slider_is_deleted for our Slider Object
    * @param $slider = slider row from slider database table
    * @return slider_order field from the database
    */
    protected function set_slider_is_deleted($slider) {
        return $slider['slider_is_deleted'];
    }

    /**
    * Get the slider_id for our Slider Object
    * @param $slider = slider object
    * @return slider_id from the object
    */
    public function get_slider_id() {
        return $this->slider_id;
    }

    /**
    * Get the slider_range_low for our Slider Object
    * @param $slider = slider object
    * @return slider_range_low from the object
    */
    public function get_slider_range_low() {
        return $this->slider_range_low;
    }

    /**
    * Get the slider_range_high for our Slider Object
    * @param $slider = slider object
    * @return slider_range_high from the object
    */
    public function get_slider_range_high() {
        return $this->slider_range_high;
    }

    /**
    * Get the slider_correct_low for our Slider Object
    * @param $slider = slider object
    * @return slider_correct_low from the object
    */
    public function get_slider_correct_low() {
        return $this->slider_correct_low;
    }

    /**
    * Get the slider_correct_high for our Slider Object
    * @param $slider = slider object
    * @return slider_correct_high from the object
    */
    public function get_slider_correct_high() {
        return $this->slider_correct_high;
    }

    /**
    * Get the slider_increment for our Slider Object
    * @param $slider = slider object
    * @return slider_increment from the object
    */
    public function get_slider_increment() {
        return $this->slider_increment;
    }

    /**
    * Get the slider_prefix for our Slider Object
    * @param $slider = slider object
    * @return slider_prefix from the object
    */
    public function get_slider_prefix() {
        return $this->slider_prefix;
    }

    /**
    * Get the slider_suffix for our Slider Object
    * @param $slider = slider object
    * @return slider_suffix from the object
    */
    public function get_slider_suffix() {
        return $this->slider_suffix;
    }

    /**
    * Get the slider_input_size for our Slider Object
    * @param $slider = slider object
    * @return slider_input_size from the object
    */
    public function get_slider_input_size() {
        return $this->slider_input_size;
    }

    /**
    * Get the slider_start for our Slider Object
    * @param $slider = slider object
    * @return slider_start from the object
    */
    public function get_slider_start() {
        return $this->slider_start;
    }

    /**
    * Get the slider_responses for our Slider Object
    * @param $slider = slider object
    * @return slider_responses from the object
    */
    public function get_slider_responses() {
        return $this->slider_responses;
    }

    /**
    * Get the slider_is_deleted for our Slider Object
    * @param $slider = slider object
    * @return slider_is_deleted from the object
    */
    public function get_slider_is_deleted() {
        return $this->slider_is_deleted;
    }

    /**
    * Get the built question with all MC Option data for taking a quiz
    *
    * @param $slider = slider object
    * @return array with full slider data for taking quizzes or converting to JSON
    */
    public function get_take_slider_array() {
        // for now, just cast object to array. Later we may need to process this more
        return (array) $this;
    }

    /*
    * Evaluate if a number is correct, low, high, or invalid
    * @param $selected = number you want to evaluate
    * @return (string) 'correct' = $selected is correct
    *                  'low' = $selected number is under the correct range
    *                  'high' = $selected number is above the correct range
    *                  'invalid' = $selected number is outside of the slider range
    *
    */

    public function check_slider_answer($selected) {
            $selected = (float) $selected;
            $slider_range_low = $this->get_slider_range_low();
            $slider_range_high = $this->get_slider_range_high();
            $slider_correct_low = $this->get_slider_correct_low();
            $slider_correct_high = $this->get_slider_correct_high();


            // check if it's correct
            if($slider_correct_low <= $selected && $selected <= $slider_correct_high) {
                $check = 'correct';
            }
            // check if it's low
            elseif($selected < $slider_correct_low && $slider_range_low <= $selected) {
                $check = 'low';
            }
            // check if it's high
            elseif($slider_correct_high < $selected && $selected <= $slider_range_high) {
                $check = 'high';
            }
            // it's outside the allowed slider range
            else {
                $check = 'invalid';
            }

            return $check;

    }

}
?>
