<?php
/**
 * Back-compat class name for drafts that expected Engage\QuizSpamAnalysis\Analysis.
 *
 * @package Engage\QuizSpamAnalysis
 */

namespace Engage\QuizSpamAnalysis;

use Engage\Admin\QuizSpamAnalysis\QuizSpamAnalyzer;

/**
 * @deprecated Use Engage\Admin\QuizSpamAnalysis\QuizSpamAnalyzer.
 */
class Analysis extends QuizSpamAnalyzer {}
