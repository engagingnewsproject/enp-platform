<?php
/**
 * No-op stub for legacy references; the shipped admin UI does not log via this class.
 *
 * @package Engage\QuizSpamAnalysis
 */

namespace Engage\QuizSpamAnalysis;

/**
 * @deprecated Present only so old bootstrap code referencing Logger does not fatal.
 */
final class Logger {

	/** @var self|null */
	private static $instance = null;

	/**
	 * Returns the shared stub instance.
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}
