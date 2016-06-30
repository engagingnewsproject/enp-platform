<?php

/**
 * Loads and generates the AB_results
 *
 * @link       http://engagingnewsproject.org
 * @since      0.0.1
 *
 * @package    Enp_quiz
 * @subpackage Enp_quiz/public/AB_results
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version,
 * and registers & enqueues quiz create scripts and styles
 *
 * @package    Enp_quiz
 * @subpackage Enp_quiz/public/AB_results
 * @author     Engaging News Project <jones.jeremydavid@gmail.com>
 */
class Enp_quiz_AB_results extends Enp_quiz_Quiz_results {
    public $ab_test,
           $quiz_a,
           $quiz_b;

    public function __construct() {
        $this->ab_test = $this->load_ab_test_object();
        $this->quiz_a = new Enp_quiz_Quiz_AB_test_result($this->ab_test->get_quiz_id_a(),$this->ab_test->get_ab_test_id());
        $this->quiz_b = new Enp_quiz_Quiz_AB_test_result($this->ab_test->get_quiz_id_b(), $this->ab_test->get_ab_test_id());
        // we're including this as a fallback for the other pages.
        // Other page classes will not need to do this
        add_filter( 'the_content', array($this, 'load_template' ));
        // load take quiz styles
		add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
		// load take quiz scripts
		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // add in json data for scores
        add_action('wp_footer', array($this, 'quiz_results_json'));
        // add an empty slider data object
        add_action('wp_head', array($this, 'setup_slider_results_json'));
    }

    public function load_template() {
        ob_start();
        //Start the class
        $ab_test = $this->ab_test;
        $quiz_a = $this->quiz_a;
        $quiz_b = $this->quiz_b;
        $quiz_a->quiz_score_chart_data = $quiz_a->get_quiz_score_chart_data();
        $quiz_b->quiz_score_chart_data = $quiz_b->get_quiz_score_chart_data();
        include_once( ENP_QUIZ_CREATE_TEMPLATES_PATH.'/ab-results.php' );
        $content = ob_get_contents();
        if (ob_get_length()) ob_end_clean();

        return $content;

    }

    public function quiz_results_json() {

        // get scores from a sorted by key (score % (int)) and value (number of people with that score)
        $quiz_a_scores = $this->quiz_a->get_quiz_scores_group_count();
        $quiz_b_scores = $this->quiz_b->get_quiz_scores_group_count();

        // create a new array for the labels
        $ab_labels = $quiz_a_scores + $quiz_b_scores;
        // index high to low
        ksort($ab_labels);
        // loop through labels and insert null values if missing for new results arrays
        $ab_results_labels = array();
        $quiz_a_series = array();
        $quiz_b_series = array();
        foreach($ab_labels as $key => $val) {
            // set the key for the labels
            $ab_results_labels[] = $key.'%';
            // if the key doesn't exist in there, make it null
            if(!array_key_exists($key, $quiz_a_scores)) {
                $quiz_a_series[] = null;
            } else {
                $quiz_a_series[] = $quiz_a_scores[$key];
            }

            // if the key doesn't exist in there, make it null
            if(!array_key_exists($key, $quiz_b_scores)) {
                $quiz_b_series[] = null;
            } else {
                $quiz_b_series[] = $quiz_b_scores[$key];
            }
        }

        // decide if a or b is the winner
        $winner = $this->get_ab_test_winner();
        $quiz_a_id = $this->quiz_a->get_quiz_id();
        if((int) $winner === (int) $quiz_a_id) {
            $ab_test_winner = 'a';
            $quiz_a_class = 'enp-test-winner';
            $quiz_b_class = 'enp-test-loser';
        } else {
            $ab_test_winner = 'b';
            $quiz_b_class = 'enp-test-winner';
            $quiz_a_class = 'enp-test-loser';
        }

        $ab_results = array(
            'ab_results_labels' => $ab_results_labels,
            'quiz_a_scores' => $quiz_a_series,
            'quiz_a_class' => $quiz_a_class,
            'quiz_b_scores' => $quiz_b_series,
            'quiz_b_class' => $quiz_b_class,
            'ab_test_winner' => $ab_test_winner
        );



        echo '<script type="text/javascript">';
		    // print this whole object as js global vars in json
			echo 'var ab_results_json = '.json_encode($ab_results).';';
		echo '</script>';

    }

    public function enqueue_styles() {
        wp_register_style( $this->plugin_name.'-chartist', plugin_dir_url( __FILE__ ) . '../css/chartist.min.css', array(), ENP_QUIZ_VERSION );
 	  	wp_enqueue_style( $this->plugin_name.'-chartist' );
	}

	/**
	 * Register and enqueue the JavaScript for quiz create.
	 *
	 * @since    0.0.1
	 */
	public function enqueue_scripts() {
        wp_register_script( $this->plugin_name.'-charts', plugin_dir_url( __FILE__ ) . '../js/utilities/chartist.min.js', ENP_QUIZ_VERSION, true );
		wp_enqueue_script( $this->plugin_name.'-charts' );

        wp_register_script( $this->plugin_name.'-accordion', plugin_dir_url( __FILE__ ) . '../js/utilities/accordion.js', array( 'jquery' ), ENP_QUIZ_VERSION, true );
		wp_enqueue_script( $this->plugin_name.'-accordion' );
        // general scripts
		wp_register_script( $this->plugin_name.'-ab-results', plugin_dir_url( __FILE__ ) . '../js/dist/ab-results.js', array( 'jquery', 'underscore', $this->plugin_name.'-accordion' ), ENP_QUIZ_VERSION, true );
		wp_enqueue_script( $this->plugin_name.'-ab-results' );

	}
    /**
    * Decide who is the winner based on which one has a higher % of finishes
    * @return quiz id of winner
    */
    public function get_ab_test_winner() {
        // get the % of finshes from each
        $quiz_a_finishes = $this->percentagize($this->quiz_a->get_quiz_finishes(), $this->quiz_a->get_quiz_views(), 2);
        $quiz_b_finishes = $this->percentagize($this->quiz_b->get_quiz_finishes(), $this->quiz_b->get_quiz_views(), 2);

        if($quiz_a_finishes <= $quiz_b_finishes) {
            $winner = $this->quiz_b->get_quiz_id();
        } else {
            $winner = $this->quiz_a->get_quiz_id();
        }
        return $winner;
    }

    public function ab_test_winner_loser_class($quiz_id) {
        $winner = $this->get_ab_test_winner();
        if((int) $quiz_id === (int) $winner) {
            return 'enp-results--winner';
        } else {
            return 'enp-results--loser';
        }
    }
}
