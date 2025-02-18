<?php
namespace Engage\Models;

use Timber;
use Timber\Post;

class ResearchArticle extends Post {

	public $report = false;
	public $summary = false;
	public $video = false;
    public $researchers = false;
    public $id;
    public $authors;        // Research authors
    public $citation;       // Citation information
    public $download;       // Download link
    public $featured;       // Featured status
    public $methodology;    // Research methodology
    public $publication;    // Publication details
    public $relatedPosts;   // Related research posts
    
	public function __construct($pid = null)
    {
        parent::__construct($pid);
        
        $this->authors = get_field('authors', $this->ID);
        $this->citation = get_field('citation', $this->ID);
        $this->download = get_field('download', $this->ID);
        $this->featured = get_field('featured_research', $this->ID);
        $this->methodology = get_field('methodology', $this->ID);
        $this->publication = get_field('publication', $this->ID);
        $this->relatedPosts = $this->getRelatedPosts();
    }
    
    public function getReport() {
    	if($this->report === false) {
    		$this->report = get_field('report_here');
    	}
    	return $this->report;
    }

    public function getSummary() {
    	if($this->summary === false) {
    		$this->summary = get_field('summary_research_');
    	}
    	return $this->summary;
    }

    public function getVideoEmbedLink() {
        if($this->video === false) {
            $this->video = get_field('video_embed_link');
        }
        return $this->video;
    }

    public function getResearchers() {

        if($this->researchers === false) {
            $this->researchers = Timber::get_posts([
                'post_type'=> 'team',
                'post_status' => 'publish',
                'post__in' => $this->meta('project_team_member', $this->ID),
                'orderby' => 'post__in',
                'order' => 'ASC',
                'posts_per_page' => -1
            ]);
        }
        return $this->researchers;
    }

    /**
     * Get related posts based on shared categories
     * 
     * @return array Array of related ResearchArticle posts
     */
    public function getRelatedPosts() {
        $categories = $this->terms('research-categories');
        $category_ids = array_map(function($cat) {
            return $cat->id;
        }, $categories);

        $args = array(
            'post_type' => 'research',
            'posts_per_page' => 3,
            'post__not_in' => array($this->ID),
            'tax_query' => array(
                array(
                    'taxonomy' => 'research-categories',
                    'field' => 'id',
                    'terms' => $category_ids
                )
            )
        );

        // Use Timber 2.0 approach for getting posts
        return Timber::get_posts($args);
    }
}
