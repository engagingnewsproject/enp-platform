<?php

namespace WP_Defender\Controller;

/**
 * Since advanced tools will have many sub modules, this just using for render
 *
 * Class Advanced_Tools
 * @package WP_Defender\Controller
 */
class Advanced_Tools extends \WP_Defender\Controller2 {
	public $slug = 'wdf-advanced-tools';

	/**
	 * Advanced_Tools constructor.
	 */
	public function __construct() {
		$this->register_page( esc_html__( 'Advanced Tools', 'wpdef' ), $this->slug, [
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
	 * Render the root element for frontend
	 */
	public function main_view() {
		$this->render( 'main' );
	}

	/**
	 *
	 */
	public function remove_settings() {
		( new \WP_Defender\Model\Setting\Mask_Login() )->delete();
		( new \WP_Defender\Model\Setting\Security_Headers() )->delete();
	}

	/**
	 * Drop Defender's directories and files in /uploads/
	 *
	 * @since 2.4.6
	 */
	public function remove_data() {
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
			'audit',
			'internal',
			'malware-scan',
			'mask',
			'notification',
			'scan',
			'scan_malware',
			'test',
			'defender.log',
		);

		foreach ( $arr_deleted_files as $deleted_file ) {
			$wp_filesystem->delete( $deleted_file );
		}
	}

	public function data_frontend() {
		return [
			'mask_login'       => wd_di()->get( Mask_Login::class )->data_frontend(),
			'security_headers' => wd_di()->get( Security_Headers::class )->data_frontend()
		];
	}

	public function to_array() {
		// TODO: Implement to_array() method.
	}

	public function import_data( $data ) {
		// TODO: Implement import_data() method.
	}

	/**
	 * @return array
	 */
	public function export_strings() {
		return [];
	}
}
