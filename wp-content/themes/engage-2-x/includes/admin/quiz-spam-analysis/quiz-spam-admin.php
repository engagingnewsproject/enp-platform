<?php
/**
 * WordPress admin pieces for quiz spam triage: run analysis, show results on the list, browse embed sites.
 *
 * Hooks: Quizzes list screen (button, columns, sort, filter), chunked HTTP analysis, and Quizzes ▸ Embed Sites.
 * Requires manage_options for analysis and embed screens; stores scores in post meta on the quiz CPT.
 *
 * @package Engage
 */

use Engage\Admin\QuizSpamAnalysis\QuizSpamAnalyzer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** How many quiz posts to score per DB batch inside one HTTP request. */
const ENGAGE_QUIZ_SPAM_CHUNK = 80;
/**
 * How many DB batches to run per request before redirecting again.
 * Admin runs this at admin_init before any HTML, so multiple HTTP redirects feel like “nothing happens”;
 * batching several chunks here cuts round-trips while staying under typical PHP timeouts.
 */
const ENGAGE_QUIZ_SPAM_MAX_INNER_BATCHES = 15;
/** Post meta: numeric points from all fired rules. */
const ENGAGE_QUIZ_META_RISK_SCORE  = '_enp_quiz_risk_score';
/** Post meta: low | medium | high for filters and sorting. */
const ENGAGE_QUIZ_META_RISK_TIER   = '_enp_quiz_risk_tier';
/** Post meta: JSON array of rule id strings (e.g. disposable_domain = risky embed site host). */
const ENGAGE_QUIZ_META_RULE_HITS   = '_enp_quiz_rule_hits';
/** Post meta: ISO 8601 timestamp of last successful analysis for this post. */
const ENGAGE_QUIZ_META_ANALYZED_AT = '_enp_quiz_spam_analyzed_at';
/** Post meta: JSON array of disposable/extra-risk embed hits (url, host, matched_domain); absent when none. */
const ENGAGE_QUIZ_META_SPAM_EMBED_MATCHES = '_enp_quiz_spam_embed_matches';

require_once __DIR__ . '/spam-user-quiz-actions.php';

/**
 * Wires all actions and filters once; called at the bottom of this file after functions are defined.
 *
 * Technical: registers admin_notices (finished + error), admin_init (priority 1), list-table filters, pre_get_posts, parse_query, submenu.
 * List action buttons live in manage-quizzes.php.
 */
function engage_quiz_spam_admin_init(): void {
	add_action( 'admin_notices', 'engage_quiz_spam_finished_notice' );
	add_action( 'admin_notices', 'engage_quiz_spam_error_notice' );
	add_action( 'admin_notices', 'engage_quiz_spam_disposable_embed_export_notice' );
	add_action( 'admin_init', 'engage_quiz_spam_handle_analyze_request', 1 );
	add_action( 'admin_init', 'engage_quiz_spam_handle_export_disposable_embeds', 1 );
	add_filter( 'manage_edit-quiz_columns', 'engage_quiz_spam_add_columns', 25 );
	add_action( 'manage_quiz_posts_custom_column', 'engage_quiz_spam_column_content', 10, 2 );
	add_filter( 'manage_edit-quiz_sortable_columns', 'engage_quiz_spam_sortable_columns' );
	add_action( 'pre_get_posts', 'engage_quiz_spam_pre_get_posts' );
	add_action( 'restrict_manage_posts', 'engage_quiz_spam_restrict_posts' );
	add_action( 'parse_query', 'engage_quiz_spam_parse_query' );
	add_action( 'admin_menu', 'engage_quiz_spam_embed_sites_submenu' );
	add_action( 'load-quiz_page_engage-quiz-embed-sites', 'engage_quiz_embed_sites_load_screen' );
	add_filter( 'set_screen_option_embed_sites_per_page', 'engage_quiz_embed_sites_set_per_page_option', 10, 3 );
	add_action( 'admin_menu', 'engage_quiz_spam_user_quizzes_submenu' );
	add_action( 'load-quiz_page_engage-quiz-spam-user-quizzes', 'engage_quiz_spam_user_quizzes_load_screen' );
	add_filter( 'set_screen_option_spam_user_quizzes_per_page', 'engage_quiz_spam_user_quizzes_set_per_page_option', 10, 3 );
	add_action( 'admin_notices', 'engage_quiz_spam_user_quizzes_admin_notices' );
}

/**
 * Layman: Registers “how many rows per page” for the Embed Sites screen so Screen Options works like other list tables.
 */
function engage_quiz_embed_sites_load_screen(): void {
	add_screen_option(
		'per_page',
		array(
			'label'   => __( 'Embed sites per page', 'engage' ),
			'default' => 20,
			'option'  => 'embed_sites_per_page',
		)
	);
	add_action( 'admin_footer', 'engage_quiz_embed_sites_footer_search_enter_fix', 5 );
}

/**
 * Layman: Pressing Enter in the URL search was the same as pressing the first submit button in the form—the bulk “Apply” control—so WordPress ran the bulk-action guard and showed “select at least one item” if Export (or any real bulk action) was selected.
 *
 * Technical: `common.js` listens for `submitter.name === 'bulk_action'`; HTML implicit submit uses the first submit button. Rebind Enter on `input[name="s"]` to programmatically activate `#search-submit` so `submitter` is the search control.
 */
function engage_quiz_embed_sites_footer_search_enter_fix(): void {
	$screen = get_current_screen();
	if ( ! $screen || 'quiz_page_engage-quiz-embed-sites' !== $screen->id ) {
		return;
	}
	?>
<script>
(function () {
	var form = document.getElementById( 'engage-embed-sites-filter' );
	if ( ! form ) {
		return;
	}
	var searchInput = form.querySelector( 'input[name="s"]' );
	var searchBtn   = document.getElementById( 'search-submit' );
	if ( searchInput && searchBtn ) {
		searchInput.addEventListener( 'keydown', function ( e ) {
			if ( e.key !== 'Enter' ) {
				return;
			}
			e.preventDefault();
			searchBtn.click();
		} );
	}
})();
</script>
	<?php
}

/**
 * Layman: Saves the per-page choice from Screen Options into user meta (same rules as core lists: 1–999).
 *
 * @param mixed                 $_screen_option Prior value from the filter chain (unused).
 * @param string                $_option        Option name (unused; always embed_sites_per_page).
 * @param int|string            $value          Submitted value.
 * @return int|false           Integer to save, or false to abort.
 */
function engage_quiz_embed_sites_set_per_page_option( $_screen_option, $_option, $value ) {
	$v = (int) $value;
	if ( $v < 1 || $v > 999 ) {
		return false;
	}
	return $v;
}

/**
 * Layman: User-specific key for progress data while a long “Analyse Quizzes” run is in flight.
 *
 * @return string Transient name for this user’s progress snapshot.
 */
function engage_quiz_spam_progress_transient_key(): string {
	return 'engage_qs_spam_prog_' . get_current_user_id();
}

/**
 * Layman: User-specific key for a one-shot error message after analysis fails mid-run.
 *
 * @return string Transient name for the error text.
 */
function engage_quiz_spam_error_transient_key(): string {
	return 'engage_qs_spam_err_' . get_current_user_id();
}

/**
 * Layman: Counts how many quiz posts exist that have ENP sync meta (same pool the analyzer walks).
 *
 * Technical: WP_Query with meta _enp_quiz_id EXISTS; uses found_posts.
 */
function engage_quiz_spam_count_synced_quiz_posts(): int {
	$q = new WP_Query(
		array(
			'post_type'      => 'quiz',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => '_enp_quiz_id',
					'compare' => 'EXISTS',
				),
			),
		)
	);

	return (int) $q->found_posts;
}

/**
 * Layman: Reads total quiz count stored when the run started (for progress labels). Falls back to 0 if missing.
 *
 * @return int Total posts from transient, or 0.
 */
function engage_quiz_spam_get_progress_total(): int {
	$data = get_transient( engage_quiz_spam_progress_transient_key() );
	if ( ! is_array( $data ) || ! isset( $data['total'] ) ) {
		return 0;
	}

	return max( 0, (int) $data['total'] );
}

/**
 * Layman: Sends a small HTML page so the browser shows “still working” between batch redirects instead of a blank load.
 *
 * Technical: 200 response, nocache_headers, optional meta refresh as fallback if JS is off; $is_starting=true for the first hop after clicking Analyse.
 *
 * @param int    $processed    Number of list positions advanced (next query offset).
 * @param string $continue_url Absolute URL for the next batch (must already pass wp_nonce_url).
 * @param int    $total        Denominator for progress; at least 1 for bar math.
 * @param bool   $is_starting  True on the initial “0 of N” screen before the first heavy batch.
 */
function engage_quiz_spam_render_analyze_progress_page( int $processed, string $continue_url, int $total, bool $is_starting = false ): void {
	nocache_headers();
	header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );

	$total_for_bar = max( 1, $total );
	$pct           = min( 100, (int) round( 100 * min( $processed, $total_for_bar ) / $total_for_bar ) );
	$esc_url       = esc_url( $continue_url, array( 'http', 'https' ) );
	$json_url      = wp_json_encode( $continue_url, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );

	if ( $is_starting ) {
		/* translators: %d: total quiz posts to analyze */
		$line = sprintf( __( 'Starting analysis… up to %d quiz posts will be scored.', 'engage' ), $total );
	} else {
		/* translators: 1: processed count, 2: total quiz posts */
		$line = sprintf( __( 'Analyzing quizzes… %1$d of %2$d processed so far.', 'engage' ), min( $processed, $total_for_bar ), $total_for_bar );
	}

	$hint     = __( 'This page will reload automatically to continue. You can leave this tab open until it finishes.', 'engage' );
	$esc_line = esc_html( $line );
	$esc_hint = esc_html( $hint );

	$html_lang = str_replace( '_', '-', get_locale() );
	echo '<!DOCTYPE html><html lang="' . esc_attr( $html_lang ) . '"><head>';
	echo '<meta charset="' . esc_attr( get_bloginfo( 'charset' ) ) . '">';
	echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
	echo '<meta http-equiv="refresh" content="3;url=' . esc_attr( $esc_url ) . '">';
	echo '<title>' . esc_html__( 'Quiz analysis in progress', 'engage' ) . '</title>';
	echo '<style>body{margin:40px auto;max-width:520px;font:14px/1.5-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:#f0f0f1;color:#1d2327}a{color:#2271b1}.bar{height:10px;background:#c3c4c7;border-radius:3px;overflow:hidden;margin:16px 0}.bar>span{display:block;height:100%;background:#2271b1;width:' . (int) $pct . '%;transition:width .2s}</style>';
	echo '</head><body>';
	echo '<h1>' . esc_html__( 'Quiz spam analysis', 'engage' ) . '</h1>';
	echo '<p>' . $esc_line . '</p>';
	echo '<div class="bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . (int) $pct . '"><span></span></div>';
	echo '<p style="color:#50575e;font-size:13px">' . $esc_hint . '</p>';
	echo '<p><a href="' . $esc_url . '">' . esc_html__( 'Continue now if this page does not advance.', 'engage' ) . '</a></p>';
	echo '<script>window.location.replace(' . $json_url . ');</script>';
	echo '</body></html>';
}

/**
 * Layman: Aborts a run, shows an error on the quiz list, and drops progress state.
 *
 * @param string $message Human-readable message for the notice.
 */
function engage_quiz_spam_abort_analyze_with_error( string $message ): void {
	delete_transient( engage_quiz_spam_progress_transient_key() );
	set_transient( engage_quiz_spam_error_transient_key(), $message, 300 );
	wp_safe_redirect(
		add_query_arg(
			array(
				'post_type'           => 'quiz',
				'engage_spam_error' => '1',
			),
			admin_url( 'edit.php' )
		)
	);
	exit;
}

/**
 * Admin notice when engage_spam_error=1 is present after a failed analysis hop.
 */
function engage_quiz_spam_error_notice(): void {
	if ( ! isset( $_GET['engage_spam_error'] ) || '1' !== $_GET['engage_spam_error'] ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || 'edit-quiz' !== $screen->id || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$msg = get_transient( engage_quiz_spam_error_transient_key() );
	delete_transient( engage_quiz_spam_error_transient_key() );
	if ( ! is_string( $msg ) || '' === $msg ) {
		$msg = __( 'Quiz analysis stopped due to an error. Check the server error log or try again.', 'engage' );
	}
	printf(
		'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
		esc_html( $msg )
	);
}

/**
 * Scores quiz posts in batches of ENGAGE_QUIZ_SPAM_CHUNK, up to ENGAGE_QUIZ_SPAM_MAX_INNER_BATCHES per HTTP request, then redirects.
 *
 * Technical: GET engage_analyze_quizzes=1 starts (stores total, shows first progress page → continue); engage_spam_continue=1 + spam_off=N runs batches; check_admin_referer( engage_analyze_quizzes ).
 * Between hops, engage_quiz_spam_render_analyze_progress_page provides visible feedback instead of silent redirects.
 */
function engage_quiz_spam_handle_analyze_request(): void {
	if ( ! is_admin() ) {
		return;
	}
	$start    = isset( $_GET['engage_analyze_quizzes'] ) && '1' === $_GET['engage_analyze_quizzes'];
	$continue = isset( $_GET['engage_spam_continue'] ) && '1' === $_GET['engage_spam_continue'];
	if ( ! $start && ! $continue ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	check_admin_referer( 'engage_analyze_quizzes' );

	global $wpdb;

	if ( $start ) {
		$total = engage_quiz_spam_count_synced_quiz_posts();
		set_transient(
			engage_quiz_spam_progress_transient_key(),
			array( 'total' => $total ),
			HOUR_IN_SECONDS
		);
		if ( 0 === $total ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'post_type'              => 'quiz',
						'engage_spam_finished' => '1',
						'analyzed'               => '0',
					),
					admin_url( 'edit.php' )
				)
			);
			exit;
		}
		$continue_url = wp_nonce_url(
			add_query_arg(
				array(
					'post_type'            => 'quiz',
					'engage_spam_continue' => '1',
					'spam_off'             => 0,
				),
				admin_url( 'edit.php' )
			),
			'engage_analyze_quizzes'
		);
		engage_quiz_spam_render_analyze_progress_page( 0, $continue_url, $total, true );
		exit;
	}

	$offset = 0;
	if ( isset( $_GET['spam_off'] ) ) {
		$offset = max( 0, (int) $_GET['spam_off'] );
	}

	$progress_total = engage_quiz_spam_get_progress_total();
	if ( $progress_total <= 0 ) {
		$progress_total = engage_quiz_spam_count_synced_quiz_posts();
		set_transient(
			engage_quiz_spam_progress_transient_key(),
			array( 'total' => $progress_total ),
			HOUR_IN_SECONDS
		);
	}

	try {
		$analyzer = new QuizSpamAnalyzer( QuizSpamAnalyzer::default_data_dir() );
		$context  = engage_quiz_spam_build_context( $wpdb );
	} catch ( \Throwable $e ) {
		engage_quiz_spam_abort_analyze_with_error( $e->getMessage() );
	}

	$ref            = new \DateTimeImmutable( 'now', wp_timezone() );
	$current_offset = $offset;
	$last_full      = false;

	try {
		for ( $b = 0; $b < ENGAGE_QUIZ_SPAM_MAX_INNER_BATCHES; $b++ ) {
			$post_ids = get_posts(
				array(
					'post_type'      => 'quiz',
					'post_status'    => 'any',
					'posts_per_page' => ENGAGE_QUIZ_SPAM_CHUNK,
					'offset'         => $current_offset,
					'fields'         => 'ids',
					'orderby'        => 'ID',
					'order'          => 'ASC',
					'meta_query'     => array(
						array(
							'key'     => '_enp_quiz_id',
							'compare' => 'EXISTS',
						),
					),
				)
			);

			$batch_count = count( $post_ids );
			if ( 0 === $batch_count ) {
				$last_full = false;
				break;
			}

			foreach ( $post_ids as $post_id ) {
				$quiz_id = (int) get_post_meta( $post_id, '_enp_quiz_id', true );
				if ( $quiz_id <= 0 ) {
					continue;
				}
				if ( ! isset( $context['quiz_rows'][ $quiz_id ] ) ) {
					continue;
				}
				$row   = $context['quiz_rows'][ $quiz_id ];
				$out   = $analyzer->analyze_row( $row, $context['email_day_counts'], $ref );
				$stamp = $ref->format( 'c' );

				update_post_meta( $post_id, ENGAGE_QUIZ_META_RISK_SCORE, (string) $out['risk_score'] );
				update_post_meta( $post_id, ENGAGE_QUIZ_META_RISK_TIER, $out['risk_tier'] );
				update_post_meta( $post_id, ENGAGE_QUIZ_META_RULE_HITS, wp_json_encode( $out['rule_hits'] ) );
				update_post_meta( $post_id, ENGAGE_QUIZ_META_ANALYZED_AT, $stamp );
				$matches = isset( $out['spam_embed_matches'] ) && is_array( $out['spam_embed_matches'] ) ? $out['spam_embed_matches'] : array();
				if ( count( $matches ) > 0 ) {
					update_post_meta( $post_id, ENGAGE_QUIZ_META_SPAM_EMBED_MATCHES, wp_json_encode( $matches ) );
				} else {
					delete_post_meta( $post_id, ENGAGE_QUIZ_META_SPAM_EMBED_MATCHES );
				}
			}

			$current_offset += $batch_count;

			if ( $batch_count < ENGAGE_QUIZ_SPAM_CHUNK ) {
				$last_full = false;
				break;
			}
			$last_full = true;
		}
	} catch ( \Throwable $e ) {
		engage_quiz_spam_abort_analyze_with_error( $e->getMessage() );
	}

	$next = $current_offset;

	if ( $last_full ) {
		$continue_url = wp_nonce_url(
			add_query_arg(
				array(
					'post_type'            => 'quiz',
					'engage_spam_continue' => '1',
					'spam_off'               => $next,
				),
				admin_url( 'edit.php' )
			),
			'engage_analyze_quizzes'
		);
		engage_quiz_spam_render_analyze_progress_page( $next, $continue_url, $progress_total, false );
		exit;
	}

	delete_transient( engage_quiz_spam_progress_transient_key() );
	wp_safe_redirect(
		add_query_arg(
			array(
				'post_type'              => 'quiz',
				'engage_spam_finished' => '1',
				'analyzed'               => (string) $next,
			),
			admin_url( 'edit.php' )
		)
	);
	exit;
}

/**
 * Layman: Transient key for a one-time “no rows to export” message on the Quizzes list.
 *
 * @return string User-scoped transient name.
 */
function engage_quiz_disposable_export_empty_notice_key(): string {
	return 'engage_disposable_export_empty_' . (int) get_current_user_id();
}

/**
 * Layman: Resolves disposable embed hits for one synced quiz—saved analysis meta first, then a live scan of embed URLs on the post.
 *
 * @param int               $post_id  Quiz CPT post ID.
 * @param QuizSpamAnalyzer  $analyzer Loaded blocklists.
 * @return array<int, array{url: string, host: string, matched_domain: string}>
 */
function engage_quiz_disposable_embed_matches_for_post( int $post_id, QuizSpamAnalyzer $analyzer ): array {
	$raw_matches = get_post_meta( $post_id, ENGAGE_QUIZ_META_SPAM_EMBED_MATCHES, true );
	$matches     = is_string( $raw_matches ) ? json_decode( $raw_matches, true ) : $raw_matches;
	if ( is_array( $matches ) && count( $matches ) > 0 ) {
		return $matches;
	}

	$embed_sites = trim( (string) get_post_meta( $post_id, 'embed_sites', true ) );
	if ( '' === $embed_sites ) {
		return array();
	}

	return $analyzer->find_disposable_embed_matches_in_urls( $embed_sites );
}

/**
 * Layman: Builds every quiz row that should appear in the disposable-embed CSV (blocklisted embed hosts only).
 *
 * Scans synced quizzes that have embed URLs or prior saved matches; uses current blocklist files at export time.
 *
 * @return array<int, array{post_id: int, enp_id: int, title: string, matches: array<int, array{url: string, host: string, matched_domain: string}>, tier: string, score: string, analyzed: string}>
 */
function engage_quiz_collect_disposable_embed_export_rows(): array {
	$analyzer = new QuizSpamAnalyzer( QuizSpamAnalyzer::default_data_dir() );

	$query = new \WP_Query(
		array(
			'post_type'      => 'quiz',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => '_enp_quiz_id',
					'compare' => 'EXISTS',
				),
				array(
					'relation' => 'OR',
					array(
						'key'     => 'embed_sites',
						'value'   => '',
						'compare' => '!=',
					),
					array(
						'key'     => ENGAGE_QUIZ_META_SPAM_EMBED_MATCHES,
						'compare' => 'EXISTS',
					),
				),
			),
			'no_found_rows'  => true,
		)
	);

	$rows = array();
	foreach ( $query->posts as $post_id ) {
		$post_id = (int) $post_id;
		$matches = engage_quiz_disposable_embed_matches_for_post( $post_id, $analyzer );
		if ( count( $matches ) === 0 ) {
			continue;
		}

		$rows[] = array(
			'post_id'  => $post_id,
			'enp_id'   => (int) get_post_meta( $post_id, '_enp_quiz_id', true ),
			'title'    => get_the_title( $post_id ),
			'matches'  => $matches,
			'tier'     => (string) get_post_meta( $post_id, ENGAGE_QUIZ_META_RISK_TIER, true ),
			'score'    => (string) get_post_meta( $post_id, ENGAGE_QUIZ_META_RISK_SCORE, true ),
			'analyzed' => (string) get_post_meta( $post_id, ENGAGE_QUIZ_META_ANALYZED_AT, true ),
		);
	}

	return $rows;
}

/**
 * Layman: After an empty disposable-embed export, explains why no CSV was downloaded and what to try next.
 */
function engage_quiz_spam_disposable_embed_export_notice(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || 'edit-quiz' !== $screen->id ) {
		return;
	}

	$key = engage_quiz_disposable_export_empty_notice_key();
	if ( ! get_transient( $key ) ) {
		return;
	}
	delete_transient( $key );

	echo '<div class="notice notice-warning is-dismissible"><p>';
	echo esc_html__(
		'No quizzes were exported: none of your synced quizzes have embed URLs whose hosts appear on the disposable or extra-risk domain lists. Run Sync and Analyse Quizzes first, check Quizzes ▸ Embed Sites for suspicious hosts, and add custom domains to extra_risk_domains.txt in the theme data folder if needed.',
		'engage'
	);
	echo '</p></div>';
}

/**
 * Streams a CSV of quizzes whose embed hosts matched disposable/extra-risk lists.
 *
 * Layman: Downloads quizzes with blocklisted embed hosts; rescans embed URLs at export time so new list entries appear without re-running Analyse.
 * Technical: GET engage_export_spam_embed_quizzes=1; redirects with transient notice when zero rows.
 */
function engage_quiz_spam_handle_export_disposable_embeds(): void {
	if ( ! is_admin() ) {
		return;
	}
	if ( ! isset( $_GET['engage_export_spam_embed_quizzes'] ) || '1' !== $_GET['engage_export_spam_embed_quizzes'] ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- check_admin_referer below.
	if ( ! isset( $_GET['post_type'] ) || 'quiz' !== sanitize_key( wp_unslash( $_GET['post_type'] ) ) ) {
		return;
	}
	global $pagenow;
	if ( 'edit.php' !== $pagenow ) {
		return;
	}
	check_admin_referer( 'engage_export_spam_embed_quizzes' );

	if ( function_exists( 'set_time_limit' ) ) {
		// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- admin export may scan thousands of embed URLs.
		@set_time_limit( 120 );
	}

	$export_rows = engage_quiz_collect_disposable_embed_export_rows();
	if ( count( $export_rows ) === 0 ) {
		set_transient( engage_quiz_disposable_export_empty_notice_key(), 1, 300 );
		wp_safe_redirect( admin_url( 'edit.php?post_type=quiz' ) );
		exit;
	}

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=quiz-disposable-embeds-' . gmdate( 'Y-m-d' ) . '.csv' );

	$out = fopen( 'php://output', 'w' );
	if ( false === $out ) {
		exit;
	}

	fputcsv(
		$out,
		array(
			'wp_post_id',
			'enp_quiz_id',
			'quiz_title',
			'matched_domains',
			'matched_hosts',
			'matched_embed_urls',
			'risk_tier',
			'risk_score',
			'analyzed_at',
		)
	);

	foreach ( $export_rows as $row ) {
		$matches = $row['matches'];
		$domains = array();
		$hosts   = array();
		$urls    = array();
		foreach ( $matches as $m ) {
			if ( ! is_array( $m ) ) {
				continue;
			}
			$d = isset( $m['matched_domain'] ) ? (string) $m['matched_domain'] : '';
			$h = isset( $m['host'] ) ? (string) $m['host'] : '';
			$u = isset( $m['url'] ) ? (string) $m['url'] : '';
			if ( '' !== $d && ! in_array( $d, $domains, true ) ) {
				$domains[] = $d;
			}
			if ( '' !== $h && ! in_array( $h, $hosts, true ) ) {
				$hosts[] = $h;
			}
			if ( '' !== $u && ! in_array( $u, $urls, true ) ) {
				$urls[] = $u;
			}
		}

		fputcsv(
			$out,
			array(
				(string) $row['post_id'],
				(string) $row['enp_id'],
				$row['title'],
				implode( '; ', $domains ),
				implode( '; ', $hosts ),
				implode( '; ', $urls ),
				$row['tier'],
				$row['score'],
				$row['analyzed'],
			)
		);
	}

	fclose( $out );
	exit;
}

/**
 * Loads everything the scorer needs from MySQL once per chunk (all ENP quizzes + embed counts + burst map).
 *
 * Resolves quiz_owner user IDs to emails for disposable-domain and burst rules.
 *
 * @param \wpdb $wpdb WordPress DB object (table prefix applied to enp_* tables).
 * @return array{quiz_rows: array<int, array<string, string>>, email_day_counts: array<string, int>}
 */
function engage_quiz_spam_build_context( \wpdb $wpdb ): array {
	$table = $wpdb->prefix . 'enp_quiz';
	$rows  = $wpdb->get_results( "SELECT * FROM `{$table}`", ARRAY_A );
	if ( ! is_array( $rows ) ) {
		$rows = array();
	}

	$embed_table      = $wpdb->prefix . 'enp_embed_quiz';
	$embed_site_table = $wpdb->prefix . 'enp_embed_site';
	$embed_sql        = "SELECT quiz_id, COUNT(*) AS c FROM `{$embed_table}` GROUP BY quiz_id";
	$embed_rows       = $wpdb->get_results( $embed_sql, ARRAY_A );
	$embed_map        = array();
	if ( is_array( $embed_rows ) ) {
		foreach ( $embed_rows as $er ) {
			$embed_map[ (int) $er['quiz_id'] ] = (int) $er['c'];
		}
	}

	$embed_urls_by_quiz = array();
	$pair_sql           = "
		SELECT eq.quiz_id, s.embed_site_url
		FROM `{$embed_table}` eq
		INNER JOIN `{$embed_site_table}` s ON eq.embed_site_id = s.embed_site_id
	";
	$url_pairs = $wpdb->get_results( $pair_sql, ARRAY_A );
	if ( is_array( $url_pairs ) ) {
		foreach ( $url_pairs as $pair ) {
			$qid = isset( $pair['quiz_id'] ) ? (int) $pair['quiz_id'] : 0;
			$url = isset( $pair['embed_site_url'] ) ? trim( (string) $pair['embed_site_url'] ) : '';
			if ( $qid <= 0 || '' === $url ) {
				continue;
			}
			if ( ! isset( $embed_urls_by_quiz[ $qid ] ) ) {
				$embed_urls_by_quiz[ $qid ] = array();
			}
			$embed_urls_by_quiz[ $qid ][ $url ] = true;
		}
	}

	$owner_emails = array();
	foreach ( $rows as $r ) {
		$oid = isset( $r['quiz_owner'] ) ? (int) $r['quiz_owner'] : 0;
		if ( $oid > 0 && ! isset( $owner_emails[ $oid ] ) ) {
			$user                    = get_userdata( $oid );
			$owner_emails[ $oid ] = ( $user && ! empty( $user->user_email ) ) ? $user->user_email : '';
		}
	}

	$count_rows = array();
	foreach ( $rows as $r ) {
		$oid = isset( $r['quiz_owner'] ) ? (int) $r['quiz_owner'] : 0;
		$count_rows[] = array(
			'owner_email'       => $oid > 0 ? ( $owner_emails[ $oid ] ?? '' ) : '',
			'quiz_created_at' => isset( $r['quiz_created_at'] ) ? (string) $r['quiz_created_at'] : '',
		);
	}
	$email_day_counts = QuizSpamAnalyzer::build_email_day_counts( $count_rows );

	$quiz_rows = array();
	foreach ( $rows as $r ) {
		$qid = isset( $r['quiz_id'] ) ? (int) $r['quiz_id'] : 0;
		if ( $qid <= 0 ) {
			continue;
		}
		$oid = isset( $r['quiz_owner'] ) ? (int) $r['quiz_owner'] : 0;
		$url_lines = array();
		if ( isset( $embed_urls_by_quiz[ $qid ] ) && is_array( $embed_urls_by_quiz[ $qid ] ) ) {
			$url_lines = array_keys( $embed_urls_by_quiz[ $qid ] );
		}
		$quiz_rows[ $qid ] = array(
			'quiz_title'        => isset( $r['quiz_title'] ) ? (string) $r['quiz_title'] : '',
			'quiz_status'       => isset( $r['quiz_status'] ) ? (string) $r['quiz_status'] : '',
			'quiz_views'        => isset( $r['quiz_views'] ) ? (string) $r['quiz_views'] : '0',
			'quiz_starts'       => isset( $r['quiz_starts'] ) ? (string) $r['quiz_starts'] : '0',
			'quiz_finishes'     => isset( $r['quiz_finishes'] ) ? (string) $r['quiz_finishes'] : '0',
			'quiz_created_at'   => isset( $r['quiz_created_at'] ) ? (string) $r['quiz_created_at'] : '',
			'quiz_is_deleted'   => isset( $r['quiz_is_deleted'] ) ? (string) $r['quiz_is_deleted'] : '0',
			'owner_email'       => $oid > 0 ? ( $owner_emails[ $oid ] ?? '' ) : '',
			'embed_row_count'   => (string) ( $embed_map[ $qid ] ?? 0 ),
			'embed_site_urls'   => implode( "\n", $url_lines ),
		);
	}

	return array(
		'quiz_rows'         => $quiz_rows,
		'email_day_counts'  => $email_day_counts,
	);
}

/**
 * Green admin notice showing how many quiz posts were processed after the final redirect.
 *
 * Triggered by GET engage_spam_finished=1 on the Quizzes list only.
 */
function engage_quiz_spam_finished_notice(): void {
	if ( ! isset( $_GET['engage_spam_finished'] ) || '1' !== $_GET['engage_spam_finished'] ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || 'edit-quiz' !== $screen->id ) {
		return;
	}
	$n = isset( $_GET['analyzed'] ) ? (int) $_GET['analyzed'] : 0;
	printf(
		'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
		esc_html(
			sprintf(
				/* translators: %d: number of quiz posts analyzed */
				_n( 'Analyzed %d quiz.', 'Analyzed %d quizzes.', $n, 'engage' ),
				$n
			)
		)
	);
}

/**
 * Adds Risk tier, score, rules, and analyzed-at columns to the Quizzes list table.
 *
 * @param array<string, string> $columns Core and theme column ids => headers.
 * @return array<string, string> Columns with spam fields appended.
 */
function engage_quiz_spam_add_columns( array $columns ): array {
	$columns['engage_spam_tier']     = __( 'Risk tier', 'engage' );
	$columns['engage_spam_score']    = __( 'Risk score', 'engage' );
	$columns['engage_spam_rules']    = __( 'Rules', 'engage' );
	$columns['engage_spam_analyzed'] = __( 'Analyzed', 'engage' );
	return $columns;
}

/**
 * Outputs HTML for one cell in the spam-related list columns (meta-backed).
 *
 * @param string   $column  Column id (engage_spam_*).
 * @param int|string $post_id Quiz post ID (WP may pass string).
 */
function engage_quiz_spam_column_content( string $column, $post_id ): void {
	$post_id = (int) $post_id;
	switch ( $column ) {
		case 'engage_spam_tier':
			$tier = get_post_meta( $post_id, ENGAGE_QUIZ_META_RISK_TIER, true );
			echo $tier ? esc_html( (string) $tier ) : '—';
			break;
		case 'engage_spam_score':
			$score = get_post_meta( $post_id, ENGAGE_QUIZ_META_RISK_SCORE, true );
			echo $score !== '' ? esc_html( (string) $score ) : '—';
			break;
		case 'engage_spam_rules':
			$raw = get_post_meta( $post_id, ENGAGE_QUIZ_META_RULE_HITS, true );
			if ( ! is_string( $raw ) || '' === $raw ) {
				echo '—';
				break;
			}
			$dec = json_decode( $raw, true );
			echo is_array( $dec ) ? esc_html( implode( ', ', $dec ) ) : '—';
			break;
		case 'engage_spam_analyzed':
			$at = get_post_meta( $post_id, ENGAGE_QUIZ_META_ANALYZED_AT, true );
			echo $at ? esc_html( (string) $at ) : '—';
			break;
	}
}

/**
 * Makes spam columns sortable; slugs are mapped to meta in engage_quiz_spam_pre_get_posts().
 *
 * @param array<string, string> $columns Sortable column slug => orderby key.
 * @return array<string, string>
 */
function engage_quiz_spam_sortable_columns( array $columns ): array {
	$columns['engage_spam_tier']     = 'engage_spam_tier';
	$columns['engage_spam_score']    = 'engage_spam_score';
	$columns['engage_spam_analyzed'] = 'engage_spam_analyzed';
	return $columns;
}

/**
 * When the user clicks a column header, tells WP_Query to sort by the right post meta key.
 *
 * @param \WP_Query $query Main query on edit.php?post_type=quiz only.
 */
function engage_quiz_spam_pre_get_posts( \WP_Query $query ): void {
	if ( ! is_admin() || ! $query->is_main_query() || 'quiz' !== $query->get( 'post_type' ) ) {
		return;
	}
	$orderby = $query->get( 'orderby' );
	if ( 'engage_spam_score' === $orderby ) {
		$query->set( 'meta_key', ENGAGE_QUIZ_META_RISK_SCORE );
		$query->set( 'orderby', 'meta_value_num' );
	}
	if ( 'engage_spam_tier' === $orderby ) {
		$query->set( 'meta_key', ENGAGE_QUIZ_META_RISK_TIER );
		$query->set( 'orderby', 'meta_value' );
	}
	if ( 'engage_spam_analyzed' === $orderby ) {
		$query->set( 'meta_key', ENGAGE_QUIZ_META_ANALYZED_AT );
		$query->set( 'orderby', 'meta_value' );
	}
}

/**
 * Renders the “All risk tiers” filter dropdown above the Quizzes list.
 *
 * @param string $post_type Current list post type (hook passes this as first argument).
 */
function engage_quiz_spam_restrict_posts( string $post_type ): void {
	if ( 'quiz' !== $post_type ) {
		return;
	}
	$current = isset( $_GET['engage_spam_tier'] ) ? sanitize_text_field( wp_unslash( $_GET['engage_spam_tier'] ) ) : '';
	?>
	<select name="engage_spam_tier" id="engage_spam_tier">
		<option value=""><?php esc_html_e( 'All risk tiers', 'engage' ); ?></option>
		<option value="high" <?php selected( $current, 'high' ); ?>><?php esc_html_e( 'High', 'engage' ); ?></option>
		<option value="medium" <?php selected( $current, 'medium' ); ?>><?php esc_html_e( 'Medium', 'engage' ); ?></option>
		<option value="low" <?php selected( $current, 'low' ); ?>><?php esc_html_e( 'Low', 'engage' ); ?></option>
	</select>
	<?php
}

/**
 * Narrows the Quizzes list when a risk tier is chosen in the dropdown (meta_query on tier meta).
 *
 * @param \WP_Query $query Main admin list query for post_type quiz.
 */
function engage_quiz_spam_parse_query( \WP_Query $query ): void {
	if ( ! is_admin() || ! $query->is_main_query() || 'quiz' !== $query->get( 'post_type' ) ) {
		return;
	}
	if ( empty( $_GET['engage_spam_tier'] ) ) {
		return;
	}
	$tier = sanitize_text_field( wp_unslash( $_GET['engage_spam_tier'] ) );
	if ( ! in_array( $tier, array( 'low', 'medium', 'high' ), true ) ) {
		return;
	}
	$mq   = $query->get( 'meta_query' );
	$mq   = is_array( $mq ) ? $mq : array();
	$mq[] = array(
		'key'   => ENGAGE_QUIZ_META_RISK_TIER,
		'value' => $tier,
	);
	$query->set( 'meta_query', $mq );
}

/**
 * Adds Quizzes ▸ Embed Sites for browsing which third-party URLs embed which quizzes.
 *
 * Capability manage_options; callback engage_quiz_render_embed_sites_page.
 */
function engage_quiz_spam_embed_sites_submenu(): void {
	add_submenu_page(
		'edit.php?post_type=quiz',
		__( 'Embed Sites', 'engage' ),
		__( 'Embed Sites', 'engage' ),
		'manage_options',
		'engage-quiz-embed-sites',
		'engage_quiz_render_embed_sites_page'
	);
}

/**
 * Renders either the WP_List_Table of all external embed sites or the drill-down for one site id.
 *
 * Chooses view from GET embed_site_id (positive int = quiz list for that site).
 */
function engage_quiz_render_embed_sites_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'engage' ) );
	}

	require_once __DIR__ . '/class-engage-embed-sites-list-table.php';

	$site_id = isset( $_GET['embed_site_id'] ) ? (int) $_GET['embed_site_id'] : 0;

	if ( $site_id > 0 ) {
		engage_quiz_render_embed_site_quizzes( $site_id );
		return;
	}

	echo '<div class="wrap">';
	echo '<h1 class="wp-heading-inline">' . esc_html__( 'Embed Sites', 'engage' ) . '</h1>';
	echo '<hr class="wp-header-end" />';

	if ( isset( $_GET['engage_embed_export'] ) && 'none' === $_GET['engage_embed_export'] ) {
		wp_admin_notice(
			__( 'Select at least one embed site to export.', 'engage' ),
			array(
				'type' => 'warning',
			)
		);
	}

	$table = new Engage_Embed_Sites_List_Table();
	$table->prepare_items();

	$table->views();

	echo '<form id="engage-embed-sites-filter" method="get">';
	echo '<input type="hidden" name="post_type" value="quiz" />';
	echo '<input type="hidden" name="page" value="engage-quiz-embed-sites" />';

	$table->display();

	echo '</form>';
	echo '</div>';
}

/**
 * Shows a simple table: quiz title, WordPress author (owner), link to edit the synced quiz post if it exists.
 *
 * @param int $embed_site_id Primary key in enp_embed_site / enp_embed_quiz.
 */
function engage_quiz_render_embed_site_quizzes( int $embed_site_id ): void {
	global $wpdb;

	$t_eq = $wpdb->prefix . 'enp_embed_quiz';
	$t_q  = $wpdb->prefix . 'enp_quiz';
	$t_pm = $wpdb->postmeta;
	$t_p  = $wpdb->posts;

	$url_row = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT embed_site_url FROM {$wpdb->prefix}enp_embed_site WHERE embed_site_id = %d",
			$embed_site_id
		)
	);
	$label = $url_row && isset( $url_row->embed_site_url ) ? $url_row->embed_site_url : '#' . $embed_site_id;

	$sql = "
		SELECT DISTINCT q.quiz_id, q.quiz_title, q.quiz_owner
		FROM {$t_eq} eq
		INNER JOIN {$t_q} q ON eq.quiz_id = q.quiz_id
		WHERE eq.embed_site_id = %d
		ORDER BY q.quiz_title ASC
	";

	$quizzes = $wpdb->get_results( $wpdb->prepare( $sql, $embed_site_id ) );

	echo '<div class="wrap">';
	echo '<h1>' . esc_html( $label ) . '</h1>';
	echo '<p><a href="' . esc_url( admin_url( 'edit.php?post_type=quiz&page=engage-quiz-embed-sites' ) ) . '">' . esc_html__( '&larr; All embed sites', 'engage' ) . '</a></p>';

	if ( empty( $quizzes ) ) {
		echo '<p>' . esc_html__( 'No quizzes found for this site.', 'engage' ) . '</p>';
		echo '</div>';
		return;
	}

	echo '<table class="wp-list-table widefat fixed striped"><thead><tr>';
	echo '<th>' . esc_html__( 'Quiz', 'engage' ) . '</th>';
	echo '<th>' . esc_html__( 'Author', 'engage' ) . '</th>';
	echo '<th>' . esc_html__( 'Edit', 'engage' ) . '</th>';
	echo '</tr></thead><tbody>';

	foreach ( $quizzes as $q ) {
		$post_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT p.ID FROM {$t_pm} pm
				INNER JOIN {$t_p} p ON p.ID = pm.post_id AND p.post_type = 'quiz'
				WHERE pm.meta_key = '_enp_quiz_id' AND pm.meta_value = %s
				LIMIT 1",
				(string) (int) $q->quiz_id
			)
		);

		$title = isset( $q->quiz_title ) ? $q->quiz_title : '';
		$oid   = isset( $q->quiz_owner ) ? (int) $q->quiz_owner : 0;
		$user  = $oid ? get_userdata( $oid ) : false;
		$auth  = $user ? $user->user_login : '—';

		echo '<tr>';
		echo '<td>' . esc_html( $title ) . '</td>';
		if ( $user ) {
			printf(
				'<td><a href="%s">%s</a></td>',
				esc_url( admin_url( 'user-edit.php?user_id=' . $oid ) ),
				esc_html( $auth )
			);
		} else {
			echo '<td>' . esc_html( $auth ) . '</td>';
		}
		if ( $post_id ) {
			printf(
				'<td><a href="%s">%s</a></td>',
				esc_url( get_edit_post_link( $post_id, 'raw' ) ),
				esc_html__( 'Edit quiz', 'engage' )
			);
		} else {
			echo '<td>—</td>';
		}
		echo '</tr>';
	}

	echo '</tbody></table></div>';
}

/**
 * Registers Spam user quizzes under the Quizzes menu.
 */
function engage_quiz_spam_user_quizzes_submenu(): void {
	add_submenu_page(
		'edit.php?post_type=quiz',
		__( 'Spam user quizzes', 'engage' ),
		__( 'Spam user quizzes', 'engage' ),
		'manage_options',
		'engage-quiz-spam-user-quizzes',
		'engage_quiz_render_spam_user_quizzes_page'
	);
}

/**
 * Screen options and bulk/delete handlers for Spam user quizzes.
 */
function engage_quiz_spam_user_quizzes_load_screen(): void {
	add_screen_option(
		'per_page',
		array(
			'label'   => __( 'Quizzes per page', 'engage' ),
			'default' => 20,
			'option'  => 'spam_user_quizzes_per_page',
		)
	);

	add_action( 'admin_footer', 'engage_quiz_spam_user_quizzes_footer_search_enter_fix', 5 );

	engage_quiz_spam_user_quizzes_handle_requests();
}

/**
 * Layman: Enter in the search box triggers Search, not the bulk Apply button.
 */
function engage_quiz_spam_user_quizzes_footer_search_enter_fix(): void {
	$screen = get_current_screen();
	if ( ! $screen || 'quiz_page_engage-quiz-spam-user-quizzes' !== $screen->id ) {
		return;
	}
	?>
<script>
(function () {
	var form = document.getElementById( 'engage-spam-user-quizzes-filter' );
	if ( ! form ) {
		return;
	}
	var searchInput = form.querySelector( 'input[name="s"]' );
	var searchBtn   = document.getElementById( 'search-submit' );
	if ( searchInput && searchBtn ) {
		searchInput.addEventListener( 'keydown', function ( e ) {
			if ( e.key !== 'Enter' ) {
				return;
			}
			e.preventDefault();
			searchBtn.click();
		} );
	}
})();
</script>
	<?php
}

/**
 * Persists per-page screen option for the spam user quizzes table.
 *
 * @param mixed $status Default status.
 * @param string $option Option name.
 * @param mixed $value  Submitted value.
 * @return mixed
 */
function engage_quiz_spam_user_quizzes_set_per_page_option( $status, $option, $value ) {
	if ( 'spam_user_quizzes_per_page' === $option ) {
		return (int) $value;
	}
	return $status;
}

/**
 * Layman: Handles bulk confirm, draft execution, and chunked permanent delete before the list renders.
 */
function engage_quiz_spam_user_quizzes_handle_requests(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( isset( $_GET['engage_spam_quiz_delete_continue'] ) && '1' === $_GET['engage_spam_quiz_delete_continue'] ) {
		engage_quiz_spam_user_quizzes_process_delete_continue();
		return;
	}

	if ( isset( $_POST['engage_spam_quiz_confirm_run'] ) && '1' === $_POST['engage_spam_quiz_confirm_run'] ) {
		engage_quiz_spam_user_quizzes_process_confirm();
		return;
	}

	$action = false;
	if ( isset( $_REQUEST['action'] ) && '-1' !== $_REQUEST['action'] ) {
		$action = sanitize_key( wp_unslash( $_REQUEST['action'] ) );
	} elseif ( isset( $_REQUEST['action2'] ) && '-1' !== $_REQUEST['action2'] ) {
		$action = sanitize_key( wp_unslash( $_REQUEST['action2'] ) );
	}

	if ( ! in_array( $action, array( 'set_to_draft', 'permanent_delete' ), true ) ) {
		return;
	}

	check_admin_referer( 'bulk-quizzes' );

	$raw_ids = isset( $_REQUEST['quiz'] ) ? array_map( 'intval', (array) wp_unslash( $_REQUEST['quiz'] ) ) : array();
	$valid   = engage_quiz_validate_spam_quiz_ids( $raw_ids );

	if ( empty( $valid ) ) {
		engage_quiz_spam_set_success_notice( 'no_selection' );
		wp_safe_redirect( engage_quiz_spam_user_quizzes_admin_url() );
		exit;
	}

	set_transient(
		engage_quiz_spam_pending_transient_key(),
		array(
			'action'   => $action,
			'quiz_ids' => $valid,
		),
		15 * MINUTE_IN_SECONDS
	);

	$confirm = 'set_to_draft' === $action ? 'draft' : 'delete';
	wp_safe_redirect(
		engage_quiz_spam_user_quizzes_admin_url(
			array( 'engage_spam_quiz_confirm' => $confirm )
		)
	);
	exit;
}

/**
 * Renders confirm step or main list table.
 */
function engage_quiz_render_spam_user_quizzes_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'engage' ) );
	}

	if ( isset( $_GET['engage_spam_quiz_confirm'] ) ) {
		engage_quiz_render_spam_user_quizzes_confirm();
		return;
	}

	require_once __DIR__ . '/class-engage-spam-user-quizzes-list-table.php';

	$table = new Engage_Spam_User_Quizzes_List_Table();
	$table->prepare_items();

	echo '<div class="wrap">';
	echo '<h1 class="wp-heading-inline">' . esc_html__( 'Spam user quizzes', 'engage' ) . '</h1>';
	$notice_markup = engage_quiz_spam_user_quizzes_notice_markup();
	if ( '' !== $notice_markup ) {
		echo $notice_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_html/esc_attr.
	}
	echo '<p class="description">' . esc_html__( 'Quizzes owned by users with the spam_user role (administrators excluded). Permanent delete removes ENP data via the synced quiz post—moving a post to Trash does not.', 'engage' ) . '</p>';
	echo '<hr class="wp-header-end" />';

	echo '<form id="engage-spam-user-quizzes-filter" method="post">';
	wp_nonce_field( 'bulk-quizzes' );
	echo '<input type="hidden" name="page" value="engage-quiz-spam-user-quizzes" />';

	$table->views();
	$table->search_box( __( 'Search quizzes', 'engage' ), 'spam-user-quiz' );
	$table->display();

	echo '</form>';
	echo '</div>';
}

/**
 * Confirmation screen before bulk draft or permanent delete.
 */
function engage_quiz_render_spam_user_quizzes_confirm(): void {
	$confirm_type = isset( $_GET['engage_spam_quiz_confirm'] ) ? sanitize_key( wp_unslash( $_GET['engage_spam_quiz_confirm'] ) ) : '';
	if ( ! in_array( $confirm_type, array( 'draft', 'delete' ), true ) ) {
		wp_safe_redirect( engage_quiz_spam_user_quizzes_admin_url() );
		exit;
	}

	$pending = get_transient( engage_quiz_spam_pending_transient_key() );
	if ( ! is_array( $pending ) || empty( $pending['quiz_ids'] ) ) {
		engage_quiz_spam_set_success_notice( 'expired' );
		wp_safe_redirect( engage_quiz_spam_user_quizzes_admin_url() );
		exit;
	}

	$expected_action = 'draft' === $confirm_type ? 'set_to_draft' : 'permanent_delete';
	if ( ! isset( $pending['action'] ) || $expected_action !== $pending['action'] ) {
		engage_quiz_spam_set_success_notice( 'expired' );
		wp_safe_redirect( engage_quiz_spam_user_quizzes_admin_url() );
		exit;
	}

	$quiz_ids = array_map( 'intval', $pending['quiz_ids'] );
	$count    = count( $quiz_ids );

	echo '<div class="wrap">';
	echo '<h1>' . esc_html__( 'Confirm bulk action', 'engage' ) . '</h1>';

	if ( 'draft' === $confirm_type ) {
		echo '<p>' . esc_html(
			sprintf(
				/* translators: %d: number of quizzes */
				_n(
					'Set %d quiz to draft (unpublish in ENP)?',
					'Set %d quizzes to draft (unpublish in ENP)?',
					$count,
					'engage'
				),
				$count
			)
		) . '</p>';
		echo '<p>' . esc_html__( 'Afterward, run Sync Quizzes on the main quiz list so WordPress meta stays in sync.', 'engage' ) . '</p>';
	} else {
		echo '<p>' . esc_html(
			sprintf(
				/* translators: %d: number of quizzes */
				_n(
					'Permanently delete %d quiz? This cannot be undone.',
					'Permanently delete %d quizzes? This cannot be undone.',
					$count,
					'engage'
				),
				$count
			)
		) . '</p>';
		echo '<p>' . esc_html__( 'This removes the quiz from ENP (so it leaves this list) and deletes any synced WordPress quiz posts when permitted.', 'engage' ) . '</p>';
	}

	echo '<form method="post" action="' . esc_url( engage_quiz_spam_user_quizzes_admin_url() ) . '">';
	wp_nonce_field( 'engage_spam_quiz_bulk', 'engage_spam_quiz_bulk_nonce' );
	echo '<input type="hidden" name="engage_spam_quiz_confirm_run" value="1" />';
	echo '<input type="hidden" name="engage_spam_quiz_confirm_type" value="' . esc_attr( $confirm_type ) . '" />';

	if ( 'delete' === $confirm_type ) {
		echo '<p><label><input type="checkbox" name="engage_spam_quiz_reviewed" value="1" required /> ';
		echo esc_html__( 'I have reviewed an export or the quiz list', 'engage' ) . '</label></p>';
	}

	submit_button( 'draft' === $confirm_type ? __( 'Set to draft', 'engage' ) : __( 'Permanently delete', 'engage' ), 'primary', 'submit', false );
	echo ' <a class="button" href="' . esc_url( engage_quiz_spam_user_quizzes_admin_url() ) . '">' . esc_html__( 'Cancel', 'engage' ) . '</a>';
	echo '</form></div>';
}

/**
 * Executes confirmed bulk draft or starts chunked delete queue.
 */
function engage_quiz_spam_user_quizzes_process_confirm(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	check_admin_referer( 'engage_spam_quiz_bulk', 'engage_spam_quiz_bulk_nonce' );

	$confirm_type = isset( $_POST['engage_spam_quiz_confirm_type'] ) ? sanitize_key( wp_unslash( $_POST['engage_spam_quiz_confirm_type'] ) ) : '';
	if ( ! in_array( $confirm_type, array( 'draft', 'delete' ), true ) ) {
		wp_safe_redirect( engage_quiz_spam_user_quizzes_admin_url() );
		exit;
	}

	if ( 'delete' === $confirm_type && empty( $_POST['engage_spam_quiz_reviewed'] ) ) {
		engage_quiz_spam_set_success_notice( 'review_required' );
		wp_safe_redirect( engage_quiz_spam_user_quizzes_admin_url() );
		exit;
	}

	$pending = get_transient( engage_quiz_spam_pending_transient_key() );
	delete_transient( engage_quiz_spam_pending_transient_key() );

	if ( ! is_array( $pending ) || empty( $pending['quiz_ids'] ) ) {
		engage_quiz_spam_set_success_notice( 'expired' );
		wp_safe_redirect( engage_quiz_spam_user_quizzes_admin_url() );
		exit;
	}

	$quiz_ids = engage_quiz_validate_spam_quiz_ids( $pending['quiz_ids'] );

	if ( 'draft' === $confirm_type ) {
		$result = engage_quiz_bulk_draft_spam_quizzes( $quiz_ids );
		engage_quiz_spam_set_success_notice(
			'drafted',
			array(
				'updated' => (int) $result['updated'],
				'skipped' => (int) $result['skipped'],
			)
		);
		wp_safe_redirect( engage_quiz_spam_user_quizzes_admin_url() );
		exit;
	}

	set_transient(
		engage_quiz_spam_delete_queue_transient_key(),
		array(
			'quiz_ids'     => $quiz_ids,
			'enp_removed'  => 0,
			'cpt_removed'  => 0,
			'cpt_failed'   => 0,
		),
		HOUR_IN_SECONDS
	);

	$continue_url = wp_nonce_url(
		engage_quiz_spam_user_quizzes_admin_url(
			array(
				'engage_spam_quiz_delete_continue' => '1',
				'spam_quiz_off'                    => 0,
			)
		),
		'engage_spam_quiz_bulk',
		'engage_spam_quiz_bulk_nonce'
	);

	engage_quiz_spam_render_delete_progress_page( 0, $continue_url, count( $quiz_ids ), true );
	exit;
}

/**
 * Layman: Progress page between chunks when permanently deleting many quizzes.
 *
 * @param int    $processed    Quizzes processed so far.
 * @param string $continue_url Next chunk URL.
 * @param int    $total        Total in queue.
 * @param bool   $is_starting  First hop.
 */
function engage_quiz_spam_render_delete_progress_page( int $processed, string $continue_url, int $total, bool $is_starting = false ): void {
	nocache_headers();
	header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );

	$total_for_bar = max( 1, $total );
	$pct           = min( 100, (int) round( 100 * min( $processed, $total_for_bar ) / $total_for_bar ) );
	$esc_url       = esc_url( $continue_url, array( 'http', 'https' ) );
	$json_url      = wp_json_encode( $continue_url, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );

	if ( $is_starting ) {
		$line = sprintf(
			/* translators: %d: total quizzes */
			__( 'Starting permanent delete… %d quizzes queued.', 'engage' ),
			$total
		);
	} else {
		$line = sprintf(
			/* translators: 1: processed, 2: total */
			__( 'Deleting quizzes… %1$d of %2$d processed.', 'engage' ),
			min( $processed, $total_for_bar ),
			$total_for_bar
		);
	}

	$html_lang = str_replace( '_', '-', get_locale() );
	echo '<!DOCTYPE html><html lang="' . esc_attr( $html_lang ) . '"><head>';
	echo '<meta charset="' . esc_attr( get_bloginfo( 'charset' ) ) . '">';
	echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
	echo '<meta http-equiv="refresh" content="3;url=' . esc_attr( $esc_url ) . '">';
	echo '<title>' . esc_html__( 'Deleting quizzes', 'engage' ) . '</title>';
	echo '<style>body{margin:40px auto;max-width:520px;font:14px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif}.bar{height:10px;background:#c3c4c7;border-radius:3px;margin:16px 0}.bar>span{display:block;height:100%;background:#b32d2e;width:' . (int) $pct . '%}</style>';
	echo '</head><body><h1>' . esc_html__( 'Spam user quizzes — delete', 'engage' ) . '</h1>';
	echo '<p>' . esc_html( $line ) . '</p>';
	echo '<div class="bar"><span></span></div>';
	echo '<p><a href="' . $esc_url . '">' . esc_html__( 'Continue now', 'engage' ) . '</a></p>';
	echo '<script>window.location.replace(' . $json_url . ');</script></body></html>';
}

/**
 * Processes one chunk of permanent deletes, then continues or finishes.
 */
function engage_quiz_spam_user_quizzes_process_delete_continue(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	check_admin_referer( 'engage_spam_quiz_bulk', 'engage_spam_quiz_bulk_nonce' );

	$queue = get_transient( engage_quiz_spam_delete_queue_transient_key() );
	if ( ! is_array( $queue ) || empty( $queue['quiz_ids'] ) ) {
		engage_quiz_spam_set_success_notice( 'expired' );
		wp_safe_redirect( engage_quiz_spam_user_quizzes_admin_url() );
		exit;
	}

	$quiz_ids = engage_quiz_validate_spam_quiz_ids( $queue['quiz_ids'] );
	$offset   = isset( $_GET['spam_quiz_off'] ) ? max( 0, (int) $_GET['spam_quiz_off'] ) : 0;
	$total    = count( $quiz_ids );

	$result = engage_quiz_bulk_delete_spam_quizzes_chunk( $quiz_ids, $offset, ENGAGE_SPAM_USER_QUIZ_DELETE_CHUNK );

	$queue['enp_removed'] = (int) ( $queue['enp_removed'] ?? 0 ) + (int) $result['enp_removed'];
	$queue['cpt_removed'] = (int) ( $queue['cpt_removed'] ?? 0 ) + (int) $result['cpt_removed'];
	$queue['cpt_failed']  = (int) ( $queue['cpt_failed'] ?? 0 ) + (int) $result['cpt_failed'];

	$next_offset = $offset + (int) $result['processed'];

	if ( ! $result['done'] ) {
		set_transient( engage_quiz_spam_delete_queue_transient_key(), $queue, HOUR_IN_SECONDS );
		$continue_url = wp_nonce_url(
			engage_quiz_spam_user_quizzes_admin_url(
				array(
					'engage_spam_quiz_delete_continue' => '1',
					'spam_quiz_off'                    => $next_offset,
				)
			),
			'engage_spam_quiz_bulk',
			'engage_spam_quiz_bulk_nonce'
		);
		engage_quiz_spam_render_delete_progress_page( $next_offset, $continue_url, $total, false );
		exit;
	}

	delete_transient( engage_quiz_spam_delete_queue_transient_key() );

	engage_quiz_spam_set_success_notice(
		'deleted',
		array(
			'enp_removed' => (int) $queue['enp_removed'],
			'cpt_removed' => (int) $queue['cpt_removed'],
			'cpt_failed'  => (int) $queue['cpt_failed'],
		)
	);

	wp_safe_redirect( engage_quiz_spam_user_quizzes_admin_url() );
	exit;
}

/**
 * Resolves notice payload once per request (transient, then URL fallback).
 *
 * @return array{type: string, counts: array<string, mixed>}|null
 */
function engage_quiz_spam_user_quizzes_notice_data(): ?array {
	static $resolved = false;
	static $data     = null;

	if ( $resolved ) {
		return $data;
	}
	$resolved = true;

	$stored = engage_quiz_spam_get_and_clear_success_notice();
	if ( is_array( $stored ) ) {
		return $data = $stored;
	}

	if ( isset( $_GET['engage_spam_quiz_notice'] ) ) {
		$counts = array();
		if ( isset( $_GET['updated'] ) ) {
			$counts['updated'] = (int) $_GET['updated'];
		}
		if ( isset( $_GET['skipped'] ) ) {
			$counts['skipped'] = (int) $_GET['skipped'];
		}
		if ( isset( $_GET['enp_removed'] ) ) {
			$counts['enp_removed'] = (int) $_GET['enp_removed'];
		}
		if ( isset( $_GET['cpt_removed'] ) ) {
			$counts['cpt_removed'] = (int) $_GET['cpt_removed'];
		}
		if ( isset( $_GET['cpt_failed'] ) ) {
			$counts['cpt_failed'] = (int) $_GET['cpt_failed'];
		}
		return $data = array(
			'type'   => sanitize_key( wp_unslash( $_GET['engage_spam_quiz_notice'] ) ),
			'counts' => $counts,
		);
	}

	return $data = null;
}

/**
 * Layman: Builds the green/yellow admin notice HTML for bulk actions on this screen.
 *
 * Uses a short-lived transient first, then legacy URL query args.
 *
 * @return string Empty when there is nothing to show.
 */
function engage_quiz_spam_user_quizzes_notice_markup(): string {
	if ( isset( $_GET['engage_spam_quiz_export'] ) && 'none' === $_GET['engage_spam_quiz_export'] ) {
		return sprintf(
			'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
			esc_html__( 'Select at least one quiz to export.', 'engage' )
		);
	}

	$payload = engage_quiz_spam_user_quizzes_notice_data();
	if ( ! is_array( $payload ) || empty( $payload['type'] ) ) {
		return '';
	}

	$type   = $payload['type'];
	$counts = $payload['counts'];

	$message = '';
	$class   = 'notice-success';

	switch ( $type ) {
		case 'drafted':
			$updated = isset( $counts['updated'] ) ? (int) $counts['updated'] : 0;
			$skipped = isset( $counts['skipped'] ) ? (int) $counts['skipped'] : 0;
			$message = sprintf(
				/* translators: 1: updated count, 2: skipped count */
				__( 'Set %1$d quiz(es) to draft. %2$d already non-published. Run Sync Quizzes on the main list to refresh meta.', 'engage' ),
				$updated,
				$skipped
			);
			break;
		case 'deleted':
			$enp_removed = isset( $counts['enp_removed'] ) ? (int) $counts['enp_removed'] : 0;
			$cpt_removed = isset( $counts['cpt_removed'] ) ? (int) $counts['cpt_removed'] : 0;
			$cpt_failed  = isset( $counts['cpt_failed'] ) ? (int) $counts['cpt_failed'] : 0;
			if ( $enp_removed <= 0 ) {
				$class   = 'notice-error';
				$message = __( 'No quizzes were removed from ENP. The selection may have already been deleted or is no longer owned by a spam user.', 'engage' );
				break;
			}
			$message = sprintf(
				/* translators: 1: ENP quizzes removed, 2: WP posts removed */
				__( 'Removed %1$d quiz(es) from ENP. Deleted %2$d synced WordPress post(s).', 'engage' ),
				$enp_removed,
				$cpt_removed
			);
			if ( $cpt_failed > 0 ) {
				$message .= ' ' . sprintf(
					/* translators: %d: posts that could not be deleted */
					__( '%d synced post(s) may still exist; check the main Quizzes list or trash. ENP rows were removed.', 'engage' ),
					$cpt_failed
				);
				$class = 'notice-warning';
			}
			break;
		case 'no_selection':
			$class   = 'notice-warning';
			$message = __( 'Select at least one quiz for that bulk action.', 'engage' );
			break;
		case 'expired':
			$class   = 'notice-warning';
			$message = __( 'That bulk action expired. Please try again.', 'engage' );
			break;
		case 'review_required':
			$class   = 'notice-error';
			$message = __( 'Confirm that you reviewed the list before permanently deleting.', 'engage' );
			break;
		default:
			return '';
	}

	return sprintf(
		'<div class="notice %1$s is-dismissible"><p>%2$s</p></div>',
		esc_attr( $class ),
		esc_html( $message )
	);
}

/**
 * Admin notices for spam user quiz bulk actions (duplicate of inline notice for standard WP placement).
 */
function engage_quiz_spam_user_quizzes_admin_notices(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$on_page = isset( $_GET['page'] ) && 'engage-quiz-spam-user-quizzes' === $_GET['page'];
	if ( ! $on_page ) {
		return;
	}

	$markup = engage_quiz_spam_user_quizzes_notice_markup();
	if ( '' !== $markup ) {
		echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

engage_quiz_spam_admin_init();
