    <?php
/**
 * Template Name: Publications Template
 * Description: A Page Template for Publications
 */

// Code to display Page goes here...
$context = Timber::get_context();

$post = new Engage\Models\Publications();

$context['post'] = $post;
function console_log($output, $with_script_tags = true)
{
    $js_code = 'console.log(' . json_encode($output, JSON_HEX_TAG) .');';
    if ($with_script_tags) {
        $js_code = '<script>' . $js_code . '</script>';
    }
    echo $js_code;
}

Timber::render([ 'page-publications.twig' ], $context, ENGAGE_PAGE_CACHE_TIME);
