<?php
/**
 * Helpers for ENP quiz embed site URLs in admin UI.
 *
 * @package Engage
 */

/**
 * Normalized host for this WordPress install (lowercase, no leading www.).
 *
 * @return string Empty string if the host cannot be determined.
 */
function engage_quiz_get_local_embed_host(): string {
	$host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
	if ( ! is_string( $host ) || $host === '' ) {
		return '';
	}
	return strtolower( preg_replace( '#^www\.#i', '', $host ) );
}

/**
 * Whether a URL’s host is this site (on-site quiz embed), including www vs non-www.
 *
 * @param string $url Full or site-root URL (e.g. embed_site_url or embed_quiz_url).
 */
function engage_quiz_is_local_embed_url( string $url ): bool {
	$local = engage_quiz_get_local_embed_host();
	if ( $local === '' ) {
		return false;
	}
	$host = wp_parse_url( $url, PHP_URL_HOST );
	if ( ! is_string( $host ) || $host === '' ) {
		return false;
	}
	$host = strtolower( preg_replace( '#^www\.#i', '', $host ) );
	return $host === $local;
}

/**
 * Drop embed_site rows that point at this WordPress host so admin lists omit on-site embeds.
 *
 * @param object[] $rows Rows with an embed_site_url property.
 * @return object[] Re-indexed array.
 */
function engage_quiz_filter_external_embed_site_rows( array $rows ): array {
	return array_values(
		array_filter(
			$rows,
			static function ( $row ) {
				return isset( $row->embed_site_url )
					&& ! engage_quiz_is_local_embed_url( (string) $row->embed_site_url );
			}
		)
	);
}

/**
 * Drop URL strings whose host matches this site (for sync / meta that stores full page URLs).
 *
 * @param string[] $urls
 * @return string[] Re-indexed array.
 */
function engage_quiz_filter_external_embed_url_strings( array $urls ): array {
	return array_values(
		array_filter(
			$urls,
			static function ( $url ) {
				return ! engage_quiz_is_local_embed_url( (string) $url );
			}
		)
	);
}
