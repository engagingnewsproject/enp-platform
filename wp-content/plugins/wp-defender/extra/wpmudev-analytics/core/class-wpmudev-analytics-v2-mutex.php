<?php

if ( ! class_exists( 'WPMUDEV_Analytics_V2_Mutex' ) ) {
	class WPMUDEV_Analytics_V2_Mutex {
		public function __construct( $key ) {
		}

		public function __call( $name, $arguments ) {
			_deprecated_function( $name, '2.0' );
		}
	}
}