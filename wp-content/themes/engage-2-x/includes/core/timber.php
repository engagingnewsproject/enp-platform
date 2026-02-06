<?php
/**
 * Timber initialization and configuration
 */

Timber\Timber::init();

Timber::$dirname = ['templates'];

/**
 * By default, Timber does NOT autoescape values. Want to enable Twig's autoescape?
 * No prob! Just set this value to true
 */
Timber::$autoescape = false;

/**
 * Get width and height for an attachment from WordPress metadata (reliable when file path is missing).
 *
 * @param int $attachment_id WordPress attachment post ID.
 * @return array{width?: int, height?: int} Associative array with 'width' and 'height' keys, or empty if unavailable.
 */
function engage_attachment_image_dimensions(int $attachment_id): array
{
	$meta = wp_get_attachment_metadata($attachment_id);
	if (!is_array($meta) || empty($meta['width']) || empty($meta['height'])) {
		return [];
	}
	return [
		'width'  => (int) $meta['width'],
		'height' => (int) $meta['height'],
	];
}

add_filter('timber/twig/functions', function (array $functions): array {
	$functions['attachment_image_dimensions'] = [
		'callable' => 'engage_attachment_image_dimensions',
	];
	return $functions;
}); 