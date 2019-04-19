<?php
namespace Engage\Models;
use Engage\Managers\Queries as Queries;
use Timber\PostQuery;
use Timber\Post;

class About extends Post {

	public  $funders, $Query;

	public function __construct($pid = null)
    {
        parent::__construct($pid);
        $this->Query = new Queries();
        $this->setFunders();
    }

    public function setFunders() {
    	$this->funders = new PostQuery(
            ['post_type' => 'funders', 'posts_per_page' => -1, 'orderby' => 'menu_order', 'order' => 'ASC'],
            'Engage\Models\Funder'
        );
    }
}

?>
