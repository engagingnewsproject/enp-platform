<?php

namespace WP_Defender\Controller;

/**
 * Since advanced tools will have many submodules, this just using for render.
 *
 * Class Advanced_Tools
 * @package WP_Defender\Controller
 */
class Advanced_Tools extends \WP_Defender\Controller2 {
	public $slug = 'wdf-advanced-tools';

	public function __construct() {
		$this->register_page( esc_html__( 'Tools', 'wpdef' ), $this->slug, [
			&$this,
			'main_view'
		], $this->parent_slug );
		add_action( 'defender_enqueue_assets', [ &$this, 'enqueue_assets' ] );
	}

	public function enqueue_assets() {
		if ( ! $this->is_page_active() ) {
			return;
		}

		$data = [];
		wp_enqueue_script( 'clipboard' );
		$data = apply_filters( 'wp_defender_advanced_tools_data', $data );
		wp_localize_script( 'def-advancedtools', 'advanced_tools', $data );
		wp_enqueue_script( 'def-advancedtools' );
		$this->enqueue_main_assets();
	}

	/**
	 * Render the root element for frontend.
	 */
	public function main_view() {
		$this->render( 'main' );
	}

	/**
	 * Remove settings of submodules.
	 */
	public function remove_settings() {
		( new \WP_Defender\Model\Setting\Mask_Login() )->delete();
		( new \WP_Defender\Model\Setting\Security_Headers() )->delete();
		( new \WP_Defender\Model\Setting\Password_Protection() )->delete();
		( new \WP_Defender\Model\Setting\Password_Reset() )->delete();
		( new \WP_Defender\Model\Setting\Recaptcha() )->delete();
	}

	/**
	 * Drop Defender's directories and files in /uploads/.
	 *
	 * @since 2.4.6
	 */
	public function remove_data() {
		( new \WP_Defender\Controller\Password_Reset() )->remove_data();
		$upload_def_dir = $this->get_tmp_path();
		if ( empty( $upload_def_dir ) ) {
			return;
		}
		global $wp_filesystem;
		if ( is_null( $wp_filesystem ) ) {
			WP_Filesystem();
		}

		$maxmind_dir = $upload_def_dir . DIRECTORY_SEPARATOR . 'maxmind';
		$wp_filesystem->delete( $maxmind_dir, true );
		$arr_deleted_files = array(
			// Files without '.log'. We can delete it when we switch to the <category>.log format completely.
			'audit',
			'internal',
			'malware_scan',
			'notification-audit',
			'scan',
			'password',
			// Files with '.log'.
			'defender.log',
			'audit.log',
			'firewall.log',
			'internal.log',
			'malware_scan.log',
			'notification-audit.log',
			'scan.log',
			'password.log',
			// Old category titles.
			'backlog',
			'mask',
			'notification',
		);

		foreach ( $arr_deleted_files as $deleted_file ) {
			$wp_filesystem->delete( $deleted_file );
		}
	}

	public function data_frontend() {
		return [
			'mask_login'       => wd_di()->get( Mask_Login::class )->data_frontend(),
			'security_headers' => wd_di()->get( Security_Headers::class )->data_frontend(),
			'pwned_passwords'  => wd_di()->get( Password_Protection::class )->data_frontend(),
			'recaptcha'        => wd_di()->get( Recaptcha::class )->data_frontend(),
		];
	}

	public function to_array() {}

	public function import_data( $data ) {}

	/**
	 * @return array
	 */
	public function export_strings() {
		return [];
	}
}
