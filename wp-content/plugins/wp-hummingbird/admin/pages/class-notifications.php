<?php
/**
 * Notifications page.
 *
 * @since 3.1.1
 * @package Hummingbird\Admin\Pages
 */

namespace Hummingbird\Admin\Pages;

use Hummingbird\Admin\Page;
use Hummingbird\Core\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Notifications
 */
class Notifications extends Page {

	/**
	 * Register meta boxes.
	 *
	 * @since 3.1.1
	 */
	public function register_meta_boxes() {
		$this->add_meta_box(
			'notifications/summary',
			null,
			null,
			null,
			null,
			'main',
			array(
				'box_class'         => 'sui-box sui-summary sui-summary-sm ' . Utils::get_whitelabel_class(),
				'box_content_class' => false,
			)
		);

		if ( Utils::is_member() ) {
			return;
		}

		$this->add_meta_box(
			'notifications/configure',
			__( 'Configure', 'wphb' ),
			null,
			null,
			null,
			'main',
			array(
				'box_content_class' => 'sui-box-body sui-upsell-items',
			)
		);
	}

}