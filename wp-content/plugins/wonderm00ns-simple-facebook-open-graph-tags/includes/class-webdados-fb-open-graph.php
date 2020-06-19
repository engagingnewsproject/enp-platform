<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Webdados_FB {

	/* Unique identifier */
	//protected $plugin_slug; - Necessário??

	/* Version */
	protected $version;

	/* Database options */
	public $options;

	/* Image sizes */
	public $img_w = WEBDADOS_FB_W;
	public $img_h = WEBDADOS_FB_H;

	/* Public class */
	//public $plugin_public; - Necessário?

	/* Construct */
	public function __construct( $version ) {
		//$this->plugin_slug = 'wonderm00ns-simple-facebook-open-graph-tags';
		$this->version = $version;
		$this->options = $this->load_options();
		$this->load_dependencies();
		//$this->set_locale();
		$this->call_global_hooks();
		if ( is_admin() ) $this->call_admin_hooks();
		if ( !is_admin() ) $this->call_public_hooks();
	}

	/* Default options */
	private function default_options() {
		return apply_filters( 'fb_og_default_options', array(
			//System
			'fb_keep_data_uninstall' => 1,
			'fb_image_min_size' => 200,
			//General
			'fb_desc_chars' => 300,
			'fb_image_use_specific' => 1,
			'fb_image_use_featured' => 1,
			'fb_image_use_content' => 0,
			'fb_image_use_media' => 0,
			'fb_image_use_default' => 1,
			'fb_image_use_mshot' => 0,
			'fb_adv_disable_image_size' => 0,
			//OG
			'fb_title_show' => 1,
			'fb_sitename_show' => 1,
			'fb_url_show' => 1,
			'fb_desc_show' => 1,
			'fb_image_show' => 1,
			'fb_type_show' => 1,
			'fb_author_show' => 1,
			'fb_article_dates_show' => 1,
			'fb_article_sections_show' => 1,
			'fb_publisher_show' => 1,
			'fb_locale_show' => 1,
			'fb_declaration_method' => 'prefix',
			'fb_adv_notify_fb' => 1,
			//Twitter
			'fb_title_show_twitter'	=> 1,
			'fb_url_show_twitter'	=> 1,
			'fb_desc_show_twitter'	=> 1,
			'fb_image_show_twitter' => 1,
			'fb_author_show_twitter' => 1,
			'fb_publisher_show_twitter' => 1,
			'fb_twitter_card_type' => 'summary_large_image',
			//Schema
			'fb_title_show_schema' => 1,
			'fb_desc_show_schema' => 1,
			'fb_image_show_schema' => 1,
			'fb_author_show_schema' => 1,
			'fb_article_dates_show_schema' => 1,
			'fb_publisher_show_schema' => 1,
			//SEO
			//...
			//3rd party
			'fb_show_wpseoyoast' => 1,
			'fb_show_aioseop' => 0,
			'fb_wc_useproductgallery' => 1,
			'fb_subheading_position' => 'after',
		) );
	}

	/* All Settings and sanitize function */
	public function all_options() {
		return apply_filters( 'fb_og_all_options', array(
			'fb_app_id_show'						=>	'intval',
			'fb_app_id'								=>	'trim',
			'fb_admin_id_show'						=>	'intval',
			'fb_admin_id'							=>	'trim',
			'fb_locale_show'						=>	'intval',
			'fb_locale'								=>	'trim',
			'fb_sitename_show'						=>	'intval',
			'fb_title_show'							=>	'intval',
			'fb_title_show_schema'					=>	'intval',
			'fb_title_show_twitter'					=>	'intval',
			'fb_url_show'							=>	'intval',
			'fb_url_show_twitter'					=>	'intval',
			'fb_url_canonical'						=>	'intval',
			'fb_url_add_trailing'					=>	'intval',
			'fb_type_show'							=>	'intval',
			'fb_type_show_schema'					=>	'intval',
			'fb_type_homepage'						=>	'trim',
			'fb_type_schema_homepage'				=>	'trim',
			'fb_type_schema_post'					=>	'trim',
			'fb_article_dates_show'					=>	'intval',
			'fb_article_dates_show_schema'			=>	'intval',
			'fb_article_sections_show'				=>	'intval',
			'fb_publisher_show'						=>	'intval',
			'fb_publisher'							=>	'trim',
			'fb_publisher_show_schema'				=>	'intval',
			'fb_publisher_schema'					=>	'trim',
			'fb_publisher_show_twitter'				=>	'intval',
			'fb_publisher_twitteruser'				=>	'trim',
			'fb_author_show'						=>	'intval',
			'fb_author_show_schema'					=>	'intval',
			'fb_author_show_meta'					=>	'intval',
			'fb_author_show_linkrelgp'				=>	'intval',
			'fb_author_show_twitter'				=>	'intval',
			'fb_author_hide_on_pages'				=>	'intval',
			'fb_desc_show'							=>	'intval',
			'fb_desc_show_meta'						=>	'intval',
			'fb_desc_show_schema'					=>	'intval',
			'fb_desc_show_twitter'					=>	'intval',
			'fb_desc_chars'							=>	'intval',
			'fb_desc_homepage'						=>	'trim',
			'fb_desc_homepage_customtext'			=>	'trim',
			'fb_desc_default_option'				=>	'trim',
			'fb_desc_default'						=>	'trim',
			'fb_image_show'							=>	'intval',
			'fb_image_size_show'					=>	'intval',
			'fb_image_show_schema'					=>	'intval',
			'fb_image_show_twitter'					=>	'intval',
			'fb_image'								=>	'trim',
			'fb_image_rss'							=>	'intval',
			'fb_image_use_specific'					=>	'intval',
			'fb_image_use_featured'					=>	'intval',
			'fb_image_use_content'					=>	'intval',
			'fb_image_use_media'					=>	'intval',
			'fb_image_use_default'					=>	'intval',
			'fb_image_use_mshot'					=>	'intval',
			'fb_adv_disable_image_size'				=>	'intval',
			'fb_image_min_size'						=>	'intval',
			'fb_show_wpseoyoast'					=>	'intval',
			'fb_show_aioseop'						=>	'intval',
			'fb_show_subheading'					=>	'intval',
			'fb_subheading_position'				=>	'trim',
			'fb_show_businessdirectoryplugin'		=>	'intval',
			'fb_keep_data_uninstall'				=>	'intval',
			'fb_adv_force_local'					=>	'intval',
			'fb_adv_notify_fb'						=>	'intval',
			'fb_adv_notify_fb_app_id'				=>	'trim',
			'fb_adv_notify_fb_app_secret'			=>	'trim',
			'fb_adv_supress_fb_notice'				=>	'intval',
			'fb_twitter_card_type'					=>	'trim',
			'fb_wc_usecategthumb'					=>	'intval',
			'fb_wc_useproductgallery'				=>	'intval',
			'fb_wc_usepg_png_overlay'				=>	'intval',
			'fb_image_overlay'						=>	'intval',
			'fb_image_overlay_not_for_default'		=>	'intval',
			'fb_image_overlay_image'				=>	'trim',
			'fb_image_overlay_original_behavior'	=>	'trim',
			'fb_publisher_show_meta'				=>	'intval',
			'fb_declaration_method'					=>	'trim',
			'settings_last_tab'						=>	'intval',
		) );
	}

	/* Load Options */
	private function load_options() {
		$user_options = get_option( 'wonderm00n_open_graph_settings' );
		if ( !is_array($user_options) ) $user_options = array();
		$all_options = $this->all_options();
		$default_options = $this->default_options();
		if ( is_array( $all_options ) ) {
			//Merge the settings "all together now" (yes, it's a Beatles reference)
			foreach( $all_options as $key => $sanitize ) {
				//We have it on the user settings ?
				if ( isset( $user_options[$key] ) ) {
					//Is it empty?
					if ( mb_strlen( trim( $user_options[$key] ) ) == 0 ) {
						//Should we get it from defaults, then?
						if ( !empty( $default_options[$key] ) ) {
							$user_options[$key] = $default_options[$key];
						}
					}
				} else {
					if ( !empty( $default_options[$key] ) ) {
						//Get it from defaults
						$user_options[$key] = $default_options[$key];
					} else {
						//Or just set it as an empty strings to avoid php notices and having to test isset() all the time
						$user_options[$key] = '';
					}
				}
			}
		}
		//Some defaults...
		//Default type to 'website' - https://wordpress.org/support/topic/the-ogtype-blog-is-not-valid-anymore/
		$user_options['fb_type_homepage'] = 'website';
		//No GD? No overlay
		if ( !extension_loaded('gd') ) $user_options['fb_image_overlay'] = 0;
		return $user_options;
	}

	/* Dependencies */
	private function load_dependencies() {
		if ( is_admin() ) require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-webdados-fb-open-graph-admin.php';
		if ( !is_admin() ) require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-webdados-fb-open-graph-public.php';
	}

	/* Translations */
	private function set_locale() {
		//load_plugin_textdomain( 'wonderm00ns-simple-facebook-open-graph-tags', false, dirname(plugin_basename(__FILE__)) . '/../lang/' );
		load_plugin_textdomain( 'wonderm00ns-simple-facebook-open-graph-tags' );
	}

	/* Global hooks */
	private function call_global_hooks() {
		//Update
		add_action( 'plugins_loaded', array( $this, 'update_db_check' ) );
		//Image sizes - After PRO is loaded
		add_action( 'plugins_loaded', array( $this, 'set_image_sizes' ), 12 );
		//Add excerpts to pages
		add_action( 'init', array( $this, 'add_excerpts_to_pages' ) );
	}

	/* Admin hooks */
	private function call_admin_hooks() {
		$plugin_admin = new Webdados_FB_Admin( $this->options, $this->version );
		// Menu
		add_action( 'admin_menu', array( $plugin_admin, 'create_admin_menu' ) );
		// Register settings
		add_action( 'admin_init', array( $plugin_admin, 'options_init' ) );
		// WPML - Translate options
		add_action( 'update_option_wonderm00n_open_graph_settings', array( $plugin_admin, 'options_wpml' ), 10, 3 );
		// Settings link on the Plugins list
		add_filter( 'plugin_action_links_wonderm00ns-simple-facebook-open-graph-tags/wonderm00n-open-graph.php', array( $plugin_admin, 'place_settings_link' ) );
		// User Facebook, Google+ and Twitter profiles
		add_action( 'user_contactmethods', array( $plugin_admin, 'user_contactmethods' ) );
		// Add metabox to posts
		add_action( 'add_meta_boxes', array( $plugin_admin, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $plugin_admin, 'save_meta_boxes' ) );
		// Admin notices
		add_action( 'admin_notices', array( $plugin_admin, 'admin_notices' ) );
		// Admin link to manually update cache
		add_action( 'post_updated_messages', array( $plugin_admin, 'post_updated_messages' ) );
		// Session start so we can know if the cache was cleared on Facebook
		//if(!session_id())
			//@session_start(); //We use @ because some other plugin could previously sent something to the browser
	}

	/* Public hooks */
	private function call_public_hooks() {
		//Create public object
		$plugin_public = new Webdados_FB_Public( $this->options, $this->version );
		// Get Post as soon as he's set, because some plugins, like BDP usally mess with it
		add_action( 'the_post', array( $plugin_public, 'get_post' ), 0 );
		// hook to upate plugin db/options based on version
		add_action( 'wp_head', array( $plugin_public, 'insert_meta_tags' ), 99999 );
		// hook to add Open Graph Namespace
		add_filter( 'language_attributes', array( $plugin_public, 'add_open_graph_namespace' ), 99999 );
		// Add Schema.org itemtype
		add_filter( 'language_attributes', array( $plugin_public, 'add_schema_itemtype' ), 99999 );
		// RSS
		add_action( 'rss2_ns', array( $plugin_public, 'images_on_feed_yahoo_media_tag') );
		add_action( 'rss_item', array( $plugin_public, 'images_on_feed_image') );
		add_action( 'rss2_item', array( $plugin_public, 'images_on_feed_image') );
	}

	/* Update database */
	public function update_db_check() {
		$upgrade = false;
		//Upgrade from 0.5.4 - Last version with individual settings
		if ( !$v = get_option('wonderm00n_open_graph_version') ) {
			//No version because it's a new install or because it's 0.5.4 or less?
			if ( $this->version <= '0.5.4' ) {

			} else {
				//A new install - set the default data on the database
				$upgrade = true;
				update_option( 'wonderm00n_open_graph_settings', $this->options );
			}
		} else {
			if ( $v < $this->version ) {
				//Any version upgrade
				$upgrade=true;
				//We should do any upgrade we need, right here
				if ( $v < '2.0.8' ) {
					$this->options['fb_declaration_method'] = 'xmlns';
					update_option( 'wonderm00n_open_graph_settings', $this->options );
				}
			}
		}
		//Set version on database
		if ($upgrade) {
			update_option( 'wonderm00n_open_graph_version', $this->version );
		}
	}

	/* Set image sizes */
	public function set_image_sizes() {
		$size = apply_filters( 'fb_og_image_size', array( $this->img_w, $this->img_h ) );
		$this->img_w = $size[0];
		$this->img_h = $size[1];
	}

	/* Add excerpt to pages */
	public function add_excerpts_to_pages() {
		add_post_type_support( 'page', 'excerpt' );
	}

	/* WPML */
	public function is_wpml_active() {
		if ( function_exists( 'icl_object_id' ) && function_exists( 'icl_register_string' ) ) {
			global $sitepress;
			if ( is_object($sitepress) ) {
				return true;
			} else {
				return false;
			}
		}
		return false;
	}

	/* WordPress Locale */
	public function get_locale() {
		$locale = get_locale();
		// Facebook doesn't has all the WordPress locales
		$locale_mappings = array(
			'af' => 'af_ZA',
			'ar' => 'ar_AR',
			'ary' => 'ar_AR',
			'as' => 'as_IN',
			'az' => 'az_AZ',
			'azb' => 'az_AZ',
			'bel' => 'be_BY',
			'bn_BD' => 'bn_IN',
			'bo' => 'bp_IN',
			'ca' => 'ca_ES',
			'ceb' => 'cx_PH',
			'ckb' => 'cb_IQ',
			'cy' => 'cy_GB',
			'de_CH' => 'de_DE',
			'de_CH_informal' => 'de_DE',
			'de_DE_formal' => 'de_DE',
			'el' => 'el_GR',
			'en_AU' => 'en_GB',
			'en_CA' => 'en_US',
			'en_NZ' => 'en_GB',
			'en_ZA' => 'en_GB',
			'eo' => 'eo_EO',
			'es_AR' => 'es_ES',
			'es_CL' => 'es_ES',
			'es_CO' => 'es_ES',
			'es_GT' => 'es_ES',
			'es_PE' => 'es_ES',
			'es_VE' => 'es_ES',
			'et' => 'et_EE',
			'eu' => 'eu_ES',
			'fi' => 'fi_FI',
			'fr_BE' => 'fr_FR',
			'gd' => 'ga_IE',
			'gu' => 'gu_IN',
			'hr' => 'hr_HR',
			'hy' => 'hy_AM',
			'ja' => 'ja_JP',
			'km' => 'km_KH',
			'lo' => 'lo_LA',
			'lv' => 'lv_LV',
			'mn' => 'mn_MN',
			'mr' => 'mr_IN',
			'nl_NL_formal' => 'nl_NL',
			'ps' => 'ps_AF',
			'pt_PT_ao90' => 'pt_PT',
			'sah' => 'ky_KG',
			'sq' => 'sq_AL',
			'te' => 'te_IN',
			'th' => 'th_TH',
			'tl' => 'tl_PH',
			'uk' => 'uk_UA',
			'ur' => 'ur_PK',
			'vi' => 'vi_VN',
		);
		if ( isset($locale_mappings[$locale]) ) $locale = $locale_mappings[$locale];
		return trim($locale);
	}

	/* 3rd Party - Yoast SEO */
	public function is_yoast_seo_active() {
		if ( defined( 'WPSEO_VERSION' ) ) {
			return true;
		}
		return false;
	}

	/* 3rd Party - All in One SEO Pack */
	public function is_aioseop_active() {
		if ( defined( 'AIOSEOP_VERSION' ) ) {
			return true;
		}
		return false;
	}

	/* 3rd Party - WooCommerce */
	public function is_woocommerce_active() {
		return in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
	}

	/* 3rd Party - Subheading */
	public function is_subheading_plugin_active() {
		if ( class_exists( 'SubHeading' ) && function_exists( 'get_the_subheading' ) ) {
			return true;
		}
		return false;
	}

	/* 3rd Party - Business Directory Plugin */
	public function is_business_directory_active() {
		@include_once(ABSPATH . 'wp-admin/includes/plugin.php');
		if ( is_plugin_active( 'business-directory-plugin/business-directory-plugin.php' ) ) {
			return true;
		}
		return false;
	}

}