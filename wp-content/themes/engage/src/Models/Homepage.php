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
            $moreRecent;

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

		foreach($verticals as $vertical){
			$verticalName = $vertical->slug;
			 // Get the most recent post and moreResearch posts for that specific vertical
			$this->recent = array_merge($this->getRecentFeaturedResearch($verticalName), $this->recent);
			$this->moreRecent = array_merge($this->getMoreRecentResearch($this->recent, $verticalName), $this->moreRecent);
		}

        // sort the posts by their time
        $this->sortByDate($this->recent);
        $this->sortByDate($this->moreRecent);
    }

    //get the most recent featured research
    public function getRecentFeaturedResearch($verticalName){
        $featuredPosts = $this->queryPosts(true, $verticalName);
        $recentFeaturedPosts = array();
        //only show one featured research per vertical;
		foreach($featuredPosts as $featurePost){
			array_push($recentFeaturedPosts, $featurePost);
			break;
		}
        return $recentFeaturedPosts;
    }

    //get the more research posts
	public function getMoreRecentResearch($featuredSliderPosts, $verticalName){
        // how many more_research_posts should be display on the home page for each vertical
        $numFeaturedPerVertical = [
            "journalism" => 2,
            "media-ethics" => 2,
			"social-platforms" => 2,
            "science-communication" => 2
        ];
        $allRecentResearch = $this->queryPosts(false, $verticalName);
        $moreRecentResearch = array();
        for($i = 0; $i<count($allRecentResearch) && $numFeaturedPerVertical[$verticalName] > 0; $i++){
            $curr = $allRecentResearch[$i];
            // make sure not already displaying the post, and that we haven't used up the allotted num of posts
            if($this->notInSlider($curr, $featuredSliderPosts)){
                $numFeaturedPerVertical[$verticalName]--;
                array_push($moreRecentResearch, $curr);
            }
        }
        return $moreRecentResearch;
	}

    // check if a post with the same id as curr is not on the slider
    public function notInSlider($curr, $featuredSliderPosts){
		foreach($featuredSliderPosts as $featurePost){
			// posts with same id are the same post
            if($curr->id == $featurePost->id){
                return false;
            }
		}
        return true;
    }

    // query the posts with the given arguments
    public function queryPosts($is_featured, $verticalName){
		$posts = ($is_featured ? '1' : '4'); // 4 featured posts (1 for each vertical), 4 otherwise to account for if already used up in featured posts (and want to display more than 2)
        $args = [
            'postType' => 'research',
            'vertical' => $verticalName,
			 'postsPerPage' => $posts
        ];
        if($is_featured){
            // add extraQuery if want to get only posts that are marked by the admin to "show"
            // in the featured_research custom field
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

    // sort by the date
    public function sortByDate($posts){
        usort($posts, function($a, $b){
            return strtotime($b->post_date) - strtotime($a->post_date);
        });
    }

    public function setVerticals() {
        $this->verticals = $this->Query->getVerticals();
    }





}
