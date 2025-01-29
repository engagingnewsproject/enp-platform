<?php

/**
 * The template for displaying Archive pages.
 *
 * Used to display archive-type pages if nothing more specific matches a query.
 * For example, puts together date-based pages if no date.php file exists.
 *
 * Learn more: https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 */



$context = Timber::context();
global $wp_query;
use Engage\Models\TileArchive;

use Engage\Models\TeamArchive;

$globals = new Engage\Managers\Globals();


$options = [
    'categories' => ['principal-investigators', 'staff', 'student-researchers', 'research-collaborators', 'board', 'alumni']
];
$teamGroups = [];


if ((
    is_post_type_archive(['team']) || 
    is_tax('team_category'))) {
      
	// ACF Gr: Frontend Team Settings
	// ACF Group Settings: Location Rules / Taxonomy: team_category
	// This group setting will show the fields on the team_category taxonomy page
	// Example: WP Admin > Team > Team Category > click category > Frontend Team Settings
	
	// TODO: Add ACF Frontend Team Settings color pickers for Title and Supertitle (maybe more!)
	
	// Get all team categories from WordPress, including empty ones
	$team_categories = get_terms([
		'taxonomy' => 'team_category',
		'hide_empty' => false,
	]);
	// Initialize arrays in the context to store processed titles and subtitles
	$context['processed_titles'] = [];
	// Default category titles if no ACF fields are set
	$context['category_titles'] = [
		'principal-investigators' => "Principal Investigators", 
		'staff' => "Staff", 
		'student-researchers' => "Student Researchers",
		'research-collaborators' => "Research Collaborators", 
		'board' => "Board", 
		'alumni' => "Alumni"
	];
	
	// Loop through each category to process titles and supertitles
	foreach ($team_categories as $category) {
		// Get the category's URL-friendly name (slug)
		$slug = $category->slug;
		
		// Initialize the processed title array for this category
		// Example: $context['processed_titles']['principal-investigators']
		$context['processed_titles'][$slug] = [
			'has_acf' => false,
			'title' => '',
			'subtitle' => ''
		];

        // Get custom title from ACF field 'category_title' for this team category
		// Properties:
		// - 'category_title' is the name of the custom field created in ACF
		// - 'team_category_' is a prefix that ACF uses to identify taxonomy terms
		// - '$category->term_id' is the unique ID number of the current category
        // Example: If term_id is 5, looks for field in 'team_category_5'
		$acf_title = get_field('category_title', 'team_category_' . $category->term_id);
		$acf_supertitle = get_field('category_supertitle', 'team_category_' . $category->term_id);

		if ($acf_title || $acf_supertitle) {
			// Use ACF fields if they exist
			$context['processed_titles'][$slug] = [
				'has_acf' => true,
				'title' => $acf_title,
				'supertitle' => $acf_supertitle
			];
		} else {
			// Fall back to default title and split it if necessary
			$default_title = $context['category_titles'][$slug] ?? '';
			$title_parts = explode(' ', $default_title);
			// If no ACF fields exist, process the default title
			// For "Principal Investigators": 
			// - If multiple words: title="Investigators", subtitle="Principal"
			// - If single word: title="Word", subtitle=""
			$context['processed_titles'][$slug] = [
				'has_acf' => false, 													// Indicates no custom fields were found
				'title' => count($title_parts) > 1 ? $title_parts[1] : $title_parts[0], // For multi-word titles, use second word, otherwise use first
				'supertitle' => count($title_parts) > 1 ? $title_parts[0] : ''			// For multi-word titles, use first word as subtitle
			];
		}
	}
		
    $archive = new TeamArchive($options, $wp_query); // Props need to be in correct order
        // No need to set context archive, we'll do that below
        $context['filtered_posts'] = $archive->getFilteredPosts(); // Retrieve grouped and sorted posts from the TeamArchive class
        $context['category_titles'] = ['principal-investigators' =>"Principal Investigators", 'staff'=>"Staff", 'student-researchers'=>"Student Researchers",'research-collaborators'=>"Research Collaborators", 'board'=>"Board", 'alumni' =>"Alumni"];

        $context['categories'] = ['principal-investigators', 'staff', 'student-researchers', 'research-collaborators', 'board', 'alumni'];
}
Timber::render(['page-team.twig'], $context);