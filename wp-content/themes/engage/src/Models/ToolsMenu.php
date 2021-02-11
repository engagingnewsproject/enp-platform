<?php
namespace Engage\Models;
use Timber\PostQuery;

function console_log( $data ){
  echo '<script>';
  echo 'console.log('. json_encode( $data ) .')';
  echo '</script>';
}

class ToolsMenu extends Article {

	  public $video = false,
				 $directors = false,
				 $menulinks = false;

	  public function __construct($pid = null) {
        parent::__construct($pid);
    }

		public function getMenuLinks() {
				if($this->menulinks === false) {
						$menu_items = get_field('vertical_menu_links');
						foreach($menu_items as $item) {
								$split = explode(": ", $item['menu_item']);
								$this->menulinks[$split[0]] = $split[1];
						}
				}
				console_log($this->menulinks);
				return $this->menulinks;
		}
}
