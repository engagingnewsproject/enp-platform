<?php
namespace Engage\Models;

use Timber\Post;

class Page extends Post
{
    public $template;      // Page template
    public $children;      // Child pages
    public $parent;        // Parent page
    public $thumbnail;     // Featured image
    public $excerpt;       // Page excerpt

    public function __construct($pid = null)
    {
        parent::__construct($pid);
        
        $this->template = $this->getTemplate();
        $this->children = $this->getChildren();
        $this->parent = $this->getParent();
        $this->thumbnail = get_the_post_thumbnail_url($this->ID, 'full');
        $this->excerpt = $this->getExcerpt();
    }

    protected function getTemplate()
    {
        return get_page_template_slug($this->ID);
    }

    protected function getChildren()
    {
        $args = [
            'post_parent' => $this->ID,
            'post_type' => 'page',
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ];

        return \Timber::get_posts($args, __CLASS__);
    }

    protected function getParent()
    {
        return $this->post_parent ? new self($this->post_parent) : false;
    }

    protected function getExcerpt()
    {
        return has_excerpt($this->ID) ? get_the_excerpt($this->ID) : wp_trim_words(get_the_content(null, false, $this->ID), 20);
    }
} 