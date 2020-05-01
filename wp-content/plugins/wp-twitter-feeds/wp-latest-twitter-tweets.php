<?php
/**
 * Plugin Name: WP Twitter Feeds
 * Plugin URI: https://www.startbitsolutions.com
 * Description: Displays latest tweets from your Twitter account using Twitter oAuth API 1.1.
 * Author: Team Startbit
 * Version: 1.5
 * Author:       Team Startbit
 * Author URI:   https://www.startbitsolutions.com/
 * Author Email: support@startbitsolutions.com
 * @package    WP Twitter Feeds
 * @since      1.0.0
 * @author     Team Startbit
 * @copyright  Copyright (c) 2016, Startbit IT Solutions Pvt. Ltd.
 * @license    http://www.gnu.org/licenses/gpl-2.0.html
  */
 /*  Copyright 2014-2015  Ashley Sheinwald  (email : ashley@planet-interactive.co.uk)
	  Copyright 2016  Startbit IT Solutions Pvt. Ltd.  (email : support@startbitsolutions.com)
		This program is free software; you can redistribute it and/or modify
		it under the terms of the GNU General Public License, version 2, as
		published by the Free Software Foundation.

		This program is distributed in the hope that it will be useful,
		but WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
		GNU General Public License for more details.

		You should have received a copy of the GNU General Public License
		along with this program; if not, write to the Free Software
		Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
add_action('init', 'viva_twitter_feed');
function viva_twitter_feed()
{
    load_plugin_textdomain('viva-twitter-feed', FALSE, dirname(plugin_basename(__FILE__)).'/languages/');
} 

include( plugin_dir_path( __FILE__ ) . 'twitter_usr_validation.php');
require_once( plugin_dir_path( __FILE__ ) . 'controller/twitter_widget.class.php');
add_action( 'widgets_init', 'viwptf_reg_widget');
function viwptf_reg_widget()
{
register_widget("viwptf_TwitterTweets");
}
 
add_filter('plugin_row_meta', 'viwptf_add_meta_links',10, 2);
function viwptf_add_meta_links($links, $file) {
	if ( strpos( $file, 'wp-latest-twitter-tweets.php' ) !== false ) {
		$links[] = '<a href="http://wordpress.org/support/plugin/wp-twitter-feeds" target="_blank">Support</a>';
	}
	return $links;
}
?>