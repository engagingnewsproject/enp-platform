<?php
namespace Engage\Models;

use Timber\Post;

/**
 * Class Article
 *
 * Extends Timber's Post class to add custom functionality for articles.
 *
 * @package Engage\Models
 */
class Article extends Post
{
    public $categories;     // Categories associated with the article
    public $tags;          // Tags associated with the article
    public $thumbnail;     // Featured image
    public $excerpt;       // Article excerpt
    public $link;          // URL to the article
    public $postType;      // Type of post
    public $terms;         // All terms associated with the post

    /**
     * Initializes the Article object.
     *
     * Calls the parent constructor from Timber\Post to initialize the article with an optional post ID.
     *
     * @param int|null $postID The ID of the post to initialize. Defaults to null.
     */
    public function __construct($pid = null)
    {
        parent::__construct($pid);
        
        $this->categories = $this->getCategories();
        $this->tags = $this->getTags();
        $this->thumbnail = $this->getThumbnail();
        $this->excerpt = $this->getExcerpt();
        $this->link = $this->getLink();
        $this->postType = $this->getPostType();
        $this->terms = $this->getTerms();
    }

    protected function getCategories()
    {
        $categories = [];
        $taxonomies = ['category', 'research-categories', 'blogs-category', 'announcement-category', 'tribe_events_cat'];
        
        foreach ($taxonomies as $taxonomy) {
            if (has_term('', $taxonomy, $this->ID)) {
                $terms = get_the_terms($this->ID, $taxonomy);
                if ($terms && !is_wp_error($terms)) {
                    $categories = array_merge($categories, $terms);
                }
            }
        }
        
        return $categories;
    }

    protected function getTags()
    {
        $tags = [];
        $taxonomies = ['post_tag', 'research-tags'];
        
        foreach ($taxonomies as $taxonomy) {
            if (has_term('', $taxonomy, $this->ID)) {
                $terms = get_the_terms($this->ID, $taxonomy);
                if ($terms && !is_wp_error($terms)) {
                    $tags = array_merge($tags, $terms);
                }
            }
        }
        
        return $tags;
    }

    protected function getTerms()
    {
        $terms = [];
        $taxonomies = get_object_taxonomies($this->post_type);
        
        foreach ($taxonomies as $taxonomy) {
            if (has_term('', $taxonomy, $this->ID)) {
                $post_terms = get_the_terms($this->ID, $taxonomy);
                if ($post_terms && !is_wp_error($post_terms)) {
                    $terms[$taxonomy] = $post_terms;
                }
            }
        }
        
        return $terms;
    }

    public function getLink()
    {
        return get_permalink($this->ID);
    }

    protected function getThumbnail()
    {
        return get_the_post_thumbnail_url($this->ID, 'full');
    }

    protected function getExcerpt()
    {
        return has_excerpt($this->ID) ? get_the_excerpt($this->ID) : wp_trim_words(get_the_content(null, false, $this->ID), 20);
    }
}
