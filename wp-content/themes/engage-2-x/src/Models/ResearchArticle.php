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
    
	public function init($pid = null)
    {
        parent::__construct($pid);
    }
    
    public function getVertical() {
        if( isset(get_the_terms($this->ID, 'verticals')[0]->term_id)){
            $verticals = $this->terms([
                'taxonomy' => 'verticals',
            ]);
            if (is_array($verticals) && !empty($verticals)) {
                $this->vertical = $verticals[0];
            }
        }
        return $this->vertical;
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
     * Get the Team author as a Timber\Post (or Teammate model) from the ACF 'author' relationship field.
     *
     * @return \Timber\Post|null
     */
    public function getTeamAuthors()
    {
        $authors_ids = get_field('author', $this->ID);
        if (is_array($authors_ids) && count($authors_ids) && $authors_ids[0]) {
            return Timber::get_posts($authors_ids);
        }
        return null;
    }
}
