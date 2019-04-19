<?php
/**
 * The core plugin class that is used to choose which
 * classes to run
 * Used for phpunit tests
 */
require_once '../../enp-quiz-config.php';
 // which files are required for this to run?
require ENP_QUIZ_PLUGIN_DIR . 'includes/class-enp_quiz.php';
require ENP_QUIZ_PLUGIN_DIR . 'includes/class-enp_quiz-slugify.php';
require ENP_QUIZ_PLUGIN_DIR . 'includes/class-enp_quiz-quiz.php';
require ENP_QUIZ_PLUGIN_DIR . 'includes/class-enp_quiz-question.php';
require ENP_QUIZ_PLUGIN_DIR . 'includes/class-enp_quiz-mc_option.php';
require ENP_QUIZ_PLUGIN_DIR . 'includes/class-enp_quiz-slider.php';
require ENP_QUIZ_PLUGIN_DIR . 'includes/class-enp_quiz-slider-result.php';
require ENP_QUIZ_PLUGIN_DIR . 'includes/class-enp_quiz-slider-ab_result.php';
require ENP_QUIZ_PLUGIN_DIR . 'includes/class-enp_quiz-user.php';
require ENP_QUIZ_PLUGIN_DIR . 'includes/class-enp_quiz-nonce.php';
require_once ENP_QUIZ_PLUGIN_DIR . 'includes/class-enp_quiz-cookies.php';
require_once ENP_QUIZ_PLUGIN_DIR . 'includes/class-enp_quiz-search_quizzes.php';
require_once ENP_QUIZ_PLUGIN_DIR . 'includes/class-enp_quiz-paginate.php';
require_once ENP_QUIZ_PLUGIN_DIR . 'public/quiz-take/includes/class-enp_quiz-cookies_quiz_take.php';
require ENP_QUIZ_PLUGIN_DIR . 'includes/class-enp_quiz-ab_test.php';
require ENP_QUIZ_PLUGIN_DIR . 'includes/class-enp_quiz-quiz_ab_test_result.php';
require ENP_QUIZ_PLUGIN_DIR . 'includes/class-enp_quiz-question_ab_test_result.php';
require ENP_QUIZ_PLUGIN_DIR . 'includes/class-enp_quiz-mc_option_ab_test_result.php';
require ENP_QUIZ_PLUGIN_DIR . 'includes/class-enp_embed-site.php';
require ENP_QUIZ_PLUGIN_DIR . 'includes/class-enp_embed-site-type.php';
require ENP_QUIZ_PLUGIN_DIR . 'includes/class-enp_embed-site-bridge.php';
require ENP_QUIZ_PLUGIN_DIR . 'includes/class-enp_embed-quiz.php';

// Database
require ENP_QUIZ_PLUGIN_DIR . 'database/class-enp_quiz_db.php';
require ENP_QUIZ_PLUGIN_DIR . 'database/class-enp_quiz_save.php';
require ENP_QUIZ_PLUGIN_DIR . 'database/class-enp_quiz_save_quiz.php';
require ENP_QUIZ_PLUGIN_DIR . 'database/class-enp_quiz_save_quiz_option.php';
require ENP_QUIZ_PLUGIN_DIR . 'database/class-enp_quiz_save_question.php';
require ENP_QUIZ_PLUGIN_DIR . 'database/class-enp_quiz_save_mc_option.php';
require ENP_QUIZ_PLUGIN_DIR . 'database/class-enp_quiz_save_slider.php';
require ENP_QUIZ_PLUGIN_DIR . 'database/class-enp_quiz_save_quiz_response.php';
require ENP_QUIZ_PLUGIN_DIR . 'database/class-enp_quiz_save_ab_test.php';
require ENP_QUIZ_PLUGIN_DIR . 'database/class-enp_quiz_save_embed_quiz.php';
require ENP_QUIZ_PLUGIN_DIR . 'database/class-enp_quiz_save_embed_site.php';


// Database for Quiz Take side (only need it to reset data)
require_once ENP_QUIZ_PLUGIN_DIR . 'database/class-enp_quiz_save_quiz_take.php';
require_once ENP_QUIZ_PLUGIN_DIR . 'database/class-enp_quiz_save_quiz_take_quiz_data.php';

// Test Functions
require ENP_QUIZ_PLUGIN_DIR .'tests/EnpTestCase.php';
