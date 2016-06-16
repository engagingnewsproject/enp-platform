<?php

/**
 * Fired during plugin activation
 *
 * @link       http://engagingnewsproject.org
 * @since      0.0.1
 *
 * @package    Enp_quiz
 * @subpackage Enp_quiz/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      0.0.1
 * @package    Enp_quiz
 * @subpackage Enp_quiz/includes
 * @author     Engaging News Project <jones.jeremydavid@gmail.com>
 */
class Enp_quiz_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    0.0.1
	 */
	public function __construct() {
		global $wpdb;
		$this->create_tables($wpdb);

		// set enp-database-quiz-config file path
		$this->enp_database_config_path = $this->get_enp_database_config_path();

		$database_config_file_exists = $this->check_database_config_file();
		// if doesn't exist, create it
		if($database_config_file_exists === false) {
			//create the config file
			$this->create_database_config_file();
		}

		// set enp-quiz-config file path
		$this->enp_config_path = WP_CONTENT_DIR.'/enp-quiz-config.php';

		$config_file_exists = $this->check_config_file();
		// if doesn't exist, create it
		if($config_file_exists === false) {
			//create the config file
			$this->create_config_file();
		}

		// include the config file now that we have it
		$this->include_config_file();

		// check to see if our image upload directory exists
		if (!file_exists(ENP_QUIZ_IMAGE_DIR)) {
			// if it doesn't exist, create it
		    mkdir(ENP_QUIZ_IMAGE_DIR, 0777, true);
		}


		// Set-up rewrite rules based on our config files
		// add our rewrite rules to htaccess
		$this->add_rewrite_rules();
		// hard flush on rewrite rules so it regenerates the htaccess file
		flush_rewrite_rules();

	}

	protected function include_config_file() {
		$config_file_exists = $this->check_config_file();
		//check to make sure the config file exists
		if($config_file_exists === true) {
			include($this->enp_config_path);
		} else {
			die('Could not find the enp-quiz-config.php file in the wp-content folder. Try deactivating and re-activating the plugin. This should create the config file.');
		}
	}

	protected function add_rewrite_rules() {
		// path to quiz
		// we have to remove the base path because add_rewrite_rule will start // at the base directory. Take ABSPATH and subtract it from our Config Template Path
		$enp_quiz_take_template_path = str_replace(ABSPATH,"",ENP_QUIZ_TAKE_TEMPLATES_PATH);
		$enp_quiz_create_template_path = str_replace(ABSPATH,"",ENP_QUIZ_CREATE_TEMPLATES_PATH);
		// Quiz Create
		add_rewrite_rule('enp-quiz/([^/]*)/([^/]*)?','index.php?enp_quiz_template=$matches[1]&enp_quiz_id=$matches[2]','top');

		// Quiz Take
		add_rewrite_rule('quiz-embed/([0-9]+)?$', $enp_quiz_take_template_path.'quiz.php?quiz_id=$1','top');

		// Take AB Test
		add_rewrite_rule('ab-embed/([0-9]+)?$', $enp_quiz_take_template_path.'ab-test.php?ab_test_id=$1','top');


	}

	protected function create_tables($wpdb) {

		$charset_collate = $wpdb->get_charset_collate();
		// quiz table name
		$this->quiz_table_name = $wpdb->prefix . 'enp_quiz';
		$quiz_table_name = $this->quiz_table_name;
		$quiz_sql = "CREATE TABLE $quiz_table_name (
					quiz_id BIGINT(20) NOT NULL AUTO_INCREMENT,
					quiz_title VARCHAR(255) NOT NULL,
					quiz_status VARCHAR(11) NOT NULL,
					quiz_finish_message VARCHAR(510) NOT NULL,
					quiz_owner BIGINT(20) NOT NULL,
					quiz_created_by BIGINT(20) NOT NULL,
					quiz_created_at DATETIME NOT NULL,
					quiz_updated_by BIGINT(20) NOT NULL,
					quiz_updated_at DATETIME NOT NULL,
					quiz_views BIGINT(20) NOT NULL DEFAULT '0',
					quiz_starts BIGINT(20) NOT NULL DEFAULT '0',
					quiz_finishes BIGINT(20) NOT NULL DEFAULT '0',
					quiz_score_average DECIMAL(5,4) NOT NULL DEFAULT 0,
					quiz_time_spent BIGINT(40) NOT NULL DEFAULT '0',
					quiz_time_spent_average BIGINT(20) NOT NULL DEFAULT '0',
					quiz_is_deleted BOOLEAN DEFAULT 0,
					PRIMARY KEY  (quiz_id)
				) $charset_collate;";

		$this->quiz_option_table_name = $wpdb->prefix . 'enp_quiz_option';
		$quiz_option_table_name = $this->quiz_option_table_name;
		$quiz_option_sql = "CREATE TABLE $quiz_option_table_name (
					quiz_option_id BIGINT(20) NOT NULL AUTO_INCREMENT,
					quiz_id BIGINT(20) NOT NULL,
					quiz_option_name VARCHAR(191) NOT NULL,
					quiz_option_value LONGTEXT NOT NULL,
					PRIMARY KEY  (quiz_option_id),
					FOREIGN KEY  (quiz_id) REFERENCES $quiz_table_name (quiz_id)
				) $charset_collate;";

		$this->question_table_name = $wpdb->prefix . 'enp_question';
		$question_table_name = $this->question_table_name;
		$question_sql = "CREATE TABLE $question_table_name (
					question_id BIGINT(20) NOT NULL AUTO_INCREMENT,
					quiz_id BIGINT(20) NOT NULL,
					question_title VARCHAR(255) NOT NULL,
					question_image TEXT NOT NULL,
					question_image_alt VARCHAR(255) NOT NULL,
					question_order BIGINT(3) NOT NULL,
					question_type VARCHAR(20) NOT NULL,
					question_explanation VARCHAR(510) NOT NULL,
					question_views BIGINT(20) NOT NULL DEFAULT '0',
					question_responses BIGINT(20) NOT NULL DEFAULT '0',
					question_responses_correct BIGINT(20) NOT NULL DEFAULT 0,
					question_responses_incorrect BIGINT(20) NOT NULL DEFAULT 0,
					question_responses_correct_percentage DECIMAL(5,4) NOT NULL DEFAULT 0,
					question_responses_incorrect_percentage DECIMAL(5,4) NOT NULL DEFAULT 0,
					question_score_average DECIMAL(5,4) NOT NULL DEFAULT 0,
					question_time_spent BIGINT(40) NOT NULL,
					question_time_spent_average BIGINT(20) NOT NULL,
					question_is_deleted BOOLEAN DEFAULT 0,
					PRIMARY KEY  (question_id),
					FOREIGN KEY  (quiz_id) REFERENCES $quiz_table_name (quiz_id)
				) $charset_collate;";

		$this->mc_option_table_name = $wpdb->prefix . 'enp_question_mc_option';
		$mc_option_table_name = $this->mc_option_table_name;
		$mc_option_sql = "CREATE TABLE $mc_option_table_name (
					mc_option_id BIGINT(20) NOT NULL AUTO_INCREMENT,
					question_id BIGINT(20) NOT NULL,
					mc_option_content VARCHAR(255) NOT NULL,
					mc_option_correct BOOLEAN NOT NULL,
					mc_option_order BIGINT(3) NOT NULL,
					mc_option_responses BIGINT(20) NOT NULL,
					mc_option_is_deleted BOOLEAN DEFAULT 0,
					PRIMARY KEY  (mc_option_id),
					FOREIGN KEY  (question_id) REFERENCES $question_table_name (question_id)
				) $charset_collate;";

		$this->slider_table_name = $wpdb->prefix . 'enp_question_slider';
		$slider_table_name = $this->slider_table_name;
		$slider_sql = "CREATE TABLE $slider_table_name (
					slider_id BIGINT(20) NOT NULL AUTO_INCREMENT,
					question_id BIGINT(20) NOT NULL,
					slider_range_low DECIMAL(20,4) NOT NULL,
					slider_range_high DECIMAL(20,4) NOT NULL,
					slider_correct_low DECIMAL(20,4) NOT NULL,
					slider_correct_high DECIMAL(20,4) NOT NULL,
					slider_increment DECIMAL(20,4) NOT NULL,
					slider_prefix VARCHAR(70) NOT NULL,
					slider_suffix VARCHAR(70) NOT NULL,
					slider_is_deleted BOOLEAN DEFAULT 0,
					PRIMARY KEY  (slider_id),
					FOREIGN KEY  (question_id) REFERENCES $question_table_name (question_id)
				) $charset_collate;";

		$this->response_quiz_table_name = $wpdb->prefix . 'enp_response_quiz';
		$response_quiz_table_name = $this->response_quiz_table_name;
		$response_quiz_sql = "CREATE TABLE $response_quiz_table_name (
					response_quiz_id BIGINT(20) NOT NULL AUTO_INCREMENT,
					quiz_id BIGINT(20) NOT NULL,
					user_ip VARBINARY(16) NOT NULL,
					user_id CHAR(36) BINARY NOT NULL,
					quiz_viewed BOOLEAN DEFAULT 0,
					quiz_started BOOLEAN DEFAULT 0,
					quiz_completed BOOLEAN DEFAULT 0,
					quiz_restarted BOOLEAN DEFAULT 0,
					quiz_score DECIMAL(5,4) NOT NULL DEFAULT 0,
					response_quiz_created_at DATETIME NOT NULL,
					response_quiz_viewed_at DATETIME NOT NULL,
					response_quiz_updated_at DATETIME NOT NULL,
					response_quiz_is_ab_test BOOLEAN DEFAULT 0,
					response_quiz_is_deleted BOOLEAN DEFAULT 0,
					PRIMARY KEY  (response_quiz_id),
					FOREIGN KEY  (quiz_id) REFERENCES $quiz_table_name (quiz_id)
				) $charset_collate;";

		$this->response_question_table_name = $wpdb->prefix . 'enp_response_question';
		$response_question_table_name = $this->response_question_table_name;
		$response_question_sql = "CREATE TABLE $response_question_table_name (
					response_question_id BIGINT(20) NOT NULL AUTO_INCREMENT,
					response_quiz_id BIGINT(20) NOT NULL,
					question_id BIGINT(20) NOT NULL,
					question_viewed BOOLEAN DEFAULT 0,
					question_responded BOOLEAN DEFAULT 0,
					response_correct BOOLEAN NOT NULL,
					response_question_created_at DATETIME NOT NULL,
					response_question_viewed_at DATETIME NOT NULL,
					response_question_updated_at DATETIME NOT NULL,
					response_question_is_deleted BOOLEAN DEFAULT 0,
					PRIMARY KEY  (response_question_id),
					FOREIGN KEY  (response_quiz_id) REFERENCES $response_quiz_table_name (response_quiz_id),
					FOREIGN KEY  (question_id) REFERENCES $question_table_name (question_id)
				) $charset_collate;";

		$this->response_mc_table_name = $wpdb->prefix . 'enp_response_mc';
		$response_mc_table_name = $this->response_mc_table_name;
		$response_mc_sql = "CREATE TABLE $response_mc_table_name (
					response_mc_id BIGINT(20) NOT NULL AUTO_INCREMENT,
					response_quiz_id BIGINT(20) NOT NULL,
					response_question_id BIGINT(20) NOT NULL,
					mc_option_id BIGINT(20) NOT NULL,
					response_mc_is_deleted BOOLEAN DEFAULT 0,
					PRIMARY KEY  (response_mc_id),
					FOREIGN KEY  (response_quiz_id) REFERENCES $response_quiz_table_name (response_quiz_id),
					FOREIGN KEY  (response_question_id) REFERENCES $response_question_table_name (response_question_id),
					FOREIGN KEY  (mc_option_id) REFERENCES $mc_option_table_name (mc_option_id)
				) $charset_collate;";

		$this->response_slider_table_name = $wpdb->prefix . 'enp_response_slider';
		$response_slider_table_name = $this->response_slider_table_name;
		$response_slider_sql = "CREATE TABLE $response_slider_table_name (
					response_slider_id BIGINT(20) NOT NULL AUTO_INCREMENT,
					response_quiz_id BIGINT(20) NOT NULL,
					response_question_id BIGINT(20) NOT NULL,
					slider_id BIGINT(20) NOT NULL,
					response_slider DECIMAL(20,4) NOT NULL,
					response_slider_is_deleted BOOLEAN DEFAULT 0,
					PRIMARY KEY  (response_slider_id),
					FOREIGN KEY  (response_quiz_id) REFERENCES $response_quiz_table_name (response_quiz_id),
					FOREIGN KEY  (response_question_id) REFERENCES $response_question_table_name (response_question_id),
					FOREIGN KEY  (slider_id) REFERENCES $slider_table_name (slider_id)
				) $charset_collate;";

		$this->ab_test_table_name = $wpdb->prefix . 'enp_ab_test';
		$ab_test_table_name = $this->ab_test_table_name;
		$ab_test_sql = "CREATE TABLE $ab_test_table_name (
					ab_test_id BIGINT(20) NOT NULL AUTO_INCREMENT,
					ab_test_title VARCHAR(255) NOT NULL,
					quiz_id_a BIGINT(20) NOT NULL,
					quiz_id_b BIGINT(20) NOT NULL,
					ab_test_owner BIGINT(20) NOT NULL,
					ab_test_created_by BIGINT(20) NOT NULL,
					ab_test_created_at DATETIME NOT NULL,
					ab_test_updated_by BIGINT(20) NOT NULL,
					ab_test_updated_at DATETIME NOT NULL,
					ab_test_is_deleted BOOLEAN DEFAULT 0,
					PRIMARY KEY  (ab_test_id),
					FOREIGN KEY  (quiz_id_a) REFERENCES $quiz_table_name (quiz_id),
					FOREIGN KEY  (quiz_id_b) REFERENCES $quiz_table_name (quiz_id)
				) $charset_collate;";

		$this->ab_test_response_table_name = $wpdb->prefix . 'enp_response_ab_test';
		$ab_test_response_table_name = $this->ab_test_response_table_name;
		$ab_test_response_sql = "CREATE TABLE $ab_test_response_table_name (
					response_ab_test_id BIGINT(20) NOT NULL AUTO_INCREMENT,
					response_quiz_id BIGINT(20) NOT NULL,
					ab_test_id BIGINT(20) NOT NULL,
					PRIMARY KEY  (response_ab_test_id),
					FOREIGN KEY  (response_quiz_id) REFERENCES $response_quiz_table_name (response_quiz_id),
					FOREIGN KEY  (ab_test_id) REFERENCES $ab_test_table_name (ab_test_id)
				) $charset_collate;";

		// create a tables array,
		// store all the table names and queries
		$tables = array(
					array(
						'name'=>$this->quiz_table_name,
		 				'sql'=>$quiz_sql
					),
					array(
						'name'=>$this->quiz_option_table_name,
		 				'sql'=>$quiz_option_sql
					),
					array(
						'name'=>$this->question_table_name,
		 				'sql'=>$question_sql
					),
					array(
						'name'=>$this->mc_option_table_name,
		 				'sql'=>$mc_option_sql
					),
					array(
						'name'=>$this->slider_table_name,
		 				'sql'=>$slider_sql
					),
					array(
						'name'=>$this->response_quiz_table_name,
		 				'sql'=>$response_quiz_sql
					),
					array(
						'name'=>$this->response_question_table_name,
		 				'sql'=>$response_question_sql
					),
					array(
						'name'=>$this->response_mc_table_name,
		 				'sql'=>$response_mc_sql
					),
					array(
						'name'=>$this->response_slider_table_name,
		 				'sql'=>$response_slider_sql
					),
					array(
						'name'=>$this->ab_test_table_name,
		 				'sql'=>$ab_test_sql
					),
					array(
						'name'=>$this->ab_test_response_table_name,
		 				'sql'=>$ab_test_response_sql
					),
				);

		// require file that allows table creation
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		// loop through all of our tables and
		// create them if they haven't already been created
		foreach($tables as $table) {
			$table_name = $table['name'];
			$table_sql = $table['sql'];
			// see if the table exists or not
			if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
				// it doesn't exist, so create the table
				dbDelta($table_sql);
			}
		}
	}

	protected function get_enp_database_config_path() {
		return $_SERVER["DOCUMENT_ROOT"].'/enp-quiz-database-config.php';
	}

	protected function check_database_config_file() {
		// see if the file exists
		if(file_exists($this->enp_database_config_path)) {
			// if the file exists, return true
			return true;
		}
		// if the file doesn't exist, return false
		return false;
	}

	protected function create_database_config_file() {
		// creates and opens the file for writing
		$database_config_file = fopen($this->enp_database_config_path, "w");

// use single quotes around the DB_PASSWORD in case they have
// a $ in their password
$database_connection = '<?php
// Modify these to match your Quiz Database credentials
$enp_db_name = "'.DB_NAME.'";
$enp_db_user = "'.DB_USER.'";
$enp_db_password = \''.DB_PASSWORD.'\';
$enp_db_host = "'.DB_HOST.'";
$enp_quiz_table_quiz = "'.$this->quiz_table_name.'";
$enp_quiz_table_quiz_option = "'.$this->quiz_option_table_name.'";
$enp_quiz_table_question = "'.$this->question_table_name.'";
$enp_quiz_table_question_mc_option = "'.$this->mc_option_table_name.'";
$enp_quiz_table_question_slider = "'.$this->slider_table_name.'";
$enp_quiz_table_ab_test = "'.$this->ab_test_table_name.'";
$enp_quiz_table_response_quiz = "'.$this->response_quiz_table_name.'";
$enp_quiz_table_response_question = "'.$this->response_question_table_name.'";
$enp_quiz_table_response_mc = "'.$this->response_mc_table_name.'";
$enp_quiz_table_response_slider = "'.$this->response_slider_table_name.'";
$enp_quiz_table_ab_test_response = "'.$this->ab_test_response_table_name.'";
;?>';

		// write to the file
		fwrite($database_config_file, $database_connection);
		// close the file
		fclose($database_config_file);
		return true;
	}

	protected function check_config_file() {
		// see if the file exists
		if(file_exists($this->enp_config_path)) {
			// if the file exists, return true
			return true;
		}
		// if the file doesn't exist, return false
		return false;
	}

	protected function create_config_file() {
		// creates and opens the file for writing
		$config_file = fopen($this->enp_config_path, "w");
		// get site url and append our string
		$enp_take_url = site_url();
		$enp_create_url = site_url('enp-quiz');
		// default image directory for question image uploads
		$image_dir = wp_upload_dir();

$config_contents =
'<?php
include("'.$this->enp_database_config_path.'");
define("ENP_QUIZ_CREATE_TEMPLATES_PATH", "'.ENP_QUIZ_ROOT.'public/quiz-create/templates/");
define("ENP_QUIZ_CREATE_MAIN_TEMPLATE_PATH", "'.ENP_QUIZ_ROOT.'public/quiz-create/templates/enp-quiz-page.php");
define("ENP_QUIZ_TAKE_TEMPLATES_PATH", "'.ENP_QUIZ_ROOT.'public/quiz-take/templates/");
define("ENP_QUIZ_TAKE_RESOURCES_PATH", "'.ENP_QUIZ_ROOT.'public/quiz-take/");
define("ENP_QUIZ_DASHBOARD_URL", "'.$enp_create_url.'/dashboard/");
define("ENP_QUIZ_CREATE_URL", "'.$enp_create_url.'/quiz-create/");
define("ENP_QUIZ_PREVIEW_URL", "'.$enp_create_url.'/quiz-preview/");
define("ENP_QUIZ_PUBLISH_URL", "'.$enp_create_url.'/quiz-publish/");
define("ENP_QUIZ_RESULTS_URL", "'.$enp_create_url.'/quiz-results/");
define("ENP_AB_TEST_URL", "'.$enp_create_url.'/ab-test/");
define("ENP_AB_RESULTS_URL", "'.$enp_create_url.'/ab-results/");
define("ENP_QUIZ_URL", "'.$enp_take_url.'/quiz-embed/");
define("ENP_TAKE_AB_TEST_URL", "'.$enp_take_url.'/ab-embed/");
define("ENP_QUIZ_IMAGE_DIR", "'.$image_dir["basedir"].'/enp-quiz/");
define("ENP_QUIZ_IMAGE_URL", "'.$image_dir["baseurl"].'/enp-quiz/");
define("ENP_QUIZ_PLUGIN_DIR", "'.ENP_QUIZ_ROOT.'");
define("ENP_QUIZ_PLUGIN_URL", "'.ENP_QUIZ_ROOT_URL.'/");
?>';

		// write to the file
		fwrite($config_file, $config_contents);
		// close the file
		fclose($config_file);
		return true;
	}

}
