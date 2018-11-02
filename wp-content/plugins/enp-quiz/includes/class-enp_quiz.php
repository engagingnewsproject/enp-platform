<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://engagingnewsproject.org
 * @since      0.0.1
 *
 * @package    Enp_quiz
 * @subpackage Enp_quiz/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      0.0.1
 * @package    Enp_quiz
 * @subpackage Enp_quiz/includes
 * @author     Engaging News Project <jones.jeremydavid@gmail.com>
 */
class Enp_quiz {

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    0.0.1
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Figure out which parts of the plugin we need to load
	 *
	 * @since    0.0.1
	 */
	public function __construct() {

		$this->plugin_name = 'enp_quiz';

		// choose which Class(es) we need to load
		if(defined('DOING_AJAX') && DOING_AJAX) {
			$this->load_quiz_create();
		} elseif(is_admin()) {
			// Run the admin class
			$this->load_admin();
		} else {
			$this->load_quiz_create();
		}

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    0.0.1
	 * @access   private
	 */
	private function load_admin() {
		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-enp_quiz-admin.php';

		$plugin_admin = new Enp_quiz_Admin( $this->get_plugin_name() );

	}

	/**
	 * Load the quiz_create public facing class
	 *
	 * @since    0.0.1
	 * @access   private
	 */
	private function load_quiz_create() {
		/**
		 * The class responsible for defining all actions that occur in the public-facing quiz creation
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/quiz-create/class-enp_quiz-create.php';

		$quiz_create = new Enp_quiz_Create( $this->get_plugin_name() );

	}


	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     0.0.1
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     0.0.1
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return ENP_QUIZ_VERSION;
	}

}
