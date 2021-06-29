<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Component\Audit;

use Calotes\Helper\Array_Cache;
use WP_Defender\Behavior\Utils;
use WP_Defender\Component;
use WP_Defender\Model\Setting\Audit_Logging;
use WP_Defender\Module\Audit\Component\Audit_API;
use WP_Defender\Module\Audit\Model\Settings;
use WP_Defender\Traits\IP;
use WP_Defender\Traits\User;

/**
 * Class Audit_Event
 * @package WP_Defender\Component\Audit
 */
abstract class Audit_Event extends Component {
	use User, IP;

	const ACTION_DELETED = 'deleted', ACTION_TRASHED = 'trashed', ACTION_RESTORED = 'restored', ACTION_UPDATED = 'updated';

	/**
	 * Return an array of hooks
	 *
	 * @return mixed
	 */
	public abstract function get_hooks();

	/**
	 * @param $args array, the custom args
	 * @param $params , the whole parameters of current hook
	 */
	private static function get_custom_args( $args, &$params, &$leftover ) {
		foreach ( $args as $key => $arg ) {
			if ( is_string( $arg ) && preg_match( '/{{.*}}/', $arg ) ) {
				$search = str_replace( array( '{{', '}}' ), '', $arg );
				$found  = self::recursive_look_array( $params, $search );
				if ( $found !== false ) {
					$params[ $key ] = $found;
				}
			} else {
				$params[ $key ] = $arg;
			}
		}
	}

	/**
	 * @param $args
	 * @param $params
	 * @param $leftover
	 */
	private static function get_program_args( $args, &$params, &$leftover ) {
		foreach ( $args as $key => $arg ) {
			//loop thourh each
			$func_args = array();
			if ( isset( $arg['params'] ) && is_array( $arg['params'] ) ) {
				foreach ( $arg['params'] as $value ) {
					if ( is_string( $value ) && preg_match( '/{{.*}}/', $value ) ) {
						$search = str_replace( array( '{{', '}}' ), '', $value );
						$found  = self::recursive_look_array( $params, $search );
						if ( $found !== false ) {
							$func_args[] = $found;
						}
					} else {
						$func_args[] = $value;
					}
				}
			}
			//now call the function
			if ( ! is_array( $arg['callable'] ) ) {
				$ret = call_user_func_array( $arg['callable'], $func_args );
			} else {
				$reflection_method = new \ReflectionMethod( $arg['callable'][0], $arg['callable'][1] );
				$ret               = $reflection_method->invokeArgs( new $arg['callable'][0], $func_args );
			}
			if ( isset( $arg['result_property'] ) ) {
				$ret = self::recursive_look( $ret, explode( '->', $arg['result_property'] ) );
			}

			$params[ $key ] = $ret;
		}
	}


	/**
	 * @param $obj
	 * @param $links
	 *
	 * @return bool|mixed
	 */
	private static function recursive_look( $obj, $links ) {
		$look = null;
		while ( count( $links ) ) {
			$link = array_shift( $links );
			if ( is_array( $obj ) ) {
				$obj = @$obj[ $link ];
			} elseif ( is_object( $obj ) ) {
				$obj = $obj->$link;
			} else {
				$look = false;
				break;
			}
		}
		if ( $look === false ) {
			return false;
		}

		return $obj;
	}

	/**
	 * @param $data
	 * @param $links
	 *
	 * @return bool|mixed
	 */
	private static function recursive_look_array( $data, $links ) {
		$links = explode( '->', $links );
		$obj   = $data[ $links[0] ];
		unset( $links[0] );
		$obj = self::recursive_look( $obj, $links );

		return $obj;
	}

	/**
	 * In many case, text can be condition, example an array contain text 1, text 2, text 3, if 2 matched, we get the first
	 *
	 * @param $text
	 * @param $params
	 *
	 * @return mixed
	 */
	private function get_text( $text, $params ) {
		if ( ! is_array( $text ) ) {
			return $text;
		}

		$matched = array();
		foreach ( $text as $row ) {
			$t = $row[0];
			//this will be inside parameters
			$value_placeholder = str_replace( array( '{{', '}}' ), '', $row[1] );
			//no need to validate here, all should be passed by hardcode
			$value = self::recursive_look_array( $params, $value_placeholder );

			$compare = $row[2];
			if ( is_string( $compare ) && preg_match( '/{{.*}}/', $compare ) ) {
				$search  = str_replace( array( '{{', '}}' ), '', $compare );
				$compare = self::recursive_look_array( $params, $search );
			}

			$comparison = isset( $row[3] ) ? $row[3] : '==';

			if ( is_array( $value ) && is_array( $compare ) ) {
				//comare 2 array
				$map = array(
					'==' => count( self::array_recursive_diff( $value, $compare ) ) == 0 ? true : false,
					'!=' => count( self::array_recursive_diff( $value, $compare ) ) == 0 ? false : true
				);
			} elseif ( is_array( $compare ) ) {
				///in or not in array
				$map = array(
					'in'     => in_array( $value, $compare ),
					'not_in' => in_array( $value, $compare ) == false
				);
			} else {
				$map = array(
					'==' => $value == $compare,
					'!=' => $value != $compare,
					'>'  => $value > $compare,
					'>=' => $value >= $compare,
					'<'  => $value < $compare,
					'<=' => $value <= $compare
				);
			}

			if ( $map[ $comparison ] ) {
				$matched[] = $t;
			}
		}

		return array_shift( $matched );
	}

	private static function array_recursive_diff( $aArray1, $aArray2 ) {
		$aReturn = array();

		foreach ( $aArray1 as $mKey => $mValue ) {
			if ( array_key_exists( $mKey, $aArray2 ) ) {
				if ( is_array( $mValue ) ) {
					$aRecursiveDiff = self::array_recursive_diff( $mValue, $aArray2[ $mKey ] );
					if ( count( $aRecursiveDiff ) ) {
						$aReturn[ $mKey ] = $aRecursiveDiff;
					}
				} else {
					if ( $mValue != $aArray2[ $mKey ] ) {
						$aReturn[ $mKey ] = $mValue;
					}
				}
			} else {
				$aReturn[ $mKey ] = $mValue;
			}
		}

		return $aReturn;
	}

	private static function check_condition( $conditions, $params ) {
		$good = true;
		foreach ( $conditions as $condition ) {
			$links   = $condition[0];
			$value   = self::recursive_look_array( $params, str_replace( array( '{{', '}}' ), '', $links ) );
			$compare = $condition[1];
			if ( is_string( $compare ) && preg_match( '/{{.*}}/', $compare ) ) {
				$search  = str_replace( array( '{{', '}}' ), '', $compare );
				$compare = self::recursive_look_array( $params, $search );
			}

			if ( is_array( $compare ) && in_array( $value, $compare ) ) {
				$good = false;
			} elseif ( $value == $compare ) {
				$good = false;
			}
		}

		return $good;
	}

	public function build_log_data() {
		$args      = func_get_args();
		$hook_name = $args[0];
		$params    = $args[1];

		$user_id   = get_current_user_id();
		$hook_data = $args[2];
		//have to build iup params first
		if ( count( $hook_data['args'] ) != count( $params ) ) {
			//return false for now
			return false;
		} else {
			if ( empty( $hook_data['args'] ) && empty( $params ) ) {
				$params = array();
			} else {
				$params = array_combine( $hook_data['args'], $params );
			}
		}

		if ( isset( $hook_data['callback'] ) && ! empty( $hook_data['callback'] ) ) {
			//custom callback provided, call it
			$reflection_method = new \ReflectionMethod( $hook_data['callback'][0], $hook_data['callback'][1] );
			$ret               = $reflection_method->invokeArgs( new $hook_data['callback'][0], array(
				$hook_name,
				$params
			) );

			if ( is_array( $ret ) && count( $ret ) == 2 ) {
				list( $text, $context ) = $ret;
			} elseif ( is_array( $ret ) && count( $ret ) == 3 ) {
				list( $text, $context, $action ) = $ret;
			} else {
				$text = false;
			}
		} else {
			/**
			 * first we need to query all parameters
			 * we have to loop around custom args, program args to queries and maintain the params list
			 * then we will check the left over, till all done
			 * then we will build up the text
			 */

			$leftover = array();
			if ( isset( $hook_data['custom_args'] ) ) {
				self::get_custom_args( $hook_data['custom_args'], $params, $leftover );
			}

			if ( isset( $hook_data['program_args'] ) ) {
				self::get_program_args( $hook_data['program_args'], $params, $leftover );
			}
			//finally, default params
			$params = array_merge( $this->get_default_params(), $params );
			//still need to check if this condition okay
			if ( isset( $hook_data['false_when'] ) && self::check_condition( $hook_data['false_when'],
					$params ) == false ) {
				return false;
			}
			//now we got all params as key=>value, just build the text
			$text = self::get_text( $hook_data['text'], $params );

			if ( empty( $text ) ) {
				return false;
			}

			foreach ( $params as $key => $val ) {
				$replacer = $val;
				if ( is_array( $replacer ) || is_object( $replacer ) ) {
					continue;
				}
				$text = str_replace( '{{' . $key . '}}', $replacer, $text );
			}

			$context = '';
			if ( isset( $hook_data['context'] ) ) {
				if ( is_callable( $hook_data['context'] ) ) {
					$reflection_method = new \ReflectionMethod( $hook_data['context'][0], $hook_data['context'][1] );
					$context           = $reflection_method->invokeArgs( new $hook_data['context'][0], array(
						$hook_name,
						$params
					) );
				} elseif ( preg_match( '/{{.*}}/', $hook_data['context'] ) ) {
					$context = self::recursive_look_array( $params, str_replace( array(
						'{{',
						'}}'
					), '', $hook_data['context'] ) );
				} else {
					$context = $hook_data['context'];
				}
			}
		}

		if ( $user_id == 0 && in_array( $hook_name, array( 'wp_login' ) ) ) {
			//in this state, user id still 0, we have to get the id via hooks param
			$user    = $params['user'];
			$user_id = $user->ID;
		}

		//we got text, now build the data
		if ( $text == false ) {
			return;
		}
		$settings = new Audit_Logging();
		//build data
		$post = array(
			'timestamp'   => time(),
			'event_type'  => $hook_data['event_type'],
			'action_type' => isset( $action ) ? $action : $hook_data['action_type'],
			'site_url'    => network_site_url(),
			'user_id'     => $user_id,
			'context'     => $context,
			'ip'          => $this->get_user_ip(),
			'msg'         => strip_tags( $text ),
			'blog_id'     => get_current_blog_id(),
			'ttl'         => strtotime( '+ ' . $settings->storage_days )
		);
		Array_Cache::append( 'logs', $post, 'audit' );
	}

	/**
	 * @return array
	 */
	private function get_default_params() {
		return array(
			'wp_user'    => is_user_logged_in() ? ( $this->get_user_display( get_current_user_id() ) ) : __( 'Guest',
				'wpdef' ),
			'wp_user_id' => get_current_user_id()
		);
	}

}
