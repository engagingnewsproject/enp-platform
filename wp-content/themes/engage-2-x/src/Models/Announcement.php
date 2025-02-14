<?php
namespace Engage\Models;

class Announcement extends Article
{
    public $date;           // Announcement date
    public $relatedPosts;   // Related announcements
    public $externalLink;   // External link if any

    public function __construct($pid = null)
    {
        parent::__construct($pid);
        
        $this->date = get_the_date('F j, Y', $this->ID);
        $this->externalLink = get_field('external_link', $this->ID);
        $this->relatedPosts = $this->getRelatedPosts();
    }

    protected function getRelatedPosts()
    {
        $related_args = [
            'post_type' => 'announcement',
            'posts_per_page' => 3,
            'post__not_in' => [$this->ID],
            'orderby' => 'rand',
            'tax_query' => [
                [
                    'taxonomy' => 'announcement-category',
                    'field' => 'term_id',
                    'terms' => wp_list_pluck($this->categories, 'term_id')
                ]
            ]
        ];

        return \Timber::get_posts($related_args, __CLASS__);
    }

    public function getLink()
    {
        return $this->externalLink ?: parent::getLink();
    }
} 