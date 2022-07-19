<?php

/**
 * Class WPMUDEV_Dashboard_Multi_Site_Test_Preg_Math
 *
 * just to ensure boot is well
 *
 */
class WPMUDEV_Dashboard_Multi_Site_Test_Init extends WP_UnitTestCase {

	// always logout on teardown
	public function tearDown() {
		WPMUDEV_Dashboard_Test_Util::logout();
		parent::tearDown();
	}

	public function test_loaded() {
		$this->assertNotEmpty( WPMUDEV_Dashboard::$site );
		$this->assertNotEmpty( WPMUDEV_Dashboard::$api );
		$this->assertNotEmpty( WPMUDEV_Dashboard::$upgrader );
		$this->assertNotEmpty( WPMUDEV_Dashboard::$ui );
	}
}
