<?php
namespace Engage\Models;

class ResearchArticle extends Article {

	public $report = false,
		   $summary = false,
		   $resources = null;

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

}