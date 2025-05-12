<?php
/**
 * Handles the token management for code analysis.
 *
 * @package    WP_Defender\Component\Scan
 */

namespace WP_Defender\Component\Scan;

use WP_Defender\Component;

/**
 * Manages tokens for code analysis.
 */
class Tokens extends Component {

	/**
	 * Holds all tokens.
	 *
	 * @var array
	 */
	public static $tokens = array();

	/**
	 * Retrieves tokens by their line number.
	 *
	 * @param  int $line  The line number to fetch tokens for.
	 *
	 * @return array An array of tokens found on the specified line.
	 */
	public static function get_tokens_by_line( $line ): array {
		$catch = array();
		foreach ( self::$tokens as $token ) {
			if ( $line === $token['line'] ) {
				$catch[] = $token;
			}
		}

		return $catch;
	}

	/**
	 * Formats a list of tokens into a single string.
	 *
	 * @param  array $tokens  An array of tokens to format.
	 *
	 * @return string A string created by concatenating the content of each token.
	 */
	public static function formatter( $tokens ): string {
		$string = '';
		foreach ( $tokens as $token ) {
			$string .= $token['content'];
		}

		return $string;
	}

	/**
	 * Determines the line number from an offset in the content.
	 * This method is a polyfill for environments with PHP versions less than 8.0.
	 *
	 * @param  string $content  The content to examine.
	 * @param  int    $offset  The offset in the content to find the line number for.
	 *
	 * @return int The line number at the specified offset.
	 */
	public static function get_line_from_offset( $content, $offset ): int {
		// Polyfill for PHP version < 8.0.
		$offset     = max( $offset, 1 );
		[ $before ] = str_split( $content, $offset );

		return strlen( $before ) - strlen( str_replace( PHP_EOL, '', $before ) ) + 1;
	}

	/**
	 * Get a list of [line,column] of each token. We will need to use this for highlight the code on frontend.
	 *
	 * @param  array $tokens  An array of tokens to map.
	 *
	 * @return array
	 */
	public static function get_offsets_map( $tokens = array() ): array {
		$tmp    = array();
		$mapper = array();
		foreach ( $tokens as $token ) {
			$tmp[ $token['line'] ][] = $token['column'] + $token['length'];
		}
		foreach ( $tmp as $line => &$cols ) {
			sort( $cols );
			$start    = count( $cols ) > 1 ? current( $cols ) : 0;
			$range    = array( $start, end( $cols ) );
			$mapper[] = array(
				'line'  => $line,
				'range' => $range,
			);
		}

		return $mapper;
	}
}