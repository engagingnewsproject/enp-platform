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
class Enp_quiz_Save_question extends Enp_quiz_Save_quiz {
    protected static $question;
    // building responses
    // parent::$response_obj->add_error('Wow. Such errors.');

    public function __construct() {

    }

    /**
    * Reformat and set values for a submitted question
    *
    * @param $question = array() in this format:
    *    $question = array(
    *            'question_id' => $question['question_id'],
    *            'question_title' =>$question['question_title'],
    *            'question_type' =>$question['question_type'],
    *            'question_explanation' =>  $question['question_explanation'],
    *            'question_order' => $question['question_order'],
    *        );
    * @return nicely formatted and value validated question array
    *         ready to get passed on to mc_option or slider validation
    */
    protected function prepare_submitted_question($question) {
        self::$question = $question;
        // set the defaults/get the submitted values
        $question_id = $this->set_question_value('question_id', 0);
        $question_title = $this->set_question_value('question_title', '');
        $question_image_alt = $this->set_question_value('question_image_alt', '');
        $question_type = $this->set_question_value('question_type', 'mc');
        $question_explanation = $this->set_question_value('question_explanation', '');
        $question_order = $question['question_order'];
        $question_is_deleted = $question['question_is_deleted'];
        // build our new array
        $prepared_question = array(
                                'question_id' => $question_id,
                                'question_title' => $question_title,
                                'question_image_alt' => $question_image_alt,
                                'question_type' => $question_type,
                                'question_explanation' => $question_explanation,
                                'question_order' => $question_order,
                                'question_is_deleted' => $question_is_deleted,
                            );

        self::$question = array_merge(self::$question, $prepared_question);
        // set the image
        self::$question['question_image'] = $this->set_question_image();

        // we need to preprocess_mc_options and preprocess_slider to make sure each question has at least a slider array and mc_option array
        $this->preprocess_mc_options();
        $this->preprocess_slider();
        // merge the prepared question array so we don't lose our mc_option or slider values

        // check what the question type is and set the values accordingly
        if(self::$question['question_type'] === 'mc') {
            // we have a mc question, so prepare the values
            // and set it as the mc_option array
            self::$question['mc_option'] = $this->prepare_submitted_mc_options(self::$question['mc_option']);

            // check if the user action is to add a question. If so, add a slider in too
            // we only need to check here, because the default is MC option
            if(parent::$user_action_action === 'add' && parent::$user_action_element === 'question') {
                self::$question['slider'] = $this->prepare_submitted_slider(self::$question['slider']);
            }
        } elseif(self::$question['question_type'] === 'slider') {
            self::$question['slider'] = $this->prepare_submitted_slider(self::$question['slider']);
        }

        return self::$question;
    }

    /**
    * Sets our question image, uploads an image, or deletes it
    */
    protected function set_question_image() {
        // set our default
        $question_image = $this->set_question_value('question_image', '');
        // see if the user is trying to delete the image
        if(parent::$user_action_action === 'delete' && parent::$user_action_element === 'question_image') {
            // see if it matches this question
            if(parent::$user_action_details['question_id'] === (int)self::$question['question_id']) {
                // they want to delete this image. I wonder what was so bad about it?
                $question_image = '';
                parent::$response_obj->add_success('Image deleted for Question #'.(self::$question['question_order']+1).'.');
            }
        }

        // process images if necessary
        // See if there's an image trying to be uploaded for this question
        if(!empty($_FILES)) {
            // This is the name="" field for that question image in the form
            $question_image_file = 'question_image_upload_'.self::$question['question_id'];
            // some question has a file submitted, let's see if it's this one
            // check for size being set and that the size is greater than 0
            if( isset($_FILES[$question_image_file]["size"]) && $_FILES[$question_image_file]["size"] > 0 ) {
                // we have a new image to upload!
                // upload it
                $new_question_image = $this->upload_question_image($question_image_file);
                // see if it worked
                if($new_question_image !== false) {
                    // if it worked, set it as the question_image
                    $question_image = $new_question_image;
                }
            }
        }

        return $question_image;
    }

    /*
    * Uploads an image to
    * @param $question_image_file (string) name of "name" field in HTML form for that question
    * @return (string) filename of image uploaded to save to DB
    */
    protected function upload_question_image($question_image_file) {
        $new_image_name = false;
        $image_upload = wp_upload_bits( $_FILES[$question_image_file]['name'], null, @file_get_contents( $_FILES[$question_image_file]['tmp_name'] ) );
        // check to make sure there are no errors
        if($image_upload['error'] === false) {
            // success! set the image
            // set the URL to the image as our question_image
            // create / delete our directory for these images
            $this->prepare_quiz_image_dir();
            $path = $this->prepare_question_image_dir();

            // now upload all the resized images we'll need
            $new_image_name = $this->resize_image($image_upload, $path, null);
            // we have the full path, but we just need the filename
            $new_image_name = str_replace(ENP_QUIZ_IMAGE_DIR . parent::$quiz['quiz_id'].'/'.self::$question['question_id'].'/', '', $new_image_name);
            // resize all the other images
            $this->resize_image($image_upload, $path, 1000);
            $this->resize_image($image_upload, $path, 740);
            $this->resize_image($image_upload, $path, 580);
            $this->resize_image($image_upload, $path, 320);
            $this->resize_image($image_upload, $path, 200);

            // delete the image we initially uploaded from the wp-content dir
            $this->delete_file($image_upload['file']);

            // add a success message
            parent::$response_obj->add_success('Image uploaded for Question #'.(self::$question['question_order']+1).'.');
        } else {
            // add an error message
            parent::$response_obj->add_error('Image upload failed for Question #'.(self::$question['question_order']+1).'.');
        }

        return $new_image_name;
    }

    /**
    * Resizes images using wp_get_image_editor
    * and appends the width to the filename
    * @param $question_image_upload (string) path to image
    * @param $path (string) path to upload image to
    * @param $width (int) maxwidth of image to resize it to
    * @return path to saved resized image
    */
    protected function resize_image($question_image_upload, $path, $width) {
		// Resize the image to fit the single goal's page dimensions
		$image = wp_get_image_editor( $question_image_upload['file']);
        if ( ! is_wp_error( $image ) ) {
            if($width !== null && is_int($width)) {
                // make our height max out at 4x6 aspect ratio so we don't have a HUUUUGEly tall image
                $height = $width * 1.666667;
                $extension = $width.'w';
                // Get the actual filename (rather than the directory + filename)
                $image->resize( $width, $height, false );

            } else {
                $extension = '-original';
            }

            $filename = $image->generate_filename( $extension, $path, NULL );
            $saved_image = $image->save($filename);
            return $saved_image['path'];
        }

        return false;
    }

    /**
    * Check for mc_option array and append it if it's missing
    * because every question needs to have a mc_option and slider row with it
    */
    protected function preprocess_mc_options() {
        // if it doesn't exist, create an empty array of arrays so the
        // mc_option save prepare function gets run
        if(!array_key_exists('mc_option', self::$question)) {
            self::$question['mc_option'] = array(
                                        array(),
                                    );
        }
        // append array if adding an option
        if(parent::$user_action_action === 'add' && parent::$user_action_element === 'mc_option') {
            // find out which question we need to append an option to
            $question_id = parent::$user_action_details['question_id'];
            // if the question we want to add a mc_option to is THIS question,
            // append an extra mc_option array
            if($question_id === (int)self::$question['question_id']) {
                // add a new empty mc_option array to the end of the array
                // so our foreach loop will run one extra time when saving this question
                self::$question['mc_option'][] = array();
            }

        }
        return self::$question;
    }

    /**
    * Check for mc_option array and append it if it's missing
    * because every question needs to have a mc_option and slider row with it
    */
    protected function preprocess_slider() {
        if(!array_key_exists('slider', self::$question)) {
            self::$question['slider'] = array();
        }
        return self::$question;
    }

    /**
    * Reformat and set values for all submitted question mc_option
    *
    * @param $mc_option = array() in this format:
    *        $mc_option = array(
    *                        array(
    *                            'mc_option_id' => $question[$i]['mc_option'][$i]['mc_option_id'],
    *                            'mc_option_content' =>$question[$i]['mc_option'][$i]['mc_option_content'],
    *                            'mc_option_correct' =>  $question[$i]['mc_option'][$i]['mc_option_correct'],
    *                            'mc_option_order' => $mc_option_i,
    *                        ),
    *                    );
    * @return nicely formatted and value validated mc_option array ready for saving
    */
    protected function prepare_submitted_mc_options($mc_options) {
        // set our counter
        $i = 0;
        // open a new array
        $prepared_mc_options = array();
        // loop through all submitted $mc_options
        foreach($mc_options as $mc_option) {
            // add in our new mc_option_order value
            $mc_option['mc_option_order'] = $i;
            // create the object
            $mc_obj = new Enp_quiz_Save_mc_option();
            // prepare the values
            $mc_option = $mc_obj->prepare_submitted_mc_option($mc_option);
            // set the nicely formatted returned $mc_option
            $prepared_mc_options[$i] = $mc_option;
            // increase our counter and do it again!
            $i++;
        }
        // Return our nicely prepared_mc_options array
        return $prepared_mc_options;
    }

    /**
    * Reformat and set values for all submitted question slider
    *
    * @param $slider = array() in this format:
    *        $slider = array(
*                            'slider_id' => $question[$i]['slider']['slider_id'],
*                            'slider_range_low' =>$question[$i]['slider']['slider_range_low'],
*                            'slider_range_high' =>  $question[$i]['slider']['slider_range_high'],
*                           ...
    *                    );
    * @return nicely formatted and value validated mc_option array ready for saving
    */
    protected function prepare_submitted_slider($slider) {
        // create the object
        $slider_obj = new Enp_quiz_Save_slider();
        // Return our nicely prepared slider array
        return $slider_obj->prepare_submitted_slider($slider);
    }

    /**
    * Check to see if a value was passed in parent::$quiz['question'] array
    * If it was, set it as the value. If it wasn't, set the value
    * from parent::$quiz_obj
    *
    * @param $key = key that should be set in the quiz['question'] array.
    * @param $default = int or string of default value if nothing is found
    * @return value from either parent::$quiz['question'][$question_number][$key] or parent::$quiz_obj->get_question_$key()
    */
    protected function set_question_value($key, $default) {
        $param_value = $default;
        // see if the value is already in our submitted quiz
        if(array_key_exists($key, self::$question) && self::$question[$key] !== "") {
            $param_value = self::$question[$key];
        } else {
            // if we set it from the object, then we can't delete values...
            // hmm...
            // check to see if there's even a question_id to try to get
            /*if(array_key_exists('question_id', self::$question) &&  self::$question['question_id'] !== 0) {
                // if it's not in our submited quiz, try to get it from the object
                // dynamically create the quiz getter function
                $question_obj = new Enp_quiz_Question(self::$question['question_id']);
                $get_obj_value = 'get_'.$key;
                // get the quiz object value
                $obj_value = $question_obj->$get_obj_value();
                // if the object value isn't null, then we have a value to set
                if($obj_value !== null) {
                    $param_value = $obj_value;
                }
            }*/
        }

        return $param_value;
    }


    /**
     * Save a question array in the database
     * Often used in a foreach loop to loop over all questions
     * If ID is passed, it will update that ID.
     * If no ID or ID = 0, it will insert
     *
     * @param    $question = array(); of question data
     * @return   ID of saved question or false if error
     * @since    0.0.1
     */
    protected function save_question($question) {
        // set the question array
        self::$question = $question;

        // check to see if the id exists
        if(self::$question['question_id'] === 0) {
            // It doesn't exist yet, so insert it!
            $this->insert_question();
        } else {
            // we have a question_id, so update it!
            $this->update_question();
        }
    }

    /**
    * Connects to DB and inserts the question.
    * @param $question = formatted question array
    * @param $quiz_id = which quiz this question goes with
    * @return builds and returns a response message
    */
    protected function insert_question() {
        // connect to PDO
        $pdo = new enp_quiz_Db();
        // Get our Parameters ready
        $params = array(':quiz_id'          => parent::$quiz['quiz_id'],
                        ':question_title'   => self::$question['question_title'],
                        ':question_image'   => self::$question['question_image'],
                        ':question_image_alt'   => self::$question['question_image_alt'],
                        ':question_type'    => self::$question['question_type'],
                        ':question_explanation' => self::$question['question_explanation'],
                        ':question_order'   => self::$question['question_order'],
                    );
        // write our SQL statement
        $sql = "INSERT INTO ".$pdo->question_table." (
                                            quiz_id,
                                            question_title,
                                            question_image,
                                            question_image_alt,
                                            question_type,
                                            question_explanation,
                                            question_order
                                        )
                                        VALUES(
                                            :quiz_id,
                                            :question_title,
                                            :question_image,
                                            :question_image_alt,
                                            :question_type,
                                            :question_explanation,
                                            :question_order
                                        )";
        // insert the question into the database
        $stmt = $pdo->query($sql, $params);

        // success!
        if($stmt !== false) {
            self::$question['question_id'] = $pdo->lastInsertId();
            // set-up our response array
            $question_response = array(
                                        'status'       => 'success',
                                        'action'       => 'insert',
                                );
            $question_response = array_merge($this->build_question_response(), $question_response);
            // pass the response array to our response object
            parent::$response_obj->set_question_response($question_response, self::$question);

            // SUCCESS MESSAGES
            // see if we we're adding a mc_option in here...
            if(self::$user_action_action === 'add' && self::$user_action_element === 'question') {
                // we added a mc_option successfully, let them know!
                parent::$response_obj->add_success('Question added.');
            }

            // pass the question on to save_mc_option or save_slider
            // add the question_id to the questions array
            $this->save_question_type_options();

        } else {
            parent::$response_obj->add_error('Question number '.$question['question_order'].' could not be added to the database. Try again and if it continues to not work, send us an email with details of how you got to this error.');
        }
    }

    /**
    * Connects to DB and updates the question.
    * @param $question = formatted question array
    * @param $quiz_id = which quiz this question goes with
    * @return builds and returns a response message
    */
    protected function update_question() {
        // connect to PDO
        $pdo = new enp_quiz_Db();
        // Get our Parameters ready
        $params = array(':question_id'      => self::$question['question_id'],
                        ':question_title'   => self::$question['question_title'],
                        ':question_image'   => self::$question['question_image'],
                        ':question_image_alt'   => self::$question['question_image_alt'],
                        ':question_type'    => self::$question['question_type'],
                        ':question_explanation' => self::$question['question_explanation'],
                        ':question_order'   => self::$question['question_order'],
                        ':question_is_deleted'   => self::$question['question_is_deleted']
                    );
        // write our SQL statement
        $sql = "UPDATE ".$pdo->question_table."
                   SET  question_title = :question_title,
                        question_image = :question_image,
                        question_image_alt = :question_image_alt,
                        question_type = :question_type,
                        question_explanation = :question_explanation,
                        question_order = :question_order,
                        question_is_deleted = :question_is_deleted

                 WHERE  question_id = :question_id";
        // update the question in the database
        $stmt = $pdo->query($sql, $params);

        // success!
        if($stmt !== false) {
            // set-up our response array
            $question_response = array(
                                        'status'       => 'success',
                                        'action'       => 'update'
                                );
            $question_response = array_merge($this->build_question_response(), $question_response);
            // pass the response array to our response object
            parent::$response_obj->set_question_response($question_response, self::$question);
            // see if we were deleting a question in here...
            if(self::$question['question_is_deleted'] === 1) {
                // we deleted a question successfully. Let's let them know!
                parent::$response_obj->add_success('Question deleted.');
            }

            // pass the question on to save_mc_option or save_slider
            $this->save_question_type_options();

        } else {
            parent::$response_obj->add_error('Question number '.self::$question['question_order'].' could not be updated.');
        }
    }

    protected function build_question_response() {
        // build all the values for the question and return them in the JSON array
        return array(
                'question_id' => self::$question['question_id'],
                'question_title' => self::$question['question_title'],
                'question_image' => self::$question['question_image'],
                'question_image_alt' => self::$question['question_image_alt'],
                'question_type' => self::$question['question_type'],
                'question_explanation' => self::$question['question_explanation'],
                'question_order' => self::$question['question_order']
            );
    }

    /**
     * Chooses which question type we need to save, and passes it
     * to the correct function to save it
     * Not a great function name though...
     *
     * @param    $question = array of the question data
     */
    protected function save_question_type_options() {
        $question_type = self::$question['question_type'];
        // now try to save the mc_option or slider
        if($question_type === 'mc') {
            // pass the mc_option array for saving
            $this->save_mc_options(self::$question['mc_option']);
        }
        // if it's a slider, or if we're adding a question, we need to save a slider too
        if( $question_type === 'slider' || (parent::$user_action_action === 'add' && parent::$user_action_element === 'question') ) {
            //TODO: create slider save
            $this->save_slider(self::$question['slider']);
        }
    }

    /**
     * Loop through a $question['mc_option'] array to save to the db
     *
     * @param    $mc_option = array(); of all question['mc_option'] data
     * @since    0.0.1
     */
    protected function save_mc_options($mc_options) {
        if(!empty($mc_options)){
            // loop through the questions and save each one
            foreach($mc_options as $mc_option) {
                // create a new object
                $mc_option_obj = new Enp_quiz_Save_mc_option();
                // pass to save_mc_options so we can decide
                // if we should insert or update the mc_option
                $mc_option_obj->save_mc_option($mc_option);
            }
        }
    }

    /**
     * Save a slider array in the database
     * If ID is passed, it will update that ID.
     * If no ID or ID = 0, it will insert
     *
     * @param    $slider = array(); of slider data
     * @return   ID of saved slider or false if error
     * @since    0.0.1
     */
    protected function save_slider($slider) {
        // create a new object
        $slider_obj = new Enp_quiz_Save_slider();
        // pass to save_sliders so we can decide
        // if we should insert or update the slider
        $slider_obj->save_slider($slider);
    }

    /*
    * Creates a new directory for the quiz images, if necessary
    */
    protected function prepare_quiz_image_dir() {
        $path = ENP_QUIZ_IMAGE_DIR . parent::$quiz['quiz_id'].'/';
        $path = $this->build_dir($path);
        return $path;
    }

    /*
    * Creates a new directory for the question images, if necessary
    * and DELETES all files in the directory if there are any
    */
    protected function prepare_question_image_dir() {
        $path = ENP_QUIZ_IMAGE_DIR . parent::$quiz['quiz_id'].'/'.self::$question['question_id'].'/';
        $path = $this->build_dir($path);
        $this->delete_files($path);

        // check if directory exists
        // check to see if our image question upload directory exists
        return $path;
    }

    /**
    * Creates a new directory if it doesn't exist
    */
    private function build_dir($path) {
        if (!file_exists($path)) {
            // if it doesn't exist, create it
            mkdir($path, 0777, true);
        }
        return $path;
    }

    /**
    * Deletes files in a directory, restricted to ENP_QUIZ_IMG_DIR
    */
    private function delete_files($path) {
        if(strpos($path,  ENP_QUIZ_IMAGE_DIR) === false) {
            // uh oh... someone is misusing this
            return false;
        }
        if (file_exists($path)) {
            // delete all the images in it
            $files = glob($path.'*'); // get all file names
            foreach($files as $file){ // iterate files
              $this->delete_file($file);
            }
        }
    }

    /**
    * Deletes a file by path
    */
    private function delete_file($file) {
        if(is_file($file)) {
          unlink($file); // delete file
        }
    }

}
