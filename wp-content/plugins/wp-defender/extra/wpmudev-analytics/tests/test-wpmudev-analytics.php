<?php

class Test_WPMUDEV_Analytics extends WP_UnitTestCase {
	private $time;

	public function set_up() {
		parent::set_up();

		Testable_Mixpanel::$track_count = array();
	}

	/**
	 * @dataProvider mysql_version
	 */
	public function test_event_limit_enforced( $mysql_version ) {
		$time = time();
		$this->set_time( $time );

		$event_limit = 3;
		$analytics   = $this->given_analytics( $event_limit );
		$analytics->set_mysql_version( $mysql_version );
		$analytics->track( 'event' );
		$analytics->track( 'event' );
		$analytics->track( 'event' );
		// 3 events tracked
		$this->then_event_tracked_times( 'event', 3 );

		$analytics->track( 'event' );
		// Event not tracked but exceeded limit event is generated
		$this->then_event_tracked_times( 'event', 3 );
		$this->then_event_tracked_times( 'exceeded_daily_limit', 1 );

		$analytics->track( 'event' );
		// Nothing happened
		$this->then_event_tracked_times( 'event', 3 );
		$this->then_event_tracked_times( 'exceeded_daily_limit', 1 );

		$time_window = HOUR_IN_SECONDS * 24;
		$this->set_time( $time + $time_window );
		$analytics->track( 'event' );
		$this->then_event_tracked_times( 'event', 3 );
		$this->then_event_tracked_times( 'exceeded_daily_limit', 1 );

		$this->set_time( $time + $time_window + 1 );
		$analytics->track( 'event' );
		$analytics->track( 'event' );
		$analytics->track( 'event' );
		// Time window has passed, 3 more events generated
		$this->then_event_tracked_times( 'event', 6 );
		$this->then_event_tracked_times( 'exceeded_daily_limit', 1 );

		$analytics->track( 'event' );
		// Another exceeded event
		$this->then_event_tracked_times( 'event', 6 );
		$this->then_event_tracked_times( 'exceeded_daily_limit', 2 );
	}

	private function then_event_tracked_times( $event_name, $expected_count ) {
		$this->assertEquals( $expected_count, Testable_Mixpanel::$track_count[ $event_name ] );
	}

	private function set_time( $time ) {
		$this->time = $time;
	}

	public function time_function() {
		return $this->time;
	}

	private function given_analytics( $event_limit ) {
		$options   = array(
			'time_function' => array( $this, 'time_function' ),
		);
		$analytics = new WPMUDEV_Analytics_V4( 'plugin_slug', 'plugin_name', $event_limit, 'project_token', $options );
		$analytics->set_mixpanel( new Testable_Mixpanel() );
		return $analytics;
	}

	public function mysql_version() {
		return [
			[ '5.6' ],
			[ '5.7' ],
		];
	}
}

class Testable_Mixpanel {
	static $track_count = array();

	public function track( $event_name ) {
		self::$track_count[ $event_name ] ++;
	}
}