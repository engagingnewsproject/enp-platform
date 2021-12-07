<?php

namespace WP_Defender;

class Component extends \Calotes\Base\Component {
	use \WP_Defender\Traits\IO;
	use \WP_Defender\Traits\User;
	use \WP_Defender\Traits\Formats;
	use \WP_Defender\Traits\IP;

	/**
	 * @param $freq
	 *
	 * @return string
	 */
	public function frequency_to_text( $freq ) {
		switch ( $freq ) {
			case 1:
				$text = __( 'daily', 'wpdef' );
				break;
			case 7:
				$text = __( 'weekly', 'wpdef' );
				break;
			case 30:
				$text = __( 'monthly', 'wpdef' );
				break;
			default:
				$text = '';
				//param not from the button on frontend, log it
				$this->log( sprintf( __( 'Unexpected value %s from IP %s', 'wpdef' ), $freq, $this->get_user_ip() ) );
				break;
		}

		return $text;
	}
}