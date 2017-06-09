<?php
/**
 * Save a quiz embedded on a site
 *
 * @link       http://engagingnewsproject.org
 * @since      1.1.0
 *
 * @package    Enp_quiz
 * @subpackage Enp_quiz/database
 * @author     Engaging News Project <jones.jeremydavid@gmail.com>
 */

class Enp_quiz_Save_embed_quiz extends Enp_quiz_Save {
    public  $embed_quiz = false, // object
            $response = array(
                              'error'=>array()
                             );

    public function __construct() {

    }

    /**
    * Kick off the save process for the enp_embed_quiz table
    *
    * @param $embed_quiz (ARRAY) of values depending on what you want to save
    *                   see the actual PDO queries to see what values are required
    * @return $response (ARRAY) that looks like
    *                            array('success'=>array(),'error'=>array())
    */
    public function save_embed_quiz($embed_quiz) {
        // decide what we need to do
        if($embed_quiz['action'] === 'insert') {
            // try to insert
            $this->insert_embed_quiz($embed_quiz);
        } else if($embed_quiz['action'] === 'save_load') {
            // try to save a load
            $this->update_embed_quiz_loads($embed_quiz);
        } else {
            $this->add_error('Invalid action.');
        }

        return $this->response;
    }

    /**
    * Validation to make sure we're allowed to insert. Checks on
    *  and adds items to $this->response[errors]
    * array if any validations fails.
    *
    * @param $embed_quiz (ARRAY)
    *        array(embed_quiz_url, quiz_id, embed_site_id, embed_site_quiz_at)
    * @
    */
    public function validate_before_insert($embed_quiz) {

        $required = array(
                        'embed_quiz_url',
                        'quiz_id',
                        'embed_site_id',
                        'embed_quiz_updated_at'
                    );
        foreach($required as $require) {
            if(!array_key_exists($require, $embed_quiz)) {
                $this->add_error($require.' is not set in the array.');
            } else if(empty($embed_quiz[$require])) {
                $this->add_error($require.' is empty.');
            }
        }

        $url = $embed_quiz['embed_quiz_url'];
        $quiz_id = $embed_quiz['quiz_id'];
        $site_id = $embed_quiz['embed_site_id'];
        $date = $embed_quiz['embed_quiz_updated_at'];

        // check that we have a valid url
        if($this->is_valid_url($url) === false) {
            $this->add_error('Invalid URL');
        }



        // check if the quiz exists
        if($this->does_quiz_exist($quiz_id) === false) {
            $this->add_error('Quiz doesn\'t exist');
        }

        // check that it exists && if the quiz matches
        // we want to allow multiple quizzes on one page,
        // but want each quiz on a page to be unique entry
        // ie, we don't want two entries for
        // $url = jeremyjon.es/dev
        // $quiz_id = 1
        if($this->does_embed_quiz_exist($url) === true && $this->does_quiz_exist($quiz_id) === true) {
            // check if the quiz_id matches with it
            $embed_quiz = new Enp_quiz_Embed_quiz($url);
            $embed_quiz_id = $embed_quiz->get_embed_quiz_id();

            if((int) $embed_quiz->get_quiz_id() === (int) $quiz_id) {
                $this->add_error('This URL and Quiz ID row already exists');
            }
        }

        // check if the site id exists
        if($this->does_embed_site_exist($site_id) === false) {
            $this->add_error('Embed Site doesn\'t exist');
        }

        // check if the date is valid
        if($this->is_date($date) === false) {
            $this->add_error('Invalid date.');
        }

        return $this->is_valid($this->response);
    }

    public function validate_before_save_load($embed_quiz) {

        $id = $embed_quiz['embed_quiz_id'];
        $date = $embed_quiz['embed_quiz_updated_at'];
        // check to see if we have one
        if($this->does_embed_quiz_exist($id) === false) {
            $this->add_error('Embed Quiz doesn\'t exist. Add the embed quiz first.');
        }

        // check if the date is valid
        if($this->is_date($date) === false) {
            $this->add_error('Invalid date.');
        }

        return $this->is_valid($this->response);
    }


    /**
    * Connects to DB and inserts a new embed quiz
    * @param $embed_quiz (array) data we'll be saving to the embed_quiz table
    *         must have array('embed_site_id', 'quiz_id', 'embed_quiz_updated_at')
    * @return builds and returns a response message
    */
    protected function insert_embed_quiz($embed_quiz) {
        // validate
        $valid = $this->validate_before_insert($embed_quiz);
        // check if there are any errors
        if($valid !== true) {
            return $this->response;
        }

        // connect to PDO
        $pdo = new enp_quiz_Db();
        // Get our Parameters ready
        $params = array(':quiz_id'      => $embed_quiz['quiz_id'],
                        ':embed_site_id'      => $embed_quiz['embed_site_id'],
                        ':embed_quiz_url'  => $embed_quiz['embed_quiz_url'],
                        ':embed_quiz_loads'  => 1,
                        ':embed_quiz_views'  => 1,
                        ':embed_quiz_created_at' => $embed_quiz['embed_quiz_updated_at'],
                        ':embed_quiz_updated_at' => $embed_quiz['embed_quiz_updated_at']
                    );
        // write our SQL statement
        $sql = "INSERT INTO ".$pdo->embed_quiz_table." (
                                            quiz_id,
                                            embed_site_id,
                                            embed_quiz_url,
                                            embed_quiz_loads,
                                            embed_quiz_views,
                                            embed_quiz_created_at,
                                            embed_quiz_updated_at
                                        )
                                        VALUES(
                                            :quiz_id,
                                            :embed_site_id,
                                            :embed_quiz_url,
                                            :embed_quiz_loads,
                                            :embed_quiz_views,
                                            :embed_quiz_created_at,
                                            :embed_quiz_updated_at
                                        )";
        // insert the mc_option into the database
        $stmt = $pdo->query($sql, $params);

        // success!
        if($stmt !== false) {
            // set-up our response array
            $response = array(
                                        'embed_quiz_id' => $pdo->lastInsertId(),
                                        'status'       => 'success',
                                        'action'       => 'insert'
                                );

            // merge the response arrays
            $success = array_merge($embed_quiz, $response);
            $this->response = array_merge($this->response, $success);

        } else {
            // handle errors
            $this->add_error('Insert embed quiz failed.');
        }
        // return response
        return $this->response;
    }

    /**
    * Connects to DB and adds one to the number of quiz loads
    * @param $embed_quiz (array) data we'll be saving to the embed quiz table
    *        must have array('embed_quiz_id'=>$id, 'embed_quiz_updated_at'=>$time)
    * @return builds and returns a response message
    */
    protected function update_embed_quiz_loads($embed_quiz) {

        $valid = $this->validate_before_save_load($embed_quiz);

        // check if there are any errors
        if($valid !== true) {
            return $this->response;
        }

        // connect to PDO
        $pdo = new enp_quiz_Db();
        // Get our Parameters ready
        $params = array(':embed_quiz_id'      => $embed_quiz['embed_quiz_id'],
                        ':embed_quiz_updated_at' => $embed_quiz['embed_quiz_updated_at']
                    );
        // write our SQL statement
        $sql = "UPDATE ".$pdo->embed_quiz_table."
                   SET  embed_quiz_loads = embed_quiz_loads + 1,
                        embed_quiz_updated_at = :embed_quiz_updated_at
                 WHERE  embed_quiz_id = :embed_quiz_id";
        // insert the mc_option into the database
        $stmt = $pdo->query($sql, $params);

        // success!
        if($stmt !== false) {

            // set-up our response array
            $response = array(
                                        'embed_quiz_id' => $embed_quiz['embed_quiz_id'],
                                        'status'       => 'success',
                                        'action'       => 'updated_quiz_embed_loads'
                                );

            // merge the response arrays
            $success = array_merge($embed_quiz, $response);
            $this->response = array_merge($this->response, $success);

        } else {
            // handle errors
            $this->add_error('Save quiz embed loads failed.');
        }
        // return response
        return $this->response;
    }
}
