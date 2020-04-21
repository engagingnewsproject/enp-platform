<?php

// Case-insensitive regex for whether a path is a static file
$regex_path_static_suffix = "\\.(?:jpe?g|gif|png|css|js|ico|zip|7z|tgz|gz|rar|bz2|do[ct][mx]?|xl[ast][bmx]?|exe|pdf|p[op][ast][mx]?|sld[xm]?|thmx?|txt|tar|midi?|wav|bmp|rtf|avi|mp\\d|mpg|iso|mov|djvu|dmg|flac|r70|mdf|chm|sisx|sis|flv|thm|bin|swf|cert|otf|ttf|eot|svgx?|woff2?|jar|class|log|web[pma]|ogv)";

// Regex for paths that should be cached for a long time, and irrespective of user agent.
$timthumb_script_regex = "/(?:php|[tT]im)?[tT]humb(?:nail)?\\.(?:php|bmp)";
$permacache_regex_list = array (
	"$timthumb_script_regex\$",
	"/ima?ge?\\.php\$",
	"[^a-zA-Z0-9](?:css|js|scripts?|style(?:sheet)?s?|j(?:ava)?scripts?)\\.php\$",	// many kinds of js and css PHP scripts look like this
	"/plugins/b?wp-minify/min",
    "/plugins/sidebartabs/styleSidebar_global\\.php\$",
    "/plugins/s2member/s2member-o\\.php\$",
	"/shopp?/images?/\\d+/?",
    "/gradient\\.php\$",
	"^/robots\\.txt\$",
);
$regex_is_path_dynamic_long_cache = join('|',$permacache_regex_list);

// Regex for non-path characters inside HTML/CSS attributes.
$regex_charlist_ends_uri = "'\"\\)";
$regex_uri_segment = "[^$regex_charlist_ends_uri]*";

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////	Original names and forms of these regular expressions.
/////	New code should use the variables from above this line because they're better documented and explained.
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$static_suffix_regex = "$regex_path_static_suffix\$";

$static_dirs_cdn_regex         = "/(?:wp-content/(?:themes|plugins|uploads|files|wptouch-data|gallery)|wp-includes|wp-admin|files|images|img|css|js|fancybox|assets)/";

$known_statics_args_regex = "(?:mcsf_action=main_css|ak_action=aktt_(?:css|js)|sjsl=|cf_action=cfmobi_admin_(?:js|css)|apipp_style=|action=shadowboxjs|s=jquery-comment-preview\\.js)";

$wpe_replace_siteurl_with_https = '(?<!\<span id="sample-permalink" tabindex="-1"\>)(?<!id="siteurl" value=")(?<!id="home" value=")';

/*
 * Pattern/replace for removing MyIsam from queries
 */
$wpe_find_myisam_in_create_regex = '/(.*ENGINE\s*=\s*[\'"]{0,1})MYISAM([\'"]{0,1})/i';
$wpe_replace_myisam_in_create_with_innodb = "$1INNODB$2";

class Patterns
{
	static public function build_http_to_https ($html, $blog_url)
	{
		// Strip the protocol
		$blog_url = preg_replace('(https?://)', '', $blog_url);
		$r = '#([\\s-]?src[\s]*=[\s]*[\'"])http://('.$blog_url.')(/?.*[\'"])#i';
		// Let's skip anything that's in a <textarea>
		$ignore_start = $ignore_end   = 0;
		if ( preg_match( "#<textarea.+?</textarea>#is", $html, $match, PREG_OFFSET_CAPTURE ) ) {
			$ignore_start = $match[0][1];
			$ignore_end   = $ignore_start + strlen( $match[0][0] );
			$html         = self::preg_replace_around( $r, "\$1https://".$blog_url."\$3", $html, $ignore_start, $ignore_end );
		} else {
			$html = preg_replace( $r, "\$1https://".$blog_url."\$3", $html );
		}
        	return $html;
	}

	// Performs a preg_replace(), but ignores the substring between the two ignore ends, exclusive.
	static public function preg_replace_around( $re, $repl, $src, $ignore_start, $ignore_end )
	{
		// trivial cases
		if ( $ignore_start >= $ignore_end )
			return preg_replace( $re, $repl, $src );
		// replace before and after and stitch together
		$a = preg_replace( $re, $repl, substr( $src, 0, $ignore_start ) );
		$b = substr( $src, $ignore_start, $ignore_end - $ignore_start );
		$c = preg_replace( $re, $repl, substr( $src, $ignore_end ) );
		return $a . $b . $c;
	}
}

// Given a regular expression which matches the path-part of a URL, returns a regular expression
// which matches the entire path + qargs part.
function get_uri_regex_from_path_regex( $re )
{
	$prefix = "^[^\\?]*?";
	if ( $re[0] == '#' ) $re = $re[0] . $prefix . substr($re,1);
	else $re = $prefix . $re;
	return str_replace("\$","(?:\\?|\$)",$re);
}

// Either add or remove protocol/domain parts from timthumb-style src-path references
function ec_modify_timthumb_src_urls( $html, $domain, $add_domain )
{
	global $timthumb_script_regex;

	if ( $add_domain ) {
        $html = preg_replace(
                "#\\b(src=[\"'](https?)://[^\"']+$timthumb_script_regex\\?src=)(?!http)/?#i", "\$1\$2://$domain/", $html
        );
	} else {
		$re_domain = preg_quote($domain);
        $html = preg_replace(
                "#\\b(src=[\"']https?://[^\"']+$timthumb_script_regex\\?src=)https?://{$re_domain}/#i", "\$1/", $html
        );
	}
	return $html;
}

// Given a map of domains -> CDN domains, returns a list of URL replacement rules which map those
// using our standard regexs for what things are supposed to go on a CDN.
// @param $map_domain_cdn mapping of domain -> CDN domain for transformations
function ec_get_cdn_replacement_rules( $map_domain_cdn )
{
	global $static_dirs_cdn_regex, $regex_is_path_dynamic_long_cache, $regex_path_static_suffix;
	global $regex_charlist_ends_uri, $regex_uri_segment, $known_statics_args_regex;

	// Trivial cases
	if ( empty($map_domain_cdn) ) return array();
	// Regex set
	$cdn_regexs = array(
		"#" . get_uri_regex_from_path_regex("^/[^/${regex_charlist_ends_uri}]+${regex_path_static_suffix}\$") . "#i",		// any static in the root
		"#${static_dirs_cdn_regex}.+${regex_path_static_suffix}#i",		// static in a known-static location
		"#" . get_uri_regex_from_path_regex($regex_is_path_dynamic_long_cache) . "#",	// known-dynamic-cacheable paths
		"#\\?.*${known_statics_args_regex}#",		// known-dynamic-cacheable query args
	);
	$rules = array();
	foreach ( $map_domain_cdn as $src_domain => $cdn_domain ) {
		foreach ( $cdn_regexs as $uri_re ) {
			$rules[] = array (
				'src_domain' => $src_domain,
				'src_uri' => $uri_re,
				'dst_domain' => $cdn_domain,
			);
		}
	}
	return $rules;
}

// Converting the site-config 'cdn_regexs' array into CDN replacements
function ec_add_cdn_replacement_rules_from_cdn_regexs( &$rules, $cdn_regexs, $src_domain, $cdn_domain )
{
	if ( empty($cdn_regexs) ) return;		// common case when config isn't there
	foreach ( $cdn_regexs as $re ) {
		$rules[] = array (
			'src_domain' => $src_domain,
			'src_uri' => '#' . $re . '#',
			'dst_domain' => $cdn_domain,
		);
	}
}

// Replaces URLs in a block of HTML, adhereing to a variety of rules
// @param $rules array of replacement rules as a 'src_domain' of domain to find, 'src_uri' to match the absolute path portion, 'dst_domain' to specify the
//			new domain to replace with, 'dst_prefix' as an optional path prefix to pre-pend to the path.
// @param $default_domain if non-null, any absolute paths should be considered this domain for the purposes of rule-application.
function ec_url_replacements( $html, $rules, $default_domain = null, $is_ssl = false )
{
	global $regex_charlist_ends_uri, $regex_uri_segment;
	// Trivial cases
	if ( ! $html || strlen($html) < 5 ) return $html;
	if ( empty($rules) ) return $html;

	// Find all the things which are URLs at all
	$re_start = "\\b(?:(?:src|href|value|data)\\s*=|\\burl\\s*)[\\('\"\\s]+";
	if ( ! preg_match_all( "#${re_start}((?:https?:)?/?/)([^/${regex_charlist_ends_uri}]+)(${regex_uri_segment})#i", $html, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) )
		return $html;
//print_r($matches);

	// Process each thing we need to replace, determining the thing to replace with.
	$pieces = array();
	$next_offset = 0;
	foreach ( $matches as $m ) {

		// Load info
		$offset = $m[1][1];
		$end = $m[3][1] + strlen($m[3][0]);
		$protocol = $m[1][0];
		$domain = $m[2][0];
		$path = $m[3][0];

		// If this is an absolute path: If we have a default domain use it, otherwise skip this
		// because we can't resolve it against any rules.
		if ( $protocol == "/" ) {
			if ( $default_domain ) {
				$path = '/' . $domain . $path;	// the path is composed of what is normally the hostname and the path
				$domain = $default_domain;		// set the domain
			}
			else
				continue;
		}
//print("[$protocol][$domain][$path]\n");
		// Protocol could be missing or just / or // at this point.
		if (strlen($protocol) < 6) {
			$protocol = $is_ssl ? 'https://' : 'http://';
		}

		// If the linked resource is https, but the page is not, don't CDN. Doing so would result in
		// the resource being served unsecured (or broken: https://help.wpengine.com/tickets/174180).
		// This is somewhat suboptimal, but it is safe. We are not handling the case where an insecure
		// page has a secure resource *and* the site has secure CDN enabled.
		if ((! $is_ssl) && ('https://' == $protocol)) {
			continue;
		}

		// Run rules to find a replacement, if any
		$replacement = null;
		foreach ( $rules as &$r ) {
			if ( strcasecmp($r['src_domain'],$domain) == 0 && ( !isset($r['src_uri']) || preg_match($r['src_uri'],$path) ) ) {
				// Rule matches!
				$replacement = "$protocol" . $r['dst_domain'] . (isset($r['dst_prefix']) ? $r['dst_prefix'] : "") . $path;
				break;
			}
		}

		// If there's a replacement, accumulate the various strings.
		if ( $replacement ) {
			if ( $offset > $next_offset )
				$pieces[] = substr($html,$next_offset,$offset-$next_offset);
			$pieces[] = $replacement;
			$next_offset = $end;
		}
	}

	// Trivial case
	if ( ! $next_offset ) return $html;

	// Put all the pieces together.
	$pieces[] = substr($html,$next_offset);			// accumulate the last piece
//print_r($pieces);
	$html = join("",$pieces);

	// Finished
	return $html;
}

// Given a PHP backtrace array, finds the first element which is NOT part of a core routine,
// then returns the trace from that point forward.  If $backtrace is null, uses the current
// backtrace from the time of this call.
function ec_get_non_core_backtrace( $backtrace = null )
{
	// Make sure we have a backtrace of some kind
	if ( ! $backtrace ) {
	    // Save lots of memory by not populating the function arguments.
	    // We did see large (>500k) memory consumption by this function on site "edd."
	    // Protected against values not being defined, because some are added in later versions of PHP.
	    $backtrace_options = defined('DEBUG_BACKTRACE_IGNORE_ARGS') ? DEBUG_BACKTRACE_IGNORE_ARGS : 0;
		$backtrace = debug_backtrace( $backtrace_options );
	}

	// Scan for things not in core and not in our own plugin
	for( $k=0 ; $k<count($backtrace) ; $k++ ) {
		if ( ! isset($backtrace[$k]["file"]) ) continue;
		if ( preg_match("#/(?:wp-(?:admin|includes)/|wp-(?:load|blog-header)\\.php|mu-plugins\\.php|wpengine-common/|db\\.php)#", $backtrace[$k]["file"]) )
			continue;
		break;
	}
	return array_slice( $backtrace, $k, 5 );
}
