<?php

/**
 * Template Name: Annual Report
 * Description: A Page Template for Annual Reports
 */

// Code to display Page goes here...
$context 	= Timber::context();
$post 		= $context['post'];

Timber::render([ 'page-annual-report.twig' ], $context, ENGAGE_PAGE_CACHE_TIME);



// $post = new TimberPost();
// if (isset($post->header_image) && strlen($post->header_image)) {
//     $post->header_image = new Timber\Image($post->header_image);
// }
// if (isset($post->director_image) && strlen($post->director_image)) {
//     $post->director_image = new Timber\Image($post->director_image);
// }
// if (isset($post->member_image) && strlen($post->member_image)) {
//     $post->member_image = new Timber\Image($post->member_image);
// }
// if (isset($post->box_left_image_05) && strlen($post->box_left_image_05)) {
//     $post->box_left_image_05 = new Timber\Image($post->box_left_image_05);
// }
// if (isset($post->box_right_image_05) && strlen($post->box_right_image_05)) {
//     $post->box_right_image_05 = new Timber\Image($post->box_right_image_05);
// }
// if (isset($post->box_left_image_06) && strlen($post->box_left_image_06)) {
//     $post->box_left_image_06 = new Timber\Image($post->box_left_image_06);
// }
// if (isset($post->box_right_image_06) && strlen($post->box_right_image_06)) {
//     $post->box_right_image_06 = new Timber\Image($post->box_right_image_06);
// }
// if (isset($post->zoom_meet_img_01) && strlen($post->zoom_meet_img_01)) {
//     $post->zoom_meet_img_01 = new Timber\Image($post->zoom_meet_img_01);
// }
// if (isset($post->zoom_meet_img_02) && strlen($post->zoom_meet_img_02)) {
//     $post->zoom_meet_img_02 = new Timber\Image($post->zoom_meet_img_02);
// }
// if (isset($post->texas_safe_img) && strlen($post->texas_safe_img)) {
//     $post->texas_safe_img = new Timber\Image($post->texas_safe_img);
// }
// $context['post'] = $post;
// $context['the_post_template'] = $post->_wp_page_template;
// Timber::render(['page-annual-report.twig'], $context, ENGAGE_PAGE_CACHE_TIME);
