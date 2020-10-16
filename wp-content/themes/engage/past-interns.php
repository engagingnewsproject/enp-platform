<?php
/**
 * Template Name: Past Intern Template
 * Description: Past Intern page
 */

use Timber\PostQuery;

$context = Timber::get_context();
$post = new TimberPost();

Timber::render( [], $context, ENGAGE_PAGE_CACHE_TIME );