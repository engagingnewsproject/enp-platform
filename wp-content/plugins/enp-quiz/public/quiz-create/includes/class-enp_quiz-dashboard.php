<?php

/**
 * Loads and generates the dashboard
 *
 * @link       http://engagingnewsproject.org
 * @since      0.0.1
 *
 * @package    Enp_quiz
 * @subpackage Enp_quiz/public/dashboard
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version,
 * and registers & enqueues quiz create scripts and styles
 *
 * @package    Enp_quiz
 * @subpackage Enp_quiz/public/dashboard
 * @author     Engaging News Project <jones.jeremydavid@gmail.com>
 */
class Enp_quiz_Dashboard extends Enp_quiz_Create {
    public function __construct() {
        // temporary function while completing beta testing of quiz tool
        $this->first_visit();
        // we're including this as a fallback for the other pages.
        // Other page classes will not need to do this
        add_filter( 'the_content', array($this, 'load_template' ));
        // load take quiz styles
		add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
		// load take quiz scripts
		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    // temporary function while completing beta testing of quiz tool
    public function first_visit() {
        if(!isset($_COOKIE['enp_quiz_creator_first_visit'])) {
            setcookie('enp_quiz_creator_first_visit', '1', 1481743941);
            $_COOKIE['enp_quiz_creator_first_visit'] = '1';
        } elseif($_COOKIE['enp_quiz_creator_first_visit'] === '1') {
            setcookie('enp_quiz_creator_first_visit', '0', 1481743941);
            $_COOKIE['enp_quiz_creator_first_visit'] = '0';
        }
    }

    public function load_template() {
        ob_start();
        //Start the class
        $user = new Enp_quiz_User(get_current_user_id());
        include_once( ENP_QUIZ_CREATE_TEMPLATES_PATH.'/dashboard.php' );
        $content = ob_get_contents();
        if (ob_get_length()) ob_end_clean();

        return $content;
    }

    public function enqueue_styles() {

	}

	/**
	 * Register and enqueue the JavaScript for quiz create.
	 *
	 * @since    0.0.1
	 */
	public function enqueue_scripts() {

		wp_register_script( $this->plugin_name.'-dashboard', plugin_dir_url( __FILE__ ) . '../js/dashboard.js', array( 'jquery' ), ENP_QUIZ_VERSION, true );
		wp_enqueue_script( $this->plugin_name.'-dashboard' );
        // addClass with SVG shim for old jquery
        wp_register_script( $this->plugin_name.'-svg-class-shim', plugin_dir_url( __FILE__ ) . '../js/dist/svg-class-shim.min.js', array( 'jquery' ), ENP_QUIZ_VERSION, true );
		wp_enqueue_script( $this->plugin_name.'-svg-class-shim' );

	}

    public function get_quiz_dashboard_item_title($quiz) {
        if(!is_object($quiz)) {
            return false;
        }
        $quiz_status = $quiz->get_quiz_status();
        if($quiz_status === 'published') {
            $quiz_title = '<a href="'.ENP_QUIZ_RESULTS_URL.$quiz->get_quiz_id().'">'.$quiz->get_quiz_title().'</a>';
        } elseif($quiz_status === 'draft') {
            // if you want to add back in the edit pencil icons
            //$quiz_title .= ' <svg class="enp-icon enp-dash-item__title__icon"><use xlink:href="#icon-edit" /></svg>';
            $quiz_title = '<a href="'.ENP_QUIZ_CREATE_URL.$quiz->get_quiz_id().'"><span class="enp-screen-reader-text">Edit </span>'.$quiz->get_quiz_title().'</a>';
        }
        return $quiz_title;
    }

    public function get_quiz_actions($quiz) {
        if(!is_object($quiz)) {
            return false;
        }
        // set blank array
        $quiz_actions = array();

        $quiz_status = $quiz->get_quiz_status();
        $quiz_id = $quiz->get_quiz_id();
        if($quiz_status === 'published') {
            $quiz_actions[] = array(
                                    'title'=>'Results',
                                    'url' => ENP_QUIZ_RESULTS_URL.$quiz_id,
                            );
            $quiz_actions[] = array(
                                    'title'=>'Edit',
                                    'url' => ENP_QUIZ_CREATE_URL.$quiz_id,
                            );
            $quiz_actions[] = array(
                                    'title'=>'Settings',
                                    'url' => ENP_QUIZ_PREVIEW_URL.$quiz_id,
                            );
            $quiz_actions[] = array(
                                    'title'=>'Embed',
                                    'url' => ENP_QUIZ_PUBLISH_URL.$quiz_id,
                            );
        } elseif($quiz_status === 'draft') {
            $quiz_actions[] = array(
                                    'title'=>'Edit',
                                    'url' => ENP_QUIZ_CREATE_URL.$quiz_id,
                            );

            // see if the quiz is valid. If it is, allow a preview for it
            $response = new Enp_quiz_Save_quiz_Response();
            $validate = $response->validate_quiz_and_questions($quiz);
            if($validate === 'valid') {
                $quiz_actions[] = array(
                                        'title'=>'Preview',
                                        'url' => ENP_QUIZ_PREVIEW_URL.$quiz_id,
                                );
            }
        }


        return $quiz_actions;
    }

    public function get_dashboard_quiz_views($quiz) {
        $views = 0;
        if($quiz->get_quiz_status() === 'published') {
            $views = $quiz->get_quiz_views();
        }
        return $views;
    }

    public function get_dashboard_quiz_finishes($quiz) {
        $finishes = 0;
        if($quiz->get_quiz_status() === 'published') {
            $finishes = $quiz->get_quiz_finishes();
        }
        return $finishes;
    }

    public function get_dashboard_quiz_score_average($quiz) {
        $score_average = 0;
        if($quiz->get_quiz_status() === 'published') {
            $score_average = round($quiz->get_quiz_score_average() * 100);
        }
        return $score_average;
    }

}
