<?php
namespace Engage\Models;
use Timber\PostQuery;

class ResearchArticle extends Article {

	public $report = false,
		   $summary = false,
			 $video = false,
		   $resources = null,
       $researchers = false;

	public function __construct($pid = null)
    {
        parent::__construct($pid);
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

		public function getVideo() {
			if($this->video === false) {
    		$this->video = get_field('video_here');
    	}
    	return $this->video;
		}

    public function getResources() {
    	if($this->resources === false) {
    		$this->resources = [];
    		$resources = explode("\n", get_post_meta($post->ID, 'research_resources', true));
    		if(!empty($resources)) {
    			foreach($resources as $link) {
    				$this->resources[] = explode('|', $link);
    			}
    		}

    	}
    	return $this->resources;
    }

    public function getResearchers() {
        if($this->researchers === false) {
            $this->researchers = new PostQuery(
                [
                    'post_type'=> 'team',
                    'post_status' => 'publish',
                    'post__in' => get_field('project_team_member', $this->ID),
                    'orderby' => 'post__in',
                    'order' => 'ASC',
                    'posts_per_page' => -1
                ],
                'Engage\Models\Teammate'
            );
        }
        return $this->researchers;
    }
}
