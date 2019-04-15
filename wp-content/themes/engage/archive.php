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


$context = Timber::get_context();

$options = [];
$globals = new Engage\Managers\Globals();
$articleClass = 'Engage\Models\Article';
$teamGroups = [];


if(get_query_var('vertical_base')) {
    if(is_post_type_archive(['team'])) {
        $articleClass = 'Engage\Models\Teammate';
    }
	$options = [
		'filters'	=> $globals->getVerticalMenu(get_query_var('verticals'))
	];
}
else if(is_post_type_archive(['research']) || is_tax('research-categories')) {
	$articleClass = 'Engage\Models\ResearchArticle';
	$options = [
		'filters'	=> $globals->getResearchMenu()
	];
}
else if(is_post_type_archive(['announcement']) || is_tax('announcement-category')) {
	$options = [
		'filters'	=> $globals->getAnnouncementMenu()
	];
}
else if(is_post_type_archive(['case-study']) || is_tax('case-study-category')) {
	$options = [
		'filters'	=> $globals->getCaseStudyMenu()
	];
}
else if(is_post_type_archive(['team']) || is_tax('team_category')) {
    $articleClass = 'Engage\Models\Teammate';
  	$options = [
  		'filters'	=> $globals->getTeamMenu()
  	];

} else if(is_post_type_archive(['tribe_events'])) {
	$articleClass = 'Engage\Models\Event';
	$options = [
		'filters'	=> $globals->getEventMenu()
	];
}

// build intro
$query = false;
$archive = new Engage\Models\TileArchive($options, $query, $articleClass);
$context['archive'] = $archive;

$current = 0;

if(is_post_type_archive(['team']) || is_tax('team_category')) {
  // Build groupings of team categories and team members to fit those categories
  $filters = $archive -> filters;
  foreach ($filters["terms"] as $filter){
    foreach ($filter["terms"] as $subfilter){
        if( $subfilter['current'] ) {
          $current = $subfilter['title'];
        }
        $teamGroups[$subfilter["title"]] = [
            "name" => $subfilter["title"],
            "mates" => [],
        ];
        foreach ($archive->posts as $mate) {
          foreach ($mate->getTerms() as $category) {
            if ($category->name == $subfilter["title"]) {
              array_push($teamGroups[$subfilter["title"]]["mates"], $mate);
            }
          }
        }
    }
  }

  if($current) {
    foreach ($teamGroups as $group) {
      if($group["name"] != $current) {
        $teamGroups[$group["name"]] = [];
      }
    }
  }

  $context['archive']['teamGroups'] = $teamGroups;
}


if(get_query_var('verticals') == 'media-ethics' && $_SERVER['REQUEST_URI'] == '/vertical/media-ethics/') {
  $context['navTiles'] = [
    [
      "title" => "Digital Ethics",
      "content" => "Ethics and controversies in online and digital technologies",
      "imgSrc" => "https://images.unsplash.com/photo-1486312338219-ce68d2c6f44d?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1952&q=80",
      "imgAlt" => "",
      "link" => "/vertical/media-ethics/research/category/digital-ethics/"
    ],
    [
      "title" => "Journalism Ethics",
      "content" => "Exploring values and decisions in how news is reported and covered",
      "imgSrc" => "https://images.unsplash.com/photo-1498644035638-2c3357894b10?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=2134&q=80",
      "imgAlt" => "",
      "link" => "/vertical/media-ethics/research/category/journalism-ethics/"
    ],
    [
      "title" => "Advertising/Public Relations Ethics",
      "content" => "Ethical decisions and conflicting values in advertising",
      "imgSrc" => "https://images.unsplash.com/photo-1504913659239-6abc87875a63?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1950&q=80",
      "imgAlt" => "",
      "link" => "/vertical/media-ethics/research/category/advertising-public-relation-ethics/"
    ],
    [
      "title" => "Free Speech",
      "content" => "Examining the limits and values of free speech in a variety of forms",
      "imgSrc" => "https://images.unsplash.com/photo-1533228100845-08145b01de14?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1956&q=80",
      "imgAlt" => "",
      "link" => "/vertical/media-ethics/research/category/free-speech/"
    ],
    [
      "title" => "Aesthetics, Art, & Ethics",
      "content" => "Controversies over the nature and value of various art forms and practices",
      "imgSrc" => "https://images.unsplash.com/photo-1456086272160-b28b0645b729?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=2089&q=80",
      "imgAlt" => "",
      "link" => "/vertical/media-ethics/research/category/aesthetics-art-ethics/"
    ],
    [
      "title" => "Health Communications",
      "content" => "Examining decisions/values in conveying health-related messages to the public",
      "imgSrc" => "https://images.unsplash.com/photo-1513224502586-d1e602410265?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1489&q=80",
      "imgAlt" => "",
      "link" => "/vertical/media-ethics/research/category/health-communication-ethics/"
    ],
    [
      "title" => "Sports Media & Journalism Ethics",
      "content" => "Controversies in sports media coverage and related communication practices",
      "imgSrc" => "https://images.unsplash.com/photo-1521412644187-c49fa049e84d?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=634&q=80",
      "imgAlt" => "",
      "link" => "/vertical/media-ethics/research/category/sports-media-journalism-ethics/"
    ],
    [
      "title" => "How to Use Case Studies in Your Class",
      "content" => "Read Report",
      "imgSrc" => "https://sghsri.github.io/img/cme_logo.png",
      "imgAlt" => "",
      "link" => "/research/how-to-use-case-studies-in-class/"
    ]
  ];
  Timber::render( ['ethics-landing-page.twig'], $context, ENGAGE_PAGE_CACHE_TIME);
  return;
}

function comparator($a, $b) {
  $a_index = get_field('index', 'team_category_' . $a['ID']);
  $b_index = get_field('index', 'team_category_' . $b['ID']);
  if($a_index == $b_index) {
    return 0;
  }

  return $a_index < $b_index ? -1 : 1;
}

// This will ensure that our columns stay sorted for our team member names. 
usort($context['archive']->filters['terms']['journalism']['terms'], "comparator");

Timber::render( ['archive.twig'], $context, ENGAGE_PAGE_CACHE_TIME);
