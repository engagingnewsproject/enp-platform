    <?php
/**
 * Template Name: Press Template
 * Description: A Page Template for Press
 */

// Code to display Page goes here...
$context = Timber::get_context();

$post = new Engage\Models\Press();

$context['post'] = $post;
function console_log($output, $with_script_tags = true)
{
    $js_code = 'console.log(' . json_encode($output, JSON_HEX_TAG) .');';
    if ($with_script_tags) {
        $js_code = '<script>' . $js_code . '</script>';
    }
    echo $js_code;
}


#

# we need to start generating some rows from effectively what is a csv file

console_log($context);
console_log($post);

Timber::render([ 'page-press.twig' ], $context, ENGAGE_PAGE_CACHE_TIME);
