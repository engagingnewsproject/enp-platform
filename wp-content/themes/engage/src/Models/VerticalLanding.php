<?php
namespace Engage\Models;
use Timber\PostQuery;

class VerticalLanding extends Article {

	public $video = false,
				 $directors = false;

	  public function __construct($pid = null) {
        parent::__construct($pid);
    }

		public function getVideoEmbedLink() {
			if($this->video === false) {
    		$this->video = get_field('video_embed_link');
    	}
    	return $this->video;
		}

		public function getDirectors() {
      if($this->directors === false) {
          $this->directors = new PostQuery(
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
      return $this->directors;
    }
}
