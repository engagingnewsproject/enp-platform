<?php
/**
 * Namespaced helpers kept for drafts that called Engage\QuizSpamAnalysis\get_instance().
 *
 * @package Engage\QuizSpamAnalysis
 */

namespace Engage\QuizSpamAnalysis;

/**
 * Legacy accessor; returns a no-op bootstrap singleton.
 *
 * @return Legacy_Admin_Bootstrap
 */
function get_instance() {
	return Legacy_Admin_Bootstrap::instance();
}
