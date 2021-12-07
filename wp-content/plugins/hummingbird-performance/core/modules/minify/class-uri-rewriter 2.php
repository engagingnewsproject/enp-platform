<?php
/**
 * Rewrite file-relative URIs as root-relative in CSS files
 *
 * @package Hummingbird\Core\Modules\Minify
 * @author Stephen Clay <steve@mrclay.org>
 */

namespace Hummingbird\Core\Modules\Minify;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class URI_Rewriter
 */
class URI_Rewriter {

	/**
	 * Defines which class to call as part of callbacks, change this
	 * if you extend URI_Rewriter
	 *
	 * @var string
	 */
	protected static $class_name = 'Hummingbird\\Core\\Modules\\Minify\\URI_Rewriter';

	/**
	 * Directory of this stylesheet
	 *
	 * @var string
	 */
	private static $current_dir = '';

	/**
	 * DOC_ROOT
	 *
	 * @var string
	 */
	private static $doc_root = '';

	/**
	 * Directory replacements to map symlink targets back to their
	 * source (within the document root) E.g. '/var/www/symlink' => '/var/realpath'
	 *
	 * @var array
	 */
	private static $symlinks = array();

	/**
	 * Path to prepend
	 *
	 * @var string
	 */
	private static $prepend_path = null;

	/**
	 * Methods rewrite() and rewrite_relative() append debugging information here
	 *
	 * @var string $debug_text
	 */
	public static $debug_text = '';

	/**
	 * Get realpath with any trailing slash removed. If realpath() fails,
	 * just remove the trailing slash.
	 *
	 * @param string $path  Path to process.
	 *
	 * @return mixed path with no trailing slash
	 */
	protected static function realpath( $path ) {
		$real_path = realpath( $path );
		if ( false !== $real_path ) {
			$path = $real_path;
		}
		return rtrim( $path, '/\\' );
	}

	/**
	 * Trim URLs
	 *
	 * @param string $css  CSS URL.
	 *
	 * @return string
	 */
	private static function trim_urls( $css ) {
		return preg_replace(
			'/
            url\\(      # url(
            \\s*
            ([^\\)]+?)  # 1 = URI (assuming does not contain ")")
            \\s*
            \\)         # )
        /x',
			'url($1)',
			$css
		);
	}

	/**
	 * Process callback function.
	 *
	 * @used-by URI_Rewriter::rewrite()
	 * @used-by URI_Rewriter::prepend()
	 *
	 * @param array $m  Array of found links.
	 *
	 * @return string
	 */
	private static function process_uri_cb( $m ) {
		// $m matched either '/@import\\s+([\'"])(.*?)[\'"]/' or '/url\\(\\s*([^\\)\\s]+)\\s*\\)/'
		$is_import = ( '@' === $m[0][0] );
		// determine URI and the quote character (if any).
		if ( $is_import ) {
			$quote_char = $m[1];
			$uri        = $m[2];
		} else {
			// $m[1] is either quoted or not
			$quote_char = ( "'" === $m[1][0] || '"' === $m[1][0] )
				? $m[1][0]
				: '';
			$uri        = ( '' === $quote_char )
				? $m[1]
				: substr( $m[1], 1, strlen( $m[1] ) - 2 );
		}
		// analyze URI (non rool-relative).
		if ( '/' !== $uri[0]                          // root-relative.
			&& false === strpos( $uri, '//' )  // protocol (non-data).
			&& 0 !== strpos( $uri, 'data:' )   // data protocol.
		) {
			// URI is file-relative: rewrite depending on options.
			if ( null === self::$prepend_path ) {
				$uri = self::rewrite_relative( $uri, self::$current_dir, self::$doc_root, self::$symlinks );
			} else {
				$uri = self::$prepend_path . $uri;
				if ( '/' === $uri[0] ) {
					$root          = '';
					$root_relative = $uri;
					$uri           = $root . self::remove_dots( $root_relative );
				} elseif ( preg_match( '@^((https?\:)?//([^/]+))/@', $uri, $m ) && ( false !== strpos( $m[3], '.' ) ) ) {
					$root          = $m[1];
					$root_relative = substr( $uri, strlen( $root ) );
					$uri           = $root . self::remove_dots( $root_relative );
				}
			}
		} elseif ( '/' === $uri[0]                            // root-relative.
					&& false === strpos( $uri, '//' )  // protocol (non-data).
					&& 0 !== strpos( $uri, 'data:' )   // data protocol.
					&& null !== self::$prepend_path
		) {
			preg_match( '@^((https?\:)?//([^/]+))/@', self::$prepend_path, $m );
			if ( preg_match( '@^((https?\:)?//([^/]+))/@', self::$prepend_path, $m ) && ( false !== strpos( $m[3], '.' ) ) ) {
				$uri = $m[1] . self::remove_dots( $uri );
			}
		}

		return $is_import
			? "@import {$quote_char}{$uri}{$quote_char}"
			: "url({$quote_char}{$uri}{$quote_char})";
	}

	/**
	 * In CSS content, rewrite file relative URIs as root relative
	 *
	 * @param string $css  The directory of the current CSS file.
	 *
	 * @param string $current_dir The directory of the current CSS file.
	 *
	 * @param string $doc_root The document root of the web site in which
	 * the CSS file resides (default = $_SERVER['DOCUMENT_ROOT']).
	 *
	 * @param array  $symlinks (default = array()) If the CSS file is stored in
	 *                         a symlink-ed directory, provide an array of link paths to
	 *                         target paths, where the link paths are within the document root. Because
	 *                         paths need to be normalized for this to work, use "//" to substitute
	 *                         the doc root in the link paths (the array keys). E.g.:
	 *                         <code>
	 *                           array('//symlink' => '/real/target/path') // unix
	 *                           array('//static' => 'D:\\staticStorage')  // Windows
	 *                         </code>.
	 *
	 * @return string
	 */
	public static function rewrite( $css, $current_dir, $doc_root = null, $symlinks = array() ) {
		self::$doc_root    = self::realpath(
			$doc_root ? $doc_root : $_SERVER['DOCUMENT_ROOT'] // Input var ok.
		);
		self::$current_dir = self::realpath( $current_dir );
		self::$symlinks    = array();

		// Normalize symlinks.
		foreach ( $symlinks as $link => $target ) {
			$link                    = ( '//' === $link )
				? self::$doc_root
				: str_replace( '//', self::$doc_root . '/', $link );
			$link                    = strtr( $link, '/', DIRECTORY_SEPARATOR );
			self::$symlinks[ $link ] = self::realpath( $target );
		}

		self::$debug_text .= 'docRoot    : ' . self::$doc_root . "\n"
							. 'currentDir : ' . self::$current_dir . "\n";
		if ( self::$symlinks ) {
			self::$debug_text .= 'symlinks : ' . var_export( self::$symlinks, 1 ) . "\n";
		}
		self::$debug_text .= "\n";

		$css = self::trim_urls( $css );

		// Rewrite.
		$css = preg_replace_callback(
			'/@import\\s+([\'"])(.*?)[\'"]/',
			array( self::$class_name, 'process_uri_cb' ),
			$css
		);
		$css = preg_replace_callback(
			'/url\s*\\(\\s*([^\\)\\s]+)\\s*\\)/',
			array( self::$class_name, 'process_uri_cb' ),
			$css
		);

		return $css;
	}

	/**
	 * In CSS content, prepend a path to relative URIs
	 *
	 * @param string $css  Content of CSS file.
	 *
	 * @param string $path The path to prepend.
	 *
	 * @return string
	 */
	public static function prepend( $css, $path ) {
		self::$prepend_path = $path;

		$css = self::trim_urls( $css );

		// Append.
		$css = preg_replace_callback(
			'/@import\\s+([\'"])(.*?)[\'"]/',
			array( self::$class_name, 'process_uri_cb' ),
			$css
		);
		$css = preg_replace_callback(
			'/url\\(\\s*([^\\)\\s]+)\\s*\\)/',
			array( self::$class_name, 'process_uri_cb' ),
			$css
		);

		self::$prepend_path = null;
		return $css;
	}

	/**
	 * Get a root relative URI from a file relative URI
	 *
	 * <code>
	 * URI_Rewriter::rewrite_relative(
	 *       '../img/hello.gif'
	 *     , '/home/user/www/css'  // path of CSS file
	 *     , '/home/user/www'      // doc root
	 * );
	 * // returns '/img/hello.gif'
	 *
	 * // example where static files are stored in a symlinked directory
	 * URI_Rewriter::rewrite_relative(
	 *       'hello.gif'
	 *     , '/var/staticFiles/theme'
	 *     , '/home/user/www'
	 *     , array('/home/user/www/static' => '/var/staticFiles')
	 * );
	 * // returns '/static/theme/hello.gif'
	 * </code>
	 *
	 * @param string $uri file Relative URI.
	 *
	 * @param string $real_current_dir The realpath of the current file's directory.
	 *
	 * @param string $real_doc_root The realpath of the site document root.
	 *
	 * @param array  $symlinks (default = array()) If the file is stored in
	 *                         a symlink-ed directory, provide an array of link paths to
	 *                         real target paths, where the link paths "appear" to be within the document
	 *                         root. E.g.:
	 *                         <code>
	 *                           array('/home/foo/www/not/real/path' => '/real/target/path') // unix
	 *                           array('C:\\htdocs\\not\\real' => 'D:\\real\\target\\path')  // Windows
	 *                         </code>.
	 *
	 * @return string
	 */
	public static function rewrite_relative( $uri, $real_current_dir, $real_doc_root, $symlinks = array() ) {
		// Prepend path with current dir separator (OS-independent).
		$path = strtr( $real_current_dir, '/', DIRECTORY_SEPARATOR )
				. DIRECTORY_SEPARATOR . strtr( $uri, '/', DIRECTORY_SEPARATOR );

		self::$debug_text .= "file-relative URI  : {$uri}\n"
							. "path prepended     : {$path}\n";

		// "unresolve" a symlink back to doc root.
		foreach ( $symlinks as $link => $target ) {
			if ( 0 === strpos( $path, $target ) ) {
				// Replace $target with $link.
				$path = $link . substr( $path, strlen( $target ) );

				self::$debug_text .= "symlink unresolved : {$path}\n";

				break;
			}
		}
		// Strip doc root.
		$path = substr( $path, strlen( $real_doc_root ) );

		self::$debug_text .= "docroot stripped   : {$path}\n";

		// Fix to root-relative URI.
		$uri = strtr( $path, '/\\', '//' );
		$uri = self::remove_dots( $uri );

		self::$debug_text .= "traversals removed : {$uri}\n\n";

		return $uri;
	}

	/**
	 * Remove instances of "./" and "../" where possible from a root-relative URI
	 *
	 * @param string $uri  URI to parse.
	 *
	 * @return string
	 */
	public static function remove_dots( $uri ) {
		$uri = str_replace( '/./', '/', $uri );
		// Inspired by patch from Oleg Cherniy.
		do {
			$uri = preg_replace( '@/[^/]+/\\.\\./@', '/', $uri, 1, $changed );
		} while ( $changed );

		return $uri;
	}

}
