<?php
namespace Engage\Models;
use Timber\PostQuery;

class ResearchArticle extends Article {

	public $report = false,
		   $summary = false,
			 $video = false,
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

		public function getVideoEmbedLink() {
			if($this->video === false) {
    		$this->video = get_field('video_embed_link');
    	}
    	return $this->video;
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
