<?php
/**
 * Template Name: Accessibility Report
 * Description: Private page showing Lighthouse a11y results (admin only). Data from yarn lighthouse-a11y.
 */

if (!current_user_can('manage_options')) {
	wp_die(esc_html__('You do not have permission to view this page.'), '', ['response' => 403]);
}

$context = Timber::context();
$context['post'] = $context['post'] ?? Timber::get_post();

$csv_path = get_stylesheet_directory() . '/scripts/accessibility/reports/lighthouse-a11y/accessibility-below-100.csv';
$context['accessibility_report'] = [];
$context['has_report'] = false;

$context['a11y_ajax_url'] = admin_url('admin-ajax.php');
$context['a11y_nonce']   = wp_create_nonce('engage_a11y_report');
/** Production base URL for "Prod" column links (replaces local origin). */
$context['a11y_prod_base_url'] = 'https://mediaengagement.org';

if (is_readable($csv_path)) {
	$rows = [];
	$handle = fopen($csv_path, 'r');
	if ($handle) {
		$header = fgetcsv($handle);
		while (($row = fgetcsv($handle)) !== false) {
			if (count($row) >= 2) {
				$url = trim($row[0], '"');
				$parsed = parse_url($url);
				$path = isset($parsed['path']) ? $parsed['path'] : '/';
				$rewritten = home_url($path);
				if (!empty($parsed['query'])) {
					$rewritten .= '?' . $parsed['query'];
				}
				if (!empty($parsed['fragment'])) {
					$rewritten .= '#' . $parsed['fragment'];
				}
				$prod_url = $context['a11y_prod_base_url'] . $path;
				if (!empty($parsed['query'])) {
					$prod_url .= '?' . $parsed['query'];
				}
				if (!empty($parsed['fragment'])) {
					$prod_url .= '#' . $parsed['fragment'];
				}
				$done = isset($row[2]) && (string) $row[2] === '1';
				$dev  = isset($row[3]) ? trim((string) $row[3]) : '';
				$rows[] = [
					'url'       => $rewritten,
					'path'      => $path,
					'prod_url'  => $prod_url,
					'score'     => (int) $row[1],
					'completed' => $done,
					'dev'       => $dev,
				];
			}
		}
		fclose($handle);
	}
	$context['accessibility_report'] = $rows;
	$context['has_report'] = count($rows) > 0;
}

Timber::render(['page-accessibility-report.twig'], $context, ENGAGE_PAGE_CACHE_TIME);
