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
class Enp_quiz_Save_slider extends Enp_quiz_Save_question {
    protected static $slider;

    public function __construct() {

    }

    /**
    * Reformat and set values for a submitted slider
    *
    * @param $slider = array() in this format:
    *    $slider = array(
    *            'slider_id' => $slider['slider_id'],
    *            'slider_range_high' =>$slider['slider_range_high'],
    *            'slider_correct' =>  $slider['slider_correct'],
    *            'slider_increment' => $slider['slider_increment'],
    *        );
    * @return nicely formatted and value validated slider array ready for saving
    */
    protected function prepare_submitted_slider($slider) {
        self::$slider = $slider;
        // set the defaults/get the submitted values
        self::$slider['slider_id'] = $this->set_slider_value('slider_id', 0);
        self::$slider['slider_prefix'] = $this->set_slider_value('slider_prefix', '');
        self::$slider['slider_suffix'] = $this->set_slider_value('slider_suffix', '');

        // add in the increment
        $this->set_slider_increment();
        // add in the low and high range.
        $this->set_slider_range();
        // add in the correct low and high range.
        $this->set_slider_correct();

        return self::$slider;
    }

    /**
    * Check to see if a value was passed in  parent::$quiz['question'][$question_i]['slider'] array
    * If it was, set it as the value. If it wasn't, set the value
    * from the $slider_obj we'll create
    *
    * @param $key = key that should be set in the quiz['question'] array.
    * @param $default = int or string of default value if nothing is found
    * @return value from either parent::$quiz['question'][$question_i]['slider'][$slider_i] or $slider_obj->get_slider_$key()
    */
    protected function set_slider_value($key, $default) {
        $param_value = $default;
        // see if the value is already in our submitted quiz
        if(array_key_exists($key, self::$slider) && self::$slider[$key] !== "") {
            $param_value = self::$slider[$key];
        }

        return $param_value;
    }

    /**
    * Set increment for the slider
    *
    * @param self::$slider
    */
    protected function set_slider_increment() {
        $increment = (float) 1;
        // slider increment can be anything other than 0
        if(array_key_exists('slider_increment', self::$slider)) {
            $increment = (float) self::$slider['slider_increment'];
        }

        if(empty($increment)) {
            // empty should catch a (float) 0
            $increment = (float) 1;
        }

        self::$slider['slider_increment'] = $increment;
    }
    /**
    * Set correct_low and correct_high for the slider
    *
    * @param self::$slider
    */
    protected function set_slider_range() {
        // If they entered a higher value in the slider_range_high value, don't throw an error trying to explain that they entered a higher value in the low one, just switch it for them
        // we're setting these as an agnostic range right now. We'll set the actual values later
        $a = $this->set_slider_value('slider_range_low', 0);
        $b = $this->set_slider_value('slider_range_high', 10);
        $a = (float) $a;
        $b = (float) $b;

        // see if they don't have a range_a value, set is as the range_b value
        if(empty($a) && $a !== (float) 0 ) {
            $a = $b - 1;
        }
        // see if they don't have a range_b value, set is as the range_a value
        if(empty($b) && $b !== (float) 0) {
            $b = $a + 1;
        }

        self::$slider['slider_range_low'] = $this->set_low_value($a, $b);
        self::$slider['slider_range_high'] = $this->set_high_value($a, $b);
    }

    /**
    * Set correct_low and correct_high for the slider
    *
    * @param self::$slider
    */
    protected function set_slider_correct() {
        // If they entered a higher value in the slider_correct_high value, don't throw an error trying to explain that they entered a higher value in the low one, just switch it for them
        // we're setting these as an agnostic range right now. We'll set the actual values later
        $a = $this->set_slider_value('slider_correct_low', '5');
        $b = $this->set_slider_value('slider_correct_high', '5');
        $a = (float) $a;
        $b = (float) $b;



        // see if they don't have a correct_a value, set is as the correct_b value
        if(empty($a) && $a !== (float) 0 ) {
            $a = $b;
        }
        // see if they don't have a correct_b value, set is as the correct_a value
        if(empty($b) && $b !== (float) 0 ) {
            $b = $a;
        }

        self::$slider['slider_correct_low'] = $this->set_low_value($a, $b);
        self::$slider['slider_correct_high'] = $this->set_high_value($a, $b);

    }


    /**
     * Save a slider array in the database
     * Often used in a foreach loop to loop over all sliders
     * If ID is passed, it will update that ID.
     * If no ID or ID = 0, it will insert
     *
     * @param    $slider = array(); of slider data
     * @return   ID of saved slider or false if error
     * @since    0.0.1
     */
    protected function save_slider($slider) {
        self::$slider = $slider;
        // check to see if the id exists
        if(self::$slider['slider_id'] === 0) {
            // It doesn't exist yet, so insert it!
            $this->insert_slider();
        } else {
            // we have a slider_id, so update it!
            $this->update_slider();
        }
    }


    /**
    * Connects to DB and inserts the slider.
    * @param $slider = formatted slider array
    * @param $question_id = which quiz this slider goes with
    * @return builds and returns a response message
    */
    protected function insert_slider() {
        // connect to PDO
        $pdo = new enp_quiz_Db();
        // Get our Parameters ready
        $params = array(':question_id'      => parent::$question['question_id'],
                        ':slider_range_high'=> self::$slider['slider_range_high'],
                        ':slider_range_low'=> self::$slider['slider_range_low'],
                        ':slider_correct_high'=> self::$slider['slider_correct_high'],
                        ':slider_correct_low'=> self::$slider['slider_correct_low'],
                        ':slider_increment'  => self::$slider['slider_increment'],
                        ':slider_prefix'=> self::$slider['slider_prefix'],
                        ':slider_suffix'=> self::$slider['slider_suffix'],
                    );
        // write our SQL statement
        $sql = "INSERT INTO ".$pdo->question_slider_table." (
                                            question_id,
                                            slider_range_low,
                                            slider_range_high,
                                            slider_correct_low,
                                            slider_correct_high,
                                            slider_increment,
                                            slider_prefix,
                                            slider_suffix
                                        )
                                        VALUES(
                                            :question_id,
                                            :slider_range_low,
                                            :slider_range_high,
                                            :slider_correct_low,
                                            :slider_correct_high,
                                            :slider_increment,
                                            :slider_prefix,
                                            :slider_suffix
                                        )";
        // insert the slider into the database
        $stmt = $pdo->query($sql, $params);

        // success!
        if($stmt !== false) {
            // set-up our response array
            $slider_response = array(
                                        'slider_id' => $pdo->lastInsertId(),
                                        'status'       => 'success',
                                        'action'       => 'insert'
                                );
            $slider_response = array_merge(self::$slider, $slider_response);
            // pass the response array to our response object
            parent::$response_obj->set_slider_response($slider_response, parent::$question);

            // see if we we're adding a slider in here...
            if(self::$user_action_action === 'add' && self::$user_action_element === 'slider') {
                // we added a slider successfully, let them know!
                parent::$response_obj->add_success('Slider added to Question #'.(parent::$question['question_order']+1).'.');
            }
        } else {
            parent::$response_obj->add_error('Question #'.(parent::$question['question_order']+1).' could not add a Slider.');
        }
    }

    /**
    * Connects to DB and updates the question.
    * @param $question = formatted question array
    * @param $question_id = which quiz this question goes with
    * @return builds and returns a response message
    */
    protected function update_slider() {
        // connect to PDO
        $pdo = new enp_quiz_Db();
        // Get our Parameters ready
        $params = array(':slider_id'     => self::$slider['slider_id'],
                        ':slider_range_low'=> self::$slider['slider_range_low'],
                        ':slider_range_high'=> self::$slider['slider_range_high'],
                        ':slider_correct_low'=> self::$slider['slider_correct_low'],
                        ':slider_correct_high'=> self::$slider['slider_correct_high'],
                        ':slider_increment'  => self::$slider['slider_increment'],
                        ':slider_prefix'  => self::$slider['slider_prefix'],
                        ':slider_suffix'  => self::$slider['slider_suffix']
                    );
        // write our SQL statement
        $sql = "UPDATE ".$pdo->question_slider_table."
                   SET  slider_range_low = :slider_range_low,
                        slider_range_high = :slider_range_high,
                        slider_correct_low = :slider_correct_low,
                        slider_correct_high = :slider_correct_high,
                        slider_increment = :slider_increment,
                        slider_prefix = :slider_prefix,
                        slider_suffix = :slider_suffix

                 WHERE  slider_id = :slider_id";
        // update the slider in the database
        $stmt = $pdo->query($sql, $params);

        // success!
        if($stmt !== false) {

            // set-up our response array
            $slider_response = array(
                                        'slider_id' => self::$slider['slider_id'],
                                        'status'       => 'success',
                                        'action'       => 'update'
                                );
            $slider_response = array_merge(self::$slider, $slider_response);
            // pass the response array to our response object
            parent::$response_obj->set_slider_response($slider_response, parent::$question);

        } else {
            // add an error that we couldn't update the slider
            parent::$response_obj->add_error('Question #'.(parent::$question['question_order']+1).' could not update the Slider. Please try again and contact support if you continue to see this error message.');
        }
    }

}
