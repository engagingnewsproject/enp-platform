<?php

/**
 * Generic post functionality that extends TimberPost
 */

namespace Engage\Models;

use Timber\PostQuery;
use Timber;

class Archive extends PostQuery
{
    // Properties declaration
    public $posts;            // Holds the posts for the archive
    public $pagination;       // Holds the pagination for the archive
    public $slug;             // Holds the slug of the queried object
    public $intro = [];       // Holds the intro information for the archive
    public $vertical = false; // Holds the vertical of the queried object
    public $category = false; // Holds the category of the queried object
    public $postType;         // Holds the post type of the queried object
    public $queriedObject;    // Holds the queried object
    public $taxonomy;         // Holds the taxonomy of the queried object

    /**
     * Constructor function to initialize the object.
     *
     * @param array $query The query parameters for the archive.
     * @return void
     */
    public function init($query)
    {
        // Set various properties using helper functions
        $this->setVertical();
        $this->setCategory();
        $this->setPostType();

        // Call the parent constructor with the provided query
        parent::__construct($query);
        $this->setQueriedObject(); // Set the queried object
        $this->posts = Timber::get_posts($query); // Get posts using Timber
        $this->pagination = $this->pagination(); // Set pagination
        
        // Check if the queried object is a WP_Term (taxonomy term)
        if (get_class($this->queriedObject) === 'WP_Term') {
            $this->taxonomy = $this->queriedObject->taxonomy; // Set taxonomy
        } elseif (get_class($this->queriedObject) === 'WP_Post_Type') {
            // If the queried object is a post type, set taxonomy to empty or handle accordingly
            $this->taxonomy = ''; // Or handle in a different way if needed
        } else {
            $this->taxonomy = ''; // Set to empty or handle accordingly for other cases
        }
        
        // $this->taxonomy = $this->queriedObject->taxonomy; // Set taxonomy
        $this->slug = isset($this->queriedObject->slug) ? $this->queriedObject->slug : ''; // Set slug
        $this->setIntro(); // Set intro information
    }

    /**
     * Function to set the queried object.
     *
     * @return void
     */
    public function setQueriedObject()
    {

        $Permalinks = new Permalinks();
        // because of our taxonomy rewrites, we're messing with the queried object quite a bit. As a result, we need to use this model to find the right one.
        if ($Permalinks->getQueriedCategory()) {
            $this->queriedObject = $Permalinks->getQueriedCategory();
        } elseif ($this->vertical) {
            $this->queriedObject = $this->vertical;
        } else {
            $this->queriedObject = get_queried_object();
        }
        return;
    }

    /**
     * Function to set the vertical property.
     *
     * @return void
     */
    public function setVertical()
    {
        $Permalinks = new Permalinks();
        $this->vertical = $Permalinks->getQueriedVertical();
    }

    /**
     * Function to set the category property.
     *
     * @return void
     */
    public function setCategory()
    {
        $Permalinks = new Permalinks();
        $this->category = $Permalinks->getQueriedCategory();
    }

    /**
     * Function to set the postType property.
     *
     * @return void
     */
    public function setPostType()
    {
        $Permalinks = new Permalinks();
        $this->postType = $Permalinks->getQueriedPostType();
    }

    /**
     * Sets the archive page title.
     *
     * @return string The title of the archive page.
     */
    public function getTitle()
    {
        // Determine the archive page title based on different conditions
        $title = 'Archive';
        if (is_day()) {
            $title = 'Archive: ' . get_the_date('D M Y');
        } elseif (is_month()) {
            $title = 'Archive: ' . get_the_date('M Y');
        } elseif (is_year()) {
            $title = 'Archive: ' . get_the_date('Y');
        } elseif (get_query_var('query_name') === 'past_events') {
            $title = 'Past Events';
        } elseif (get_query_var('query_name') === 'upcoming_events') {
            $title = 'Upcoming Events';
        } elseif (get_class($this->queriedObject) === 'WP_Term') {
            $title = $this->queriedObject->name;
            $term = $this->queriedObject->taxonomy;
            if (str_contains($term, '-tags')) {
                $title = 'tag - ' . $title;
            }
        } elseif (get_class($this->queriedObject) === 'WP_Post_Type') {
            $title = $this->queriedObject->label;
            if ($this->vertical) {
                // since it's generic, let's add the vertical in front of the name
                $title = $this->vertical->name . ' ' . $title;
            }
        } elseif (get_search_query()) {
            $title = 'Search: ' . get_search_query();
        }
        return $title;
    }

    /**
     * Function to set the intro property.
     *
     * @return void
     */
    public function setIntro()
    {
        // initially set off queried object
        $this->intro = [
            'vertical'	=> $this->vertical,
            'title'   => $this->getTitle(),
            'excerpt' => wpautop($this->queriedObject->description)
        ];

        // if we're on a category that isn't a vertical, then bail. These intro are set by the category name and description
        if (get_class($this->queriedObject) === 'WP_Term' && $this->queriedObject->taxonomy !== 'verticals') {
            return;
        }

        // check if we have one from the settings
        $intros = get_field('archive_landing_pages', 'option');
        if (!$intros) {
            return;
        }
        foreach ($intros as $intro) {
            if ($intro['landing_slug']['value'] === $this->postType->name && $this->vertical == $intro['landing_vertical']) {

                $this->intro['title'] = $intro['landing_page_title'];
                $this->intro['excerpt'] = wpautop($intro['landing_page_content']);
                break;
            }
        }
    }
}
