<?php
namespace Engage\Models;
use Engage\Managers\Queries as Queries;
use Timber\PostQuery;
use Timber\Post;

class Homepage extends Post {

	public $funders,
           $verticals,
           $Query;

	public function __construct($pid = null)
    {
        parent::__construct($pid);
        $this->Query = new Queries();
        $this->setFunders();
        $this->setVerticals();
        $this->research = $this->Query->getRecentPosts([
            'postType'      => 'research',
            'postsPerPage'  => 6
        ]);
        $this->caseStudies = $this->Query->getRecentPosts([
            'postType'      => 'case-study',
            'postsPerPage'  => 6
        ]);
        $this->announcements = $this->Query->getRecentPosts([
            'postType'      => 'announcement',
            'postsPerPage'  => 6
        ]);
    }

    public function setFunders() {
    	$this->funders = new PostQuery(
            ['post_type' => 'funders', 'posts_per_page' => -1, 'orderby' => 'menu_order', 'order' => 'ASC'],
            'Engage\Models\Funder'
        );
    }

    public function setVerticals() {
        $verticals = $this->Query->getVerticals();
    }


}