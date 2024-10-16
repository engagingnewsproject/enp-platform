<?php
/**
* Set data needed for teams page
*/
namespace Engage\Models;

/**
 * Class TeamArchive
 *
 * Extends the TileArchive class to manage and organize team posts on team archive pages.
 * Handles custom sorting and regrouping of posts based on various criteria, such as ACF fields and taxonomies.
 *
 * @package Engage\Models
 */

class TeamArchive extends TileArchive
{
    public $filtered_posts = [];

    /**
     * Constructor for the TeamArchive class.
     *
     * Initializes the object by calling the parent constructor with the provided query and options.
     * Sorts posts by last name within categories.
     */
    public function __construct($options, $query = false)
    {

        parent::__construct($options, $query); // Props correct order
        if(is_post_type_archive('team')) {
            $this->regroupByCategory();
        }
    }
  
    /**
     * Compare two posts by their last names.
     *
     * This method compares the last names of two posts based on their titles (stored in the `post_title` field).
     * It assumes that the last word in the post title is the last name. The function splits the full name by spaces,
     * extracts the last name, and performs a string comparison using `strcmp()`.
     *
     * @param object $a The first post object to compare, containing a `post_title`.
     * @param object $b The second post object to compare, containing a `post_title`.
     * 
     * @return int Returns:
     *  - A negative number if the last name of post $a is less than that of post $b.
     *  - Zero if the last names are equal.
     *  - A positive number if the last name of post $a is greater than that of post $b.
     */
    public function lastNameCompare($a, $b)
    {
        $titleA = isset($a->post_title) ? $a->post_title : '';
        $titleB = isset($b->post_title) ? $b->post_title : '';

        $nameA = explode(' ', $titleA);
        $nameB = explode(' ', $titleB);

        return strcmp(end($nameA), end($nameB));  // Compare last names
    }

    // Dont need desigOrderCompare, regroupByACFField, regroupByDesignation or getPosts
     /**
     * Reorganizes posts by their category  (taxonomy terms) and then sorts each group alphabetically by last name.
     *
     * Fetches the 'team_category' term for each post and groups them by their slug.
     * The posts are then sorted within each group alphabetically by last name.
     *
     * @return void
     */
    public function regroupByCategory()
    {
        $postsArray = iterator_to_array($this->posts);
        $groups = [];
        // Splits the queried posts by category, using the slugs as keys
        foreach($postsArray as $post) {
            $design_slug = '';
            if(!empty($post->getTermCat()) && isset($post->getTermCat()[0])) { // Ensure getTermCat() returns a valid value
                $design_slug = $post->getTermCat()[0]->slug;
                // var_dump( $design_slug );
            }

            // Adds team members to group based on their category
            if (!array_key_exists($design_slug, $groups)) {
                $groups[$design_slug] = [$post];
            } else {
                array_push($groups[$design_slug], $post);
            }
        }

        // Sorts each designation group alphabetically then merges back to posts
        foreach($groups as &$group) { // "&" used to pass the array element by reference
            usort($group, [$this, 'lastNameCompare']);
        }
        $this->filtered_posts = $groups;
    }
    /**
     * Return the grouped and sorted posts.
     */
    public function getFilteredPosts()
    {
        return $this->filtered_posts;
    }
}
