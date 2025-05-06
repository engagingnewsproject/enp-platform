<?php
/**
 * Class that handles URLs functionality.
 *
 * @link    https://wpmudev.com
 * @since   4.11.4
 * @author  Joel James <joel@incsub.com>
 * @package WPMUDEV_Dashboard_Sui_Page_Urls
 */

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

/**
 * Class WPMUDEV_Dashboard_Sui_Page_Urls
 */
class WPMUDEV_Dashboard_Sui_Page_Urls {
	/**
	 * Dashboard page link.
	 *
	 * @var string
	 */
	public $dashboard_url = '';

	/**
	 * Settings page link.
	 *
	 * @var string
	 */
	public $settings_url = '';

	/**
	 * Plugins page link.
	 *
	 * @var string
	 */
	public $plugins_url = '';

	/**
	 * Support page link.
	 *
	 * @var string
	 */
	public $support_url = '';

	/**
	 * Tools page link.
	 *
	 * @var string
	 */
	public $tools_url = '';

	/**
	 * Remote site base URL.
	 *
	 * @var string
	 */
	public $remote_site = 'https://wpmudev.com/';

	/**
	 * Support URL link.
	 *
	 * @var string
	 */
	public $external_support_url = '';

	/**
	 * Hub2 page link.
	 *
	 * @var string
	 */
	public $hub_url = 'https://wpmudev.com/hub2';

	/**
	 * Hub1 page link.
	 *
	 * @var string
	 */
	public $hub_url_old = 'https://wpmudev.com/hub';

	/**
	 * Documentation section urls.
	 *
	 * @var string[]
	 */
	public $documentation_url = array(
		'dashboard'  => 'https://wpmudev.com/docs/wpmu-dev-plugins/wpmu-dev-dashboard-plugin-instructions/',
		'plugins'    => 'https://wpmudev.com/docs/wpmu-dev-plugins/wpmu-dev-dashboard-plugin-instructions/#plugins',
		'support'    => 'https://wpmudev.com/docs/wpmu-dev-plugins/wpmu-dev-dashboard-plugin-instructions/#support',
		'analytics'  => 'https://wpmudev.com/docs/wpmu-dev-plugins/wpmu-dev-dashboard-plugin-instructions/#analytics',
		'whitelabel' => 'https://wpmudev.com/docs/wpmu-dev-plugins/wpmu-dev-dashboard-plugin-instructions/#white-label',
		'settings'   => 'https://wpmudev.com/docs/wpmu-dev-plugins/wpmu-dev-dashboard-plugin-instructions/#settings',
	);

	/**
	 * Community page link.
	 *
	 * @var string
	 */
	public $community_url = 'https://wpmudev.com/hub2/community';

	/**
	 * Academy page url.
	 *
	 * @var string
	 */
	public $academy_url = 'https://wpmudev.com/academy';

	/**
	 * Hub accounts page url.
	 *
	 * @var string
	 */
	public $hub_account_url = 'https://wpmudev.com/hub/account';

	/**
	 * Trail page url.
	 *
	 * @var string
	 */
	public $trial_url = 'https://wpmudev.com/#trial';

	/**
	 * Backward compat.
	 *
	 * @var string
	 */
	public $real_support_url = '';

	/**
	 * Themes page url.
	 *
	 * @var string
	 */
	public $themes_url = '';

	/**
	 * Whip page url.
	 *
	 * @var string
	 */
	public $whip_url = '';

	/**
	 * Blog page url.
	 *
	 * @var string
	 */
	public $blog_url = '';

	/**
	 * Roadmap page url.
	 *
	 * @var string
	 */
	public $roadmap_url = '';

	/**
	 * Analytics page url.
	 *
	 * @var string
	 */
	public $analytics_url = '';

	/**
	 * Whitelabel page url.
	 *
	 * @var string
	 */
	public $whitelabel_url = '';

	/**
	 * Skip trial page url.
	 *
	 * @var string
	 */
	public $skip_trial_url = '';

	/**
	 * Construct class.
	 *
	 * @access public
	 */
	public function __construct() {
		// Set URL values.
		$this->dashboard_url = network_admin_url( 'admin.php?page=wpmudev' );
		$this->settings_url  = $this->dashboard_url;
		$this->plugins_url   = $this->dashboard_url;
		$this->support_url   = $this->dashboard_url;
		$this->tools_url     = $this->dashboard_url;

		// Set plugin links if logged in.
		if ( WPMUDEV_Dashboard::$api->has_key() ) {
			$this->settings_url   = network_admin_url( 'admin.php?page=wpmudev-settings' );
			$this->plugins_url    = network_admin_url( 'admin.php?page=wpmudev-plugins' );
			$this->support_url    = network_admin_url( 'admin.php?page=wpmudev-support' );
			$this->tools_url      = network_admin_url( 'admin.php?page=wpmudev-tools' );
			$this->analytics_url  = network_admin_url( 'admin.php?page=wpmudev-analytics' );
			$this->whitelabel_url = network_admin_url( 'admin.php?page=wpmudev-whitelabel' );
		}

		// Set remote url if custom api is set.
		if ( defined( 'WPMUDEV_CUSTOM_API_SERVER' ) && WPMUDEV_CUSTOM_API_SERVER ) {
			$this->remote_site = trailingslashit( WPMUDEV_CUSTOM_API_SERVER );
		}

		// External URLs.
		$this->hub_url              = $this->remote_site . 'hub2';
		$this->external_support_url = $this->remote_site . 'hub2/#ask-question';
		$this->community_url        = $this->remote_site . 'hub/community/';
		$this->academy_url          = $this->remote_site . 'academy/';
		$this->hub_account_url      = $this->remote_site . 'hub/account';
		$this->blog_url             = $this->remote_site . 'blog';
		$this->whip_url             = $this->remote_site . 'blog/get-the-whip/';
		$this->roadmap_url          = $this->remote_site . 'roadmap/';
		$this->trial_url            = $this->remote_site . '#trial';
		$this->skip_trial_url       = $this->remote_site . 'hub/account/?skip_trial ';
	}
}