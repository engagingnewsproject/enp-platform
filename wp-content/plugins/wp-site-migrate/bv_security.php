<?php

class BVSecurity {
	var $config;
	/**
	 * PHP5 constructor.
	 */
	function __construct() {
		global $blogvault;
		$default_conf = array('max_failed_logins' => 10,
			'login_entries' => 20,
			'time_per_login_entry' => 180
		);
		$this->config = $blogvault->getOption('bvsecurityconfig');
		if (!is_array($this->config)) {
			$this->config = array();
		}
		$this->config = array_merge($default_conf, $this->config);
		if (isset($this->config['state']) && ($this->config['state'] == 'disabled')) {
			return;
		}
		if (isset($this->config['limitlogins']) &&  $this->config['limitlogins'] == 'active') {
			add_filter('authenticate', array($this, 'preLogin'), 30, 3);
			add_action('wp_login', array($this, 'loginSuccess'), 10, 2);
			add_action('wp_login_failed', array($this, 'loginFailed'), 1, 1);
		}
	}

	/**
	 * PHP4 constructor.
	 */
	function BVSecurity() {
		BVSecurity::__construct();
	}

	static public function &init() {
		static $instance = false;
		if (!$instance) {
			$instance = new BVSecurity();
		}
		return $instance;
	}

	function preLogin($user, $username = '', $password = '') {
		$ip = ip2long($_SERVER['REMOTE_ADDR']);
		$curtime = time();
		$count = 0;
		$max_entries = $this->config['login_entries'];
		$counting_period = $this->config['time_per_login_entry'] * $max_entries;
		for ($i = 0; $i < $max_entries; $i++) {
			$option = get_option("bv_failed_logins_".$i);
			if (is_array($option) && isset($option['time'])) {
				if (($curtime - $option['time']) <= $counting_period) {
					if (isset($option['ips'][$ip])) {
						$count += $option['ips'][$ip];
					}
				}
			}
		}
		if ($count > $this->config['max_failed_logins']) {
			header('HTTP/1.0 403 Forbidden');
			die("Maximum Login limit reached. Try again after some time. - blogVault Security");
		}
		return $user;
	}

	function loginSuccess($username, $user = NULL) {
		$ip = ip2long($_SERVER['REMOTE_ADDR']);
		for ($i = 0; $i < 20; $i++) {
			$option = get_option("bv_failed_logins_".$i);
			if (is_array($option) && isset($option['time'])) {
				if (isset($option['ips'][$ip])) {
					unset($option['ips'][$ip]);
					update_option("bv_failed_logins_".$i, $option);
				}
			}
		}
	}

	function loginFailed($username) {
		$ip = ip2long($_SERVER['REMOTE_ADDR']);
		$curtime = time();
		$last_option = array('time' => $curtime, 'ips' => array());
		$last_index = 0;
		$last_time = $curtime;
		$max_entries = $this->config['login_entries'];
		$time_per_entry = $this->config['time_per_login_entry'];
		for ($i = 0; $i < $max_entries; $i++) {
			$option = get_option("bv_failed_logins_".$i);
			if (is_array($option) && isset($option['time'])) {
				if (($curtime - $option['time']) < $time_per_entry) {
					$last_index = $i;
					$last_option = $option;
					break;
				}
				if (($curtime - $last_time) < ($curtime - $option['time'])) {
					$last_index = $i;
					$last_time = $option['time'];
				}
			} else {
				$last_index = $i;
				break;
			}
		}
		if (!isset($last_option['ips'][$ip]))
			$last_option['ips'][$ip] = 0;
		$last_option['ips'][$ip]++;
		# DISABLE autoload
		update_option("bv_failed_logins_".$last_index, $last_option);
	}
}