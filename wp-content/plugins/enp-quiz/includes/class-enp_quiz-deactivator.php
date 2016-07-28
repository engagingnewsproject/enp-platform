<?php

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      0.0.1
 * @package    Enp_quiz
 * @subpackage Enp_quiz/includes
 * @author     Engaging News Project <jones.jeremydavid@gmail.com>
 */
class Enp_quiz_Deactivator {

	/**
	 * What to do when our plugin is deactivated
	 * @since    0.0.1
	 */
	public function __construct() {
		// Remove the rewrite rules we added with a hard flush on rewrite rules so it regenerates the htaccess file
		flush_rewrite_rules();
	}

}
