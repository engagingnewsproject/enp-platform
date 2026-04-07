<?php
/**
 * Legacy filename guard: some branches required this path instead of quiz-spam-admin.php.
 *
 * The canonical loader is quiz-spam-admin.php (see functions.php). This file only prevents
 * a missing-file fatal if an old require remains; it does not duplicate hook registration.
 *
 * @package Engage
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'engage_quiz_spam_admin_init' ) ) {
	require_once __DIR__ . '/quiz-spam-admin.php';
}
