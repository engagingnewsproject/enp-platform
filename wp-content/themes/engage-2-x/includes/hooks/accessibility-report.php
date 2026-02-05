<?php
/**
 * Accessibility report: "done" state stored in the CSV file (url, score, done)
 * so it can be committed and deployed. AJAX handler updates the CSV.
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Path to the accessibility report CSV (theme-relative). Columns: url, score, done (0|1).
 *
 * @return string
 */
function engage_a11y_get_csv_path(): string {
	return get_stylesheet_directory() . '/scripts/accessibility/reports/lighthouse-a11y/accessibility-below-100.csv';
}

/**
 * AJAX handler: toggle done for a path by updating the CSV. Requires manage_options.
 */
function engage_a11y_ajax_toggle_complete(): void {
	if (!check_ajax_referer('engage_a11y_report', 'nonce', false)) {
		wp_send_json_error(['message' => 'Invalid nonce']);
	}
	if (!current_user_can('manage_options')) {
		wp_send_json_error(['message' => 'Forbidden']);
	}

	$path = isset($_POST['path']) ? wp_unslash(sanitize_text_field($_POST['path'])) : '';
	if ($path === '') {
		wp_send_json_error(['message' => 'Missing path']);
	}

	$csv_path = engage_a11y_get_csv_path();
	if (!is_readable($csv_path)) {
		wp_send_json_error(['message' => 'Report file not found']);
	}
	if (!is_writable($csv_path)) {
		wp_send_json_error(['message' => 'Report file is not writable']);
	}

	$rows = [];
	$handle = fopen($csv_path, 'r');
	if (!$handle) {
		wp_send_json_error(['message' => 'Could not read report file']);
	}
	$header = fgetcsv($handle);
	$now_completed = null;
	while (($row = fgetcsv($handle)) !== false) {
		if (count($row) >= 2) {
			$row_url = trim($row[0], '"');
			$row_path = '';
			if ($row_url !== '') {
				$parsed = parse_url($row_url);
				$row_path = isset($parsed['path']) ? $parsed['path'] : '/';
			}
			$done = (isset($row[2]) && (string) $row[2] === '1') ? 1 : 0;
			if ($row_path === $path) {
				$done = $done ? 0 : 1;
				$now_completed = (bool) $done;
			}
			$rows[] = [$row[0], $row[1], $done];
		}
	}
	fclose($handle);

	if ($now_completed === null) {
		wp_send_json_error(['message' => 'Path not found in report']);
	}

	$out = fopen($csv_path, 'w');
	if (!$out) {
		wp_send_json_error(['message' => 'Could not write report file']);
	}
	fputcsv($out, ['url', 'score', 'done']);
	foreach ($rows as $row) {
		fputcsv($out, $row);
	}
	fclose($out);

	wp_send_json_success([
		'completed' => $now_completed,
		'path'      => $path,
	]);
}

add_action('wp_ajax_engage_a11y_toggle_complete', 'engage_a11y_ajax_toggle_complete');
