<?php
namespace Engage\Models;

use Timber;
use Timber\Post;

/**
 * PressArticle Model
 * 
 * Extends Timber's Post class to handle press article specific functionality.
 * This model manages press article data including publisher, publication date, and URL.
 * 
 * @package Engage\Models
 */
class PressArticle extends Post {
    /**
     * Publisher of the press article
     * @var string|false
     */
    public $press_article_publisher = false;

    /**
     * Publication date of the press article
     * @var string|false
     */
    public $press_article_publication_date = false;

    /**
     * URL to the original press article
     * @var string|false
     */
    public $press_article_url = false;

    /**
     * Initialize the press article
     * 
     * Sets up the post and logs debug information for ACF fields
     * 
     * @param int|null $pid Post ID to initialize
     * @return void
     */
    public function init($pid = null)
    {
        parent::__construct($pid);
    }

    /**
     * Get the publisher of the press article
     * 
     * Retrieves the publisher from ACF field 'press_article_publisher'.
     * Returns an empty string if no publisher is set.
     * 
     * @return string The publisher name or empty string if not set
     */
    public function getPressArticlePublisher() {
        if($this->press_article_publisher === false) {
            $this->press_article_publisher = get_field('press_article_publisher', $this->ID);
            // If no publisher set, return empty string
            if (!$this->press_article_publisher) {
                $this->press_article_publisher = '';
            }
        }
        return $this->press_article_publisher;
    }

    /**
     * Get the publication date of the press article
     * 
     * Retrieves the publication date from ACF field 'press_article_publication_date'.
     * Falls back to the post's creation date if no publication date is set.
     * 
     * @return string The publication date in Y-m-d format
     */
    public function getPressArticlePublicationDate() {
        if($this->press_article_publication_date === false) {
            $date = get_field('press_article_publication_date', $this->ID);
            // Convert the date to the site's timezone
            $timestamp = strtotime($date);
            $this->press_article_publication_date = date('Y-m-d', $timestamp);
        }
        return $this->press_article_publication_date;
    }
    
    /**
     * Get the URL of the press article
     * 
     * Retrieves the URL from ACF field 'press_article_url'.
     * Returns an empty string if no URL is set.
     * 
     * @return string The article URL or empty string if not set
     */
    public function getPressArticleUrl() {
        if($this->press_article_url === false) {
            $this->press_article_url = get_field('press_article_url', $this->ID);
            // If no URL set, return empty string
            if (!$this->press_article_url) {
                $this->press_article_url = '';
            }
        }
        return $this->press_article_url;
    }
} 