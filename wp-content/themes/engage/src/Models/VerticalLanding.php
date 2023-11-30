<?php
namespace Engage\Models;
use Timber\PostQuery;

class VerticalLanding extends Article {
	
	public $video = false,
	$directors = false,
	$menulinks = false,
	$sublinkNum = 0;
	
	public function __construct($pid = null) {
		parent::__construct($pid);
	}
	
	public function getVideoEmbedLink() {
		if($this->video === false) {
			$this->video = get_field('video_embed_link');
		}
		return $this->video;
	}
	
	public function getTitleSublinkNum() {
		$this->sublinkNum = get_field('mobile_title_sublinks');
		return $this->sublinkNum;
	}
	
	public function getDirectors() {
		if($this->directors === false) {
			$this->directors = Timber::get_posts(
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
	
	public function getMenuLinks() {
		if($this->menulinks === false) {
			$menu_items = get_field('vertical_menu_links');
			foreach($menu_items as $item) {
				$split = explode(": ", $item['menu_item']);
				$this->menulinks[$split[0]] = $split[1];
			}
		}
		console_log($this->menulinks);
		return $this->menulinks;
	}
}
