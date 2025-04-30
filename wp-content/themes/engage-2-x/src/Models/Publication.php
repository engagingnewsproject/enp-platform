<?php
namespace Engage\Models;

use Timber;
use Timber\Post;

class Publication extends Post {
    public $authors = false;
    public $publication_date = false;
    public $subtitle = false;
    public $url = false;
    public $id;

    public function init($pid = null)
    {
        parent::__construct($pid);
    }

    public function getAuthors() {
        if($this->authors === false) {
            $this->authors = get_field('publication_authors');
            // If no authors set, return empty string
            if (!$this->authors) {
                $this->authors = '';
            }
        }
        return $this->authors;
    }

    public function getPublicationDate() {
        if($this->publication_date === false) {
            $this->publication_date = get_field('publication_date');
            // If no publication date set, use the post date
            if (!$this->publication_date) {
                $this->publication_date = get_the_date('Y-m-d', $this->ID);
            }
        }
        return $this->publication_date;
    }

    public function getSubtitle() {
        if($this->subtitle === false) {
            $this->subtitle = get_field('publication_subtitle');
            // If no subtitle set, return empty string
            if (!$this->subtitle) {
                $this->subtitle = '';
            }
        }
        return $this->subtitle;
    }

    public function getUrl() {
        if($this->url === false) {
            $this->url = get_field('publication_url');
            // If no URL set, return empty string
            if (!$this->url) {
                $this->url = '';
            }
        }
        return $this->url;
    }
} 