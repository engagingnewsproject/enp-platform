<?php

namespace WP_Defender\Component\Security_Tweaks;

use Calotes\Base\Component;
use WP_Defender\Component\Security_Tweaks\Security_Key_Const_Interface;
use WP_Defender\Traits\Security_Tweaks_Option;

/**
 * Class Prevent_Enum_Users
 * @package WP_Defender\Component\Security_Tweaks
 */
class Prevent_Enum_Users extends Component implements Security_Key_Const_Interface {

	use Security_Tweaks_Option;

	public $slug     = 'prevent-enum-users';
	public $resolved = false;

	/**
	 * Enabled user enumerations list.
	 *
	 * @var array
	 */
	private $enabled_user_enums = array();

	/**
	 * Check whether the issue has been resolved or not.
	 *
	 * @return bool
	 */
	public function check() {
		return $this->resolved;
	}

	/**
	 * Here is the code for processing, if the return is true, we add it to resolve list, WP_Error if any error.
	 *
	 * @return bool
	 */
	public function process() {
		return true;
	}

	/**
	 * This is for un-do stuff that has been done in @process.
	 *
	 * @return bool
	 */
	public function revert() {
		return true;
	}

	/**
	 * Shield up.
	 *
	 * @return void
	 */
	public function shield_up() {
		$this->resolved = true;

		if ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return;
		}

		add_filter( 'oembed_response_data', array( $this, 'oembed_author_filter' ), 99, 4 );
		add_filter( 'wp_sitemaps_users_pre_url_list', array( $this, 'block_user_url_sitemap' ), 99, 0 );
		add_filter( 'wp_sitemaps_add_provider', array( $this, 'block_user_provider_sitemap' ), 99, 2 );
		add_filter( 'rest_authentication_errors', array( $this, 'block_rest_api_user_endpoint' ) );

		if ( empty( $_SERVER['QUERY_STRING'] ) ) {
			return;
		}

		$this->maybe_block( $_SERVER['QUERY_STRING'] );

		add_filter( 'redirect_canonical', array( $this, 'maybe_block' ) );
	}

	/**
	 * Maybe block the request if it's trying to access the author page with query param.
	 *
	 * @param string $request
	 *
	 * @return string
	 */
	public function maybe_block( $request ) {

		if ( ! current_user_can( 'edit_others_posts' ) ) {
			$message     = __( 'Sorry, you are not allowed to access this page', 'wpdef' );
			$title       = __( 'Forbidden', 'wpdef' );
			$status_code = 403;
			$pattern     = '/author=([0-9]*)|\?author=([0-9]*)(\/*)/i';

			if ( preg_match( $pattern, $request ) ) {
				wp_die( $message, $title, $status_code );
			}
		}

		return $request;
	}

	/**
	 * Return a summary data of this tweak.
	 *
	 * @return array
	 */
	public function to_array() {
		return [
			'slug'             => $this->slug,
			'title'            => __( 'Prevent user enumeration', 'wpdef' ),
			'errorReason'      => __( 'User enumeration is currently allowed.', 'wpdef' ),
			'successReason'    => __( 'User enumeration is currently blocked, nice work!', 'wpdef' ),
			'misc'             => [],
			'bulk_description' => __( 'To brute force your login,  hackers and bots can simply type the query string ?author=1, ?author=2 and so on, which will redirect the page to /author/username/ - bam, the bot now has your usernames to begin brute force attacks with. We can add a .htaccess file to your site to prevent the redirection.', 'wpdef' ),
			'bulk_title'       => __( 'Prevent user enumeration', 'wpdef' )
		];
	}

	/**
	 * Getter method of enabled_user_enums.
	 *
	 * @return array Return array of enabled enumeration options.
	 */
	public function get_enabled_user_enums() {
		$enabled_user_enums = $this->get_option( 'enabled_user_enums' );

		$this->enabled_user_enums = is_null( $enabled_user_enums )
			? array()
			: (array) $enabled_user_enums;

		return $this->enabled_user_enums;
	}

	/**
	 * Setter method of enabled_user_enums.
	 *
	 * @param array $value Array of enabled user enumeration options.
	 *
	 * @return bool Return true if value updated, otherwise false.
	 */
	public function set_enabled_user_enums( $value ) {
		$this->enabled_user_enums = (array) $value;

		return $this->update_option(
			'enabled_user_enums',
			$this->enabled_user_enums
		);
	}

	/**
	 * Filters the author detail from oEmbed response data.
	 *
	 * @param array   $data   The response data.
	 * @param WP_Post $post   The post object.
	 * @param int     $width  The requested width.
	 * @param int     $height The calculated height.
	 *
	 * @return array $data The response data.
	 */
	public function oembed_author_filter( $data, $post, $width, $height ) {
		if (
			in_array(
				'enum-oembed',
				$this->get_enabled_user_enums(),
				true
			)
		) {
			unset( $data['author_name'] );
			unset( $data['author_url'] );
		}

		return $data;
	}

	/**
	 * Blocks user url generation in sitemap.
	 *
	 * Consider the URL: http://example.com/wp-sitemap-users-1.xml will not return user URL XML.
	 *
	 * @return bool|null If option sitemap enumeration enabled then return false else null/void will return.
	 */
	public function block_user_url_sitemap() {
		if (
			in_array(
				'enum-sitemap',
				$this->get_enabled_user_enums(),
				true
			)
		) {
			return false;
		}
	}

	/**
	 * Blocks the user provider sitemap before it is added from wp-sitemap.xml.
	 *
	 * @param WP_Sitemaps_Provider $provider Instance of a WP_Sitemaps_Provider.
	 * @param string               $name     Name of the sitemap provider.
	 *
	 * @return bool|WP_Sitemaps_Provider If option sitemap enumeration enabled then return false else WP_Sitemaps_Provider instance.
	 */
	public function block_user_provider_sitemap( $provider, $name ) {
		if (
			in_array(
				'enum-sitemap',
				$this->get_enabled_user_enums(),
				true
			) &&
			'users' === $name
		) {
			return false;
		}

		return $provider;
	}

	/**
	 * Block user REST API endpoint for not logged-in access.
	 *
	 * @param WP_Error|null|true $errors WP_Error if authentication error, null if authentication
	 *                                   method wasn't used, true if authentication succeeded.
	 *
	 * @return WP_Error|null|true $errors WP_Error if authentication error, null if authentication
	 *                                    method wasn't used, true if authentication succeeded.
	 */
	public function block_rest_api_user_endpoint( $errors ) {
		$request_uri = filter_input( INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_URL );
		$rest_route  = filter_input( INPUT_GET, 'rest_route', FILTER_SANITIZE_URL );

		$is_url_match = preg_match( '/users/i', $request_uri ) !== 0 ||
			preg_match( '/users/i', $rest_route ) !== 0;

		if (
			in_array(
				'enum-rest',
				$this->get_enabled_user_enums(),
				true
			) &&
			$is_url_match &&
			! is_user_logged_in()
		) {
			return new \WP_Error(
				'rest_cannot_access',
				esc_html__( 'Only logged-in users can access the User endpoint REST API.', 'wpdef' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return $errors;
	}
}
