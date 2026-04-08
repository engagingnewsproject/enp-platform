<?php
/**
 * Turns quiz metadata (title, views, embed sites, owner email for burst rule, etc.) into a risk score and tier.
 *
 * This is the PHP version of the offline “Phase 1” script in the ENP quiz plugin’s LOCAL folder;
 * rules and weights live in JSON and text files next to this code. Disposable-domain matching uses embed URLs.
 *
 * @package Engage\Admin\QuizSpamAnalysis
 */

namespace Engage\Admin\QuizSpamAnalysis;

/**
 * Reads config and disposable-domain lists from disk, then scores one quiz “row” at a time.
 *
 * Disposable / extra-risk domains are matched against **third-party embed site URLs** for the quiz
 * (ENP embed_site rows), not the WordPress owner email. It does not read question text.
 */
class QuizSpamAnalyzer {

	/**
	 * Parsed thresholds.json (weights, tier cutoffs, allowlist).
	 *
	 * @var array<string, mixed>
	 */
	private array $cfg;

	/**
	 * Trusted owner-email domains: `allowlist_domains` in JSON plus `trusted_domains.txt` (deduped, lowercase).
	 *
	 * @var array<int, string>
	 */
	private array $allowlist_domains;

	/**
	 * Domains treated as throwaway signups, for fast lookup.
	 *
	 * @var array<string, bool>
	 */
	private array $disposable_domains;

	/**
	 * Loads rule settings, trusted/disposable domain lists from a folder on disk.
	 *
	 * @param string $data_dir Absolute path to directory containing thresholds.json and domain list files.
	 */
	public function __construct( string $data_dir ) {
		$config_path = trailingslashit( $data_dir ) . 'thresholds.json';
		$raw         = file_get_contents( $config_path );
		if ( false === $raw ) {
			throw new \RuntimeException( 'QuizSpamAnalyzer: cannot read ' . $config_path );
		}
		$decoded = json_decode( $raw, true );
		if ( ! is_array( $decoded ) ) {
			throw new \RuntimeException( 'QuizSpamAnalyzer: invalid JSON in thresholds.json' );
		}
		$this->cfg = $decoded;
		$json_allow = isset( $this->cfg['allowlist_domains'] ) && is_array( $this->cfg['allowlist_domains'] )
			? $this->cfg['allowlist_domains']
			: array();
		$this->allowlist_domains  = self::merge_allowlist_sources(
			$json_allow,
			trailingslashit( $data_dir ) . 'trusted_domains.txt'
		);
		$this->disposable_domains = self::load_domain_set(
			trailingslashit( $data_dir ) . 'disposable_email_domains.txt',
			trailingslashit( $data_dir ) . 'extra_risk_domains.txt'
		);
	}

	/**
	 * Merges JSON allowlist entries with domains from trusted_domains.txt; skips duplicates case-insensitively.
	 *
	 * JSON entries are listed first; file lines use the same format as load_domain_set() (# comments OK).
	 *
	 * @param array<int, mixed> $from_json Values from thresholds.json allowlist_domains.
	 * @param string            $trusted_path Absolute path to trusted_domains.txt (ignored if unreadable).
	 * @return array<int, string> Hostnames passed to is_domain_allowlisted().
	 */
	public static function merge_allowlist_sources( array $from_json, string $trusted_path ): array {
		$file_domains = array_keys( self::load_domain_set( $trusted_path ) );
		$merged       = array();
		$seen         = array();
		foreach ( $from_json as $entry ) {
			$d = strtolower( trim( (string) $entry ) );
			if ( '' === $d || isset( $seen[ $d ] ) ) {
				continue;
			}
			$seen[ $d ] = true;
			$merged[]   = $d;
		}
		foreach ( $file_domains as $d ) {
			if ( isset( $seen[ $d ] ) ) {
				continue;
			}
			$seen[ $d ] = true;
			$merged[]   = $d;
		}
		return $merged;
	}

	/**
	 * Builds one combined list of “bad” email domains from one or more text files (one domain per line).
	 *
	 * Lines starting with # are ignored. Result keys are lowercase for quick isset() checks.
	 *
	 * @param string ...$paths Absolute file paths to merge.
	 * @return array<string, bool> Map domain => true.
	 */
	public static function load_domain_set( string ...$paths ): array {
		$domains = array();
		foreach ( $paths as $path ) {
			if ( ! is_readable( $path ) ) {
				continue;
			}
			$lines = file( $path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
			if ( ! is_array( $lines ) ) {
				continue;
			}
			foreach ( $lines as $line ) {
				$line = strtolower( trim( $line ) );
				if ( '' === $line || str_starts_with( $line, '#' ) ) {
					continue;
				}
				$domains[ $line ] = true;
			}
		}
		return $domains;
	}

	/**
	 * Counts how many quizzes share the same owner email on the same calendar day (spots bulk signups).
	 *
	 * @param array<int, array<string, string>> $rows Each row needs owner_email and quiz_created_at (export-style strings).
	 * @return array<string, int> Composite key "email|Y-m-d" => occurrence count.
	 */
	public static function build_email_day_counts( array $rows ): array {
		$counts = array();
		foreach ( $rows as $row ) {
			$email = isset( $row['owner_email'] ) ? strtolower( trim( (string) $row['owner_email'] ) ) : '';
			$dt    = self::parse_dt( $row['quiz_created_at'] ?? null );
			if ( '' === $email || null === $dt ) {
				continue;
			}
			$key = $email . '|' . $dt->format( 'Y-m-d' );
			$counts[ $key ] = ( $counts[ $key ] ?? 0 ) + 1;
		}
		return $counts;
	}

	/**
	 * Turns a database/export datetime string into a real date object, or null if it is empty or invalid.
	 *
	 * @param string|null $value Typical formats: Y-m-d H:i:s (optional microseconds).
	 * @return \DateTimeImmutable|null Parsed instant or null.
	 */
	public static function parse_dt( ?string $value ): ?\DateTimeImmutable {
		if ( null === $value ) {
			return null;
		}
		$s = trim( $value );
		if ( '' === $s || strtoupper( $s ) === 'NULL' ) {
			return null;
		}
		foreach ( array( 'Y-m-d H:i:s', 'Y-m-d H:i:s.u' ) as $fmt ) {
			$d = \DateTimeImmutable::createFromFormat( $fmt, $s );
			if ( $d instanceof \DateTimeImmutable ) {
				return $d;
			}
		}
		return null;
	}

	/**
	 * Parses a number from CSV/DB text; treats blank or “NULL” as zero so rules do not break.
	 *
	 * @param string|null $value Raw cell value.
	 * @param int         $default Fallback when empty or non-numeric.
	 * @return int Parsed integer.
	 */
	public static function safe_int( ?string $value, int $default = 0 ): int {
		if ( null === $value ) {
			return $default;
		}
		$s = trim( (string) $value );
		if ( '' === $s || strtoupper( $s ) === 'NULL' ) {
			return $default;
		}
		if ( is_numeric( $s ) ) {
			return (int) $s;
		}
		return $default;
	}

	/**
	 * Extracts the part after @ from an email (e.g. gmail.com) in lowercase, for domain blocklist checks.
	 *
	 * @return string Domain or empty if address is missing @.
	 */
	public static function owner_email_domain( string $email ): string {
		$e = strtolower( trim( $email ) );
		if ( ! str_contains( $e, '@' ) ) {
			return '';
		}
		$parts = explode( '@', $e, 2 );
		return trim( $parts[1] ?? '' );
	}

	/**
	 * Host part of a stored embed_site_url for blocklist checks (scheme added when missing).
	 */
	public static function host_from_embed_url( string $url ): string {
		$url = trim( $url );
		if ( '' === $url ) {
			return '';
		}
		if ( ! preg_match( '#^[a-z][a-z0-9+.-]*://#i', $url ) ) {
			$url = 'http://' . ltrim( $url, '/' );
		}
		$host = \wp_parse_url( $url, PHP_URL_HOST );
		if ( ! is_string( $host ) || '' === $host ) {
			return '';
		}
		return strtolower( (string) preg_replace( '#^www\.#i', '', $host ) );
	}

	/**
	 * Hostname plus a naive registrable suffix (last two labels) for lists keyed by eTLD+1-style domains.
	 *
	 * @return string[] Unique candidates, lowercase.
	 */
	public static function candidate_domains_for_disposable( string $host ): array {
		$host = strtolower( trim( $host ) );
		if ( '' === $host ) {
			return array();
		}
		$out   = array( $host );
		$parts = explode( '.', $host );
		if ( count( $parts ) >= 2 ) {
			$out[] = $parts[ count( $parts ) - 2 ] . '.' . $parts[ count( $parts ) - 1 ];
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * Returns true if the domain should never be flagged as disposable (your org, partners, etc.).
	 *
	 * Matches exact host or subdomains (e.g. news.utexas.edu vs utexas.edu).
	 *
	 * @param string             $domain    Host part of email, usually lowercase.
	 * @param array<int, string> $allowlist Trusted hostnames (e.g. merged JSON + trusted_domains.txt).
	 * @return bool True if domain is trusted.
	 */
	public static function is_domain_allowlisted( string $domain, array $allowlist ): bool {
		$d = strtolower( trim( $domain ) );
		if ( '' === $d ) {
			return false;
		}
		foreach ( $allowlist as $allowed ) {
			$a = strtolower( trim( (string) $allowed ) );
			if ( '' === $a ) {
				continue;
			}
			if ( $d === $a || str_ends_with( $d, '.' . $a ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Measures the quiz title: how long it is, word count, and how “readable” it is (letters vs symbols).
	 *
	 * Low “readable” ratio often means keyboard mash or spammy punctuation.
	 *
	 * @return array{0: int, 1: int, 2: float} char length, word count, ratio of alnum+space to length.
	 */
	public static function title_metrics( ?string $title ): array {
		if ( null === $title ) {
			return array( 0, 0, 1.0 );
		}
		$t = trim( $title );
		$n = strlen( $t );
		if ( 0 === $n ) {
			return array( 0, 0, 1.0 );
		}
		$good  = 0;
		$len   = strlen( $t );
		for ( $i = 0; $i < $len; $i++ ) {
			$c = $t[ $i ];
			if ( ctype_alnum( $c ) || ctype_space( $c ) ) {
				++$good;
			}
		}
		$words = str_word_count( $t );
		return array( $n, $words, $good / $n );
	}

	/**
	 * Runs all rules on one quiz and returns points, tier, and which rules fired (for the admin table).
	 *
	 * @param array<string, string> $row              Row must include embed_site_urls (newline-separated embed URLs), embed_row_count, owner_email (for burst only), and quiz_* fields.
	 * @param array<string, int>    $email_day_counts Output of build_email_day_counts(); keys email|Y-m-d.
	 * @param \DateTimeInterface    $reference_dt     Current time used to decide “old enough” for zero-engagement rule.
	 * @return array{risk_score: int, risk_tier: string, rule_hits: array<int, string>}
	 */
	public function analyze_row( array $row, array $email_day_counts, \DateTimeInterface $reference_dt ): array {
		$hits  = array();
		$score = 0;

		$weights = isset( $this->cfg['rule_weights'] ) && is_array( $this->cfg['rule_weights'] )
			? $this->cfg['rule_weights']
			: array();

		$title = $row['quiz_title'] ?? '';
		list($t_len, , $alnum_ratio) = self::title_metrics( $title );
		$max_short = (int) ( $this->cfg['title_very_short_max_len'] ?? 3 );
		$low_read  = (float) ( $this->cfg['title_low_readable_max_ratio'] ?? 0.55 );

		$email     = trim( (string) ( $row['owner_email'] ?? '' ) );
		$allowlist = $this->allowlist_domains;

		$deleted  = self::safe_int( $row['quiz_is_deleted'] ?? '0', 0 );
		$status   = strtolower( trim( (string) ( $row['quiz_status'] ?? '' ) ) );
		$embed_n  = self::safe_int( $row['embed_row_count'] ?? '0', 0 );
		$views    = self::safe_int( $row['quiz_views'] ?? '0', 0 );
		$starts   = self::safe_int( $row['quiz_starts'] ?? '0', 0 );
		$finishes = self::safe_int( $row['quiz_finishes'] ?? '0', 0 );

		$created   = self::parse_dt( $row['quiz_created_at'] ?? null );
		$email_key = strtolower( $email );
		$day_key   = null;
		if ( $created ) {
			$day_key = $created->format( 'Y-m-d' );
		}
		$burst_key = ( '' !== $email_key && null !== $day_key ) ? $email_key . '|' . $day_key : '';
		$burst_n   = ( '' !== $burst_key && isset( $email_day_counts[ $burst_key ] ) )
			? (int) $email_day_counts[ $burst_key ]
			: 0;
		$burst_threshold = (int) ( $this->cfg['email_burst_threshold'] ?? 4 );

		$embed_blob = trim( (string) ( $row['embed_site_urls'] ?? '' ) );
		if ( '' !== $embed_blob ) {
			$lines            = preg_split( '/\r\n|\r|\n/', $embed_blob );
			$disposable_found = false;
			if ( is_array( $lines ) ) {
				foreach ( $lines as $line ) {
					$line = trim( (string) $line );
					if ( '' === $line ) {
						continue;
					}
					if ( function_exists( 'engage_quiz_is_local_embed_url' ) && engage_quiz_is_local_embed_url( $line ) ) {
						continue;
					}
					$host = self::host_from_embed_url( $line );
					if ( '' === $host || self::is_domain_allowlisted( $host, $allowlist ) ) {
						continue;
					}
					foreach ( self::candidate_domains_for_disposable( $host ) as $cand ) {
						if ( '' === $cand || self::is_domain_allowlisted( $cand, $allowlist ) ) {
							continue;
						}
						if ( isset( $this->disposable_domains[ $cand ] ) ) {
							$disposable_found = true;
							break 2;
						}
					}
				}
			}
			if ( $disposable_found ) {
				$hits[] = 'disposable_domain';
				$score += (int) ( $weights['disposable_domain'] ?? 0 );
			}
		}

		if ( $t_len > 0 && $t_len <= $max_short ) {
			$hits[] = 'short_title';
			$score += (int) ( $weights['short_title'] ?? 0 );
		}

		if ( $t_len > 0 && $alnum_ratio < $low_read ) {
			$hits[] = 'low_readable_title';
			$score += (int) ( $weights['low_readable_title'] ?? 0 );
		}

		if ( 0 === $deleted && 'published' === $status && 0 === $embed_n ) {
			$hits[] = 'published_no_embeds';
			$score += (int) ( $weights['published_no_embeds'] ?? 0 );
		}

		$min_age = (int) ( $this->cfg['zero_engagement_min_age_days'] ?? 1 );
		$age_ok  = false;
		if ( $created ) {
			$ref  = \DateTimeImmutable::createFromInterface( $reference_dt );
			$diff = $ref->getTimestamp() - $created->getTimestamp();
			$age_ok = ( $diff / 86400.0 ) >= (float) $min_age;
		}
		if ( 0 === $deleted && 'published' === $status && 0 === $views && 0 === $starts && 0 === $finishes && $age_ok ) {
			$hits[] = 'zero_engagement';
			$score += (int) ( $weights['zero_engagement'] ?? 0 );
		}

		if ( $burst_n >= $burst_threshold ) {
			$hits[] = 'email_burst';
			$score += (int) ( $weights['email_burst'] ?? 0 );
		}

		$tier = $this->compute_tier( $hits, $score );

		return array(
			'risk_score' => $score,
			'risk_tier'  => $tier,
			'rule_hits'  => $hits,
		);
	}

	/**
	 * Buckets the quiz into low / medium / high for sorting and filters in wp-admin.
	 *
	 * Combines total score with “strong” rules (e.g. disposable embed host) per thresholds.json tier section.
	 *
	 * @param array<int, string> $hits Rule ids that fired, in order.
	 * @param int                $score Weighted sum from rule_weights.
	 * @return string One of low, medium, high.
	 */
	public function compute_tier( array $hits, int $score ): string {
		$tier_cfg = isset( $this->cfg['tier'] ) && is_array( $this->cfg['tier'] )
			? $this->cfg['tier']
			: array();

		$weak     = isset( $this->cfg['weak_rules'] ) && is_array( $this->cfg['weak_rules'] )
			? array_flip( $this->cfg['weak_rules'] )
			: array();
		$moderate = isset( $this->cfg['moderate_rules'] ) && is_array( $this->cfg['moderate_rules'] )
			? array_flip( $this->cfg['moderate_rules'] )
			: array();
		$strong   = isset( $this->cfg['strong_rules'] ) && is_array( $this->cfg['strong_rules'] )
			? array_flip( $this->cfg['strong_rules'] )
			: array();

		$hit_set       = array_flip( $hits );
		$weak_hits     = count( array_intersect_key( $hit_set, $weak ) );
		$moderate_hits = count( array_intersect_key( $hit_set, $moderate ) );

		if ( ! empty( $tier_cfg['high_if_any_strong'] ) && count( array_intersect_key( $hit_set, $strong ) ) > 0 ) {
			return 'high';
		}
		if ( $weak_hits >= (int) ( $tier_cfg['high_if_weak_hits_at_least'] ?? 999 ) ) {
			return 'high';
		}
		if ( $score >= (int) ( $tier_cfg['score_high'] ?? 999 ) ) {
			return 'high';
		}

		if ( ! empty( $tier_cfg['medium_min_weak_and_moderate'] ) && $moderate_hits >= 1 && $weak_hits >= 1 ) {
			return 'medium';
		}
		if ( $score >= (int) ( $tier_cfg['score_medium'] ?? 999 ) ) {
			return 'medium';
		}

		return 'low';
	}

	/**
	 * Where this theme ships thresholds.json and disposable domain files (next to this class).
	 *
	 * @return string Absolute filesystem path ending without trailing slash.
	 */
	public static function default_data_dir(): string {
		return dirname( __FILE__ ) . '/data';
	}
}
