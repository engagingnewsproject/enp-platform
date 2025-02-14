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

    protected function getRelatedPosts()
    {
        // Get related posts based on categories and tags, not verticals
        $related_args = [
            'post_type' => 'research',
            'posts_per_page' => 3,
            'post__not_in' => [$this->ID],
            'orderby' => 'rand',
            'tax_query' => [
                'relation' => 'OR',
                [
                    'taxonomy' => 'research-categories',
                    'field' => 'term_id',
                    'terms' => wp_list_pluck($this->categories, 'term_id')
                ],
                [
                    'taxonomy' => 'research-tags',
                    'field' => 'term_id',
                    'terms' => wp_list_pluck($this->tags, 'term_id')
                ]
            ]
        ];

        return \Timber::get_posts($related_args, __CLASS__);
    }
}
