<?php
/**
 * Abstract class used for handling audit events in the WP_Defender plugin.
 *
 * @package WP_Defender\Component\Audit
 */

namespace WP_Defender\Component\Audit;

use Countable;
use ReflectionMethod;
use ReflectionException;
use WP_Defender\Component;
use WP_Defender\Traits\IP;
use WP_Defender\Traits\User;
use Calotes\Helper\Array_Cache;
use WP_Defender\Model\Setting\Audit_Logging;

/**
 * Base class for all audit events.
 */
abstract class Audit_Event extends Component {

	use User;
	use IP;

	public const ACTION_DELETED = 'deleted', ACTION_TRASHED = 'trashed', ACTION_RESTORED = 'restored', ACTION_UPDATED = 'updated';

	/**
	 * Return an array of hooks.
	 *
	 * @return mixed
	 */
	abstract public function get_hooks();

	/**
	 * Loop through the arguments array and process each argument based on its type.
	 *
	 * @param  array $args  The array of arguments to process.
	 * @param  array $params  The parameters array to update with processed arguments.
	 * @param  mixed $leftover  Additional leftover parameter.
	 */
	private static function get_custom_args( $args, &$params, &$leftover ) {
		foreach ( $args as $key => $arg ) {
			if ( is_string( $arg ) && preg_match( '/{{.*}}/', $arg ) ) {
				$search = str_replace( array( '{{', '}}' ), '', $arg );
				$found  = self::recursive_look_array( $params, $search );
				if ( false !== $found ) {
					$params[ $key ] = $found;
				}
			} else {
				$params[ $key ] = $arg;
			}
		}
	}

	/**
	 * Loop through the arguments array and process each argument based on its type.
	 *
	 * @param  mixed $args  The array of arguments to process.
	 * @param  array $params  The parameters array to update with processed arguments.
	 * @param  mixed $leftover  Additional leftover parameter.
	 *
	 * @throws ReflectionException  If the class or method does not exist.
	 */
	private static function get_program_args( $args, &$params, &$leftover ) {
		foreach ( $args as $key => $arg ) {
			// Loop through each.
			$func_args = array();
			if ( isset( $arg['params'] ) && is_array( $arg['params'] ) ) {
				foreach ( $arg['params'] as $value ) {
					if ( is_string( $value ) && preg_match( '/{{.*}}/', $value ) ) {
						$search = str_replace( array( '{{', '}}' ), '', $value );
						$found  = self::recursive_look_array( $params, $search );
						if ( false !== $found ) {
							$func_args[] = $found;
						}
					} else {
						$func_args[] = $value;
					}
				}
			}
			// Call the function.
			if ( ! is_array( $arg['callable'] ) ) {
				$ret = call_user_func_array( $arg['callable'], $func_args );
			} else {
				$reflection_method = new ReflectionMethod( $arg['callable'][0], $arg['callable'][1] );
				$ret               = $reflection_method->invokeArgs( new $arg['callable'][0](), $func_args );
			}
			if ( isset( $arg['result_property'] ) ) {
				$ret = self::recursive_look( $ret, explode( '->', $arg['result_property'] ) );
			}

			$params[ $key ] = $ret;
		}
	}

	/**
	 * Recursively looks up a value within a nested array or object based on provided links.
	 *
	 * This method takes an object or an array and an array of links. It iterates over the links
	 * and uses each link to access a nested property or array element of the $obj. For example,
	 * if the $obj is an array ['a' => ['b' => 'c']], and the links are ['a', 'b'], the method will
	 * return 'c'. If the link does not exist in the object or array, the method will return false.
	 *
	 * @param  mixed $obj  The object or array to access nested properties or array elements.
	 * @param  array $links  The array of links to access nested properties or array elements of the $obj.
	 *
	 * @return mixed The final value of $obj if the iteration is successful, false otherwise.
	 */
	private static function recursive_look( $obj, array $links ) {
		// We will iterate over the links and use each link to access a nested property or array element of the $obj.
		foreach ( $links as $link ) {
			if ( is_array( $obj ) && array_key_exists( $link, $obj ) ) {
				$obj = $obj[ $link ];
			} elseif ( is_object( $obj ) && isset( $obj->$link ) ) {
				$obj = $obj->$link;
			} else {
				return false;
			}
		}
		// If the iteration is successful, we return the final value of $obj.
		return $obj;
	}

	/**
	 * Performs a recursive lookup in an array based on a given set of links.
	 *
	 * @param  array  $data  The array to perform the lookup on.
	 * @param  string $links  The links to follow in the array, separated by '->'.
	 *
	 * @return mixed The value found at the end of the links, or null if not found.
	 */
	private static function recursive_look_array( $data, $links ) {
		$links_array = explode( '->', $links );

		// Check if the first link exists and is valid.
		if ( empty( $links_array ) || ! isset( $data[ $links_array[0] ] ) ) {
			return false;
		}

		// If the first link exists and is valid, retrieve the value of the first link from the data array.
		$obj = $data[ $links_array[0] ];

		// If there are more links to process, pass them to recursive_look.
		if ( count( $links_array ) > 1 ) {
			// Pass the remaining links starting from index 1 to the recursive_look method.
			$remaining_links = array_slice( $links_array, 1 );
			$obj             = self::recursive_look( $obj, $remaining_links );
		}

		// Finally, return the final value found at the end of the links.
		return $obj;
	}

	/**
	 * This is a private method that is used to get the text based on the provided parameters.
	 * In many case, text can be condition, example an array contain text 1, text 2, text 3,
	 * if 2 matched, we get the first.
	 *
	 * @param  mixed $text  The text to be processed. It can be either a string or an array.
	 * @param  array $params  The parameters used to replace placeholders in the text.
	 *
	 * @return mixed The processed text or false if no match is found.
	 */
	private function get_text( $text, $params ) {
		if ( ! is_array( $text ) ) {
			return $text;
		}

		$matched = array();
		foreach ( $text as $row ) {
			$t = $row[0];
			// This will be inside parameters.
			$value_placeholder = str_replace( array( '{{', '}}' ), '', $row[1] );
			// No need to validate here, all should be passed by hardcode.
			$value = self::recursive_look_array( $params, $value_placeholder );

			$compare = $row[2];
			if ( is_string( $compare ) && preg_match( '/{{.*}}/', $compare ) ) {
				$search  = str_replace( array( '{{', '}}' ), '', $compare );
				$compare = self::recursive_look_array( $params, $search );
			}

			$comparison = $row[3] ?? '==';

			if ( is_array( $value ) && is_array( $compare ) ) {
				// Compare 2 arrays.
				$map = array(
					'==' => count( self::array_recursive_diff( $value, $compare ) ) === 0,
					'!=' => count( self::array_recursive_diff( $value, $compare ) ) !== 0,
				);
			} elseif ( is_array( $compare ) ) {
				// In or not in array.
				$map = array(
					'in'     => in_array( $value, $compare, true ),
					'not_in' => in_array( $value, $compare, true ) === false,
				);
			} else {
				$map = array(
					'===' => $value === $compare,
					'!==' => $value !== $compare,
					'>'   => $value > $compare,
					'>='  => $value >= $compare,
					'<'   => $value < $compare,
					'<='  => $value <= $compare,
					'=='  => $value === $compare,
					'!='  => $value !== $compare,
				);
			}

			if ( $map[ $comparison ] ) {
				$matched[] = $t;
			}
		}

		return array_shift( $matched );
	}

	/**
	 * Recursively finds the difference between two arrays.
	 *
	 * @param  array $array1  The first array to compare.
	 * @param  array $array2  The second array to compare against.
	 *
	 * @return array Difference between the two arrays.
	 */
	protected static function array_recursive_diff( $array1, $array2 ): array {
		$result = array();

		foreach ( $array1 as $key => $value ) {
			if ( array_key_exists( $key, $array2 ) ) {
				if ( is_array( $value ) ) {
					$recursive_diff = self::array_recursive_diff( $value, $array2[ $key ] );
					if ( count( $recursive_diff ) ) {
						$result[ $key ] = $recursive_diff;
					}
				} elseif ( $value !== $array2[ $key ] ) {
					$result[ $key ] = $value;
				}
			} else {
				$result[ $key ] = $value;
			}
		}

		return $result;
	}

	/**
	 * Check conditions against parameters and return a boolean result.
	 *
	 * @param  mixed $conditions  The conditions to check against.
	 * @param  array $params  The parameters to use for comparison.
	 *
	 * @return bool The boolean result of the condition check.
	 */
	private static function check_condition( $conditions, $params ): bool {
		$good = true;
		foreach ( $conditions as $condition ) {
			$links   = $condition[0];
			$value   = self::recursive_look_array( $params, str_replace( array( '{{', '}}' ), '', $links ) );
			$compare = $condition[1];
			if ( is_string( $compare ) && preg_match( '/{{.*}}/', $compare ) ) {
				$search  = str_replace( array( '{{', '}}' ), '', $compare );
				$compare = self::recursive_look_array( $params, $search );
			}

			if ( is_array( $compare ) && in_array( $value, $compare, true ) ) {
				$good = false;
			} elseif ( $value === $compare ) {
				$good = false;
			}
		}

		return $good;
	}

	/**
	 * Build log data based on the provided hook data and parameters.
	 *
	 * @return mixed Returns false if the log data cannot be built, otherwise an array of log data.
	 * @throws ReflectionException If the class or method does not exist.
	 */
	public function build_log_data() {
		$args      = func_get_args();
		$hook_name = $args[0];
		$params    = $args[1];
		$user_id   = get_current_user_id();
		$hook_data = $args[2];
		// Have to build iup params first.
		if ( ( is_array( $hook_data['args'] ) || $hook_data['args'] instanceof Countable ? count( $hook_data['args'] ) : 0 ) !== ( is_array( $params ) || $params instanceof Countable ? count( $params ) : 0 ) ) {
			return false;
		} elseif ( empty( $hook_data['args'] ) && empty( $params ) ) {
			$params = array();
		} else {
			$params = array_combine( $hook_data['args'], $params );
		}

		if ( isset( $hook_data['callback'] ) && ! empty( $hook_data['callback'] ) ) {
			// Custom callback provided, call it.
			$reflection_method = new ReflectionMethod( $hook_data['callback'][0], $hook_data['callback'][1] );
			$ret               = $reflection_method->invokeArgs(
				new $hook_data['callback'][0](),
				array(
					$hook_name,
					$params,
				)
			);

			if ( is_array( $ret ) && 2 === count( $ret ) ) {
				[ $text, $context ] = $ret;
			} elseif ( is_array( $ret ) && 3 === count( $ret ) ) {
				[ $text, $context, $action ] = $ret;
			} else {
				$text = false;
			}
		} else {
			/**
			 * First we need to query all parameters,
			 * we have to loop around custom args, program args to queries and maintain the params list,
			 * then we will check the left over, till all done,
			 * then we will build up the text.
			 */
			$leftover = array();
			if ( isset( $hook_data['custom_args'] ) ) {
				self::get_custom_args( $hook_data['custom_args'], $params, $leftover );
			}

			if ( isset( $hook_data['program_args'] ) ) {
				self::get_program_args( $hook_data['program_args'], $params, $leftover );
			}
			$params = array_merge( $this->get_default_params(), $params );
			// Still need to check if this condition okay.
			if ( isset( $hook_data['false_when'] ) && self::check_condition(
				$hook_data['false_when'],
				$params
			) === false ) {
				return false;
			}
			// Now we got all params as key=>value, just build the text.
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
				if ( is_array( $hook_data['context'] ) && is_callable( $hook_data['context'] ) ) {
					$reflection_method = new ReflectionMethod( $hook_data['context'][0], $hook_data['context'][1] );
					$context           = $reflection_method->invokeArgs(
						new $hook_data['context'][0](),
						array(
							$hook_name,
							$params,
						)
					);
				} elseif ( preg_match( '/{{.*}}/', $hook_data['context'] ) ) {
					$context = self::recursive_look_array(
						$params,
						str_replace(
							array(
								'{{',
								'}}',
							),
							'',
							$hook_data['context']
						)
					);
				} else {
					$context = $hook_data['context'];
				}
			}
		}

		$anonymous_override_list = array(
			'wp_login',
			'wp_logout',
			'retrieve_password',
			'after_password_reset',
		);
		if ( 0 === $user_id && in_array( $hook_name, $anonymous_override_list, true ) ) {
			// In this state, user id still 0, we have to get the id via hooks param.
			if ( isset( $params['user'] ) ) {
				$user    = $params['user'];
				$user_id = $user->ID;
			} elseif ( isset( $params['user_id'] ) ) {
				$user_id = $params['user_id'];
			} else {
				// Incorrect data. Return.
				return false;
			}
		}

		// We don't get text. Return.
		if ( ! $text ) {
			return false;
		}

		// Build data.
		$settings    = new Audit_Logging();
		$time        = time();
		$event_type  = $hook_data['event_type'];
		$action_type = $action ?? $hook_data['action_type'];
		$site_url    = network_site_url();
		$msg         = wp_strip_all_tags( $text );
		$blog_id     = get_current_blog_id();
		$ttl         = strtotime( '+ ' . $settings->storage_days );
		foreach ( $this->get_user_ip() as $ip ) {
			$post = array(
				'timestamp'   => $time,
				'event_type'  => $event_type,
				'action_type' => $action_type,
				'site_url'    => $site_url,
				'user_id'     => $user_id,
				'context'     => $context,
				'ip'          => $ip,
				'msg'         => $msg,
				'blog_id'     => $blog_id,
				'ttl'         => $ttl,
			);
			Array_Cache::append( 'logs', $post, 'audit' );
		}
	}

	/**
	 * Retrieves default parameters for building log data.
	 *
	 * @return array default parameters.
	 */
	private function get_default_params(): array {
		return array(
			'wp_user'    => $this->get_source_of_action(),
			'wp_user_id' => get_current_user_id(),
			'blog_name'  => is_multisite() ? '[' . get_bloginfo( 'name' ) . ']' : '',
		);
	}
}