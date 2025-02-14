<?php
namespace Engage\Models;
use Timber;

class LandingPage extends Article {
    
    public $video = false;
    public $directors = false;
    public $menuLinks = false;
    public $sublinkNum = 0;
    
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
        if ($this->directors === false) {
            // Get the selected team members from the ACF field
            $team_members = get_field('project_team_member', $this->ID);

            // Check if the field is empty
            if (empty($team_members)) {
                // Return an empty array if no members are selected
                $this->directors = [];
            } else {
                // Otherwise, get the selected posts
                $this->directors = Timber::get_posts([
                    'post_type' => 'team',
                    'post_status' => 'publish',
                    'post__in' => $team_members,
                    'orderby' => 'post__in',
                    'order' => 'ASC',
                    'posts_per_page' => -1
                ]);
            }
        }
        return $this->directors;
    }
    
    public function getMenuLinks() {
        if($this->menuLinks === false) {
            $this->menuLinks = array(); // Initialize as an empty array
            $menu_items = get_field('page_menu_links'); // Changed from vertical_menu_links
            if($menu_items) {
                foreach($menu_items as $item) {
                    $split = explode(": ", $item['menu_item']);
                    $this->menuLinks[$split[0]] = $split[1];
                }
            }
        }
        return $this->menuLinks;
    }
} 