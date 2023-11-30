<?php
/**
* The template for displaying Archive pages.
*
* Used to display archive-type pages if nothing more specific matches a query.
* For example, puts together date-based pages if no date.php file exists.
*
* Learn more: http://codex.wordpress.org/Template_Hierarchy
*
* Methods for TimberHelper can be found in the /lib sub-directory
*
* @package  WordPress
* @subpackage  Timber
* @since   Timber 0.2
*/

$context = Timber::context();

$options = [];
$globals = new Engage\Managers\Globals();
$teamGroups = [];

// Set options for sidebar filters
if(get_query_var('vertical_base')) {
	$options = [
		'filters'	=> $globals->getVerticalMenu(get_query_var('verticals')),
	];
} else if (is_post_type_archive(['research']) || is_tax('research-categories')) {
	$options = [
		'options'	=> $globals->getResearchMenu(),
	];	
}
$query = false;
// $book = [
// 	'slug' => ''
// ]
$posts = $context['posts'];


$context['archive'] = $options; // Sidebar filters
$context['archive']['posts'] = $posts; // Setting posts 
// Getting intro 'vertical', 'title' & 'excerpt' ex below:
/**
 * intro: array:3 [▼
 *  "vertical" => WP_Term {#5944 ▼
 *    +term_id: 240
 *    +name: "Journalism"
 *    +slug: "journalism"
 *    +term_group: 0
 *    +term_taxonomy_id: 240
 *    +taxonomy: "verticals"
 *    +description: ""
 *    +parent: 0
 *    +count: 184
 *    +filter: "raw"
 *  }
 *  "title" => "Journalism"
 *  "excerpt" => ""
 * ]
 **/
// long way
//  $context['archive']['intro'] = [
// 	'vertical' => Timber::get_term(),
// 	'title'			=> Timber::get_term(['title' => 'name'])
// ];

// Retrieve the taxonomy terms for the current archive
$context['archive']['test'] = get_query_var('vertical_base');

if(get_query_var('vertical_base')) {
	// var_dump( 'Vertical base' );
	// $context['archive']['intro'] = Timber::get_term(); // GOOD
} else {
	// var_dump( 'Not vertical base' );
	// $context['archive']['intro'] = Timber::get_term('research-categories'); // TRY TO GET research-categories
}

// $cats = Timber::get_terms('research-categories');

// $context['archive']['vertical'] = Timber::get_term();
// $context['archive']['terms'] = $cats;

// Timber::get_posts($query, $options);
// $context['archive']['book'] = $book;

// $tag = Timber::get_term();
// $context['archive'] = $options;
// $context['archive']['tag'] = $tag;
	// foreach ($posts as $post) {
	// 	echo '<p>' . $post->title() . '</p>';
	// }

// Set archive values
// $context['intro'] = [
	// 	'vertical'	=> Timber::get_term('verticals'),
	// 	'title'   => get_the_title(),
	// 	'excerpt' => get_the_excerpt()
	// ];
	
	// $context['archive'] = $options;
	// Merge the $options with default query parameters
	// $query_args = array_merge($options, $archive);
	
	// var_dump( $tags );
	// var_dump( $tags );
	// Get the posts
	
	
	// $tags = Timber::get_term();
	
	
	// $context['archive']['tags'] = $tags;
	
	// $context['archive']['intro'] = [
		// 	'vertical'	=> $tags,
		// 	'title'			=> $tags->name
		// ];
		// $context['archive']['posts'] = Timber::get_posts();
		
		// 'vertical'	=> $tags,
		// 	'title'   	=> $tags->name,
		// ];
		// $context['archive']['intro'] = [
			// 	'vertical'	=> Timber::get_term('verticals'),
			// 	'title'   => get_the_title(),
			// 	'excerpt' => get_the_excerpt()
			// ];
			// $context['archive_meta'] = $archive;
			// var_dump( $context['archive'] );
			
Timber::render( ['archive.twig'], $context, ENGAGE_PAGE_CACHE_TIME);
			