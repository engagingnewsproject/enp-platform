<?php
/**
 * Minimal singleton for legacy Engage\QuizSpamAnalysis\get_instance() callers.
 *
 * @package Engage\QuizSpamAnalysis
 */

namespace Engage\QuizSpamAnalysis;

/**
 * @deprecated Real wiring lives in quiz-spam-admin.php; init() is a no-op.
 */
final class Legacy_Admin_Bootstrap {

	/** @var self|null */
	private static $instance = null;

	/**
	 * Returns the shared legacy bootstrap object.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Intentionally empty; hooks register from quiz-spam-admin.php.
	 */
	public function init(): void {}
}
