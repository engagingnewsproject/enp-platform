<?php
namespace Engage\Models;
use Engage\Managers\Queries as Queries;
use Timber\PostQuery;
use Timber\Post;

class Homepage extends Post {

	public  $funders,
            $verticals,
            $Query,
            $recent;

	public function __construct($pid = null)
    {
        parent::__construct($pid);
        $this->Query = new Queries();
        $this->setFunders();
        $this->setVerticals();
        $this->getRecent();
    }

    public function setFunders() {
    	$this->funders = new PostQuery(
            ['post_type' => 'funders', 'posts_per_page' => -1, 'orderby' => 'menu_order', 'order' => 'ASC'],
            'Engage\Models\Funder'
        );
    }

    // Function to get the most recent posts from each vertical
    public function getRecent(){
        // Get an array of all of the verticals
        $verticals = $this->Query->getVerticals();

        $this->recent = []; // Set to an empty array to allow array_merge

        // Loop through each of the verticals
        for ($i = 0; $i < count($verticals); $i++) {

            $verticalName = $verticals[$i]->slug;

            // Get the most recent post for that specific vertical
            $tempArray = $this->Query->getRecentPosts([
                'postType' => 'any',
                'vertical' => $verticalName,
                'postsPerPage' => 1,
            ]);

            // Merge the new post with the existing ones
            // (Maybe there's a more efficient way to do this?)
            $this->recent = array_merge($tempArray, $this->recent);
        }

    }

    public function setVerticals() {
        $this->verticals = $this->Query->getVerticals();
    }


}
