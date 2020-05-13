<?php
/**
 * WpeDeployment
 *
 * This file contains legacy staging deployment functionality
 *
 * @package wpengine/common-mu-plugin
 */

/**
 * Class WpeDeployment
 *
 * This class contains legacy staging deployment functionality
 */
class WpeDeployment {
	/**
	 * File path for the status file
	 *
	 * @var string
	 */
	public $status_file;

	/**
	 * The content of the status file
	 *
	 * @var false|string
	 */
	public $status;

	/**
	 * Last modified timestamp of status file
	 *
	 * @var false|int
	 */
	public $time;

	/**
	 * I don't think this is used anywhere.
	 *
	 * @var bool
	 */
	public $notice = false;

	/**
	 * I don't think this is used anywhere.
	 *
	 * @var bool
	 */
	public $log = false;

	/**
	 * Should JS warning show up
	 *
	 * @var bool|int
	 */
	public $warn = false;

	/**
	 * WpeDeployment constructor.
	 */
	public function __construct() {
		// If a deployment is underway.
		$this->status_file = ABSPATH . '/wpe-deploy-status-' . PWP_NAME;

		// Stop here if there's no status file.
		if ( ! file_exists( $this->status_file ) ) {
			return;
		}

		// Check status and either delete the status file if it is more than five minutes old, else post a nag message.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$this->status = file_get_contents( $this->status_file );
		$this->time   = filemtime( $this->status_file );
		if ( strstr( $this->status, 'Deploy Completed' ) ) {
			$compare = time() - $this->time;
			// If the deploy status is older than five minutes.
			if ( $compare > 60 * 5 ) {
				unlink( ABSPATH . '/wpe-deploy-status-' . PWP_NAME );
			} else {
				add_action( 'wpe_notices', array( $this, 'nag' ) );
			}
		} else {
			$this->warn = 1;
		}
	}

	/**
	 * Singleton Function
	 */
	public static function instance() {
		static $instance;
		if ( $instance ) {
			return $instance;
		}
		$instance = new WpeDeployment();
		return $instance;
	}

	/**
	 * Used to notify javascript that a deploy is under way.
	 */
	public static function warn() {
		$instance = self::instance();
		return $instance->warn;
	}

	/**
	 * Add an admin notice about the recent deployment
	 *
	 * @param array $notice_obj WP Notice object.
	 */
	public function nag( $notice_obj ) {
		$notice_obj->notices['messages'][] = array(
			'id'      => 'deploy-notice-' . gmdate( 'Y-m-d' ),
			'starts'  => gmdate( 'Y-m-d h:i:s', time() - 600 ),
			'ends'    => gmdate( 'Y-m-d h:i:s', time() + 600 ),
			'class'   => 'alert',
			'type'    => 'normal',
			'message' => 'A deployment was recently completed for this site. If you need to revert to previous state you can do so via the WP Engine <a href="http://my.wpengine.com">User Portal</a>.',
			'force'   => 1,
		);
	}

	/**
	 * Destruct
	 *
	 * Not implemented.
	 */
	public function __destruct() { }
}
