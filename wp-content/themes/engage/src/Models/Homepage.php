<?php
namespace Engage\Models;
use Engage\Managers\Queries as Queries;
use Timber\PostQuery;
use Timber\Post;
use \WP_Query;


class Homepage extends Post {

	public  $funders,
            $verticals,
            $Query,
            $recent,
            $moreRecent,
			$numFeaturedPerVertical = [
				"journalism" => 4,
				"media-ethics" => 2,
				"science-communication" => 2
			];

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
        $this->moreRecent = [];

        // Loop through each of the verticals
		foreach ($verticals as $vertical) {
			$verticalName = $vertical->slug;
             // Get the most recent post and moreResearch posts for that specific vertical
            $this->recent = array_merge($this->getRecentFeaturedResearch($verticalName), $this->recent);
            $this->moreRecent = array_merge($this->getMoreRecentResearch($verticalName), $this->moreRecent);
		}
        // sort the posts by their time
        $this->sortByDate($this->recent);
        $this->sortByDate($this->moreRecent);
    }

    //get the most recent featured research
    public function getRecentFeaturedResearch($verticalName){
        $featuredPosts = $this->queryPosts($verticalName, true);
        $recentFeaturedPosts = array();
		if(count($featuredPosts) > 0){
			array_push($recentFeaturedPosts, $featuredPosts[0]);
		}
        return $recentFeaturedPosts;
    }

    //get the more research posts
	public function getMoreRecentResearch($verticalName){
        // how many more_research_posts should be display on the home page for each vertical
		$postIDArray = $this->postToIDArray($this->recent);
		// console_log($postIDArray);
        $morePostsPerVertical = $this->queryPosts($verticalName, false, $postIDArray);
        return $morePostsPerVertical;
	}


    // query the posts with the given arguments
    public function queryPosts($verticalName, $is_featured=false, $id_posts=[]){
        $args = [
            'postType' => 'research',
            'vertical' => $verticalName,
            'postsPerPage' => strval($this->numFeaturedPerVertical[$verticalName]),
			'post__not_in' => $id_posts
        ];
        if($is_featured){
            // add extraQuery if want to get only posts that are marked by the admin to "show"
            // in the featured_research custom field
			$args['postsPerPage'] = '1';
            $args['extraQuery'] = [
                'meta_query' => [
                    'relation' => 'OR',
                    [
                        'key' => 'featured_research',
                        'value' => serialize(array('Show')),
                        'compare' => 'LIKE'
                    ],
                    [
                        'key' => 'featured_research',
                        'value' => serialize(array('Showpost')),
                        'compare' => 'LIKE'
                    ]
                ],
            ];
        }
        return $this->Query->getRecentPosts($args);
    }

    public function setVerticals() {
        $this->verticals = $this->Query->getVerticals();
    }

	// sort by the date
	public function sortByDate($posts){
		usort($posts, function($a, $b){
			return strtotime($b->post_date) - strtotime($a->post_date);
		});
	}

	public function postToIDArray($posts){
		$idArray = [];
		foreach($posts as $post){
			array_push($idArray, $post->id);
		}
		return $idArray;
	}

}
