<?php
namespace Engage\Models;

class TeamMember extends Article
{
    public $position;       // Team member's position
    public $email;         // Team member's email
    public $phone;         // Team member's phone
    public $education;     // Team member's education
    public $socialMedia;   // Team member's social media links
    public $relatedPosts;  // Related team members

    public function __construct($pid = null)
    {
        parent::__construct($pid);
        
        $this->position = get_field('position', $this->ID);
        $this->email = get_field('email', $this->ID);
        $this->phone = get_field('phone', $this->ID);
        $this->education = get_field('education', $this->ID);
        $this->socialMedia = get_field('social_media', $this->ID);
        $this->relatedPosts = $this->getRelatedPosts();
    }

    protected function getRelatedPosts()
    {
        $related_args = [
            'post_type' => 'team',
            'posts_per_page' => 3,
            'post__not_in' => [$this->ID],
            'orderby' => 'rand',
            'tax_query' => [
                [
                    'taxonomy' => 'team_category',
                    'field' => 'term_id',
                    'terms' => wp_list_pluck($this->categories, 'term_id')
                ]
            ]
        ];

        return \Timber::get_posts($related_args, __CLASS__);
    }
} 