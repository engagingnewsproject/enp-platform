<?php
/**
 * Save processes for posting to
 *
 * @link       http://engagingnewsproject.org
 * @since      1.1.0
 *
 * @package    Enp_quiz
 * @subpackage Enp_quiz/database
 * @author     Engaging News Project <jones.jeremydavid@gmail.com>
 */

// set enp-quiz-config file path (eehhhh... could be better to not use relative path stuff)
require_once '../../../enp-quiz-config.php';
// which files are required for this to run?
require ENP_QUIZ_PLUGIN_DIR . 'includes/class-enp_quiz.php';
require ENP_QUIZ_PLUGIN_DIR . 'includes/class-enp_quiz-quiz.php';
require ENP_QUIZ_PLUGIN_DIR . 'includes/class-enp_embed-site.php';
require ENP_QUIZ_PLUGIN_DIR . 'includes/class-enp_embed-quiz.php';
require ENP_QUIZ_PLUGIN_DIR . 'includes/class-enp_embed-site-type.php';
require ENP_QUIZ_PLUGIN_DIR . 'includes/class-enp_embed-site-bridge.php';

// Database
require ENP_QUIZ_PLUGIN_DIR . 'database/class-enp_quiz_db.php';
require ENP_QUIZ_PLUGIN_DIR . 'database/class-enp_quiz_save.php';
require ENP_QUIZ_PLUGIN_DIR . 'database/class-enp_quiz_save_embed_quiz.php';
require ENP_QUIZ_PLUGIN_DIR . 'database/class-enp_quiz_save_embed_site.php';

class Enp_quiz_Save_embed extends Enp_quiz_Save {
    public $date,
           $response = array(
                              'error'=>array()
                             );

    public function __construct($embed_data) {
        $this->date = date("Y-m-d H:i:s");
        $embed_data = $this->decode($embed_data);

        $save = $embed_data['save'];

        if($save === 'embed_site') {
            $this->save_embed_site($embed_data);
        } else if($save === 'embed_quiz') {
            $this->save_embed_quiz($embed_data);
        }


    }

    protected function save_embed_site($embed_data) {
        // load required files
        $embed_data['embed_site_updated_at'] = $this->date;

        // start our embed save
        $save_site = new Enp_quiz_Save_embed_site();
        $this->response = $save_site->save_embed_site($embed_data);

        return $this->response;
    }

    protected function save_embed_quiz($embed_data) {
        // load required files

        $embed_data['embed_quiz_updated_at'] = $this->date;

        // start our embed save
        $save_quiz = new Enp_quiz_Save_embed_quiz();
        // decide what we need our action to be
        $exists = $this->does_embed_quiz_exist($embed_data['embed_quiz_url']);
        if($exists === true) {
            $embed_quiz = new Enp_quiz_Embed_quiz($embed_data['embed_quiz_url']);
            // get the ID
            $embed_data['embed_quiz_id'] = $embed_quiz->get_embed_quiz_id();
            // update it
            $embed_data['action'] = 'save_load';
        } else {
            // insert it
            $embed_data['action'] = 'insert';
        }
        $this->response = $save_quiz->save_embed_quiz($embed_data);
        return $this->response;
    }

    protected function decode($array) {
        $decoded = array();
        foreach($array as $key => $val) {
            $decoded[$key] = urldecode($val);
        }
        return $decoded;
    }

    public function get_response() {
        return $this->response;
    }
}


if(isset($_POST['save'])) {
    $embed_save = new Enp_quiz_Save_embed($_POST);

    $response = $embed_save->get_response();

    if(isset($_POST['doing_ajax'])) {
        header('Content-type: application/json');
        echo json_encode($response);
        // don't produce anymore HTML or render anything else
        // otherwise the server keeps going and sends us all
        // the HTML of the page too, but we just want the JSON data
        die();
    }
    return $response;
}
