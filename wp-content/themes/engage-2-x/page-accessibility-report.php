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

if (is_readable($csv_path)) {
	$rows = [];
	$handle = fopen($csv_path, 'r');
	if ($handle) {
		$header = fgetcsv($handle);
		while (($row = fgetcsv($handle)) !== false) {
			if (count($row) >= 2) {
				$rows[] = [
					'url'   => $row[0],
					'score' => (int) $row[1],
				];
			}
		}
		fclose($handle);
	}
	$context['accessibility_report'] = $rows;
	$context['has_report'] = count($rows) > 0;
}

Timber::render(['page-accessibility-report.twig'], $context, ENGAGE_PAGE_CACHE_TIME);
