<?php
namespace Engage\Models;

class BlogPost extends Article
{
    public $author;         // Blog post author
    public $authorBio;      // Author biography
    public $authorImage;    // Author image
    public $relatedPosts;   // Related blog posts

    public function __construct($pid = null)
    {
        parent::__construct($pid);
        
        $this->author = get_field('blog_author', $this->ID);
        $this->authorBio = get_field('blog_author_bio', $this->ID);
        $this->authorImage = get_field('blog_author_image', $this->ID);
        $this->relatedPosts = $this->getRelatedPosts();
    }

    protected function getRelatedPosts()
    {
        $related_args = [
            'post_type' => 'blogs',
            'posts_per_page' => 3,
            'post__not_in' => [$this->ID],
            'orderby' => 'rand',
            'tax_query' => [
                [
                    'taxonomy' => 'blogs-category',
                    'field' => 'term_id',
                    'terms' => wp_list_pluck($this->categories, 'term_id')
                ]
            ]
        ];

        return \Timber::get_posts($related_args, __CLASS__);
    }
} 