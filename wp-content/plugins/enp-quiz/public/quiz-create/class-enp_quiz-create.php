<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://engagingnewsproject.org
 * @since      0.0.1
 *
 * @package    Enp_quiz
 * @subpackage Enp_quiz/public/quiz-create
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version,
 * and registers & enqueues quiz create scripts and styles
 *
 * @package    Enp_quiz
 * @subpackage Enp_quiz/public
 * @author     Engaging News Project <jones.jeremydavid@gmail.com>
 */
class Enp_quiz_Create {

	/**
	 * The ID of this plugin.
	 *
	 * @since    0.0.1
	 * @access   protected
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	protected $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    0.0.1
	 * @access   protected
	 * @var      string    $version    The current version of this plugin.
	 */
	protected $version;
	public static $message,
				  $nonce,
				  $saved_quiz_id,
				  $user_action;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    0.0.1
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		// set-up class
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		include_once(WP_CONTENT_DIR.'/enp-quiz-config.php');
		// load take quiz styles
		add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
		// load take quiz scripts
		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
		add_action('init', array($this, 'set_enp_quiz_nonce'), 1);
		add_action('init', array($this, 'add_enp_quiz_rewrite_tags'));

		// redirect to the page they should go to
		add_action('template_redirect', array($this, 'enp_quiz_template_rewrite_catch' ));

		// add our ajax save to be available
		add_action( 'wp_ajax_save_quiz', array($this, 'save_quiz'), 1 );
		// we're including this as a fallback for the other pages.
        // process save, if necessary
        // if the enp-quiz-submit is posted, then they probably want to try to
        // save the quiz. Be nice, try to save the quiz.
        if(isset($_POST['enp-quiz-submit'])) {
            add_action('template_redirect', array($this, 'save_quiz'), 1);
        } elseif(isset($_POST['enp-ab-test-submit'])) {
			add_action('template_redirect', array($this, 'save_ab_test'), 1);
		}
		// custom action hook for displaying messages
        add_action( 'enp_quiz_display_messages', array($this, 'display_messages' ));

		// set title tag
        add_filter( 'document_title_parts', array($this, 'set_title_tag'));
		// remove wp_admin bar
		add_filter('show_admin_bar', '__return_false');
	}

	public function set_enp_quiz_nonce() {
		//Start the session
	   session_start();
	   //Start the class
	   self::$nonce = new Enp_quiz_Nonce();
	}

	/**
	 * Register and enqueue the stylesheets for quiz create.
	 *
	 * @since    0.0.1
	 */
	public function enqueue_styles() {

		wp_register_style( $this->plugin_name.'-quiz-create', plugin_dir_url( __FILE__ ) . 'css/enp_quiz-create.min.css', array(), $this->version );
 	  	wp_enqueue_style( $this->plugin_name.'-quiz-create' );

	}

	/**
	 * Register and enqueue the JavaScript for quiz create.
	 *
	 * @since    0.0.1
	 */
	public function enqueue_scripts() {



	}
	/*
	*	Adds a enp_quiz_template parameter for WordPress to look for
	*   ?enp_quiz_template=dashboard
	*/
	public function add_enp_quiz_rewrite_tags(){
		add_rewrite_tag( '%enp_quiz_template%', '([^/]+)' );
		add_rewrite_tag( '%enp_quiz_id%', '([^/]+)' );
	}

	/*
	* If we find a enp_quiz_template parameter, process it
	* and use the right template file
	* This deletes the_title and the_content and replaces it
	* with our own HTML so we can use their default template, but
	* use our own content injected into their template.
	*
	* @since    0.0.1
	*/
	public function enp_quiz_template_rewrite_catch() {
		global $wp_query;
		// see if enp_quiz_template is one of the query_vars posted
		if ( array_key_exists( 'enp_quiz_template', $wp_query->query_vars ) ) {
			// if it's there, then see what the value is
			$this->template = $wp_query->get( 'enp_quiz_template' );
			$this->template_file = ENP_QUIZ_CREATE_TEMPLATES_PATH.'/'.$this->template.'.php';

			// make sure we have a user
			// and if they're accessing a quiz, that they own it
			$this->validate_user();

			// make sure there's something there
			if(!empty($this->template)) {
				// convert the dashes (-) to underscores (_) so it will match a function
				$this->template_underscored = str_replace('-','_',$this->template);
				// load the template
				$this->load_template();
			}
		}
	}

	/**
	* Get the requested quiz_id from the URL
	* @return	quiz_id if found, else false
	* @since    0.0.1
	**/
	public function enp_quiz_id_rewrite_catch() {
		global $wp_query;
		$quiz_id = false;
		// see if enp_quiz_template is one of the query_vars posted
		if ( array_key_exists( 'enp_quiz_id', $wp_query->query_vars ) ) {
			// if it's there, then see what the value is
			$quiz_id = $wp_query->get( 'enp_quiz_id' );
		}

		return $quiz_id;
	}

	/**
	* Get the requested ab_test_id from the URL
	* @return	quiz_id if found, else false
	* @since    0.0.1
	**/
	public function enp_ab_test_id_rewrite_catch() {
		// same as the quiz_id request right now
		return $this->enp_quiz_id_rewrite_catch();
	}

	/*
	* Load the requested template file.
	* If it's not found, show the dashboard instead
	* @since    0.0.1
	*/
	public function load_template() {
		// load our default page template instead of their theme template
		add_filter('template_include', array($this, 'load_page_template'), 1, 1);
		// add enp-quiz class to the body
		add_filter('body_class', array($this, 'add_enp_quiz_body_class'));
		// check to make sure the template file exists
		if(file_exists($this->template_file)) {
			// set our classname to load (ie - load_dashboard)
			$load_template = 'load_'.$this->template_underscored;
			// load the template dynamically based on the template name
			$this->$load_template();
		} else {
			// if we can't find it, fallback to the dashboard
			$this->load_dashboard();
		}
	}

	/*
	* Sets Title Tag attribute based on template filename
	*/
	public function set_title_tag($title) {
		// $title is an array returned by WP of all the pieces
		// we just need to change the title attribute
		$page_title = str_replace('-',' ',$this->template);
		$page_title = ucwords($page_title);
		$title['title'] = $page_title;

		return $title;
	}

	/*
	* Loads quiz object based on url query
	*/
	public function load_quiz() {
        // prepare the quiz object
        $quiz_id = $this->enp_quiz_id_rewrite_catch();
		// set-up variables
        $quiz = new Enp_quiz_Quiz($quiz_id);
		// check to see if the user owns this quiz
        return $quiz;
    }

	/*
	* Loads quiz object based on url query
	*/
	public function load_ab_test_object() {
        // prepare the quiz object
        $ab_test_id = $this->enp_ab_test_id_rewrite_catch();
		// set-up variables
        $ab_test = new Enp_quiz_AB_test($ab_test_id);
		// check to see if the user owns this quiz
        return $ab_test;
    }
	/*
	* Checks to see if a quiz is valid or not,
	* then redirects to quiz create page if invalid
	*
	* @param $quiz = quiz object
	* @param $publish = 'publish' Publish quiz on URL if valid
	* if they clicked the publish link instead of the button, go ahead and publish it
	* and redirect to the publish page (if JS is on, we'll just divert the link to click
	* the publish button)
	* It won't SAVE their previous settings, but they will still be able to edit them.
	*/
	public function validate_quiz_redirect($quiz, $publish = false) {
		$response = new Enp_quiz_Save_quiz_Response();
        $validate = $response->validate_quiz_and_questions($quiz);
        if($validate === 'invalid') {
			// combine the arrays
			if(is_array(self::$message['error'])) {
				self::$message['error'] = array_merge(self::$message['error'], $response->get_error_messages());
			} else {
				self::$message['error'] = $response->get_error_messages();
			}

			// check to make sure there's actually a quiz...
			if($quiz->get_quiz_id() === null) {
				// nope... redirect to quiz create
				$this->redirect_to_quiz_create('new');
			} else {
				// There's a quiz, it's just an invalid quiz.
				// Send them back to the create page to fix it.
	            $this->redirect_to_quiz_create($quiz->get_quiz_id());
			}

        } elseif($validate === 'valid' && $publish === 'publish') {
			/* publish the quiz and send them to the publish page
				$save = new Enp_quiz_Save_quiz();
				$save->publish_quiz($quiz);
				// redirect them so we can add the messages to the output
				$this->redirect_to_quiz_publish($quiz->get_quiz_id());
			*/
			// let's just send them to the preview page if they're trying to
			// access the publish URL on a NON-published quiz
			if($quiz->get_quiz_status() !== 'published') {
				// add error message
				// add error message
				self::$message['error'][] = "Please use the Publish Button to publish a quiz instead of the Breadcrumb Publish link.";
				// redirect to preview page
				$this->redirect_to_quiz_preview($quiz->get_quiz_id());
			}
		}
	}

	/*
	* If a quiz is published, we won't let them access the create page
	* and redirect them to preview page
	*/
	public function quiz_published_redirect($quiz) {
		if($quiz->get_quiz_status() === 'published') {
			// add error message
			self::$message['error'][] = "You can't edit a published quiz. Please create a new quiz if you need to make changes.";
            // uh oh, invalid quiz. Send them back to the create page to fix it.
            $this->redirect_to_quiz_preview($quiz->get_quiz_id());
        }
	}

	/*
	* for child classes to set the variable on the user_action
	*/
	public function load_user_action() {
		if(!empty(self::$user_action)) {
			return self::$user_action;
		} elseif(isset($_GET['enp_user_action'])) {
			return $_GET['enp_user_action'];
		} else {
			return false;
		}

	}

	public function add_enp_quiz_body_class($classes) {
		$classes[] = 'enp-quiz';
		$classes[] = 'enp-'.$this->template;
		return $classes;
	}

	public function load_page_template() {
		return ENP_QUIZ_CREATE_MAIN_TEMPLATE_PATH;
	}

	public function load_ab_test() {
		include_once(dirname(__FILE__).'/includes/class-enp_quiz-ab_test_view.php');
		new Enp_quiz_AB_test_view();
	}

	public function load_ab_results() {
		include_once(dirname(__FILE__).'/includes/class-enp_quiz-quiz_results.php');
		include_once(dirname(__FILE__).'/includes/class-enp_quiz-ab_results.php');
		new Enp_quiz_AB_results();
	}

	public function load_dashboard() {
		include_once(dirname(__FILE__).'/includes/class-enp_quiz-dashboard.php');
		new Enp_quiz_Dashboard();
	}

	public function load_quiz_create() {
		include_once(dirname(__FILE__).'/includes/class-enp_quiz-quiz_create.php');
		new Enp_quiz_Quiz_create();
	}

	public function load_quiz_preview() {
		include_once(dirname(__FILE__).'/includes/class-enp_quiz-quiz_preview.php');
		new Enp_quiz_Quiz_preview();
	}

	public function load_quiz_publish() {
		include_once(dirname(__FILE__).'/includes/class-enp_quiz-quiz_publish.php');
		new Enp_quiz_Quiz_publish();
	}

	public function load_quiz_results() {
		include_once(dirname(__FILE__).'/includes/class-enp_quiz-quiz_results.php');
		new Enp_quiz_Quiz_results();
	}


	public function save_quiz() {

		// make sure they're logged in and own this quiz
		// returns current_user_id if valid
		$user_id = $this->validate_user();

		$params = array();

	   if(isset($_POST['enp_quiz'])) {
		   $posted_quiz = $_POST['enp_quiz'];
		   if(isset($posted_quiz['new_quiz'])) {
			   $new_quiz_flag = $posted_quiz['new_quiz'];
		   } else {
			    $new_quiz_flag = '0';
		   }
	   }
	   if(isset($_POST['enp_question'])) {
	   		$posted_question = $_POST['enp_question'];
		}

		if(isset($_POST['enp-quiz-submit'])) {
			$posted_user_action = $_POST['enp-quiz-submit'];
		}

		if(isset($_POST['enp_quiz_nonce'])) {
			$posted_nonce = $_POST['enp_quiz_nonce'];
		}

	   //Is it a POST request?
 	   if($_SERVER['REQUEST_METHOD'] === 'POST') {

 		   //Validate the form key
 		   if(!isset($posted_nonce) || !self::$nonce->validate($posted_nonce)) {
 			   // Form key is invalid,
			   // return them to the page (they're probably refreshing the page)
			   self::$message['error'][] = 'Quiz was not resaved';

			   return false;
 		   }
 	   }
	   // initiate the save_quiz object
		// get access to wpdb
		global $wpdb;
		// set-up an empty array for our quiz submission
		$quiz = array();

		// extract values
		// set the date_time to pass
		$date_time = date("Y-m-d H:i:s");
		// build our array to save
		if(isset($posted_quiz)) {
			$quiz = array(
						'quiz' => $posted_quiz,
						'quiz_updated_by' => $user_id,
						'quiz_updated_at' => $date_time,
					);
		}

		if(isset($posted_question) && !empty($posted_question)) {
			$quiz['question'] = $posted_question;
		}

		if(isset($posted_user_action) && !empty($posted_user_action)) {
			// get the value of the button they clicked
			$quiz['user_action'] = $posted_user_action;
		} else {
			// no submit button clicked? Should never happen
			self::$message['error'][] = 'The form was not submitted right. Please contact our support and let them know how you reached this error';
			return false;
		}

		// initiate the save_quiz object
		$save_quiz = new Enp_quiz_Save_quiz();
		// save the quiz by passing our $quiz array to the save function
		$response = $save_quiz->save($quiz);
		// set it as our messages to return to the user
		self::$message = $response->message;

		// get the ID of the quiz that was just created (if there)
		$quiz_id = $response->quiz_id;
		// set-up vars for our next steps
		$save_action = $response->action;
		// set the user_action so we know what the user was wanting to do
		self::$user_action = $response->user_action;

		// check to see if we have a successful save response from the save class
		// REMEMBER: A successful save can still have an error message
		// such as "Quiz Updated. Hey! You don't have any questions though!"
		if($response->status !== 'success') {
			// No successful save, so return them to the same page and display error messages
			return false;
		}
		  //*************************//
		 //  SUCCESS! Now what...?  //
		//*************************//

		if (defined('DOING_AJAX') && DOING_AJAX) {
			$json_response = $response;
			$json_response = json_encode($json_response);
			wp_send_json($json_response);
			// always end ajax with exit()
			exit();
		}
		// if they want to go to the preview page AND there are no errors,
		// let them move on to the preview page
		elseif(self::$user_action['action'] === 'next' && self::$user_action['element'] === 'preview' && empty(self::$message['error'])) {
			// unset the cookies for the current quiz
			// in case they deleted questions and just in general
			// to make it feel as expected (starting quiz from beginning)
			$preview_quiz = new Enp_quiz_Quiz($quiz_id);
			$this->unset_quiz_take_cookies($preview_quiz);
			$this->redirect_to_quiz_preview($quiz_id);
		}
		// if they want to move on to the quiz-publish page and there are no errors, let them
		elseif(self::$user_action['action'] === 'next' && self::$user_action['element'] === 'publish' && empty(self::$message['error'])) {
			// unset the cookies for the current quiz
			$published_quiz = new Enp_quiz_Quiz($quiz_id);
			$this->unset_quiz_take_cookies($published_quiz);
			// redirect to the quiz publish page
			$this->redirect_to_quiz_publish($quiz_id);
		}
		// catch if we're just creating the new quiz, send them to the new quiz page
		elseif($save_action === 'insert' || $new_quiz_flag === '1') {
			// they don't want to move on yet, but they're inserting,
			// so we need to send them to their newly created quiz create page
			$this->redirect_to_quiz_create($quiz_id);
		}
		// we're just updating the same page, return false to send them back
	 	else {
			// we have errors! Oh no! Send them back to fix it
			return false;
		}

	}

	protected function redirect_to_quiz_create($quiz_id) {
		// set a messages array to pass to url on redirect
		$url_query = http_build_query(array('enp_messages' => self::$message, 'enp_user_action'=> self::$user_action));
		// they just created a new page (quiz) so we need to redirect them to it and post our messages
		wp_redirect( site_url( '/enp-quiz/quiz-create/'.$quiz_id.'/?'.$url_query ) );
		exit;
	}

	protected function redirect_to_quiz_preview($quiz_id) {
		// set a messages array to pass to url on redirect
		$url_query = http_build_query(array('enp_messages' => self::$message, 'enp_user_action'=> self::$user_action));

		wp_redirect( site_url( '/enp-quiz/quiz-preview/'.$quiz_id.'/?'.$url_query ) );
		exit;
	}

	protected function redirect_to_quiz_publish($quiz_id) {
		// set a messages array to pass to url on redirect
		$url_query = http_build_query(array('enp_messages' => self::$message, 'enp_user_action'=> self::$user_action));
		wp_redirect( site_url( '/enp-quiz/quiz-publish/'.$quiz_id.'/?'.$url_query ) );
		exit;
	}


	public function save_ab_test() {
		// make sure they're logged in and own this quiz
		// returns current_user_id if valid
		$user_id = $this->validate_user();

		if(isset($_POST['enp_quiz_nonce'])) {
			$posted_nonce = $_POST['enp_quiz_nonce'];
		}

	   	//Is it a POST request?
 	   	if($_SERVER['REQUEST_METHOD'] === 'POST') {

 		   //Validate the form key
 		   if(!isset($posted_nonce) || !self::$nonce->validate($posted_nonce)) {
 			   // Form key is invalid,
			   // return them to the page (they're probably refreshing the page)
			   self::$message['error'][] = 'AB Test was not saved';

			   return false;
 		   }
 	   	}

		$params = $_POST;
		$params['ab_test_updated_by'] = $user_id;
		$save_ab_test = new Enp_quiz_Save_ab_test();
		$response = $save_ab_test->save($params);
		self::$message = $response['messages'];


		if(empty(self::$message['error']) && $response['status'] === 'success' && $response['action'] === 'insert' && isset($response['ab_test_id'])) {
			// successful insert, so redirect them to the embed code section of the results page
			// set a messages array to pass to url on redirect
			$url_query = http_build_query(array('enp_messages' => self::$message, 'enp_user_action' => 'ab_test_created'));
			// they just created a new page (quiz) so we need to redirect them to it and post our messages
			wp_redirect( site_url( '/enp-quiz/ab-results/'.$response['ab_test_id'].'/?'.$url_query ) );
			exit;
		}

	}

	/**
	* Process any error/success messages and output
	* them to the browser.
	* @return false if message, HTML output with messages if found
	* @usage Display in templates using an action hook
	*   	 do_action('enp_quiz_display_messages');
	*		 To set error messages from child classes, add
	*		 parent::$messages['error'][] = 'error message';
	*/
	public function display_messages() {
		// try to get self::$message first bc they might
		// have reloaded a page with a $_GET variable or something
		// and we want our self::$message ones to override that
		if(!empty(self::$message)) {
			// check for self first
			$messages = self::$message;
		} elseif(isset($_GET['enp_messages'])) {
			// check URL second
			$messages = $_GET['enp_messages'];
		} else {
			// no messages. Fail.
			return false;
		}

        $message_content = '';
        if(!empty($messages['error'])) {
            $message_type = 'error';
			$message_content .= $this->display_message_html($messages['error'], $message_type);
        }
		if(!empty($messages['success'])) {
            $message_type = 'success';
			$message_content .= $this->display_message_html($messages['success'], $message_type);
        }

        if(!empty($message_content)) {
            echo $message_content;
        } else {
			return false;
		}

    }

	public function display_message_html($messages, $message_type) {
		$message_html = '';
		if(!empty($messages) && !empty($message_type)) {
			$message_html .= '<section class="enp-quiz-message enp-quiz-message--'.$message_type.' enp-container">
						<h2 class="enp-quiz-message__title enp-quiz-message__title--'.$message_type.'"> '.$message_type.'</h2>
						<ul class="enp-message__list enp-message__list--'.$message_type.'">';
				foreach($messages as $message) {
					$message_html .= '<li class="enp-message__item enp-message__item--'.$message_type.'">'.stripslashes($message).'</li>';
				}
			$message_html .='</ul>
					</section>';
		}

		return $message_html;
	}


	/**
	 * Validate that the user is allowed to be doing this
	 * Checks if they're logged in
	 * Checks if they own the quiz they're trying to access
	 * @return   get_current_user_id(); OR Redirect to login page
	 * @since    0.0.1
	 */
	public function validate_user() {
		if(is_user_logged_in() === false) {
			auth_redirect();
		} else {
			$current_user_id = get_current_user_id();
			// if we're loading a template, find out which one and set the vars accordingly
			if(!empty($this->template)) {
				if($this->template === ('ab-test' || 'ab-results')) {
					// load the ab_test_object
					// they're logged in, but do they own this quiz?
					// get the quiz, if any
					$ab_test = $this->load_ab_test_object();
					if(is_object($ab_test)) {
						$ab_test_id = $ab_test->get_ab_test_id();
						$ab_test_owner = $ab_test->get_ab_test_owner();
						// looks like we have a real quiz
						if($ab_test_id !== null && $ab_test_owner !== null) {
							// see if the owner matches the current user
							if((int) $ab_test_owner !== $current_user_id) {
								// Hey! Get outta here!
								self::$message['error'][] = "You don't have permission to view this AB Test.";
								$url_query = http_build_query(array('enp_messages' => self::$message, 'enp_user_action'=> self::$user_action));
								wp_redirect( site_url( '/enp-quiz/dashboard/user/?'.$url_query ) );
								exit;
							} else {
								// valid!
							}
						}
					}
				} else {
					// we're probably on a quiz
					// they're logged in, but do they own this quiz?
					// get the quiz, if any
					$quiz = $this->load_quiz();
					if(is_object($quiz)) {
						$quiz_id = $quiz->get_quiz_id();
						$quiz_owner = $quiz->get_quiz_owner();
						// looks like we have a real quiz
						if($quiz_id !== null && $quiz_owner !== null) {
							// see if the owner matches the current user
							if((int) $quiz_owner !== $current_user_id) {
								// Hey! Get outta here!
								self::$message['error'][] = "You don't have permission to edit that quiz.";
								$url_query = http_build_query(array('enp_messages' => self::$message, 'enp_user_action'=> self::$user_action));
								wp_redirect( site_url( '/enp-quiz/dashboard/user/?'.$url_query ) );
								exit;
							} else {
								// valid!
							}
						}
					}
				}
			}

			return $current_user_id;
		}
	}


	/**
	* Utility function for returning a percentage
	* @param $part (int) The number you want to see what it's a percentage of
	* @param $whole (int) The whole you want a percentage of
	* @param $decimals (int) How many decimal places you want returned. Defaults to no modification.
	*/
	public function percentagize($part, $whole, $decimals = false) {
		$part = (int) $part;
		$whole = (int) $whole;
		// check to make sure it's valid
		if($whole === 0) {
			return 0;
		}
		// percentage function
		$percentage = ($part / $whole) * 100;
		// if they want some decimals, round it to that amount of decimals
		if($decimals !== false) {
			$percentage = round($percentage, $decimals);
		}
		// return the percentage
		return $percentage;
	}

	/**
	* Duplicate of unset_cookies from class-enp_quiz-take.php
	* probably shouldn't do that, but was running into issues requiring that file for its code
	* and calling the quiz take class
	*/
	public function unset_quiz_take_cookies($quiz) {
		$quiz_id = $quiz->get_quiz_id();
		$question_ids = $quiz->get_questions();
		$twentythirtyeight = 2147483647;
		$path = parse_url(ENP_QUIZ_URL, PHP_URL_PATH);

		setcookie('enp_take_quiz_'.$quiz_id.'_state', 'question', $twentythirtyeight, $path);
		setcookie('enp_take_quiz_'.$quiz_id.'_question_id', $question_ids[0], $twentythirtyeight, $path);

		// loop through all questions and unset their cookie
		foreach($question_ids as $question_id) {
			// build cookie name
			$cookie_name = 'enp_take_quiz_'.$quiz_id.'_'.$question_id;
			// set cookie
			setcookie($cookie_name, '', time() - 3600, $path);
		}

	}

	public function dashboard_breadcrumb_link() {
		return '<a class="enp-breadcrumb-link" href="'.ENP_QUIZ_DASHBOARD_URL.'/user">
				    <svg class="enp-breadcrumb-link__icon enp-icon">
				      <use xlink:href="#icon-chevron-left" />
				    </svg> Dashboard
				</a>';
	}

}
