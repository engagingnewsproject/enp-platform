<?php
/**
 * Helpers for the Spam user quizzes admin screen: resolve owners, validate IDs, draft, delete, CSV.
 *
 * @package Engage
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Quizzes permanently deleted per HTTP request during bulk delete. */
const ENGAGE_SPAM_USER_QUIZ_DELETE_CHUNK = 25;

/** Transient key suffix: pending bulk quiz IDs for confirm step (per admin user). */
const ENGAGE_SPAM_USER_QUIZ_PENDING_TRANSIENT = 'engage_spam_quiz_pending_';

/** Transient key suffix: quiz IDs queued for chunked delete (per admin user). */
const ENGAGE_SPAM_USER_QUIZ_DELETE_QUEUE_TRANSIENT = 'engage_spam_quiz_delete_queue_';

/**
 * Layman: Returns WordPress user IDs that have the spam_user role but are not site admins.
 *
 * Technical: get_users( role spam_user ); excludes anyone with manage_options.
 *
 * @return int[]
 */
function engage_quiz_get_spam_owner_ids(): array {
	$user_ids = get_users(
		array(
			'role'   => 'spam_user',
			'fields' => 'ID',
			'number' => -1,
		)
	);

	$ids = array();
	foreach ( $user_ids as $uid ) {
		$uid = (int) $uid;
		if ( $uid > 0 && ! user_can( $uid, 'manage_options' ) ) {
			$ids[] = $uid;
		}
	}

	return $ids;
}

/**
 * Layman: Key for storing selected quiz IDs between bulk confirm and execution.
 *
 * @return string
 */
function engage_quiz_spam_pending_transient_key(): string {
	return ENGAGE_SPAM_USER_QUIZ_PENDING_TRANSIENT . get_current_user_id();
}

/**
 * Layman: Key for the delete queue during chunked permanent delete.
 *
 * @return string
 */
function engage_quiz_spam_delete_queue_transient_key(): string {
	return ENGAGE_SPAM_USER_QUIZ_DELETE_QUEUE_TRANSIENT . get_current_user_id();
}

/**
 * Layman: Remembers the success message after bulk draft/delete so you still see it after redirect or refresh.
 *
 * @return string
 */
function engage_quiz_spam_success_transient_key(): string {
	return 'engage_spam_quiz_success_' . get_current_user_id();
}

/**
 * Stores a one-shot admin notice for the spam user quizzes screen.
 *
 * @param string               $type   drafted|deleted|no_selection|expired|review_required.
 * @param array<string, mixed> $counts Optional counts (updated, skipped, enp_removed, etc.).
 */
function engage_quiz_spam_set_success_notice( string $type, array $counts = array() ): void {
	set_transient(
		engage_quiz_spam_success_transient_key(),
		array(
			'type'   => $type,
			'counts' => $counts,
		),
		300
	);
}

/**
 * Reads and clears the stored success notice (if any).
 *
 * @return array{type: string, counts: array<string, mixed>}|null
 */
function engage_quiz_spam_get_and_clear_success_notice(): ?array {
	$key  = engage_quiz_spam_success_transient_key();
	$data = get_transient( $key );
	delete_transient( $key );
	if ( ! is_array( $data ) || empty( $data['type'] ) ) {
		return null;
	}
	return array(
		'type'   => (string) $data['type'],
		'counts' => isset( $data['counts'] ) && is_array( $data['counts'] ) ? $data['counts'] : array(),
	);
}

/**
 * Layman: Checks that each quiz ID belongs to a spam user (not an admin) before draft/delete.
 *
 * @param int[] $quiz_ids Candidate ENP quiz_id values.
 * @return int[] Valid quiz IDs.
 */
function engage_quiz_validate_spam_quiz_ids( array $quiz_ids ): array {
	global $wpdb;

	$quiz_ids = array_values(
		array_unique(
			array_filter(
				array_map( 'intval', $quiz_ids ),
				static function ( $id ) {
					return $id > 0;
				}
			)
		)
	);

	if ( empty( $quiz_ids ) ) {
		return array();
	}

	$owner_ids = engage_quiz_get_spam_owner_ids();
	if ( empty( $owner_ids ) ) {
		return array();
	}

	$placeholders = implode( ',', array_fill( 0, count( $quiz_ids ), '%d' ) );
	$owner_ph     = implode( ',', array_fill( 0, count( $owner_ids ), '%d' ) );
	$table        = $wpdb->prefix . 'enp_quiz';

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name; placeholders for IDs.
	$sql = "SELECT quiz_id FROM `{$table}` WHERE quiz_id IN ({$placeholders}) AND quiz_owner IN ({$owner_ph})";

	$found = $wpdb->get_col( $wpdb->prepare( $sql, array_merge( $quiz_ids, $owner_ids ) ) );

	return array_map( 'intval', $found ? $found : array() );
}

/**
 * Layman: Maps ENP quiz IDs to synced WordPress quiz post IDs for the current page.
 *
 * @param int[] $quiz_ids ENP quiz_id values.
 * @return array<int, int> quiz_id => post_id (0 if not synced).
 */
function engage_quiz_map_post_ids_for_quizzes( array $quiz_ids ): array {
	global $wpdb;

	$quiz_ids = array_values( array_filter( array_map( 'intval', $quiz_ids ) ) );
	$map      = array_fill_keys( $quiz_ids, 0 );

	if ( empty( $quiz_ids ) ) {
		return $map;
	}

	$placeholders = implode( ',', array_fill( 0, count( $quiz_ids ), '%d' ) );

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- placeholders only.
	$sql = "
		SELECT pm.meta_value AS quiz_id, p.ID AS post_id
		FROM {$wpdb->postmeta} pm
		INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id AND p.post_type = 'quiz'
		WHERE pm.meta_key = '_enp_quiz_id'
		AND CAST( pm.meta_value AS UNSIGNED ) IN ({$placeholders})
	";

	$rows = $wpdb->get_results( $wpdb->prepare( $sql, $quiz_ids ), ARRAY_A );

	if ( is_array( $rows ) ) {
		foreach ( $rows as $row ) {
			$qid = isset( $row['quiz_id'] ) ? (int) $row['quiz_id'] : 0;
			$pid = isset( $row['post_id'] ) ? (int) $row['post_id'] : 0;
			if ( $qid > 0 && $pid > 0 ) {
				$map[ $qid ] = $pid;
			}
		}
	}

	return $map;
}

/**
 * Layman: Finds every WordPress quiz post linked to one ENP quiz id (handles duplicate sync rows).
 *
 * @param int $quiz_id ENP quiz_id.
 * @return int[] Post IDs.
 */
function engage_quiz_get_all_post_ids_for_enp_quiz( int $quiz_id ): array {
	global $wpdb;

	$quiz_id = (int) $quiz_id;
	if ( $quiz_id <= 0 ) {
		return array();
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- prepared quiz_id only.
	$sql = "
		SELECT p.ID
		FROM {$wpdb->postmeta} pm
		INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id AND p.post_type = 'quiz'
		WHERE pm.meta_key = '_enp_quiz_id'
		AND CAST( pm.meta_value AS UNSIGNED ) = %d
	";

	$ids = $wpdb->get_col( $wpdb->prepare( $sql, $quiz_id ) );

	return array_values(
		array_filter(
			array_map( 'intval', is_array( $ids ) ? $ids : array() ),
			static function ( $id ) {
				return $id > 0;
			}
		)
	);
}

/**
 * Layman: Removes one quiz from the ENP database tables (embed links, then the quiz row).
 *
 * @param int $quiz_id ENP quiz_id.
 * @return bool True if the quiz row no longer exists.
 */
function engage_quiz_delete_enp_quiz_rows( int $quiz_id ): bool {
	global $wpdb;

	$quiz_id = (int) $quiz_id;
	if ( $quiz_id <= 0 ) {
		return false;
	}

	$embed_table = $wpdb->prefix . 'enp_embed_quiz';
	$quiz_table  = $wpdb->prefix . 'enp_quiz';

	$wpdb->delete( $embed_table, array( 'quiz_id' => $quiz_id ), array( '%d' ) );
	$wpdb->delete( $quiz_table, array( 'quiz_id' => $quiz_id ), array( '%d' ) );

	$still = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT quiz_id FROM `{$quiz_table}` WHERE quiz_id = %d",
			$quiz_id
		)
	);

	return null === $still;
}

/**
 * Layman: Removes a synced quiz WordPress post when normal trash/delete fails (permissions or stale state).
 *
 * @param int $post_id Quiz CPT post ID.
 * @return bool True when the post row is gone.
 */
function engage_quiz_force_delete_quiz_post( int $post_id ): bool {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 ) {
		return false;
	}

	$post = get_post( $post_id );
	if ( ! $post || 'quiz' !== $post->post_type ) {
		return false;
	}

	if ( current_user_can( 'delete_post', $post_id ) ) {
		$deleted = wp_delete_post( $post_id, true );
		if ( $deleted ) {
			return true;
		}
	}

	global $wpdb;

	$wpdb->delete( $wpdb->postmeta, array( 'post_id' => $post_id ), array( '%d' ) );
	$wpdb->delete( $wpdb->posts, array( 'ID' => $post_id ), array( '%d' ) );
	clean_post_cache( $post_id );

	return null === get_post( $post_id );
}

/**
 * Layman: Deletes one spam-user quiz completely—ENP row first, then every synced WordPress post—so it leaves this list.
 *
 * Handles quizzes already marked deleted in ENP (quiz_is_deleted) by hard-removing the row and any orphan CPT.
 *
 * @param int $quiz_id ENP quiz_id (must pass spam-owner validation).
 * @return array{enp_removed: bool, cpt_removed: int, cpt_failed: int}
 */
function engage_quiz_permanently_delete_spam_quiz( int $quiz_id ): array {
	$valid = engage_quiz_validate_spam_quiz_ids( array( $quiz_id ) );
	if ( empty( $valid ) ) {
		return array(
			'enp_removed'  => false,
			'cpt_removed'  => 0,
			'cpt_failed'   => 0,
		);
	}

	$post_ids    = engage_quiz_get_all_post_ids_for_enp_quiz( $quiz_id );
	$cpt_removed = 0;
	$cpt_failed  = 0;

	// Remove ENP first so the spam list updates even when CPT delete fails; includes soft-deleted (quiz_is_deleted) rows.
	$enp_removed = engage_quiz_delete_enp_quiz_rows( $quiz_id );

	foreach ( $post_ids as $post_id ) {
		if ( engage_quiz_force_delete_quiz_post( (int) $post_id ) ) {
			++$cpt_removed;
		} else {
			++$cpt_failed;
		}
	}

	// Orphan CPTs can remain if meta was out of sync; search again by ENP id before meta is cleared.
	$remaining = engage_quiz_get_all_post_ids_for_enp_quiz( $quiz_id );
	foreach ( $remaining as $post_id ) {
		if ( engage_quiz_force_delete_quiz_post( (int) $post_id ) ) {
			++$cpt_removed;
		} else {
			++$cpt_failed;
		}
	}

	return array(
		'enp_removed' => $enp_removed,
		'cpt_removed' => $cpt_removed,
		'cpt_failed'  => $cpt_failed,
	);
}

/**
 * Layman: Loads risk tier/score post meta for quiz posts on the current list page.
 *
 * @param array<int, int> $post_ids post_id list.
 * @return array<int, array{tier: string, score: string}>
 */
function engage_quiz_map_risk_meta_for_posts( array $post_ids ): array {
	$out = array();
	foreach ( $post_ids as $pid ) {
		$pid = (int) $pid;
		if ( $pid <= 0 ) {
			continue;
		}
		$out[ $pid ] = array(
			'tier'  => (string) get_post_meta( $pid, '_enp_quiz_risk_tier', true ),
			'score' => (string) get_post_meta( $pid, '_enp_quiz_risk_score', true ),
		);
	}
	return $out;
}

/**
 * Layman: Sets selected ENP quizzes to draft so they are no longer published in the app.
 *
 * @param int[] $quiz_ids Validated ENP quiz_id values.
 * @return array{updated: int, skipped: int}
 */
function engage_quiz_bulk_draft_spam_quizzes( array $quiz_ids ): array {
	global $wpdb;

	$quiz_ids = engage_quiz_validate_spam_quiz_ids( $quiz_ids );
	if ( empty( $quiz_ids ) ) {
		return array(
			'updated' => 0,
			'skipped' => 0,
		);
	}

	$table   = $wpdb->prefix . 'enp_quiz';
	$updated = 0;
	$skipped = 0;

	foreach ( $quiz_ids as $quiz_id ) {
		$current = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT quiz_status FROM `{$table}` WHERE quiz_id = %d",
				$quiz_id
			)
		);
		if ( 'published' !== (string) $current ) {
			++$skipped;
			continue;
		}
		$ok = $wpdb->update(
			$table,
			array( 'quiz_status' => 'draft' ),
			array( 'quiz_id' => $quiz_id ),
			array( '%s' ),
			array( '%d' )
		);
		if ( false !== $ok ) {
			++$updated;
		}
	}

	return array(
		'updated' => $updated,
		'skipped' => $skipped,
	);
}

/**
 * Layman: Permanently deletes up to N synced quiz posts (which removes ENP rows via theme hook).
 *
 * @param int[] $quiz_ids Full validated queue (ordered).
 * @param int   $offset   Index to start at.
 * @param int   $limit    Max quizzes to process this request.
 * @return array{enp_removed: int, cpt_removed: int, cpt_failed: int, processed: int, done: bool}
 */
function engage_quiz_bulk_delete_spam_quizzes_chunk( array $quiz_ids, int $offset, int $limit ): array {
	$quiz_ids = engage_quiz_validate_spam_quiz_ids( $quiz_ids );
	$total    = count( $quiz_ids );
	$offset   = max( 0, $offset );
	$limit    = max( 1, $limit );

	$slice = array_slice( $quiz_ids, $offset, $limit );

	$enp_removed = 0;
	$cpt_removed = 0;
	$cpt_failed  = 0;

	foreach ( $slice as $quiz_id ) {
		$out = engage_quiz_permanently_delete_spam_quiz( (int) $quiz_id );
		if ( ! empty( $out['enp_removed'] ) ) {
			++$enp_removed;
		}
		$cpt_removed += (int) $out['cpt_removed'];
		$cpt_failed  += (int) $out['cpt_failed'];
	}

	$processed = count( $slice );
	$done      = ( $offset + $processed ) >= $total;

	return array(
		'enp_removed' => $enp_removed,
		'cpt_removed' => $cpt_removed,
		'cpt_failed'  => $cpt_failed,
		'processed'   => $processed,
		'done'        => $done,
	);
}

/**
 * Layman: Streams a CSV of spam-user quizzes for audit before destructive actions.
 *
 * @param object[] $rows Row objects from the list table query.
 * @param int[]    $ids  Selected quiz_id values (empty = no export).
 */
function engage_quiz_stream_spam_user_quiz_csv( array $rows, array $ids ): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to export.', 'engage' ) );
	}

	$ids = array_values( array_filter( array_map( 'intval', $ids ) ) );
	if ( empty( $ids ) ) {
		wp_safe_redirect(
			add_query_arg(
				'engage_spam_quiz_export',
				'none',
				admin_url( 'edit.php?post_type=quiz&page=engage-quiz-spam-user-quizzes' )
			)
		);
		exit;
	}

	$map = array();
	foreach ( $rows as $row ) {
		$map[ (int) $row->quiz_id ] = $row;
	}

	$post_map = engage_quiz_map_post_ids_for_quizzes( $ids );

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=spam-user-quizzes-' . gmdate( 'Y-m-d' ) . '.csv' );

	$out = fopen( 'php://output', 'w' );
	if ( false === $out ) {
		exit;
	}

	fputcsv(
		$out,
		array(
			'quiz_id',
			'quiz_title',
			'quiz_status',
			'quiz_owner',
			'owner_login',
			'quiz_created_at',
			'wp_post_id',
			'risk_tier',
			'risk_score',
		)
	);

	foreach ( $ids as $quiz_id ) {
		if ( ! isset( $map[ $quiz_id ] ) ) {
			continue;
		}
		$r       = $map[ $quiz_id ];
		$post_id = isset( $post_map[ $quiz_id ] ) ? (int) $post_map[ $quiz_id ] : 0;
		$tier    = '';
		$score   = '';
		if ( $post_id > 0 ) {
			$tier  = (string) get_post_meta( $post_id, '_enp_quiz_risk_tier', true );
			$score = (string) get_post_meta( $post_id, '_enp_quiz_risk_score', true );
		}
		fputcsv(
			$out,
			array(
				(string) (int) $r->quiz_id,
				(string) $r->quiz_title,
				(string) $r->quiz_status,
				(string) (int) $r->quiz_owner,
				isset( $r->owner_login ) ? (string) $r->owner_login : '',
				isset( $r->quiz_created_at ) ? (string) $r->quiz_created_at : '',
				$post_id > 0 ? (string) $post_id : '',
				$tier,
				$score,
			)
		);
	}

	fclose( $out );
	exit;
}

/**
 * Layman: Fetches all ENP quizzes owned by spam users, with optional status view and search.
 *
 * @param string $status_view all|published|draft|enp_deleted.
 * @param string $search      Search string or empty.
 * @return object[] Rows as stdClass.
 */
function engage_quiz_fetch_spam_user_quizzes( string $status_view = 'all', string $search = '' ): array {
	global $wpdb;

	$table   = $wpdb->prefix . 'enp_quiz';
	$cap_key = $wpdb->prefix . 'capabilities';

	$sql = "
		SELECT q.quiz_id, q.quiz_title, q.quiz_status, q.quiz_owner, q.quiz_created_at, q.quiz_views, q.quiz_is_deleted, u.user_login AS owner_login
		FROM `{$table}` q
		INNER JOIN {$wpdb->users} u ON u.ID = q.quiz_owner
		INNER JOIN {$wpdb->usermeta} um ON um.user_id = u.ID
			AND um.meta_key = %s
			AND um.meta_value LIKE %s
		WHERE NOT EXISTS (
			SELECT 1 FROM {$wpdb->usermeta} um2
			WHERE um2.user_id = u.ID
			AND um2.meta_key = %s
			AND um2.meta_value LIKE %s
		)
	";

	$params = array(
		$cap_key,
		'%' . $wpdb->esc_like( 'spam_user' ) . '%',
		$cap_key,
		'%' . $wpdb->esc_like( 'administrator' ) . '%',
	);

	if ( 'enp_deleted' === $status_view ) {
		$sql .= ' AND q.quiz_is_deleted != 0';
	} else {
		$sql .= ' AND ( q.quiz_is_deleted = 0 OR q.quiz_is_deleted IS NULL )';
		if ( 'published' === $status_view ) {
			$sql     .= ' AND q.quiz_status = %s';
			$params[] = 'published';
		} elseif ( 'draft' === $status_view ) {
			$sql     .= " AND q.quiz_status != %s";
			$params[] = 'published';
		}
	}

	$search = trim( $search );
	if ( '' !== $search ) {
		$like     = '%' . $wpdb->esc_like( $search ) . '%';
		$sql     .= ' AND ( q.quiz_title LIKE %s OR u.user_login LIKE %s';
		$params[] = $like;
		$params[] = $like;
		if ( is_numeric( $search ) ) {
			$sql     .= ' OR q.quiz_id = %d';
			$params[] = (int) $search;
		}
		$sql .= ' )';
	}

	$sql .= ' ORDER BY q.quiz_id ASC';

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- dynamic WHERE built above.
	$rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ) );

	return is_array( $rows ) ? $rows : array();
}

/**
 * Layman: Counts quizzes per status view for subsubsub links (spam owners only).
 *
 * @return array{all: int, published: int, draft: int, enp_deleted: int}
 */
function engage_quiz_count_spam_user_quizzes_by_view(): array {
	static $counts = null;
	if ( is_array( $counts ) ) {
		return $counts;
	}

	$all         = engage_quiz_fetch_spam_user_quizzes( 'all', '' );
	$enp_deleted = engage_quiz_fetch_spam_user_quizzes( 'enp_deleted', '' );
	$published   = 0;
	$draft       = 0;
	foreach ( $all as $row ) {
		if ( isset( $row->quiz_status ) && 'published' === (string) $row->quiz_status ) {
			++$published;
		} else {
			++$draft;
		}
	}
	$counts = array(
		'all'         => count( $all ),
		'published'   => $published,
		'draft'       => $draft,
		'enp_deleted' => count( $enp_deleted ),
	);
	return $counts;
}

/**
 * Layman: Admin URL for the Spam user quizzes screen.
 *
 * @param array<string, string|int> $args Query args.
 * @return string
 */
function engage_quiz_spam_user_quizzes_admin_url( array $args = array() ): string {
	$base = array(
		'post_type' => 'quiz',
		'page'      => 'engage-quiz-spam-user-quizzes',
	);
	return add_query_arg( array_merge( $base, $args ), admin_url( 'edit.php' ) );
}
