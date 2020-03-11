<?php
namespace Engage\Models;

use Engage\Managers\Queries as Queries;
use Timber\PostQuery;
use Timber\Post;
use \WP_Query;

function console_log($output, $with_script_tags = true)
{
    $js_code = 'console.log(' . json_encode($output, JSON_HEX_TAG) .
');';
    if ($with_script_tags) {
        $js_code = '<script>' . $js_code . '</script>';
    }
    echo $js_code;
}

class Homepage extends Post
{
    public $funders;
    public $verticals;
    public $Query;
    public $recent;
    public $moreRecent;

    public function __construct($pid = null)
    {
        parent::__construct($pid);
        $this->Query = new Queries();
        $this->setFunders();
        $this->setVerticals();
        $this->getRecent();
    }

    public function setFunders()
    {
        $this->funders = new PostQuery(
            ['post_type' => 'funders', 'posts_per_page' => -1, 'orderby' => 'menu_order', 'order' => 'ASC'],
            'Engage\Models\Funder'
        );
    }

    // Function to get the most recent posts from each vertical
    public function getRecent()
    {
        // Get an array of all of the verticals
        $verticals = $this->Query->getVerticals();

        $this->recent = []; // Set to an empty array to allow array_merge
        $this->moreRecent = [];

        foreach ($verticals as $vertical) {
            $verticalName = $vertical->slug;
            // Get the most recent post and moreResearch posts for that specific vertical
            $this->recent = array_merge($this->getRecentFeaturedResearch($verticalName), $this->recent);
            $this->moreRecent = array_merge($this->moreRecent, $this->getMoreRecentResearch($this->recent, $verticalName));
        }

        // $this->sortByDate(true);
        $this->sortByDate(false);

        $this->sortSliderByTopFeatured();
    }

    //get the most recent featured research
    public function getRecentFeaturedResearch($verticalName)
    {
        $featuredPosts = $this->queryPosts(true, $verticalName, 1);
        $recentFeaturedPosts = array();
        //only show one featured research per vertical;
        foreach ($featuredPosts as $featurePost) {
            array_push($recentFeaturedPosts, $featurePost);
            break;
        }
        return $recentFeaturedPosts;
    }

    //get the more research posts
    public function getMoreRecentResearch($featuredSliderPosts, $verticalName)
    {
        // how many more_research_posts should be display on the home page for each vertical
        $numFeaturedPerVertical = [
            "journalism" => 4,
        ];

        $num_posts = array_key_exists($verticalName, $numFeaturedPerVertical) ?
                    $numFeaturedPerVertical[$verticalName] : 1;

        $allRecentResearch = $this->queryPosts(false, $verticalName, $num_posts);
        return $allRecentResearch;
    }

    // query the posts with the given arguments
    public function queryPosts($is_featured, $verticalName, $numberOfPosts)
    {
        $args = [
            'postType' => 'research',
            'vertical' => $verticalName,
             'postsPerPage' => $numberOfPosts,
             'post__not_in' => array_map(function ($post) {
                 return $post->id;
             }, $this->recent)
        ];
        if ($is_featured) {
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
    public function sortByDate($is_slider)
    {
        if ($is_slider) {
            usort($this->recent, function ($a, $b) {
                return strtotime($b->post_date) - strtotime($a->post_date);
            });
        } else {
            usort(
                $this->moreRecent,
                function ($a, $b) {
                    return strtotime($b->post_date) - strtotime($a->post_date);
                }
            );
        }
    }


    public function sortSliderByTopFeatured()
    {
        usort($this->recent, function ($a, $b) {
            return strcmp($b->top_featured_research, $a->top_featured_research);
        });
    }

    public function setVerticals()
    {
        $this->verticals = $this->Query->getVerticals();
    }
}
