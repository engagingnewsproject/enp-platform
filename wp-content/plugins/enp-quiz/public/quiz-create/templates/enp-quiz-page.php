<?php
/**
 * The template for displaying Quiz Create Pages
 *
 * This is a wrapper page for all Quiz Create Pages
 * If you want to override this, copy the entire
 * /enp-quiz/public/quiz-create/templates directory into your own theme
 * and edit the ENP_QUIZ_CREATE_TEMPLATES_PATH path of
 * the wp-content/enp-quiz-config.php file to match your template directory.
 *
 * @package    Enp_quiz
 * @subpackage Enp_quiz/public/quiz-create/templates
 * @since      v0.0.1
 */

get_header();
// get all of our SVG files
include( ENP_QUIZ_ROOT.'/public/quiz-create/svg/symbol-defs.svg');?>

<main id="enp-quiz" class="enp-quiz__main" role="main">
<?// this will include our template files
the_content();?>
</main>

<?// This is usually better without the sidebar
get_footer(); ?>
