<?php

if (!defined('ABSPATH')) exit;
if (!class_exists('WPEInfo')) :
	class WPEInfo {
		public $settings;
		public $plugname = 'wpengine';
		public $brandname = 'WPEngine Migration';
		public $badgeinfo = 'wpebadge';
		public $ip_header_option = 'wpeipheader';
		public $brand_option = 'wpebrand';
		public $version = '4.35';
		public $webpage = 'https://wpengine.com';
		public $appurl = 'https://wpengine.blogvault.net';
		public $slug = 'wp-site-migrate/wpengine.php';
		public $plug_redirect = 'wperedirect';
		public $logo = '../assets/img/wpengine-logo.png';
		public $brand_icon = '/assets/img/favicon.ico';

		public function __construct($settings) {
			$this->settings = $settings;
		}

		public function isManualSignup() {
			$scanOption = $this->settings->getOption('bvmanualsignup');
			return (isset($scanOption) && $scanOption == 1);
		}

		public function getBrandInfo() {
			return $this->settings->getOption($this->brand_option);
		}

		public function getBrandName() {
			$brand = $this->getBrandInfo();
			if ($brand && array_key_exists('menuname', $brand)) {
				return $brand['menuname'];
			}
		  
			return $this->brandname;
		}

		public function getBrandIcon() {
			$brand = $this->getBrandInfo();
			if ($brand && array_key_exists('brand_icon', $brand)) {
				return $brand['brand_icon'];
			}
			return $this->brand_icon;
		}

		public function getWatchTime() {
			$time = $this->settings->getOption('bvwatchtime');
			return ($time ? $time : 0);
		}

		public function appUrl() {
			if (defined('BV_APP_URL')) {
				return BV_APP_URL;
			} else {
				$brand = $this->getBrandInfo();
				if ($brand && array_key_exists('appurl', $brand)) {
					return $brand['appurl'];
				}
				return $this->appurl;
			}
		}

		public function isActivePlugin() {
			$expiry_time = time() - (3 * 24 * 3600);
			return ($this->getWatchTime() > $expiry_time);
		}

		public function isProtectModuleEnabled() {
			return ($this->settings->getOption('bvptplug') === $this->plugname) &&
				$this->isActivePlugin();
		}

		public function isDynSyncModuleEnabled() {
			return ($this->settings->getOption('bvdynplug') === $this->plugname) &&
				$this->isActivePlugin();
		}

		public function isActivateRedirectSet() {
			return ($this->settings->getOption($this->plug_redirect) === 'yes') ? true : false;
		}

		public function isMalcare() {
			return $this->getBrandName() === 'MalCare - Pro';
		}

		public function isBlogvault() {
			return $this->getBrandName() === 'BlogVault';
		}

		public function info() {
			return array(
				"bvversion" => $this->version,
				"sha1" => "true",
				"plugname" => $this->plugname
			);
		}
	}
endif;